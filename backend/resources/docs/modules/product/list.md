# 商品列表

## 页面概述
商品列表页用于管理商城所有商品，支持搜索、筛选、分页、上下架切换、批量操作。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/products | 商品列表（分页） |
| POST | /api/v1/admin/products | 新增商品 |
| GET | /api/v1/admin/products/{id} | 商品详情 |
| PUT | /api/v1/admin/products/{id} | 编辑商品 |
| DELETE | /api/v1/admin/products/{id} | 删除商品 |
| PUT | /api/v1/admin/products/{id}/status | 切换上下架 |

## 字段映射
| 列表字段 | 数据库字段 | 类型 | 说明 |
|---------|-----------|------|------|
| ID | id | int | 主键 |
| 商品名称 | name | string | 标题 |
| 主图 | main_image | string | 缩略图URL |
| 价格 | price | decimal | 售价 |
| 原价 | original_price | decimal | 划线价 |
| 库存 | stock | int | 总库存 |
| 销量 | sales | int | 累计销量 |
| 状态 | status | enum | draft/active/inactive |
| 分类 | category_id | int | 关联categories |
| 品牌 | brand_id | int | 关联brands |
| 创建时间 | created_at | datetime | - |

## 操作流程
1. 进入列表 -> 加载分页数据
2. 搜索/筛选 -> 按名称/分类/状态/时间过滤
3. 新增商品 -> 填写信息 -> 上传主图/附图/视频 -> 设置SKU -> 保存
4. 编辑商品 -> 回显 -> 修改 -> 保存
5. 上下架切换 -> 状态变更 -> 列表刷新
6. 批量操作 -> 选中 -> 批量上架/下架/删除
7. 删除商品 -> 确认 -> 软删除

## 权限控制
| 操作 | 权限标识 |
|------|----------|
| 查看列表 | product:list |
| 新增 | product:create |
| 编辑 | product:edit |
| 删除 | product:delete |

## 关联模块
- 依赖：商品分类、品牌管理、商家管理
- 被依赖：订单管理、分销管理、营销管理

## 验收清单
- [x] 列表显示数据（分页）
- [x] 搜索功能正常
- [x] 筛选功能正常
- [x] 新增商品能保存（含主图/SKU）
- [x] 编辑商品能回显并保存
- [x] 删除功能正常
- [x] 上下架切换正常
- [x] 缩略图正常显示
- [ ] 批量操作待完善
- [ ] 商品预览待完善
