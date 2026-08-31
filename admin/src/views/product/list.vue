<template>
  <div class="product-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="商品名称" clearable style="width: 200px" @keyup.enter="fetchList" />
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="searchForm.category_id" placeholder="全部分类" clearable style="width: 160px">
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部状态" clearable style="width: 120px">
            <el-option label="已上架" :value="1" />
            <el-option label="已下架" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="fetchList">搜索</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never" style="margin-top: 16px">
      <template #header>
        <div class="card-header">
          <span>商品列表（共 {{ total }} 件）</span>
        </div>
      </template>

      <el-table :data="list" v-loading="loading" stripe style="width: 100%">
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品信息" min-width="280">
          <template #default="{ row }">
            <div class="product-info">
              <el-image :src="row.main_image" fit="cover" style="width: 60px; height: 60px; border-radius: 4px" :preview-src-list="[row.main_image]">
                <template #error><div class="image-error">无图</div></template>
              </el-image>
              <div class="product-detail">
                <div class="product-name">{{ row.name }}</div>
                <div class="product-meta">
                  <el-tag size="small" type="info" v-if="row.category">{{ row.category.name }}</el-tag>
                  <span v-if="row.is_hot" style="color:#f56c6c;margin-left:8px">热</span>
                  <span v-if="row.is_new" style="color:#67c23a;margin-left:4px">新</span>
                  <span v-if="row.is_recommend" style="color:#e6a23c;margin-left:4px">荐</span>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="120">
          <template #default="{ row }">
            <div class="price">¥{{ row.price }}</div>
            <div class="market-price" v-if="row.market_price">¥{{ row.market_price }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="90" />
        <el-table-column prop="sales" label="销量" width="90" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleToggleStatus(row)" />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="170" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small">编辑</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="limit"
          :page-sizes="[10, 20, 50]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchList"
          @current-change="fetchList"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getProductList, deleteProduct, toggleProductStatus, getCategories } from '@/api/product'

const loading = ref(false)
const list = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(20)
const categories = ref([])

const searchForm = reactive({
  keyword: '',
  category_id: null,
  status: null,
})

const fetchList = async () => {
  loading.value = true
  try {
    const res = await getProductList({
      page: page.value,
      limit: limit.value,
      keyword: searchForm.keyword || undefined,
      category_id: searchForm.category_id || undefined,
      status: searchForm.status !== null ? searchForm.status : undefined,
    })
    list.value = res.data.list
    total.value = res.data.total
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const res = await getCategories()
    categories.value = res.data
  } catch (e) {
    console.error(e)
  }
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.category_id = null
  searchForm.status = null
  page.value = 1
  fetchList()
}

const handleToggleStatus = async (row) => {
  try {
    await toggleProductStatus(row.id)
    ElMessage.success(row.status == 1 ? '已上架' : '已下架')
  } catch (e) {
    row.status = row.status == 1 ? 0 : 1
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(`确定删除商品「${row.name}」？`, '提示', { type: 'warning' })
    await deleteProduct(row.id)
    ElMessage.success('删除成功')
    fetchList()
  } catch (e) {
    if (e !== 'cancel') console.error(e)
  }
}

onMounted(() => {
  fetchCategories()
  fetchList()
})
</script>

<style scoped>
.search-form { margin-bottom: 0; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.product-info { display: flex; gap: 12px; align-items: center; }
.product-detail { flex: 1; }
.product-name { font-size: 14px; color: #303133; margin-bottom: 4px; line-height: 1.4; }
.product-meta { display: flex; align-items: center; font-size: 12px; }
.price { color: #f56c6c; font-weight: bold; font-size: 16px; }
.market-price { color: #909399; font-size: 12px; text-decoration: line-through; }
.image-error { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background: #f5f7fa; color: #c0c4cc; font-size: 12px; border-radius: 4px; }
.pagination { margin-top: 20px; display: flex; justify-content: flex-end; }
</style>
