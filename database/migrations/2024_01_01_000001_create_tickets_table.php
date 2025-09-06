<?php

declare(strict_types=1);

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
        Schema::create('tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['open', 'closed', 'pending'])->default('open');
            $table->string('category')->nullable();
            $table->text('explanation')->nullable();
            $table->float('confidence', 8, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('status');
            $table->index('category');
            $table->index(['status', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
