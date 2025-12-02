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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('type'); // matériaux, transport, main_oeuvre, location, autres
            $table->string('title'); // Titre de la dépense
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2); // Montant
            $table->date('expense_date'); // Date de la dépense
            $table->string('supplier')->nullable(); // Fournisseur
            $table->string('invoice_number')->nullable(); // Numéro de facture
            $table->date('invoice_date')->nullable(); // Date de facture
            $table->string('invoice_file')->nullable(); // Fichier facture (photo/PDF)
            $table->foreignId('material_id')->nullable()->constrained()->onDelete('set null'); // Si type = matériaux
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null'); // Si type = main-d'œuvre
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(false); // Payé ou non
            $table->date('paid_date')->nullable(); // Date de paiement
            $table->timestamps();
            
            $table->index(['project_id', 'expense_date']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
