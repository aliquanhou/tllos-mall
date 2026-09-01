# 用户列表

## 页面概述
用户列表页用于管理所有注册用户，支持搜索、筛选、查看详情、状态控制、余额积分调整。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/users | 用户列表 |
| GET | /api/v1/admin/users/{id} | 用户详情 |
| PUT | /api/v1/admin/users/{id}/status | 状态切换 |
| GET | /api/v1/admin/user-center/levels | 用户等级 |
| GET | /api/v1/admin/user-center/recharges | 充值记录 |
| GET | /api/v1/admin/user-center/withdraws | 提现记录 |
| GET | /api/v1/admin/user-center/addresses | 收货地址 |
| GET | /api/v1/admin/user-center/account-logs | 账户日志 |
| GET | /api/v1/admin/user-points | 积分日志 |
| GET | /api/v1/admin/user-favorites | 收藏列表 |

## 验收清单
- [x] 用户列表正常显示
- [x] 用户详情可查看
- [x] 状态切换正常
- [x] 等级/充值/提现/地址/日志正常
