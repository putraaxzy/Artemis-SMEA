<?php

namespace App\Http\Controllers;

use App\Models\Tugas;
use App\Models\Penugasaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TugasController extends Controller
{
    /**
     * Buat tugas baru (hanya guru)
     */
    public function buatTugas(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya guru yang dapat membuat tugas'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'target' => 'required|in:siswa,kelas',
            'id_target' => 'required|array|min:1',
            'tipe_pengumpulan' => 'required|in:link,langsung',
            'tampilkan_nilai' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => $validator->errors()->first()
            ], 400);
        }

        // Validasi target berdasarkan tipe
        $siswaIds = [];
        if ($request->target === 'siswa') {
            // Validasi langsung ID siswa
            $siswaIds = $request->id_target;
            $siswaCount = User::whereIn('id', $siswaIds)->where('role', 'siswa')->count();
            if ($siswaCount !== count($siswaIds)) {
                return response()->json([
                    'berhasil' => false,
                    'pesan' => 'Beberapa ID siswa tidak valid'
                ], 400);
            }
        } else {
            // Target kelas: ambil semua siswa dari kelas yang dipilih
            foreach ($request->id_target as $kelasInfo) {
                if (!isset($kelasInfo['kelas']) || !isset($kelasInfo['jurusan'])) {
                    return response()->json([
                        'berhasil' => false,
                        'pesan' => 'Format target kelas harus berisi kelas dan jurusan'
                    ], 400);
                }
                
                $siswaKelas = User::where('role', 'siswa')
                    ->where('kelas', $kelasInfo['kelas'])
                    ->where('jurusan', $kelasInfo['jurusan'])
                    ->pluck('id')
                    ->toArray();
                
                $siswaIds = array_merge($siswaIds, $siswaKelas);
            }
            $siswaIds = array_unique($siswaIds);
        }

        if (empty($siswaIds)) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tidak ada siswa ditemukan untuk target yang dipilih'
            ], 400);
        }

        // buat tugas
        $tugas = Tugas::create([
            'id_guru' => $user->id,
            'judul' => $request->judul,
            'target' => $request->target,
            'id_target' => $request->id_target,
            'tipe_pengumpulan' => $request->tipe_pengumpulan,
            'tampilkan_nilai' => $request->tampilkan_nilai ?? false,
        ]);

        // buat tugas untuk setiap siswa
        foreach ($siswaIds as $siswaId) {
            Penugasaan::create([
                'id_tugas' => $tugas->id,
                'id_siswa' => $siswaId,
                'status' => 'pending'
            ]);
        }

        return response()->json([
            'berhasil' => true,
            'data' => [
                'id' => $tugas->id,
                'id_guru' => $tugas->id_guru,
                'judul' => $tugas->judul,
                'target' => $tugas->target,
                'id_target' => $tugas->id_target,
                'tipe_pengumpulan' => $tugas->tipe_pengumpulan,
                'tampilkan_nilai' => $tugas->tampilkan_nilai,
                'total_siswa' => count($siswaIds),
                'dibuat_pada' => $tugas->created_at->toISOString(),
                'diperbarui_pada' => $tugas->updated_at->toISOString()
            ],
            'pesan' => 'Tugas berhasil dibuat'
        ], 201);
    }

    /**
     * Ambil semua tugas (filter berdasarkan role)
     */
    public function ambilTugas()
    {
        $user = auth()->user();

        if ($user->role === 'guru') {
            $tugas = Tugas::where('id_guru', $user->id)
                ->with(['penugasan'])
                ->latest()
                ->get();
        } else {
            $tugas = Tugas::whereHas('penugasan', function($query) use ($user) {
                $query->where('id_siswa', $user->id);
            })
            ->with(['guru:id,name', 'penugasan' => function($query) use ($user) {
                $query->where('id_siswa', $user->id);
            }])
            ->latest()
            ->get();
        }

        return response()->json([
            'berhasil' => true,
            'data' => $tugas->map(function($t) use ($user) {
                $data = [
                    'id' => $t->id,
                    'judul' => $t->judul,
                    'target' => $t->target,
                    'tipe_pengumpulan' => $t->tipe_pengumpulan,
                    'tampilkan_nilai' => $t->tampilkan_nilai,
                    'dibuat_pada' => $t->created_at->toISOString(),
                ];

                if ($user->role === 'guru') {
                    $data['total_siswa'] = $t->penugasan->count();
                    $data['pending'] = $t->penugasan->where('status', 'pending')->count();
                    $data['dikirim'] = $t->penugasan->where('status', 'dikirim')->count();
                    $data['selesai'] = $t->penugasan->where('status', 'selesai')->count();
                } else {
                    $penugasan = $t->penugasan->first();
                    $data['guru'] = $t->guru->name;
                    $data['status'] = $penugasan->status ?? 'pending';
                    
                    // Siswa hanya bisa lihat nilai jika guru aktifkan fitur tampilkan_nilai
                    if ($t->tampilkan_nilai && $penugasan) {
                        $data['nilai'] = $penugasan->nilai;
                        $data['catatan_guru'] = $penugasan->catatan_guru;
                    }
                }

                return $data;
            }),
            'pesan' => 'Data tugas berhasil diambil'
        ]);
    }

    /**
     * Ajukan penugasan (siswa)
     */
    public function ajukanPenugasan(Request $request, $id)
    {
        $user = auth()->user();

        if ($user->role !== 'siswa') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya siswa yang dapat mengajukan penugasan'
            ], 403);
        }

        // Ambil penugasan dan tugas untuk cek tipe_pengumpulan
        $penugasan = Penugasaan::where('id_tugas', $id)
            ->where('id_siswa', $user->id)
            ->with('tugas:id,tipe_pengumpulan')
            ->first();

        if (!$penugasan) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Penugasan tidak ditemukan'
            ], 404);
        }

        if ($penugasan->status === 'selesai') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Penugasan sudah selesai, tidak dapat diubah'
            ], 400);
        }

        // Validasi berbeda berdasarkan tipe pengumpulan
        $tipePengumpulan = $penugasan->tugas->tipe_pengumpulan;
        
        if ($tipePengumpulan === 'link') {
            $validator = Validator::make($request->all(), [
                'link_drive' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'berhasil' => false,
                    'pesan' => $validator->errors()->first()
                ], 400);
            }

            $penugasan->update([
                'status' => 'dikirim',
                'link_drive' => $request->link_drive,
                'tanggal_pengumpulan' => now()
            ]);
        } else {
            // Tipe langsung: tidak perlu link_drive, langsung update status
            $penugasan->update([
                'status' => 'dikirim',
                'tanggal_pengumpulan' => now()
            ]);
        }

        return response()->json([
            'berhasil' => true,
            'data' => [
                'id' => $penugasan->id,
                'id_tugas' => $penugasan->id_tugas,
                'id_siswa' => $penugasan->id_siswa,
                'status' => $penugasan->status,
                'link_drive' => $penugasan->link_drive,
                'tanggal_pengumpulan' => $penugasan->tanggal_pengumpulan->toISOString(),
                'dibuat_pada' => $penugasan->created_at->toISOString(),
                'diperbarui_pada' => $penugasan->updated_at->toISOString()
            ],
            'pesan' => 'Penugasan berhasil diajukan'
        ]);
    }

    /**
     * Ambil penugasan pending untuk tugas tertentu (guru)
     */
    public function ambilPenugasanPending($id)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya guru yang dapat melihat data ini'
            ], 403);
        }

        $tugas = Tugas::where('id', $id)
            ->where('id_guru', $user->id)
            ->first();

        if (!$tugas) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tugas tidak ditemukan'
            ], 404);
        }

        $penugasan = Penugasaan::where('id_tugas', $id)
            ->where('status', 'pending')
            ->with(['siswa:id,name,telepon,kelas,jurusan'])
            ->get();

        return response()->json([
            'berhasil' => true,
            'data' => $penugasan->map(function ($p) {
                return [
                    'id' => $p->id,
                    'id_siswa' => $p->id_siswa,
                    'siswa' => [
                        'id' => $p->siswa->id,
                        'name' => $p->siswa->name,
                        'telepon' => $p->siswa->telepon,
                        'kelas' => $p->siswa->kelas,
                        'jurusan' => $p->siswa->jurusan,
                    ],
                    'status' => $p->status,
                    'dibuat_pada' => $p->created_at->toISOString(),
                ];
            }),
            'pesan' => 'Data penugasan pending berhasil diambil'
        ]);
    }

    /**
     * Ambil detail tugas dengan semua penugasan (guru)
     */
    public function ambilDetailTugas($id)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya guru yang dapat melihat data ini'
            ], 403);
        }

        $tugas = Tugas::where('id', $id)
            ->where('id_guru', $user->id)
            ->with(['penugasan.siswa:id,name,username,telepon,kelas,jurusan'])
            ->first();

        if (!$tugas) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Tugas tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }

        return response()->json([
            'berhasil' => true,
            'data' => [
                'id' => $tugas->id,
                'judul' => $tugas->judul,
                'target' => $tugas->target,
                'id_target' => $tugas->id_target,
                'tipe_pengumpulan' => $tugas->tipe_pengumpulan,
                'tampilkan_nilai' => $tugas->tampilkan_nilai,
                'dibuat_pada' => $tugas->created_at->toISOString(),
                'statistik' => [
                    'total_siswa' => $tugas->penugasan->count(),
                    'pending' => $tugas->penugasan->where('status', 'pending')->count(),
                    'dikirim' => $tugas->penugasan->where('status', 'dikirim')->count(),
                    'selesai' => $tugas->penugasan->where('status', 'selesai')->count(),
                    'ditolak' => $tugas->penugasan->where('status', 'ditolak')->count(),
                ],
                'penugasan' => $tugas->penugasan->map(function($p) use ($tugas) {
                    $data = [
                        'id' => $p->id,
                        'siswa' => [
                            'id' => $p->siswa->id,
                            'username' => $p->siswa->username,
                            'name' => $p->siswa->name,
                            'telepon' => $p->siswa->telepon,
                            'kelas' => $p->siswa->kelas,
                            'jurusan' => $p->siswa->jurusan,
                        ],
                        'status' => $p->status,
                        'link_drive' => $p->link_drive,
                        'tanggal_pengumpulan' => $p->tanggal_pengumpulan?->toISOString(),
                        'dibuat_pada' => $p->created_at->toISOString(),
                        'diperbarui_pada' => $p->updated_at->toISOString(),
                    ];
                    
                    // Hanya tampilkan nilai jika guru mengaktifkan fitur tampilkan_nilai
                    if ($tugas->tampilkan_nilai) {
                        $data['nilai'] = $p->nilai;
                        $data['catatan_guru'] = $p->catatan_guru;
                    }
                    
                    return $data;
                })
            ],
            'pesan' => 'Detail tugas berhasil diambil'
        ]);
    }

    /**
     * Update status penugasan (guru)
     */
    public function updateStatusPenugasan(Request $request, $id)
    {
        $user = auth()->user();

        if ($user->role !== 'guru') {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Hanya guru yang dapat mengubah status penugasan'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:selesai,ditolak',
            'nilai' => 'nullable|integer|min:0|max:100',
            'catatan_guru' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'berhasil' => false,
                'pesan' => $validator->errors()->first()
            ], 400);
        }

        $penugasan = Penugasaan::findOrFail($id);
        $tugas = $penugasan->tugas;

        if ($tugas->id_guru !== $user->id) {
            return response()->json([
                'berhasil' => false,
                'pesan' => 'Anda tidak memiliki akses untuk mengubah penugasan ini'
            ], 403);
        }

        $penugasan->update([
            'status' => $request->status,
            'nilai' => $request->nilai,
            'catatan_guru' => $request->catatan_guru,
        ]);

        return response()->json([
            'berhasil' => true,
            'data' => [
                'id' => $penugasan->id,
                'status' => $penugasan->status,
                'nilai' => $penugasan->nilai,
                'catatan_guru' => $penugasan->catatan_guru,
                'diperbarui_pada' => $penugasan->updated_at->toISOString()
            ],
            'pesan' => 'Status penugasan berhasil diubah'
        ]);
    }
}
