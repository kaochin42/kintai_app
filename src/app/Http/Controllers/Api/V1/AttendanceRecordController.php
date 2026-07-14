<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を取得する（user_id・date・monthで絞り込み、ページネーション対応）
     *
     * @param Request $request クエリパラメータ（user_id, date, month, page, per_page）
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min($request->input('per_page', 20), 100);

        $records = AttendanceRecord::with(['user', 'attendanceBreaks'])
            ->when($request->user_id, function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->date, function ($q) use ($request) {
                $q->whereDate('date', $request->date);
            })
            ->when($request->month, function ($q) use ($request) {
                $q->where('date', 'like', $request->month . '%');
            })
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * 勤怠詳細を取得する（ユーザー・休憩・修正申請を含む）
     *
     * @param AttendanceRecord $attendanceRecord ルートモデルバインディングで解決された勤怠レコード
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'attendanceBreaks', 'stampCorrectionRequests']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を新規登録する（認証済みユーザー本人の勤怠として作成）
     *
     * @param StoreAttendanceRecordRequest $request バリデーション済みの登録内容
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = $request->user()->attendanceRecords()->create([
            'date' => $request->date,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'comment' => $request->comment,
        ]);

        $attendanceRecord->load(['user', 'attendanceBreaks']);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠を更新する（本人または管理者のみ許可）
     *
     * @param UpdateAttendanceRecordRequest $request バリデーション済みの更新内容
     * @param AttendanceRecord $attendanceRecord 更新対象の勤怠レコード
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());

        $attendanceRecord->load(['user', 'attendanceBreaks']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を削除する（本人または管理者のみ許可）
     *
     * @param AttendanceRecord $attendanceRecord 削除対象の勤怠レコード
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);
        
        $attendanceRecord->delete();

        return response()->noContent();
    }
}
