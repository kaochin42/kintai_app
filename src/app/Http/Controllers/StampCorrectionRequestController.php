<?php

namespace App\Http\Controllers;

use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function index()
    {
        if (Auth::user()->admin_status) {
            $pendingRequests = StampCorrectionRequest::where('is_approved', false)
                ->with(['user', 'attendanceRecord'])
                ->get();

            $approvedRequests = StampCorrectionRequest::where('is_approved', true)
                ->with(['user', 'attendanceRecord'])
                ->get();
        } else {
            $pendingRequests = StampCorrectionRequest::where('user_id', Auth::id())
                ->where('is_approved', false)
                ->with('attendanceRecord')
                ->get();

            $approvedRequests = StampCorrectionRequest::where('user_id', Auth::id())
                ->where('is_approved', true)
                ->with('attendanceRecord')
                ->get();
        }

        return view('stamp_correction_request.list', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
        ]);
    }
}
