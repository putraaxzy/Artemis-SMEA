<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    use HasFactory;

    protected $table = 'tugas';
    
    protected $fillable = [
        'id_guru',
        'judul',
        'target',
        'id_target',
        'tipe_pengumpulan',
    ];

    protected $casts = [
        'id_target' => 'array',
    ];

    /**
     * Relasi ke user (guru pembuat tugas)
     */
    public function guru()
    {
        return $this->belongsTo(User::class, 'id_guru');
    }

    /**
     * Relasi ke penugasan
     */
    public function penugasan()
    {
        return $this->hasMany(Penugasaan::class, 'id_tugas');
    }

    /**
     * Relasi ke bot reminder
     */
    public function botReminders()
    {
        return $this->hasMany(BotReminder::class, 'id_tugas');
    }
}