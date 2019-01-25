<?php

namespace Cuatromedios\Kusikusi\Models\Http;

use Illuminate\Http\Response;

class ApiResponse
{

    const TEXT_BADREQUEST = 'Bad Request';
    const TEXT_UNAUTHORIZED = 'Unauthorized';
    const TEXT_FORBIDDEN = 'Forbidden';
    const TEXT_NOTFOUND = 'Not Found';
    const TEXT_METHODNOTALLOWED = 'Method Not Allowed';
    const TEXT_INTERNALERROR = 'Internar Server Error';

    const STATUS_BADREQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOTFOUND = 404;
    const STATUS_METHODNOTALLOWED = 404;
    const STATUS_INTERNALERROR = 500;

    private $_result;
    private $_success;
    private $_status;
    private $_info;
    public function __construct($result, $success = TRUE, $info = NULL, $status = 200)
    {
        $this->setResult($result);
        $this->setSuccess($success);
        $this->setStatus($status);
        $this->setInfo($info);
    }
    public function getResult() {
        return $this->_result;
    }
    public function getSuccess() {
        return $this->_success;
    }
    public function getStatus() {
        return $this->_status;
    }
    public function getInfo() {
        return $this->_info;
    }
    public function setResult($result) {
        $this->_result = $result;
    }
    public function setSuccess($success) {
        if ($success === TRUE || $success === 'true') {
            $this->_success = TRUE;
        } else {
            $this->_success = FALSE;
        }
    }
    public function setStatus($status) {
        $status = (int) $status;
        if (is_nan($status)) { $status = 500; }
        $this->_status = $status;
    }
    public function setInfo($info) {
        $this->_info = $info;
    }
    public function response() {
        $response = new Response([
            "success" =>  $this->getSuccess(),
            "result" =>  $this->getResult(),
            "info" =>  $this->getInfo()
        ], $this->getStatus());
        return $response;
    }
}
