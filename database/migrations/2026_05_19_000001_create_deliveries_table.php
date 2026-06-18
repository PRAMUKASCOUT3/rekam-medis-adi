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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('nama_istri', 255);
            $table->string('nama_suami', 255)->nullable();
            $table->unsignedInteger('umur_istri')->nullable();
            $table->unsignedInteger('umur_suami')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telpon', 20)->nullable();
            $table->string('pekerjaan_istri', 255)->nullable();
            $table->string('pekerjaan_suami', 255)->nullable();
            $table->text('keluhan')->nullable();
            $table->text('tindakan')->nullable();
            $table->boolean('bayi_lahir')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
