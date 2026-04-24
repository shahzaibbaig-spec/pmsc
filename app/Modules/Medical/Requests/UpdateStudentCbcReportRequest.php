<?php

namespace App\Modules\Medical\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentCbcReportRequest extends StoreStudentCbcReportRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Doctor')) {
            return $user->can('create_cbc_report');
        }

        return $user->hasAnyRole(['Principal', 'Admin']) && $user->can('view_all_cbc_reports');
    }
}
