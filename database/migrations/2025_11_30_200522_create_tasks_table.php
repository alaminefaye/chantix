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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // maçonnerie, fondations, électricité, peinture, plomberie, etc.
            $table->string('status')->default('a_faire'); // a_faire, en_cours, termine, bloque
            $table->string('priority')->default('moyenne'); // basse, moyenne, haute, urgente
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->integer('progress')->default(0); // Pourcentage d'avancement (0-100)
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
