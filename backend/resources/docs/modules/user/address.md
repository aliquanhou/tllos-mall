# 收货地址
## 1. 页面概述
管理用户收货地址，管理端查看，用户端增删改。每个用户可设一个默认地址。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-center/addresses | 管理端地址列表 |
| GET | /api/v1/user/addresses | AddressController@lists | 用户端地址列表 |
| POST | /api/v1/user/addresses | AddressController@add | 用户端新增 |
| PUT | /api/v1/user/addresses/{id} | AddressController@edit | 用户端编辑 |
| DELETE | /api/v1/user/addresses/{id} | AddressController@delete | 用户端删除 |
## 3. 字段映射（user_addresses表14字段）
id, user_id, name, mobile, province_id/city_id/district_id, province_name/city_name/district_name, detail, postal_code, is_default, created_at, updated_at
## 4. 权限控制
Sanctum Token，auth:sanctum
## 5. 验收清单
- [x] 地址列表加载
- [x] 默认地址标记正确
- [x] 用户端增删改生效
## 6. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 默认地址不唯一 | 新增未取消其他默认 | 设置新默认前将其他置0 |
