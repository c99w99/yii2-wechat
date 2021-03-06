<?php

namespace lspbupt\wechat;

use Yii;
use yii\helpers\ArrayHelper;


/**
 *  微信小程序代码，详见https://developers.weixin.qq.com/miniprogram/dev/api-backend/auth.code2Session.html
 *  除了sns/jscode2session部分，其余接口的调用与wechat实例一致。
 **/
class WxSmallApp extends \lspbupt\wechat\Wechat
{
    public $excludeActions = ['/sns/jscode2session'];
    public $wechat;

    public function init()
    {
        parent::init();
        //兼容历史
        $this->wechat = $this;
    }

    public function jscode2session($js_code, $grant_type = 'authorization_code')
    {
        return $this->setGet()->httpExec('/sns/jscode2session', [
            'appid' => $this->appid,
            'secret' => $this->appsecret,
            'js_code' => $js_code,
            'grant_type' => $grant_type,
        ]);
    }

    public function checkSign($sessionKey, $rawData, $sign)
    {
        $checkSign = sha1($rawData.$sessionKey);
        return $sign == $checkSign;
    }

    public function decrypt($sessionKey, $encryptedData, $iv, &$data)
    {
        if (strlen($sessionKey) != 24) {
            return 'AESKEY不正确';
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            return 'iv不正确';
        }
        $aesIV = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, 1, $aesIV);

        $data = json_decode($result, true);
        if (empty($data)) {
            return '数据不正确';
        }
        $appid = ArrayHelper::getValue($data, 'watermark.appid', '');
        if ($appid != $this->appid) {
            return 'appid不正确';
        }
        return false;
    }
}
