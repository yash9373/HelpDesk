<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class RegisterRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_as_agent_via_role_field()
    {
        $resp = $this->post(route('register'), [
            'name' => 'Agent User',
            'email' => 'agent@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'agent',
        ]);

        $resp->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'agent@example.test', 'role' => 'agent']);
    }

    public function test_invalid_role_is_rejected()
    {
        $resp = $this->post(route('register'), [
            'name' => 'Bad Role',
            'email' => 'badrole@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'superuser',
        ]);

        $resp->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'badrole@example.test']);
    }
}
