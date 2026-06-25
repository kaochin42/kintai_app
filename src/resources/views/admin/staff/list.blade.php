@extends('layouts.default')

@section('title','スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">
@endsection

@section('content')
@include('components.header')

<div class="staff-list center">
    <h1 class="page__title-bar">スタッフ一覧</h1>

    <table class="staff-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <a href="/admin/attendance/staff/{{ $user->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection