# 会员折扣

## 1. 页面概述
会员折扣是营销模块的等级权益配置功能，采用**"等级→折扣率"映射模式**，不同会员等级享受不同的购物折扣率。折扣率存储在用户等级表（user_levels）的discount字段中，等级越高折扣力度越大。管理员可在此页面查看和修改各等级的折扣率。

### 折扣率说明
| 折扣率 | 含义 | 优惠计算 |
|--------|------|----------|
| 100% | 无折扣 | 原价支付 |
| 95% | 95折 | 订单金额×0.95 |
| 90% | 9折 | 订单金额×0.90 |
| 85% | 85折 | 订单金额×0.85 |

### 等级折扣配置
| 等级 | 等级值 | 折扣率 | 升级所需积分 | 状态 |
|------|--------|--------|-------------|------|
| 普通会员 | 0 | 100%（无折扣） | 0（默认等级） | 启用 |
| 银卡会员 | 1 | 95%（95折） | 1000 | 启用 |
| 金卡会员 | 2 | 90%（9折） | 5000 | 启用 |
| 钻石会员 | 3 | 85%（85折） | 20000 | 启用 |

### 使用场景
1. 会员权益配置：设置不同等级的购物折扣率
2. 促销活动：临时调整折扣率刺激消费
3. 等级差异化：高等级会员享受更大折扣，激励用户升级
4. 订单结算：用户下单时按其等级折扣率计算优惠金额

## 2. API接口清单

| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/marketing/member-discount | MarketingController@memberDiscount | 获取会员折扣列表（所有等级及折扣率） |
| PUT | /api/v1/admin/marketing/member-discount/{id} | MarketingController@memberDiscountUpdate | 更新指定等级的折扣率 |

## 3. 请求参数

### 获取会员折扣列表
无请求参数。

### 更新会员折扣
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 等级ID（URL路径参数） |
| discount | decimal | 是 | 折扣率（%，0-100，如95表示95折） |
| name | string | 否 | 等级名称（最大100字符） |
| status | int | 否 | 状态（0停用1启用） |

## 4. 返回示例

### 获取会员折扣列表
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {"id":1,"name":"普通会员","level":0,"discount":"100.00","upgrade_points":0,"is_default":1,"status":1,"created_at":"2026-09-01 13:25:58","updated_at":"2026-09-01 23:23:06"},
      {"id":2,"name":"银卡会员","level":1,"discount":"95.00","upgrade_points":1000,"is_default":0,"status":1,"created_at":"2026-09-01 13:25:58","updated_at":"2026-09-01 23:23:06"},
      {"id":3,"name":"金卡会员","level":2,"discount":"90.00","upgrade_points":5000,"is_default":0,"status":1,"created_at":"2026-09-01 13:25:58","updated_at":"2026-09-01 23:23:06"},
      {"id":4,"name":"钻石会员","level":3,"discount":"85.00","upgrade_points":20000,"is_default":0,"status":1,"created_at":"2026-09-01 13:25:58","updated_at":"2026-09-01 23:23:06"}
    ],
    "total": 4
  }
}
```

### 更新会员折扣
```json
{"code":200,"message":"更新成功","data":null}
```

## 5. 字段映射表

### user_levels表（12字段，折扣相关字段）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 主键（等级ID） |
| name | varchar(100) | 等级名称 |
| level | int | 等级值（0普通/1银卡/2金卡/3钻石） |
| discount | decimal(5,2) | 折扣率（%，100=无折扣，95=95折） |
| benefits | text | 等级权益（JSON格式，含discount/free_shipping等） |
| upgrade_points | int | 升级所需积分 |
| is_default | tinyint | 是否默认等级（1是0否） |
| points | int | 等级积分 |
| status | tinyint | 状态（0停用1启用） |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### 折扣计算逻辑
| 场景 | 计算方式 |
|------|----------|
| 订单优惠金额 | 订单原价 × (100 - discount) / 100 |
| 订单实付金额 | 订单原价 × discount / 100 |
| 示例：银卡会员（95折）购买100元商品 | 优惠5元，实付95元 |

## 6. 操作流程

### 会员折扣配置与使用流程
```mermaid
flowchart TD
    A[管理员进入会员折扣页面] --> B[调用GET /member-discount获取等级列表]
    B --> C[展示各等级折扣率]
    C --> D[管理员修改某等级折扣率]
    D --> E[调用PUT /member-discount/{id}更新]
    E --> F[更新user_levels表discount字段]
    F --> G[配置实时生效]
    G --> H[用户登录商城]
    H --> I[系统读取用户等级对应的discount]
    I --> J[用户下单结算]
    J --> K[按折扣率计算优惠金额]
    K --> L[订单实付金额=原价×折扣率]
