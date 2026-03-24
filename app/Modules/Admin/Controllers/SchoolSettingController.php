<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use App\Modules\Admin\Requests\UpdateSchoolSettingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SchoolSettingController extends Controller
{
    public function edit(): View
    {
        $supportsDefaulterBlocks = $this->supportsDefaulterBlocks();

        $defaults = [
            'school_name' => 'School Management System',
            'logo_path' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
        ];
        if ($supportsDefaulterBlocks) {
            $defaults['block_results_for_defaulters'] = false;
            $defaults['block_admit_card_for_defaulters'] = false;
            $defaults['block_id_card_for_defaulters'] = false;
        }

        $setting = SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            $defaults
        );

        return view('modules.admin.settings', [
            'setting' => $setting,
            'logoUrl' => $setting->logo_path ? Storage::disk('public')->url($setting->logo_path) : null,
            'supportsDefaulterBlocks' => $supportsDefaulterBlocks,
        ]);
    }

    public function update(UpdateSchoolSettingRequest $request): RedirectResponse
    {
        $supportsDefaulterBlocks = $this->supportsDefaulterBlocks();

        $defaults = [
            'school_name' => 'School Management System',
            'logo_path' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
        ];
        if ($supportsDefaulterBlocks) {
            $defaults['block_results_for_defaulters'] = false;
            $defaults['block_admit_card_for_defaulters'] = false;
            $defaults['block_id_card_for_defaulters'] = false;
        }

        $setting = SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            $defaults
        );

        $data = $request->validated();
        $setting->school_name = $data['school_name'];
        if ($supportsDefaulterBlocks) {
            $setting->block_results_for_defaulters = $request->boolean('block_results_for_defaulters');
            $setting->block_admit_card_for_defaulters = $request->boolean('block_admit_card_for_defaulters');
            $setting->block_id_card_for_defaulters = $request->boolean('block_id_card_for_defaulters');
        }

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

    private function supportsDefaulterBlocks(): bool
    {
        if (! Schema::hasTable('school_settings')) {
            return false;
        }

        return Schema::hasColumn('school_settings', 'block_results_for_defaulters')
            && Schema::hasColumn('school_settings', 'block_admit_card_for_defaulters')
            && Schema::hasColumn('school_settings', 'block_id_card_for_defaulters');
    }
}
