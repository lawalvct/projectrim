<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function general()
    {
        $settings = Setting::getGroup('general');

        return view('admin.settings.general', compact('settings'));
    }

    public function monetization()
    {
        $settings = Setting::getGroup('monetization');

        return view('admin.settings.monetization', compact('settings'));
    }

    public function payment()
    {
        $settings = Setting::getGroup('payment');

        return view('admin.settings.payment', compact('settings'));
    }

    public function seller()
    {
        $settings = Setting::getGroup('seller');

        return view('admin.settings.seller', compact('settings'));
    }

    public function carousel()
    {
        $settings = Setting::getGroup('carousel');

        return view('admin.settings.carousel', compact('settings'));
    }

    public function update(Request $request)
    {
        $group = $request->input('group', 'general');

        foreach ($request->input('settings', []) as $key => $value) {
            Setting::setValue($key, $value, $group);
        }

        return back()->with('success', 'Settings saved successfully.');
    }
}
