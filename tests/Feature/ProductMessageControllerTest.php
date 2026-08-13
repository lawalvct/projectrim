<?php

use App\Mail\NewMessageNotification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function productMessagePayload(User $sender): array
{
    return [
        'sender_name' => $sender->name,
        'sender_email' => $sender->email,
        'subject' => 'Question about this project',
        'body' => 'Please send me some more information about this project.',
        'honeypot' => '',
    ];
}

test('a stored product message returns success and queues its email notification', function () {
    Mail::fake();

    config()->set('queue.notifications.connection', 'database');
    config()->set('queue.notifications.queue', 'default');

    $owner = User::factory()->create(['role' => 'seller']);
    $sender = User::factory()->create();
    $product = Product::create([
        'user_id' => $owner->id,
        'title' => 'Message Test Product',
        'slug' => 'message-test-product',
        'status' => 'published',
    ]);

    $response = $this->actingAs($sender)
        ->postJson(route('products.messages.store', $product), productMessagePayload($sender));

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Your message has been sent successfully.')
        ->assertJsonStructure(['message_id']);

    $messageId = $response->json('message_id');

    $this->assertDatabaseHas('messages', [
        'id' => $messageId,
        'product_id' => $product->id,
        'sender_email' => $sender->email,
    ]);
    $this->assertDatabaseHas('message_recipients', [
        'message_id' => $messageId,
        'user_id' => $owner->id,
    ]);

    Mail::assertQueued(NewMessageNotification::class, function (NewMessageNotification $mail) use ($owner) {
        return $mail->hasTo($owner->email)
            && $mail->connection === 'database'
            && $mail->queue === 'default';
    });
});

test('email queue failure cannot change a stored message into a server error', function () {
    config()->set('queue.notifications.connection', 'missing-message-queue');

    $owner = User::factory()->create(['role' => 'seller']);
    $sender = User::factory()->create();
    $product = Product::create([
        'user_id' => $owner->id,
        'title' => 'Queue Failure Message Product',
        'slug' => 'queue-failure-message-product',
        'status' => 'published',
    ]);

    $response = $this->actingAs($sender)
        ->postJson(route('products.messages.store', $product), productMessagePayload($sender));

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Your message has been sent successfully.');

    $this->assertDatabaseHas('messages', [
        'product_id' => $product->id,
        'sender_email' => $sender->email,
        'subject' => 'Question about this project',
    ]);
    $this->assertDatabaseHas('message_recipients', [
        'message_id' => $response->json('message_id'),
        'user_id' => $owner->id,
    ]);
});
