# 优惠券

## 页面概述
优惠券管理用于创建和管理商城优惠券，支持满减券、折扣券，查看领取和使用记录。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/coupons | 优惠券列表 |
| POST | /api/v1/admin/coupons | 新增优惠券 |
| PUT | /api/v1/admin/coupons/{id} | 编辑优惠券 |
| DELETE | /api/v1/admin/coupons/{id} | 删除优惠券 |
| GET | /api/v1/admin/coupons/records | 领取记录 |

## 字段映射
| 字段 | 数据库字段 | 类型 | 说明 |
|------|-----------|------|------|
| 优惠券名称 | name | string | - |
| 类型 | type | enum | fixed/discount |
| 优惠金额 | discount_amount | decimal | 满减金额或折扣率 |
| 使用门槛 | min_amount | decimal | 最低消费金额 |
| 发放总量 | total_count | int | - |
| 已领取 | used_count | int | - |
| 有效期 | start_time/end_time | datetime | - |
| 状态 | status | tinyint | 1启用/0禁用 |

## 验收清单
- [x] 优惠券列表正常
- [x] 新增/编辑/删除正常
- [x] 领取记录正常
