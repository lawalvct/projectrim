<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;

class AdminNewsletterController extends Controller
{
    public function subscribers()
    {
        $subscribers = NewsletterSubscriber::latest()->paginate(30);

        return view('admin.newsletter.subscribers', compact('subscribers'));
    }

    public function destroySubscriber(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
