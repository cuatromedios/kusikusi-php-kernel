<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Config;
use Cuatromedios\Kusikusi\Models\Permission;
use Cuatromedios\Kusikusi\Models\Activity;
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
  public function authenticate(Request $request)
  {
    try {
      $this->validate($request, [
          "username" => "required_without:email|string",
          "email" => "required_without:username|string",
          "password" => "required|string"
      ], Config::get('validator.messages'));
      if ($request->input('username') !== null) {
        $authResult = User::authenticate($request->input('username'), $request->input('password'), $request->ip(), false);
      } else {
        $authResult = User::authenticate($request->input('email'), $request->input('password'), $request->ip(), true);
      }

      if ($authResult !== FALSE) {
        Activity::add($authResult['user']['id'], '', 'login', TRUE, '', "{}");
        return (new ApiResponse($authResult, TRUE))->response();
      } else {
        $status = 401;
        Activity::add('', '', 'login', FALSE, '', json_encode(["error" => $status, "username" => $request->input('username')]));
        return (new ApiResponse(NULL, FALSE, 'Incorrect credentials', $status))->response();
      }

    } catch (ValidationException $e) {
      $status = 400;
      Activity::add('', '', 'login', FALSE, '', json_encode(["error" => $status]));
      return (new ApiResponse(NULL, FALSE, ExceptionDetails::filter($e), $status))->response();
    } catch (\Exception $e) {
      $status = 500;
      Activity::add('', '', 'login', FALSE, '', json_encode(["error" => $status, "username" => $request->input('username')]));
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
  public function getPermissions($id)
  {
    try {
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id]) === true) {
        $permissionResult = Permission::getPermissions($id);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getPermissions', "{}");
        return (new ApiResponse($permissionResult, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getPermissions', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getPermissions', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Creates a new permission entry.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function postPermissions(Request $request)
  {
    try {
      $body = $request->json()->all();
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$request->user_id]) === true) {
        $permissionResult = Permission::postPermissions($body);
        Activity::add(\Auth::user()['id'], $permissionResult['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'postPermissions', json_encode(["body" => $body]));
        return (new ApiResponse($permissionResult, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postPermissions', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postPermissions', json_encode(["body" => $body, "error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Updates the permissions in the given entity.
   *
   * @param $id , Request $request
   * @return \Illuminate\Http\Response
   */
  public function patchPermissions($id, Request $request)
  {
    try {
      $body = $request->json()->all();
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
        $permissionResult = Permission::patchPermissions($id, $body);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'patchPermissions', json_encode(["body" => $body]));
        return (new ApiResponse($permissionResult, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patchPermissions', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patchPermissions', json_encode(["body" => $body, "error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }
}
