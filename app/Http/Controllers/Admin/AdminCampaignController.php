<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminCampaignController extends Controller
{
    public function index()
    {
        $campaigns = NewsletterCampaign::latest()->paginate(20);

        return view('admin.newsletter.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('admin.newsletter.campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'audience' => 'required|in:subscribers,users,sellers,all',
        ]);

        NewsletterCampaign::create($validated);

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign created.');
    }

    public function show(NewsletterCampaign $campaign)
    {
        return view('admin.newsletter.campaigns.show', compact('campaign'));
    }

    public function edit(NewsletterCampaign $campaign)
    {
        return view('admin.newsletter.campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'audience' => 'required|in:subscribers,users,sellers,all',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign updated.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function send(NewsletterCampaign $campaign)
    {
        $emails = collect();

        if (in_array($campaign->audience, ['subscribers', 'all'])) {
            $emails = $emails->merge(NewsletterSubscriber::where('is_active', true)->pluck('email'));
        }

        if (in_array($campaign->audience, ['users', 'all'])) {
            $emails = $emails->merge(User::pluck('email'));
        }

        if (in_array($campaign->audience, ['sellers', 'all'])) {
            $emails = $emails->merge(
                User::where('is_seller_approved', true)->pluck('email')
            );
        }

        $emails = $emails->unique()->values();

        foreach ($emails as $email) {
            Mail::raw($campaign->body, function ($message) use ($email, $campaign) {
                $message->to($email)->subject($campaign->subject);
            });
        }

        $campaign->update([
            'sent_at' => now(),
            'recipients_count' => $emails->count(),
        ]);

        return back()->with('success', "Campaign sent to {$emails->count()} recipients.");
    }
}
