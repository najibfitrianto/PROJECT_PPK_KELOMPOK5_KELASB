<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'facility_id' => ['required', 'exists:facilities,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:255'],
        ]);

        $startTimeStr = $data['start_time'] . ':00';
        $endTimeStr = $data['end_time'] . ':00';

        // Validate time boundaries (07:00 - 20:00)
        if ($startTimeStr < '07:00:00' || $startTimeStr >= '20:00:00') {
            throw ValidationException::withMessages([
                'start_time' => ['Jam mulai harus di antara 07:00 dan 20:00.'],
            ]);
        }

        if ($endTimeStr <= '07:00:00' || $endTimeStr > '20:00:00') {
            throw ValidationException::withMessages([
                'end_time' => ['Jam selesai harus di antara 07:00 dan 20:00.'],
            ]);
        }

        // Validate 30-minute interval step
        $startSec = strtotime($startTimeStr) - strtotime('07:00:00');
        $endSec = strtotime($endTimeStr) - strtotime('07:00:00');

        if ($startSec % 1800 !== 0) {
            throw ValidationException::withMessages([
                'start_time' => ['Jam mulai harus kelipatan 30 menit (contoh: 07:00, 07:30, 08:00).'],
            ]);
        }

        if ($endSec % 1800 !== 0) {
            throw ValidationException::withMessages([
                'end_time' => ['Jam selesai harus kelipatan 30 menit (contoh: 07:30, 08:00, 08:30).'],
            ]);
        }

        // Check facility status
        $facility = Facility::findOrFail($data['facility_id']);
        if ($facility->status !== 'active') {
            throw ValidationException::withMessages([
                'facility_id' => ['Fasilitas ini sedang ' . ($facility->status === 'maintenance' ? 'dalam perbaikan' : 'nonaktif') . ' dan tidak dapat dipesan.'],
            ]);
        }

        // Atomic transaction to check overlap and save
        $reservation = DB::transaction(function () use ($user, $data, $startTimeStr, $endTimeStr) {
            $overlap = Reservation::where('facility_id', $data['facility_id'])
                ->where('reservation_date', $data['reservation_date'])
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) use ($startTimeStr, $endTimeStr) {
                    $query->where('start_time', '<', $endTimeStr)
                          ->where('end_time', '>', $startTimeStr);
                })
                ->lockForUpdate()
                ->first();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'start_time' => ['Waktu reservasi bentrok dengan reservasi lain yang sudah ada pada tanggal dan ruang tersebut.'],
                ]);
            }

            return Reservation::create([
                'user_id' => $user->id,
                'facility_id' => $data['facility_id'],
                'reservation_date' => $data['reservation_date'],
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
                'purpose' => $data['purpose'],
                'status' => 'pending',
            ]);
        });

        return redirect()->route('dashboard')->with('status', 'Pengajuan reservasi berhasil dikirim! Menunggu persetujuan petugas.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $user = Auth::user();

        if ($reservation->user_id !== $user->id && !$user->isAdmin() && !$user->isPetugas()) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        if (!in_array($reservation->status, ['pending', 'approved'])) {
            return back()->with('error', 'Reservasi ini tidak dapat dibatalkan.');
        }

        $reason = $request->input('cancellation_reason', 'Dibatalkan oleh pemohon');

        DB::transaction(function () use ($reservation, $reason) {
            $reservation->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);
        });

        return back()->with('status', 'Reservasi berhasil dibatalkan.');
    }

    public function approve(Request $request, Reservation $reservation)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPetugas()) {
            abort(403);
        }

        DB::transaction(function () use ($reservation, $user) {
            $reservation->update([
                'status' => 'approved',
                'processed_by' => $user->id,
                'processed_at' => now(),
                'approved_at' => now(),
            ]);
        });

        return back()->with('status', 'Reservasi disetujui.');
    }

    public function reject(Request $request, Reservation $reservation)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPetugas()) {
            abort(403);
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'max:255']]);

        DB::transaction(function () use ($reservation, $user, $request) {
            $reservation->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'processed_by' => $user->id,
                'processed_at' => now(),
                'rejected_at' => now(),
            ]);
        });

        return back()->with('status', 'Reservasi ditolak.');
    }
}
