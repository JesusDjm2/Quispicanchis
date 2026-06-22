<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Distingue los 10 distritos atendidos directamente por la UGEL Quispicanchi
 * de Lucre y Oropesa, que pertenecen politicamente a la provincia y son
 * obligatorios para el consolidado provincial (INEI/MIDIS/ESCALE) aunque
 * no los gestione esta UGEL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            $table->boolean('managed_by_ugel')->default(true)->after('ubigeo');
        });
    }

    public function down(): void
    {
        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('managed_by_ugel');
        });
    }
};
