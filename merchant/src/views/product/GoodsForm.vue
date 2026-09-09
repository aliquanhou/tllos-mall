<template>
  <div class="goods-form">
    <el-card>
      <template #header>
        <div class="header-bar">
          <span>{{ form.id ? '编辑商品' : '新增商品' }}</span>
          <div>
            <el-button @click="showPreview = true" :disabled="!form.name">
              <el-icon><View /></el-icon> 预览商品
            </el-button>
            <el-button type="primary" @click="handleSubmit" :loading="loading">保存商品</el-button>
            <el-button @click="$router.back()">返回</el-button>
          </div>
        </div>
      </template>

      <el-form :model="form" :rules="rules" ref="formRef" label-width="100px">
        <el-form-item label="商品名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入商品名称" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="商品副标题">
          <el-input v-model="form.subtitle" placeholder="请输入商品副标题" maxlength="200" />
        </el-form-item>
        <el-form-item label="商品分类" prop="category_id">
          <el-select v-model="form.category_id" placeholder="请选择分类" style="width:100%">
            <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="商品主图" prop="main_image">
          <el-upload
            :action="uploadUrl" :headers="uploadHeaders" list-type="picture-card"
            :limit="1" :on-success="handleMainImageSuccess" :before-upload="beforeUpload"
            v-if="!form.main_image"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
          <div v-else class="main-image-preview">
            <el-image :src="form.main_image" style="width:100px;height:100px;border-radius:4px" fit="cover" :preview-src-list="[form.main_image]" />
            <el-button type="danger" size="small" @click="form.main_image = ''">删除</el-button>
          </div>
        </el-form-item>
        <el-form-item label="商品附图">
          <el-upload
            :action="uploadUrl" :headers="uploadHeaders" list-type="picture-card"
            :limit="5" :on-success="handleImagesSuccess" :before-upload="beforeUpload"
            :file-list="imageFileList" :on-remove="handleImageRemove"
          >
            <el-icon><Plus /></el-icon>
          </el-upload>
          <div class="tip">最多5张，建议尺寸800x800</div>
        </el-form-item>
        <el-form-item label="商品视频">
          <el-upload :action="videoUploadUrl" :headers="uploadHeaders" :limit="1"
            :on-success="handleVideoSuccess" :before-upload="beforeVideoUpload" accept="video/*">
            <el-button>上传视频</el-button>
          </el-upload>
          <div v-if="form.video" class="video-preview">
            <video :src="form.video" controls style="max-width:300px;margin-top:10px"></video>
            <el-button type="danger" size="small" @click="form.video = ''">删除视频</el-button>
          </div>
        </el-form-item>
        <el-form-item label="销售价" prop="price">
          <el-input-number v-model="form.price" :min="0.01" :precision="2" :step="1" style="width:200px" />
          <span class="unit">元</span>
        </el-form-item>
        <el-form-item label="市场价">
          <el-input-number v-model="form.market_price" :min="0" :precision="2" :step="1" style="width:200px" />
          <span class="unit">元</span>
        </el-form-item>
        <el-form-item label="库存" prop="stock">
          <el-input-number v-model="form.stock" :min="0" :step="1" style="width:200px" />
          <span class="unit">件</span>
        </el-form-item>
        <el-form-item label="商品重量">
          <el-input-number v-model="form.weight" :min="0" :precision="2" :step="0.1" style="width:200px" />
          <span class="unit">kg</span>
        </el-form-item>
        <el-form-item label="商品标签">
          <el-checkbox v-model="form.is_new">新品</el-checkbox>
          <el-checkbox v-model="form.is_hot">热销</el-checkbox>
          <el-checkbox v-model="form.is_recommend">推荐</el-checkbox>
        </el-form-item>
        <el-form-item label="是否多规格">
          <el-switch v-model="hasSku" @change="handleSkuSwitch" />
          <span class="tip">开启后可设置不同规格不同价格库存</span>
        </el-form-item>
        <el-form-item v-if="hasSku" label="规格设置">
          <div class="sku-manager">
            <div class="spec-list">
              <div v-for="(spec, sIndex) in specs" :key="sIndex" class="spec-item">
                <el-input v-model="spec.name" placeholder="规格名（如：颜色）" style="width:120px;margin-right:10px" />
                <el-input v-model="spec.values" placeholder="规格值，用逗号分隔（如：红色,蓝色）" style="width:300px;margin-right:10px" />
                <el-button type="danger" size="small" @click="removeSpec(sIndex)">删除</el-button>
              </div>
              <el-button type="primary" size="small" @click="addSpec" style="margin-top:10px">添加规格</el-button>
            </div>
            <el-button type="success" size="small" @click="generateSku" style="margin:10px 0">生成SKU</el-button>
            <el-table v-if="skuList.length" :data="skuList" border size="small">
              <el-table-column prop="spec_text" label="规格" />
              <el-table-column label="价格" width="150">
                <template #default="{ row }"><el-input-number v-model="row.price" :min="0.01" :precision="2" size="small" /></template>
              </el-table-column>
              <el-table-column label="库存" width="150">
                <template #default="{ row }"><el-input-number v-model="row.stock" :min="0" size="small" /></template>
              </el-table-column>
              <el-table-column label="SKU编码" width="180">
                <template #default="{ row }"><el-input v-model="row.sku_no" size="small" placeholder="可选" /></template>
              </el-table-column>
            </el-table>
          </div>
        </el-form-item>
        <el-form-item label="商品详情">
          <el-input v-model="form.description" type="textarea" :rows="10" placeholder="请输入商品详情，支持HTML" />
          <div class="tip">可粘贴HTML代码或纯文本描述</div>
        </el-form-item>
        <el-form-item label="商品状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">上架</el-radio>
            <el-radio :value="0">下架</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 商品预览弹窗 -->
    <el-dialog v-model="showPreview" title="商品预览" width="800px" top="5vh">
      <div class="goods-preview">
        <div class="preview-images">
          <el-image :src="form.main_image || 'https://picsum.photos/seed/placeholder/400/400.jpg'" style="width:100%;height:400px;border-radius:8px" fit="cover" />
          <div class="sub-images" v-if="form.images && form.images.length">
            <el-image v-for="(img, i) in form.images" :key="i" :src="img" style="width:60px;height:60px;margin:5px;border-radius:4px" fit="cover" />
          </div>
        </div>
        <div class="preview-info">
          <h2>{{ form.name || '商品名称' }}</h2>
          <p class="subtitle">{{ form.subtitle }}</p>
          <div class="price-row">
            <span class="price">¥{{ form.price || '0.00' }}</span>
            <span class="market-price" v-if="form.market_price">¥{{ form.market_price }}</span>
          </div>
          <div class="tags">
            <el-tag v-if="form.is_new" type="success" size="small">新品</el-tag>
            <el-tag v-if="form.is_hot" type="danger" size="small">热销</el-tag>
            <el-tag v-if="form.is_recommend" type="warning" size="small">推荐</el-tag>
            <el-tag :type="form.status === 1 ? 'success' : 'info'" size="small">{{ form.status === 1 ? '上架中' : '已下架' }}</el-tag>
          </div>
          <div class="meta">
            <span>库存：{{ form.stock }}件</span>
            <span v-if="form.weight">重量：{{ form.weight }}kg</span>
          </div>
          <div v-if="hasSku && skuList.length" class="sku-preview">
            <h4>规格选择</h4>
            <el-table :data="skuList" border size="small">
              <el-table-column prop="spec_text" label="规格" />
              <el-table-column prop="price" label="价格" width="100" />
              <el-table-column prop="stock" label="库存" width="80" />
            </el-table>
          </div>
          <div class="description" v-if="form.description">
            <h4>商品详情</h4>
            <div v-html="form.description"></div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus, View } from '@element-plus/icons-vue'
