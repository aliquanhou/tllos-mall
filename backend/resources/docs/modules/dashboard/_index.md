# TLLOS 商城 · 全局架构总纲

## 项目概述
TLLOS商城是基于Laravel 11 + Vue3的全新架构多商户商城系统，独立知识产权（Apache 2.0开源）。支持PC端、H5端、微信小程序、Flutter APP四端适配。

## 技术栈
- **后端**：Laravel 11（PHP 8.2）+ MariaDB 10.11 + Redis + Sanctum认证
- **管理端**：Vue3 + Vite + Element Plus + Pinia + vue-i18n
- **商家端**：Vue3 + Vite + Element Plus
- **H5端**：Vue3 + Vite + Element Plus（自适应）

## 15大模块
| 模块 | 核心表 |
|------|--------|
| 工作台 | - |
| 商品管理 | products, categories, brands, goods_sku |
| 订单管理 | orders, order_items, after_sales, refunds |
| 商家管理 | shops, merchant_levels, merchant_categories |
| 用户管理 | users, user_levels, user_points |
| 分销管理 | distributors, distribute_orders, distribute_levels |
| 营销管理 | coupons, seckills, groups |
| 应用管理 | materials, articles, announcements, notices |
| 装修管理 | decorate_pages, decorate_templates, banners, navigations |
| 财务管理 | finance_income, finance_refund, withdraws, settlements |
| 权限管理 | admin_roles, admin_menus, departments, admins |
| 系统设置 | order_settings, pay_configs, logistics_configs, delivery_types |
| 组织管理 | organizations, jobs |
| 渠道设置 | channel_settings, oa_menus, oa_replies |
| 开发工具 | 代码生成器 |

## 统一账号
- 管理后台：admin / admin123
- 商家后台：admin / admin123
- 用户端H5：13300133002 / 123456

## 可访问入口
- 管理后台：https://mall.tllos.com/admin/
- 商家后台：https://mall.tllos.com/merchant/
- 用户端H5：https://mall.tllos.com/h5/
- GitHub：https://github.com/aliquanhou/tllos-mall
