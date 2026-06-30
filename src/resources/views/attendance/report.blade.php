@extends('layouts.default')

@section('title','マイ勤怠レポート')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="report center">
    <h1 class="page__title-bar">マイ勤怠レポート</h1>

    {{-- 基本サマリー --}}
    <div class="report-section">
        <h2 class="report-section__title">基本サマリー（過去6ヶ月）</h2>
        <div class="report-cards">
            <div class="report-card">
                <p class="report-card__label">総労働時間</p>
                <p class="report-card__value">{{ $totalWorkTime }}</p>
            </div>
            <div class="report-card">
                <p class="report-card__label">総残業時間</p>
                <p class="report-card__value">{{ $totalOvertime }}</p>
            </div>
            <div class="report-card">
                <p class="report-card__label">平均労働時間/日</p>
                <p class="report-card__value">{{ $averageWorkTime }}</p>
            </div>
        </div>
    </div>

    {{-- 月次推移 --}}
    <div class="report-section">
        <h2 class="report-section__title">月次推移</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th>月</th>
                    <th>労働時間</th>
                    <th>残業時間</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyData as $data)
                <tr>
                    <td>{{ $data['month'] }}</td>
                    <td>{{ $data['work_time'] }}</td>
                    <td>{{ $data['overtime'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 当月の異常検知 --}}
    <div class="report-section">
        <h2 class="report-section__title">今月の異常検知</h2>
        <p style="font-size:12px; color:#666; margin-bottom:16px;">基準: 始業 09:00 / 終業 18:00 / 長時間労働は1日10時間超</p>
        <div class="report-cards">
            <div class="report-card">
                <p class="report-card__label">遅刻回数</p>
                <p class="report-card__value">{{ $lateCount }}回</p>
            </div>
            <div class="report-card">
                <p class="report-card__label">早退回数</p>
                <p class="report-card__value">{{ $earlyLeaveCount }}回</p>
            </div>
            <div class="report-card">
                <p class="report-card__label">長時間労働日数</p>
                <p class="report-card__value">{{ $longWorkCount }}日</p>
            </div>
        </div>
    </div>
</div>
@endsection