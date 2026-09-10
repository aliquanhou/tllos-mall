<template>
  <div class="goods-list">
    <el-card>
      <template #header>
        <div class="header">
          <span>商品管理（共{{ total }}件）</span>
          <div>
            <el-button type="primary" @click="$router.push('/product/create')">
              <el-icon><Plus /></el-icon> 新增商品
            </el-button>
          </div>
        </div>
      </template>
      
      <div class="search-bar">
        <el-input v-model="search.keyword" placeholder="搜索商品名称" style="width:200px;margin-right:10px" clearable @keyup.enter="loadList" />
        <el-select v-model="search.status" placeholder="商品状态" style="width:120px;margin-right:10px" clearable>
          <el-option label="上架" :value="1" />
          <el-option label="下架" :value="0" />
        </el-select>
        <el-button type="primary" @click="loadList">搜索</el-button>
      </div>

      <div class="batch-bar" v-if="selectedIds.length">
        <span>已选{{ selectedIds.length }}件</span>
        <el-button size="small" type="success" @click="batchUpdateStatus(1)">批量上架</el-button>
        <el-button size="small" type="warning" @click="batchUpdateStatus(0)">批量下架</el-button>
        <el-button size="small" type="danger" @click="batchDelete">批量删除</el-button>
      </div>

      <el-table :data="list" border style="margin-top:15px" @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品信息" min-width="280">
          <template #default="{ row }">
            <div class="goods-info">
              <el-image v-if="row.main_image" :src="row.main_image" style="width:60px;height:60px;border-radius:4px" fit="cover" :preview-src-list="[row.main_image]" />
              <div v-else class="no-image">无图</div>
              <div class="goods-detail">
                <div class="goods-name">{{ row.name }}</div>
                <div class="goods-sub" v-if="row.subtitle">{{ row.subtitle }}</div>
                <div class="goods-tags">
                  <el-tag v-if="row.is_new" type="success" size="small">新</el-tag>
                  <el-tag v-if="row.is_hot" type="danger" size="small">热</el-tag>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="140">
          <template #default="{ row }">
            <div class="price">¥{{ row.price }}</div>
            <div class="market-price" v-if="row.market_price">¥{{ row.market_price }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="80" />
        <el-table-column prop="sales" label="销量" width="80" />
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
              {{ row.status === 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="$router.push(`/product/edit/${row.id}`)">编辑</el-button>
            <el-button size="small" type="success" @click="toggleStatus(row)">
              {{ row.status === 1 ? '下架' : '上架' }}
            </el-button>
            <el-button size="small" type="danger" @click="deleteGoods(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="page"
        v-model:page-size="limit"
        :total="total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="loadList"
        @current-change="loadList"
        style="margin-top:20px;justify-content:flex-end"
      />
    </el-card>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const list = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const selectedIds = ref([])
const search = reactive({ keyword: '', status: null })

const loadList = async () => {
  try {
    const params = { page: page.value, limit: limit.value, ...search }
    const res = await request({ url: '/merchant/goods', params })
    list.value = res.data?.list || res.data || []
    total.value = res.data?.total || list.value.length
  } catch (e) { console.error(e) }
}

const handleSelectionChange = (rows) => {
  selectedIds.value = rows.map(r => r.id)
}

const toggleStatus = async (row) => {
  try {
    await request({ url: `/merchant/goods/${row.id}`, method: 'put', data: { status: row.status === 1 ? 0 : 1 } })
    ElMessage.success('状态更新成功')
    loadList()
  } catch (e) { console.error(e) }
}

const deleteGoods = async (row) => {
  await ElMessageBox.confirm(`确定删除商品"${row.name}"？`, '提示', { type: 'warning' })
  try {
    await request({ url: `/merchant/goods/${row.id}`, method: 'delete' })
    ElMessage.success('删除成功')
    loadList()
  } catch (e) { console.error(e) }
}

const batchUpdateStatus = async (status) => {
  try {
    await Promise.all(selectedIds.value.map(id => 
      request({ url: `/merchant/goods/${id}`, method: 'put', data: { status } })
    ))
    ElMessage.success('批量操作成功')
    loadList()
  } catch (e) { console.error(e) }
}

const batchDelete = async () => {
  await ElMessageBox.confirm(`确定删除选中的${selectedIds.value.length}件商品？`, '提示', { type: 'warning' })
  try {
    await Promise.all(selectedIds.value.map(id => 
      request({ url: `/merchant/goods/${id}`, method: 'delete' })
    ))
    ElMessage.success('批量删除成功')
    loadList()
  } catch (e) { console.error(e) }
}

onMounted(() => { loadList() })
</script>
<style scoped>
.goods-list { padding: 20px; }
.header { display: flex; justify-content: space-between; align-items: center; }
.search-bar { display: flex; align-items: center; margin-bottom: 15px; }
.batch-bar { display: flex; align-items: center; gap: 10px; padding: 10px; background: #ecf5ff; border-radius: 4px; margin-bottom: 10px; }
.goods-info { display: flex; align-items: center; gap: 10px; }
.no-image { width: 60px; height: 60px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px; border-radius: 4px; }
.goods-detail { flex: 1; }
.goods-name { font-weight: 500; color: #303133; margin-bottom: 4px; }
.goods-sub { color: #909399; font-size: 12px; margin-bottom: 4px; }
.goods-tags { display: flex; gap: 4px; }
.price { color: #f56c6c; font-weight: 600; }
.market-price { color: #c0c4cc; text-decoration: line-through; font-size: 12px; }
</style>
