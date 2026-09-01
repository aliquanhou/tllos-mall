# 实名认证
## 1. 页面概述
管理用户提交的实名信息审核，管理员查看身份证照片，审核通过或拒绝。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-auth | UserAuthController@index | 认证列表（分页+筛选+3项统计） |
| GET | /api/v1/admin/user-auth/{id} | UserAuthController@show | 认证详情 |
| POST | /api/v1/admin/user-auth/{id}/audit | UserAuthController@audit | 审核（1通过2拒绝） |
## 3. 状态机
0待审核→1审核通过；0→2审核拒绝（用户可重新提交）
## 4. 字段映射（user_real_names表12字段）
id, user_id(唯一), real_name, id_card, id_card_front, id_card_back, status, audit_remark, audit_at, admin_id, created_at, updated_at
## 5. 操作流程
用户提交实名信息→待审核→管理员审核(通过/拒绝)
## 6. 权限控制
Sanctum Token，auth:sanctum
## 7. 验收清单
- [x] 列表含待审核/通过/拒绝3项统计
- [x] 详情含用户信息和实名信息
- [x] 审核通过(0→1)/拒绝(0→2)生效
- [x] 重复审核被阻止
## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 用户重复提交 | user_id唯一索引 | 拒绝后重新提交需先删除旧记录 |
