<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Unauthenticated access to the root route should redirect to /login.
     * (The root dashboard is protected by the auth middleware.)
     */
    public function test_the_application_redirects_unauthenticated_users_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
