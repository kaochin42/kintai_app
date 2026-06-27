@extends('layouts.default')

@section('title','申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="request-list center">
    <h1 class="page__title-bar">申請一覧</h1>

    <div class="tab">
        <a href="?tab=pending"
            class="tab__item {{ request('tab') !== 'approved' ? 'active' : '' }}">承認待ち</a>
        <a href="?tab=approved"
            class="tab__item {{ request('tab') === 'approved' ? 'active' : '' }}">承認済み</a>
    </div>

    <table class="request-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @if(request('tab') === 'approved')
            @foreach($approvedRequests as $request)
            <tr>
                <td>承認済み</td>
                <td>
                    @if(Auth::user()->admin_status)
                    {{ $request->user->name }}
                    @else
                    {{ Auth::user()->name }}
                    @endif
                </td>
                <td>{{ $request->attendanceRecord->date->format('Y/m/d') }}</td>
                <td>{{ $request->new_comment }}</td>
                <td>{{ $request->created_at->format('Y/m/d') }}</td>
                <td><a href="/stamp_correction_request/approve/{{ $request->id }}">詳細</a></td>
            </tr>
            @endforeach
            @else
            @foreach($pendingRequests as $request)
            <tr>
                <td>承認待ち</td>
                <td>
                    @if(Auth::user()->admin_status)
                    {{ $request->user->name }}
                    @else
                    {{ Auth::user()->name }}
                    @endif
                </td>
                <td>{{ $request->attendanceRecord->date->format('Y/m/d') }}</td>
                <td>{{ $request->new_comment }}</td>
                <td>{{ $request->created_at->format('Y/m/d') }}</td>
                <td><a href="/stamp_correction_request/approve/{{ $request->id }}">詳細</a></td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>
</div>
@endsection