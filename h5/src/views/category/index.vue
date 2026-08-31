<template>
  <div class="category-page">
    <div class="cat-sidebar">
      <div v-for="(cat, i) in categories" :key="cat.id"
        :class="['cat-tab', { active: currentCat === i }]"
        @click="currentCat = i">{{ cat.name }}</div>
    </div>
    <div class="cat-content">
      <h3 class="cat-title">{{ categories[currentCat]?.name }}</h3>
      <div class="sub-grid">
        <div v-for="sub in categories[currentCat]?.subs" :key="sub.id" class="sub-item">
          <div class="sub-icon" :style="{ background: sub.color }">{{ sub.icon }}</div>
          <span>{{ sub.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
const currentCat = ref(0)
const categories = [
  { id: 1, name: '数码电器', subs: [
    { id: 11, name: '手机', icon: '📱', color: '#e3f2fd' },
    { id: 12, name: '电脑', icon: '💻', color: '#e3f2fd' },
    { id: 13, name: '耳机', icon: '🎧', color: '#e3f2fd' },
    { id: 14, name: '相机', icon: '📷', color: '#e3f2fd' },
    { id: 15, name: '智能设备', icon: '⌚', color: '#e3f2fd' },
    { id: 16, name: '配件', icon: '🔌', color: '#e3f2fd' }
  ]},
  { id: 2, name: '服饰鞋包', subs: [
    { id: 21, name: '男装', icon: '👔', color: '#fce4ec' },
    { id: 22, name: '女装', icon: '👗', color: '#fce4ec' },
    { id: 23, name: '鞋靴', icon: '👟', color: '#fce4ec' },
    { id: 24, name: '箱包', icon: '👜', color: '#fce4ec' },
    { id: 25, name: '配饰', icon: '⌚', color: '#fce4ec' },
    { id: 26, name: '内衣', icon: '🩲', color: '#fce4ec' }
  ]},
  { id: 3, name: '美妆护肤', subs: [
    { id: 31, name: '护肤', icon: '🧴', color: '#f3e5f5' },
    { id: 32, name: '彩妆', icon: '💄', color: '#f3e5f5' },
    { id: 33, name: '香水', icon: '🌸', color: '#f3e5f5' },
    { id: 34, name: '工具', icon: '🪞', color: '#f3e5f5' }
  ]},
  { id: 4, name: '食品生鲜', subs: [
    { id: 41, name: '零食', icon: '🍪', color: '#e8f5e9' },
    { id: 42, name: '饮料', icon: '🥤', color: '#e8f5e9' },
    { id: 43, name: '生鲜', icon: '🥩', color: '#e8f5e9' },
    { id: 44, name: '粮油', icon: '🍚', color: '#e8f5e9' }
  ]},
  { id: 5, name: '家居家装', subs: [
    { id: 51, name: '家具', icon: '🛋️', color: '#fff3e0' },
    { id: 52, name: '家纺', icon: '🛏️', color: '#fff3e0' },
    { id: 53, name: '厨具', icon: '🍳', color: '#fff3e0' },
    { id: 54, name: '收纳', icon: '📦', color: '#fff3e0' }
  ]},
  { id: 6, name: '运动户外', subs: [
    { id: 61, name: '健身', icon: '🏋️', color: '#e0f7fa' },
    { id: 62, name: '户外', icon: '⛺', color: '#e0f7fa' },
    { id: 63, name: '骑行', icon: '🚴', color: '#e0f7fa' },
    { id: 64, name: '球类', icon: '⚽', color: '#e0f7fa' }
  ]}
]
</script>
<style scoped>
.category-page { display: flex; height: 100%; }
.cat-sidebar { width: 90px; background: #fff; overflow-y: auto; flex-shrink: 0; }
.cat-tab { padding: 16px 10px; text-align: center; font-size: 13px; color: var(--text-secondary); border-left: 3px solid transparent; }
.cat-tab.active { background: var(--bg); color: var(--primary); border-left-color: var(--primary); font-weight: 500; }
.cat-content { flex: 1; padding: 16px; overflow-y: auto; }
.cat-title { font-size: 16px; margin-bottom: 16px; }
.sub-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.sub-item { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.sub-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.sub-item span { font-size: 12px; color: var(--text); }
</style>
