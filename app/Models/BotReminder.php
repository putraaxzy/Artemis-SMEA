<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotReminder extends Model
{
    use HasFactory;

    protected $table = 'bot_reminder';
    
    protected $fillable = [
        'id_tugas',
        'id_siswa',
        'pesan',
        'id_pesan',
    ];

    /**
     * Relasi ke tugas
     */
    public function tugas()
    {
        return $this->belongsTo(Tugas::class, 'id_tugas');
    }

    /**
     * Relasi ke user (siswa)
     */
    public function siswa()
    {
        return $this->belongsTo(User::class, 'id_siswa');
    }

    /**
     * Scope untuk reminder berdasarkan tugas
     */
    public function scopeByTugas($query, $idTugas)
    {
        return $query->where('id_tugas', $idTugas);
    }

    /**
     * Scope untuk reminder berdasarkan siswa
     */
    public function scopeBySiswa($query, $idSiswa)
    {
        return $query->where('id_siswa', $idSiswa);
    }
}