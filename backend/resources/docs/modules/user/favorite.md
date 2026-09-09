# 用户收藏
## 1. 页面概述
管理用户收藏的商品记录，支持按用户/关键词筛选，展示收藏商品信息（名称/图片/价格/库存/状态）。
## 2. API接口清单
| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| GET | /api/v1/admin/user-favorites | FavoriteController@index | 收藏列表（分页+筛选+统计） |
| DELETE | /api/v1/admin/user-favorites/{id} | FavoriteController@destroy | 删除收藏记录 |
## 3. 字段映射（user_favorites表4字段）
id, user_id, goods_id(关联products.id), created_at
## 4. 关联数据
users(user_id)→昵称/手机号；products(goods_id)→商品名称/主图/价格/库存/状态
## 5. 统计指标
收藏总数、收藏用户数
## 6. 权限控制
Sanctum Token，auth:sanctum
## 7. 验收清单
- [x] 收藏列表含商品关联信息
- [x] 用户筛选/关键词搜索生效
- [x] 删除收藏生效
- [x] 统计数据正确
## 8. 常见问题
| 问题 | 原因 | 解决方案 |
|------|------|----------|
| 商品信息为空 | goods_id对应商品已删除 | LEFT JOIN保留记录，商品信息null |
