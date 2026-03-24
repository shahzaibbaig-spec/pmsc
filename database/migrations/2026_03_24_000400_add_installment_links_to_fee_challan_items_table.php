<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_challan_items', function (Blueprint $table): void {
            $table->foreignId('fee_installment_id')
                ->nullable()
                ->after('fee_structure_id')
                ->constrained('fee_installments')
                ->nullOnDelete();

            $table->foreignId('student_arrear_id')
                ->nullable()
                ->after('fee_installment_id')
                ->constrained('student_arrears')
                ->nullOnDelete();

            $table->decimal('paid_amount', 10, 2)
                ->default(0)
                ->after('amount');

            $table->index(['fee_installment_id'], 'fee_challan_items_installment_index');
            $table->index(['student_arrear_id'], 'fee_challan_items_arrear_index');
        });
    }

    public function down(): void
    {
        Schema::table('fee_challan_items', function (Blueprint $table): void {
            $table->dropIndex('fee_challan_items_arrear_index');
            $table->dropIndex('fee_challan_items_installment_index');
            $table->dropConstrainedForeignId('student_arrear_id');
            $table->dropConstrainedForeignId('fee_installment_id');
            $table->dropColumn('paid_amount');
        });
    }
};

