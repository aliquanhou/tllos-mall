<template>
  <div class="skeleton-wrapper">
    <!-- 商品列表骨架屏 -->
    <div v-if="type === 'product-grid'" class="skeleton-grid">
      <div class="skeleton-card" v-for="i in count" :key="i">
        <div class="skeleton-image"></div>
        <div class="skeleton-info">
          <div class="skeleton-line title"></div>
          <div class="skeleton-line price"></div>
          <div class="skeleton-line meta"></div>
        </div>
      </div>
    </div>
    <!-- 商品详情骨架屏 -->
    <div v-else-if="type === 'product-detail'" class="skeleton-detail">
      <div class="skeleton-detail-image"></div>
      <div class="skeleton-detail-info">
        <div class="skeleton-line long"></div>
        <div class="skeleton-line medium"></div>
        <div class="skeleton-line short"></div>
        <div class="skeleton-line long"></div>
        <div class="skeleton-line medium"></div>
        <div class="skeleton-btn-group">
          <div class="skeleton-btn"></div>
          <div class="skeleton-btn"></div>
        </div>
      </div>
    </div>
    <!-- 列表骨架屏 -->
    <div v-else class="skeleton-list">
      <div class="skeleton-list-item" v-for="i in count" :key="i">
        <div class="skeleton-avatar"></div>
        <div class="skeleton-list-info">
          <div class="skeleton-line long"></div>
          <div class="skeleton-line medium"></div>
        </div>
        <div class="skeleton-list-right">
          <div class="skeleton-line short"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  type: { type: String, default: 'product-grid' },
  count: { type: Number, default: 10 }
})
</script>

<style scoped>
.skeleton-wrapper { width: 100%; }
.skeleton-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; }
.skeleton-card { background: #fff; border-radius: 8px; overflow: hidden; }
.skeleton-image { width: 100%; padding-top: 100%; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.skeleton-info { padding: 12px; }
.skeleton-line { height: 12px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin-bottom: 8px; }
.skeleton-line.title { height: 14px; width: 90%; }
.skeleton-line.price { height: 18px; width: 50%; }
.skeleton-line.meta { height: 10px; width: 70%; margin-bottom: 0; }
.skeleton-line.long { width: 100%; }
.skeleton-line.medium { width: 70%; }
.skeleton-line.short { width: 40%; }
.skeleton-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; background: #fff; padding: 30px; border-radius: 8px; }
.skeleton-detail-image { width: 100%; padding-top: 100%; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; }
.skeleton-detail-info { padding-top: 20px; }
.skeleton-btn-group { display: flex; gap: 12px; margin-top: 20px; }
.skeleton-btn { width: 140px; height: 40px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; }
.skeleton-list { background: #fff; border-radius: 8px; padding: 10px; }
.skeleton-list-item { display: flex; align-items: center; gap: 16px; padding: 16px; border-bottom: 1px solid #f5f5f5; }
.skeleton-list-item:last-child { border-bottom: none; }
.skeleton-avatar { width: 60px; height: 60px; border-radius: 8px; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; flex-shrink: 0; }
.skeleton-list-info { flex: 1; }
.skeleton-list-right { width: 100px; }
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>
