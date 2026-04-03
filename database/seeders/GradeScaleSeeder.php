<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use Illuminate\Database\Seeder;

class GradeScaleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['grade_code' => 'A*', 'label' => 'Excellent', 'percentage_equivalent' => 95.00, 'grade_point' => 6.00, 'sort_order' => 1],
            ['grade_code' => 'A', 'label' => 'Very Good', 'percentage_equivalent' => 88.00, 'grade_point' => 5.50, 'sort_order' => 2],
            ['grade_code' => 'B', 'label' => 'Good', 'percentage_equivalent' => 80.00, 'grade_point' => 5.00, 'sort_order' => 3],
            ['grade_code' => 'C', 'label' => 'Satisfactory', 'percentage_equivalent' => 72.00, 'grade_point' => 4.50, 'sort_order' => 4],
            ['grade_code' => 'D', 'label' => 'Basic', 'percentage_equivalent' => 64.00, 'grade_point' => 4.00, 'sort_order' => 5],
            ['grade_code' => 'E', 'label' => 'Needs Improvement', 'percentage_equivalent' => 56.00, 'grade_point' => 3.50, 'sort_order' => 6],
            ['grade_code' => 'F', 'label' => 'Weak', 'percentage_equivalent' => 48.00, 'grade_point' => 3.00, 'sort_order' => 7],
            ['grade_code' => 'G', 'label' => 'Very Weak', 'percentage_equivalent' => 38.00, 'grade_point' => 2.00, 'sort_order' => 8],
            ['grade_code' => 'U', 'label' => 'Ungraded / Unsatisfactory', 'percentage_equivalent' => 0.00, 'grade_point' => 0.00, 'sort_order' => 9],
        ];

        foreach ($rows as $row) {
            GradeScale::query()->updateOrCreate(
                ['grade_code' => $row['grade_code']],
                [
                    'label' => $row['label'],
                    'percentage_equivalent' => $row['percentage_equivalent'],
                    'grade_point' => $row['grade_point'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
