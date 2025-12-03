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
        // Tambah field tampilkan_nilai di tabel tugas
        Schema::table('tugas', function (Blueprint $table) {
            $table->boolean('tampilkan_nilai')->default(false)->after('tipe_pengumpulan');
        });

        // Tambah field nilai di tabel penugasaan
        Schema::table('penugasaan', function (Blueprint $table) {
            $table->integer('nilai')->nullable()->after('tanggal_pengumpulan');
            $table->text('catatan_guru')->nullable()->after('nilai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->dropColumn('tampilkan_nilai');
        });

        Schema::table('penugasaan', function (Blueprint $table) {
            $table->dropColumn(['nilai', 'catatan_guru']);
        });
    }
};
