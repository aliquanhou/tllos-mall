# 商品评价

## 页面概述
审核和管理用户对商品的评价，支持评分筛选、审核、商家回复、删除。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/comments | 评价列表 |
| PUT | /api/v1/admin/comments/{id}/status | 审核评价 |
| DELETE | /api/v1/admin/comments/{id} | 删除评价 |

## 字段映射
| 字段 | 数据库字段 | 类型 | 说明 |
|------|-----------|------|------|
| 用户 | user_id | int | 关联users |
| 商品 | product_id | int | 关联products |
| 评分 | rating | tinyint | 1-5星 |
| 内容 | content | text | 评价文字 |
| 状态 | status | tinyint | 0待审/1通过/2拒绝 |

## 验收清单
- [x] 评价列表正常显示
- [x] 评分筛选功能
- [x] 审核通过/拒绝
- [x] 删除评价
