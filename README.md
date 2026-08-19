# Hope CMS

**Hope CMS**（希望 CMS）是一款面向中文站长的轻量开源 CMS：博客写作、主题与插件扩展、支付能力开箱可用，适合个人站、工作室站与付费资源站。

[![PHP](https://img.shields.io/badge/PHP-7.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://www.apache.org/licenses/LICENSE-2.0)
[![Version](https://img.shields.io/badge/Version-1.0.2-green.svg)](https://www.hopecms.cn)

---

## 链接

| | 地址 |
| --- | --- |
| **官网** | [https://www.hopecms.cn](https://www.hopecms.cn) |
| **在线演示** | [https://demo.hopecms.cn](https://demo.hopecms.cn) |

> 请仅从 [www.hopecms.cn](https://www.hopecms.cn) 下载安装包，其他来源存在安全风险。

---

## 功能特性

- **Markdown 写作** — Editor.md 编辑器，支持分类 / 标签 / 封面 / 别名 / 置顶 / 阅读密码
- **多用户与权限** — 管理员 / 编辑 / 作者 / 访客，后台模块级权限控制
- **SEO 友好** — 站点 / 文章 / 分类 / 标签 TDK，动态与伪静态链接
- **REST API** — API Key 鉴权与访问限流
- **主题与插件** — 挂载点扩展，示例主题 `default`、示例插件 `tips` 可直接对照学习
- **媒体与下载** — 附件管理、个人媒体库、前台资源下载
- **用户与支付** — 个人中心、余额充值、邀请奖励，微信 / 支付宝等在线支付
- **AI 工作台** — 后台 AI 助手与创作辅助
- **运维工具** — 备份导入、更新缓存、SMTP、多语言后台

## 适用场景

- 个人博客 / 内容站
- 付费下载 / 会员资源站
- 应用分发 / 插件主题商店

## 环境要求

| 项目 | 要求 |
| --- | --- |
| PHP | 7.0+（推荐 8.0+） |
| 数据库 | MySQL 5.6+ / MariaDB 10.3+（`mysqli` 或 `pdo_mysql`） |
| Web 服务器 | Apache / Nginx（IIS 需 URL Rewrite） |
| 建议扩展 | `mbstring`、`json`、`curl`、`gd` 或 `imagick`、`openssl` |

以下目录需可写：`content/upload/`、`content/cache/`、`logs/`

## 快速安装

```bash
# 1. 克隆本仓库，或从官网下载安装包并解压到网站根目录
git clone <repo-url> .

# 2. 确保 content/upload、content/cache、logs 目录可写

# 3. 浏览器访问安装向导
# http://你的域名/install.php
```

安装步骤：

1. 访问 `install.php`，填写数据库信息与管理员账号
2. 安装完成后**删除或重命名** `install.php`
3. 登录后台（默认入口 `admin.php`，建议重命名为不易猜测的文件名）
4. 启用主题与插件，执行 **设置 → 更新缓存**

已有站点升级请勿重跑安装向导，详见 [升级说明](http://www.hopecms.cn/docs.html#upgrade)。

## 目录结构

```
├── admin.php              # 后台入口（建议重命名）
├── install.php            # 安装向导（安装后删除）
├── content/
│   ├── admin/             # 后台静态资源
│   ├── cache/             # 缓存
│   ├── plugin/            # 插件目录
│   ├── theme/             # 主题目录
│   └── upload/            # 上传文件
├── system/
│   ├── admin/             # 后台页面
│   ├── app/               # 控制器 / 模型 / 服务
│   ├── lib/               # 核心库
│   └── install/           # 安装 SQL
└── logs/                  # 运行日志
```

## 内置主题与插件

**主题：** `default`（示例）

**插件：** `tips`（示例）

业务扩展请放在 `content/plugin/` 与 `content/theme/`，避免直接修改 `system/` 核心文件。

## 开发文档

完整文档位于 http://www.hopecms.cn/docs.html：

| 文档 | 说明 |
| --- | --- |
| [intro.md](http://www.hopecms.cn/docs.html#intro) | 项目简介 |
| [install.md](http://www.hopecms.cn/docs.html#install) | 安装指南 |
| [upgrade.md](http://www.hopecms.cn/docs.html#upgrade) | 升级与更新 |
| [dev.md](http://www.hopecms.cn/docs.html#dev) | 开发准备工作 |
| [hooks.md](http://www.hopecms.cn/docs.html#hooks) | 挂载点手册 |
| [plugin.md](http://www.hopecms.cn/docs.html#plugin) | 插件开发指南 |
| [theme.md](http://www.hopecms.cn/docs.html#theme) | 主题开发指南 |
| [database.md](http://www.hopecms.cn/docs.html#database) | 数据库与 SQL |
| [api.md](http://www.hopecms.cn/docs.html#api) | REST API |
| [rewrite.md](http://www.hopecms.cn/docs.html#rewrite) | 伪静态与路由 |

## 许可证

Hope CMS（希望 CMS）核心代码按 [Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0) 发布。部分捆绑的第三方库可能采用各自许可证，以其目录内说明为准。

## 相关链接

- 官网：[https://www.hopecms.cn](https://www.hopecms.cn)
- 演示站：[https://demo.hopecms.cn](https://demo.hopecms.cn)
