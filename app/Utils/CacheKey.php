<?php

namespace App\Utils;

class CacheKey
{
    const EMAIL_VERIFY_CODE = "EMAIL_VERIFY_CODE";
    const LAST_SEND_EMAIL_VERIFY_TIMESTAMP = "LAST_SEND_EMAIL_VERIFY_TIMESTAMP";
    const SERVER_V2RAY_ONLINE_USER = "SERVER_V2RAY_ONLINE_USER";
    const SERVER_V2RAY_LAST_CHECK_AT = "SERVER_V2RAY_LAST_CHECK_AT";
    const SERVER_V2RAY_LAST_PUSH_AT = "SERVER_V2RAY_LAST_PUSH_AT";
    const SERVER_TROJAN_ONLINE_USER = "SERVER_TROJAN_ONLINE_USER";
    const SERVER_TROJAN_LAST_CHECK_AT = "SERVER_TROJAN_LAST_CHECK_AT";
    const SERVER_TROJAN_LAST_PUSH_AT = "SERVER_TROJAN_LAST_PUSH_AT";
    const SERVER_SHADOWSOCKS_ONLINE_USER = "SERVER_SHADOWSOCKS_ONLINE_USER";
    const SERVER_SHADOWSOCKS_LAST_CHECK_AT = "SERVER_SHADOWSOCKS_LAST_CHECK_AT";
    const SERVER_SHADOWSOCKS_LAST_PUSH_AT = "SERVER_SHADOWSOCKS_LAST_PUSH_AT";
    const TEMP_TOKEN = "TEMP_TOKEN";
    const LAST_SEND_EMAIL_REMIND_TRAFFIC = "LAST_SEND_EMAIL_REMIND_TRAFFIC";




    CONST KEYS = [
         self::EMAIL_VERIFY_CODE => '邮箱验证码',
         self::LAST_SEND_EMAIL_VERIFY_TIMESTAMP => '最后一次发送邮箱验证码时间',
         self::SERVER_V2RAY_ONLINE_USER => '节点在线用户',
         self::SERVER_V2RAY_LAST_CHECK_AT => '节点最后检查时间',
         self::SERVER_V2RAY_LAST_PUSH_AT => '节点最后推送时间',
         self::SERVER_TROJAN_ONLINE_USER => 'trojan节点在线用户',
         self::SERVER_TROJAN_LAST_CHECK_AT => 'trojan节点最后检查时间',
         self::SERVER_TROJAN_LAST_PUSH_AT => 'trojan节点最后推送时间',
         self::SERVER_SHADOWSOCKS_ONLINE_USER => 'ss节点在线用户',
         self::SERVER_SHADOWSOCKS_LAST_CHECK_AT => 'ss节点最后检查时间',
         self::SERVER_SHADOWSOCKS_LAST_PUSH_AT => 'ss节点最后推送时间',
         self::TEMP_TOKEN => '临时令牌',
         self::LAST_SEND_EMAIL_REMIND_TRAFFIC => '最后发送流量邮件提醒'
    ];

    /**
     * 获取cacheKey
     *
     * @param string $key
     * @param $uniqueValue
     * @return string|null
     */
    public static function get(string $key, $uniqueValue): ?string
    {
        if (!in_array($key, array_keys(self::KEYS))) {
            return null;
        }
        return $key . '_' . $uniqueValue;
    }

}
