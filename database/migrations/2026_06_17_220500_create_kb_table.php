<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('no_regis')->unique();
            $table->string('nama_istri');
            $table->string('nama_suami')->nullable();
            $table->integer('umur_istri')->nullable();
            $table->text('alamat')->nullable();
            $table->string('nik_istri')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('tekanan_darah')->nullable();
            $table->string('berat_badan')->nullable();
            $table->string('metode_kb');
            $table->date('tanggal_kunjungan');
            $table->date('tanggal_kunjungan_ulang')->nullable();
            $table->text('keluhan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb');
    }
};
