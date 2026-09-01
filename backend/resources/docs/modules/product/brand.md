# 品牌管理

## 页面概述
维护商城商品品牌信息，包括品牌名称、Logo、排序、状态。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/brands | 品牌列表 |
| POST | /api/v1/admin/brands | 新增品牌 |
| PUT | /api/v1/admin/brands/{id} | 编辑品牌 |
| DELETE | /api/v1/admin/brands/{id} | 删除品牌 |

## 字段映射
| 字段 | 数据库字段 | 类型 | 说明 |
|------|-----------|------|------|
| 品牌名称 | name | string | - |
| Logo | logo | string | Logo URL |
| 排序 | sort | int | - |
| 状态 | status | tinyint | 1启用/0禁用 |

## 验收清单
- [x] 品牌列表正常显示
- [x] 新增/编辑/删除正常
- [x] Logo上传功能
