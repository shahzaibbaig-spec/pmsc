<?php

namespace Tests\Feature;

use App\Models\FeeChallan;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentFeeStructure;
use App\Models\User;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeChallanGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_student_custom_fee_amounts_when_creating_challans(): void
    {
        $service = app(FeeManagementService::class);
        [$user, $classRoom, $student] = $this->basicFeeFixtures();

        $this->createMonthlyFeeHeads($classRoom, $user, '2025-2026');

        StudentFeeStructure::query()->create([
            'student_id' => $student->id,
            'session' => '2025-2026',
            'tuition_fee' => 800,
            'computer_fee' => 100,
            'exam_fee' => 150,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $summary = $service->generateClassChallans('2025-2026', (int) $classRoom->id, '2026-03', '2026-03-15', (int) $user->id);

        $this->assertSame(1, $summary['created']);
        $this->assertSame(0, $summary['updated_existing']);

        $challan = FeeChallan::query()->with('items')->sole();

        $this->assertSame([
            'computer' => 100.0,
            'exam' => 150.0,
            'tuition' => 800.0,
        ], $this->itemAmountsByType($challan));
        $this->assertSame(1050.0, round((float) $challan->total_amount, 2));
    }

    public function test_it_refreshes_existing_unpaid_challans_without_payments_when_custom_fee_changes(): void
    {
        $service = app(FeeManagementService::class);
        [$user, $classRoom, $student] = $this->basicFeeFixtures();

        $this->createMonthlyFeeHeads($classRoom, $user, '2025-2026');

        $firstSummary = $service->generateClassChallans('2025-2026', (int) $classRoom->id, '2026-03', '2026-03-15', (int) $user->id);

        $this->assertSame(1, $firstSummary['created']);
        $this->assertCount(1, FeeChallan::all());

        StudentFeeStructure::query()->create([
            'student_id' => $student->id,
            'session' => '2025-2026',
            'tuition_fee' => 800,
            'computer_fee' => 100,
            'exam_fee' => 150,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $secondSummary = $service->generateClassChallans('2025-2026', (int) $classRoom->id, '2026-03', '2026-03-18', (int) $user->id);

        $this->assertSame(0, $secondSummary['created']);
        $this->assertSame(1, $secondSummary['updated_existing']);
        $this->assertSame(0, $secondSummary['skipped_existing']);
        $this->assertCount(1, FeeChallan::all());

        $challan = FeeChallan::query()->with('items')->sole();

        $this->assertSame('2026-03-18', optional($challan->due_date)->toDateString());
        $this->assertSame([
            'computer' => 100.0,
            'exam' => 150.0,
            'tuition' => 800.0,
        ], $this->itemAmountsByType($challan));
        $this->assertSame(1050.0, round((float) $challan->total_amount, 2));
    }

    /**
     * @return array{0:User,1:SchoolClass,2:Student}
     */
    private function basicFeeFixtures(): array
    {
        $user = User::factory()->create();
        $classRoom = SchoolClass::query()->create([
            'name' => '10',
            'section' => 'A',
            'status' => 'active',
        ]);
        $student = Student::query()->create([
            'student_id' => 'STD-001',
            'name' => 'Ali Raza',
            'class_id' => $classRoom->id,
            'status' => 'active',
        ]);

        return [$user, $classRoom, $student];
    }

    private function createMonthlyFeeHeads(SchoolClass $classRoom, User $user, string $session): void
    {
        FeeStructure::query()->create([
            'session' => $session,
            'class_id' => $classRoom->id,
            'title' => 'Monthly Tuition Fee',
            'amount' => 1000,
            'fee_type' => 'tuition',
            'is_monthly' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        FeeStructure::query()->create([
            'session' => $session,
            'class_id' => $classRoom->id,
            'title' => 'Computer Fee',
            'amount' => 200,
            'fee_type' => 'computer',
            'is_monthly' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        FeeStructure::query()->create([
            'session' => $session,
            'class_id' => $classRoom->id,
            'title' => 'Exam Fee',
            'amount' => 300,
            'fee_type' => 'exam',
            'is_monthly' => true,
            'is_active' => true,
            'created_by' => $user->id,
        ]);
    }

    /**
     * @return array<string, float>
     */
    private function itemAmountsByType(FeeChallan $challan): array
    {
        return $challan->items
            ->sortBy('fee_type')
            ->mapWithKeys(fn ($item): array => [(string) $item->fee_type => round((float) $item->amount, 2)])
            ->all();
    }
}
