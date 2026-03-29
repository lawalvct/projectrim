<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        if ($group === 'carousel') {
            return $this->updateCarousel($request);
        }

        foreach ($request->input('settings', []) as $key => $value) {
            Setting::setValue($key, $value, $group);
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    private function updateCarousel(Request $request)
    {
        $existing = json_decode(Setting::getValue('carousel_slides', '[]'), true) ?: [];

        $slides = [];
        $slideInputs = $request->input('slides', []);

        foreach ($slideInputs as $i => $slide) {
            $entry = [
                'title' => $slide['title'] ?? '',
                'description' => $slide['description'] ?? '',
                'link' => $slide['link'] ?? '',
                'image' => $existing[$i]['image'] ?? '',
            ];

            if ($request->hasFile("slides.{$i}.image_file")) {
                // Delete old image if it exists
                if (! empty($existing[$i]['image'])) {
                    Storage::disk('public')->delete($existing[$i]['image']);
                }
                $entry['image'] = $request->file("slides.{$i}.image_file")
                    ->store('carousel', 'public');
            }

            $slides[] = $entry;
        }

        // Clean up images from removed slides
        foreach ($existing as $j => $old) {
            if (! isset($slideInputs[$j]) && ! empty($old['image'])) {
                Storage::disk('public')->delete($old['image']);
            }
        }

        Setting::setValue('carousel_slides', json_encode($slides), 'carousel');

        return back()->with('success', 'Carousel settings saved successfully.');
    }
}
