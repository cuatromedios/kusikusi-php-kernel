<?php
namespace Cuatromedios\Kusikusi\Models\Http;

use Illuminate\Http\Response;

/**
 * Class ApiResponse
 *
 * @package Cuatromedios\Kusikusi\Models\Http
 */
class ApiResponse
{

    /**
     *
     */
    const TEXT_BADREQUEST = 'Bad Request';
    /**
     *
     */
    const TEXT_UNAUTHORIZED = 'Unauthorized';
    /**
     *
     */
    const TEXT_FORBIDDEN = 'Forbidden';
    /**
     *
     */
    const TEXT_NOTFOUND = 'Not Found';
    /**
     *
     */
    const TEXT_METHODNOTALLOWED = 'Method Not Allowed';
    /**
     *
     */
    const TEXT_INTERNALERROR = 'Internar Server Error';

    /**
     *
     */
    const STATUS_BADREQUEST = 400;
    /**
     *
     */
    const STATUS_UNAUTHORIZED = 401;
    /**
     *
     */
    const STATUS_FORBIDDEN = 403;
    /**
     *
     */
    const STATUS_NOTFOUND = 404;
    /**
     *
     */
    const STATUS_METHODNOTALLOWED = 404;
    /**
     *
     */
    const STATUS_INTERNALERROR = 500;

    /**
     * @var
     */
    private $_result;
    /**
     * @var
     */
    private $_success;
    /**
     * @var
     */
    private $_status;
    /**
     * @var
     */
    private $_info;

    /**
     * ApiResponse constructor.
     *
     * @param $result
     * @param bool $success
     * @param null $info
     * @param int $status
     */
    public function __construct($result, $success = true, $info = null, $status = 200)
    {
        $this->setResult($result);
        $this->setSuccess($success);
        $this->setStatus($status);
        $this->setInfo($info);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function response()
    {
        $response = new Response([
            "success" => $this->getSuccess(),
            "result"  => $this->getResult(),
            "info"    => $this->getInfo(),
        ], $this->getStatus());

        return $response;
    }

    /**
     * @return mixed
     */
    public function getSuccess()
    {
        return $this->_success;
    }

    /**
     * @param $success
     */
    public function setSuccess($success)
    {
        if ($success === true || $success === 'true') {
            $this->_success = true;
        } else {
            $this->_success = false;
        }
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * @param $result
     */
    public function setResult($result)
    {
        $this->_result = $result;
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->_info;
    }

    /**
     * @param $info
     */
    public function setInfo($info)
    {
        $this->_info = $info;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param $status
     */
    public function setStatus($status)
    {
        $status = (int)$status;
        if (is_nan($status)) {
            $status = 500;
        }
        $this->_status = $status;
    }
}
