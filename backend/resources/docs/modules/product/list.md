# 商品列表

## 页面概述
商品列表页用于管理商城所有商品，支持搜索、筛选、分页、上下架切换、批量操作、商品预览。是后台最核心的管理页面。

## API接口清单
| 方法 | 路径 | 说明 | 权限 |
|------|------|------|------|
| GET | /api/v1/admin/products | 商品列表（分页） | product:list |
| POST | /api/v1/admin/products | 新增商品 | product:create |
| GET | /api/v1/admin/products/{id} | 商品详情 | product:list |
| PUT | /api/v1/admin/products/{id} | 编辑商品 | product:edit |
| DELETE | /api/v1/admin/products/{id} | 删除商品 | product:delete |
| PUT | /api/v1/admin/products/{id}/status | 切换上下架 | product:edit |
| POST | /api/v1/admin/products/batch | 批量操作 | product:edit |
| POST | /api/v1/upload/image | 上传商品图片 | - |
| POST | /api/v1/upload/video | 上传商品视频 | - |

## 字段映射表
| 列表字段 | 数据库字段 | 类型 | 说明 |
|---------|-----------|------|------|
| ID | id | int | 自增主键 |
| 商品名称 | name | string | 商品标题 |
| 主图 | main_image | string | 缩略图URL |
| 价格 | price | decimal | 售价（元） |
| 原价 | original_price | decimal | 划线价 |
| 库存 | stock | int | 总库存（SKU汇总） |
| 销量 | sales | int | 累计销量 |
| 状态 | status | enum | draft/active/inactive |
| 分类 | category_id | int | 关联categories表 |
| 品牌 | brand_id | int | 关联brands表 |
| 创建时间 | created_at | datetime | - |

## 新增商品表单字段
| 字段 | 数据库字段 | 类型 | 必填 | 说明 |
|------|-----------|------|------|------|
| 商品名称 | name | string | 是 | 最多100字 |
| 商品分类 | category_id | int | 是 | 三级分类 |
| 商品品牌 | brand_id | int | 否 | - |
| 商品主图 | main_image | string | 是 | 建议800x800 |
| 商品附图 | images | json | 否 | 最多10张 |
| 商品视频 | video | string | 否 | mp4格式 |
| 商品价格 | price | decimal | 是 | 单位元 |
| 原价 | original_price | decimal | 否 | 划线价 |
| 库存 | stock | int | 是 | 总库存 |
| SKU规格 | goods_sku | array | 否 | 多规格时必填 |
| 商品详情 | content | text | 否 | 富文本编辑器 |
| 商品状态 | status | enum | 是 | 草稿/上架/下架 |

## 操作流程
1. 进入商品列表 -> 加载分页数据
2. 搜索/筛选 -> 按名称/分类/状态/时间范围过滤
3. 新增商品 -> 填写基本信息 -> 上传主图/附图/视频 -> 设置SKU规格 -> 富文本编辑详情 -> 保存
4. 编辑商品 -> 回显数据 -> 修改 -> 保存
5. 商品预览 -> 弹窗展示商品详情页效果
6. 上下架切换 -> 状态变更 -> 列表刷新
7. 批量操作 -> 选中多行 -> 批量上架/下架/删除
8. 删除商品 -> 确认弹窗 -> 软删除 -> 列表刷新

## SKU规格管理
- 支持单规格（无SKU，直接用商品主表的价格库存）
- 支持多规格（颜色/尺寸等，每个SKU独立价格库存）
- SKU字段：sku_no（SKU编码）、spec_text（规格文本）、price、stock
- 商品总库存 = 所有SKU库存之和

## 权限控制
| 操作 | 权限标识 | 按钮显示 |
|------|----------|----------|
| 查看列表 | product:list | 页面访问 |
| 新增 | product:create | 「新增商品」按钮 |
| 编辑 | product:edit | 「编辑」操作列 |
| 删除 | product:delete | 「删除」操作列 |
| 上下架 | product:edit | 状态开关 |
| 批量操作 | product:edit | 批量按钮 |

## 关联模块
- **依赖**：商品分类（categories）、品牌管理（brands）、商家管理（merchants）、素材管理（上传图片视频）
- **被依赖**：订单管理（orders）、分销管理（distribute_goods）、营销管理（coupons/seckills/groups）、装修管理（商品推荐组件）

## 验收清单
- [x] 列表能正常显示数据（分页）
- [x] 搜索功能正常（按名称模糊搜索）
- [x] 筛选功能正常（分类/状态/时间范围）
- [x] 新增商品能保存（含主图/SKU/详情）
- [x] 编辑商品能回显并保存
- [x] 删除商品功能正常
- [x] 上下架切换功能正常
- [x] 商品缩略图正常显示
- [x] 商品主图/附图/视频上传
- [x] SKU规格管理
- [x] 富文本编辑器
- [ ] 商品预览功能待完善
- [ ] 批量操作待完善
