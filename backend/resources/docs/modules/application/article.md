# 文章资讯

## 1. 页面概述
### 功能描述
商城辅助应用，包括充值管理、素材管理、文章资讯、消息管理、客服设置等。本页面负责文章资讯的管理操作，支持数据的增删改查、搜索筛选和状态管理。

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
| GET | /api/v1/admin/application/article | ApplicationController@index | 文章资讯列表 | application:list |
| POST | /api/v1/admin/application/article | ApplicationController@store | 新增文章资讯 | application:create |
| GET | /api/v1/admin/application/article/{id} | ApplicationController@show | 文章资讯详情 | application:view |
| PUT | /api/v1/admin/application/article/{id} | ApplicationController@update | 编辑文章资讯 | application:edit |
| DELETE | /api/v1/admin/application/article/{id} | ApplicationController@destroy | 删除文章资讯 | application:delete |

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
| ID | application.id | 直接读取 | 实时 |
| 名称 | application.name | 直接读取 | 实时 |
| 状态 | application.status | 0禁用1启用 | 实时 |
| 创建时间 | application.created_at | 直接读取 | 实时 |

---

## 4. 操作流程
### 文章资讯业务流程图
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
| 查看列表 | application:list | 管理员 |
| 新增 | application:create | 管理员 |
| 编辑 | application:edit | 管理员 |
| 删除 | application:delete | 管理员 |

### 权限说明
- 权限通过Sanctum中间件校验，在路由组中统一配置
- 超级管理员拥有所有权限，不受权限点限制
- 无权限用户访问API返回403，前端隐藏对应操作按钮

---

## 6. 关联模块
### 依赖模块
| 模块 | 依赖内容 | 具体关联字段 |
|------|----------|-------------|
| 用户管理 | 充值用户 | recharge_orders.user_id → users.id |
| 系统设置 | 存储配置 | materials存储依赖system_configs.storage |

### 被依赖模块
| 模块 | 使用方式 | 具体关联字段 |
|------|----------|-------------|
| 商品管理 | 商品图片素材 | products.thumbnail → materials.url |
| 装修管理 | 广告图素材 | banners.image → materials.url |
| 用户端H5 | 文章和公告展示 | articles, notices在H5端展示 |

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
| 素材上传失败 | 存储配置错误或文件超限 | 检查config/filesystems.php和文件大小限制 |
| 素材缩略图不生成 | GD库未安装 | 安装php-gd扩展，支持jpg/png/webp |
| 文章不显示 | 文章未发布或分类错误 | 检查articles.status=1和category_id |
| 消息推送失败 | 推送配置错误 | 检查短信/邮件/站内信配置 |
| 客服设置不生效 | 缓存未清除 | 修改kefu_settings后清除缓存 |
| 充值订单未到账 | 支付回调未处理 | 检查recharge_orders.pay_time和users.balance更新 |
| 素材文件夹无法删除 | 文件夹下有素材 | 先移动或删除文件夹内素材 |
| 公告不显示 | 公告未启用或时间未到 | 检查notices.status和start_time/end_time |
