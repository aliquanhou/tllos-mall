# 商家管理模块总览

## 模块概述
多商户商城的商家管理模块，负责商家入驻、审核、店铺信息、等级、分类、账户日志等管理。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| shops | 商家表 | id, name, user_id, level_id, category_id, status, audit_status |
| merchant_levels | 商家等级 | id, name, commission_rate, status |
| merchant_categories | 商家分类 | id, name, sort, status |
| merchant_account_logs | 账户日志 | id, shop_id, type, amount, balance, remark |

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 商家列表 | list.md | 商家管理、状态控制 |
| 入驻审核 | audit.md | 入驻申请审核 |
