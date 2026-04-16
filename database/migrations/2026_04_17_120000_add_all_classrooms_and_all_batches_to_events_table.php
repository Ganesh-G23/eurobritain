<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'all_classrooms')) {
                $table->tinyInteger('all_classrooms')->default(0)->after('classroom_id');
            }
            if (!Schema::hasColumn('events', 'all_batches')) {
                $table->tinyInteger('all_batches')->default(0)->after('batch_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'all_batches')) {
                $table->dropColumn('all_batches');
            }
            if (Schema::hasColumn('events', 'all_classrooms')) {
                $table->dropColumn('all_classrooms');
            }
        });
    }
};
