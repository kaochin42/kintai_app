<header class="header">
    <div class="header__logo">
        <a href="/"><img src="{{ asset('img/logo.png') }}" alt="ロゴ"></a>
    </div>
    @auth
    @if(!in_array(Route::currentRouteName(), ['register', 'login', 'admin.login', 'verification.notice']))
    <nav class="header__nav">
        <ul>
            @if(Auth::user()->admin_status)
            {{-- 管理者メニュー --}}
            <li><a href="/admin/attendance/list">勤怠一覧</a></li>
            <li><a href="/admin/staff/list">スタッフ一覧</a></li>
            <li><a href="/stamp_correction_request/list">申請一覧</a></li>
            @else
            {{-- 一般ユーザーメニュー --}}
            <li><a href="/attendance">勤怠</a></li>
            <li><a href="/attendance/list">勤怠一覧</a></li>
            <li><a href="/stamp_correction_request/list">申請</a></li>
            @endif
            <li>
                <form action="{{ Auth::user()->admin_status ? '/admin/logout' : '/logout' }}" method="post">
                    @csrf
                    <button class="header__logout">ログアウト</button>
                </form>
            </li>
        </ul>
    </nav>
    @endif
    @endauth
</header>