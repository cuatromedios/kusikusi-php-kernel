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
use Illuminate\Support\Facades\Validator;
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
   * Display all entities.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function get(Request $request)
  {
    try {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $permissions = Auth::user()->permissions;
        // TODO: Get every entity descendant of the user's 'home'
        $query = Entity::select();
        $query = deserialize_select($query, $request);
        $entity = $query->get()->compact();
        if (Gate::allows(AuthServiceProvider::READ_ALL)) {
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
      $entity = $query->find($id);
      if ($entity != null) {
        $entity = $query->find($id)->compact();
      }
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
        $this->validate($request, [
            "parent_id" => "required|string",
            "model" => "required|string"
            ], Config::get('validator.messages'));
        $body = $request->json()->all();
        if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$request->parent_id]) === true) {
            $entityPosted = new Entity($body);
            $entityPosted->save();
            Activity::add(\Auth::user()['id'], $entityPosted['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'post', json_encode(["body" => $body]));
            return (new ApiResponse($entityPosted, TRUE))->response();
        } else {
            Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'post', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
            return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
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
        $entityPatched = Entity::find($id)->update($body);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'patch', json_encode(["body" => $body]));
        return (new ApiResponse($entityPatched, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patch', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
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
        $entitySoftDeleted = Entity::find($id)->deleteEntity();
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'softDelete', '{}');
        return (new ApiResponse($id, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'softDelete', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
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
        $entitySoftDeleted = Entity::withTrashed()->find($id)->deleteEntity(true);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'hardDelete', '{}');
        return (new ApiResponse($id, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'hardDelete', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
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
        $query = Entity::select();
        $query = deserialize_select($query, $request);
        //TODO: Select attached data fields
        $entity = $query->parentOf($id)->get()->compact();
        if(count($entity) > 0) {
          if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$entity[0]['id'], 'getParent', "{}"])) {
            return (new ApiResponse($entity, TRUE))->response();
          } else {
            return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
          }
        } else {
          return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
        }
      } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
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
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select();
        $query = deserialize_select($query, $request);
        //TODO: Select attached data fields
        $entity = $query->childOf($id)->get()->compact();
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getChildren', "{}"])) {
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
   * Display entity's ancestors.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getAncestors($id, Request $request)
  {
    try {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select();
        $query = deserialize_select($query, $request);
        //TODO: Select attached data fields
        $entity = $query->ancestorOf($id)->get()->compact();
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getAncestors', "{}"])) {
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
   * Display entity's ancestors.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getDescendants($id, Request $request)
  {
    try {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select();
        $query = deserialize_select($query, $request);
        //TODO: Select attached data fields
        $entity = $query->descendantOf($id)->get()->compact();
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getDescendants', "{}"])) {
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
   * Display entity's relations.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getRelations($id, $kind = NULL, Request $request)
  {
    try {
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $query = Entity::select();
      $query = deserialize_select($query, $request);
      //TODO: Select attached data fields
      $entity = $query->withRelations(function($relation) use ($kind) {
        if ($kind == NULL) {
          $relation->where('kind', '<>', 'ancestor');
        } else {
          $relation->whereKind($kind);
        }
      })->find($id);
      if ($entity != NULL) {
        $entity->find($id)->compact();
      }
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getRelations', "{}"])) {
        return (new ApiResponse($entity['relations'], TRUE))->response();
      } else {
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
    } 
  }

  /**
   * Display entity's inverse relations.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getInverseRelations($id, $kind = NULL, Request $request)
  {
    try {
      $lang = $request->input('lang', Config::get('general.langs')[0]);
      $query = Entity::select();
      $query = deserialize_select($query, $request);
      //TODO: Select attached data fields
      $entity = $query->withInverseRelations(function($relation) use ($kind) {
        if ($kind == NULL) {
          $relation->where('kind', '<>', 'ancestor');
        } else {
          $relation->whereKind($kind);
        }
      })->find($id);
      if ($entity != NULL) {
        $entity->find($id)->compact();
      }
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getRelations', "{}"])) {
        return (new ApiResponse($entity['inverse_relations'], TRUE))->response();
      } else {
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
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
      $this->validate($request, [
          "id" => "required|string",
          "kind" => "required|string"
      ], Config::get('validator.messages'));
      $body = $request->json()->all();
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id, 'relation']) === true) {
        $relationPosted = Entity::find($id)->addRelation($body);
        Activity::add(\Auth::user()['id'], $relationPosted['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'postRelation', json_encode(["body" => $body]));
        return (new ApiResponse($relationPosted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postRelation', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
    }
  }

  /**
   * Deletes the specified relation.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function deleteRelation($id, $kind, $called)
  {
    try {
      // TODO: Filter the json to delete al not used data
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id, 'relation']) === true) {
        $relationDeleted = Entity::find($id)->deleteRelation($kind, $called);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'deleteRelation', json_encode(["body" => ["called" => $called, "kind" => $kind]]));
        return (new ApiResponse($relationDeleted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'deleteRelation', json_encode(["body" => ["called" => $called, "kind" => $kind], "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
    }
  }
}
