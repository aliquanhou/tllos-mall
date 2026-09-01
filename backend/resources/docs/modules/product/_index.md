# 商品管理模块总览

## 模块概述
商品管理是TLLOS商城的核心模块，负责商品的全生命周期管理，包括商品信息、分类、品牌、SKU规格、库存、评价等。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| products | 商品主表 | id, name, price, stock, sales, status, category_id, brand_id |
| categories | 商品分类 | id, name, parent_id, level, sort, status |
| brands | 品牌 | id, name, logo, sort, status |
| goods_sku | SKU规格 | id, product_id, sku_no, spec_text, price, stock |
| comments | 商品评价 | id, product_id, user_id, rating, content, status |

## 路由总表
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/products | 商品列表 |
| POST | /api/v1/admin/products | 新增商品 |
| GET | /api/v1/admin/categories | 分类列表 |
| GET | /api/v1/admin/brands | 品牌列表 |
| GET | /api/v1/admin/comments | 评价列表 |

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 商品列表 | list.md | 商品CRUD、上下架、批量操作 |
| 商品分类 | category.md | 分类树形结构 |
| 商品评价 | comment.md | 评价审核 |
| 品牌管理 | brand.md | 品牌CRUD |
