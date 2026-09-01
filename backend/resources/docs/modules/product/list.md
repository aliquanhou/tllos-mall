# 商品列表

## 1. 页面概述
### 功能描述
商城核心模块，管理所有商品信息，包括商品列表、分类、品牌、评价等。本页面负责商品列表的管理操作，支持数据的增删改查、搜索筛选和状态管理。

### 核心指标
| 指标 | 含义 | 业务价值 |
|------|------|----------|
| 数据总数 | 当前模块记录总数 | 衡量业务规模 |
| 今日新增 | 今日新创建记录数 | 衡量运营活跃度 |
| 启用数量 | 状态为启用的记录数 | 衡量有效数据量 |
| 待处理 | 需要审核或处理的记录数 | 及时处理提醒 |

### 使用场景
1. 日常管理：新增/编辑/删除数据
2. 数据查询：搜索筛选定位记录
3. 状态管理：启用/禁用/审核操作
4. 数据统计：查看业务数据趋势

---

## 2. API接口清单（基于真实控制器实现）
| 方法 | 路径 | 控制器方法 | 说明 | 权限标识 |
|------|------|-----------|------|----------|
| GET | /api/v1/admin/products | AdminProductController@index | 商品列表 | product:list |
| POST | /api/v1/admin/products | AdminProductController@store | 新增商品 | product:create |
| GET | /api/v1/admin/products/{id} | AdminProductController@show | 商品详情 | product:view |
| PUT | /api/v1/admin/products/{id} | AdminProductController@update | 编辑商品 | product:edit |
| DELETE | /api/v1/admin/products/{id} | AdminProductController@destroy | 删除商品 | product:delete |
| POST | /api/v1/admin/products/batch | AdminProductController@batchUpdate | 批量操作 | product:batch |
| PUT | /api/v1/admin/products/{id}/status | AdminProductController@toggleStatus | 上下架 | product:status |
| GET | /api/v1/admin/products/{id}/skus | AdminProductController@skus | SKU列表 | product:sku:list |

### 请求参数
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认20 |
| keyword | string | 否 | 搜索关键词 |
| status | int | 否 | 状态筛选 |
| start_date | date | 否 | 开始日期 |
| end_date | date | 否 | 结束日期 |

### 返回示例
```json
{
  "code": 0,
  "message": "success",
  "data": {
    "total": 100,
    "page": 1,
    "limit": 20,
    "list": [
      {"id": 1, "name": "示例数据", "status": 1, "created_at": "2026-09-01 10:00:00"}
    ]
  },
  "timestamp": 1756700000
}
```

### 错误码
| 错误码 | 说明 |
|--------|------|
| 10001 | 无权限操作 |
| 10002 | 数据不存在或已删除 |
| 10003 | 参数校验失败 |
| 10004 | 数据库操作失败 |
| 10005 | 数据已存在，不可重复创建 |

---

## 3. 字段映射表
| 展示字段 | 数据来源 | 计算方式 | 更新频率 |
|----------|----------|----------|----------|
| 商品ID | products.id | 直接读取 | 实时 |
| 商品名称 | products.name | 直接读取 | 实时 |
| 缩略图 | products.thumbnail | 直接读取 | 实时 |
| 价格 | products.price | SKU最低价 | 实时 |
| 原价 | products.original_price | 直接读取 | 实时 |
| 库存 | product_skus.stock | SUM汇总 | 实时 |
| 销量 | products.sales | 累计统计 | 准实时 |
| 状态 | products.status | 0下架1上架 | 实时 |

---

## 4. 操作流程
### 商品列表业务流程图
```mermaid
flowchart TD
    A[进入商品列表] --> B[搜索/筛选]
    B --> C[查看列表数据]
    C --> D{操作选择}
    D -->|新增| E[填写基本信息]
    E --> F[上传主图/附图/视频]
    F --> G[编辑富文本详情]
    G --> H[设置SKU规格]
    H --> I[保存上架]
    D -->|编辑| J[回显商品信息]
    J --> K[修改字段]
    K --> L[保存更新]
    D -->|批量| M[勾选商品]
    M --> N[批量上下架/删除]
    D -->|删除| O[确认弹窗]
    O --> P[软删除]
    I --> Q[列表刷新]
    L --> Q
    N --> Q
    P --> Q
```

