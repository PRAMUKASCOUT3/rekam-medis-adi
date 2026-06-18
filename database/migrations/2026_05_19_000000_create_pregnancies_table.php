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
        Schema::create('pregnancies', function (Blueprint $table) {
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
            $table->unsignedInteger('gravida')->default(0);
            $table->unsignedInteger('partus')->default(0);
            $table->unsignedInteger('abortus')->default(0);
            $table->date('hpht')->nullable();
            $table->date('tp')->nullable();
            $table->text('pemeriksaan')->nullable();
            $table->text('keluhan')->nullable();
            $table->text('terapi')->nullable();
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
        Schema::dropIfExists('pregnancies');
    }
};
