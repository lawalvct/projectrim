<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::emailVerification());
});

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});

test('verification notification uses the cross-session signed route', function () {
    $user = User::factory()->unverified()->create();
    $notification = new VerifyEmail;

    $mail = $notification->toMail($user);
    $verificationUrl = $mail->actionUrl;

    expect($verificationUrl)->toContain('/email/complete-verification/'.$user->id.'/');
    expect(URL::hasValidSignature(Request::create($verificationUrl)))->toBeTrue();
});
