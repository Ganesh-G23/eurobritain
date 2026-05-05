<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_reminder_logs')) {
            return;
        }

        Schema::create('event_reminder_logs', function (Blueprint $table) {
            $table->id();
            /** @var string Teacher Event id prefixed "e:", personal "p:" */
            $table->string('event_key', 72);
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notifiable_type');
            $table->unsignedTinyInteger('offset_hours');
            $table->timestamp('sent_at')->useCurrent();

            $table->unique(
                ['event_key', 'notifiable_id', 'notifiable_type', 'offset_hours'],
                'event_reminder_log_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reminder_logs');
    }
};
