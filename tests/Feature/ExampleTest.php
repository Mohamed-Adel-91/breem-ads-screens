<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * `/` intentionally redirects to the locale-prefixed home page, so the
     * default skeleton expectation of a 200 never matched this application.
     */
    public function test_the_root_url_redirects_to_the_current_locale(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/' . app()->getLocale());
    }
}
