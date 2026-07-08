@extends('layouts.default')
@section('title','申請詳細')
@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance-detail center">
    <h1 class="page__title-bar">勤怠詳細</h1>

    <div class="detail-table">
        <div class="detail-row">
            <span class="detail-label">名前</span>
            <span class="detail-value">{{ $stampCorrectionRequest->user->name }}</span>
        </div>

        <div class="detail-row">
            <span class="detail-label">日付</span>
            <span class="detail-value">
                {{ $stampCorrectionRequest->attendanceRecord->date->format('Y年') }}
                {{ $stampCorrectionRequest->attendanceRecord->date->format('n月j日') }}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">出勤・退勤</span>
            <div class="time-value">
                <span>{{ $stampCorrectionRequest->new_clock_in?->format('H:i') }}</span>
                <span>～</span>
                <span>{{ $stampCorrectionRequest->new_clock_out?->format('H:i') }}</span>
            </div>
        </div>

        {{-- 休憩（correctionBreaksをループ） --}}
        @foreach($stampCorrectionRequest->correctionBreaks as $break)
        <div class="detail-row">
            <span class="detail-label">休憩{{ $loop->iteration }}</span>
            <div class="time-value">
                <span>{{ $break->new_break_in?->format('H:i') }}</span>
                <span>～</span>
                <span>{{ $break->new_break_out?->format('H:i') }}</span>
            </div>
        </div>
        @endforeach

        <div class="detail-row">
            <span class="detail-label">備考</span>
            <span>{{ $stampCorrectionRequest->new_comment }}</span>
        </div>
    </div>

    {{-- 承認ボタン --}}
    @if($stampCorrectionRequest->is_approved)
    <button class="btn btn--big" disabled>承認済み</button>
    @else
    <form action="/stamp_correction_request/approve/{{ $stampCorrectionRequest->id }}" method="post">
        @csrf
        <button class="btn btn--big  detail-submit">承認</button>
    </form>
    @endif
</div>
@endsection