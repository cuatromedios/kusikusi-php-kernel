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
        if (Gate::allows(AuthServiceProvider::READ_ALL)) {
            $lang = $request->input('lang', Config::get('general.langs')[0]);
            $permissions = Auth::user()->permissions;
            // TODO: Get every entity descendant of the user's 'home'
            $query = Entity::select();
            $query = process_querystring($query, $request);
            $entity = $query->get()->compact();
            Activity::add(Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, TRUE, 'get', '{}');
            return (new ApiResponse($entity, TRUE))->response();
        } else {
            Activity::add(Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, FALSE, 'get', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
            return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, FALSE, 'get', json_encode(["error" => $exceptionDetails]));
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
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getOne', "{}"])) {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select();
        $query = process_querystring($query, $request);
        //TODO: Select attached data fields
        $entity = $query->find($id);
        if ($entity != null) {
          $entity = $query->find($id)->compact();
        }
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getOne', '{}');
        return (new ApiResponse($entity, TRUE))->response();
      } else {
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getOne', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(Auth::user()['id'], '', AuthServiceProvider::READ_ENTITY, FALSE, 'getOne', json_encode(["error" => $exceptionDetails]));
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
        if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$request->parent_id]) === true) {
            $this->validate($request, [
              "parent_id" => "required|string",
              "model" => "required|string"
              ], Config::get('validator.messages'));
            $body = $request->json()->all();
            $entityPosted = new Entity($body);
            $entityPosted->save();
            Activity::add(\Auth::user()['id'], $entityPosted['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'post', json_encode(["body" => $body]));
            return (new ApiResponse($entityPosted, TRUE))->response();
        } else {
            Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'post', json_encode(["body" => $request->json()->all(), "error" => ApiResponse::TEXT_FORBIDDEN]));
            return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'post', json_encode(["error" => $exceptionDetails]));
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
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
        // TODO: Filter the json to delete all not used data
        $body = $request->json()->all();
        $entityPatched = Entity::find($id)->update($body);
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, TRUE, 'patch', json_encode(["body" => $body]));
        return (new ApiResponse($entityPatched, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'patch', json_encode(["body" => $request->json()->all(), "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'post', json_encode(["error" => $exceptionDetails]));
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
      Activity::add(Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'softDelete', json_encode(["error" => $exceptionDetails]));
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
      Activity::add(Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'hardDelete', json_encode(["error" => $exceptionDetails]));
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
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getParent', "{}"])) {
          $lang = $request->input('lang', Config::get('general.langs')[0]);
          $query = Entity::select();
          $query = process_querystring($query, $request);
          //TODO: Select attached data fields
          $entity = $query->parentOf($id)->get()->compact();
          if (count($entity) > 0) {
            Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getParent', '{}');
            return (new ApiResponse($entity, TRUE))->response();
          } else {
            Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => ApiResponse::TEXT_NOTFOUND]));
            return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
          }
        } else {
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
          return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
      } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => $exceptionDetails]));
        return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
      }
  }

  /**
   * Display entity's parent.
   *
   * @param $id
   * @return \Illuminate\Http\Response
   */
  public function getTree($id, Request $request)
  {
    try {
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getTree', "{}"])) {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select()
                ->descendantOf($id, 'asc', $request['depth'] ?? 999);
        $query = process_querystring($query, $request) ->addSelect('id', 'parent_id');
        $entities = $query->get()->compact();
        $tree = self::buildTree($entities, $id);
        if (count($entities) > 0) {
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getParent', '{}');
          return (new ApiResponse($tree, TRUE))->response();
        } else {
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => ApiResponse::TEXT_NOTFOUND]));
          return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
        }
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getParent', json_encode(["error" => $exceptionDetails]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
    }
  }
  private static function buildTree(array &$entities, $parent_id = 'root') {
    $branch = [];
    foreach ($entities as &$entity) {
      if ($entity['parent_id'] == $parent_id) {
        $children = self::buildTree($entities, $entity['id']);
        if ($children) {
          $entity['children'] = $children;
        }
        $branch[] = $entity;
        unset($entity);
      }
    }
    return $branch;
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
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getChildren', "{}"])) {
          $lang = $request->input('lang', Config::get('general.langs')[0]);
          $query = Entity::select();
          $query = process_querystring($query, $request);
          //TODO: Select attached data fields
          $entity = $query->childOf($id)->get()->compact();
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getChildren', '{}');
          return (new ApiResponse($entity, TRUE))->response();
        } else {
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getChildren', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
          return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
      } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getChildren', json_encode(["error" => $exceptionDetails]));
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
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getAncestors', "{}"])) {
          $lang = $request->input('lang', Config::get('general.langs')[0]);
          $query = Entity::select();
          $query = process_querystring($query, $request, ['treealias' => 'rel_tree_anc']);
          //TODO: Select attached data fields
          $entity = $query->ancestorOf($id)->get()->compact();
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getAncestors', '{}');
          return (new ApiResponse($entity, TRUE))->response();
        } else {
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getAncestors', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
          return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getAncestors', json_encode(["error" => $exceptionDetails]));
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
        if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getDescendants', "{}"])) {
          $lang = $request->input('lang', Config::get('general.langs')[0]);
          $query = Entity::select();
          $query = process_querystring($query, $request);
          //TODO: Select attached data fields
          $entity = $query->descendantOf($id)->get()->compact();
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getDescendants', '{}');
          return (new ApiResponse($entity, TRUE))->response();
        } else {
          Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getDescendants', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
          return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
        }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getDescendants', json_encode(["error" => $exceptionDetails]));
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
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getRelations', "{}"])) {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select();
        $query = process_querystring($query, $request);
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
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getRelations', '{}');
        return (new ApiResponse($entity['relations'], TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getRelations', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getRelations', json_encode(["error" => $exceptionDetails]));
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
      if (Gate::allows(AuthServiceProvider::READ_ENTITY, [$id, 'getInverseRelations', "{}"])) {
        $lang = $request->input('lang', Config::get('general.langs')[0]);
        $query = Entity::select();
        $query = process_querystring($query, $request);
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
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, TRUE, 'getInverseRelations', '{}');
        return (new ApiResponse($entity['inverse_relations'], TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getInverseRelations', json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
        $exceptionDetails = ExceptionDetails::filter($e);
        Activity::add(Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, FALSE, 'getInverseRelations', json_encode(["error" => $exceptionDetails]));
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
      if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id, 'relation']) === true) {
        // TODO: Filter the json to delete al not used data
        $this->validate($request, [
            "id" => "required|string",
            "kind" => "required|string"
        ], Config::get('validator.messages'));
        $body = $request->json()->all();
        $relationPosted = Entity::find($id)->addRelation($body);
        Activity::add(\Auth::user()['id'], $relationPosted['id'], AuthServiceProvider::WRITE_ENTITY, TRUE, 'postRelation', json_encode(["body" => $body]));
        return (new ApiResponse($relationPosted, TRUE))->response();
      } else {
        Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postRelation', json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));
        return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
      }
    } catch (\Throwable $e) {
      $exceptionDetails = ExceptionDetails::filter($e);
      Activity::add(Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, FALSE, 'postRelation', json_encode(["error" => $exceptionDetails]));
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
      Activity::add(Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, FALSE, 'postRelation', json_encode(["error" => $exceptionDetails]));
      return (new ApiResponse(NULL, FALSE, $exceptionDetails, $exceptionDetails['code']))->response();
    }
  }
}
