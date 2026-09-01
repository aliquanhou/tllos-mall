# 用户等级
## 1. 页面概述
用户等级体系根据积分自动或手动升级，不同等级享受不同折扣和权益。当前4个等级：普通/银卡/金卡/钻石。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-center/levels | UserCenterController@levels | 等级列表（含各等级用户数统计） |
| POST | /api/v1/admin/user-center/levels | UserCenterController@levelStore | 新增等级 |
| PUT | /api/v1/admin/user-center/levels/{id} | UserCenterController@levelUpdate | 编辑等级 |
| DELETE | /api/v1/admin/user-center/levels/{id} | UserCenterController@levelDestroy | 删除等级（有用户时阻止） |
## 3. 字段映射（user_levels表11字段）
id, name, level, discount(折扣率100=无折扣), benefits(权益JSON), upgrade_points(升级积分), is_default, points, status, created_at, updated_at
## 4. 操作流程
新用户注册→默认普通会员→积分达标自动升级→享受更高折扣权益
## 5. 权限控制
Sanctum Token，auth:sanctum
## 6. 验收清单
- [x] 4个等级列表，含各等级用户数统计
- [x] 新增/编辑/删除生效
- [x] 删除有用户的等级被阻止
- [x] 默认等级标记正确
## 7. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 删除失败 | 该等级下有用户 | 先迁移用户 |
| 折扣不生效 | 结算未读取等级折扣 | 检查结算逻辑 |
