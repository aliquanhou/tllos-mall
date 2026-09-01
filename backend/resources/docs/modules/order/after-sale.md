# 售后管理

## 页面概述
售后管理用于处理用户的退货、换货、维修申请，支持审核、拒绝、完成等操作。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/after-sale | 售后列表 |
| GET | /api/v1/admin/after-sale/{id} | 售后详情 |
| PUT | /api/v1/admin/after-sale/{id}/approve | 审核通过 |
| PUT | /api/v1/admin/after-sale/{id}/reject | 审核拒绝 |

## 验收清单
- [x] 售后列表正常显示
- [x] 审核通过/拒绝功能
