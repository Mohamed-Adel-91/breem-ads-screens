<?php

namespace Tests\Unit\Models;

use App\Enums\AdStatus;
use App\Models\Ad;
use App\Models\User;
use App\Support\CreativeMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 13 — the ad status map, the media map, and Ad's mass-assignment surface.
 *
 * These assert the rules themselves rather than their HTTP behaviour, so a change to
 * the map is caught even if no controller path happens to exercise it.
 */
class AdLifecycleTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------ transition map

    /**
     * Only Active plays. The scheduler tests `status === AdStatus::Active`, so this
     * is the one place that answers "live?" for presentation and it must agree.
     */
    public function test_only_the_active_status_is_live(): void
    {
        foreach (AdStatus::cases() as $status) {
            $this->assertSame(
                $status === AdStatus::Active,
                $status->isLive(),
                "isLive() is wrong for [{$status->value}]."
            );
        }
    }

    public function test_only_approved_and_active_count_as_reviewed(): void
    {
        $this->assertTrue(AdStatus::Approved->isReviewed());
        $this->assertTrue(AdStatus::Active->isReviewed());

        $this->assertFalse(AdStatus::Pending->isReviewed());
        $this->assertFalse(AdStatus::Rejected->isReviewed());
        $this->assertFalse(AdStatus::Expired->isReviewed());
    }

    /**
     * The complete declared map, written out longhand. If an edge is added or removed
     * this fails, which is the point: a transition must be a deliberate decision.
     */
    public function test_the_transition_map_is_exactly_as_declared(): void
    {
        $expected = [
            'pending' => ['approve' => 'approved', 'reject' => 'rejected'],
            'approved' => ['publish' => 'active', 'reject' => 'rejected', 'expire' => 'expired'],
            'rejected' => ['approve' => 'approved'],
            'active' => ['unpublish' => 'approved', 'reject' => 'rejected', 'expire' => 'expired'],
            'expired' => ['approve' => 'approved'],
        ];

        foreach (AdStatus::cases() as $status) {
            $actual = array_map(
                fn (AdStatus $target) => $target->value,
                $status->allowedTransitions()
            );

            $this->assertSame(
                $expected[$status->value],
                $actual,
                "The edges from [{$status->value}] changed."
            );
        }
    }

    public function test_pending_has_no_direct_route_to_live(): void
    {
        $this->assertFalse(AdStatus::Pending->allows(AdStatus::ACTION_PUBLISH));
        $this->assertNull(AdStatus::Pending->resultOf(AdStatus::ACTION_PUBLISH));

        // The only way to Active is via Approved.
        $this->assertSame(AdStatus::Approved, AdStatus::Pending->resultOf(AdStatus::ACTION_APPROVE));
        $this->assertSame(AdStatus::Active, AdStatus::Approved->resultOf(AdStatus::ACTION_PUBLISH));
    }

    public function test_an_unknown_action_never_resolves_to_a_status(): void
    {
        foreach (AdStatus::cases() as $status) {
            $this->assertFalse($status->allows('promote'));
            $this->assertNull($status->resultOf('promote'));
        }
    }

    /**
     * Every terminal-looking status still has a way back, so no ad can be stranded.
     */
    public function test_no_status_is_a_dead_end(): void
    {
        foreach (AdStatus::cases() as $status) {
            $this->assertNotEmpty(
                $status->allowedTransitions(),
                "[{$status->value}] has no outgoing edge and would strand an ad."
            );
        }
    }

    // ------------------------------------------------------------------ media map

    public function test_the_media_category_comes_from_the_mime_type(): void
    {
        $this->assertSame(CreativeMedia::CATEGORY_VIDEO, CreativeMedia::category('video/mp4'));
        $this->assertSame(CreativeMedia::CATEGORY_GIF, CreativeMedia::category('image/gif'));
        $this->assertSame(CreativeMedia::CATEGORY_IMAGE, CreativeMedia::category('image/png'));
        $this->assertSame(CreativeMedia::CATEGORY_IMAGE, CreativeMedia::category('image/jpeg'));

        $this->assertNull(CreativeMedia::category('application/pdf'));
        $this->assertNull(CreativeMedia::category('text/x-php'));
        $this->assertNull(CreativeMedia::category(null));
    }

    /**
     * Every category the map produces must be storable in the `file_type` enum
     * column, or a valid upload would fail at the database.
     */
    public function test_every_category_is_a_valid_file_type_column_value(): void
    {
        $columnValues = ['video', 'image', 'gif'];

        foreach (CreativeMedia::allowedMimeTypes() as $mimeType) {
            $this->assertContains(
                CreativeMedia::category($mimeType),
                $columnValues,
                "[{$mimeType}] maps to a category the ads.file_type column cannot hold."
            );
        }
    }

    /**
     * Every accepted type must have a stored extension, otherwise the derived
     * extension would fall back to null and the file would land with no suffix.
     */
    public function test_every_accepted_mime_type_has_a_stored_extension(): void
    {
        foreach (CreativeMedia::allowedMimeTypes() as $mimeType) {
            $extension = CreativeMedia::extensionFor($mimeType);

            $this->assertNotNull($extension, "[{$mimeType}] has no stored extension.");
            $this->assertDoesNotMatchRegularExpression(
                '/^(php|phtml|phar|html?|svg)$/i',
                $extension,
                "[{$mimeType}] would be stored with an executable or scriptable extension."
            );
        }
    }

    public function test_only_video_requires_a_probed_duration(): void
    {
        $this->assertTrue(CreativeMedia::requiresProbedDuration(CreativeMedia::CATEGORY_VIDEO));
        $this->assertFalse(CreativeMedia::requiresProbedDuration(CreativeMedia::CATEGORY_IMAGE));
        $this->assertFalse(CreativeMedia::requiresProbedDuration(CreativeMedia::CATEGORY_GIF));
    }

    public function test_size_limits_are_config_backed_and_per_category(): void
    {
        config([
            'ads.upload.image_max_kb' => 111,
            'ads.upload.gif_max_kb' => 222,
            'ads.upload.video_max_kb' => 333,
        ]);

        $this->assertSame(111, CreativeMedia::maxKilobytes(CreativeMedia::CATEGORY_IMAGE));
        $this->assertSame(222, CreativeMedia::maxKilobytes(CreativeMedia::CATEGORY_GIF));
        $this->assertSame(333, CreativeMedia::maxKilobytes(CreativeMedia::CATEGORY_VIDEO));
        $this->assertSame(333, CreativeMedia::absoluteMaxKilobytes());
    }

    // ----------------------------------------------------------- mass assignment

    public function test_the_ad_model_declares_an_explicit_fillable_list(): void
    {
        $ad = new Ad();

        $this->assertNotEmpty($ad->getFillable(), 'Ad must not fall back to $guarded = [].');

        // Every column a legitimate call site writes.
        foreach ([
            'title', 'description', 'file_path', 'file_type', 'duration_seconds',
            'status', 'created_by', 'created_by_admin_id', 'approved_by',
            'approved_by_admin_id', 'approved_at', 'start_date', 'end_date',
        ] as $attribute) {
            $this->assertContains($attribute, $ad->getFillable(), "[{$attribute}] must stay fillable.");
        }
    }

    public function test_mass_assignment_cannot_reach_the_primary_key_or_timestamps(): void
    {
        $ad = Ad::create([
            'title' => ['en' => 'Guarded'],
            'file_path' => 'upload/ads/guarded.png',
            'file_type' => 'image',
            'duration_seconds' => 5,
            'status' => AdStatus::Pending,
            'created_by' => User::factory()->create()->id,
            // None of these are fillable.
            'id' => 987654,
            'created_at' => '1999-01-01 00:00:00',
            'updated_at' => '1999-01-01 00:00:00',
        ]);

        $this->assertNotSame(987654, $ad->id);
        $this->assertNotSame('1999', $ad->created_at->format('Y'));
    }

    public function test_playback_relevant_attributes_exclude_descriptive_fields(): void
    {
        // Title and description never reach a device, so editing them must not force
        // re-approval.
        $this->assertNotContains('title', Ad::PLAYBACK_RELEVANT_ATTRIBUTES);
        $this->assertNotContains('description', Ad::PLAYBACK_RELEVANT_ATTRIBUTES);

        foreach (['file_path', 'file_type', 'duration_seconds', 'start_date', 'end_date'] as $attribute) {
            $this->assertContains($attribute, Ad::PLAYBACK_RELEVANT_ATTRIBUTES);
        }
    }
}
