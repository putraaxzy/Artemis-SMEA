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
        Schema::create('penugasaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_tugas');
            $table->unsignedBigInteger('id_siswa');
            $table->enum('status', ['pending', 'dikirim', 'selesai', 'ditolak'])->default('pending');
            $table->text('link_drive')->nullable();
            $table->timestamp('tanggal_pengumpulan')->nullable();
            $table->timestamps();

            $table->foreign('id_tugas')->references('id')->on('tugas')->onDelete('cascade');
            $table->foreign('id_siswa')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penugasaan');
    }
};
