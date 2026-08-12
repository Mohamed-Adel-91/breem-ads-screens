<?php

namespace Tests\Feature\Admin;

use App\Enums\AdStatus;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\Admin;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use App\Support\CreativeMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 13 — the ad creative replacement lifecycle.
 *
 * The filesystem is not transactional, so the safety here is ordering plus explicit
 * compensation, not a rollback:
 *
 *   upload new  ->  probe  ->  DB transaction  ->  delete the replaced file
 *                     |             |
 *                     +-------------+---->  discard the new file, keep the old one
 *
 * `AdController::update()` previously did neither half: a failed probe returned
 * early leaving the new upload orphaned, and a *successful* replacement never called
 * commitReplacedFiles(), so every superseded creative stayed on disk forever.
 *
 * All writes land in the per-test temporary upload root from Tests\TestCase, so
 * nothing here touches the real public/ tree.
 */
class AdCreativeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.approve'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $this->admin = Admin::create([
            'first_name' => 'Creative',
            'last_name' => 'Tester',
            'email' => 'creative-lifecycle@example.com',
            'password' => 'password',
            'mobile' => '9200000001',
        ]);
        $this->admin->givePermissionTo(['ads.view', 'ads.create', 'ads.edit', 'ads.delete', 'ads.approve']);

        $this->owner = User::factory()->create();

        // ffprobe is not available in CI, and the tests that need a probe failure say
        // so explicitly.
        config(['ads.try_ffprobe' => false]);
    }

    // ------------------------------------------------------------------- helpers

    private function jpeg(string $name = 'creative.jpg', int $kilobytes = 40): UploadedFile
    {
        return UploadedFile::fake()->image($name, 640, 480)->size($kilobytes);
    }

    /**
     * A file whose *contents* are an MP4 — the client name is deliberately variable
     * so the tests can prove detection ignores it.
     */
    private function video(string $name = 'creative.mp4', int $kilobytes = 128): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'video/mp4');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => ['en' => 'Creative Campaign'],
            'description' => ['en' => 'Body copy'],
            'created_by' => $this->owner->id,
            'duration_seconds' => 15,
        ], $overrides);
    }

    private function makeAd(array $overrides = []): Ad
    {
        return Ad::create(array_merge([
            'title' => ['en' => 'Existing Campaign'],
            'file_path' => 'upload/ads/old.mp4',
            'file_type' => 'video',
            'duration_seconds' => 30,
            'status' => AdStatus::Pending,
            'created_by' => $this->owner->id,
        ], $overrides));
    }

    /**
     * Put a real file on disk at the ad's stored path, so "was the old file kept?"
     * is a question about bytes rather than about a database string.
     */
    private function placeCreativeOnDisk(Ad $ad): string
    {
        $absolute = $this->uploadPath($ad->file_path);

        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, 'original-bytes');

        return $absolute;
    }

    /**
     * A genuine upload carrying real bytes, with a client filename and client MIME
     * type that may be lies.
     *
     * `UploadedFile::fake()` cannot be used for the spoofing tests: it fabricates
     * `getMimeType()` from the filename, which is precisely the input under test.
     * Writing real bytes and constructing the UploadedFile directly makes
     * `getMimeType()` sniff the file with finfo — the same call the production path
     * makes.
     */
    private function realUpload(string $clientName, string $bytes, string $claimedMime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'breem-creative-');
        file_put_contents($path, $bytes);

        // $test = true keeps isValid() true without an actual multipart request.
        return new UploadedFile($path, $clientName, $claimedMime, null, true);
    }

    /**
     * The smallest byte sequence finfo recognises as a GIF.
     */
    private function gifBytes(): string
    {
        return "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!"
            ."\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00"
            ."\x00\x02\x02D\x01\x00;";
    }

    /**
     * An `ftyp` box that finfo reports as video/mp4.
     */
    private function mp4Bytes(): string
    {
        return "\x00\x00\x00\x20ftypisom\x00\x00\x02\x00isomiso2avc1mp41"
            .str_repeat("\x00", 64);
    }

    /**
     * Make the next query containing all of the given fragments throw, to stand in
     * for a database failure mid-write.
     *
     * The fragments are matched against the SQL with identifier quoting stripped, so
     * the same expectation works whichever driver the suite runs on.
     */
    private function failQueriesMatching(string ...$fragments): void
    {
        DB::listen(function ($query) use ($fragments): void {
            $sql = strtolower(str_replace(['`', '"', '[', ']'], '', $query->sql));

            foreach ($fragments as $fragment) {
                if (! str_contains($sql, strtolower($fragment))) {
                    return;
                }
            }

            throw new \RuntimeException('simulated database failure');
        });
    }

    /**
     * @return array<int, string>
     */
    private function storedCreatives(): array
    {
        $directory = $this->uploadPath(Ad::UPLOAD_FOLDER);

        if (! is_dir($directory)) {
            return [];
        }

        return array_values(array_diff(scandir($directory) ?: [], ['.', '..']));
    }

    // -------------------------------------------------------------------- create

    public function test_a_valid_image_is_stored_and_typed_from_its_contents(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => $this->jpeg(),
            ]))
            ->assertRedirect();

        $ad = Ad::firstOrFail();

        $this->assertSame(CreativeMedia::CATEGORY_IMAGE, $ad->file_type);
        $this->assertFileExists($this->uploadPath($ad->file_path));
        $this->assertStringEndsWith('.jpg', $ad->file_path);
    }

    public function test_a_valid_video_is_stored_and_typed_from_its_contents(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => $this->video(),
                'duration_seconds' => 42,
            ]))
            ->assertRedirect();

        $ad = Ad::firstOrFail();

        $this->assertSame(CreativeMedia::CATEGORY_VIDEO, $ad->file_type);
        $this->assertSame(42, $ad->duration_seconds);
        $this->assertFileExists($this->uploadPath($ad->file_path));
    }

    /**
     * The stored extension and the recorded type both come from the file's contents,
     * never from the client filename.
     *
     * This is the concrete defect: an MP4 named `holiday.jpg` used to be written to
     * disk as `<random>.jpg` and recorded with `file_type = image`, so the player was
     * handed a video it had been told was a still.
     */
    public function test_a_spoofed_extension_decides_neither_the_stored_name_nor_the_type(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => $this->realUpload('holiday.jpg', $this->mp4Bytes(), 'image/jpeg'),
                'duration_seconds' => 18,
            ]))
            ->assertRedirect();

        $ad = Ad::firstOrFail();

        $this->assertSame(CreativeMedia::CATEGORY_VIDEO, $ad->file_type);
        $this->assertStringEndsWith('.mp4', $ad->file_path);
        $this->assertStringNotContainsString('.jpg', $ad->file_path);
    }

    /**
     * A GIF named to look like a script never reaches the disk at all: Laravel's own
     * `mimetypes` rule refuses php-family client extensions before the upload is
     * moved. Worth pinning — it is a second, independent layer under the derived
     * extension above, and a future rule rewrite must not drop it.
     */
    public function test_a_php_client_filename_is_refused_outright(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => $this->realUpload('payload.php', $this->gifBytes(), 'image/gif'),
            ]))
            ->assertSessionHasErrors('creative');

        $this->assertSame(0, Ad::count());
        $this->assertSame([], $this->storedCreatives());
    }

    /**
     * The inverse: a script renamed to look like an image is refused outright, and
     * nothing is written.
     */
    public function test_an_executable_disguised_as_an_image_is_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                // A PHP script named and declared as a JPEG. The declared type is
                // ignored; the contents are what fail validation.
                'creative' => $this->realUpload(
                    'shell.jpg',
                    "<?php echo shell_exec(\$_GET['c']); ?>",
                    'image/jpeg'
                ),
            ]))
            ->assertSessionHasErrors('creative');

        $this->assertSame(0, Ad::count());
        $this->assertSame([], $this->storedCreatives());
    }

    public function test_an_unsupported_media_type_is_rejected(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('creative');

        $this->assertSame(0, Ad::count());
        $this->assertSame([], $this->storedCreatives());
    }

    public function test_an_oversized_image_is_rejected_against_the_image_limit(): void
    {
        config(['ads.upload.image_max_kb' => 100, 'ads.upload.video_max_kb' => 10000]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                // Comfortably under the video ceiling, over the image one.
                'creative' => $this->jpeg('big.jpg', 500),
            ]))
            ->assertSessionHasErrors('creative');

        $this->assertSame(0, Ad::count());
        $this->assertSame([], $this->storedCreatives());
    }

    public function test_an_oversized_video_is_rejected(): void
    {
        config(['ads.upload.video_max_kb' => 200]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => $this->video('big.mp4', 900),
            ]))
            ->assertSessionHasErrors('creative');

        $this->assertSame(0, Ad::count());
    }

    public function test_a_video_within_its_own_larger_limit_is_accepted(): void
    {
        config(['ads.upload.image_max_kb' => 100, 'ads.upload.video_max_kb' => 10000]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                // Far over the image ceiling; the per-category check must use the
                // video one.
                'creative' => $this->video('fine.mp4', 800),
                'duration_seconds' => 20,
            ]))
            ->assertRedirect();

        $this->assertSame(1, Ad::count());
    }

    public function test_a_failed_probe_on_create_leaves_no_orphan_file(): void
    {
        // Probing enabled, but pointed at a binary that does not exist, so the probe
        // returns null exactly as a missing/broken ffprobe would.
        config(['ads.try_ffprobe' => true, 'ads.ffprobe_bin' => 'definitely-not-ffprobe-13']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ads.store', ['lang' => 'en']), $this->payload([
                'creative' => $this->video(),
                // No usable duration, so the probe is required.
                'duration_seconds' => 0,
            ]))
            ->assertSessionHasErrors('duration_seconds');

        $this->assertSame(0, Ad::count(), 'No ad may be written with an unknown duration.');
        $this->assertSame(
            [],
            $this->storedCreatives(),
            'A failed probe must not leave the uploaded candidate behind.'
        );
    }

    public function test_a_failed_database_write_on_create_leaves_no_orphan_file(): void
    {
        // A screen id that passes validation, then a rolled-back transaction.
        $screen = Screen::factory()->create([
            'place_id' => Place::factory()->create()->id,
            'code' => 'SCR-CREATE-FAIL',
            'status' => ScreenStatus::Online->value,
        ]);

        // Identifier quoting differs between drivers, so match on the bare table
        // name rather than on a backticked or double-quoted form.
        $this->failQueriesMatching('insert into', 'ad_screen');

        try {
            $this->actingAs($this->admin, 'admin')->post(
                route('admin.ads.store', ['lang' => 'en']),
                $this->payload([
                    'creative' => $this->jpeg(),
                    'screens' => [$screen->id],
                ])
            );
        } catch (\Throwable) {
            // The controller rethrows after cleaning up; the cleanup is the subject.
        }

        $this->assertSame(0, Ad::count(), 'The transaction must have rolled the ad back.');
        $this->assertSame(
            [],
            $this->storedCreatives(),
            'A failed database write must discard the uploaded creative.'
        );
    }

    // -------------------------------------------------------------------- update

    public function test_a_successful_replacement_stores_the_new_file_and_removes_the_old(): void
    {
        $ad = $this->makeAd();
        $oldAbsolute = $this->placeCreativeOnDisk($ad);
        $oldPath = $ad->file_path;

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]), $this->payload([
                'creative' => $this->jpeg('replacement.jpg'),
            ]))
            ->assertRedirect();

        $ad->refresh();

        $this->assertNotSame($oldPath, $ad->file_path, 'The ad must point at the new creative.');
        $this->assertFileExists($this->uploadPath($ad->file_path));
        $this->assertFileDoesNotExist($oldAbsolute, 'The replaced creative must be removed once committed.');
        $this->assertCount(1, $this->storedCreatives());
    }

    public function test_a_failed_probe_on_update_preserves_the_old_creative(): void
    {
        config(['ads.try_ffprobe' => true, 'ads.ffprobe_bin' => 'definitely-not-ffprobe-13']);

        $ad = $this->makeAd();
        $oldAbsolute = $this->placeCreativeOnDisk($ad);
        $oldPath = $ad->file_path;

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]), $this->payload([
                'creative' => $this->video('new.mp4'),
                'duration_seconds' => 0,
            ]))
            ->assertSessionHasErrors('duration_seconds');

        $ad->refresh();

        $this->assertSame($oldPath, $ad->file_path, 'The database must still point at the old creative.');
        $this->assertFileExists($oldAbsolute, 'The old file must survive a failed probe.');
        $this->assertSame(
            [basename($oldPath)],
            $this->storedCreatives(),
            'Only the old creative may remain; the new candidate is discarded.'
        );
    }

    public function test_a_failed_database_write_on_update_preserves_the_old_creative(): void
    {
        $ad = $this->makeAd();
        $oldAbsolute = $this->placeCreativeOnDisk($ad);
        $oldPath = $ad->file_path;

        $this->failQueriesMatching('update', 'ads');

        try {
            $this->actingAs($this->admin, 'admin')->put(
                route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]),
                $this->payload(['creative' => $this->jpeg('doomed.jpg')])
            );
        } catch (\Throwable) {
            // Expected: the controller discards the upload and rethrows.
        }

        $ad->refresh();

        $this->assertSame($oldPath, $ad->file_path);
        $this->assertFileExists($oldAbsolute, 'A rolled-back update must never lose the working creative.');
        $this->assertSame([basename($oldPath)], $this->storedCreatives());
    }

    public function test_an_update_without_a_new_file_leaves_the_creative_untouched(): void
    {
        $ad = $this->makeAd();
        $oldAbsolute = $this->placeCreativeOnDisk($ad);
        $oldPath = $ad->file_path;

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]), $this->payload([
                'title' => ['en' => 'Renamed only'],
                'duration_seconds' => 30,
            ]))
            ->assertRedirect();

        $ad->refresh();

        $this->assertSame($oldPath, $ad->file_path);
        $this->assertSame('video', $ad->file_type);
        $this->assertFileExists($oldAbsolute);
        $this->assertSame([basename($oldPath)], $this->storedCreatives());
    }

    public function test_replacing_a_video_with_an_image_retypes_the_ad(): void
    {
        $ad = $this->makeAd();
        $this->placeCreativeOnDisk($ad);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.ads.update', ['lang' => 'en', 'ad' => $ad->id]), $this->payload([
                'creative' => $this->jpeg('now-a-still.jpg'),
                'duration_seconds' => 12,
            ]))
            ->assertRedirect();

        $this->assertSame(CreativeMedia::CATEGORY_IMAGE, $ad->fresh()->file_type);
    }

    // -------------------------------------------------------------------- delete

    public function test_deleting_an_ad_removes_the_creative_it_owned(): void
    {
        $ad = $this->makeAd();
        $absolute = $this->placeCreativeOnDisk($ad);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.ads.destroy', ['lang' => 'en', 'ad' => $ad->id]))
            ->assertRedirect();

        $this->assertNull($ad->fresh());
        $this->assertFileDoesNotExist($absolute);
    }

    /**
     * A path can be referenced by more than one ad — a duplicated row, or a shared
     * seeded asset. Unlinking it would break the other ad's playback, so the file
     * stays.
     */
    public function test_deleting_an_ad_never_removes_a_creative_another_ad_still_uses(): void
    {
        $ad = $this->makeAd();
        $sibling = $this->makeAd(['title' => ['en' => 'Shares the same creative']]);
        $absolute = $this->placeCreativeOnDisk($ad);

        $this->assertSame($ad->file_path, $sibling->file_path);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.ads.destroy', ['lang' => 'en', 'ad' => $ad->id]))
            ->assertRedirect();

        $this->assertNull($ad->fresh());
        $this->assertNotNull($sibling->fresh());
        $this->assertFileExists($absolute, 'A creative still referenced by another ad must not be deleted.');
    }
}
