<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    protected $fillable = [
        'name',
    ];

    public function wardens(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }

    public function roomAllocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(WardenDailyReport::class);
    }
}
