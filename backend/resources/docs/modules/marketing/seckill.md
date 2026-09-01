# 限时秒杀

## 页面概述
限时秒杀用于创建秒杀活动，设置秒杀商品、秒杀价格、活动时间。

## API接口
| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/v1/admin/seckills | 秒杀列表 |
| POST | /api/v1/admin/seckills | 新增秒杀 |
| PUT | /api/v1/admin/seckills/{id} | 编辑秒杀 |
| DELETE | /api/v1/admin/seckills/{id} | 删除秒杀 |

## 验收清单
- [x] 秒杀列表正常
- [x] CRUD功能正常
