<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use App\Modules\Admin\Requests\UpdateSchoolSettingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SchoolSettingController extends Controller
{
    public function edit(): View
    {
        $setting = SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'School Management System',
                'logo_path' => null,
                'address' => null,
                'phone' => null,
                'email' => null,
            ]
        );

        return view('modules.admin.settings', [
            'setting' => $setting,
            'logoUrl' => $setting->logo_path ? Storage::disk('public')->url($setting->logo_path) : null,
        ]);
    }

    public function update(UpdateSchoolSettingRequest $request): RedirectResponse
    {
        $setting = SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'School Management System',
                'logo_path' => null,
                'address' => null,
                'phone' => null,
                'email' => null,
            ]
        );

        $data = $request->validated();
        $setting->school_name = $data['school_name'];

        if ($request->hasFile('logo')) {
            if ($setting->logo_path) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $setting->logo_path = $request->file('logo')->store('school/logo', 'public');
        }

        $setting->save();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'School settings updated successfully.');
    }
}

