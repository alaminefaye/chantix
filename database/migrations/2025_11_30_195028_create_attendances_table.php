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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('check_in')->nullable(); // Heure d'arrivée
            $table->time('check_out')->nullable(); // Heure de départ
            $table->decimal('hours_worked', 5, 2)->nullable(); // Heures travaillées (calculées)
            $table->decimal('overtime_hours', 5, 2)->default(0); // Heures supplémentaires
            $table->string('check_in_location')->nullable(); // Localisation GPS check-in
            $table->string('check_out_location')->nullable(); // Localisation GPS check-out
            $table->text('notes')->nullable();
            $table->boolean('is_present')->default(true); // Présent ou absent
            $table->string('absence_reason')->nullable(); // Raison de l'absence
            $table->timestamps();
            
            $table->unique(['project_id', 'employee_id', 'date']);
            $table->index(['project_id', 'date']);
            $table->index(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
