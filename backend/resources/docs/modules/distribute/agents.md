# 分销商

## 1. 页面概述
### 功能描述
分销体系核心，管理分销商、等级、订单、佣金、设置等。本页面负责分销商的管理操作，支持数据的增删改查、搜索筛选和状态管理。

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
| GET | /api/v1/admin/distribute/agents | DistributeController@index | 分销商列表 | distribute:list |
| POST | /api/v1/admin/distribute/agents | DistributeController@store | 新增分销商 | distribute:create |
| GET | /api/v1/admin/distribute/agents/{id} | DistributeController@show | 分销商详情 | distribute:view |
| PUT | /api/v1/admin/distribute/agents/{id} | DistributeController@update | 编辑分销商 | distribute:edit |
| DELETE | /api/v1/admin/distribute/agents/{id} | DistributeController@destroy | 删除分销商 | distribute:delete |

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
| ID | distribute.id | 直接读取 | 实时 |
| 名称 | distribute.name | 直接读取 | 实时 |
| 状态 | distribute.status | 0禁用1启用 | 实时 |
| 创建时间 | distribute.created_at | 直接读取 | 实时 |

---

## 4. 操作流程
### 分销商业务流程图
```mermaid
flowchart TD
    A[进入列表页] --> B[加载数据]
    B --> C[搜索/筛选]
    C --> D[查看列表]
    D --> E{操作选择}
    E -->|新增| F[填写表单]
    F --> G[提交保存]
    E -->|编辑| H[回显数据]
    H --> I[修改并保存]
    E -->|删除| J[确认弹窗]
    J --> K[执行删除]
    E -->|查看| L[详情页]
    G --> M[刷新列表]
    I --> M
    K --> M
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
| 查看列表 | distribute:list | 管理员 |
| 新增 | distribute:create | 管理员 |
| 编辑 | distribute:edit | 管理员 |
| 删除 | distribute:delete | 管理员 |

### 权限说明
- 权限通过Sanctum中间件校验，在路由组中统一配置
- 超级管理员拥有所有权限，不受权限点限制
- 无权限用户访问API返回403，前端隐藏对应操作按钮

---

## 6. 关联模块
### 依赖模块
| 模块 | 依赖内容 | 具体关联字段 |
|------|----------|-------------|
| 用户管理 | 分销商用户 | distributors.user_id → users.id |
| 商品管理 | 分销商品 | distribute_goods.product_id → products.id |
| 订单管理 | 分销订单 | distribute_orders.order_id → orders.id |

### 被依赖模块
| 模块 | 使用方式 | 具体关联字段 |
|------|----------|-------------|
| 财务管理 | 佣金结算 | distribute_orders.commission → finance_withdraw |
| 用户端H5 | 分销中心 | 用户端展示分销商品和佣金 |

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
| 分销商审核不通过 | 申请信息不全或不符合条件 | 检查distributors.apply_info和审核标准 |
| 分销订单不记录 | 分销关系未建立或订单未标记 | 检查orders.is_distribute=1和distributor_id |
| 佣金计算错误 | 佣金比例配置或层级关系错误 | 检查distribute_levels.commission_rate和上下级关系 |
| 分销商等级不升级 | 升级条件未达到 | 检查distribute_levels升级条件（销售额/人数） |
| 分销商品不显示 | 商品未设置分销或已下架 | 检查distribute_goods.is_open和products.status |
| 佣金无法提现 | 佣金未结算或余额不足 | 检查distribute_orders.status=settled和distributors.commission |
| 分销关系错乱 | 上级分销商变更或绑定错误 | 检查distributors.parent_id和绑定时间逻辑 |
| 分销设置不生效 | 缓存未清除 | 修改distribute_settings后清除Redis缓存 |
