# 商家列表

## 页面概述
商家列表页用于管理所有入驻商家，支持查看店铺信息、状态控制、账户余额查看。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/merchants | 商家列表 |
| GET | /api/v1/admin/merchants/{id} | 商家详情 |
| PUT | /api/v1/admin/merchants/{id}/status | 状态切换 |
| GET | /api/v1/admin/merchant-levels | 商家等级 |
| GET | /api/v1/admin/merchant-categories | 商家分类 |
| GET | /api/v1/admin/merchant-account-logs | 账户日志 |

## 验收清单
- [x] 商家列表正常显示
- [x] 商家详情可查看
- [x] 状态切换正常
- [x] 商家等级/分类正常
- [x] 账户日志正常
