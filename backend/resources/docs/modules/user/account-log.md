# 账户日志
## 1. 页面概述
记录用户余额变动明细，包括充值/消费/退款/提现退回/人工调整。支持按用户/类型筛选，按类型汇总金额。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-center/account-logs | UserCenterController@accountLogs | 日志列表（分页+筛选+按类型统计） |
## 3. 日志类型
1充值 2消费(订单支付) 3退款 4提现退回 5人工调整
## 4. 字段映射（user_balance_logs表9字段）
id, user_id, type, amount, balance_before, balance_after, order_no, remark, created_at
## 5. 权限控制
Sanctum Token，auth:sanctum
## 6. 验收清单
- [x] 日志列表加载
- [x] 用户/类型筛选生效
- [x] 按类型统计金额正确
- [x] 变动前后余额记录正确
## 7. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 余额对不上 | 变动时未记录日志 | 所有余额变动必须写日志 |
