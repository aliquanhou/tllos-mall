# TLLOS Mall - 开源商城系统

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20.svg)](https://laravel.com/)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D.svg)](https://vuejs.org/)

## 项目简介

TLLOS Mall 是一套基于 Laravel 11 + Vue 3 的全新架构开源商城系统，支持多商户、分销、营销活动等完整电商功能。

## 技术栈

- **后端**: Laravel 11 + PHP 8.2 + MySQL + Redis
- **管理端**: Vue 3 + Vite + Element Plus + Pinia
- **商家端**: Vue 3 + Vite + Element Plus
- **用户端**: H5 响应式
- **API**: RESTful API + Sanctum 认证

## 功能模块

- 商品管理（分类/SKU/评价）
- 订单管理（下单/支付/发货/退款/售后）
- 商家管理（入驻/审核/结算）
- 用户管理（等级/余额/积分/优惠券）
- 分销管理（分销商/等级/订单/商品）
- 营销管理（优惠券/秒杀/拼团/会员折扣）
- 财务管理（收款/退款/提现/结算）
- 系统管理（配置/权限/日志/定时任务）

## 目录结构

```
tllos/
├── backend/          # Laravel后端
├── admin/            # 管理端Vue
├── merchant/         # 商家端Vue
├── h5/               # 用户端H5
├── .github/workflows/ # CI/CD配置
└── README.md
```

## 快速开始

### 环境要求
- PHP >= 8.2
- MySQL >= 5.7
- Node.js >= 18
- Composer

### 后端部署
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### 前端构建
```bash
# 管理端
cd admin && npm install && npm run build

# 商家端
cd merchant && npm install && npm run build

# H5端
cd h5 && npm install && npm run build
```

## 访问地址

- 管理后台: `/admin/`
- 商家后台: `/merchant/`
- 用户商城: `/h5/`

## License

Apache License 2.0 - 详见 [LICENSE](LICENSE) 文件
