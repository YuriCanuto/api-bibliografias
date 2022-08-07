<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Entities\User;
use Illuminate\Validation\ValidationException;
use Modules\User\Http\Requests\ResetRequest;
use Carbon\Carbon;
use DB;

class ResetPasswordController extends Controller
{
    public function formReset(Request $request)
    {
        // return view to form
    }

    public function reset(ResetRequest $request)
    {
        try {

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'The provided credentials are incorrect.',
                ]);
            }

            $updatePassword = DB::table('password_resets')->where([
                                    'email' => $request->email
                                ])->first();

            if (!$updatePassword){
                throw ValidationException::withMessages([
                    'The provided credentials are incorrect.',
                ]);
            }

            if (!Hash::check($request->token, $updatePassword->token)) {
                throw ValidationException::withMessages([
                    'Invalid token!',
                ]);
            }

            $created_at = Carbon::parse($updatePassword->created_at);
            if ($created_at->diffInMinutes(now()) > config('auth.passwords.users.expire')) {
                throw ValidationException::withMessages([
                    'Token has expired',
                ]);
            }

            $user->where('email', $request->email)->update([
                'password' => Hash::make($request->password)
            ]);

            DB::table('password_resets')->where(['email'=> $request->email])->delete();

        } catch (\Throwable $th) {
            throw $th;
        }

        $user->sendPasswordResetConfirmationNotification();

        return response([
            'status' => 'Your password has been reset!',
        ], JsonResponse::HTTP_OK);
    }
}
