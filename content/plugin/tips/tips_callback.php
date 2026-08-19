<?php
defined('HOPE_ROOT') || exit('access denied!');

function callback_init() {
    // 插件启用即生效，无需写入配置
}

function callback_up() {
    callback_init();
}

function callback_rm() {
    Storage::getInstance('tips')->deleteAllName('YES');
}
