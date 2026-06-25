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

    public function show($id)
    {
        $stampCorrectionRequest = StampCorrectionRequest::where('id', $id)
            ->with(['user', 'attendanceRecord', 'correctionBreaks'])
            ->firstOrFail();

        return view('stamp_correction_request.approve', [
            'stampCorrectionRequest' => $stampCorrectionRequest,
        ]);
    }

    public function update($id)
    {
        $stampCorrectionRequest = StampCorrectionRequest::where('id', $id)
            ->with('correctionBreaks')
            ->firstOrFail();

        // 1. 申請を承認済みに更新
        $stampCorrectionRequest->update(['is_approved' => true]);

        // 2. 勤怠レコードを修正申請の内容で更新
        $stampCorrectionRequest->attendanceRecord->update([
            'clock_in' => $stampCorrectionRequest->new_clock_in,
            'clock_out' => $stampCorrectionRequest->new_clock_out,
            'comment' => $stampCorrectionRequest->new_comment,
        ]);

        // 既存の休憩を全削除して再作成
        $stampCorrectionRequest->attendanceRecord->attendanceBreaks()->delete();

        foreach ($stampCorrectionRequest->correctionBreaks as $correctionBreak) {
            $stampCorrectionRequest->attendanceRecord->attendanceBreaks()->create([
                'break_in' => $correctionBreak->new_break_in,
                'break_out' => $correctionBreak->new_break_out,
            ]);
        }

        return redirect('/stamp_correction_request/list');
    }
}