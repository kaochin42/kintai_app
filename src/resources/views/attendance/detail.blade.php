@extends('layouts.default')

@section('title','勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance-detail center">
    <h1 class="page__title-bar">勤怠詳細</h1>

    @if($hasPendingRequest)
    {{-- 承認待ちの場合：表示のみ --}}
    <div class="detail-table">
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
            <div class="time-value">
                <span>{{ $attendanceRecord->clock_in?->format('H:i') }}</span>
                <span>～</span>
                <span>{{ $attendanceRecord->clock_out?->format('H:i') }}</span>
            </div>
        </div>
        @foreach($pendingRequest->correctionBreaks as $break)
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
            <span>{{ $attendanceRecord->comment }}</span>
        </div>
    </div>
    <p class="pending-message">*承認待ちのため修正はできません。</p>

    @else
    {{-- 通常の修正フォーム --}}
    <form action="{{ Auth::user()->admin_status ? '/admin/attendance/'.$attendanceRecord->id : '/attendance/detail/'.$attendanceRecord->id }} "
        method="post"
        class="detail-table"
        id="attendance-form">
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
            <div class="time-value">
                <input type="time" name="clock_in" value="{{ $attendanceRecord->clock_in?->format('H:i') }}">
                <span>～</span>
                <input type="time" name="clock_out" value="{{ $attendanceRecord->clock_out?->format('H:i') }}">
            </div>
            <div class="form__error">
                @error('clock_out')
                {{ $message }}
                @enderror
            </div>
        </div>
        @foreach($attendanceRecord->attendanceBreaks as $break)
        <div class="detail-row">
            <span class="detail-label">休憩{{ $loop->iteration }}</span>
            <div class="time-value">
                <input type="time" name="breaks[{{ $loop->index }}][break_in]" value="{{ $break->break_in?->format('H:i') }}"> <span>～</span>
                <input type="time" name="breaks[{{ $loop->index }}][break_out]" value="{{ $break->break_out?->format('H:i') }}">
            </div>
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
            <div class="time-value">
                <input type="text"
                    name="breaks[{{ $attendanceRecord->attendanceBreaks->count() }}][break_in]"
                    class="time-input"
                    value=""
                    placeholder=""
                    onfocus="this.type='time'"
                    onblur="if(this.value=='') this.type='text'">
                <span>～</span>
                <input type="text"
                    name="breaks[{{ $attendanceRecord->attendanceBreaks->count() }}][break_out]"
                    class="time-input"
                    value=""
                    placeholder=""
                    onfocus="this.type='time'"
                    onblur="if(this.value=='') this.type='text'">
            </div>
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
        </div>
        <div class="form__error">
            @error('comment')
            {{ $message }}
            @enderror
        </div>
    </form>
    <button class="btn btn--big detail-submit" form="attendance-form">修正</button>
    @endif
</div>
@endsection