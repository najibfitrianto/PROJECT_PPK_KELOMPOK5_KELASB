<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Report;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $facilities = Facility::orderBy('name')->get();

        if ($user->isAdmin()) {
            $pendingUsers = User::where('verification_status', 'pending')->orderByDesc('created_at')->get();
            $allUsers = User::with('verifier')->orderByDesc('created_at')->get();
            $reservations = Reservation::with(['user', 'facility', 'processor'])->orderByDesc('created_at')->get();
            $reports = Report::with(['reporter', 'facility', 'handler', 'photos'])->orderByDesc('created_at')->get();

            $stats = [
                'pending_users' => $pendingUsers->count(),
                'pending_reservations' => $reservations->where('status', 'pending')->count(),
                'open_reports' => $reports->whereIn('status', ['baru', 'diproses'])->count(),
                'total_facilities' => $facilities->count(),
            ];

            return view('dashboard.index', compact(
                'user', 'facilities', 'pendingUsers', 'allUsers',
                'reservations', 'reports', 'stats'
            ));
        }

        if ($user->isPetugas()) {
            $reservations = Reservation::with(['user', 'facility', 'processor'])->orderByDesc('created_at')->get();
            $reports = Report::with(['reporter', 'facility', 'handler', 'photos'])->orderByDesc('created_at')->get();

            $stats = [
                'pending_reservations' => $reservations->where('status', 'pending')->count(),
                'open_reports' => $reports->whereIn('status', ['baru', 'diproses'])->count(),
                'active_facilities' => $facilities->where('status', 'active')->count(),
            ];

            return view('dashboard.index', compact(
                'user', 'facilities', 'reservations', 'reports', 'stats'
            ));
        }

        // Regular User
        $myReservations = Reservation::with(['facility', 'processor'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $myReports = Report::with(['facility', 'handler', 'photos'])
            ->where('reporter_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'active_reservations' => $myReservations->whereIn('status', ['pending', 'approved'])->count(),
            'open_reports' => $myReports->whereIn('status', ['baru', 'diproses'])->count(),
            'available_facilities' => $facilities->where('status', 'active')->count(),
        ];

        return view('dashboard.index', compact(
            'user', 'facilities', 'myReservations', 'myReports', 'stats'
        ));
    }
}
