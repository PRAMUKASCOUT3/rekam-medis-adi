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
        Schema::create('obat_rekam_medis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rekam_medis_id')
                ->constrained('rekam_medis')
                ->cascadeOnDelete();

            $table->foreignId('obat_id')
                ->constrained('obats');

            $table->unsignedInteger('jumlah')->default(1);
            $table->text('dosis')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['rekam_medis_id', 'obat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_medical_record');
    }
};
