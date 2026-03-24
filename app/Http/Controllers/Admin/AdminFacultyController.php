<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminFacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::withCount('departments', 'products')->orderBy('name')->paginate(20);

        return view('admin.categories.faculties', compact('faculties'));
    }

    public function create()
    {
        return view('admin.categories.faculty-form', ['faculty' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name',
            'slug' => 'nullable|string|max:255|unique:faculties,slug',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        Faculty::create($validated);

        return redirect()->route('admin.faculties.index')->with('success', 'Faculty created.');
    }

    public function edit(Faculty $faculty)
    {
        return view('admin.categories.faculty-form', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:faculties,name,' . $faculty->id,
            'slug' => 'nullable|string|max:255|unique:faculties,slug,' . $faculty->id,
        ]);

        $faculty->update($validated);

        return redirect()->route('admin.faculties.index')->with('success', 'Faculty updated.');
    }

    public function destroy(Faculty $faculty)
    {
        $faculty->delete();

        return redirect()->route('admin.faculties.index')->with('success', 'Faculty deleted.');
    }
}
