DROP TABLE IF EXISTS {db_prefix}article;
CREATE TABLE {db_prefix}article (
  gid int(11) unsigned NOT NULL auto_increment COMMENT '文章表',
  title varchar(255) NOT NULL default '' COMMENT '文章标题',
  date bigint(20) NOT NULL COMMENT '发布时间',
  content longtext NOT NULL COMMENT '文章内容',
  excerpt longtext NOT NULL COMMENT '文章摘要',
  cover varchar(255) NOT NULL DEFAULT '' COMMENT '封面图',
  alias varchar(255) NOT NULL DEFAULT '' COMMENT '文章别名',
  author int(11) NOT NULL default '1' COMMENT '作者UID',
  sortid int(11) NOT NULL default '-1' COMMENT '分类ID',
  type varchar(20) NOT NULL default 'blog' COMMENT '文章OR页面',
  views int(11) unsigned NOT NULL default '0' COMMENT '阅读量',
  comnum int(11) unsigned NOT NULL default '0' COMMENT '评论数量',
  top enum('n','y') NOT NULL default 'n' COMMENT '置顶',
  sortop enum('n','y') NOT NULL default 'n' COMMENT '分类置顶',
  hide enum('n','y') NOT NULL default 'n' COMMENT '草稿y',
  checked enum('n','y') NOT NULL default 'y' COMMENT '文章是否审核',
  allow_remark enum('n','y') NOT NULL default 'y' COMMENT '允许评论y',
  password varchar(255) NOT NULL default '' COMMENT '访问密码',
  template varchar(255) NOT NULL default '' COMMENT '模板',
  tags text COMMENT '标签',
  link varchar(255) NOT NULL DEFAULT '' COMMENT '文章跳转链接',
  fields text COMMENT '拓展字段',
  feedback varchar(2048) NOT NULL DEFAULT '' COMMENT 'audit feedback',
  PRIMARY KEY (gid),
  KEY author (author),
  KEY views (views),
  KEY comnum (comnum),
  KEY sortid (sortid),
  KEY top (top,date),
  KEY date (date)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO {db_prefix}article (gid,title,date,content,excerpt,author,views,comnum,top,sortop,hide,allow_remark,password) VALUES (1, '欢迎使用 Hope CMS', {demo_time}, '恭喜您成功安装了 Hope CMS，这是系统自动生成的演示文章。编辑或者删除它，然后开始您的创作吧！', '', 1, 0, 1, 'n', 'n', 'n', 'y', '');

DROP TABLE IF EXISTS {db_prefix}upload;
CREATE TABLE {db_prefix}upload (
  aid int(11) unsigned NOT NULL auto_increment COMMENT '资源文件表',
  alias varchar(64) NOT NULL default '' COMMENT '资源别名',
  author int(11) unsigned NOT NULL default '1' COMMENT '作者UID',
  sortid int(11) NOT NULL default '0' COMMENT '分类ID',
  filename varchar(255) NOT NULL default '' COMMENT '文件名',
  filesize int(11) NOT NULL default '0' COMMENT '文件大小',
  filepath varchar(255) NOT NULL default '' COMMENT '文件路径',
  addtime bigint(20) NOT NULL default '0' COMMENT '创建时间',
  width int(11) NOT NULL default '0' COMMENT '图片宽度',
  height int(11) NOT NULL default '0' COMMENT '图片高度',
  mimetype varchar(40) NOT NULL default '' COMMENT '文件mime类型',
  download_count bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '下载次数',
  PRIMARY KEY (aid),
  KEY thum_uid (author),
  KEY addtime (addtime)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}upload_sort;
CREATE TABLE {db_prefix}upload_sort (
  id int(11) unsigned NOT NULL auto_increment COMMENT '资源分类表',
  sortname varchar(255) NOT NULL default '' COMMENT '分类名',
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}comment;
CREATE TABLE {db_prefix}comment (
  cid int(11) unsigned NOT NULL auto_increment COMMENT '评论表',
  gid int(11) unsigned NOT NULL default '0' COMMENT '文章ID',
  pid int(11) unsigned NOT NULL default '0' COMMENT '父级评论ID',
  top enum('n','y') NOT NULL default 'n' COMMENT '置顶',
  poster varchar(20) NOT NULL default '' COMMENT '发布人昵称',
  uid int(11) NOT NULL default '0' COMMENT '发布人UID',
  comment text NOT NULL COMMENT '评论内容',
  mail varchar(60) NOT NULL default '' COMMENT 'email',
  url varchar(75) NOT NULL default '' COMMENT 'homepage',
  ip varchar(128) NOT NULL default '' COMMENT 'ip address',
  agent varchar(512) NOT NULL default '' COMMENT 'user agent',
  hide enum('n','y') NOT NULL default 'n' COMMENT '是否审核',
  date bigint(20) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (cid),
  KEY gid (gid),
  KEY date (date),
  KEY hide (hide)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO {db_prefix}comment (gid, date, poster, comment) VALUES (1, {demo_time}, 'Hope CMS', '欢迎使用 Hope CMS！');

DROP TABLE IF EXISTS {db_prefix}options;
CREATE TABLE {db_prefix}options (
  option_id INT(11) UNSIGNED NOT NULL auto_increment COMMENT '站点配置信息表',
  option_name VARCHAR(75) NOT NULL COMMENT '配置项',
  option_value LONGTEXT NOT NULL COMMENT '配置项值',
  PRIMARY KEY (option_id),
  UNIQUE KEY option_name_uindex (option_name)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO {db_prefix}options (option_name, option_value) VALUES
('sitename','{sitename}'),
('siteurl','{site_url}'),
('detect_url','y'),
('siteinfo','{siteinfo}'),
('footer_info','powered by <a href="http://www.hopecms.cn">Hope CMS</a>'),
('timezone','Asia/Shanghai'),
('language','zh-cn'),
('debug','n'),
('close','n'),
('site_title','{site_title}'),
('log_title_style','0'),
('site_key','{sitename}'),
('site_description','{site_description}'),
('admin_lognum','15'),
('index_lognum','15'),
('admin_perpage_num','6'),
('search_date','3'),
('is_signup','y'),
('ischkarticle','y'),
('login_code','n'),
('email_code','n'),
('article_uneditable','n'),
('writer_permissions','article,upload,comment'),
('editor_permissions','article,page,comment,upload,twitter,category,tag'),
('posts_per_day','10'),
('iscomment','y'),
('ischkcomment','y'),
('comment_code','n'),
('login_comment','n'),
('comment_needchinese','y'),
('comment_order','newer'),
('comment_interval',60),
('comment_pnum','10'),
('comment_paging','y'),
('forbid_user_upload','n'),
('att_type','rar,zip,gif,jpg,jpeg,png,webp,txt,pdf,docx,doc,xls,xlsx,mp4,mp3'),
('att_maxsize','1024000'),
('smtp_mail',''),
('smtp_pw',''),
('smtp_from_name',''),
('smtp_server',''),
('smtp_port',''),
('mail_notice_comment','n'),
('mail_notice_post','n'),
('linkmode','0'),
('rule_index','{%host%}?page={%page%}'),
('rule_page','{%host%}?id={%id%}'),
('rule_article','{%host%}?article={%id%}'),
('rule_category','{%host%}?category={%id%}&page={%page%}'),
('rule_tag','{%host%}?tags={%tag%}&page={%page%}'),
('rule_record','{%host%}?date={%date%}&page={%page%}'),
('is_openapi','n'),
('is_limitapi','n'),
('apikey','{apikey}'),
('limit_num','3'),
('payment',''),
('nonce_theme','default'),
('active_plugins','{def_plugin}'),
('apply_bind_key',''),
('apply_bind_username',''),
('apply_bind_uid',''),
('apply_bind_type',''),
('apply_store_url','');

DROP TABLE IF EXISTS {db_prefix}link;
CREATE TABLE {db_prefix}link (
  id int(11) unsigned NOT NULL auto_increment COMMENT '链接表',
  sitename varchar(255) NOT NULL default '' COMMENT '名称',
  siteurl varchar(255) NOT NULL default '' COMMENT '地址',
  icon varchar(512) NOT NULL default '' COMMENT '图标URL',
  description varchar(512) NOT NULL default '' COMMENT '备注信息',
  hide enum('n','y') NOT NULL default 'n' COMMENT '是否隐藏',
  taxis int(11) unsigned NOT NULL default '0' COMMENT '排序序号',
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO {db_prefix}link (id, sitename, siteurl, icon, description, taxis) VALUES (1, 'Hope CMS', 'http://www.hopecms.cn', '', 'Hope CMS 官方网站', 0);

DROP TABLE IF EXISTS {db_prefix}menu;
CREATE TABLE {db_prefix}menu (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单表',
  menuname varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单名称',
  url varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '菜单地址',
  target enum('n','y') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n' COMMENT '在新窗口打开',
  hide enum('n','y') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n' COMMENT '是否隐藏',
  taxis int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '排序序号',
  pid int(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '父级ID',
  home enum('n','y') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'y' COMMENT '是否系统默认菜单，如首页',
  type tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '自定义(0) 文章(1) 页面(2) 分类(3) 标签(4) 侧边栏(5)',
  type_id varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '类型对应ID',
  fields longtext COLLATE utf8mb4_unicode_ci COMMENT '字段',
  PRIMARY KEY (id)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO {db_prefix}menu (id, menuname, url, pid, home, type) VALUES
(1, '主菜单', '', 0, 'n', 0),
(2, '首页', '', 1, 'y', 0),
(3, '后台', 'admin.php', 1, 'y', 0);

DROP TABLE IF EXISTS {db_prefix}tag;
CREATE TABLE {db_prefix}tag (
  tid int(11) unsigned NOT NULL auto_increment COMMENT '标签表',
  tagname varchar(60) NOT NULL default '' COMMENT '标签名',
  gid text NOT NULL COMMENT '文章ID',
  title VARCHAR(2048) NOT NULL DEFAULT '' COMMENT '标题',
  keywords VARCHAR(2048) NOT NULL DEFAULT '' COMMENT '关键词',
  description varchar(512) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (tid),
  KEY tagname (tagname)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}sort;
CREATE TABLE {db_prefix}sort (
  sid int(11) unsigned NOT NULL auto_increment COMMENT '分类表',
  sortname varchar(255) NOT NULL default '' COMMENT '分类名',
  alias VARCHAR(255) NOT NULL DEFAULT '' COMMENT '别名',
  taxis int(11) unsigned NOT NULL default '0' COMMENT '排序序号',
  pid int(11) unsigned NOT NULL default '0' COMMENT '父分类ID',
  keywords VARCHAR(2048) NOT NULL DEFAULT '' COMMENT '关键词',
  description text NOT NULL COMMENT '分类描述',
  style varchar(255) NOT NULL default '' COMMENT '分类样式',
  logstyle varchar(255) NOT NULL default '' COMMENT '文章样式',
  sortimg varchar(512) NOT NULL default '' COMMENT '分类图像',
  PRIMARY KEY (sid)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}user;
CREATE TABLE {db_prefix}user (
  uid int(11) unsigned NOT NULL auto_increment COMMENT '用户表',
  username varchar(32) NOT NULL default '' COMMENT '用户名',
  password varchar(64) NOT NULL default '' COMMENT '用户密码',
  nickname varchar(20) NOT NULL default '' COMMENT '昵称',
  role varchar(60) NOT NULL default '' COMMENT '用户组',
  ischeck enum('n','y') NOT NULL default 'n' COMMENT '内容是否需要管理员审核',
  photo varchar(255) NOT NULL default '' COMMENT '头像',
  email varchar(60) NOT NULL default '' COMMENT '邮箱',
  qq varchar(20) NOT NULL DEFAULT '' COMMENT 'QQ号',
  description varchar(255) NOT NULL default '' COMMENT '备注',
  ip varchar(128) NOT NULL default '' COMMENT 'ip地址',
  state tinyint NOT NULL DEFAULT '0' COMMENT '用户状态 0正常 1禁用',
  credits int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户积分',
  balance decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '账户余额',
  invite_code varchar(16) NOT NULL DEFAULT '' COMMENT '邀请码',
  invited_by int(11) unsigned NOT NULL DEFAULT '0' COMMENT '邀请人UID',
  homepage varchar(255) NOT NULL DEFAULT '' COMMENT '个人主页',
  create_time int(11) NOT NULL COMMENT '创建时间',
  update_time int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (uid),
  KEY username (username),
  KEY email (email)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO {db_prefix}user (uid, username, email, password, nickname, role, create_time, update_time) VALUES (1, '{username}', '{email}', '{password_hash}', '{nickname}', 'admin', {user_time}, {user_time});

DROP TABLE IF EXISTS {db_prefix}user_recharge;
CREATE TABLE {db_prefix}user_recharge (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  amount decimal(10,2) NOT NULL,
  status tinyint(1) NOT NULL DEFAULT '1',
  create_time int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_user_id (user_id)
) COMMENT='用户充值记录' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}user_invite_reward;
CREATE TABLE {db_prefix}user_invite_reward (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL COMMENT '获奖用户',
  from_user_id int(11) NOT NULL DEFAULT '0',
  amount decimal(10,2) NOT NULL,
  type varchar(20) NOT NULL DEFAULT 'invite',
  create_time int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_user_id (user_id)
) COMMENT='邀请奖励记录' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}twitter;
CREATE TABLE {db_prefix}twitter (
  id INT NOT NULL AUTO_INCREMENT COMMENT '微语笔记表',
  content text NOT NULL COMMENT '微语内容',
  img varchar(255) DEFAULT NULL COMMENT '图片',
  author int(11) NOT NULL default '1' COMMENT '作者UID',
  date bigint(20) NOT NULL COMMENT '创建时间',
  replynum int(11) unsigned NOT NULL default '0' COMMENT '回复数量',
  private enum('n','y') NOT NULL default 'n' COMMENT '是否私密',
  PRIMARY KEY (id),
  KEY author (author)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS {db_prefix}storage;
CREATE TABLE {db_prefix}storage (
  sid int(8) NOT NULL AUTO_INCREMENT COMMENT '对象存储表',
  plugin varchar(32) NOT NULL COMMENT '插件名',
  name varchar(32) NOT NULL COMMENT '对象名',
  type varchar(8) NOT NULL COMMENT '对象数据类型',
  value text NOT NULL COMMENT '对象值',
  createdate int(11) NOT NULL COMMENT '创建时间',
  lastupdate int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (sid),
  UNIQUE KEY plugin (plugin,name)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
