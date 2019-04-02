<?php
namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use App\Models\Entity;
use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Models\Activity;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Cuatromedios\Kusikusi\Providers\AuthServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Class MediaController
 *
 * @package Cuatromedios\Kusikusi\Http\Controllers\Api
 */
class MediaController extends Controller
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
     * Create the specified media, it's all equal that post entity, but this forces the parent to be the Media Container.
     *
     * @param $id
     *
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        try {
            if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$request->parent_id]) === true) {
                // TODO: Filter the json to delete al not used data
                $information = $request->json()->all();
                if (!isset($information['parent_id'])) {
                    $information['parent_id'] = 'media';
                }
                if (!isset($information['model'])) {
                    $information['model'] = 'medium';
                }
                $entityPosted = new Entity($body);
                $entityPosted->save();
                Activity::add(\Auth::user()['id'], $entityPosted['id'], AuthServiceProvider::WRITE_ENTITY, true, 'post',
                    json_encode(["body" => $body]));

                return (new ApiResponse($entityPosted, true))->response();
            } else {
                Activity::add(\Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, false, 'post',
                    json_encode(["body" => $body, "error" => ApiResponse::TEXT_FORBIDDEN]));

                return (new ApiResponse(null, false, ApiResponse::TEXT_FORBIDDEN,
                    ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Throwable $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            Activity::add(Auth::user()['id'], '', AuthServiceProvider::WRITE_ENTITY, false, 'post',
                json_encode(["error" => $exceptionDetails]));

            return (new ApiResponse(null, false, $exceptionDetails, $exceptionDetails['code']))->response();
        }
    }

    /**
     * Gets the raw request and search for the corresponing entity to know its model.
     *
     * @param $request \Illuminate\Http\Request
     *
     * @return \Illuminate\Http\Response
     */
    public function upload($id, Request $request)
    {
        try {
            try {
                $entity = Entity::findOrFail($id);
            } catch (\Exception $e) {
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, false, 'get',
                    json_encode(["error" => ApiResponse::TEXT_NOTFOUND]));

                return (new ApiResponse(null, false, 'Media ' . ApiResponse::TEXT_NOTFOUND,
                    ApiResponse::STATUS_NOTFOUND))->response();
            }
            if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
                function processFile($id, $function, $file)
                {
                    $fileRead = $file->getClientOriginalExtension() ? $file->getClientOriginalExtension() : $file->guessClientExtension();
                    $format = $fileRead == 'jpeg' ? 'jpg' : $fileRead;
                    $data = [
                        'id'       => $id,
                        'format'   => $format,
                        'size'     => $file->getClientSize(),
                        'function' => $function,
                    ];
                    $storageFileName = $function . '.' . $data['format'];
                    Storage::disk('media_original')->putFileAs($id, $file, $storageFileName);
                    Storage::disk('media_processed')->deleteDirectory($id);

                    return $data;
                }

                $data = null;
                if ($request->hasFile('thumb') && $request->file('thumb')->isValid()) {
                    $data = processFile($id, 'thumb', $request->file('thumb'));
                }
                if ($request->hasFile('file') && $request->file('file')->isValid()) {
                    $data = processFile($id, 'file', $request->file('file'));
                    $entity['data'] = $data;
                    $entity->save();
                }
                if (null === $data) {
                    Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, false, 'post',
                        json_encode(["body" => $data, "error" => ApiResponse::TEXT_BADREQUEST]));

                    return (new ApiResponse(null, false, ApiResponse::TEXT_BADREQUEST,
                        ApiResponse::STATUS_BADREQUEST))->response();
                }
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, true, 'post',
                    json_encode(["body" => $data]));

                return (new ApiResponse($data, true))->response();
            } else {
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, false, 'post',
                    json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));

                return (new ApiResponse(null, false, ApiResponse::TEXT_FORBIDDEN,
                    ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, false, 'post',
                json_encode(["body" => $data, "error" => $exceptionDetails['info']]));

            return (new ApiResponse(null, false, $exceptionDetails, $exceptionDetails['code']))->response();
        }
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        try {
            try {
                $entity = Entity::findOrFail($id);
            } catch (\Exception $e) {
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::READ_ENTITY, false, 'delete',
                    json_encode(["error" => ApiResponse::TEXT_NOTFOUND]));

                return (new ApiResponse(null, false, 'Media ' . ApiResponse::TEXT_NOTFOUND,
                    ApiResponse::STATUS_NOTFOUND))->response();
            }
            if (Gate::allows(AuthServiceProvider::WRITE_ENTITY, [$id]) === true) {
                $deletedMedia = DB::table('media')->where('id', $id);
                $deletedMedia->delete();
                Storage::disk('media_original')->deleteDirectory($id);
                Storage::disk('media_processed')->deleteDirectory($id);
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, true, 'delete', '{}');

                return (new ApiResponse($entity['id'], true))->response();
            } else {
                Activity::add(\Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, false, 'delete',
                    json_encode(["error" => ApiResponse::TEXT_FORBIDDEN]));

                return (new ApiResponse(null, false, ApiResponse::TEXT_FORBIDDEN,
                    ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Throwable $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            Activity::add(Auth::user()['id'], $id, AuthServiceProvider::WRITE_ENTITY, false, 'delete',
                json_encode(["error" => $exceptionDetails]));

            return (new ApiResponse(null, false, $exceptionDetails, $exceptionDetails['code']))->response();
        }
    }
}
