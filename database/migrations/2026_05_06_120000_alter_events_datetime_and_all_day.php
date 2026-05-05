<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'all_day')) {
                $table->boolean('all_day')->default(true)->after('end_date');
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE events MODIFY start_date DATETIME NOT NULL');
            DB::statement('ALTER TABLE events MODIFY end_date DATETIME NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite: recreate pattern would be needed for type change; MAMP stack is MySQL-first.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE events MODIFY start_date DATE NOT NULL');
            DB::statement('ALTER TABLE events MODIFY end_date DATE NOT NULL');
        }

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'all_day')) {
                $table->dropColumn('all_day');
            }
        });
    }
};
