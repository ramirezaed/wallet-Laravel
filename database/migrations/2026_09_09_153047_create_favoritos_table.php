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
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')
                ->constrained('cuentas')
                ->cascadeOnDelete();
            $table->string('cbu_favorito', 22);
            $table->foreign('cbu_favorito')
                ->references('cbu')
                ->on('cuentas')
                ->restrictOnDelete();
            $table->unique(['cuenta_id', 'cbu_favorito']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
