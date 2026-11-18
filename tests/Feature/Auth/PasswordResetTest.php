<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_reset_link_when_email_is_registered(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function it_rejects_reset_link_request_when_email_is_unknown(): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'ghost@example.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_resets_password_when_token_is_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'oldpassword',
            'must_change_password' => true,
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertGuest();

        $login = $this->post(route('login.attempt'), [
            'email' => 'user@example.com',
            'password' => 'newpassword123',
        ]);

        $login->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function it_rejects_password_reset_when_token_is_invalid(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->from(route('password.request'))->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('newpassword123', $user->fresh()->password));
    }

    #[Test]
    public function it_rejects_password_reset_when_new_password_is_too_short(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::createToken($user);

        $response = $this->from(route('password.request'))->post(route('password.store'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse(Hash::check('short', $user->fresh()->password));
    }
}