import request from '@/utils/request'

const route = useRoute()
const router = useRouter()
const formRef = ref()
const loading = ref(false)
const categories = ref([])
const showPreview = ref(false)
const hasSku = ref(false)
const specs = ref([{ name: '', values: '' }])
const skuList = ref([])

const uploadUrl = '/api/v1/upload/image'
const videoUploadUrl = '/api/v1/upload/video'
const uploadHeaders = computed(() => ({ Authorization: `Bearer ${localStorage.getItem('tllos_merchant_token')}` }))

const form = reactive({
  id: null, name: '', subtitle: '', category_id: null, main_image: '',
  images: [], video: '', price: 0, market_price: 0, stock: 0, weight: 0,
  description: '', status: 1, is_new: 0, is_hot: 0, is_recommend: 0, is_sku: 0,
})

const imageFileList = computed(() => (form.images || []).map((url, i) => ({ name: `图片${i+1}`, url })))

const rules = {
  name: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
  category_id: [{ required: true, message: '请选择商品分类', trigger: 'change' }],
  price: [{ required: true, message: '请输入销售价', trigger: 'blur' }],
  stock: [{ required: true, message: '请输入库存', trigger: 'blur' }],
}

const beforeUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt2M = file.size / 1024 / 1024 < 2
  if (!isImage) { ElMessage.error('只能上传图片文件'); return false }
  if (!isLt2M) { ElMessage.error('图片大小不能超过2M'); return false }
  return true
}
const beforeVideoUpload = (file) => {
  const isVideo = file.type.startsWith('video/')
  const isLt20M = file.size / 1024 / 1024 < 20
  if (!isVideo) { ElMessage.error('只能上传视频文件'); return false }
  if (!isLt20M) { ElMessage.error('视频大小不能超过20M'); return false }
  return true
}
const handleMainImageSuccess = (res) => {
  if (res.code === 200 || res.code === 0) { form.main_image = res.data?.url || res.url || ''; ElMessage.success('主图上传成功') }
  else ElMessage.error(res.message || '上传失败')
}
const handleImagesSuccess = (res) => {
  if (res.code === 200 || res.code === 0) { const url = res.data?.url || res.url || ''; if (url) form.images.push(url) }
}
const handleImageRemove = (file) => {
  const index = form.images.findIndex(img => img === file.url)
  if (index > -1) form.images.splice(index, 1)
}
const handleVideoSuccess = (res) => {
  if (res.code === 200 || res.code === 0) { form.video = res.data?.url || res.url || ''; ElMessage.success('视频上传成功') }
}
const addSpec = () => { specs.value.push({ name: '', values: '' }) }
const removeSpec = (index) => { specs.value.splice(index, 1) }
const handleSkuSwitch = (val) => { if (!val) { skuList.value = []; specs.value = [{ name: '', values: '' }] } }
const generateSku = () => {
  const validSpecs = specs.value.filter(s => s.name && s.values)
  if (!validSpecs.length) { ElMessage.warning('请先填写规格名和规格值'); return }
  const specArrays = validSpecs.map(s => ({ name: s.name, values: s.values.split(/[,，]/).map(v => v.trim()).filter(Boolean) }))
  const combinations = [{}]
  specArrays.forEach(spec => {
    const newCombinations = []
    combinations.forEach(combo => { spec.values.forEach(val => { newCombinations.push({ ...combo, [spec.name]: val }) }) })
    combinations.length = 0; combinations.push(...newCombinations)
  })
  skuList.value = combinations.map((combo, i) => ({
    spec_text: Object.entries(combo).map(([k, v]) => `${k}:${v}`).join(' '),
    spec_data: combo, price: form.price, stock: form.stock, sku_no: `SKU${Date.now()}${i}`,
  }))
  ElMessage.success(`已生成${skuList.value.length}个SKU`)
}

