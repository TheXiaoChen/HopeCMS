<?php
/**
 * Hope CMS 钩子挂载点常量
 *
 * 插件注册：addAction(HopeHooks::INDEX_HEAD, 'my_func');
 * 系统触发：doAction(HopeHooks::INDEX_HEAD);
 *
 * @see addAction() doAction() doOnceAction() doMultiAction()
 */
final class HopeHooks
{
    /** 插件与主题加载完成后（base.php 末尾） */
    const INIT = 'init';

    /** 前台 <head> 内 */
    const INDEX_HEAD = 'index_head';
    /** 前台 <body> 开始后 */
    const INDEX_BODY_START = 'index_body_start';
    /** 前台侧边栏 widgets 之前 */
    const INDEX_SIDEBAR = 'index_sidebar';
    /** 前台页脚脚本前 */
    const INDEX_FOOTER = 'index_footer';
    /** 前台 </body> 前 */
    const INDEX_BODY_END = 'index_body_end';
    /** 404 页面 */
    const PAGE_NOT_FOUND = 'page_not_found';
    /** 路由分发前（model, method, params） */
    const ROUTE_DISPATCH = 'route_dispatch';

    /** 文章页渲染前（logid, logData 数组） */
    const LOG_VIEW = 'log_view';

    /** 文章正文输出前过滤（doMultiAction） */
    const ARTICLE_CONTENT_ECHO = 'article_content_echo';
    /** 文章页相关推荐 / 扩展区 */
    const LOG_RELATED = 'log_related';
    /** 文章外链跳转前 */
    const LOG_DIRECT_LINK = 'log_direct_link';
    /** 评论表单区 */
    const COMMENT_FORM = 'comment_form';
    /** 评论提交前 */
    const COMMENT_POST = 'comment_post';
    /** 评论保存后 */
    const COMMENT_SAVED = 'comment_saved';
    /** 评论回复后（后台） */
    const COMMENT_REPLY = 'comment_reply';

    /** 用户中心侧栏菜单扩展 */
    const USER_MENU = 'user_menu';
    /** 个人中心 API 分发（doOnceAction，插件可接管 apply 等 action） */
    const USER_CENTER_API = 'user_center_api';

    /** 后台 <head> 内 */
    const ADM_HEAD = 'adm_head';
    /** 后台首页顶部 */
    const ADM_MAIN_TOP = 'adm_main_top';
    /** 后台首页底部 */
    const ADM_MAIN_BOTTOM = 'adm_main_bottom';
    /** 后台侧栏菜单项（应用中心前） */
    const ADM_MENU = 'adm_menu';
    /** 后台侧栏菜单扩展（菜单列表末尾） */
    const ADM_MENU_EXT = 'adm_menu_ext';
    /** 后台页脚 */
    const ADM_FOOTER = 'adm_footer';
    /** 后台写文章/页面编辑器工具栏 */
    const ADM_WRITELOG_BAR = 'adm_writelog_bar';
    /** 后台评论列表扩展 */
    const ADM_COMMENT_DISPLAY = 'adm_comment_display';
    /** 后台链接列表扩展 */
    const ADM_LINK_DISPLAY = 'adm_link_display';
    /** 后台用户列表行扩展（$user） */
    const ADM_USER_DISPLAY = 'adm_user_display';
    /** 后台用户编辑表单扩展 */
    const ADM_USER_FORM = 'adm_user_form';

    /** 登录页 head */
    const LOGIN_HEAD = 'login_head';
    /** 登录页表单扩展 */
    const LOGIN_EXT = 'login_ext';
    /** 登录成功 */
    const LOGIN_SUCCESS = 'login_success';
    /** 注册页表单扩展 */
    const SIGNUP_EXT = 'signup_ext';

    /** 文章/页面保存后 */
    const SAVE_LOG = 'save_log';
    /** 文章/页面删除后 */
    const DEL_LOG = 'del_log';
    /** 分类保存后 */
    const SAVE_SORT = 'save_sort';
    /** 分类删除后 */
    const DEL_SORT = 'del_sort';
    /** 系统设置保存后 */
    const SAVE_SETTING = 'save_setting';
    /** 后台新增用户后（uid, userData） */
    const ADD_USER = 'add_user';
    /** 后台更新用户后（uid, userData） */
    const UPDATE_USER = 'update_user';
    /** 后台删除用户后（uid） */
    const DEL_USER = 'del_user';
    /** 后台禁用用户后（uid） */
    const FORBID_USER = 'forbid_user';
    /** 后台解禁用户后（uid） */
    const UNFORBID_USER = 'unforbid_user';
    /** 微语/笔记发布 */
    const POST_NOTE = 'post_note';
    /** 评论发布成功（前台） */
    const POST_COMMENT = 'post_comment';

    /** 附件上传（doOnceAction 可接管） */
    const UPLOAD_MEDIA = 'upload_media';
    /** 附件删除 */
    const DEL_MEDIA = 'del_media';
    /** 编辑器附件上传 */
    const ATTACH_UPLOAD = 'attach_upload';
    /** 资源下载 */
    const DOWNLOAD_RESOURCE = 'download_resource';

    /** 插件启用 */
    const PLUGIN_ACTIVE = 'plugin_active';
    /** 插件停用 */
    const PLUGIN_INACTIVE = 'plugin_inactive';
    /** 插件删除 */
    const PLUGIN_DELETED = 'plugin_deleted';

    /** 路由表构建后扩展（doMultiAction，可追加路由） */
    const ROUTING_REGISTER = 'routing_register';

    /** 语言包加载后 */
    const LANG_LOADED = 'lang_loaded';
    /** Gravatar 地址（doOnceAction） */
    const GET_GRAVATAR = 'get_Gravatar';
}
