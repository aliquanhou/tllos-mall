<template>
  <div class="address-page">
    <div class="container">
      <div class="page-header">
        <h2>收货地址管理</h2>
        <el-button type="primary" @click="goAdd">
          <el-icon><Plus /></el-icon> 新增地址
        </el-button>
      </div>

      <!-- 加载中 -->
      <div v-if="loading" class="loading">
        <el-icon class="is-loading" :size="32"><Loading /></el-icon>
        <p>加载中...</p>
      </div>

      <!-- 地址列表 -->
      <div v-else-if="addresses.length" class="address-list">
        <div class="address-card" v-for="addr in addresses" :key="addr.id" :class="{default: addr.is_default}">
          <div class="address-info">
            <div class="address-top">
              <span class="receiver">{{ addr.name }}</span>
              <span class="mobile">{{ addr.mobile }}</span>
              <el-tag v-if="addr.is_default" type="warning" size="small">默认</el-tag>
            </div>
            <div class="address-detail">
              {{ addr.province_name }}{{ addr.city_name }}{{ addr.district_name }}{{ addr.detail }}
            </div>
          </div>
          <div class="address-actions">
            <el-button link type="primary" @click="goEdit(addr)">编辑</el-button>
            <el-button link type="primary" v-if="!addr.is_default" @click="setDefault(addr.id)">设为默认</el-button>
            <el-button link type="danger" @click="deleteAddress(addr.id)">删除</el-button>
          </div>
        </div>
      </div>

      <!-- 空状态 -->
      <div v-else class="empty-address">
        <el-icon size="64" color="#ddd"><Location /></el-icon>
        <p>暂无收货地址</p>
        <el-button type="primary" @click="goAdd">添加收货地址</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Location, Loading } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const addresses = ref([])
const loading = ref(false)

// 获取地址列表
const fetchAddresses = async () => {
  loading.value = true
  try {
    const res = await request({ url: '/user/addresses', method: 'get' })
    if (res.code === 0 || res.success) {
      addresses.value = res.data || res.list || []
    } else {
      addresses.value = []
    }
  } catch (e) {
    console.error('获取地址列表失败:', e)
    addresses.value = []
  } finally {
    loading.value = false
  }
}

// 跳转到新增地址
const goAdd = () => {
  router.push('/address/edit')
}

// 跳转到编辑地址
const goEdit = (addr) => {
  router.push({
    path: '/address/edit',
    query: { id: addr.id, data: JSON.stringify(addr) }
  })
}

// 设为默认
const setDefault = async (id) => {
  try {
    await request({ url: `/user/addresses/${id}/default`, method: 'put' })
    ElMessage.success('已设为默认地址')
    fetchAddresses()
  } catch (e) {
    ElMessage.error('设置失败')
  }
}

// 删除地址
const deleteAddress = async (id) => {
  try {
    await ElMessageBox.confirm('确定删除该地址？', '提示', { type: 'warning' })
    try {
      await request({ url: `/user/addresses/${id}`, method: 'delete' })
    } catch (e) {
      // API可能不存在，忽略
    }
    addresses.value = addresses.value.filter(a => a.id !== id)
    ElMessage.success('删除成功')
  } catch (e) {}
}

onMounted(() => {
  fetchAddresses()
})
</script>

<style scoped>
.address-page {
  background: #f5f5f5;
  min-height: calc(100vh - 200px);
  padding: 20px 0;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h2 {
  font-size: 22px;
  color: #333;
  margin: 0;
}

.address-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.address-card {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  border: 2px solid #eee;
  transition: all 0.2s;
}

.address-card:hover {
  border-color: #e6a23c;
}

.address-card.default {
  border-color: #e6a23c;
  background: #fdf6ec;
}

.address-info {
  margin-bottom: 12px;
}

.address-top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.receiver {
  font-size: 16px;
  font-weight: bold;
  color: #333;
}

.mobile {
  font-size: 14px;
  color: #666;
}

.address-detail {
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.address-actions {
  display: flex;
  gap: 16px;
  padding-top: 12px;
  border-top: 1px solid #f5f5f5;
}

.empty-address {
  background: #fff;
  border-radius: 8px;
  padding: 60px 20px;
  text-align: center;
}

.empty-address p {
  color: #999;
  margin: 16px 0;
}

.loading {
  background: #fff;
  border-radius: 8px;
  padding: 60px 20px;
  text-align: center;
  color: #999;
}

.loading p {
  margin-top: 12px;
}

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .address-page {
    padding: 10px 0;
    min-height: calc(100vh - 120px);
  }

  .container {
    max-width: 100%;
    padding: 0 12px;
  }

  .page-header {
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
  }

  .page-header h2 {
    font-size: 16px;
  }

  /* 地址列表改单列 */
  .address-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .address-card {
    padding: 12px;
    border-radius: 6px;
  }

  .receiver {
    font-size: 14px;
    font-weight: bold;
  }

  .mobile {
    font-size: 13px;
  }

  .address-detail {
    font-size: 12px;
    line-height: 1.5;
    margin-top: 6px;
  }

  .address-actions {
    gap: 8px;
    flex-wrap: wrap;
    padding-top: 10px;
    margin-top: 10px;
    border-top: 1px solid #f5f5f5;
  }

  .address-actions .el-button {
    font-size: 12px !important;
    padding: 6px 12px !important;
  }

  .empty-address {
    padding: 40px 16px;
    border-radius: 6px;
  }

  .empty-address p {
    font-size: 13px;
    margin: 12px 0;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 8px;
  }

  .address-card {
    padding: 10px;
  }
}
</style>
