# 分销等级

## 页面概述
分销等级管理用于设置分销商等级规则，包括等级名称、佣金比例、升级条件。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/distribute/levels | 等级列表 |
| POST | /api/v1/admin/distribute/levels | 新增等级 |
| PUT | /api/v1/admin/distribute/levels/{id} | 编辑等级 |
| DELETE | /api/v1/admin/distribute/levels/{id} | 删除等级 |

## 验收清单
- [x] 等级列表正常
- [x] CRUD功能正常
