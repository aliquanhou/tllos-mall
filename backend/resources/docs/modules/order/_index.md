# 订单管理模块总览

## 模块概述
订单管理负责商城所有交易订单的全生命周期管理，包括订单创建、支付、发货、收货、评价、售后、退款等。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| orders | 订单主表 | id, order_no, user_id, merchant_id, total_amount, status, pay_status, shipping_status |
| order_items | 订单商品 | id, order_id, product_id, sku_id, quantity, price |
| after_sales | 售后申请 | id, order_id, user_id, type, reason, status |
| refunds | 退款记录 | id, order_id, amount, reason, status |

## 订单状态机
```
待付款(pending) -> 已付款(paid) -> 已发货(shipped) -> 已收货(received) -> 已完成(completed)
     |                |                |
     v                v                v
  已取消(cancelled)  退款中(refunding) 售后中(after_sale)
```

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 订单列表 | list.md | 订单查询、详情、状态操作 |
| 售后管理 | after-sale.md | 售后申请审核 |
| 退款管理 | refund.md | 退款记录查询 |
