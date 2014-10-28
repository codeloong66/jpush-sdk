<?php

class JPush_JPushClient {

    const PUSH_URL = 'https://api.jpush.cn/v3/push';
    const REPORT_URL = 'https://report.jpush.cn/v2/received';
    const USER_AGENT = 'JPush-API-PHP-Client';
    const CONNECT_TIMEOUT = 5;
    const READ_TIMEOUT = 30;
    const DEFAULT_MAX_RETRY_TIMES = 3;

    public $appKey;
    public $masterSecret;
    public $retryTimes;

    public function __construct($appKey, $masterSecret, $retryTimes = self::DEFAULT_MAX_RETRY_TIMES) {
        if (is_null($appKey) || is_null($masterSecret)) {
            throw new Exception('appKey and masterSecret must be set.');
        }
        if (! is_string($appKey) || ! is_string($masterSecret)) {
            throw new Exception('Invalid appKey or masterSecret');
        }

        $this->appKey = $appKey;
        $this->masterSecret = $masterSecret;
        $this->retryTimes = $retryTimes;
    }

    public function send(JPush_MessageInterface $message) {
        $request = $this->_createRequest(self::PUSH_URL);
        $request->setOption(CURLOPT_POSTFIELDS, $message->getPushMessage());

        return $request->send();
    }

    private function _createRequest($url, $method = JPush_Request::HTTP_POST) {
        $request = new JPush_Request($url, $method);
        $request->setHeaders($this->_getHeader());
        $request->setOptions($this->_getOptions());

        return $request;
    }

    private function _getHeader() {
        return array(
            'Authorization: Basic ' . $this->_createAuthString(),
            'User-Agent: JPush-API-PHP-Client',
            'Connection: Keep-Alive',
            'Charset: UTF-8',
            'Content-Type: application/json'
        );
    }

    private function _getOptions() {
        return array(
            CURLOPT_HEADER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        );
    }

    private function _createAuthString() {
        return base64_encode($this->appKey . ':' . $this->masterSecret);
    }
}