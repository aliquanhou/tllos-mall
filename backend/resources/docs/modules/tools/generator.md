# 代码生成器

## 1. 页面概述
### 功能描述
代码生成器页面，支持根据数据表自动生成CRUD代码，包括模型、控制器、视图、API接口。代码生成器是提高开发效率的重要工具，减少重复代码编写。

### 核心指标
| 指标 | 含义 | 业务价值 |
|------|------|----------|
| 数据表数 | 可生成代码的数据表数 | 衡量可生成范围 |
| 已生成表 | 已生成代码的数据表数 | 衡量使用情况 |
| 生成文件数 | 每次生成的文件数量 | 衡量生成效率 |
| 模板数 | 代码模板数量 | 衡量生成灵活性 |

### 使用场景
1. 表选择：选择需要生成代码的数据表
2. 字段配置：配置字段的显示/验证/搜索属性
3. 代码预览：预览生成的代码
4. 代码生成：生成并下载代码文件

---

## 2. API接口清单（基于真实控制器实现）
| 方法 | 路径 | 控制器方法 | 说明 | 权限标识 |
|------|------|-----------|------|----------|
| GET | /api/v1/admin/generator/tables | GeneratorController@tables | 数据表列表 | tools:generator:list |
| GET | /api/v1/admin/generator/tables/{table}/columns | GeneratorController@columns | 表字段列表 | tools:generator:columns |
| POST | /api/v1/admin/generator/generate | GeneratorController@generate | 生成代码 | tools:generator:generate |
| POST | /api/v1/admin/generator/preview | GeneratorController@preview | 预览代码 | tools:generator:preview |
| POST | /api/v1/admin/generator/download | GeneratorController@download | 下载代码 | tools:generator:download |

### 请求参数
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| table | string | 是 | 数据表名 |
| fields | array | 是 | 字段配置 |
| template | string | 否 | 代码模板 |

### 返回示例
```json
{
  "code":0,
  "data":{
    "files":[
      {"name":"Product.php","path":"app/Models/Product.php","content":"<?php\nnamespace App\\Models;\n..."} ,
      {"name":"ProductController.php","path":"app/Http/Controllers/Admin/ProductController.php","content":"<?php\n..."} ,
      {"name":"index.vue","path":"resources/js/views/product/index.vue","content":"<template>..."} 
    ],
    "count":3
  }
}
```

### 错误码
| 错误码 | 说明 |
|--------|------|
| 10001 | 无权限操作 |
| 10002 | 数据不存在或已删除 |
| 10003 | 参数校验失败 |
| 10004 | 数据库操作失败 |
| 10005 | 数据表不存在 |
| 10006 | 代码生成失败 |

---

## 3. 字段映射表（基于真实数据表）
| 展示字段 | 数据来源 | 计算方式 | 更新频率 |
|----------|----------|----------|----------|
| 表名 | information_schema.tables | 直接读取 | 实时 |
| 表注释 | information_schema.tables.TABLE_COMMENT | 直接读取 | 实时 |
| 字段数 | information_schema.columns COUNT | 按表统计 | 实时 |
| 字段名 | information_schema.columns.COLUMN_NAME | 直接读取 | 实时 |
| 字段类型 | information_schema.columns.COLUMN_TYPE | 直接读取 | 实时 |
| 字段注释 | information_schema.columns.COLUMN_COMMENT | 直接读取 | 实时 |
| 是否主键 | information_schema.columns.COLUMN_KEY | 直接读取 | 实时 |
| 是否必填 | information_schema.columns.IS_NULLABLE | 直接读取 | 实时 |

---

## 4. 操作流程
```
进入代码生成器 → 查看数据表列表
├── 选择数据表 → 查看表字段
├── 配置字段 → 设置显示/验证/搜索/排序属性
├── 选择模板 → 选择代码生成模板
├── 预览代码 → 查看生成的代码内容
├── 生成代码 → 生成模型/控制器/视图/API
└── 下载代码 → 下载ZIP包或直接写入项目
```

### 数据刷新机制
1. 页面加载加载数据表列表
2. 选择表后加载字段
3. 生成代码实时返回

---

## 5. 权限控制
| 操作 | 权限标识 | 默认角色 |
|------|----------|----------|
| 查看表 | tools:generator:list | 超级管理员 |
| 查看字段 | tools:generator:columns | 超级管理员 |
| 生成代码 | tools:generator:generate | 超级管理员 |
| 预览代码 | tools:generator:preview | 超级管理员 |
| 下载代码 | tools:generator:download | 超级管理员 |

---

## 6. 关联模块
### 依赖模块
| 模块 | 依赖内容 |
|------|----------|
| 数据库 | 数据表和字段读取 |
| 代码模板 | 生成代码的模板 |
| 文件系统 | 代码文件写入 |

### 被依赖模块
| 模块 | 使用方式 |
|------|----------|
| 开发流程 | 快速生成CRUD代码 |
| 所有模块 | 代码生成器生成的代码 |

---

## 7. 验收清单
### 功能验收
- [ ] 页面能正常加载无白屏/500错误
- [ ] 数据表列表正常显示
- [ ] 表字段列表正常显示
- [ ] 字段配置功能正常
- [ ] 代码预览功能正常
- [ ] 代码生成功能正常
- [ ] 代码下载功能正常
- [ ] 生成的模型代码正确
- [ ] 生成的控制器代码正确
- [ ] 生成的视图代码正确

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
| 数据表不显示 | 数据库连接错误或权限不足 | 检查数据库配置和用户权限 |
| 生成的代码有错误 | 模板配置错误或字段类型不支持 | 检查代码模板和字段类型映射 |
