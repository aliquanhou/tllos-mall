# 商家分类

## 1. 页面概述
商家分类用于对入驻商家进行行业分类管理，每个分类可设置独立的佣金比例。商家入驻时选择所属分类，平台按分类佣金比例计算平台抽成。

## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/merchant-categories | CategoryController@index | 分类列表 |
| POST | /api/v1/admin/merchant-categories | CategoryController@store | 新增分类 |
| PUT | /api/v1/admin/merchant-categories/{id} | CategoryController@update | 编辑分类 |
| DELETE | /api/v1/admin/merchant-categories/{id} | CategoryController@destroy | 删除分类 |

## 3. 请求参数
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 分类名称（唯一） |
| icon | string | 否 | 分类图标 |
| commission_rate | decimal | 否 | 佣金比例（%） |
| sort | int | 否 | 排序 |
| status | int | 否 | 1=启用，0=禁用 |

## 4. 字段映射表（merchant_categories表，8字段）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| name | varchar(50) | 分类名称（唯一） |
| icon | varchar(255) | 分类图标 |
| commission_rate | decimal(5,2) | 佣金比例（%） |
| sort | int | 排序（升序） |
| status | tinyint | 1=启用，0=禁用 |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

## 5. 操作流程
```mermaid
flowchart LR
    A[新增分类] --> B[设置名称/佣金比例]
    B --> C[保存]
    C --> D[商家入驻时选择分类]
    D --> E[订单完成按比例抽成]
```

## 6. 权限控制
- 认证：Sanctum Token
- 中间件：auth:sanctum

## 7. 验收清单
- [x] 分类列表正常加载
- [x] 新增分类正常
- [x] 编辑分类正常
- [x] 删除分类正常
- [x] 分类名称唯一约束
- [x] 按sort排序显示

## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 删除分类失败 | 有商家使用该分类 | 先将商家迁移到其他分类 |
| 佣金比例不生效 | 订单结算时未读取分类佣金 | 检查结算逻辑是否关联category_id |
