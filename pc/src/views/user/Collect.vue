<template>
  <div class="collect-page">
    <div class="goods-grid">
      <div v-for="item in list" :key="item.id" class="goods-card" @click="$router.push(`/product/${item.goods_id}`)">
        <img :src="item.main_image" class="goods-img" />
        <div class="goods-name">{{item.name}}</div>
        <div class="goods-price">¥{{item.price}}</div>
        <button class="btn btn-sm btn-danger" @click.stop="handleCancel(item)">取消收藏</button>
      </div>
      <div v-if="list.length===0" class="empty">暂无收藏</div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const list = ref([])
const fetchList = async () => { const res = await request({url:'/user/collects'}); list.value = res.data.list || [] }
const handleCancel = async item => { if(confirm('确定取消收藏？')){await request({url:'/user/collects/cancel',method:'post',data:{goods_id:item.goods_id}}); alert('已取消'); fetchList()} }
onMounted(fetchList)
</script>
<style scoped>.collect-page{min-height:100vh;background:#f5f5f5;padding:12px}.goods-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.goods-card{background:#fff;border-radius:12px;padding:12px;text-align:center}.goods-img{width:100%;height:140px;border-radius:8px;object-fit:cover}.goods-name{font-size:14px;margin:8px 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.goods-price{color:#f56c6c;font-weight:700;margin-bottom:8px}.empty{grid-column:1/-1;text-align:center;padding:60px 0;color:#999}.btn{padding:8px 16px;border:1px solid #ddd;background:#fff;border-radius:6px;cursor:pointer;font-size:14px}.btn-sm{padding:4px 12px;font-size:12px}.btn-danger{background:#f56c6c;color:#fff;border-color:#f56c6c}</style>