### 数据刷新机制
1. 页面加载时自动请求最新数据
2. 搜索筛选条件变化时立即刷新
3. 增删改操作成功后自动刷新列表
4. 统计数据缓存时间：5分钟

---

## 5. 权限控制
| 操作 | 权限标识 | 默认角色 |
|------|----------|----------|
| 查看列表 | product:list | 管理员/运营 |
| 新增商品 | product:create | 管理员/运营 |
| 编辑商品 | product:edit | 管理员/运营 |
| 删除商品 | product:delete | 管理员 |
| 批量操作 | product:batch | 管理员 |
| 上下架 | product:status | 管理员/运营 |
| SKU管理 | product:sku:manage | 管理员/运营 |

### 权限说明
- 权限通过Sanctum中间件校验，在路由组中统一配置
- 超级管理员拥有所有权限，不受权限点限制
- 无权限用户访问API返回403，前端隐藏对应操作按钮

---

## 6. 关联模块
### 依赖模块
| 模块 | 依赖内容 | 具体关联字段 |
|------|----------|-------------|
| 商品分类 | 分类树用于归类筛选 | products.category_id → product_categories.id |
| 商家管理 | 商家信息用于归属 | products.merchant_id → merchants.id |
| 素材管理 | 图片视频上传存储 | products.thumbnail, products.images → materials.url |

### 被依赖模块
| 模块 | 使用方式 | 具体关联字段 |
|------|----------|-------------|
| 订单管理 | 订单商品信息来源 | order_items.product_id → products.id |
| 营销管理 | 优惠券秒杀关联商品 | coupon_products.product_id, seckill_products.product_id |
| 分销管理 | 分销商品选择 | distribute_goods.product_id → products.id |
| 装修管理 | 首页商品推荐 | decorate_components.config → products.id |

---

## 7. 验收清单
### 功能验收
- [ ] 页面能正常加载，无白屏/500错误
- [ ] 列表分页正常，显示总数和页码
- [ ] 搜索功能正常，支持关键词模糊查询
- [ ] 筛选功能正常，支持状态和时间范围
- [ ] 新增功能完整，表单校验正确
- [ ] 编辑能正确回显所有字段
- [ ] 删除有确认弹窗，软删除不影响历史数据
- [ ] 状态切换功能正常
- [ ] 数据导出功能正常（如有）
- [ ] 批量操作功能正常（如有）

### 权限验收
- [ ] 有权限的管理员可以正常操作
- [ ] 无权限的管理员看到403或入口隐藏
- [ ] 超级管理员不受权限限制

### 性能验收
- [ ] 页面加载时间 < 2秒
- [ ] 数据查询耗时 < 500ms
- [ ] 列表分页响应 < 1秒
- [ ] 并发100用户无明显延迟

---

## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 商品缩略图不显示 | 图片路径错误或CDN配置问题 | 检查素材管理中的图片URL，确认CDN域名配置正确 |
| SKU库存为0仍可下单 | 库存校验缺失 | 下单时校验product_skus.stock > 0，库存不足提示 |
| 商品分类树加载慢 | 分类层级过深或未缓存 | 限制最多3级分类，使用Redis缓存分类树 |
| 批量上下架失败 | 部分商品状态异常 | 检查products.status字段，确保值为0或1 |
| 商品评价不显示 | 评价未审核或关联错误 | 检查product_comments.status=1且product_id正确 |
| 商品价格显示异常 | SKU价格未同步 | 保存SKU时同步更新products.price为最低价 |
| 商品详情富文本丢失 | XSS过滤过度或存储截断 | 检查content字段类型为text/longtext，调整过滤规则 |
| 品牌管理无法删除 | 品牌下有关联商品 | 先将商品转移到其他品牌或设置为无品牌 |
