<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['user:id,name,email', 'product:id,title,slug']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('product', fn ($p) => $p->where('title', 'like', "%{$search}%"));
            });
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        $report->load(['user:id,name,email', 'product:id,title,slug']);

        return view('admin.reports.show', compact('report'));
    }

    public function reviewed(Report $report)
    {
        $report->update(['status' => 'reviewed']);

        return back()->with('success', 'Report marked as reviewed.');
    }

    public function dismiss(Report $report)
    {
        $report->update(['status' => 'dismissed']);

        return back()->with('success', 'Report dismissed.');
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return back()->with('success', 'Report deleted.');
    }
}
