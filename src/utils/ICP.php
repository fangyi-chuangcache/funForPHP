<?php

require "HttpUtils.php";

class ICP
{
    public static function queryICP($domain) {
        $url = 'http://www.sojson.com/api/beian/' . $domain;
        return json_encode(HttpUtils::doGetA($url), JSON_UNESCAPED_SLASHES);
    }
}
