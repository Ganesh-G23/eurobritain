<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('amount', 'total_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('pending_amount', 12, 2)->default(0)->after('paid_amount');
        });

        // Migrate/backfill existing payments data to invoices table
        DB::table('invoices')->orderBy('id')->chunk(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                // Sum the completed/done payments for this invoice
                $paid = DB::table('payments')
                    ->where('invoice_id', $invoice->id)
                    ->whereNull('deleted_at')
                    ->sum('amount');

                DB::table('invoices')
                    ->where('id', $invoice->id)
                    ->update([
                        'paid_amount' => $paid,
                        'pending_amount' => max(0, $invoice->total_amount - $paid),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'pending_amount']);
            $table->renameColumn('total_amount', 'amount');
        });
    }
};
