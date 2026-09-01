# 用户管理模块总览

## 模块概述
用户管理负责商城注册用户的全生命周期管理，包括用户信息、等级、积分、余额、地址、收藏、账户日志等。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| users | 用户表 | id, username, mobile, email, avatar, level_id, balance, points, status |
| user_levels | 用户等级 | id, name, discount, min_points, status |
| user_points | 积分日志 | id, user_id, type, points, balance, remark |
| user_addresses | 收货地址 | id, user_id, name, phone, province, city, district, detail, is_default |
| user_favorites | 收藏 | id, user_id, product_id |

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 用户列表 | list.md | 用户管理、状态控制 |
| 用户等级 | level.md | 等级规则管理 |
