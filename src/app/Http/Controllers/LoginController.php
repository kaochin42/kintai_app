<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * ログイン画面を表示する
     */
    public function index()
    {
        return view('auth.login');
    }

    /**
     * ログイン処理を行う（管理者は対象外）
     *
     * @param LoginRequest $request バリデーション済みのログイン情報
     */
    public function store(LoginRequest $request)
    {
        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
            'admin_status' => false,
        ])) {
            return back()->withErrors([
                'password' => 'ログイン情報が登録されていません。',
            ]);
        }

        // セッションの固定化攻撃を防ぐためセッションを再生成
        $request->session()->regenerate();

        // メール未認証の場合は認証ページに飛ぶ
        if (!Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect('/attendance');
    }

    /**
     * ログアウト処理を行う
     *
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
