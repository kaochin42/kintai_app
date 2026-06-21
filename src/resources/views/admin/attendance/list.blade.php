@extends('layouts.default')

@section('title','勤怠一覧（管理者）')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance-list center">
    <h1 class="page__title-bar">{{ $date->isoFormat('YYYY年M月D日') }}の勤怠</h1>

    <div class="date-nav">
        <a href="/admin/attendance/list?date={{ $prevDate }}">← 前日</a>
        <span>{{ $date->format('Y/m/d') }}</span>
        <a href="/admin/attendance/list?date={{ $nextDate }}">翌日 →</a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendanceRecords as $record)
            <tr>
                <td>{{ $record->user->name }}</td>
                <td>{{ $record->clock_in->format('H:i') }}</td>
                <td>{{ $record->clock_out?->format('H:i') }}</td>
                <td>{{ $record->break_time }}</td>
                <td>{{ $record->work_time }}</td>
                <td>
                    <a href="/admin/attendance/{{ $record->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection