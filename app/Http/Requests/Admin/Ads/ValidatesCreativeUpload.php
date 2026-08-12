<?php

namespace App\Http\Requests\Admin\Ads;

use App\Support\CreativeMedia;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\UploadedFile;

/**
 * The per-category size ceiling for an ad creative.
 *
 * Laravel's `max:` rule takes a single number, but a 150 MB video allowance must
 * not become a 150 MB allowance for a JPEG. The blanket rule in each Form Request
 * rejects anything above the largest configured limit; this narrows it to the limit
 * for the category the file actually belongs to, detected from its contents.
 *
 * Shared by the store and update requests so the two cannot drift apart.
 */
trait ValidatesCreativeUpload
{
    protected function validateCreativeSize(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Only reachable once the `file`, `mimetypes` and blanket `max` rules
            // have passed, so the category below is always a known one.
            if ($validator->errors()->has('creative')) {
                return;
            }

            $file = $this->file('creative');

            if (! $file instanceof UploadedFile) {
                return;
            }

            $category = CreativeMedia::categoryOf($file);

            if ($category === null) {
                return;
            }

            $maxKilobytes = CreativeMedia::maxKilobytes($category);

            // getSize() is bytes; the configured limits are kilobytes.
            if ($file->getSize() <= $maxKilobytes * 1024) {
                return;
            }

            $validator->errors()->add('creative', __('validation.max.file', [
                'attribute' => __('validation.attributes.creative'),
                'max' => $maxKilobytes,
            ]));
        });
    }
}
