# 装修管理模块总览

## 模块概述
装修管理提供可视化页面装修功能，支持首页、分类页、会员中心的拖拽式装修，轮播图、导航图标、商品推荐等组件。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| decorate_pages | 装修页面 | id, name, type, layout_data, status |
| decorate_templates | 装修模板 | id, name, type, preview, status |
| banners | 轮播图 | id, title, image, link, sort, status |
| navigations | 导航图标 | id, name, icon, link, sort, status |

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 页面装修 | pages.md | 可视化编辑器 |
| 模板管理 | templates.md | 模板列表 |
| 轮播图 | banners.md | 轮播图管理 |
| 导航图标 | navigations.md | 导航图标管理 |
