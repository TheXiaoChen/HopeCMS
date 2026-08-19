<?php
/**
 * Hope CMS(希望CMS) Service：User
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class User {

    const ROLE_ADMIN = 'admin';     // 管理员、创始人
    const ROLE_WRITER = 'writer';   // 注册用户
    const ROLE_VISITOR = 'visitor'; // 游客
    const ROLE_EDITOR = 'editor';   // 内容编辑

    static function isFounder($role = ROLE, $uid = UID) {
        $uid = (int)$uid;
        return $role == self::ROLE_ADMIN && $uid === 1;
    }

    static function isAdmin($role = ROLE) {
        return $role == self::ROLE_ADMIN;
    }

    static function isVisitor($role = ROLE) {
        return $role == self::ROLE_VISITOR;
    }

    static function isEditor($role = ROLE) {
        return $role == self::ROLE_EDITOR;
    }

    static function isWriter($role = ROLE) {
        return $role == self::ROLE_WRITER;
    }

    static function haveEditPermission($role = ROLE) {
        if (self::isAdmin($role)) {
            return true;
        }
        if (self::isEditor($role)) {
            return true;
        }
        return false;
    }

    /** 后台角色可配置的权限项 */
    static function rolePermissionRegistry() {
        return [
            'article'  => '文章管理',
            'page'     => '页面管理',
            'comment'  => '评论管理',
            'upload'   => '资源管理',
            'twitter'  => '微语',
            'category' => '分类管理',
            'tag'      => '标签管理',
            'link'     => '链接管理',
        ];
    }

    /** 权限项对应的后台 act */
    static function rolePermissionActs() {
        return [
            'article'  => ['article', 'article_edit'],
            'page'     => ['page', 'page_edit'],
            'comment'  => ['comment'],
            'upload'   => ['upload'],
            'twitter'  => ['twitter'],
            'category' => ['category', 'category_edit'],
            'tag'      => ['tag'],
            'link'     => ['link', 'link_edit'],
        ];
    }

    static function editorPermissionRegistry() {
        return self::rolePermissionRegistry();
    }

    static function editorPermissionActs() {
        return self::rolePermissionActs();
    }

    /** 注册用户默认权限 */
    static function writerPermissionDefaults() {
        return ['article', 'upload', 'comment'];
    }

    /** 内容编辑默认权限 */
    static function editorPermissionDefaults() {
        return ['article', 'page', 'comment', 'upload', 'twitter', 'category', 'tag'];
    }

    static function parseRolePermissions($option_key, array $defaults) {
        $registry = self::rolePermissionRegistry();
        $raw = trim((string)Option::get($option_key));
        if ($raw === '') {
            return $defaults;
        }
        $selected = array_filter(array_map('trim', explode(',', $raw)));
        $valid = array_values(array_intersect($selected, array_keys($registry)));
        return $valid ?: $defaults;
    }

    static function buildAllowedActs(array $permission_keys) {
        $acts = ['index'];
        $map = self::rolePermissionActs();
        foreach ($permission_keys as $key) {
            if (isset($map[$key])) {
                $acts = array_merge($acts, $map[$key]);
            }
        }
        return array_values(array_unique($acts));
    }

    static function normalizeRolePermissions($input, array $defaults) {
        $registry = self::rolePermissionRegistry();
        if (!is_array($input)) {
            return implode(',', $defaults);
        }
        $selected = [];
        foreach ($input as $key) {
            $key = trim((string)$key);
            if (isset($registry[$key])) {
                $selected[$key] = $key;
            }
        }
        if (!$selected) {
            return implode(',', $defaults);
        }
        return implode(',', array_values($selected));
    }

    /** 读取已启用的注册用户权限项 */
    static function getWriterPermissions() {
        return self::parseRolePermissions('writer_permissions', self::writerPermissionDefaults());
    }

    /** 注册用户允许访问的后台 act 列表 */
    static function getWriterAllowedActs() {
        return self::buildAllowedActs(self::getWriterPermissions());
    }

    /** 保存设置时规范化注册用户权限 */
    static function normalizeWriterPermissions($input) {
        return self::normalizeRolePermissions($input, self::writerPermissionDefaults());
    }

    /** 读取已启用的内容编辑权限项 */
    static function getEditorPermissions() {
        return self::parseRolePermissions('editor_permissions', self::editorPermissionDefaults());
    }

    /** 内容编辑允许访问的后台 act 列表 */
    static function getEditorAllowedActs() {
        return self::buildAllowedActs(self::getEditorPermissions());
    }

    /** 保存设置时规范化内容编辑权限 */
    static function normalizeEditorPermissions($input) {
        return self::normalizeRolePermissions($input, self::editorPermissionDefaults());
    }

    static function getRoleName($role, $uid = 0) {
        $role_name = '';
        switch ($role) {
            case self::ROLE_ADMIN:
                $role_name = $uid == 1 ? '创始人' : '管理员';
                break;
            case self::ROLE_EDITOR:
                $role_name = '内容编辑';
                break;
            case self::ROLE_WRITER:
                $role_name = '注册用户';
                break;
            case self::ROLE_VISITOR:
                $role_name = '游客';
                break;
        }
        return $role_name;
    }

    static function checkLoginCode($login_code) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $session_code = $_SESSION['code'] ?? '';
        unset($_SESSION['code']);
        if ((!$login_code || $login_code !== $session_code) && Option::get('login_code') === 'y') {
            return false;
        }
        return true;
    }

    static function checkMailCode($mail_code) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $session_code = $_SESSION['mail_code'] ?? '';
        unset($_SESSION['code']);
        if (!$mail_code || $mail_code !== $session_code) {
            return false;
        }
        return true;
    }

    static function checkRolePermission() {
        if (self::isAdmin(ROLE)) {
            return;
        }

        $act = strtolower(Input::getStrVar('act'));
        $resource = $act === '' ? 'index' : $act;

        $writerAllowed = self::getWriterAllowedActs();
        $editorAllowed = self::getEditorAllowedActs();

        if (ROLE === self::ROLE_WRITER && !in_array($resource, $writerAllowed, true)) {
            hpMsg('你所在的用户组无法使用该功能，请联系管理员', './');
        }
        if (ROLE === self::ROLE_EDITOR && !in_array($resource, $editorAllowed, true)) {
            hpMsg('你所在的用户组无法使用该功能，请联系管理员', './');
        }
    }

}
