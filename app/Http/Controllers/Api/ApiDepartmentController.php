<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class ApiDepartmentController extends Controller
{
    public function byFaculty(int $faculty): JsonResponse
    {
        $departments = Department::where('faculty_id', $faculty)
            ->select(['id', 'name', 'slug'])
            ->withCount(['products' => fn ($query) => $query->where('status', 'published')])
            ->orderBy('name')
            ->get();

        return response()->json($departments);
    }
}
