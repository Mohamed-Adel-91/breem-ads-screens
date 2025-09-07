<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class AuthenticateMiddlewareTest extends TestCase
{
    public function test_user_routes_redirect_to_login(): void
    {
        $response = $this->get(route('auth.password.confirm'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_admin_routes_redirect_to_admin_login(): void
    {
        $response = $this->get(route('admin.index', ['lang' => app()->getLocale()]));

        $response->assertRedirect(route('admin.login', ['lang' => app()->getLocale()]));
    }
}

