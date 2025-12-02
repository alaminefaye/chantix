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
        Schema::create('project_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_planned', 10, 2)->default(0); // Quantité prévue
            $table->decimal('quantity_ordered', 10, 2)->default(0); // Quantité commandée
            $table->decimal('quantity_delivered', 10, 2)->default(0); // Quantité livrée
            $table->decimal('quantity_used', 10, 2)->default(0); // Quantité utilisée
            $table->decimal('quantity_remaining', 10, 2)->default(0); // Quantité restante (calculée)
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['project_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_materials');
    }
};
