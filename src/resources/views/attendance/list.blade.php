@extends('layouts.default')

@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance-list center">
    <h1 class="page__title-bar">勤怠一覧</h1>

    <div class="month-nav">
        <a href="/attendance/list?month={{ $prevMonth }}">← 前月</a>
        <span>{{ $currentMonth->format('Y/m') }}</span>
        <a href="/attendance/list?month={{ $nextMonth }}">翌月 →</a>
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
                <td>{{ \Carbon\Carbon::parse($date)->isoFormat('MM/DD(ddd)') }}</td>
                <td>{{ $attendanceRecords[$date]?->clock_in ?? '' }}</td>
                <td>{{ $attendanceRecords[$date]?->clock_out ?? '' }}</td>
                <td>{{ $attendanceRecords[$date]?->break_time ?? '' }}</td>
                <td>{{ $attendanceRecords[$date]?->work_time ?? '' }}</td>
                <td>
                    <a href="/attendance/detail/{{ $attendanceRecords[$date]?->id ?? '' }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection