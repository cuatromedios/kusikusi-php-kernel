<?php
namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use App\Models\User;
use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\Activity;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Cuatromedios\Kusikusi\Models\Permission;
use Cuatromedios\Kusikusi\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

/**
 * Class UserController
 *
 * @package Cuatromedios\Kusikusi\Http\Controllers\Api
 */
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
     *
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        try {
            $this->validate($request, [
                "username" => "required_without:email|string",
                "email"    => "required_without:username|string",
                "password" => "required|string",
            ], Config::get('validator.messages'));
            if ($request->input('username') !== null) {
                $authResult = User::authenticate($request->input('username'), $request->input('password'),
                    $request->ip(), false);
            } else {
                $authResult = User::authenticate($request->input('email'), $request->input('password'), $request->ip(),
                    true);
            }
            if ($authResult !== false) {
                Activity::add($authResult['user']['id'], '', 'login', true, '', "{}");

                return (new ApiResponse($authResult, true))->response();
            } else {
                $status = 401;
                Activity::add('', '', 'login', false, '',
                    json_encode(["error" => $status, "username" => $request->input('username')]));

                return (new ApiResponse(null, false, 'Incorrect credentials', $status))->response();
            }
        } catch (ValidationException $e) {
            $status = 400;
            Activity::add('', '', 'login', false, '', json_encode(["error" => $status]));

            return (new ApiResponse(null, false, ExceptionDetails::filter($e), $status))->response();
        } catch (\Exception $e) {
            $status = 500;
            Activity::add('', '', 'login', false, '',
                json_encode(["error" => $status, "username" => $request->input('username')]));

            return (new ApiResponse(null, false, ExceptionDetails::filter($e), $status))->response();
        }
    }

    /**
     * Display the permissions for the given entity.
     *
     * @param $id
     *
     * @return \Illuminate\Http\Response
     */
    public function getPermissions($id)
    {
        try {
            if (\Auth::user()['profile'] == 'admin') {
                $permissionResult = Permission::getPermissions($id);
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, true, 'getPermissions', "{}");

                return (new ApiResponse($permissionResult, true))->response();
            } else {
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, false, 'getPermissions',
                    json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));

                return (new ApiResponse(null, false, ApiResponse::TEXT_FORBIDDEN,
                    ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, false, 'getPermissions',
                json_encode(["error" => $exceptionDetails['info']]));

            return (new ApiResponse(null, false, $exceptionDetails['info'],
                $exceptionDetails['info']['code']))->response();
        }
    }

    /**
     * Creates a new permission entry.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postPermissions(Request $request)
    {
        try {
            $this->validate($request, [
                "user_id"   => "required|string",
                "entity_id" => "required|string",
                "read"      => "required|string",
                "write"     => "required|string",
            ], Config::get('validator.messages'));
            $body = $request->json()->all();
            if (\Auth::user()['profile'] == 'admin') {
                $permissionResult = Permission::addPermission($body['user_id'], $body['entity_id'], $body['read'],
                    $body['write']);

                // Activity::add(\Auth::user()['id'], $permissionResult['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'postPermissions', json_encode(["body" => $body]));
                return (new ApiResponse($permissionResult, true))->response();
            } else {
                Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, false, 'postPermissions',
                    json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));

                return (new ApiResponse(null, false, ApiResponse::TEXT_FORBIDDEN,
                    ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Throwable $e) {
            $exceptionDetails = ExceptionDetails::filter($e);

            return (new ApiResponse(null, false, $exceptionDetails, $exceptionDetails['code']))->response();
        }
    }

    /**
     * Removes the permissions from the given user.
     *
     * @param $id , Request $request
     * @param $entity_id
     *
     * @return \Illuminate\Http\Response
     */
    public function removePermissions($id, $entity, Request $request)
    {
        try {
            $body = $request->json()->all();
            if (\Auth::user()['profile'] == 'admin') {
                $permissionResult = Permission::deletePermission($id, $entity);

                // Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'patchPermissions', json_encode(["body" => $body]));
                return (new ApiResponse($permissionResult, true))->response();
            } else {
                // Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patchPermissions', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
                return (new ApiResponse(null, false, ApiResponse::TEXT_FORBIDDEN,
                    ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Throwable $e) {
            $exceptionDetails = ExceptionDetails::filter($e);

            return (new ApiResponse(null, false, $exceptionDetails, $exceptionDetails['code']))->response();
        }
    }
}
