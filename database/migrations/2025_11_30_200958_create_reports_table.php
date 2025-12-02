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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('type'); // journalier, hebdomadaire
            $table->date('report_date'); // Date du rapport (pour journalier) ou date de début (pour hebdomadaire)
            $table->date('end_date')->nullable(); // Date de fin (pour hebdomadaire)
            $table->text('data')->nullable(); // Données JSON du rapport
            $table->string('file_path')->nullable(); // Chemin du fichier PDF généré
            $table->timestamps();
            
            $table->index(['project_id', 'type', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
