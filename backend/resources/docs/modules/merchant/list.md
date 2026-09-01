# 商家列表

## 1. 页面概述
商家列表管理平台所有入驻商家，支持查看商家信息、审核状态、账户余额、商品/订单统计，以及启用/禁用商家。商家是多商户商城的核心主体，每个商家拥有独立的商品、订单、财务体系和登录账号。

### 核心指标（5项统计）
| 指标 | 数据来源 | 含义 |
|------|----------|------|
| 全部 | merchants COUNT(*) | 商家总数 |
| 待审核 | status=0 | 等待入驻审核 |
| 已通过 | status=1 | 正常经营 |
| 已拒绝 | status=2 | 审核拒绝 |
| 已禁用 | status=3 | 平台禁用 |

### 商家状态枚举
| status | 状态 | 说明 |
|--------|------|------|
| 0 | 待审核 | 提交入驻申请，等待审核 |
| 1 | 已通过 | 审核通过，正常经营 |
| 2 | 已拒绝 | 审核拒绝 |
| 3 | 已禁用 | 平台禁用 |

## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/merchants | MerchantController@index | 商家列表（分页+筛选+5项统计） |
| POST | /api/v1/admin/merchants | MerchantController@store | 商家入驻申请（含资质资料） |
| GET | /api/v1/admin/merchants/{id} | MerchantController@show | 商家详情（含审核日志） |
| PUT | /api/v1/admin/merchants/{id} | MerchantController@update | 编辑商家信息 |
| DELETE | /api/v1/admin/merchants/{id} | MerchantController@destroy | 删除商家（软删除） |
| POST | /api/v1/admin/merchants/{id}/toggle-status | MerchantController@toggleStatus | 启用/禁用商家（记录日志） |

## 3. 请求参数
### 商家列表
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| limit | int | 否 | 每页数量 |
| keyword | string | 否 | 按商家名/联系人/电话/公司搜索 |
| status | int | 否 | 按状态筛选（0-3） |
| category_id | int | 否 | 按分类筛选 |
| level | int | 否 | 按等级筛选 |
| start_time | string | 否 | 入驻开始时间 |
| end_time | string | 否 | 入驻结束时间 |

### 入驻申请
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| user_id | int | 是 | 关联用户ID |
| name | string | 是 | 商家名称（唯一） |
| contact_name | string | 是 | 联系人 |
| contact_mobile | string | 是 | 联系电话（同时作为登录账号） |
| category_id | int | 是 | 商家分类ID |
| company_name | string | 否 | 公司名称 |
| business_license | string | 否 | 营业执照号 |
| legal_person | string | 否 | 法人姓名 |
| id_card | string | 否 | 法人身份证号 |
| id_card_front | string | 否 | 身份证正面 |
| id_card_back | string | 否 | 身份证反面 |
| bank_name | string | 否 | 开户行 |
| bank_account | string | 否 | 银行账户 |
| bank_account_name | string | 否 | 开户人 |
| qualification_images | string | 否 | 资质图片（JSON） |
| agreement_version | string | 否 | 协议版本 |
| province_id/city_id/district_id | int | 否 | 地区ID |
| address | string | 否 | 详细地址 |
| description | string | 否 | 店铺描述 |

> 入驻时自动创建商家登录账号：username=contact_mobile，password=admin123（默认），status=0待审核。

## 4. 字段映射表（merchants表，47字段）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | bigint | 主键 |
| user_id | bigint | 关联用户ID |
| username | varchar(50) | 商家登录账号 |
| password | varchar(255) | 登录密码（加密） |
| nickname | varchar(50) | 昵称 |
| name | varchar(100) | 商家名称（唯一） |
| logo | varchar(255) | 商家Logo |
| banner | varchar(255) | 店铺Banner |
| description | text | 店铺描述 |
| category_id | bigint | 商家分类 |
| contact_name | varchar(50) | 联系人 |
| contact_mobile | varchar(20) | 联系电话 |
| contact_email | varchar(100) | 联系邮箱 |
| company_name | varchar(100) | 公司名称 |
| business_license | varchar(255) | 营业执照 |
| legal_person | varchar(50) | 法人 |
| id_card | varchar(30) | 身份证号 |
| id_card_front | varchar(255) | 身份证正面 |
| id_card_back | varchar(255) | 身份证反面 |
| bank_name | varchar(100) | 开户行 |
| bank_account | varchar(50) | 银行账户 |
| bank_account_name | varchar(50) | 开户人 |
| qualification_images | text | 资质图片（JSON） |
| agreement_version | varchar(20) | 协议版本 |
| agreement_signed_at | timestamp | 协议签署时间 |
| deposit | decimal(10,2) | 保证金 |
| deposit_status | tinyint | 保证金状态（0未缴1已缴） |
| province_id/city_id/district_id | bigint | 地区ID |
| address | varchar(255) | 详细地址 |
| balance | decimal(12,2) | 可用余额 |
| frozen | decimal(12,2) | 冻结金额 |
| total_income | decimal(12,2) | 累计收入 |
| total_settlement | decimal(12,2) | 累计结算 |
| product_count | int | 商品数量 |
| order_count | int | 订单数量 |
| rating | decimal(3,2) | 评分 |
| level | tinyint | 商家等级 |
| status | tinyint | 0待审核/1已通过/2已拒绝/3已禁用 |
| reject_reason | varchar(255) | 拒绝原因 |
| approved_at | timestamp | 审核通过时间 |
| created_at | timestamp | 创建时间 |
| deleted_at | timestamp | 软删除 |

## 5. 操作流程
```mermaid
flowchart TD
    A[用户提交入驻申请] --> B[status=0 待审核]
    B --> C[记录submit审核日志]
    C --> D{管理员审核}
    D -->|通过| E[status=1 已通过]
    D -->|拒绝| F[status=2 已拒绝]
    E --> G[自动创建店铺管理员账号]
    G --> H[记录approve审核日志]
    H --> I[商家可登录后台]
    F --> J[记录reject审核日志+拒绝原因]
    I --> K{违规?}
    K -->|是| L[status=3 已禁用]
    K -->|否| I
    L --> M[记录disable审核日志]
```

## 6. 权限控制
- 认证：Sanctum Token
- 中间件：auth:sanctum
- 当前无细粒度权限

## 7. 关联模块
- 依赖：用户管理（user_id）、商家分类（category_id）、商家等级（level）
- 被依赖：商品管理（merchant_id）、订单管理（merchant_id）、财务管理（商家结算/提现）、商家端（商家登录）、商家权限（shop_admins.shop_id）

## 8. 验收清单
- [x] 商家列表正常加载，返回5项状态统计
- [x] 商家详情返回完整信息+审核日志
- [x] 入驻申请正常（自动创建登录账号）
- [x] 商家状态切换正常（启用/禁用，记录日志）
- [x] 编辑商家信息正常（含银行账户）
- [x] 删除商家（软删除）正常
- [x] 按状态/关键词/分类/等级/时间筛选正常
- [x] 分页功能正常
- [x] 入驻时银行账户/资质/协议字段正常保存

## 9. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 商家名称重复 | name字段唯一约束 | 修改商家名称 |
| 入驻提示password无默认值 | 未传password字段 | store方法已自动设置默认密码admin123 |
| 禁用后商家无法登录 | status=3时商家端拒绝登录 | 启用商家后恢复 |
| 商家余额为0 | 无订单收入或已结算 | 查看账户日志确认资金流向 |
| 审核后店铺管理员未创建 | shop_roles表无对应商家角色 | audit方法已自动创建超级管理员角色 |