const loadCategories = async () => {
  try { const res = await request({ url: '/products/categories' }); categories.value = res.data?.list || res.data || [] } catch (e) { console.error(e) }
}

const loadGoodsDetail = async (id) => {
  try {
    const res = await request({ url: `/merchant/goods/${id}` })
    const data = res.data || {}
    // 确保images是数组
    if (data.images && typeof data.images === 'string') {
      try { data.images = JSON.parse(data.images) } catch (e) { data.images = [] }
    }
    if (!Array.isArray(data.images)) data.images = []
    // 处理SKU
    if (data.sku_list && typeof data.sku_list === 'string') {
      try { data.sku_list = JSON.parse(data.sku_list) } catch (e) { data.sku_list = [] }
    }
    if (data.sku_list && Array.isArray(data.sku_list) && data.sku_list.length > 0) {
      hasSku.value = true
      skuList.value = data.sku_list
    }
    if (data.is_sku) hasSku.value = true
    // 布尔值转换
    data.is_new = data.is_new ? 1 : 0
    data.is_hot = data.is_hot ? 1 : 0
    data.is_recommend = data.is_recommend ? 1 : 0
    Object.assign(form, data)
  } catch (e) { console.error('加载商品详情失败:', e); ElMessage.error('加载商品详情失败') }
}

const handleSubmit = async () => {
  await formRef.value.validate()
  loading.value = true
  try {
    const payload = { ...form, images: form.images, sku_list: hasSku.value ? skuList.value : [] }
    if (form.id) { await request({ url: `/merchant/goods/${form.id}`, method: 'put', data: payload }); ElMessage.success('商品更新成功') }
    else { await request({ url: '/merchant/goods', method: 'post', data: payload }); ElMessage.success('商品创建成功') }
    router.push('/product/list')
  } catch (e) { console.error(e) } finally { loading.value = false }
}

onMounted(() => {
  loadCategories()
  if (route.params.id) { form.id = route.params.id; loadGoodsDetail(route.params.id) }
})
</script>
<style scoped>
.goods-form { padding: 20px; }
.header-bar { display: flex; justify-content: space-between; align-items: center; }
.tip { color: #999; font-size: 12px; margin-top: 5px; }
.unit { margin-left: 10px; color: #666; }
.main-image-preview { display: flex; align-items: center; gap: 10px; }
.sku-manager { width: 100%; }
.spec-item { display: flex; align-items: center; margin-bottom: 10px; }
.video-preview { margin-top: 10px; }
.goods-preview { display: flex; gap: 20px; }
.preview-images { flex: 1; }
.sub-images { display: flex; flex-wrap: wrap; margin-top: 10px; }
.preview-info { flex: 1; }
.preview-info h2 { margin: 0 0 10px; }
.subtitle { color: #909399; margin: 0 0 15px; }
.price-row { display: flex; align-items: baseline; gap: 10px; margin-bottom: 15px; }
.price { font-size: 28px; color: #f56c6c; font-weight: 700; }
.market-price { color: #c0c4cc; text-decoration: line-through; }
.tags { display: flex; gap: 8px; margin-bottom: 15px; }
.meta { display: flex; gap: 20px; color: #606266; margin-bottom: 15px; }
.sku-preview, .description { margin-top: 20px; }
.sku-preview h4, .description h4 { margin-bottom: 10px; }
</style>
