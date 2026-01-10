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
        Schema::create('tickets', function (Blueprint $table) {
    $table->id();
            $table->timestamps();

    $table->string('subject');
    $table->text('description');

    $table->enum('category', [
        'access',
        'hardware',
        'network',
        'bug',
        'other',
    ]);

    $table->unsignedTinyInteger('severity'); // 1–5

    $table->enum('status', [
        'open',
        'in_progress',
        'resolved',
        'closed',
    ])->default('open');

    $table->foreignId('created_by')
        ->constrained('users')
        ->cascadeOnDelete();

    $table->foreignId('assigned_to')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('resolved_at')->nullable();
    $table->timestamp('closed_at')->nullable();


    $table->index(['status', 'category']);
    $table->index('created_by');
    $table->index('assigned_to');
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
