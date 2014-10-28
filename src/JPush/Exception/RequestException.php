<?php

class JPush_Exception_RequestException extends Exception {

    public $httpCode;
    public $code;
    public $message;
    public $json;

    public $rateLimitLimit;
    public $rateLimitRemaining;
    public $rateLimitReset;

    private static $expected_keys = array('code', 'message');

    public static function fromResponse(JPush_Response $response) {
        $e = new self();
        $body = json_decode($response->rowBody, true);
        if ($body != null) {
            $error = (array) $body['error'];
            foreach (self::$expected_keys as $key) {
                if (array_key_exists($key, $error)) {
                    $e->$key = $error[$key];
                }
            }
        }

        $e->json = $response->rowBody;
        $e->response = $response->rowBody;
        $e->httpCode = $response->code;
        $headers = $response->headers;
        if (!is_null($headers)) {
            $e->rateLimitLimit = $headers['x-rate-limit-limit'];
            $e->rateLimitRemaining = $headers['x-rate-limit-remaining'];
            $e->rateLimitReset = $headers['x-rate-limit-reset'];
        }

        return $e;
    }
}