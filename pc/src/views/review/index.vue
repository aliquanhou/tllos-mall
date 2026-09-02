<template>
  <div class="review-page">
    <div class="container">
      <div class="page-header">
        <h2>评价商品</h2>
        <el-button @click="$router.back()">返回</el-button>
      </div>
      <div class="order-info-card">
        <div class="order-info">
          <span class="order-no">订单号：{{ orderNo }}</span>
          <span class="order-time">下单时间：{{ orderTime }}</span>
        </div>
      </div>
      <div class="review-list">
        <div class="review-card" v-for="item in orderItems" :key="item.id">
          <div class="goods-info">
            <div class="goods-image"><img :src="item.main_image" :alt="item.name" /></div>
            <div class="goods-detail">
              <div class="goods-name">{{ item.name }}</div>
              <div class="goods-spec" v-if="item.specs">{{ item.specs }}</div>
            </div>
          </div>
          <div class="review-form">
            <div class="form-item">
              <label class="form-label">商品评分</label>
              <el-rate v-model="item.rating" :max="5" show-text :texts="['很差', '较差', '一般', '满意', '非常满意']" />
            </div>
            <div class="form-item">
              <label class="form-label">评价内容</label>
              <el-input v-model="item.content" type="textarea" :rows="4" placeholder="分享您的使用体验，帮助更多买家做出选择~" maxlength="500" show-word-limit />
            </div>
            <div class="form-item">
              <label class="form-label">上传图片</label>
              <el-upload action="#" list-type="picture-card" :auto-upload="false" :limit="5" multiple>
                <el-icon><Plus /></el-icon>
              </el-upload>
              <div class="upload-tip">最多上传5张图片，支持jpg/png格式</div>
            </div>
            <div class="form-item">
              <label class="form-label">匿名评价</label>
              <el-switch v-model="item.anonymous" />
              <span class="switch-tip">匿名评价后，其他用户将看不到您的昵称</span>
            </div>
          </div>
        </div>
      </div>
      <div class="submit-bar">
        <el-button type="primary" size="large" :loading="submitting" @click="submitReview">提交评价</el-button>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
const route = useRoute()
const router = useRouter()
const orderNo = ref(route.query.order_no || 'ORD20260901001')
const orderTime = ref('2026-09-01 10:00:00')
const submitting = ref(false)
const orderItems = ref([
  { id: 1, name: '示例商品1', specs: '规格：默认', main_image: '', rating: 5, content: '', anonymous: false },
])
const submitReview = () => {
  const invalid = orderItems.value.find(i => !i.rating)
  if (invalid) { ElMessage.warning('请为所有商品评分'); return }
  submitting.value = true
  setTimeout(() => {
    submitting.value = false
    ElMessage.success('评价提交成功，感谢您的反馈！')
    router.push('/orders')
  }, 1500)
}
</script>
<style scoped>
.review-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.page-header h2 { font-size: 22px; color: #333; margin: 0; }
.order-info-card { background: #fff; border-radius: 8px; padding: 16px 20px; margin-bottom: 16px; }
.order-info { display: flex; gap: 24px; font-size: 14px; color: #666; }
.review-list { display: flex; flex-direction: column; gap: 16px; }
.review-card { background: #fff; border-radius: 8px; padding: 20px; }
.goods-info { display: flex; gap: 16px; padding-bottom: 16px; border-bottom: 1px solid #f5f5f5; margin-bottom: 16px; }
.goods-image { width: 80px; height: 80px; border-radius: 4px; overflow: hidden; background: #f5f5f5; flex-shrink: 0; }
.goods-image img { width: 100%; height: 100%; object-fit: cover; }
.goods-detail { }
.goods-name { font-size: 15px; color: #333; margin-bottom: 6px; }
.goods-spec { font-size: 13px; color: #999; }
.review-form { }
.form-item { margin-bottom: 20px; }
.form-label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; font-weight: 500; }
.upload-tip { font-size: 12px; color: #999; margin-top: 8px; }
.switch-tip { font-size: 13px; color: #999; margin-left: 12px; }
.submit-bar { text-align: center; margin-top: 24px; }
</style>
