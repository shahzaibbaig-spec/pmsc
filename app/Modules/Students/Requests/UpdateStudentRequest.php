<?php

namespace App\Modules\Students\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('date_of_birth')) {
            $age = Carbon::parse($this->input('date_of_birth'))->age;
            $this->merge(['age' => $age]);
        }
    }

    public function rules(): array
    {
        $studentId = (int) $this->route('student')->id;

        return [
            'student_id' => ['required', 'string', 'max:50', Rule::unique('students', 'student_id')->ignore($studentId)],
            'name' => ['required', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'class_id' => ['required', Rule::exists('school_classes', 'id')],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'age' => ['nullable', 'integer', 'min:1', 'max:100'],
            'contact' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}

