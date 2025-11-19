<?php

namespace Modules\User\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\User\Services\UserService;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Http\Requests\CreateUserRequest;
use Modules\User\Http\Requests\UpdateUserRequest;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(protected UserService $userService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return $this->successResponse([
            "users" => UserResource::collection($users)
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->all());
        return $this->successResponse([
            "user" => new UserResource($user)
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        return $this->successResponse([
            "user" => new UserResource($user)
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->updateUser($id, $request->all());
        return $this->successResponse([
            "user" => new UserResource($user)
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userService->deleteUser($id);
        return $this->successResponse(null, __("message.success"));
    }
    public function register(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userService->registerUser($request->all());
        return $this->successResponse([
            "user" => new UserResource($user),
            'token' => $user->generateToken(),
        ], __("message.success"));
    }
    public function logout(): JsonResponse
    {
        auth('user')->user()->currentAccessToken()->delete();
        return $this->successResponse(null, __('message.success'));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        $user = auth('user')->user();
        $updatedUser = $this->userService->updateUser($user->id, $request->all());
        return $this->successResponse([
            "user" => new UserResource($updatedUser)
        ], __("message.success"));
    }
    public function deleteAccount(): JsonResponse
    {
        $user = auth('user')->user();
        $deleted = $this->userService->deleteUser($user->id);

        if ($deleted) {
            $user->currentAccessToken()->delete();
        }

        return $this->successResponse(null, __('message.success'));
    }
}
