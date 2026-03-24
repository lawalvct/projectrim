<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminDepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('faculty')->withCount('products')->orderBy('name')->paginate(20);

        return view('admin.categories.departments', compact('departments'));
    }

    public function create()
    {
        $faculties = Faculty::orderBy('name')->get();

        return view('admin.categories.department-form', ['department' => null, 'faculties' => $faculties]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:departments,slug',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        Department::create($validated);

        return redirect()->route('admin.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department)
    {
        $faculties = Faculty::orderBy('name')->get();

        return view('admin.categories.department-form', compact('department', 'faculties'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:departments,slug,' . $department->id,
        ]);

        $department->update($validated);

        return redirect()->route('admin.departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Department deleted.');
    }
}
