<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Report;
use App\Models\ReportPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'facility_id' => ['required', 'exists:facilities,id'],
            'category' => ['required', 'in:kerusakan,kebersihan,peralatan,keamanan,kelistrikan,jaringan,lainnya'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'photos.*' => ['nullable', 'image', 'max:5120'], // Max 5MB per photo
        ]);

        $report = DB::transaction(function () use ($user, $data, $request) {
            $report = Report::create([
                'reporter_id' => $user->id,
                'facility_id' => $data['facility_id'],
                'category' => $data['category'],
                'description' => $data['description'],
                'priority' => $data['priority'],
                'status' => 'baru',
            ]);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('reports', 'public');
                    ReportPhoto::create([
                        'report_id' => $report->id,
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getClientMimeType(),
                    ]);
                }
            }

            return $report;
        });

        return redirect()->route('dashboard')->with('status', 'Laporan masalah fasilitas berhasil dikirim.');
    }

    public function process(Request $request, Report $report)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPetugas()) {
            abort(403);
        }

        $setMaintenance = $request->boolean('set_maintenance', true);

        DB::transaction(function () use ($report, $user, $setMaintenance) {
            $report->update([
                'status' => 'diproses',
                'handled_by' => $user->id,
                'processed_at' => now(),
            ]);

            if ($setMaintenance) {
                $report->facility->update([
                    'status' => 'maintenance',
                    'maintenance_note' => 'Dalam perbaikan terkait laporan #' . $report->id . ': ' . substr($report->description, 0, 100),
                ]);
            }
        });

        return back()->with('status', 'Laporan diproses dan status fasilitas diperbarui.');
    }

    public function resolve(Request $request, Report $report)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPetugas()) {
            abort(403);
        }

        $data = $request->validate([
            'resolution_note' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($report, $user, $data) {
            $report->update([
                'status' => 'selesai',
                'resolution_note' => $data['resolution_note'],
                'handled_by' => $report->handled_by ?? $user->id,
                'resolved_at' => now(),
            ]);

            // Restore facility status to active
            $report->facility->update([
                'status' => 'active',
                'maintenance_note' => null,
            ]);
        });

        return back()->with('status', 'Laporan diselesaikan dan fasilitas kembali aktif.');
    }

    public function reject(Request $request, Report $report)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isPetugas()) {
            abort(403);
        }

        $data = $request->validate([
            'resolution_note' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($report, $user, $data) {
            $report->update([
                'status' => 'ditolak',
                'resolution_note' => $data['resolution_note'],
                'handled_by' => $report->handled_by ?? $user->id,
                'rejected_at' => now(),
            ]);
        });

        return back()->with('status', 'Laporan ditolak.');
    }
}
