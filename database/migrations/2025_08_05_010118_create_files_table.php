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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('prompt_uuid')->constrained('prompts', 'uuid')->onDelete('cascade');
            $table->string('name'); // nome original
            $table->string('path'); // caminho no storage
            $table->string('mime_type')->nullable();
            $table->bigInteger('size')->nullable(); // em bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
