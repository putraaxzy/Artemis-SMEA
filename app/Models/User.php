<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'telepon',
        'role',
        'kelas',
        'jurusan',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke tugas yang dibuat oleh guru
     */
    public function tugas()
    {
        return $this->hasMany(Tugas::class, 'id_guru');
    }

    /**
     * Relasi ke penugasan untuk siswa
     */
    public function penugasan()
    {
        return $this->hasMany(Penugasaan::class, 'id_siswa');
    }

    /**
     * Relasi ke bot reminder
     */
    public function botReminders()
    {
        return $this->hasMany(BotReminder::class, 'id_siswa');
    }
}
