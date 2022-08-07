<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Modules\User\Entities\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DB;


class ForgotPasswordController extends Controller
{
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $updatePassword = DB::table('password_resets')->where([
                'email' => $request->email
            ])->first();

            if ($updatePassword) {
                $created_at = Carbon::parse($updatePassword->created_at);
                if ($created_at->diffInMinutes(now()) > config('auth.passwords.users.retry')) {
                    throw ValidationException::withMessages([
                        'Please wait before retrying.',
                    ]);
                }
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $token = Str::random(64);
            $tokenHash = Hash::make($token);

            DB::table('password_resets')->updateOrInsert([
                'email' => $request->email,
            ], [
                'token' => $tokenHash,
                'created_at' => Carbon::now()
            ]);

            $user->sendPasswordResetNotification($token);

        } catch (\Throwable $th) {
            throw $th;
        }

        return response([
            'message' => 'Email successfully sent!',
        ], JsonResponse::HTTP_OK);
    }
}
