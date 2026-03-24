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
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($departments);
    }
}
