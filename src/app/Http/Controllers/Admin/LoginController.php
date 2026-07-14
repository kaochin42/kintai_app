<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * 管理者ログイン画面を表示する
     */
    public function index()
    {
        return view('admin.auth.login');
    }

    /**
     * 管理者ログイン処理を行う
     *
     * @param AdminLoginRequest $request バリデーション済みのログイン情報
     */
    public function store(AdminLoginRequest $request)
    {
        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
            'admin_status' => true,
        ])) {
            return back()->withErrors([
                'password' => 'ログイン情報が登録されていません。',
            ]);
        }

        $request->session()->regenerate();

        return redirect('/admin/attendance/list');
    }

    /**
     * 管理者ログアウト処理を行う
     *
     * @param Request $request
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
