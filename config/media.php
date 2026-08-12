<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Root
    |--------------------------------------------------------------------------
    |
    | Absolute filesystem directory that managed uploads (CMS media, ad
    | creatives) are written to and deleted from. Stored database paths stay
    | relative to this root, and public URLs are still built with asset(), so
    | changing this value only moves the physical write location.
    |
    | Leave it null to keep the historical behaviour: uploads live under the
    | application's public/ directory. The test suite overrides it with a
    | temporary directory so running tests never touches real media.
    |
    */

    'upload_root' => env('MEDIA_UPLOAD_ROOT'),

];
