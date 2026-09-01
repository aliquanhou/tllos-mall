# 用户列表
## 1. 页面概述
用户列表管理商城所有注册用户，支持搜索、筛选、状态管理、用户详情查看。管理员可查看用户完整画像（订单/地址/收藏/余额/积分/实名）。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/users | AdminUserController@index | 用户列表（分页+筛选+6项统计） |
| GET | /api/v1/admin/users/{id} | AdminUserController@show | 用户详情（含订单/地址/收藏/日志/实名） |
| PUT | /api/v1/admin/users/{id} | AdminUserController@update | 编辑用户（余额/积分/等级/密码） |
| POST | /api/v1/admin/users/{id}/toggle-status | AdminUserController@toggleStatus | 启用/禁用用户 |
## 3. 请求参数
| 参数 | 类型 | 说明 |
|------|------|------|
| page/limit | int | 分页 |
| keyword | string | 搜索昵称/手机号/账号 |
| status | int | 1启用0禁用 |
| level_id | int | 等级筛选 |
| start_time/end_time | string | 注册时间范围 |
## 4. 字段映射（users表18字段）
核心字段：id, username, account, mobile(唯一), nickname, avatar, status, balance, points, level_id, email, password(Hash), created_at
## 5. 操作流程
用户注册→列表展示→查看详情(用户画像)/编辑(余额积分等级)/禁用启用
## 6. 权限控制
Sanctum Token认证，auth:sanctum中间件，无细粒度权限点
## 7. 关联模块
用户等级(level_id)、订单管理(user_id)、收货地址(user_id)、用户收藏(user_id)、账户日志(user_id)、实名认证(user_id)
## 8. 验收清单
- [x] 列表含6项统计（总数/活跃/禁用/今日新增/总余额/总积分）
- [x] 搜索/状态/等级/时间筛选生效
- [x] 用户详情含订单/地址/收藏/余额日志/积分日志/实名
- [x] 编辑用户生效，密码Hash加密
- [x] 启用/禁用切换生效
## 9. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 用户无法登录 | status=0被禁用 | 管理员启用 |
| 余额修改无日志 | 直接update | 建议通过账户日志记录 |
