<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeBlockOverride;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Reports\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class StudentIdCardController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly FeeDefaulterService $feeDefaulterService,
    )
    {
    }

    public function single(Student $student): Response
    {
        $session = $this->feeDefaulterService->sessionFromDate();
        if ($this->feeDefaulterService->isStudentBlocked(FeeBlockOverride::TYPE_ID_CARD, (int) $student->id, $session)) {
            return response('Official ID card is blocked for this student due to unpaid dues.', 422);
        }

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
        $session = $this->feeDefaulterService->sessionFromDate();
        $this->feeDefaulterService->processSession($session);

        $blocked = $this->feeDefaulterService->blockedStudentsForClass((int) $class->id, $session, FeeBlockOverride::TYPE_ID_CARD);
        if ($blocked->isNotEmpty()) {
            $sample = $blocked
                ->take(3)
                ->map(fn (array $student): string => $student['name'].' ('.$student['student_id'].')')
                ->implode(', ');

            return response(sprintf(
                'ID cards are blocked for %d defaulter(s) in this class. %s',
                $blocked->count(),
                $sample !== '' ? 'Examples: '.$sample.'.' : ''
            ), 422);
        }

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
            try {
                $svg = QrCode::format('svg')
                    ->size(180)
                    ->margin(1)
                    ->generate($payload);

                return 'data:image/svg+xml;base64,'.base64_encode($svg);
            } catch (Throwable) {
                return $this->qrFallbackDataUri($payload);
            }
        }
    }

    private function qrFallbackDataUri(string $payload): string
    {
        $label = htmlspecialchars((string) Str::of($payload)->limit(40, '...'), ENT_QUOTES, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="180" viewBox="0 0 180 180">'
            .'<rect width="180" height="180" fill="#ffffff"/>'
            .'<rect x="4" y="4" width="172" height="172" fill="none" stroke="#111111" stroke-width="2"/>'
            .'<text x="90" y="74" font-size="12" text-anchor="middle" fill="#111111" font-family="Arial, sans-serif">QR unavailable</text>'
            .'<text x="90" y="94" font-size="8" text-anchor="middle" fill="#444444" font-family="Arial, sans-serif">'.$label.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
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
