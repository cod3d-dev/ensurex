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
        Schema::create('policy_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained();
            $table->foreignId('document_type_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('status')->nullable();
            $table->dateTime('status_updated_at')->nullable();
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->date('due_date')->nullable();
            $table->date('sent_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_documents');
    }
};
