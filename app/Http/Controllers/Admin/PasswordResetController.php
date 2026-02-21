<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return Inertia::render('Auth/Admin/ForgotPassword');
    }

    public function sendResetLink(ForgotPasswordRequest $request)
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $exists = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
        if (!$exists) {
            Inertia::flash('toast', [
                'message' => 'Email tidak terdaftar',
                'type' => 'error',
            ]);
            return back()->withErrors(['email' => 'Email tidak terdaftar']);
        }
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            Inertia::flash('toast', [
                'message' => trans($status),
                'type' => 'success',
            ]);
            return back();
        }

        Inertia::flash('toast', [
            'message' => trans($status),
            'type' => 'error',
        ]);
        return back()->withErrors(['email' => trans($status)]);
    }

    public function showReset(Request $request, string $token)
    {
        return Inertia::render('Auth/Admin/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->password = bcrypt($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
        if ($status === Password::PASSWORD_RESET) {
            Inertia::flash('toast', [
                'message' => trans($status),
                'type' => 'success',
            ]);
            return redirect()->route('admin.login');
        }
        Inertia::flash('toast', [
            'message' => trans($status),
            'type' => 'error',
        ]);
        return back()->withErrors(['email' => trans($status)]);
    }
}
