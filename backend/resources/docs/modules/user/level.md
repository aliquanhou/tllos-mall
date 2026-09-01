# 用户等级

## 页面概述
用户等级管理用于设置会员等级规则，包括等级名称、折扣率、升级条件等。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/user-center/levels | 等级列表 |
| POST | /api/v1/admin/user-center/levels | 新增等级 |
| PUT | /api/v1/admin/user-center/levels/{id} | 编辑等级 |
| DELETE | /api/v1/admin/user-center/levels/{id} | 删除等级 |

## 验收清单
- [x] 等级列表正常显示
- [x] 新增/编辑/删除正常
