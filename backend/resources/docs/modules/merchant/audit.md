# 入驻审核

## 页面概述
入驻审核用于处理商家的入驻申请，支持审核通过、拒绝、查看资质。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/merchants?audit_status=pending | 待审核列表 |
| PUT | /api/v1/admin/merchants/{id}/audit | 审核操作 |

## 验收清单
- [x] 待审核列表正常
- [x] 审核通过/拒绝功能
