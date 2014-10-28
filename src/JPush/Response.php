<?php

class JPush_Response {

    public $request;

    public $headers;

    public $rawHeaders;

    public $body;

    public $rawBody;

    public $code = 0;

    public function __construct($body, $headers, JPush_Request $request) {
        $this->rowBody = $body;
        $this->rawHeaders = $headers;
        $this->request = $request;

        $this->code = $this->_parseCode($headers);
        $this->headers = $this->_parseHeaders($headers);
        $this->body = $this->_parseBody($body);
    }

    public function hasErrors() {
        return $this->code >= 400;
    }

    public function hasBody() {
        return ! empty($this->body);
    }

    private function _parseBody($string) {
        if ($this->headers['content-type'] !== 'application/json') {
            return $string;
        }

        return json_decode($string);
    }

    private function _parseCode($headers) {
        $parts = explode(' ', substr($headers, 0, strpos($headers, "\r\n")));
        if (count($parts) < 2 || ! is_numeric($parts[1])) {
            throw new Exception('Unable to parse response code from HTTP response due to malformed response');
        }

        return intval($parts[1]);
    }

    private function _parseHeaders($string) {
        $lines = preg_split("/(\r|\n)+/", $string, -1, PREG_SPLIT_NO_EMPTY);
        // http header
        array_shift($lines);

        $headers = array();
        foreach ($lines as $line) {
            list($name, $value) = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }

    public function __toString() {
        return $this->rawBody;
    }
}