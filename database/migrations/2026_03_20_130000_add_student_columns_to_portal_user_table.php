<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id')->nullable()->after('role');
            $table->unsignedBigInteger('batch_id')->nullable()->after('classroom_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('batch_id');

            $table->foreign('classroom_id')->references('id')->on('classrooms')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('portal_user')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['created_by']);

            $table->dropColumn(['classroom_id', 'batch_id', 'created_by']);
        });
    }
};

