<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('created_by');
            $table->foreign('parent_id')->references('id')->on('portal_user')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
