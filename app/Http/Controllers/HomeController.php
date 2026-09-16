<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $facilities = Facility::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('home', compact('facilities'));
    }

    public function facilities(Request $request)
    {
        $query = Facility::query();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($location = $request->input('location')) {
            $query->where('location', 'like', '%' . $location . '%');
        }

        if ($capacity = $request->integer('capacity')) {
            $query->where('capacity', '>=', $capacity);
        }

        $facilities = $query->orderBy('name')->get();
        $types = Facility::distinct()->pluck('type')->filter();

        return view('facilities.index', compact('facilities', 'types'));
    }
}
