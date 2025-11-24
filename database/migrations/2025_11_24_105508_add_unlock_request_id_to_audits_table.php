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
        Schema::table('audits', function (Blueprint $table) {
            $table->uuid('unlock_request_id')->nullable()->after('tags');
            $table->index('unlock_request_id');
            
            // Add foreign key if unlock_requests table exists
            if (Schema::hasTable('unlock_requests')) {
                $table->foreign('unlock_request_id')
                    ->references('id')
                    ->on('unlock_requests')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Drop foreign key first if exists
            if (Schema::hasColumn('audits', 'unlock_request_id')) {
                $table->dropForeign(['unlock_request_id']);
            }
            $table->dropColumn('unlock_request_id');
        });
    }
};
