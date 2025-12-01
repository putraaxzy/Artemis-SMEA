<?php

namespace App\Http\Controllers;

use App\Models\BotReminder;
use App\Models\Penugasaan;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BotController extends Controller
{
    public function ambilSiswaPerluReminder()
    {
        $penugasanPending = Penugasaan::where('status', 'pending')
            ->with([
                'tugas:id,judul,id_guru',
                'tugas.guru:id,name',
                'siswa:id,username,name,telepon,kelas,jurusan'
            ])
            ->whereDoesntHave('siswa.botReminders', function($query) {
                $query->where('created_at', '>=', now()->subHours(24))
                      ->whereColumn('bot_reminder.id_tugas', 'penugasaan.id_tugas');
            })
            ->get();

        $data = $penugasanPending->map(function($p) {
            return [
                'id_penugasan' => $p->id,
                'tugas' => [
                    'id' => $p->tugas->id,
                    'judul' => $p->tugas->judul,
                    'guru' => $p->tugas->guru->name,
                ],
                'siswa' => [
                    'id' => $p->siswa->id,
                    'username' => $p->siswa->username,
                    'name' => $p->siswa->name,
                    'telepon' => $p->siswa->telepon,
                    'kelas' => $p->siswa->kelas,
                    'jurusan' => $p->siswa->jurusan,
                ],
                'dibuat_pada' => $p->created_at->toISOString(),
            ];
        });

        return response()->json([
            'berhasil' => true,
            'data' => $data,
            'total' => $data->count(),
            'pesan' => 'Data siswa yang perlu reminder berhasil diambil'
        ]);
    }

    public function ambilSiswaPendingByTugas($idTugas)
    {
        $tugas = Tugas::find($idTugas);
        
        if (!$tugas) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tugas tidak ditemukan'
            ], 404);
        }

        $penugasanPending = Penugasaan::where('id_tugas', $idTugas)
            ->where('status', 'pending')
            ->with(['siswa:id,username,name,telepon,kelas,jurusan'])
            ->whereDoesntHave('siswa.botReminders', function($query) use ($idTugas) {
                $query->where('id_tugas', $idTugas)
                      ->where('created_at', '>=', now()->subHours(24));
            })
            ->get();

        $data = $penugasanPending->map(function($p) use ($tugas) {
            return [
                'id_penugasan' => $p->id,
                'tugas_judul' => $tugas->judul,
                'siswa' => [
                    'id' => $p->siswa->id,
                    'username' => $p->siswa->username,
                    'name' => $p->siswa->name,
                    'telepon' => $p->siswa->telepon,
                    'kelas' => $p->siswa->kelas,
                    'jurusan' => $p->siswa->jurusan,
                ],
                'dibuat_pada' => $p->created_at->toISOString(),
            ];
        });

        return response()->json([
            'berhasil' => true,
            'data' => [
                'tugas_id' => $tugas->id,
                'tugas_judul' => $tugas->judul,
                'siswa_pending' => $data,
                'total' => $data->count(),
            ],
            'pesan' => 'Data siswa pending berhasil diambil'
        ]);
    }

    public function catatReminder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_tugas' => 'required|exists:tugas,id',
            'id_siswa' => 'required|exists:users,id',
            'pesan' => 'required|string',
            'id_pesan' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => $validator->errors()->first()
            ], 400);
        }

        $penugasan = Penugasaan::where('id_tugas', $request->id_tugas)
            ->where('id_siswa', $request->id_siswa)
            ->first();

        if (!$penugasan) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Penugasan tidak ditemukan'
            ], 404);
        }

        if ($penugasan->status !== 'pending') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Siswa sudah submit tugas ini, reminder tidak dicatat',
                'status_penugasan' => $penugasan->status
            ], 400);
        }

        $existingReminder = BotReminder::where('id_pesan', $request->id_pesan)->first();
        
        if ($existingReminder) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Reminder dengan ID pesan ini sudah dicatat',
                'reminder_id' => $existingReminder->id
            ], 400);
        }

        $reminder = BotReminder::create([
            'id_tugas' => $request->id_tugas,
            'id_siswa' => $request->id_siswa,
            'pesan' => $request->pesan,
            'id_pesan' => $request->id_pesan,
        ]);

        return response()->json([
            'berhasil' => true,
            'data' => [
                'id' => $reminder->id,
                'id_tugas' => $reminder->id_tugas,
                'id_siswa' => $reminder->id_siswa,
                'pesan' => $reminder->pesan,
                'id_pesan' => $reminder->id_pesan,
                'dibuat_pada' => $reminder->created_at->toISOString()
            ],
            'pesan' => 'Reminder berhasil dicatat'
        ], 201);
    }

    public function kirimReminder($idTugas)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya guru yang dapat mengirim reminder'
            ], 403);
        }

        $tugas = Tugas::where('id', $idTugas)
            ->where('id_guru', $user->id)
            ->first();

        if (!$tugas) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tugas tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        $penugasanPending = Penugasaan::where('id_tugas', $idTugas)
            ->where('status', 'pending')
            ->with(['siswa:id,name,username,telepon'])
            ->whereDoesntHave('siswa.botReminders', function($query) use ($idTugas) {
                $query->where('id_tugas', $idTugas)
                      ->where('created_at', '>=', now()->subHours(24));
            })
            ->get();

        if ($penugasanPending->isEmpty()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tidak ada siswa yang perlu diingatkan'
            ], 404);
        }

        $siswaList = $penugasanPending->map(function($p) use ($tugas) {
            return [
                'id_penugasan' => $p->id,
                'id_siswa' => $p->siswa->id,
                'username' => $p->siswa->username,
                'name' => $p->siswa->name,
                'telepon' => $p->siswa->telepon,
            ];
        });

        return response()->json([
            'berhasil' => true,
            'data' => [
                'tugas_id' => $tugas->id,
                'tugas_judul' => $tugas->judul,
                'total_siswa' => $siswaList->count(),
                'siswa' => $siswaList,
                'pesan_template' => "Reminder: Anda memiliki tugas '{$tugas->judul}' yang belum dikerjakan. Segera submit tugas Anda!",
            ],
            'pesan' => 'Daftar siswa yang perlu diingatkan berhasil diambil'
        ]);
    }

    public function ambilReminder($idTugas)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya guru yang dapat melihat history reminder'
            ], 403);
        }

        $tugas = Tugas::where('id', $idTugas)
            ->where('id_guru', $user->id)
            ->first();

        if (!$tugas) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tugas tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        $reminders = BotReminder::where('id_tugas', $idTugas)
            ->with(['siswa:id,username,name,telepon'])
            ->latest()
            ->get();

        return response()->json([
            'berhasil' => true,
            'data' => $reminders->map(function($r) {
                return [
                    'id' => $r->id,
                    'siswa' => [
                        'id' => $r->siswa->id,
                        'username' => $r->siswa->username,
                        'name' => $r->siswa->name,
                        'telepon' => $r->siswa->telepon,
                    ],
                    'pesan' => $r->pesan,
                    'id_pesan' => $r->id_pesan,
                    'dibuat_pada' => $r->created_at->toISOString()
                ];
            }),
            'total' => $reminders->count(),
            'pesan' => 'History reminder berhasil diambil'
        ]);
    }

    public function webhookStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pesan' => 'required|string',
            'status' => 'required|in:sent,delivered,read,failed',
            'error_message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => $validator->errors()->first()
            ], 400);
        }
        
        return response()->json([
            'berhasil' => true,
            'pesan' => 'Status update diterima'
        ]);
    }
}
