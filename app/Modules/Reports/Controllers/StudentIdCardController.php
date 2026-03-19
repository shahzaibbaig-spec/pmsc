<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Modules\Reports\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class StudentIdCardController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function single(Student $student): Response
    {
        $student->loadMissing('classRoom:id,name,section');

        $school = $this->reportService->schoolMeta();
        $card = $this->cardPayload($student, $school);

        $pdf = Pdf::loadView('idcards.single', [
            'school' => $school,
            'card' => $card,
        ])->setPaper('a4', 'portrait');

        $filename = 'id_card_'.($student->student_id ?: $student->id).'.pdf';

        return $pdf->stream($filename);
    }

    public function bulk(SchoolClass $class): Response
    {
        $studentColumns = ['id', 'student_id', 'name', 'father_name', 'class_id', 'photo_path', 'status'];
        if ($this->hasQrTokenColumn()) {
            $studentColumns[] = 'qr_token';
        }

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('class_id', (int) $class->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get($studentColumns);

        $school = $this->reportService->schoolMeta();
        $cards = $students->map(fn (Student $student): array => $this->cardPayload($student, $school))
            ->values()
            ->all();

        $pdf = Pdf::loadView('idcards.bulk', [
            'school' => $school,
            'class' => $class,
            'cards' => $cards,
        ])->setPaper('a4', 'portrait');

        $filename = 'id_cards_class_'.(int) $class->id.'.pdf';

        return $pdf->stream($filename);
    }

    private function cardPayload(Student $student, array $school): array
    {
        $className = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));
        $studentRef = trim((string) ($student->student_id ?: $student->id));
        $qrPayload = $this->studentQrUrl($student, $studentRef);

        return [
            'student_id' => $studentRef,
            'name' => trim((string) $student->name),
            'father_name' => trim((string) ($student->father_name ?? '')) ?: '-',
            'class_name' => $className !== '' ? $className : '-',
            'photo_absolute_path' => $this->photoAbsolutePath((string) ($student->photo_path ?? '')),
            'qr_profile_url' => $qrPayload,
            'qr_data_uri' => $this->qrDataUri($qrPayload),
        ];
    }

    private function studentQrUrl(Student $student, string $studentCode): string
    {
        $params = ['code' => $studentCode];
        $token = $this->resolveQrToken($student);

        if ($token !== null) {
            $params['token'] = $token;
        }

        return route('students.qr.profile', $params);
    }

    private function resolveQrToken(Student $student): ?string
    {
        if (! $this->hasQrTokenColumn()) {
            return null;
        }

        $currentToken = trim((string) ($student->getAttribute('qr_token') ?? ''));
        if ($currentToken !== '') {
            return $currentToken;
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $nextToken = Str::random(40);
                $student->forceFill(['qr_token' => $nextToken])->saveQuietly();

                return $nextToken;
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function photoAbsolutePath(string $photoPath): ?string
    {
        $normalized = trim($photoPath);
        if ($normalized === '') {
            return null;
        }

        $absolute = public_path('storage/'.$normalized);

        return is_file($absolute) ? $absolute : null;
    }

    private function qrDataUri(string $payload): string
    {
        try {
            $png = QrCode::format('png')
                ->size(180)
                ->margin(1)
                ->generate($payload);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (Throwable) {
            $svg = QrCode::format('svg')
                ->size(180)
                ->margin(1)
                ->generate($payload);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        }
    }

    private function hasQrTokenColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn !== null) {
            return $hasColumn;
        }

        $hasColumn = Schema::hasTable('students') && Schema::hasColumn('students', 'qr_token');

        return $hasColumn;
    }
}
