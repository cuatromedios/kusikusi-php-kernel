<?php
namespace Cuatromedios\Kusikusi\Exceptions;

use Cuatromedios\Kusikusi\Models\Http\ApiResponse;

/**
 * Class ExceptionDetails
 *
 * @package Cuatromedios\Kusikusi\Exceptions
 */
class ExceptionDetails
{
    /**
     * @param \Throwable $e
     *
     * @return mixed
     */
    public static function filter(\Throwable $e)
    {
        $result = ["info" => []];
        switch (get_class($e)) {
            case "Illuminate\Database\QueryException":
                $result["info"]["code"] = ApiResponse::STATUS_BADREQUEST;
                $result["info"]["message"] = ApiResponse::TEXT_BADREQUEST;
                break;
            case "Illuminate\Database\Eloquent\ModelNotFoundException":
                $result["info"]["code"] = ApiResponse::STATUS_NOTFOUND;
                $result["info"]["message"] = ApiResponse::TEXT_NOTFOUND;
                break;
            case "Illuminate\Validation\ValidationException":
                $result["info"]["code"] = ApiResponse::STATUS_BADREQUEST;
                $result["info"]["message"] = $messages = implode(" ",
                    array_flatten($e->validator->getMessageBag()->getMessages()));
                break;
            default:
                $result["info"]["code"] = $e->getCode() ?? $e->getCode() != 0 ? $e->getCode() : ApiResponse::STATUS_INTERNALERROR;
                $result["info"]["message"] = $e->getMessage() ?? ApiResponse::TEXT_INTERNALERROR;
        }
        if ('local' === env('APP_ENV', config('app.env', 'production'))) {
            $result['info']['exception'] = [
                "error" => $e->getMessage(),
                "file"  => $e->getFile(),
                "line"  => $e->getLine(),
                "trace" => $e->getTraceAsString(),
            ];
        }

        return $result['info'];
    }
}
