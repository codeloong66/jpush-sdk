<?php

class JPush_PushMessage implements JPush_MessageInterface {

    /**
     * 推送平台
     */
    const PLATFORM_ALL = 'all';
    const PLATFORM_IOS = 'ios';
    const PLATFORM_ANDROID = 'android';
    const PLATFORM_WINPHONE = 'winphone';

    /**
     * 推送对象规则
     */
    const AUDIENCE_ALL = 'all';
    const AUDIENCE_TAG = 'tag';
    const AUDIENCE_TAG_AND = 'tag_and';
    const AUDIENCE_ALIAS = 'alias';
    const AUDIENCE_REGISTRATION_ID = 'registration_id';

    private static $_platformType = array(
        self::PLATFORM_IOS,
        self::PLATFORM_ANDROID,
        self::PLATFORM_WINPHONE
    );

    private static $_audiencesType = array(
        self::AUDIENCE_ALL,
        self::AUDIENCE_TAG,
        self::AUDIENCE_TAG_AND,
        self::AUDIENCE_ALIAS,
        self::AUDIENCE_REGISTRATION_ID
    );

    private $platform;
    private $audience;
    private $notification;
    private $message;
    private $options;

    public function getPushMessage() {
        if (is_null($this->platform) || is_null($this->audience)) {
            throw new InvalidArgumentException('platform and audience must be set');
        }
        if (is_null($this->notification) && is_null($this->message)) {
            throw new InvalidArgumentException('Either or both notification and message must be set.');
        }

        $msg = array(
            'platform' => $this->platform,
            'audience' => $this->audience
        );

        if (! is_null($this->notification)) {
            $msg['notification'] = $this->notification;
        }

        if (! is_null($this->message)) {
            $msg['message'] = $this->message;
        }

        if (! is_null($this->options)) {
            $msg['options'] = $this->options;
        } else {
            $msg['options'] = array('sendno' => $this->_createGenerateSendno());
        }

        return json_encode($msg);
    }

    /**
     * set platfrom
     * @return JPush_PushMessage
     */
    public function setPlatform() {
        if (func_num_args() === 1 && func_get_arg(0) === self::PLATFORM_ALL) {
            $this->platform = self::PLATFORM_ALL;
        } else {
            foreach (func_get_args() as $type) {
                if (! in_array($type, self::$_platformType)) {
                    throw new InvalidArgumentException("Invalid device type: {$type}");
                }
            }

            $this->platform = func_get_args();
        }

        return $this;
    }

    /**
     * set audience
     * 
     * @return JPush_PushMessage
     */
    public function setAudience($type, array $rules = null) {
        if (! in_array($type, self::$_audiencesType)) {
            throw new InvalidArgumentException("Invalid audience type: {$type}");
        }

        if ($type === self::AUDIENCE_ALL) {
            $this->audience = self::AUDIENCE_ALL;
        } else {
            if (is_null($rules) || count($rules) < 1) {
                throw new InvalidArgumentException('Invalid rules must be a array and Length of the rule must be greater than 0');
            }

            foreach ($rules as $key=>$rule) {
                if (! is_string($rule) || strlen($rule) < 1) {
                    throw new InvalidArgumentException("Invalid rule, rules[{$key}] must be a string and length be greater than 0");
                }
            }
            $this->audience[$type] = $rules;
        }

        return $this;
    }

    /**
     * 设置通知内容
     * 
     * @param string $alert 通知内容
     */
    public function setNotification($alert) {
        if (! is_string($alert) || strlen($alert) < 1) {
            throw new InvalidArgumentException('Invalid notification.alert must be a string');
        }

        $this->notification['alert'] = $alert;
        return $this;
    }

    /**
     * 设置 IOS平台上通知
     * 
     * @param string $alert 通知内容
     * @param string $sound 通知提示音
     * @param string $badge 应用角标
     * @param boolean $contentAvailable 静默推送标志
     * @param array $extras 扩展字段
     */
    public function setNotificationForIOS($alert, $sound = null, $badge = null, $contentAvailable = null, array $extras = null) {
        if (! is_string($alert) || strlen($alert) < 1) {
            throw new InvalidArgumentException('Invalid notification.ios.alert must be a string');
        }

        $nf = array('alert'=>$alert);

        if (! is_null($sound)) {
            if (! is_string($sound)) {
                throw new InvalidArgumentException('Invalid notification.ios.sound must be a string');
            }
            $nf['sound'] = $sound;
        } else {
            $nf['sound'] = '';
        }

        if (! is_null($badge)) {
            if (is_string($badge) && ! preg_match('/^[+-]{1}[0-9]{1,3}$/', $badge)) {
                if (! is_int($badge)){
                    throw new InvalidArgumentException('Invalid notification.ios.badge');
                }
            }

            $nf['badge'] = $badge;
        } else {
            $nf['badge'] = 1;
        }

        if (! is_null($contentAvailable)) {
            if (! is_bool($contentAvailable)) {
                throw new InvalidArgumentException('Invalid notification.ios.content-available must be a bool');
            }
            if ($contentAvailable) {
                $nf['content-available'] = 1;
            }
        }

        if (! is_null($extras) && count($extras) > 0) {
            $nf['extras'] = $extras;
        }

        $this->notification['ios'] = $nf;

        return $this;
    }

