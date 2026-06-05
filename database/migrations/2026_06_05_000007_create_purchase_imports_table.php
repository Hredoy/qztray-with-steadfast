<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_imports', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('supplier')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('raw_extracted')->nullable(); // Gemini raw JSON
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_imports');
    }
};
