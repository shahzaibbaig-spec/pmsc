<?php

namespace App\Services;

use App\Models\Hostel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HostelManagementService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function getHostelList(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min($perPage, 100));

        return Hostel::query()
            ->withCount(['rooms', 'wardens'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array{name:string} $data
     */
    public function createHostel(array $data): Hostel
    {
        return DB::transaction(function () use ($data): Hostel {
            $name = trim((string) $data['name']);
            $exists = Hostel::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new RuntimeException('A hostel with this name already exists.');
            }

            return Hostel::query()->create([
                'name' => $name,
            ]);
        });
    }

    /**
     * @param array{name:string} $data
     */
    public function updateHostel(Hostel $hostel, array $data): Hostel
    {
        return DB::transaction(function () use ($hostel, $data): Hostel {
            $name = trim((string) $data['name']);
            $exists = Hostel::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->whereKeyNot((int) $hostel->id)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new RuntimeException('A hostel with this name already exists.');
            }

            $hostel->forceFill(['name' => $name])->save();

            return $hostel->refresh();
        });
    }

    public function deleteHostel(Hostel $hostel): void
    {
        DB::transaction(function () use ($hostel): void {
            $hostel->loadCount(['rooms', 'wardens', 'roomAllocations', 'dailyReports']);

            if (
                (int) ($hostel->rooms_count ?? 0) > 0
                || (int) ($hostel->wardens_count ?? 0) > 0
                || (int) ($hostel->room_allocations_count ?? 0) > 0
                || (int) ($hostel->daily_reports_count ?? 0) > 0
            ) {
                throw new RuntimeException('This hostel cannot be deleted because linked records already exist.');
            }

            $hostel->delete();
        });
    }
}

