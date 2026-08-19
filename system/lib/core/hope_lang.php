<?php
/**
 * Hope CMS 后台多语言
 */
defined('HOPE_ROOT') || exit('access denied!');

function hope_lang_is_admin() {
    return defined('HOPE_ADMIN') && HOPE_ADMIN;
}

function hope_lang_registry() {
    return [
        'zh-cn' => ['label' => '简体中文', 'native' => '简体中文', 'html' => 'zh-CN', 'rtl' => false],
        'zh-tw' => ['label' => '繁體中文', 'native' => '繁體中文', 'html' => 'zh-TW', 'rtl' => false],
        'en'    => ['label' => 'English', 'native' => 'English', 'html' => 'en', 'rtl' => false],
        'ja'    => ['label' => 'Japanese', 'native' => '日本語', 'html' => 'ja', 'rtl' => false],
        'ko'    => ['label' => 'Korean', 'native' => '한국어', 'html' => 'ko', 'rtl' => false],
        'fr'    => ['label' => 'French', 'native' => 'Français', 'html' => 'fr', 'rtl' => false],
        'de'    => ['label' => 'German', 'native' => 'Deutsch', 'html' => 'de', 'rtl' => false],
        'es'    => ['label' => 'Spanish', 'native' => 'Español', 'html' => 'es', 'rtl' => false],
        'pt'    => ['label' => 'Portuguese', 'native' => 'Português', 'html' => 'pt', 'rtl' => false],
        'ru'    => ['label' => 'Russian', 'native' => 'Русский', 'html' => 'ru', 'rtl' => false],
        'ar'    => ['label' => 'Arabic', 'native' => 'العربية', 'html' => 'ar', 'rtl' => true],
        'vi'    => ['label' => 'Vietnamese', 'native' => 'Tiếng Việt', 'html' => 'vi', 'rtl' => false],
        'th'    => ['label' => 'Thai', 'native' => 'ไทย', 'html' => 'th', 'rtl' => false],
        'id'    => ['label' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'html' => 'id', 'rtl' => false],
    ];
}

function hope_lang_normalize($locale) {
    $locale = strtolower(trim(str_replace('_', '-', (string)$locale)));
    $registry = hope_lang_registry();
    if (isset($registry[$locale])) {
        return $locale;
    }
    $short = explode('-', $locale)[0];
    foreach (array_keys($registry) as $code) {
        if (strpos($code, $short) === 0) {
            return $code;
        }
    }
    return '';
}

function hope_lang_default() {
    return hope_lang_normalize(Option::get('language') ?: 'zh-cn') ?: 'zh-cn';
}

function &hope_lang_state() {
    static $state = [
        'locale'  => '',
        'strings' => [],
        'loaded'  => false,
    ];
    return $state;
}

function hope_lang_init() {
    if (!hope_lang_is_admin()) {
        return;
    }
    $state = &hope_lang_state();
    if ($state['loaded']) {
        return;
    }
    hope_lang_load(hope_lang_default());
}

function hope_lang_file($locale) {
    return HOPE_ROOT . 'content/lang/' . $locale . '.php';
}

function hope_lang_load($locale) {
    $state = &hope_lang_state();
    $locale = hope_lang_normalize($locale) ?: 'zh-cn';
    $state['locale'] = $locale;
    $state['strings'] = [];

    $file = hope_lang_file($locale);
    if (is_file($file)) {
        $pack = include $file;
        if (is_array($pack)) {
            $state['strings'] = $pack;
        }
    }

    if (empty($state['strings'])) {
        $fallback = hope_lang_file(in_array($locale, ['zh-cn', 'zh-tw'], true) ? 'zh-cn' : 'en');
        if (is_file($fallback)) {
            $pack = include $fallback;
            if (is_array($pack)) {
                $state['strings'] = $pack;
            }
        }
    }

    $state['loaded'] = true;
    doAction('lang_loaded', $locale);
}

function hope_lang_current() {
    $state = hope_lang_state();
    return $state['locale'] !== '' ? $state['locale'] : hope_lang_default();
}

function hope_lang_html_attr() {
    $locale = hope_lang_current();
    $registry = hope_lang_registry();
    return $registry[$locale]['html'] ?? $locale;
}

function hope_lang_is_rtl() {
    if (!hope_lang_is_admin()) {
        return false;
    }
    $locale = hope_lang_current();
    $registry = hope_lang_registry();
    return !empty($registry[$locale]['rtl']);
}

function hope_lang($key, $replace = []) {
    if (!hope_lang_is_admin()) {
        return $key;
    }
    $state = hope_lang_state();
    if (!$state['loaded']) {
        hope_lang_init();
    }
    $text = $state['strings'][$key] ?? $key;
    if (!empty($replace) && is_array($replace)) {
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string)$v, $text);
        }
    }
    return $text;
}

function __($key, $replace = []) {
    return hope_lang($key, $replace);
}

function hope_lang_settings_for_admin() {
    return [
        'registry' => hope_lang_registry(),
        'language' => hope_lang_default(),
    ];
}

function hope_lang_save_settings($input) {
    $registry = hope_lang_registry();
    $language = hope_lang_normalize($input['language'] ?? '');
    if ($language === '' || !isset($registry[$language])) {
        $language = 'zh-cn';
    }
    Option::updateOption('language', $language);
}

function hope_lang_js_strings() {
    return [
        'save_success' => __('admin.msg.save_success'),
        'confirm'      => __('admin.msg.confirm'),
        'ok'           => __('admin.msg.ok'),
    ];
}
