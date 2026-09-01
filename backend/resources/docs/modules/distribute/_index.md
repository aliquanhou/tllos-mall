# 分销管理模块总览

## 模块概述
分销管理负责商城分销体系的全流程管理，包括分销商入驻、等级、分润规则、分销订单、分销商品、佣金结算等。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| distributors | 分销商 | id, user_id, level_id, parent_id, status, audit_status |
| distribute_levels | 分销等级 | id, name, commission_rate, status |
| distribute_orders | 分销订单 | id, order_id, distributor_id, commission, status |
| distribute_goods | 分销商品 | id, product_id, commission_rate, status |
| distribution_applies | 入驻申请 | id, user_id, real_name, phone, status |

## 分润模型
```
用户下单 -> 订单支付成功 -> 按分销关系计算佣金 -> 分销商账户增加佣金 -> 订单完成后结算
```

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 分销概览 | overview.md | 数据统计 |
| 分销商 | agents.md | 分销商管理 |
| 分销订单 | orders.md | 分润订单 |
| 分销等级 | levels.md | 等级规则 |
| 分销设置 | settings.md | 规则配置 |
