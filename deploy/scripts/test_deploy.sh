#!/bin/bash
echo "=== 目录结构 ==="
ls -la /var/www/mall/backend/public/

echo ""
echo "=== 入口测试 ==="
for path in "/pc/" "/admin/" "/merchant/"; do
    STATUS=$(curl -s -o /dev/null -w '%{http_code}' "https://mall.tllos.com${path}")
    echo "  ${path}: HTTP ${STATUS}"
done

echo ""
echo "=== API测试 ==="
curl -s -o /dev/null -w "  商品列表: HTTP %{http_code}\n" "https://mall.tllos.com/api/v1/products?page=1&per_page=1"

echo ""
echo "=== 登录API测试 ==="
curl -s -X POST "https://mall.tllos.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"account":"13800138000","password":"TllosAdmin2026!"}' \
  -w "\n  登录API: HTTP %{http_code}\n" 2>&1 | tail -5
