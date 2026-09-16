<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function verifyUser(Request $request, User $user)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            abort(403);
        }

        $user->update([
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        return back()->with('status', 'Akun ' . $user->name . ' telah diverifikasi.');
    }

    public function rejectUser(Request $request, User $user)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            abort(403);
        }

        $user->update([
            'verification_status' => 'rejected',
        ]);

        return back()->with('status', 'Pendaftaran akun ' . $user->name . ' ditolak.');
    }

    public function toggleBlockUser(Request $request, User $user)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Tidak dapat mengubah status akun sesama Admin.');
        }

        $newStatus = $user->account_status === 'blocked' ? 'active' : 'blocked';
        $user->update(['account_status' => $newStatus]);

        return back()->with('status', 'Status akun ' . $user->name . ' diubah menjadi ' . $newStatus . '.');
    }

    public function createPetugas(Request $request)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'petugas',
            'account_status' => 'active',
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        return back()->with('status', 'Akun Petugas berhasil dibuat.');
    }

    public function storeFacility(Request $request)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin() && !$admin->isPetugas()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:150'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,maintenance,inactive'],
            'maintenance_note' => ['nullable', 'string'],
        ]);

        Facility::create($data);

        return back()->with('status', 'Fasilitas baru berhasil ditambahkan.');
    }

    public function updateFacility(Request $request, Facility $facility)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin() && !$admin->isPetugas()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:150'],
            'capacity' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,maintenance,inactive'],
            'maintenance_note' => ['nullable', 'string'],
        ]);

        $facility->update($data);

        return back()->with('status', 'Data fasilitas berhasil diperbarui.');
    }

    public function destroyFacility(Facility $facility)
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            abort(403);
        }

        if ($facility->reservations()->exists() || $facility->reports()->exists()) {
            return back()->with('error', 'Fasilitas tidak dapat dihapus karena memiliki riwayat reservasi/laporan.');
        }

        $facility->delete();

        return back()->with('status', 'Fasilitas berhasil dihapus.');
    }
}
