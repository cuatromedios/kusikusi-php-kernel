<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Config;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;

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
            $fields = $request->input('fields', []);
            $entity = Entity::getOne($id, $fields, $lang);
            if (Gate::allows('get-entity', $id)) {
                return (new ApiResponse($entity, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
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
            if (Gate::allows('post-entity', $request->parent) === true) {
                $entityPostedId = Entity::post($request->json()->all());
                return (new ApiResponse($entityPostedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('patch-entity', $id) === true) {
                $entityPatchedId = Entity::patch($id, $request->json()->all());
                return (new ApiResponse($entityPatchedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('delete-entity', $id) === true) {
                $entitySoftDeletedId = Entity::softDelete($id);
                return (new ApiResponse($entitySoftDeletedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('delete-entity', $id) === true) {
                $entityHardDeletedId = Entity::hardDelete($id);
                return (new ApiResponse($entityHardDeletedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            $entity = Entity::getParent($id, $fields, $lang);
            if (Gate::allows('get-entity', $id)) {
                return (new ApiResponse($entity, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('get-all')) {
                $collection = Entity::get(NULL, $fields, $lang, $order);
                return (new ApiResponse($collection, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('get-entity', $id)) {
                $collection = Entity::getChildren($id, $fields, $lang, $order);
                return (new ApiResponse($collection, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('get-entity', $id)) {
                $collection = Entity::getAncestors($id, $fields, $lang, $order);
                return (new ApiResponse($collection, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('get-entity', $id)) {
                $collection = Entity::getDescendants($id, $fields, $lang, $order);
                return (new ApiResponse($collection, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('get-entity', $id)) {
                $collection = Entity::getEntityRelations($id, $kind, $fields, $lang, $order);
                return (new ApiResponse($collection, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('get-entity', $id)) {
                $collection = Entity::getInverseEntityRelations($id, $kind, $fields, $lang, $order);
                return (new ApiResponse($collection, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('patch-entity', $id) === true) {
                $entityPostedId = Entity::postRelation($id, $request->json()->all());
                return (new ApiResponse($entityPostedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
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
            if (Gate::allows('patch-entity', $id) === true) {
                $entityPostedId = Entity::deleteRelation($id, $called, $kind);
                return (new ApiResponse($entityPostedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }
}
