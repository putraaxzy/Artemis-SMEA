<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penugasaan extends Model
{
    use HasFactory;

    protected $table = 'penugasaan';
    
    protected $fillable = [
        'id_tugas',
        'id_siswa',
        'status',
        'link_drive',
        'tanggal_pengumpulan',
        'nilai',
        'catatan_guru',
    ];

    protected $casts = [
        'tanggal_pengumpulan' => 'datetime',
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
     * Scope untuk filter berdasarkan status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk penugasan yang belum dikumpulkan
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope untuk penugasan yang sudah dikirim
     */
    public function scopeDikirim($query)
    {
        return $query->where('status', 'dikirim');
    }
}
