<template>
  <div class="not-found-page">
    <div class="not-found-content">
      <div class="error-code">404</div>
      <h2 class="error-title">页面走丢了</h2>
      <p class="error-desc">抱歉，您访问的页面不存在或已被移除</p>
      <div class="error-actions">
        <el-button type="primary" size="large" @click="$router.push('/home')">返回首页</el-button>
        <el-button size="large" @click="$router.back()">返回上一页</el-button>
      </div>
      <div class="help-links">
        <span>您可能想访问：</span>
        <el-button link type="primary" @click="$router.push('/products')">全部商品</el-button>
        <el-button link type="primary" @click="$router.push('/orders')">我的订单</el-button>
        <el-button link type="primary" @click="$router.push('/profile')">个人中心</el-button>
      </div>
    </div>
  </div>
</template>
<script setup>
</script>
<style scoped>
.not-found-page { min-height: calc(100vh - 200px); background: #f5f5f5; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
.not-found-content { text-align: center; max-width: 500px; }
.error-code { font-size: 120px; font-weight: bold; color: #e6a23c; line-height: 1; margin-bottom: 16px; text-shadow: 4px 4px 0 rgba(230,162,60,0.1); }
.error-title { font-size: 28px; color: #333; margin: 0 0 12px 0; }
.error-desc { font-size: 15px; color: #999; margin: 0 0 32px 0; }
.error-actions { display: flex; gap: 16px; justify-content: center; margin-bottom: 32px; }
.help-links { font-size: 14px; color: #999; }
.help-links a { color: #e6a23c; text-decoration: none; margin: 0 8px; }
.help-links a:hover { text-decoration: underline; }
</style>
