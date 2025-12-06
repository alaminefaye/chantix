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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // 'in' (entrée), 'out' (sortie), 'adjustment' (ajustement)
            $table->decimal('quantity', 10, 2); // Quantité positive pour entrée, négative pour sortie
            $table->decimal('stock_before', 10, 2); // Stock avant le mouvement
            $table->decimal('stock_after', 10, 2); // Stock après le mouvement
            $table->string('reason')->nullable(); // Raison du mouvement (livraison, utilisation, ajustement, etc.)
            $table->text('notes')->nullable();
            $table->string('reference')->nullable(); // Référence (bon de livraison, facture, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
