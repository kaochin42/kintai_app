<?php

namespace App\Http\Controllers;

use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    /**
     * 修正申請一覧を表示する（一般ユーザーは自分の申請のみ、管理者は全ユーザーの申請）
     */
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

    /**
     * 修正申請の詳細を表示する（一般ユーザーがアクセスした場合は勤怠詳細画面にリダイレクト）
     *
     * @param int $attendance_correct_request_id 修正申請ID
     */
    public function show($attendance_correct_request_id)
    {
        $stampCorrectionRequest = StampCorrectionRequest::where('id', $attendance_correct_request_id)
            ->with(['user', 'attendanceRecord', 'correctionBreaks'])
            ->firstOrFail();

        // 一般ユーザーの場合は勤怠詳細画面にリダイレクト
        if (!Auth::user()->admin_status) {
            return redirect("/attendance/detail/{$stampCorrectionRequest->attendance_record_id}");
        }

        return view('stamp_correction_request.approve', [
            'stampCorrectionRequest' => $stampCorrectionRequest,
        ]);
    }

    /**
     * 修正申請を承認し、勤怠レコードを申請内容で更新する
     *
     * @param int $attendance_correct_request_id 修正申請ID
     */
    public function update($attendance_correct_request_id)
    {
        $stampCorrectionRequest = StampCorrectionRequest::where('id', $attendance_correct_request_id)
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