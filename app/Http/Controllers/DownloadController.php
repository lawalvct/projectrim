<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Product;
use App\Models\Setting;
use App\Services\RevenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __construct(
        private RevenueService $revenueService
    ) {}

    /**
     * Handle product download. Free products allow direct download.
     * Paid products require purchase (download record must exist).
     */
    public function download(Request $request, Product $product)
    {
        $user = $request->user();
        $ip = $request->ip();

        // Auth is enforced at the route level, but guard defensively.
        if (! $user) {
            return redirect()->guest(route('login'))
                ->with('flash', ['error' => 'Please log in to download this product.']);
        }

        // For paid products, user must have purchased
        if ($product->is_paid) {
            $hasAccess = Download::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();

            if (! $hasAccess) {
                return redirect()->route('products.show', $product->slug)
                    ->with('flash', ['error' => 'Please purchase this product first.']);
            }
        }

        // One download per user per product per day
        $alreadyDownloadedToday = Download::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyDownloadedToday) {
            return redirect()->route('products.show', $product->slug)
                ->with('flash', ['error' => 'You have already downloaded this item today. Please try again tomorrow.']);
        }

        // Get the first file
        $file = $product->files()->first();

        if (! $file) {
            return redirect()->route('products.show', $product->slug)
                ->with('flash', ['error' => 'No file available for download.']);
        }

        // Record download entry for free products (paid already has one from purchase,
        // but we still create a daily record to enforce the once-per-day rule)
        Download::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'ip_address' => $ip,
        ]);

        // Increment downloads count
        $product->increment('downloads_count');

        // Record download revenue (with anti-fraud)
        $this->revenueService->recordDownloadRevenue($product, $user?->id, $ip);

        // Check if smart links are enabled
        $smartLinkEnabled = Setting::getValue('smart_link_enabled', '0') === '1';
        $smartLinkCode = Setting::getValue('smart_link_code', '');

        if ($smartLinkEnabled && $smartLinkCode) {
            // Return view that wraps download in smart link code
            return response()->view('downloads.smart-link', [
                'product' => $product,
                'file' => $file,
                'smartLinkCode' => $smartLinkCode,
                'downloadUrl' => route('download.file', $product),
            ]);
        }

        // Direct download
        return $this->serveFile($file);
    }

    /**
     * Serve the actual file (used after smart link or directly).
     */
    public function file(Request $request, Product $product)
    {
        $user = $request->user();

        // For paid products, verify access
        if ($product->is_paid) {
            if (! $user) {
                abort(403);
            }

            $hasAccess = Download::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();

            if (! $hasAccess) {
                abort(403);
            }
        }

        $file = $product->files()->first();

        if (! $file) {
            abort(404);
        }

        return $this->serveFile($file);
    }

    private function serveFile($file)
    {
        $path = $file->file_path ?? $file->path;

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path, $file->file_name);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, $file->file_name);
        }

        abort(404, 'File not found.');
    }
}
