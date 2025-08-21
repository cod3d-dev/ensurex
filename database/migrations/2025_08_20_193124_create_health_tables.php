<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Health\ResultStores\EloquentHealthResultStore;

return new class extends Migration
{
    public function up()
    {
        $connection = 'health';
        $tableName = EloquentHealthResultStore::getHistoryItemInstance()->getTable();

        if (! Schema::connection($connection)->hasTable($tableName)) {
            Schema::connection($connection)->create($tableName, function (Blueprint $table) {
                $table->id();

                $table->string('check_name');
                $table->string('check_label');
                $table->string('status');
                $table->text('notification_message')->nullable();
                $table->string('short_summary')->nullable();
                $table->json('meta');
                $table->timestamp('ended_at');
                $table->uuid('batch');

                $table->timestamps();
            });
        }

        // Add indexes
        try {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                $table->index('created_at');
                $table->index('batch');
            });
        } catch (\Exception $e) {
            // Indexes might already exist, ignore the error
        }
    }

    public function down()
    {
        $connection = 'health';
        $tableName = EloquentHealthResultStore::getHistoryItemInstance()->getTable();

        if (Schema::connection($connection)->hasTable($tableName)) {
            Schema::connection($connection)->dropIfExists($tableName);
        }
    }
};
