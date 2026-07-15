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
            <div class="date-value">
                <span>{{ $attendanceRecord->date->format('Y年') }}</span>
                <span>{{ $attendanceRecord->date->format('n月j日') }}</span>
            </div>
        </div>
        <div class="detail-row">
            <span class="detail-label">出勤・退勤</span>
            <div class="time-value">
                <span>{{ $stampCorrectionRequest->new_clock_in?->format('H:i') }}</span>
                <span>～</span>
                <span>{{ $stampCorrectionRequest->new_clock_out?->format('H:i') }}</span>
            </div>
        </div>
        @foreach($stampCorrectionRequest->correctionBreaks as $break)
        <div class="detail-row">
            <span class="detail-label">
                @if($loop->count === 1)
                休憩
                @else
                休憩{{ $loop->iteration }}
                @endif
            </span>
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
            <div class="date-value">
                <span>{{ $attendanceRecord->date->format('Y年') }}</span>
                <span>{{ $attendanceRecord->date->format('n月j日') }}</span>
            </div>
        </div>
        <div class="detail-row">
            <span class="detail-label">出勤・退勤</span>
            <div class="field-group">
                <div class="time-value">
                    <input type="time" name="clock_in" value="{{ old('clock_in', $attendanceRecord->clock_in?->format('H:i')) }}">
                    <span>～</span>
                    <input type="time" name="clock_out" value="{{ old('clock_out', $attendanceRecord->clock_out?->format('H:i')) }}">
                </div>
                <div class="form__error">
                    @error('clock_out')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        @foreach($attendanceRecord->attendanceBreaks as $break)
        <div class="detail-row">
            <span class="detail-label">
                @if($loop->count === 1)
                休憩
                @else
                休憩{{ $loop->iteration }}
                @endif
            </span>
            <div class="field-group">
                <div class="time-value">
                    <input type="time" name="breaks[{{ $loop->index }}][break_in]" value="{{ old('breaks.' . $loop->index . '.break_in', $break->break_in?->format('H:i')) }}"> <span>～</span>
                    <input type="time" name="breaks[{{ $loop->index }}][break_out]" value="{{ old('breaks.' . $loop->index . '.break_out', $break->break_out?->format('H:i')) }}">
                </div>
                <div class="form__error">
                    @error('breaks.' . $loop->index . '.break_in')
                    <span>{{ $message }}</span>
                    @enderror
                    @error('breaks.' . $loop->index . '.break_out')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        @endforeach
        <div class="detail-row">
            <span class="detail-label">休憩{{ $attendanceRecord->attendanceBreaks->count() + 1 }}</span>
            <div class="field-group">
                <div class="time-value">
                    <input type="text"
                        name="breaks[{{ $attendanceRecord->attendanceBreaks->count() }}][break_in]"
                        class="time-input"
                        value="{{ old('breaks.' . $attendanceRecord->attendanceBreaks->count() . '.break_in', null) }}"
                        placeholder=""
                        onfocus="this.type='time'"
                        onblur="if(this.value=='') this.type='text'">
                    <span>～</span>
                    <input type="text"
                        name="breaks[{{ $attendanceRecord->attendanceBreaks->count() }}][break_out]"
                        class="time-input"
                        value="{{ old('breaks.' . $attendanceRecord->attendanceBreaks->count() . '.break_out', null) }}"
                        placeholder=""
                        onfocus="this.type='time'"
                        onblur="if(this.value=='') this.type='text'">
                </div>
                <div class="form__error">
                    @error('breaks.' . $attendanceRecord->attendanceBreaks->count() . '.break_in')
                    <span>{{ $message }}</span>
                    @enderror
                    @error('breaks.' . $attendanceRecord->attendanceBreaks->count() . '.break_out')
                    <span>{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="detail-row">
            <span class="detail-label">備考</span>
            <div class="field-group">
                <textarea name="comment">{{ old('comment', $attendanceRecord->comment) }}</textarea>
                <div class="form__error">
                    @error('comment')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
    </form>
    
    <button class="btn btn--big detail-submit" form="attendance-form">修正</button>
    @endif
</div>
@endsection