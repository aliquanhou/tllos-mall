# 用户积分
## 1. 页面概述
管理积分变动日志和积分规则配置。管理员可手动调整积分，系统按规则自动发放（签到/订单/评价/分享）。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-points | PointLogController@index | 积分日志列表（分页+筛选+统计+规则） |
| POST | /api/v1/admin/user-points | PointLogController@store | 手动调整积分（同步更新用户积分） |
| GET | /api/v1/admin/user-points/rules | PointLogController@rules | 积分规则列表 |
## 3. 积分规则
sign每日签到(10分) order订单消费(每元1分) comment商品评价(20分) share分享商品(5分)
## 4. 字段映射（user_point_logs表6字段）
id, user_id, points(正=增加负=扣减), type, description, created_at
## 5. 统计指标
总发放积分、总变动次数、今日发放积分
## 6. 权限控制
Sanctum Token，auth:sanctum
## 7. 验收清单
- [x] 积分日志列表加载
- [x] 用户/类型筛选生效
- [x] 手动调整积分生效（日志+用户积分同步）
- [x] 积分规则列表含4条规则
- [x] 统计数据正确
## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 积分未到账 | 自动发放逻辑未触发 | 检查签到/订单/评价回调 |
