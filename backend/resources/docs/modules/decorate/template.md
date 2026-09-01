# 模板管理

## 1. 页面概述
### 功能描述
装修模板管理页面，支持创建和管理页面装修模板，包括模板导入/导出、模板预览、模板应用。模板是预设的页面装修方案，可以快速应用到新页面。

### 核心指标
| 指标 | 含义 | 业务价值 |
|------|------|----------|
| 模板总数 | 所有模板数量 | 衡量模板库规模 |
| 系统模板 | 系统预设模板数 | 衡量官方模板 |
| 自定义模板 | 用户创建模板数 | 衡量自定义能力 |
| 应用次数 | 模板被应用次数 | 衡量模板使用率 |

### 使用场景
1. 模板管理：查看/编辑/删除模板
2. 模板应用：将模板应用到页面
3. 模板导入：导入外部模板
4. 模板导出：导出当前页面为模板

---

## 2. API接口清单（基于真实控制器实现）
| 方法 | 路径 | 控制器方法 | 说明 | 权限标识 |
|------|------|-----------|------|----------|
| GET | /api/v1/admin/decorate/templates | TemplateController@index | 模板列表 | decorate:template:list |
| GET | /api/v1/admin/decorate/templates/{id} | TemplateController@show | 模板详情 | decorate:template:view |
| POST | /api/v1/admin/decorate/templates | TemplateController@store | 新增模板 | decorate:template:create |
| PUT | /api/v1/admin/decorate/templates/{id} | TemplateController@update | 编辑模板 | decorate:template:edit |
| DELETE | /api/v1/admin/decorate/templates/{id} | TemplateController@destroy | 删除模板 | decorate:template:delete |
| POST | /api/v1/admin/decorate/templates/{id}/apply | TemplateController@apply | 应用模板 | decorate:template:apply |

### 请求参数
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码默认1 |
| limit | int | 否 | 每页数量默认20 |
| keyword | string | 否 | 搜索关键词 |
| page_type | string | 否 | 页面类型 |
| is_system | int | 否 | 是否系统模板 |

### 返回示例
```json
{
  "code":0,
  "data":{"total":8,"list":[
    {"id":1,"name":"经典电商模板","page_type":"home","description":"轮播图+分类导航+商品推荐","thumbnail":"https://.../template1.jpg","components":12,"is_system":1,"apply_count":156,"status":1,"created_at":"2026-08-01"}
  ]}
}
```

### 错误码
| 错误码 | 说明 |
|--------|------|
| 10001 | 无权限操作 |
| 10002 | 数据不存在或已删除 |
| 10003 | 参数校验失败 |
| 10004 | 数据库操作失败 |
| 10005 | 系统模板不能删除 |
| 10006 | 模板格式错误 |

---

## 3. 字段映射表（基于真实数据表）
| 展示字段 | 数据来源 | 计算方式 | 更新频率 |
|----------|----------|----------|----------|
| 模板ID | decorate_templates.id | 直接读取 | 实时 |
| 模板名称 | decorate_templates.name | 直接读取 | 实时 |
| 页面类型 | decorate_templates.page_type | home/category/member | 实时 |
| 描述 | decorate_templates.description | 直接读取 | 实时 |
| 缩略图 | decorate_templates.thumbnail | 直接读取 | 实时 |
| 组件数 | decorate_templates.components | JSON配置 | 实时 |
| 是否系统 | decorate_templates.is_system | 0自定义1系统 | 实时 |
| 应用次数 | decorate_templates.apply_count | 累计统计 | 准实时 |
| 状态 | decorate_templates.status | 0禁用1启用 | 实时 |

---

## 4. 操作流程
```
进入模板管理 → 查看模板列表
├── 新建模板 → 从空白创建或从页面保存为模板
├── 编辑模板 → 修改模板组件配置
├── 预览模板 → 查看模板效果
├── 应用模板 → 选择目标页面 → 一键应用
├── 导出模板 → 导出模板JSON文件
├── 导入模板 → 上传模板JSON文件
└── 删除模板 → 自定义模板可删除（系统模板不可删）
```

### 数据刷新机制
1. 页面加载加载模板列表
2. 增删改后刷新列表
3. 应用次数准实时更新

---

## 5. 权限控制
| 操作 | 权限标识 | 默认角色 |
|------|----------|----------|
| 查看模板 | decorate:template:list | 管理员/运营 |
| 新增模板 | decorate:template:create | 管理员/运营 |
| 编辑模板 | decorate:template:edit | 管理员/运营 |
| 删除模板 | decorate:template:delete | 管理员 |
| 应用模板 | decorate:template:apply | 管理员/运营 |

---

## 6. 关联模块
### 依赖模块
| 模块 | 依赖内容 |
|------|----------|
| 页面装修 | 模板应用到页面 |
| 素材管理 | 模板图片资源 |
| 组件库 | 模板组件配置 |

### 被依赖模块
| 模块 | 使用方式 |
|------|----------|
| 页面装修 | 选择模板应用 |
| 用户端H5 | 应用模板后的页面渲染 |

---

## 7. 验收清单
### 功能验收
- [ ] 页面能正常加载无白屏/500错误
- [ ] 列表分页正常显示总数和页码
- [ ] 搜索功能正常
- [ ] 筛选功能正常
- [ ] 新增功能完整
- [ ] 编辑能正确回显所有字段
- [ ] 删除有确认弹窗
- [ ] 状态切换功能正常
- [ ] 模板详情能查看组件配置和预览
- [ ] 新建模板功能正常
- [ ] 编辑模板功能正常
- [ ] 预览模板功能正常
- [ ] 应用模板功能正常
- [ ] 导出模板功能正常
- [ ] 导入模板功能正常
- [ ] 删除模板功能正常（系统模板提示）
- [ ] 按页面类型筛选正常
- [ ] 应用次数统计正确

### 权限验收
- [ ] 有权限的管理员可以正常操作
- [ ] 无权限的管理员看到403或入口隐藏

### 性能验收
- [ ] 页面加载时间 < 2秒
- [ ] 数据查询耗时 < 500ms
- [ ] 列表分页响应 < 1秒

---

## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 列表数据为空 | 数据库无数据或筛选条件不对 | 检查筛选条件确认有测试数据 |
| 新增保存失败 | 必填字段未填或格式错误 | 检查表单校验查看错误提示 |
| 编辑回显不全 | 接口返回字段缺失 | 检查控制器返回字段 |
| 删除后仍显示 | 缓存未清除 | 清除缓存 |
| 应用模板后页面不变化 | 缓存未清除或页面未发布 | 清除缓存并重新发布页面 |
| 导入模板失败 | 模板格式错误或版本不兼容 | 检查模板JSON格式和版本号 |
