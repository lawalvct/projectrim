<?php

use App\Mail\AuthorReplyNotification;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{author: User, sender: User, product: Product, message: Message, recipient: MessageRecipient}
 */
function createReplyableMessageThread(): array
{
    $author = User::factory()->create(['role' => 'seller']);
    $sender = User::factory()->create();
    $product = Product::create([
        'user_id' => $author->id,
        'title' => 'Replyable Message Product',
        'slug' => 'replyable-message-product-'.fake()->unique()->numerify('###'),
        'status' => 'published',
    ]);
    $message = Message::create([
        'product_id' => $product->id,
        'sender_user_id' => $sender->id,
        'sender_name' => $sender->name,
        'sender_email' => $sender->email,
        'subject' => 'Question about the project',
        'body' => 'Can you tell me more about this project?',
    ]);
    $recipient = MessageRecipient::create([
        'message_id' => $message->id,
        'user_id' => $author->id,
    ]);

    return compact('author', 'sender', 'product', 'message', 'recipient');
}

test('an author can reply and the original sender receives the reply in their inbox and by email', function () {
    Mail::fake();
    config()->set('queue.notifications.connection', 'database');
    config()->set('queue.notifications.queue', 'default');

    ['author' => $author, 'sender' => $sender, 'message' => $message, 'recipient' => $recipient] = createReplyableMessageThread();

    $response = $this->actingAs($author)
        ->postJson(route('dashboard.messages.reply', $recipient), [
            'body' => 'Yes. I have added more details for you.',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Reply sent successfully.')
        ->assertJsonPath('reply.sender_user_id', $author->id)
        ->assertJsonPath('reply.body', 'Yes. I have added more details for you.');

    $replyId = $response->json('reply.id');

    $this->assertDatabaseHas('messages', [
        'id' => $replyId,
        'product_id' => $message->product_id,
        'sender_user_id' => $author->id,
        'parent_message_id' => $message->id,
        'sender_email' => $author->email,
        'body' => 'Yes. I have added more details for you.',
    ]);
    $this->assertDatabaseHas('message_recipients', [
        'message_id' => $message->id,
        'user_id' => $sender->id,
        'is_read' => false,
    ]);

    Mail::assertQueued(AuthorReplyNotification::class, function (AuthorReplyNotification $mail) use ($sender, $replyId) {
        return $mail->hasTo($sender->email)
            && $mail->reply->id === $replyId
            && $mail->connection === 'database'
            && $mail->queue === 'default';
    });
});

test('the original sender sees author replies as a single conversation in their messages dashboard', function () {
    ['author' => $author, 'sender' => $sender, 'message' => $message] = createReplyableMessageThread();

    $reply = Message::create([
        'product_id' => $message->product_id,
        'sender_user_id' => $author->id,
        'parent_message_id' => $message->id,
        'sender_name' => $author->name,
        'sender_email' => $author->email,
        'subject' => $message->subject,
        'body' => 'Here is the information you requested.',
    ]);
    MessageRecipient::create([
        'message_id' => $message->id,
        'user_id' => $sender->id,
    ]);

    $this->actingAs($sender)
        ->get(route('dashboard.messages'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Messages')
            ->where('currentUserId', $sender->id)
            ->has('messages.data', 1)
            ->where('messages.data.0.thread.id', $message->id)
            ->where('messages.data.0.thread.subject', 'Question about the project')
            ->has('messages.data.0.thread.entries', 2)
            ->where('messages.data.0.thread.entries.0.body', 'Can you tell me more about this project?')
            ->where('messages.data.0.thread.entries.1.body', 'Here is the information you requested.')
            ->where('messages.data.0.thread.entries.1.sender_user_id', $author->id)
            ->where('messages.data.0.can_reply', false)
        );
});

test('a recipient who is not a current product author cannot reply', function () {
    ['message' => $message] = createReplyableMessageThread();
    $nonAuthor = User::factory()->create(['role' => 'seller']);
    $nonAuthorRecipient = MessageRecipient::create([
        'message_id' => $message->id,
        'user_id' => $nonAuthor->id,
    ]);

    $this->actingAs($nonAuthor)
        ->postJson(route('dashboard.messages.reply', $nonAuthorRecipient), [
            'body' => 'This reply should not be allowed.',
        ])
        ->assertForbidden();

    expect(Message::where('parent_message_id', $message->id)->exists())->toBeFalse();
});

test('a legacy message without an account-backed sender cannot create an undeliverable dashboard reply', function () {
    $author = User::factory()->create(['role' => 'seller']);
    $product = Product::create([
        'user_id' => $author->id,
        'title' => 'Legacy Message Product',
        'slug' => 'legacy-message-product-'.fake()->unique()->numerify('###'),
        'status' => 'published',
    ]);
    $message = Message::create([
        'product_id' => $product->id,
        'sender_name' => 'Former Sender',
        'sender_email' => 'no-account@example.test',
        'subject' => 'Old question',
        'body' => 'This was sent before accounts were linked.',
    ]);
    $recipient = MessageRecipient::create([
        'message_id' => $message->id,
        'user_id' => $author->id,
    ]);

    $this->actingAs($author)
        ->postJson(route('dashboard.messages.reply', $recipient), [
            'body' => 'A response that cannot be delivered.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');

    expect(Message::where('parent_message_id', $message->id)->exists())->toBeFalse();
});

test('an email queue failure does not turn a saved author reply into a server error', function () {
    config()->set('queue.notifications.connection', 'missing-message-reply-queue');
    ['author' => $author, 'message' => $message, 'recipient' => $recipient] = createReplyableMessageThread();

    $response = $this->actingAs($author)
        ->postJson(route('dashboard.messages.reply', $recipient), [
            'body' => 'This is still delivered in the dashboard.',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Reply sent successfully.');

    $this->assertDatabaseHas('messages', [
        'parent_message_id' => $message->id,
        'sender_user_id' => $author->id,
        'body' => 'This is still delivered in the dashboard.',
    ]);
});
