<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;

class AdminMessageController extends Controller
{
    public function index()
    {
        $messages = Message::with('product')
            ->latest()
            ->paginate(20);

        return view('admin.messages.index', compact('messages'));
    }

    public function show(Message $message)
    {
        $message->load('product', 'recipients.user');

        return view('admin.messages.show', compact('message'));
    }
}
