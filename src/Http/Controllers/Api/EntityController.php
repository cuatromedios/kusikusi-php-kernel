<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use App\Models\Entity;
use Cuatromedios\Kusikusi\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Cuatromedios\Kusikusi\Models\Activity;
use Exception;

class EntityController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Return the specified entity.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getOne($id, Request $request)
  {
    try {
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $query = Entity::select();
      $query = deserialize_select($query, $request);
      //TODO: Select attached data fields
      $entity = $query->find($id)->compact();
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getOne', "{}"])) {
        return (new ApiResponse($entity, TRUE))->response();
      } else {
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
    }
  }

  /**
   * Create the specified entity.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function post(Request $request)
  {
    try {
      // TODO: Filter the json to delete al not used data
      $body = $request->json()->all();
      if (!isset($body['parent'])) {
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_BADREQUEST . ': No parent parameter', ApiResponse::STATUS_BADREQUEST))->response();
      }
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$request->parent]) === true) {
        $entityPosted = EntityBase::post($request->json()->all());
        Activity::add(\Auth::user()['id'], $entityPosted['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'post', json_encode(["body" => $body]));
        return (new ApiResponse($entityPosted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'post', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'post', json_encode(["body" => $body, "error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Update the specified entity.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function patch($id, Request $request)
  {
    try {
      // TODO: Filter the json to delete all not used data
      $body = $request->json()->all();
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
        $entityPatched = EntityBase::patch($id, $body);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'patch', json_encode(["body" => $body]));
        return (new ApiResponse($entityPatched, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patch', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patch', json_encode(["body" => $body, "error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   *
   * Makes a soft delete on the specified entity.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */

  public function softDelete($id)
  {
    try {
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
        $entitySoftDeleted = EntityBase::softDelete($id);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'softDelete', '{}');
        return (new ApiResponse($entitySoftDeleted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'softDelete', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'softDelete', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Makes a hard deletes on the specified entity.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */

  public function hardDelete($id)
  {
    try {
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
        $entityHardDeleted = EntityBase::hardDelete($id);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'hardDelete', '{}');
        return (new ApiResponse($entityHardDeleted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'hardDelete', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'hardDelete', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }


  /**
   * Display entity's parent.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getParent($id, Request $request)
  {
    try {
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $fields = $request->input('fields', []);
      $entity = EntityBase::getParent($id, $fields, $lang);
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id])) {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getParent', "{}");
        return (new ApiResponse($entity, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Display all entities.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function get(Request $request)
  {
    try {
      $fields = $request->input('fields', []);
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $order = $request->input('order', NULL);
      if (Gate::allows(AuthServiceProvider::READ_ALL)) {
        $collection = EntityBase::get(NULL, $fields, $lang, $order);
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, TRUE, 'get', "{}");
        return (new ApiResponse($collection, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, FALSE, 'get', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, FALSE, 'get', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Display entity's children.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getChildren($id, Request $request)
  {
    try {
      $fields = $request->input('fields', []);
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $order = $request->input('order', NULL);
      $filter = $request->input('filter', NULL);
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'children'])) {
        $collection = EntityBase::getChildren($id, $fields, $lang, $order, $filter);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getChildren', "{}");
        return (new ApiResponse($collection, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getChildren', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getChildren', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Display entity's ancestors.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getAncestors($id, Request $request)
  {
    try {
      $fields = $request->input('fields', []);
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $order = $request->input('order', NULL);
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'ancestors'])) {
        $collection = EntityBase::getAncestors($id, $fields, $lang, $order);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getAncestors', "{}");
        return (new ApiResponse($collection, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getAncestors', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getAncestors', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Display entity's ancestors.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getDescendants($id, Request $request)
  {
    try {
      $fields = $request->input('fields', []);
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $order = $request->input('order', NULL);
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'descendants'])) {
        $collection = EntityBase::getDescendants($id, $fields, $lang, $order);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getDescendants', "{}");
        return (new ApiResponse($collection, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getDescendants', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getDescendants', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Display entity's relations.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getRelations($id, $kind = NULL, Request $request)
  {
    try {
      $fields = $request->input('fields', []);
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $order = $request->input('order', NULL);
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'relations'])) {
        $collection = EntityBase::getEntityRelations($id, $kind, $fields, $lang, $order);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getRelations', "{}");
        return (new ApiResponse($collection, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getRelations', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getRelations', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Display entity's relations.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getInverseRelations($id, $kind = NULL, Request $request)
  {
    try {
      $fields = $request->input('fields', []);
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $order = $request->input('order', NULL);
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'inverse-relations'])) {
        $collection = EntityBase::getInverseEntityRelations($id, $kind, $fields, $lang, $order);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getInverseRelations', "{}");
        return (new ApiResponse($collection, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getInverseRelations', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getInverseRelations', json_encode(["error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Create the specified relation.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function postRelation($id, Request $request)
  {
    try {
      // TODO: Filter the json to delete al not used data
      $body = $request->json()->all();
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id, 'relation']) === true) {
        $entityPosted = EntityBase::postRelation($id, $body);
        Activity::add(\Auth::user()['id'], $entityPosted['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'postRelation', json_encode(["body" => $body]));
        return (new ApiResponse($entityPosted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postRelation', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postRelation', json_encode(["body" => $body, "error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }

  /**
   * Create the specified entity.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function deleteRelation($id, $called, $kind)
  {
    try {
      // TODO: Filter the json to delete al not used data
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id, 'relation']) === true) {
        $entityPostedId = EntityBase::deleteRelation($id, $called, $kind);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'deleteRelation', json_encode(["body" => ["called" => $called, "kind" => $kind]]));
        return (new ApiResponse($entityPostedId, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'deleteRelation', json_encode(["body" => ["called" => $called, "kind" => $kind], "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Exception $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'deleteRelation', json_encode(["body" => ["called" => $called, "kind" => $kind], "error" => $exceptionDetails['info']]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
    }
  }
}
