# 提现管理
## 1. 页面概述
处理用户余额提现申请，管理员审核通过后打款。拒绝时自动退回用户余额。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-center/withdraws | UserCenterController@withdraws | 提现列表（分页+筛选+统计） |
| POST | /api/v1/admin/user-center/withdraws/{id}/audit | UserCenterController@withdrawAudit | 审核（1通过2拒绝，拒绝退回余额） |
| POST | /api/v1/admin/user-center/withdraws/{id}/pay | UserCenterController@withdrawPay | 打款（生成pay_no） |
## 3. 状态机
0待审核→1审核通过(待打款)→3已打款；0→2审核拒绝(退回余额)
## 4. 字段映射（withdraws表17字段）
id, user_id, amount, fee, actual_amount, pay_type, pay_account, real_name, status, audit_remark, audit_at, admin_id, paid_at, pay_no, created_at, updated_at
## 5. 操作流程
用户申请→待审核→管理员审核(通过/拒绝)→通过则打款→已打款
## 6. 权限控制
Sanctum Token，auth:sanctum
## 7. 验收清单
- [x] 列表含待审核/已打款统计
- [x] 审核通过(0→1)/拒绝(0→2+退回余额)生效
- [x] 打款(1→3+生成pay_no)生效
- [x] 重复审核被阻止
## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 拒绝后余额未退回 | 审核逻辑未处理 | 确认increment balance |
| 打款失败 | 状态不是1 | 先审核通过 |
