<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Session\UserResource;
use Modules\User\Entities\User;
use DB;
use Illuminate\Http\Response;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\User\Http\Requests\LoginRequest;
use Modules\User\Http\Requests\RegisterRequest;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class SessionController extends Controller
{
    private $user;
    private $createToken;

    public function __construct(User $user)
    {
        $this->user        = $user;
        $this->createToken = 'Mobile App';
    }

    /**
     * @param Request $request
     * @return Response|ResponseFactory
     * @throws BindingResolutionException
     * @throws BadRequestException
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        try {
            DB::beginTransaction();

            $user = $this->user->create($data);

            $user->sendWelcomeNotification();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response([
                'error' => 'Sorry! Registration is not successfull.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return response([
            'token' => $user->createToken($this->createToken)->plainTextToken,
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @return Response|ResponseFactory
     * @throws ValidationException
     * @throws BindingResolutionException
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->where('name', $this->createToken)->delete();

        return response([
            'token' => $user->createToken($this->createToken)->plainTextToken,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return UserResource
     */
    public function perfil(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * @param Request $request
     * @return Response|ResponseFactory
     * @throws BindingResolutionException
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
        } catch (\Throwable $th) {
            return response([
                'error' => 'Sorry! Logout not successfull.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return response([
            'logout' => true,
        ], JsonResponse::HTTP_OK);
    }
}
