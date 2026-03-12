<?php

namespace App\Modules\Exams\Enums;

enum ExamType: string
{
    case ClassTest = 'class_test';
    case BimonthlyTest = 'bimonthly_test';
    case FirstTerm = 'first_term';
    case FinalTerm = 'final_term';

    public function label(): string
    {
        return match ($this) {
            self::ClassTest => 'Class Test',
            self::BimonthlyTest => 'Bimonthly Test',
            self::FirstTerm => '1st Term',
            self::FinalTerm => 'Final Term',
        };
    }

    public static function options(): array
    {
        return array_map(
            static fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}

