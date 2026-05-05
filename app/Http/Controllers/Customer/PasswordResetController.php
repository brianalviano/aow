<?php

namespace App\Http\Controllers\Customer;

use App\DTOs\Auth\{ForgotPasswordData, ResetPasswordData};
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Customer;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return Inertia::render('Domains/Customer/Auth/ForgotPassword/Index');
    }

    public function sendResetLink(ForgotPasswordData $dto)
    {
        $email = mb_strtolower(trim($dto->email));
        $exists = Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if (!$exists) {
            Inertia::flash('toast', [
                'message' => 'Email tidak terdaftar',
                'type' => 'error',
            ]);
            return back()->withErrors(['email' => 'Email tidak terdaftar']);
        }

        $status = Password::broker('customers')->sendResetLink(['email' => $dto->email]);

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
        return Inertia::render('Domains/Customer/Auth/ResetPassword/Index', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(ResetPasswordData $dto)
    {
        $status = Password::broker('customers')->reset(
            [
                'email' => $dto->email,
                'password' => $dto->password,
                'password_confirmation' => $dto->passwordConfirmation,
                'token' => $dto->token
            ],
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
            return redirect()->route('customer.login');
        }

        Inertia::flash('toast', [
            'message' => trans($status),
            'type' => 'error',
        ]);
        return back()->withErrors(['email' => trans($status)]);
    }
}
