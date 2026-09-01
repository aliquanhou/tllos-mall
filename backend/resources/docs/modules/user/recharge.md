# 充值记录
## 1. 页面概述
管理用户余额充值订单，支持状态/关键词筛选，展示充值总额/笔数/待处理统计。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-center/recharges | UserCenterController@recharges | 充值列表（分页+筛选+统计） |
## 3. 字段映射（user_recharges表12字段）
id, user_id, amount, give_amount(赠送), pay_type, pay_no, status(0待支付1已支付), paid_at, admin_id, remark, created_at, updated_at
## 4. 统计指标
充值总额(status=1 SUM amount)、充值笔数、待处理数、赠送总额
## 5. 权限控制
Sanctum Token，auth:sanctum
## 6. 验收清单
- [x] 列表加载，含3项统计
- [x] 状态筛选/关键词搜索生效
## 7. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 充值未到账 | 支付回调未处理 | 检查回调逻辑 |
