# 营销管理模块总览

## 模块概述
营销管理提供商城的各种营销工具，包括优惠券、会员折扣、限时秒杀、拼团活动、砍价等。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| coupons | 优惠券 | id, name, type, discount_amount, min_amount, total_count, used_count, start_time, end_time, status |
| coupon_records | 优惠券领取记录 | id, coupon_id, user_id, order_id, status, used_time |
| seckills | 秒杀活动 | id, name, product_id, seckill_price, start_time, end_time, status |
| groups | 拼团活动 | id, name, product_id, group_price, group_num, status |
| group_buys | 拼团开团 | id, group_id, leader_id, status, expire_time |

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 优惠券 | coupon.md | 优惠券CRUD、发放记录 |
| 会员折扣 | discount.md | 会员折扣规则 |
| 限时秒杀 | seckill.md | 秒杀活动 |
| 拼团活动 | group.md | 拼团活动 |
