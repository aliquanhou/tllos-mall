# 权限管理模块总览

## 模块概述
基于RBAC模型的权限管理系统，包括角色管理、菜单管理、部门管理、管理员管理、操作日志。

## 数据库设计
| 表名 | 说明 | 关键字段 |
|------|------|----------|
| admins | 管理员 | id, username, password, role_id, dept_id, status |
| admin_roles | 角色 | id, name, permissions, status |
| admin_menus | 菜单 | id, parent_id, name, path, icon, sort, status |
| departments | 部门 | id, parent_id, name, leader, status |

## 子模块
| 子模块 | 文档 | 说明 |
|--------|------|------|
| 角色管理 | role.md | 角色权限分配 |
| 菜单管理 | menu.md | 动态菜单配置 |
| 部门管理 | dept.md | 部门树 |
| 管理员管理 | admin.md | 管理员账号 |
