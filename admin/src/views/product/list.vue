<template>
  <div class="product-list">
    <el-card shadow="never">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="关键词"><el-input v-model="searchForm.keyword" placeholder="商品名称" clearable style="width:180px" @keyup.enter="fetchList" /></el-form-item>
        <el-form-item label="分类"><el-select v-model="searchForm.category_id" placeholder="全部分类" clearable style="width:140px"><el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" /></el-select></el-form-item>
        <el-form-item label="状态"><el-select v-model="searchForm.status" placeholder="全部" clearable style="width:100px"><el-option label="已上架" :value="1" /><el-option label="已下架" :value="0" /></el-select></el-form-item>
        <el-form-item label="推荐"><el-select v-model="searchForm.is_recommend" placeholder="全部" clearable style="width:100px"><el-option label="推荐" :value="1" /><el-option label="普通" :value="0" /></el-select></el-form-item>
        <el-form-item><el-button type="primary" @click="fetchList">搜索</el-button><el-button @click="resetSearch">重置</el-button></el-form-item>
      </el-form>
    </el-card>
    <el-card shadow="never" style="margin-top:16px">
      <template #header>
        <div class="card-header">
          <div class="header-left">
            <span>商品列表（共 {{ total }} 件）</span>
            <el-tag v-if="selectedIds.length > 0" type="info" style="margin-left:12px">已选 {{ selectedIds.length }} 件</el-tag>
          </div>
          <div class="header-right">
            <el-button v-if="selectedIds.length > 0" type="success" size="small" @click="batchUpdateStatus(1)">批量上架</el-button>
            <el-button v-if="selectedIds.length > 0" type="warning" size="small" @click="batchUpdateStatus(0)">批量下架</el-button>
            <el-button v-if="selectedIds.length > 0" type="danger" size="small" @click="batchDelete">批量删除</el-button>
            <el-button type="primary" @click="handleAdd">新增商品</el-button>
          </div>
        </div>
      </template>
      <el-table :data="list" v-loading="loading" stripe @selection-change="handleSelectionChange">
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品信息" min-width="300">
          <template #default="{row}">
            <div class="product-info">
              <div class="product-thumb" @click="handlePreview(row)">
                <img v-if="row.main_image" :src="row.main_image" class="thumb-img" @error="handleImgError($event)" />
                <div v-else class="thumb-placeholder">无图</div>
              </div>
              <div class="product-detail">
                <div class="product-name" @click="handlePreview(row)">{{ row.name }}</div>
                <div class="product-meta">
                  <el-tag size="small" type="info" v-if="row.category">{{ row.category.name }}</el-tag>
                  <el-tag size="small" type="danger" v-if="row.is_hot" style="margin-left:4px">热销</el-tag>
                  <el-tag size="small" type="success" v-if="row.is_new" style="margin-left:4px">新品</el-tag>
                  <el-tag size="small" type="warning" v-if="row.is_recommend" style="margin-left:4px">推荐</el-tag>
                  <el-tag size="small" v-if="row.is_sku" style="margin-left:4px;background:#f0f2f5;color:#606266">多规格</el-tag>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="130">
          <template #default="{row}">
            <div class="price">¥{{ row.price }}</div>
            <div class="market-price" v-if="row.market_price">¥{{ row.market_price }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="80" />
        <el-table-column prop="sales" label="销量" width="80" />
        <el-table-column prop="views" label="浏览" width="80" />
        <el-table-column label="状态" width="90">
          <template #default="{row}"><el-switch v-model="row.status" :active-value="1" :inactive-value="0" @change="handleToggleStatus(row)" /></template>
        </el-table-column>
        <el-table-column label="创建时间" width="160">
          <template #default="{row}">{{ formatDate(row.created_at) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{row}">
            <el-button type="primary" link size="small" @click="handlePreview(row)">预览</el-button>
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link size="small" @click="handleCopy(row)">复制</el-button>
            <el-button type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pagination"><el-pagination v-model:current-page="page" v-model:page-size="limit" :page-sizes="[10,20,50,100]" :total="total" layout="total, sizes, prev, pager, next, jumper" @size-change="fetchList" @current-change="fetchList" /></div>
    </el-card>

    <!-- 商品预览弹窗 -->
    <el-dialog v-model="previewVisible" title="商品预览" width="800px" top="5vh">
      <div v-if="previewProduct" class="product-preview">
        <div class="preview-header">
          <h2>{{ previewProduct.name }}</h2>
          <p class="preview-subtitle">{{ previewProduct.subtitle }}</p>
        </div>
        <div class="preview-body">
          <div class="preview-images">
            <el-carousel height="350px" :interval="4000">
              <el-carousel-item v-for="(img, idx) in previewImages" :key="idx">
                <img :src="img" style="width:100%;height:350px;object-fit:contain;background:#f5f7fa" @error="handleImgError($event)" />
              </el-carousel-item>
            </el-carousel>
            <div v-if="previewProduct.video" class="preview-video">
              <video :src="previewProduct.video" controls style="width:100%;max-height:200px;margin-top:12px;border-radius:4px" />
            </div>
          </div>
          <div class="preview-info">
            <div class="price-section">
              <span class="current-price">¥{{ previewProduct.price }}</span>
              <span class="original-price" v-if="previewProduct.market_price">¥{{ previewProduct.market_price }}</span>
            </div>
            <el-descriptions :column="2" border size="small" style="margin-top:16px">
              <el-descriptions-item label="分类">{{ previewProduct.category?.name || '-' }}</el-descriptions-item>
              <el-descriptions-item label="库存">{{ previewProduct.stock }}{{ previewProduct.unit }}</el-descriptions-item>
              <el-descriptions-item label="销量">{{ previewProduct.sales }}</el-descriptions-item>
              <el-descriptions-item label="浏览">{{ previewProduct.views }}</el-descriptions-item>
              <el-descriptions-item label="收藏">{{ previewProduct.favorites }}</el-descriptions-item>
              <el-descriptions-item label="重量">{{ previewProduct.weight }}kg</el-descriptions-item>
              <el-descriptions-item label="是否多规格">{{ previewProduct.is_sku ? '是' : '否' }}</el-descriptions-item>
              <el-descriptions-item label="状态">{{ previewProduct.status ? '已上架' : '已下架' }}</el-descriptions-item>
            </el-descriptions>
            <div class="preview-tags" style="margin-top:12px">
              <el-tag v-if="previewProduct.is_hot" type="danger">热销</el-tag>
              <el-tag v-if="previewProduct.is_new" type="success" style="margin-left:8px">新品</el-tag>
              <el-tag v-if="previewProduct.is_recommend" type="warning" style="margin-left:8px">推荐</el-tag>
            </div>
          </div>
        </div>
        <div class="preview-detail" style="margin-top:20px">
          <el-divider content-position="left">商品详情</el-divider>
          <div class="detail-content" v-html="previewProduct.description"></div>
        </div>
      </div>
    </el-dialog>

    <!-- 商品编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="isEdit?'编辑商品':'新增商品'" width="900px" @close="resetForm" top="5vh">
      <el-form :model="form" label-width="100px">
        <el-divider content-position="left">基本信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12"><el-form-item label="商品名称"><el-input v-model="form.name" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="副标题"><el-input v-model="form.subtitle" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="8"><el-form-item label="商品分类"><el-select v-model="form.category_id" style="width:100%"><el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" /></el-select></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="商品品牌"><el-select v-model="form.brand_id" clearable style="width:100%"><el-option v-for="b in brands" :key="b.id" :label="b.name" :value="b.id" /></el-select></el-form-item></el-col>
          <el-col :span="8"><el-form-item label="计量单位"><el-input v-model="form.unit" /></el-form-item></el-col>
        </el-row>
        <el-divider content-position="left">商品图片</el-divider>
        <el-form-item label="主图/轮播图"><ImageUpload v-model="form.images" :max="9" /><div class="form-tip">第一张为主图，最多上传9张</div></el-form-item>
        <el-form-item label="商品视频"><VideoUpload v-model="form.video" /></el-form-item>
        <el-divider content-position="left">价格库存</el-divider>
        <el-row :gutter="20">
          <el-col :span="6"><el-form-item label="销售价"><el-input-number v-model="form.price" :min="0" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="市场价"><el-input-number v-model="form.market_price" :min="0" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="成本价"><el-input-number v-model="form.cost_price" :min="0" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="库存"><el-input-number v-model="form.stock" :min="0" style="width:100%" /></el-form-item></el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="6"><el-form-item label="重量(kg)"><el-input-number v-model="form.weight" :min="0" :precision="2" style="width:100%" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="免运费"><el-switch v-model="form.is_free_shipping" :active-value="1" :inactive-value="0" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="运费"><el-input-number v-model="form.shipping_fee" :min="0" :precision="2" style="width:100%" /></el-form-item></el-col>
        </el-row>
        <el-divider content-position="left">SKU规格</el-divider>
        <SkuManager v-model="form.skus" v-model:is-sku="form.is_sku" />
        <el-divider content-position="left">商品详情</el-divider>
        <el-form-item label="商品详情"><RichEditor v-model="form.description" placeholder="请输入商品详情..." /></el-form-item>
        <el-divider content-position="left">其他设置</el-divider>
        <el-row :gutter="20">
          <el-col :span="6"><el-form-item label="是否热销"><el-switch v-model="form.is_hot" :active-value="1" :inactive-value="0" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="是否新品"><el-switch v-model="form.is_new" :active-value="1" :inactive-value="0" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="是否推荐"><el-switch v-model="form.is_recommend" :active-value="1" :inactive-value="0" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="上架状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item></el-col>
        </el-row>
      </el-form>
      <template #footer><el-button @click="dialogVisible=false">取消</el-button><el-button type="primary" @click="handleSubmit" :loading="submitting">确定保存</el-button></template>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getProductList, createProduct, updateProduct, deleteProduct, toggleProductStatus, batchUpdateProducts, batchDeleteProducts } from '@/api/product'
import { getCategoryTree } from '@/api/category'
import RichEditor from '@/components/RichEditor.vue'
import ImageUpload from '@/components/ImageUpload.vue'
import VideoUpload from '@/components/VideoUpload.vue'
import SkuManager from '@/components/SkuManager.vue'

const list = ref([]); const total = ref(0); const loading = ref(false); const categories = ref([]); const brands = ref([])
const page = ref(1); const limit = ref(20)
const searchForm = reactive({ keyword:'', category_id:null, status:null, is_recommend:null })
const dialogVisible = ref(false); const isEdit = ref(false); const submitting = ref(false)
const selectedIds = ref([]); const selectedRows = ref([])
const previewVisible = ref(false); const previewProduct = ref(null)

const previewImages = computed(() => {
  if (!previewProduct.value) return []
  const imgs = previewProduct.value.images
  if (Array.isArray(imgs) && imgs.length > 0) return imgs
  if (previewProduct.value.main_image) return [previewProduct.value.main_image]
  return []
})

const defaultForm = () => ({ id:null, name:'', subtitle:'', category_id:null, brand_id:0, unit:'件', images:[], video:'', price:0, market_price:0, cost_price:0, stock:0, weight:0, is_free_shipping:0, shipping_fee:0, is_sku:0, skus:[], description:'', is_hot:0, is_new:0, is_recommend:0, status:1 })
const form = ref(defaultForm())

const formatDate = (d) => { if(!d) return ''; return new Date(d).toLocaleString('zh-CN') }
const handleImgError = (e) => { e.target.style.display='none'; e.target.parentElement.innerHTML='<div style="width:60px;height:60px;display:flex;align-items:center;justify-content:center;background:#f5f7fa;color:#909399;font-size:12px">无图</div>' }

const fetchList = async () => { loading.value=true; try { const res = await getProductList({page:page.value,limit:limit.value,...searchForm}); list.value=res.data.list||[]; total.value=res.data.total||0 } finally { loading.value=false } }
const fetchCategories = async () => { const res = await getCategoryTree(); categories.value=res.data||[] }
const resetSearch = () => { searchForm.keyword=''; searchForm.category_id=null; searchForm.status=null; searchForm.is_recommend=null; page.value=1; fetchList() }
const resetForm = () => { form.value = defaultForm() }
const handleSelectionChange = (rows) => { selectedRows.value=rows; selectedIds.value=rows.map(r=>r.id) }

const handleAdd = () => { isEdit.value=false; resetForm(); dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value = { ...defaultForm(), ...row, images: row.images ? (typeof row.images==='string' ? JSON.parse(row.images) : row.images) : (row.main_image ? [row.main_image] : []) }; dialogVisible.value=true }
const handlePreview = row => { previewProduct.value = row; previewVisible.value=true }
const handleCopy = async row => { await ElMessageBox.confirm(`确定复制商品"${row.name}"？`,'提示',{type:'info'}); const copyData = {...row, id:null, name:row.name+' (副本)', status:0, sales:0, views:0, favorites:0}; await createProduct(copyData); ElMessage.success('复制成功'); fetchList() }
const handleSubmit = async () => { if(!form.value.name){ElMessage.warning('请输入商品名称');return}; if(!form.value.category_id){ElMessage.warning('请选择商品分类');return}; submitting.value=true; try { const submitData = { ...form.value, main_image: form.value.images[0] || '' }; if(isEdit.value){await updateProduct(form.value.id,submitData);ElMessage.success('更新成功')}else{await createProduct(submitData);ElMessage.success('创建成功')}; dialogVisible.value=false; fetchList() } finally { submitting.value=false } }
const handleDelete = async row => { await ElMessageBox.confirm(`确定删除商品"${row.name}"？`,'提示',{type:'warning'}); await deleteProduct(row.id); ElMessage.success('删除成功'); fetchList() }
const handleToggleStatus = async row => { await toggleProductStatus(row.id,{status:row.status}); ElMessage.success('状态更新成功') }
const batchUpdateStatus = async (status) => { await ElMessageBox.confirm(`确定批量${status?'上架':'下架'} ${selectedIds.value.length} 件商品？`,'提示',{type:'info'}); await batchUpdateProducts({ids:selectedIds.value,status}); ElMessage.success('批量操作成功'); fetchList() }
const batchDelete = async () => { await ElMessageBox.confirm(`确定批量删除 ${selectedIds.value.length} 件商品？此操作不可恢复！`,'警告',{type:'warning'}); await batchDeleteProducts({ids:selectedIds.value}); ElMessage.success('批量删除成功'); selectedIds.value=[]; fetchList() }

onMounted(() => { fetchCategories(); fetchList() })
</script>
<style scoped>
.search-form{margin-bottom:0}
.card-header{display:flex;justify-content:space-between;align-items:center}
.header-left{display:flex;align-items:center}
.header-right{display:flex;gap:8px}
.product-info{display:flex;gap:12px;align-items:center}
.product-thumb{cursor:pointer;flex-shrink:0}
.thumb-img{width:60px;height:60px;border-radius:4px;object-fit:cover;border:1px solid #ebeef5}
.thumb-placeholder{width:60px;height:60px;display:flex;align-items:center;justify-content:center;background:#f5f7fa;color:#909399;font-size:12px;border-radius:4px}
.product-detail{flex:1;min-width:0}
.product-name{font-weight:500;margin-bottom:4px;cursor:pointer;color:#303133}
.product-name:hover{color:#409eff}
.product-meta{display:flex;align-items:center;flex-wrap:wrap;gap:4px}
.price{color:#f56c6c;font-weight:600}
.market-price{color:#909399;text-decoration:line-through;font-size:12px}
.pagination{margin-top:16px;display:flex;justify-content:flex-end}
.form-tip{font-size:12px;color:#909399;margin-top:4px}
.preview-header h2{margin:0 0 8px 0;font-size:20px}
.preview-subtitle{color:#909399;margin:0}
.preview-body{display:flex;gap:20px;margin-top:16px}
.preview-images{flex:1;min-width:0}
.preview-info{flex:1;min-width:0}
.price-section{display:flex;align-items:baseline;gap:12px}
.current-price{font-size:28px;color:#f56c6c;font-weight:700}
.original-price{font-size:16px;color:#909399;text-decoration:line-through}
.detail-content{line-height:1.8;color:#606266}
.detail-content img{max-width:100%}
</style>
