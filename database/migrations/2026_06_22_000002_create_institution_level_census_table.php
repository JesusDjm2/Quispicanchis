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
        Schema::create('institution_level_census', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educational_institution_id')->constrained()->cascadeOnDelete();
            $table->string('modular_code');
            $table->string('institution_code')->nullable();
            $table->string('level');
            $table->string('program_type')->nullable();
            $table->unsignedSmallInteger('students')->nullable();
            $table->unsignedSmallInteger('teachers')->nullable();
            $table->unsignedSmallInteger('sections')->nullable();
            $table->unsignedSmallInteger('census_year');
            $table->timestamps();

            $table->unique(['modular_code', 'census_year']);
            $table->index('level');
            $table->index('census_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution_level_census');
    }
};