```

### 折扣生效机制
1. **实时生效**：修改折扣率后立即生效，无需重启
2. **读取时机**：用户下单结算时读取其等级对应的discount字段
3. **等级关联**：折扣率与用户等级绑定，用户升级后自动享受更高折扣
4. **默认等级**：新用户自动分配is_default=1的等级（普通会员，100%无折扣）

## 7. 权限控制
- 认证：Sanctum Token
- 中间件：auth:sanctum
- 当前权限模型：登录管理员可查看和修改会员折扣
- 无细粒度权限点（permissions表不存在）
- 修改折扣率为写操作，需管理员权限

## 8. 关联模块

### 依赖模块
| 模块 | 依赖内容 | 具体关联字段 |
|------|----------|-------------|
| 用户等级 | 等级列表及折扣率 | user_levels.discount |
| 用户管理 | 用户等级关联 | users.level_id → user_levels.id |
| 订单管理 | 订单结算时计算折扣 | orders.user_id → users.level_id → user_levels.discount |

### 被依赖模块
| 模块 | 使用方式 |
|------|----------|
| 用户端H5 | 下单时按用户等级折扣率计算优惠金额 |
| 订单结算 | 计算订单实付金额时应用会员折扣 |
| 用户等级 | 展示等级权益时显示折扣率 |

## 9. 验收清单
- [x] 获取会员折扣列表正常（返回4个等级及折扣率）
- [x] 普通会员折扣率正确（100%，无折扣）
- [x] 银卡会员折扣率正确（95%，95折）
- [x] 金卡会员折扣率正确（90%，9折）
- [x] 钻石会员折扣率正确（85%，85折）
- [x] 按id升序排序正常
- [x] 更新会员折扣正常（PUT方法，返回"更新成功"）
- [x] 更新后配置实时生效（再次GET验证折扣率已更新）
- [x] 折扣率必填验证正常（discount字段required）
- [x] 折扣率范围验证（0-100，前端应做限制）
- [x] 同时更新等级名称正常（name字段sometimes）
- [x] 同时更新状态正常（status字段sometimes，in:0,1）
- [x] updated_at自动更新正常
- [x] 折扣存储在user_levels表的discount字段（非独立member_discounts表）
- [x] 等级数据真实存在（4个等级：普通/银卡/金卡/钻石）
- [x] 修复了memberDiscount方法无路由的问题（新增GET /member-discount路由）
- [x] 新增了memberDiscountUpdate方法和PUT /member-discount/{id}路由

## 10. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 会员折扣页面无数据 | memberDiscount方法无路由 | 已修复，新增GET /admin/marketing/member-discount路由 |
| 修改折扣率无反应 | 缺少更新折扣的API | 已修复，新增PUT /admin/marketing/member-discount/{id}路由和memberDiscountUpdate方法 |
| 用户下单未享受折扣 | 订单结算逻辑未读取user_levels.discount | 检查订单结算逻辑，应按用户等级对应的discount字段计算优惠 |
| 折扣率显示100%但用户是银卡 | 用户等级未正确关联 | 检查users.level_id是否正确关联到user_levels.id |
| 修改折扣率后旧订单价格变化 | 订单应记录下单时的折扣率快照 | 建议在orders表中记录下单时的discount_rate，避免后续修改影响历史订单 |
| 折扣率超过100% | 前端未做范围限制 | 前端应限制discount输入范围0-100，后端验证min:0,max:100 |
| 禁用等级后用户无法下单 | 等级禁用后该等级用户的折扣处理 | 禁用等级后，该等级用户应按默认等级（普通会员100%）计算折扣，或提示用户等级已停用 |
| 折扣与优惠券叠加 | 未明确折扣和优惠券是否可同时使用 | 建议明确业务规则：会员折扣与优惠券可叠加使用，先算会员折扣再算优惠券 |
