<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Cuatromedios\Kusikusi\Models\Permission;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Display a listing of all entities.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request) {
        try {
            $authResult = User::authenticate($request->input('email'), $request->input('password'), $request->ip());
            if ($authResult !== FALSE) {
                return (new ApiResponse($authResult, TRUE))->response();
            } else {
                $status = 401;
                return (new ApiResponse(NULL, FALSE, 'Email or password incorrect', $status))->response();
            }

        } catch (\Exception $e) {
            $status = 500;
            return (new ApiResponse(NULL, FALSE, ExceptionDetails::filter($e), $status))->response();
        }
        // TODO: Validate input
        /* $this->validate($request, [
            'email' => 'required',
            'password' => 'required'
        ]); */
    }

    /**
     * Display the permissions for the given entity.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function getPermissions($id) {
        try {
            if (Gate::allows('get-entity', [$id]) === true) {
                $permissionResult = Permission::getPermissions($id);
                return (new ApiResponse($permissionResult, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }

    /**
     * Creates a new permission entry.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function postPermissions(Request $request) {
        try {
            if (Gate::allows('post-entity', [$request->user_id]) === true) {
                $permissionResult = Permission::postPermissions($request->json()->all());
                return (new ApiResponse($permissionResult, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }

    /**
     * Updates the permissions in the given entity.
     *
     * @param $id, Request $request
     * @return \Illuminate\Http\Response
     */
    public function patchPermissions($id, Request $request) {
        try {
            if (Gate::allows('patch-entity', [$id]) === true) {
                $permissionResult = Permission::patchPermissions($id, $request->json()->all());
                return (new ApiResponse($permissionResult, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }
}
