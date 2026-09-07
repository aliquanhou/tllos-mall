# TLLOS商城 部署配置

## 架构说明（参考福多多）
- **根目录部署**：所有前端都在 ackend/public/ 子目录下
- **根目录只有Laravel入口**：index.php，不混合前端文件
- **Nginx配置极简**：参考福多多，一次性配置好，以后不再修改

## 目录结构
`
backend/public/              # 网站根目录
├── index.php                # Laravel入口（唯一PHP入口）
├── .htaccess                # URL重写规则
├── admin/                   # 后台管理前端（子目录）
├── merchant/                # 商家管理前端（子目录）
├── pc/                      # 用户端PC站前端（子目录）
└── storage/                 # 存储文件（软链接）
`

## 访问地址
- 用户端: https://mall.tllos.com/pc/
- 后台:   https://mall.tllos.com/admin/
- 商家:   https://mall.tllos.com/merchant/
- API:    https://mall.tllos.com/api/v1/...

## 重要原则
1. **服务器配置是标准规范，一次性配置好，以后不需要修改**
2. **项目代码必须适配服务器标准配置开发**
3. **禁止为了项目特殊需求反复修改Nginx配置**
4. **所有前端构建产物必须放到对应子目录下**

## 部署命令
`ash
# 构建前端
cd admin && npm run build
cd ../merchant && npm run build
cd ../pc && npm run build

# 部署到public子目录
cp -r admin/dist backend/public/admin
cp -r merchant/dist backend/public/merchant
cp -r pc/dist backend/public/pc

# 修改base路径
# admin/index.html: <base href=" /admin/\>
# merchant/index.html: <base href=\/merchant/\>
# pc/index.html: <base href=\/pc/\>
`

## Nginx配置
配置文件: deploy/nginx/mall.tllos.com.conf

参考福多多的极简配置：
- URL重写: 所有非真实文件/目录请求转发到index.php
- PHP处理: 标准fastcgi配置
- 静态资源: 7天缓存
