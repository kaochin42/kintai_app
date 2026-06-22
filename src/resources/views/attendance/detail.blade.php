@extends('layouts.default')

@section('title','勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance-detail center">
    <h1 class="page__title-bar">勤怠詳細</h1>

    <form action="/attendance/detail/{{ $attendanceRecord->id }}" method="post" class="detail-table">
        @csrf
        @method('PUT')

        <div class="detail-row">
            <span class="detail-label">名前</span>
            <span class="detail-value">{{ $attendanceRecord->user->name }}</span>
        </div>

        <div class="detail-row">
            <span class="detail-label">日付</span>
            <span class="detail-value">
                {{ $attendanceRecord->date->format('Y年') }}　{{ $attendanceRecord->date->format('n月j日') }}
            </span>
        </div>

        <div class="detail-row">
            <span class="detail-label">出勤・退勤</span>
            <input type="time" name="clock_in" value="{{ $attendanceRecord->clock_in?->format('H:i') }}">
            ～
            <input type="time" name="clock_out" value="{{ $attendanceRecord->clock_out?->format('H:i') }}">
            <div class="form__error">
                @error('clock_out')
                {{ $message }}
                @enderror
            </div>
        </div>

        @foreach($attendanceRecord->attendanceBreaks as $break)
        <div class="detail-row">
            <span class="detail-label">休憩{{ $loop->iteration }}</span>
            <input type="time" name="breaks[{{ $loop->index }}][break_in]" value="{{ $break->break_in?->format('H:i') }}">
            ～
            <input type="time" name="breaks[{{ $loop->index }}][break_out]" value="{{ $break->break_out?->format('H:i') }}">
            <div class="form__error">
                @error('breaks.' . $loop->index . '.break_in')
                {{ $message }}
                @enderror
                @error('breaks.' . $loop->index . '.break_out')
                {{ $message }}
                @enderror
            </div>
        </div>
        @endforeach

        <div class="detail-row">
            <span class="detail-label">休憩{{ $attendanceRecord->attendanceBreaks->count() + 1 }}</span>
            <input type="time" name="breaks[{{ $attendanceRecord->attendanceBreaks->count() }}][break_in]"> ～
            <input type="time" name="breaks[{{ $attendanceRecord->attendanceBreaks->count() }}][break_out]">
            <div class="form__error">
                @error('breaks.' . $attendanceRecord->attendanceBreaks->count() . '.break_in')
                {{ $message }}
                @enderror
                @error('breaks.' . $attendanceRecord->attendanceBreaks->count() . '.break_out')
                {{ $message }}
                @enderror
            </div>
        </div>

        <div class="detail-row">
            <span class="detail-label">備考</span>
            <input type="text" name="comment" value="{{ $attendanceRecord->comment }}">
            <div class="form__error">
                @error('comment')
                {{ $message }}
                @enderror
            </div>
        </div>

        <button class="btn btn--big">修正</button>
    </form>
</div>
@endsection