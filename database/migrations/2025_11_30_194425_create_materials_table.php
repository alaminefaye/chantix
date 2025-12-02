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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // ciment, acier, bois, electricite, plomberie, peinture, autres
            $table->string('unit')->default('unité'); // kg, m², m³, pièce, litre, etc.
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('reference')->nullable(); // Référence fournisseur
            $table->decimal('stock_quantity', 10, 2)->default(0); // Stock global de l'entreprise
            $table->decimal('min_stock', 10, 2)->default(0); // Seuil minimum pour alerte
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
