# 商品分类

## 页面概述
维护商城商品分类体系，支持无限级树形结构、排序、图标、状态控制。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/categories | 分类树形列表 |
| POST | /api/v1/admin/categories | 新增分类 |
| PUT | /api/v1/admin/categories/{id} | 编辑分类 |
| DELETE | /api/v1/admin/categories/{id} | 删除分类 |

## 字段映射
| 字段 | 数据库字段 | 类型 | 说明 |
|------|-----------|------|------|
| 分类名称 | name | string | - |
| 父级分类 | parent_id | int | 0为顶级 |
| 层级 | level | int | 1/2/3 |
| 排序 | sort | int | 越小越靠前 |
| 状态 | status | tinyint | 1启用/0禁用 |

## 验收清单
- [x] 树形结构正常展示
- [x] 新增/编辑/删除正常
- [x] 排序功能正常
