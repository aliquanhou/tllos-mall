# 订单管理

## 1. 模块概述
订单管理是商城交易的核心模块，涵盖订单列表、订单售后、订单日志三个子模块。订单记录用户购买商品的完整信息（商品快照、收货地址、支付信息、物流信息），售后处理用户的退货退款申请，日志追踪订单全生命周期操作。

## 2. 子模块列表
| 子模块 | 文档 | 核心功能 |
|--------|------|----------|
| 订单列表 | list.md | 订单查询、发货、备注、统计 |
| 售后管理 | after-sale.md | 售后审核、退货物流、确认收货、退款 |
| 订单日志 | log.md | 操作历史查询、审计追踪 |

## 3. 数据库表
| 表名 | 字段数 | 说明 |
|------|--------|------|
| orders | 42 | 订单主表（含软删除） |
| order_items | 19 | 订单商品快照 |
| order_logs | 9 | 订单操作日志 |
| order_after_sales | 22 | 售后单 |
| after_sale_logs | 6 | 售后操作日志 |
| order_refunds | 20+ | 退款单 |

## 4. 路由总表
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/orders | 订单列表 |
| GET | /api/v1/admin/orders/{id} | 订单详情 |
| POST | /api/v1/admin/orders/{id}/ship | 订单发货 |
| POST | /api/v1/admin/orders/{id}/remark | 订单备注 |
| GET | /api/v1/admin/after-sale | 售后列表 |
| GET | /api/v1/admin/after-sale/{id} | 售后详情 |
| POST | /api/v1/admin/after-sale/{id}/audit | 售后审核 |
| POST | /api/v1/admin/after-sale/{id}/receive | 确认收货 |
| POST | /api/v1/admin/after-sale/{id}/complete | 强制完成 |
| GET | /api/v1/admin/order-log | 订单日志 |

## 5. 订单状态机
0=待付款 → 1=待发货 → 2=待收货 → 3=已完成
0=待付款 → 4=已取消
2=待收货 → 5=退款中 → 6=已退款

## 6. 权限控制
- 认证：Sanctum Token
- 中间件：auth:sanctum
- 当前无细粒度权限
