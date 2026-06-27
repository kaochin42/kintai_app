@extends('layouts.default')

@section('title','勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="attendance center">
    <p class="status-badge">{{ $status }}</p>
    <p class="date">{{ $today->locale('ja')->isoFormat('YYYY年M月D日(ddd)') }}</p>
    <p class="time" id="current-time"></p>

    @push('scripts')
    <script>
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('current-time').textContent = `${hours}:${minutes}`;
        }

        updateTime();
        setInterval(updateTime, 1000);
    </script>
    @endpush

    @if($status === '勤務外')
    <form action="/attendance" method="post">
        @csrf
        <button name="action" value="clock_in" class="btn btn--big">出勤</button>
    </form>
    @elseif($status === '出勤中')
    <form action="/attendance" method="post">
        @csrf
        <button name="action" value="clock_out" class="btn btn--big">退勤</button>
        <button name="action" value="break_in" class="btn--outline">休憩入</button>
    </form>
    @elseif($status === '休憩中')
    <form action="/attendance" method="post">
        @csrf
        <button name="action" value="break_out" class="btn btn--big">休憩戻</button>
    </form>
    @elseif($status === '退勤済')
    <p class="thanks">お疲れ様でした。</p>
    @endif
</div>
@endsection