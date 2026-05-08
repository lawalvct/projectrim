<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    private function monthExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }

    private function dateRange(Request $request): array
    {
        $rules = [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];

        if ($request->filled('date_from')) {
            $rules['date_to'][] = 'after_or_equal:date_from';
        }

        $validated = $request->validate($rules);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'start' => $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null,
            'end' => $dateTo ? Carbon::parse($dateTo)->endOfDay() : null,
        ];
    }

    private function applyDateRange($query, array $dateRange, string $column = 'created_at')
    {
        return $query
            ->when($dateRange['start'], fn ($q, Carbon $start) => $q->where($column, '>=', $start))
            ->when($dateRange['end'], fn ($q, Carbon $end) => $q->where($column, '<=', $end));
    }

    private function hasDateFilter(array $dateRange): bool
    {
        return (bool) ($dateRange['start'] || $dateRange['end']);
    }

    private function chartStartDate(array $dateRange): Carbon
    {
        if ($dateRange['start']) {
            return $dateRange['start']->copy()->startOfMonth();
        }

        if ($dateRange['end']) {
            return $dateRange['end']->copy()->subMonths(11)->startOfMonth();
        }

        return Carbon::now()->subMonths(11)->startOfMonth();
    }

    private function chartEndDate(array $dateRange): Carbon
    {
        $end = ($dateRange['end'] ?? Carbon::now())->copy()->startOfMonth();

        if ($dateRange['start'] && $dateRange['start']->gt($end)) {
            return $dateRange['start']->copy()->startOfMonth();
        }

        return $end;
    }

    private function monthlyCounts(string $modelClass, array $dateRange): Collection
    {
        $start = $this->chartStartDate($dateRange);
        $end = $this->chartEndDate($dateRange);

        $counts = $modelClass::selectRaw($this->monthExpression().' as month, count(*) as count')
            ->where('created_at', '>=', $start->copy()->startOfDay())
            ->where('created_at', '<=', $end->copy()->endOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $months = collect();

        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $key = $month->format('Y-m');
            $months->put($key, (int) ($counts[$key] ?? 0));
        }

        return $months;
    }

    public function products(Request $request)
    {
        $dateRange = $this->dateRange($request);
        $dateFilters = $request->only('date_from', 'date_to');
        $hasDateFilter = $this->hasDateFilter($dateRange);

        $productQuery = fn () => $this->applyDateRange(Product::query(), $dateRange);

        $topByViews = $productQuery()
            ->orderByDesc('views_count')
            ->take(20)
            ->get(['id', 'title', 'slug', 'views_count', 'downloads_count', 'likes_count']);

        $topByLikes = $hasDateFilter
            ? Product::query()
                ->withCount(['likes as likes_count' => fn ($query) => $this->applyDateRange($query, $dateRange)])
                ->orderByDesc('likes_count')
                ->take(20)
                ->get(['id', 'title', 'slug', 'views_count', 'downloads_count'])
            : Product::orderByDesc('likes_count')
                ->take(20)
                ->get(['id', 'title', 'slug', 'views_count', 'downloads_count', 'likes_count']);

        $topByDownloads = $hasDateFilter
            ? Product::query()
                ->withCount(['downloads as downloads_count' => fn ($query) => $this->applyDateRange($query, $dateRange)])
                ->orderByDesc('downloads_count')
                ->take(20)
                ->get(['id', 'title', 'slug', 'views_count', 'likes_count'])
            : Product::orderByDesc('downloads_count')
                ->take(20)
                ->get(['id', 'title', 'slug', 'views_count', 'downloads_count', 'likes_count']);

        $topByRevenue = Product::select('products.id', 'products.title', 'products.slug')
            ->selectRaw('COALESCE(SUM(revenues.amount_usd), 0) as total_revenue')
            ->leftJoin('revenues', function ($join) use ($dateRange) {
                $join->on('products.id', '=', 'revenues.product_id');

                if ($dateRange['start']) {
                    $join->where('revenues.created_at', '>=', $dateRange['start']);
                }

                if ($dateRange['end']) {
                    $join->where('revenues.created_at', '<=', $dateRange['end']);
                }
            })
            ->groupBy('products.id', 'products.title', 'products.slug')
            ->orderByDesc('total_revenue')
            ->take(20)
            ->get();

        $monthlyUploads = $this->monthlyCounts(Product::class, $dateRange);

        return view('admin.analytics.products', compact('topByViews', 'topByLikes', 'topByDownloads', 'topByRevenue', 'monthlyUploads', 'dateFilters'));
    }

    public function users(Request $request)
    {
        $dateRange = $this->dateRange($request);
        $dateFilters = $request->only('date_from', 'date_to');

        $topSellers = User::where('role', 'seller')
            ->withCount(['products' => fn ($query) => $this->applyDateRange($query, $dateRange)])
            ->with(['revenues' => fn ($query) => $this->applyDateRange($query, $dateRange)
                ->selectRaw('user_id, SUM(amount_usd) as total')
                ->groupBy('user_id')])
            ->orderByDesc('products_count')
            ->take(20)
            ->get();

        $monthlyRegistrations = $this->monthlyCounts(User::class, $dateRange);

        $roleCounts = $this->applyDateRange(User::query(), $dateRange)
            ->selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        return view('admin.analytics.users', compact('topSellers', 'monthlyRegistrations', 'roleCounts', 'dateFilters'));
    }
}
