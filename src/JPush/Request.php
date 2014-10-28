<?php

class JPush_Request {

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';

    private $_ch;

    private $_url;

    private $_method;

    private $_headers = array();

    private $_options = array();

    public function __construct($url, $method = self::HTTP_POST) {
        $this->_url = $url;
        $this->_method = $method;
    }

    /**
     * send request
     * @return mixed
     */
    public function send() {
        $this->_initCurl();

        $result = curl_exec($this->_ch);
        if ($result === false) {
            if ($errorNO = curl_errno($this->_ch)) {
                $error = curl_error($this->_ch);
                throw new JPush_Exception_ConnectionException("Unable to connect: {$errorNO} {$error}");
            }

            throw new JPush_Exception_ConnectionException('Unable to connect.');
        }

        $info = curl_getinfo($this->_ch);
        curl_close($this->_ch);

        $proxy_regex = "/HTTP\/1\.[01] 200 Connection established.*?\r\n\r\n/s";
        if (preg_match($proxy_regex, $result)) {
            $result = preg_replace($proxy_regex, '', $result);
        }

        $response = explode("\r\n\r\n", $result, 2 + $info['redirect_count']);
        $headers = $response[0];
        $body = $response[1];

        $response = new JPush_Response($body, $headers, $this);
        if ($response->code !== 200) {
            throw JPush_Exception_RequestException::fromResponse($response);
        }

        return $response;
    }

    public function setHeader($key, $value) {
        $this->_headers[$key] = $value;
    }

    public function setHeaders(array $headers) {
        $this->_headers = $headers;
    }

    public function addHeaders(array $headers) {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    public function setOption($key, $value) {
        $this->_options[$key] = $value;
    }

    public function setOptions(array $options) {
        $this->_options = $options;
    }

    public function addOptions(array $options) {
        foreach ($options as $key => $value) {
            $this->_options[$key] = $value;
        }
    }

    public function getOptions() {
        return $this->_options;
    }

    public function getOption($option) {
        return $this->_options[$option];
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function getHeader($header) {
        return $this->_headers[$header];
    }

    public function getUrl() {
        return $this->_url;
    }

    public function getMethod() {
        return $this->_method;
    }

    private function _initCurl() {
        $ch = curl_init($this->_url);

        // set curl request method
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->_method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // set curl option
        foreach ($this->_options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        // set http header
        if (count($this->_headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
        }

        $this->_ch = $ch;
    }
}