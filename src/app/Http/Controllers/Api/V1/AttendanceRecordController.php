<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;

class AttendanceRecordController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->input('per_page', 20), 100);

        $records = AttendanceRecord::with(['user', 'attendanceBreaks'])
            ->when($request->user_id, function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->date, function ($q) use ($request) {
                $q->where('date', $request->date);
            })
            ->when($request->month, function ($q) use ($request) {
                $q->where('date', 'like', $request->month . '%');
            })
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }

    public function show(AttendanceRecord $attendanceRecord)
    {
        $attendanceRecord->load(['user', 'attendanceBreaks', 'stampCorrectionRequests']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function store(StoreAttendanceRecordRequest $request)
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

    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord)
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());

        $attendanceRecord->load(['user', 'attendanceBreaks']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    public function destroy(AttendanceRecord $attendanceRecord)
    {
        $this->authorize('delete', $attendanceRecord);
        
        $attendanceRecord->delete();

        return response()->noContent();
    }
}
