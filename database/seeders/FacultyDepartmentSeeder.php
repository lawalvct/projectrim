<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FacultyDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            'Faculty of Science' => [
                'Computer Science',
                'Mathematics',
                'Physics',
                'Chemistry',
                'Biology',
                'Statistics',
                'Microbiology',
                'Biochemistry',
            ],
            'Faculty of Engineering' => [
                'Mechanical Engineering',
                'Electrical Engineering',
                'Civil Engineering',
                'Chemical Engineering',
                'Computer Engineering',
                'Petroleum Engineering',
            ],
            'Faculty of Arts' => [
                'English Language',
                'History',
                'Philosophy',
                'Linguistics',
                'Theatre Arts',
                'Music',
            ],
            'Faculty of Social Sciences' => [
                'Economics',
                'Political Science',
                'Sociology',
                'Psychology',
                'Geography',
                'Mass Communication',
            ],
            'Faculty of Law' => [
                'Private Law',
                'Public Law',
                'International Law',
                'Commercial Law',
            ],
            'Faculty of Education' => [
                'Educational Management',
                'Guidance & Counselling',
                'Curriculum Studies',
                'Science Education',
                'Educational Technology',
            ],
            'Faculty of Medicine' => [
                'Medicine & Surgery',
                'Nursing',
                'Pharmacy',
                'Medical Laboratory Science',
                'Anatomy',
                'Physiology',
            ],
            'Faculty of Agriculture' => [
                'Agricultural Economics',
                'Crop Science',
                'Animal Science',
                'Soil Science',
                'Fisheries',
            ],
            'Faculty of Management Sciences' => [
                'Accounting',
                'Business Administration',
                'Banking & Finance',
                'Marketing',
                'Public Administration',
                'Insurance',
            ],
            'Faculty of Environmental Sciences' => [
                'Architecture',
                'Urban & Regional Planning',
                'Estate Management',
                'Building Technology',
                'Quantity Surveying',
            ],
        ];

        foreach ($faculties as $facultyName => $departments) {
            $faculty = Faculty::firstOrCreate(
                ['slug' => Str::slug($facultyName)],
                ['name' => $facultyName, 'slug' => Str::slug($facultyName)]
            );

            foreach ($departments as $departmentName) {
                Department::firstOrCreate(
                    ['slug' => Str::slug($departmentName), 'faculty_id' => $faculty->id],
                    ['name' => $departmentName, 'slug' => Str::slug($departmentName), 'faculty_id' => $faculty->id]
                );
            }
        }
    }
}
