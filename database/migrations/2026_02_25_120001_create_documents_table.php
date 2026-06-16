<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date_document')->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('checksum')->nullable();
            $table->boolean('is_private')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
            $table->index('category');
            $table->index('date_document');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
