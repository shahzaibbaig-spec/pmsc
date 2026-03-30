<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SchoolSettingSeeder::class,
            StudentModuleSeeder::class,
            SubjectModuleSeeder::class,
            FederalBoardSubjectSeeder::class,
            TeacherModuleSeeder::class,
            StandardStationeryItemSeeder::class,
            ClassPromotionMappingSeeder::class,
            ClassSectionSeeder::class,
            TimeSlotSeeder::class,
        ]);
    }
}
