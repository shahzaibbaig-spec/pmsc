<?php

namespace App\Modules\Reports\Requests;

use App\Models\Certificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasAnyRole(['Admin', 'Principal']);
    }

    public function rules(): array
    {
        return [
            'certificate_type' => ['required', 'string', Rule::in([Certificate::TYPE_MERIT])],
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'chairman_name' => ['nullable', 'string', 'max:150'],
            'principal_name' => ['nullable', 'string', 'max:150'],
            'chairman_title' => ['nullable', 'string', 'max:150'],
            'principal_title' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', Rule::in(['issued', 'draft'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'certificate_type' => trim((string) $this->input('certificate_type', Certificate::TYPE_MERIT)),
            'title' => trim((string) $this->input('title', 'Certificate of Merit')),
            'reason' => trim((string) $this->input('reason', '')),
            'issue_date' => trim((string) $this->input('issue_date', now()->toDateString())),
            'chairman_name' => trim((string) $this->input('chairman_name', 'Ch M Akhter')),
            'principal_name' => trim((string) $this->input('principal_name', 'M. Shahzaib Baig')),
            'chairman_title' => trim((string) $this->input('chairman_title', 'Chairman, KORT')),
            'principal_title' => trim((string) $this->input('principal_title', 'School Principal')),
            'status' => trim((string) $this->input('status', 'issued')),
        ]);
    }
}
