<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            $table->json('batch_ids')->nullable()->after('classroom_id');
            $table->date('date')->nullable()->after('topic');
        });

        if (Schema::hasColumn('timelines', 'batch_id')) {
            DB::table('timelines')->orderBy('id')->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $batchId = (int) ($row->batch_id ?? 0);
                    $date = $row->start_date ?? $row->end_date ?? null;

                    DB::table('timelines')->where('id', $row->id)->update([
                        'batch_ids' => json_encode($batchId > 0 ? [$batchId] : []),
                        'date' => $date,
                    ]);
                }
            });
        }

        Schema::table('timelines', function (Blueprint $table) {
            if (Schema::hasColumn('timelines', 'batch_id')) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            }
            if (Schema::hasColumn('timelines', 'start_date')) {
                $table->dropColumn('start_date');
            }
            if (Schema::hasColumn('timelines', 'end_date')) {
                $table->dropColumn('end_date');
            }
            if (Schema::hasColumn('timelines', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timelines', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('classroom_id');
            $table->date('start_date')->nullable()->after('topic');
            $table->date('end_date')->nullable()->after('start_date');
            $table->tinyInteger('status')->default(0)->after('end_date');
        });

        DB::table('timelines')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $batchIds = json_decode((string) ($row->batch_ids ?? '[]'), true);
                $firstBatchId = is_array($batchIds) && $batchIds !== []
                    ? (int) reset($batchIds)
                    : 0;

                DB::table('timelines')->where('id', $row->id)->update([
                    'batch_id' => $firstBatchId > 0 ? $firstBatchId : null,
                    'start_date' => $row->date,
                    'end_date' => $row->date,
                    'status' => 1,
                ]);
            }
        });

        Schema::table('timelines', function (Blueprint $table) {
            $table->dropColumn(['batch_ids', 'date']);
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
        });
    }
};
