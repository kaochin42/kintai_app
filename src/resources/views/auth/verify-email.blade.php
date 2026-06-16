@extends('layouts.default')

@section('title','メール認証')

@section('content')
@include('components.header')
<div class="authenticate center">
    <h1 class="page__title">メール認証</h1>
    <p class="verify__text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <a href="http://localhost:8025" target="_blank" class="btn btn--big">認証はこちらから</a>

    <form action="{{ route('verification.send') }}" method="post" class="resend-form">
        @csrf
        <button type="submit" class="link">認証メールを再送する</button>
    </form>
</div>
@endsection