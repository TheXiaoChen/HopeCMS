# Hope CMS

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 7.0+" />
  <img src="https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 5.7+" />
  <img src="https://img.shields.io/badge/License-Apache_2.0-blue.svg?style=for-the-badge" alt="Apache 2.0" />
</p>

Hope CMS 是一个基于 PHP 的轻量内容管理系统，适合用于博客、资讯站、企业内容站、知识库与个人站点的快速搭建。它集成了文章管理、分类标签、用户中心、后台管理、主题扩展和插件扩展能力，适合进行快速上线与二次开发。

## 项目简介

该项目采用原生 PHP 构建，结构清晰，便于理解与扩展，适合用于内容型站点和轻量 CMS 场景。系统具备常见运营型内容站所需的基础能力，包括文章发布、分类管理、用户注册、后台配置和主题插件化扩展。

## 核心功能

- 文章与页面管理
- 分类、标签和菜单管理
- 用户注册、登录及个人中心
- 评论与后台运营管理
- 主题切换与前台模板定制
- 插件扩展与模块化开发
- 上传资源管理
- 日志、缓存和站点配置管理
- 多语言资源结构支持

## 技术栈

- PHP 7.0+
- MySQL
- HTML / CSS / JavaScript
- 原生 PHP MVC / 模块化设计
- 前后端资源分离的主题与后台结构

## 项目结构

```text
HopeCMS/
├── admin.php              # 后台入口
├── index.php              # 前台入口
├── install.php            # 安装程序
├── nginx.htaccess        # Nginx 伪静态规则
├── README.md              # 项目说明
├── favicon.ico            # 网站图标
├── content/               # 前台内容资源
│   ├── admin/             # 后台资源文件
│   ├── lang/              # 多语言包
│   ├── plugin/            # 插件目录
│   ├── theme/             # 主题目录
│   ├── upload/            # 上传目录
│   └── ...
├── system/                # 核心程序目录
│   ├── admin/             # 后台控制器
│   ├── app/               # 应用逻辑
│   ├── bootstrap/         # 启动配置
│   ├── install/           # 安装 SQL
│   ├── lib/               # 公共类库
│   ├── options/           # 配置项与字段
│   └── ...
├── logs/                  # 日志目录
└── ...
```

## 运行环境

- PHP 7.0 及以上，推荐 PHP 8.x
- MySQL 5.7+
- 扩展支持：mysqli 或 PDO
- 需有可写权限的目录：
  - system/config.php
  - content/cache
  - content/upload
  - logs

## 快速开始

### 1. 部署项目

将项目放入 Web 服务器目录，例如：

```bash
/var/www/html
```

### 2. 创建数据库

在 MySQL 中创建数据库，并确保数据库用户具备建表和写入权限。

### 3. 访问安装入口

```text
http://localhost/install.php
```

根据提示完成数据库配置与安装步骤。

### 4. 登录后台

安装完成后，访问：

```text
http://localhost/admin.php
```

登录后台后即可完成站点配置、内容发布、主题切换和插件管理。

## 默认入口

- 前台入口：index.php
- 后台入口：admin.php
- 安装入口：install.php

## 适用场景

- 个人博客
- 技术资讯站
- 企业官网内容系统
- 知识库 / 文档站
- 轻量 CMS 二次开发基础项目

## 开发建议

- 推荐使用 Nginx 或 Apache
- 优先在 PHP 8.x 环境中运行
- 启用 mbstring、pdo_mysql、json 等常见扩展
- 为 uploads、logs、cache 等目录配置合理权限

## 说明

这是一套适合二次开发和深度定制的轻量级 CMS。它适合用于快速搭建内容站点，并在此基础上延伸会员系统、SEO 优化、支付模块、AI 助手或内容审核等高级能力。

## 贡献

欢迎提交 Issue 和 Pull Request，帮助项目不断完善。

## 许可证

本项目基于 Apache License 2.0 进行发布。有关详细信息，请参考仓库中的许可证文件。
