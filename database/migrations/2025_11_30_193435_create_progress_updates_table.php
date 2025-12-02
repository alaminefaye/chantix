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
        Schema::create('progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('progress_percentage'); // Pourcentage d'avancement
            $table->text('description')->nullable(); // Rapport texte
            $table->string('audio_file')->nullable(); // Fichier audio (rapport vocal)
            $table->json('photos')->nullable(); // Tableau de chemins vers les photos
            $table->json('videos')->nullable(); // Tableau de chemins vers les vidéos
            $table->decimal('latitude', 10, 8)->nullable(); // GPS pour les photos
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progress_updates');
    }
};
