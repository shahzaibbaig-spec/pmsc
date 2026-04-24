<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hostels')) {
            Schema::create('hostels', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        $this->ensureHostelRecords();

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'hostel_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('hostel_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('hostels')
                    ->nullOnDelete();
                $table->index('hostel_id');
            });
        }

        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'gender')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('gender', 20)->nullable()->after('age');
                $table->index('gender');
            });
        }

        if (Schema::hasTable('hostel_rooms')) {
            if (! Schema::hasColumn('hostel_rooms', 'hostel_id')) {
                Schema::table('hostel_rooms', function (Blueprint $table): void {
                    $table->foreignId('hostel_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('hostels')
                        ->nullOnDelete();
                    $table->index('hostel_id');
                });
            }

            $this->backfillRoomHostels();
        }

        if (Schema::hasTable('hostel_room_allocations')) {
            Schema::table('hostel_room_allocations', function (Blueprint $table): void {
                if (! Schema::hasColumn('hostel_room_allocations', 'hostel_id')) {
                    $table->foreignId('hostel_id')
                        ->nullable()
                        ->after('hostel_room_id')
                        ->constrained('hostels')
                        ->nullOnDelete();
                    $table->index('hostel_id');
                }

                if (! Schema::hasColumn('hostel_room_allocations', 'session')) {
                    $table->string('session', 20)->nullable()->after('allocated_to');
                    $table->index('session');
                }

                if (! Schema::hasColumn('hostel_room_allocations', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('status');
                    $table->index('is_active');
                }
            });

            $this->backfillAllocationColumns();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hostel_room_allocations')) {
            Schema::table('hostel_room_allocations', function (Blueprint $table): void {
                if (Schema::hasColumn('hostel_room_allocations', 'hostel_id')) {
                    $table->dropConstrainedForeignId('hostel_id');
                }

                if (Schema::hasColumn('hostel_room_allocations', 'session')) {
                    $table->dropColumn('session');
                }

                if (Schema::hasColumn('hostel_room_allocations', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('hostel_rooms') && Schema::hasColumn('hostel_rooms', 'hostel_id')) {
            Schema::table('hostel_rooms', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('hostel_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'hostel_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('hostel_id');
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'gender')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('gender');
            });
        }

        Schema::dropIfExists('hostels');
    }

    private function ensureHostelRecords(): void
    {
        if (! Schema::hasTable('hostels')) {
            return;
        }

        $now = now();
        foreach (['Fatimah House', 'Jinnah House'] as $name) {
            $exists = DB::table('hostels')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            DB::table('hostels')->insert([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function backfillRoomHostels(): void
    {
        $fatimahId = DB::table('hostels')->where('name', 'Fatimah House')->value('id');
        $jinnahId = DB::table('hostels')->where('name', 'Jinnah House')->value('id');

        if ($fatimahId) {
            DB::table('hostel_rooms')
                ->whereNull('hostel_id')
                ->whereRaw('LOWER(COALESCE(gender, "")) = ?', ['female'])
                ->update(['hostel_id' => (int) $fatimahId]);

            DB::table('hostel_rooms')
                ->whereNull('hostel_id')
                ->whereRaw('LOWER(room_name) like ?', ['%fatimah%'])
                ->update(['hostel_id' => (int) $fatimahId]);
        }

        if ($jinnahId) {
            DB::table('hostel_rooms')
                ->whereNull('hostel_id')
                ->whereRaw('LOWER(COALESCE(gender, "")) = ?', ['male'])
                ->update(['hostel_id' => (int) $jinnahId]);

            DB::table('hostel_rooms')
                ->whereNull('hostel_id')
                ->whereRaw('LOWER(room_name) like ?', ['%jinnah%'])
                ->update(['hostel_id' => (int) $jinnahId]);
        }
    }

    private function backfillAllocationColumns(): void
    {
        $roomHostels = DB::table('hostel_rooms')
            ->whereNotNull('hostel_id')
            ->pluck('hostel_id', 'id');

        DB::table('hostel_room_allocations')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($roomHostels): void {
                foreach ($rows as $row) {
                    $updates = [];

                    if ($row->hostel_id === null) {
                        $roomHostelId = $roomHostels->get((int) $row->hostel_room_id);
                        if ($roomHostelId !== null) {
                            $updates['hostel_id'] = (int) $roomHostelId;
                        }
                    }

                    if ($row->session === null && ! empty($row->allocated_from)) {
                        $allocatedFrom = Carbon::parse((string) $row->allocated_from);
                        $startYear = $allocatedFrom->month >= 7 ? $allocatedFrom->year : $allocatedFrom->year - 1;
                        $updates['session'] = $startYear.'-'.($startYear + 1);
                    }

                    $status = strtolower(trim((string) $row->status));
                    $updates['is_active'] = $status === 'active';

                    if ($updates !== []) {
                        DB::table('hostel_room_allocations')
                            ->where('id', (int) $row->id)
                            ->update($updates);
                    }
                }
            });
    }
};
