# 订单列表

## 页面概述
订单列表页用于查询和管理所有订单，支持按订单号、用户、状态、时间范围筛选，可查看订单详情、发货、取消等操作。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/orders | 订单列表（分页） |
| GET | /api/v1/admin/orders/{id} | 订单详情 |
| PUT | /api/v1/admin/orders/{id}/ship | 订单发货 |
| PUT | /api/v1/admin/orders/{id}/cancel | 取消订单 |
| DELETE | /api/v1/admin/orders/{id} | 删除订单 |

## 字段映射
| 字段 | 数据库字段 | 类型 | 说明 |
|------|-----------|------|------|
| 订单号 | order_no | string | 唯一订单编号 |
| 用户 | user_id | int | 关联users |
| 商家 | merchant_id | int | 关联shops |
| 订单金额 | total_amount | decimal | 订单总金额 |
| 订单状态 | status | enum | pending/paid/shipped/received/completed/cancelled |
| 支付状态 | pay_status | enum | unpaid/paid/refunded |
| 发货状态 | shipping_status | enum | unshipped/shipped/received |
| 创建时间 | created_at | datetime | - |

## 验收清单
- [x] 订单列表正常显示
- [x] 订单详情可查看
- [x] 筛选功能正常
- [x] 发货操作正常
- [x] 取消订单正常
