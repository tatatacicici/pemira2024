<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Calon;
use App\Models\Kelas;
use App\Models\Jurusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    public function index()
    {
        return view("dashboard.admin.index", [
            "title" => "Home | Pemilihan Raya 2024",
            "active" => "home",
            "sudah_memilih"  => User::where('bem_id', '!=', null)->where('bpm_id', '!=', null)->where('hmj_id', '!=', null),
            "belum_memilih"  => User::where('bem_id', '=', null)->where('bpm_id', '=', null)->where('hmj_id', '=', null),
            "suara_sah"  => User::where('is_active', '=', '1'),
            "suara_tidak_sah"  => User::where('is_active', '=', '0'),
        ]);
    }

    public function mahasiswa(Request $request)
    {
        // Membuat Sebuah Fitur Filter. dengan Menggunakan Query Scope. => Cek Models
        if (request(['search', 'kelas', 'show'])) {
            // Jika Menggunakan Filter
            // with() menggunakan Relationship Database(Eloquent ORM Laravel), paginate() Menggunakan Pagination Database
            $users = User::with('kelas', 'jurusan', 'bem')->where('status', 'aktif')->filter(request(['search', 'kelas']))->paginate((request('show') ?? 100))->withQueryString();
        } else {
            // Jika tidak menggunakan Filter
            $users = User::with('kelas', 'jurusan', 'bem')->where('status', 'aktif')->paginate(100);
        }

        return view("dashboard.admin.mahasiswa", [
            "title" => "Daftar Mahasiswa | Pemilihan Raya 2024",
            "active" => "mahasiswa",
            "users" => $users,
        ]);
    }

    public function susulan()
    {
        // Membuat Sebuah Fitur Filter. dengan Menggunakan Query Scope. => Cek Models
        if (request(['search', 'kelas', 'show'])) {
            // Jika Menggunakan Filter
            // with() menggunakan Relationship Database(Eloquent ORM Laravel), paginate() Menggunakan Pagination Database
            $users = User::with('kelas', 'jurusan', 'calon')->where('status', 'tidak aktif')->filter(request(['search', 'kelas']))->paginate((request('show') ?? 10))->withQueryString();
        } else {
            // Jika tidak menggunakan Filter
            $users = User::with('kelas', 'jurusan', 'calon')->where('status', 'tidak aktif')->paginate(10);
        }

        return view("dashboard.admin.susulan", [
            "title" => "Daftar Mahasiswa Susulan | Pemilihan Raya 2024",
            "active" => "susulan",
            "users" => $users,
        ]);
    }

    public function calon()
    {
        if (request(['search', 'kelas', 'show'])) {
            // Jika Menggunakan Filter
            // with() menggunakan Relationship Database(Eloquent ORM Laravel), paginate() Menggunakan Pagination Database
            $users = Calon::with('kelas')->filter(request(['search', 'kelas']))->paginate((request('show') ?? 10))->withQueryString();
        } else {
            // Jika tidak menggunakan Filter
            $users = Calon::with('kelas')->paginate(10);
        }

        return view("dashboard.admin.calon", [
            "title" => "Daftar Calon | Pemilihan Raya 2024",
            "active" => "calon",
            "users" => $users,
            "data"  => Calon::with('kelas'),
        ]);
    }

    public function uploadFoto()
    {
        return view('dashboard.pemilihan.upload_foto');
    }

    public function beranda()
    {
        return view('dashboard.pemilihan.beranda', [
            'bem' => Calon::with('kelas_ketua', 'kelas')
                ->where('type', 'bem')
                ->get(),
            'bpm' => Calon::with('kelas_ketua')
                ->where('type', 'bpm')
                ->where('jurusan_id', Auth::user()->jurusan_id)
                ->get(),
            'hmj' => Calon::with('kelas_ketua', 'kelas')
                ->where('type', 'hmj')
                ->where('jurusan_id', Auth::user()->jurusan_id)
                ->get(),
            'user' => User::with('jurusan', 'kelas')
                ->where('jurusan_id', Auth::user()->jurusan_id)
                ->get()
                ->first()->kelas->jurusan->id,
        ]);
    }

    public function tampilHMJ($id)
    {
        $user = User::find($id);
        $kelas = $user->kelas;
        $hmj = DB::table('calons')
            ->join('kelas', 'calons.kelas_ketua_id', '=', 'kelas.id')
            ->join('jurusans', 'kelas.jurusan_id', '=', 'jurusans.id')
            ->where('kelas.jurusan_id', '=', $kelas->jurusan_id)
            ->where('type', '=', 'hmj')
            ->select('calons.*', 'kelas.nama_kelas', 'jurusans.nama_jurusan')
            ->get();
        return view('dashboard.pemilihan.himpunan', compact('hmj'));
    }

    public function tampilBEM($id)
    {
        $user = User::find($id);
        $kelas = $user->kelas;
        $bem = DB::table('calons')
            ->join('kelas as ketua', 'calons.kelas_ketua_id', '=', 'ketua.id')
            ->join('kelas as wakil', 'calons.kelas_wakil_id', '=', 'wakil.id')
            ->join('jurusans', 'ketua.jurusan_id', '=', 'jurusans.id')
            ->where('type', '=', 'bem')
            ->get();
        return view('dashboard.pemilihan.bem', compact('bem'));
    }

    public function tampilBPM($id)
    {
        $user = User::find($id);
        $kelas = $user->kelas;
        $bpm = DB::table('calons')
            ->join('kelas', 'calons.kelas_ketua_id', '=', 'kelas.id')
            ->join('jurusans', 'kelas.jurusan_id', '=', 'jurusans.id')
            ->where('kelas.jurusan_id', '=', $kelas->jurusan_id)
            ->where('type', '=', 'bpm')
            ->select('calons.*', 'kelas.nama_kelas', 'jurusans.nama_jurusan')
            ->get();
        return view('dashboard.pemilihan.bpm', compact('bpm'));
    }
}
