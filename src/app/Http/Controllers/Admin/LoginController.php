<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('admin.auth.login');
    }

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

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
