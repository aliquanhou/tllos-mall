# TLLOS商城 Nginx标准配置

## 配置说明
此目录包含TLLOS商城的标准Nginx配置文件。

## 重要原则
1. **服务器配置是标准规范，一次性配置好，以后不需要修改**
2. **项目代码必须适配服务器标准配置开发**
3. **禁止为了项目特殊需求反复修改Nginx配置**

## 配置架构
- /api/ - API请求，直接交给PHP-FPM处理（Laravel后端）
- /storage/ - 存储文件，后端Laravel存储目录
- /admin/ - 后台管理前端（admin/dist）
- /merchant/ - 商家管理前端（merchant/dist）
- / - 用户端PC站（pc/dist，默认）

## 部署方式
`ash
# 复制配置到Nginx目录
sudo cp deploy/nginx/mall.tllos.com.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/mall.tllos.com.conf /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重载配置
sudo systemctl reload nginx
`

## PHP版本
- PHP 8.2
- PHP-FPM Socket: /run/php/php8.2-fpm.sock

## 项目目录结构
`
/var/www/mall/
├── backend/      # Laravel后端
├── admin/        # 后台管理前端
├── merchant/     # 商家管理前端
├── pc/           # 用户端PC站前端
└── deploy/       # 部署配置
    └── nginx/    # Nginx配置
`
