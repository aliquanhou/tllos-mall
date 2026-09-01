# 分销商

## 页面概述
分销商管理用于查看和管理所有分销商，支持审核入驻申请、状态控制、等级调整。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/distribute/agents | 分销商列表 |
| GET | /api/v1/admin/distribute/apply | 入驻申请 |
| PUT | /api/v1/admin/distribute/apply/{id}/audit | 审核申请 |

## 验收清单
- [x] 分销商列表正常
- [x] 入驻申请正常
- [x] 审核功能正常
