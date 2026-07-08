@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance-list center">
    <h1 class="page__title-bar">{{ $user->name }}さんの勤怠一覧</h1>

    <div class="month-nav">
        <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $prevMonth }}">← 前月</a>
        <span>{{ $currentMonth->format('Y/m') }}</span>
        <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $nextMonth }}">翌月 →</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dates as $date)
            <tr>
                <td>{{ \Carbon\Carbon::parse($date)->locale('ja')->isoFormat('MM/DD(ddd)') }}</td>
                <td>{{ $attendanceRecords->get($date)?->clock_in?->format('H:i') ?? '' }}</td>
                <td>{{ $attendanceRecords->get($date)?->clock_out?->format('H:i') ?? '' }}</td>
                <td>{{ $attendanceRecords->get($date)?->break_time ?? '' }}</td>
                <td>{{ $attendanceRecords->get($date)?->work_time ?? '' }}</td>
                <td>
                    @if($attendanceRecords->get($date))
                    <a href="/admin/attendance/{{ $attendanceRecords->get($date)->id }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="/admin/attendance/staff/{{ $user->id }}/csv?month={{ $currentMonth->format('Y-m') }}" class="btn btn--big detail-submit">CSV出力</a>
</div>
@endsection