    /**
     * 设置 Android平台上的通知
     * 
     * @param string $alert 通知内容
     * @param string $title 通知标题
     * @param integer $builderId 通知栏样式ID
     * @param array $extras 扩展字段
     */
    public function setNotificationForAndroid($alert, $title = null, $builderId = null, array $extras = null) {
        if (! is_string($alert) || strlen($alert) < 1) {
            throw new InvalidArgumentException('Invalid notification.android.alert must be a string');
        }

        $nf = array('alert'=>$alert);

        if (! is_null($title)) {
            if (! is_string($title) && strlen($title) > 0) {
                throw new InvalidArgumentException('Invalid notification.android.title must be a string and length lg 0');
            }
            $nf['title'] = $title;
        }

        if (! is_null($builderId)) {
            if (! is_int($builderId)) {
                throw new InvalidArgumentException('Invalid notification.android.builder_id must be a int');
            }
            $nf['builder_id'] = $builderId;
        }

        if (! is_null($extras)) {
            $nf['extras'] = $extras;
        }

        $this->notification['android'] = $nf;

        return $this;
    }

    /**
     * 设置 Windows Phone 平台上的通知
     * 
     * @param string $alert 通知内容
     * @param string $title 通知标题
     * @param string $openPage 点击打开的页面名称
     * @param array $extras 扩展字段
     */
    public function setNotificationForWinphone($alert, $title = null, $openPage = null, array $extras = null) {
        if (! is_string($alert) || strlen($alert) < 1) {
            throw new InvalidArgumentException('Invalid notification.winphone.alert must be a string');
        }

        $nf = array('alert'=>$alert);

        if (! is_null($title)) {
            if (! is_string($title) && strlen($title) > 0) {
                throw new InvalidArgumentException('Invalid notification.winphone.title must be a string and length lg 0');
            }
            $nf['title'] = $title;
        }

        if (! is_null($openPage)) {
            if (! is_string($openPage) && strlen($openPage) > 0) {
                throw new InvalidArgumentException('Invalid notification.winphone._open_page mesh be a string and length lg 0');
            }
            $nf['_open_page'] = $openPage;
        }

        if (! is_null($extras)) {
            $nf['extras'] = $extras;
        }

        $this->notification['winphone'] = $nf;

        return $this;
    }

    /**
     * 设置应用内消息
     * @param string $msgContent 消息内容本身
     * @param string $title 消息标题
     * @param string $contentType 消息内容类型
     * @param array $extras JSON格式的可选参数
     */
    public function setMessage($msgContent, $title = null, $contentType = null, array $extras = null) {
        $msg = array();
        if (is_null($msgContent) || ! is_string($msgContent)) {
            throw new InvalidArgumentException('message.msg_content must be a string');
        }
        $msg['msg_content'] = $msgContent;

        if (! is_null($title)) {
            if (! is_string($title)) {
                throw new InvalidArgumentException('message.title must be a string');
            }
            $msg['title'] = $title;
        }

        if (! is_null($contentType)) {
            if (! is_string($contentType)) {
                throw new InvalidArgumentException('message.content_type must be a string');
            }
            $msg['content_type'] = $contentType;
        }

        if (! is_null($extras)) {
            $msg['extras'] = $extras;
        }

        $this->message = $msg;
        return $this;
    }

    /**
     * 设置推送可选项
     * @param integer $sendno 推送序号
     * @param integer $timeToLive 离线消息保留时长，最长10天
     * @param long $overrideMsgId 要覆盖的消息ID
     * @param boolean $apnsProduction APNs是否为生产环境
     */
    public function setOptions($sendno = null, $timeToLive = null, $overrideMsgId = null, $apnsProduction = null) {
        if ($sendno === null && $timeToLive === null
            && $overrideMsgId === null
            && $apnsProduction === null) {
            throw new InvalidArgumentException('Not all options args is null');
        }

        $options = array();

        if (! is_null($sendno)) {
            if (is_int($sendno)) {
                $options['sendno'] = $sendno;
            } else {
                throw new InvalidArgumentException('options.sendno must be a int');
            }
        } else {
            $options['sendno'] = $this->_createGenerateSendno();
        }

        if (! is_null($timeToLive)) {
            if (is_int($timeToLive) && $timeToLive >= 0 && $timeToLive <= 864000) {
                $options['time_to_live'] = $timeToLive;
            } else {
                throw new InvalidArgumentException('options.time_to_live must be a int and in [0, 864000]');
            }
        }

        if (! is_null($overrideMsgId)) {
            if (is_long($overrideMsgId)) {
                $options['override_msg_id'] = $overrideMsgId;
            } else {
                throw new InvalidArgumentException('options.override_msg_id must be a long');
            }
        }

        if (! is_null($apnsProduction)) {
            if (is_bool($apnsProduction)) {
                $options['apns_production'] = $apnsProduction;
            }  else {
                throw new InvalidArgumentException('options.apns_production must be a bool');
            }
        } else {
            $options['apns_production'] = false;
        }

        $this->options = $options;

        return $this;
    }

    private function _createGenerateSendno() {
        return time();
    }
}