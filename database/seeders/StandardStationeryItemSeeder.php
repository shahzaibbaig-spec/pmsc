<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StandardStationeryItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Pencil',
            'Pen',
            'Notebook',
            'Calculator',
            'Eraser',
            'Rubber',
            'Marker',
            'Blue Ball Pen',
            'Red Pen',
            'Black Pen',
            'Ink',
        ];

        foreach ($items as $itemName) {
            InventoryItem::query()->updateOrCreate(
                ['slug' => Str::slug($itemName)],
                [
                    'name' => $itemName,
                    'category' => 'stationery',
                    'unit' => 'pcs',
                    'is_active' => true,
                ]
            );
        }
    }
}
