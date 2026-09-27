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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')
                ->constrained('cuentas')
                ->cascadeOnDelete();
            $table->enum('tipo', ['deposito', 'transferencia_salida', 'transferencia_entrada']);
            $table->decimal('monto', 15, 2);
            $table->string('cbu_contraparte', 22)->nullable();
            $table->index(['cuenta_id', 'created_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
