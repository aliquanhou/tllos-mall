<template>
  <div class="decoration-editor">
    <!-- 顶部工具栏 -->
    <div class="editor-header">
      <el-button @click="$router.back()">返回</el-button>
      <span class="title">{{ page?.name || '页面装修' }}</span>
      <div class="actions">
        <el-button @click="preview">预览</el-button>
        <el-button type="primary" @click="save" :loading="saving">保存</el-button>
      </div>
    </div>

    <div class="editor-body">
      <!-- 左侧组件列表 -->
      <div class="component-panel">
        <div class="panel-title">组件库</div>
        <div class="component-list">
          <div v-for="comp in componentTypes" :key="comp.type" class="component-item" draggable="true" @dragstart="onDragStart($event, comp)" @click="addComponent(comp)">
            <el-icon :size="20"><component :is="comp.icon" /></el-icon>
            <span>{{ comp.name }}</span>
          </div>
        </div>
      </div>

      <!-- 中间画布 -->
      <div class="canvas-panel" @dragover.prevent @drop="onDrop">
        <div class="phone-frame">
          <div class="phone-screen">
            <div v-if="components.length===0" class="empty-tip">
              <el-icon :size="48"><Plus /></el-icon>
              <p>从左侧拖拽组件到这里</p>
            </div>
            <div v-for="(comp, index) in components" :key="index" class="canvas-component" :class="{active: selectedIndex===index}" @click="selectComponent(index)">
              <div class="component-toolbar">
                <span>{{ comp.name }}</span>
                <div>
                  <el-button size="small" text @click.stop="moveUp(index)" :disabled="index===0"><el-icon><Top /></el-icon></el-button>
                  <el-button size="small" text @click.stop="moveDown(index)" :disabled="index===components.length-1"><el-icon><Bottom /></el-icon></el-button>
                  <el-button size="small" text type="danger" @click.stop="removeComponent(index)"><el-icon><Delete /></el-icon></el-button>
                </div>
              </div>
              <div class="component-preview">
                <!-- 轮播图预览 -->
                <div v-if="comp.type==='banner'" class="preview-banner">
                  <el-carousel height="120px">
                    <el-carousel-item v-for="(img, i) in (comp.config.images||defaultImages)" :key="i">
                      <el-image :src="img" fit="cover" style="width:100%;height:120px" />
                    </el-carousel-item>
                  </el-carousel>
                </div>
                <!-- 导航预览 -->
                <div v-else-if="comp.type==='nav'" class="preview-nav">
                  <div v-for="n in (comp.config.columns||5)" :key="n" class="nav-item">
                    <div class="nav-icon">图标</div>
                    <div class="nav-text">导航{{n}}</div>
                  </div>
                </div>
                <!-- 商品推荐预览 -->
                <div v-else-if="comp.type==='goods'" class="preview-goods">
                  <div class="goods-title">{{ comp.config.title || '商品推荐' }}</div>
                  <div class="goods-grid" :style="{gridTemplateColumns: `repeat(${comp.config.columns||2}, 1fr)`}">
                    <div v-for="g in (comp.config.limit||4)" :key="g" class="goods-item">
                      <div class="goods-img">商品图</div>
                      <div class="goods-name">商品名称</div>
                      <div class="goods-price">¥99.00</div>
                    </div>
                  </div>
                </div>
                <!-- 搜索框预览 -->
                <div v-else-if="comp.type==='search'" class="preview-search">
                  <el-input placeholder="搜索商品" size="small" />
                </div>
                <!-- 公告预览 -->
                <div v-else-if="comp.type==='notice'" class="preview-notice">
                  <el-icon><Bell /></el-icon>
                  <span>{{ comp.config.text || '商城公告' }}</span>
                </div>
                <!-- 优惠券预览 -->
                <div v-else-if="comp.type==='coupon'" class="preview-coupon">
                  <div v-for="c in 3" :key="c" class="coupon-item">
                    <div class="coupon-amount">¥10</div>
                    <div class="coupon-info">满100可用</div>
                  </div>
                </div>
                <!-- 通用预览 -->
                <div v-else class="preview-default">{{ comp.name }}组件</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧属性配置 -->
      <div class="property-panel">
        <div class="panel-title">属性配置</div>
        <div v-if="selectedIndex===null || !components[selectedIndex]" class="empty-tip">
          <p>请选择一个组件</p>
        </div>
        <div v-else class="property-form">
          <el-form label-position="top" size="small">
            <el-form-item label="组件名称">
              <el-input v-model="components[selectedIndex].name" />
            </el-form-item>

            <!-- 轮播图属性 -->
            <template v-if="components[selectedIndex].type==='banner'">
              <el-form-item label="高度(px)">
                <el-input-number v-model="components[selectedIndex].config.height" :min="100" :max="500" />
              </el-form-item>
              <el-form-item label="自动播放">
                <el-switch v-model="components[selectedIndex].config.autoplay" />
              </el-form-item>
              <el-form-item label="轮播图列表">
                <div v-for="(img, i) in (components[selectedIndex].config.images||[])" :key="i" class="img-item">
                  <el-input v-model="components[selectedIndex].config.images[i]" size="small" />
                  <el-button type="danger" size="small" @click="components[selectedIndex].config.images.splice(i,1)">删除</el-button>
                </div>
                <el-button size="small" @click="(components[selectedIndex].config.images=components[selectedIndex].config.images||[]).push('https://picsum.photos/seed/'+Date.now()+'/750/300.jpg')">添加图片</el-button>
              </el-form-item>
            </template>

            <!-- 导航属性 -->
            <template v-if="components[selectedIndex].type==='nav'">
              <el-form-item label="列数">
                <el-input-number v-model="components[selectedIndex].config.columns" :min="3" :max="10" />
              </el-form-item>
            </template>

            <!-- 商品推荐属性 -->
            <template v-if="components[selectedIndex].type==='goods'">
              <el-form-item label="标题">
                <el-input v-model="components[selectedIndex].config.title" />
              </el-form-item>
              <el-form-item label="列数">
                <el-input-number v-model="components[selectedIndex].config.columns" :min="1" :max="4" />
              </el-form-item>
              <el-form-item label="显示数量">
                <el-input-number v-model="components[selectedIndex].config.limit" :min="1" :max="20" />
              </el-form-item>
              <el-form-item label="商品分类">
                <el-select v-model="components[selectedIndex].config.category_id" placeholder="全部分类" clearable>
                  <el-option v-for="cat in categories" :key="cat.id" :label="cat.name" :value="cat.id" />
                </el-select>
              </el-form-item>
            </template>

            <!-- 公告属性 -->
            <template v-if="components[selectedIndex].type==='notice'">
              <el-form-item label="公告内容">
                <el-input v-model="components[selectedIndex].config.text" type="textarea" :rows="2" />
              </el-form-item>
            </template>
          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Plus, Top, Bottom, Delete, Bell, Picture, Grid, Goods, Search, Ticket, Monitor } from '@element-plus/icons-vue'
import request from '@/utils/request'

const route = useRoute()
const router = useRouter()
const page = ref(null)
const components = ref([])
const selectedIndex = ref(null)
const saving = ref(false)
const categories = ref([])

const componentTypes = [
  { type: 'banner', name: '轮播图', icon: 'Picture', config: { height: 200, autoplay: true, images: ['https://picsum.photos/seed/b1/750/300.jpg','https://picsum.photos/seed/b2/750/300.jpg'] } },
  { type: 'nav', name: '分类导航', icon: 'Grid', config: { columns: 5 } },
  { type: 'goods', name: '商品推荐', icon: 'Goods', config: { title: '为你推荐', columns: 2, limit: 10, category_id: null } },
  { type: 'search', name: '搜索框', icon: 'Search', config: {} },
  { type: 'notice', name: '公告', icon: 'Bell', config: { text: '欢迎来到商城' } },
  { type: 'coupon', name: '优惠券', icon: 'Ticket', config: {} },
]

const defaultImages = ['https://picsum.photos/seed/d1/750/300.jpg','https://picsum.photos/seed/d2/750/300.jpg']

const loadPage = async () => {
  const res = await request({ url: `/decorate/pages/${route.params.id}` })
  page.value = res.data?.page
  const comps = res.data?.components || []
  components.value = comps.map(c => ({
    type: c.component_type,
    name: c.name || c.component_type,
    config: JSON.parse(c.config || '{}')
  }))
}

const loadCategories = async () => {
  const res = await request({ url: '/admin/categories' })
  categories.value = res.data?.list || res.data || []
}

const addComponent = (comp) => {
  components.value.push({
    type: comp.type,
    name: comp.name,
    config: JSON.parse(JSON.stringify(comp.config))
  })
  selectedIndex.value = components.value.length - 1
}

const onDragStart = (e, comp) => {
  e.dataTransfer.setData('component', JSON.stringify(comp))
}

const onDrop = (e) => {
  const comp = JSON.parse(e.dataTransfer.getData('component'))
  addComponent(comp)
}

const selectComponent = (index) => {
  selectedIndex.value = index
}

const moveUp = (index) => {
  if (index > 0) {
    const temp = components.value[index]
    components.value[index] = components.value[index-1]
    components.value[index-1] = temp
    selectedIndex.value = index - 1
  }
}

const moveDown = (index) => {
  if (index < components.value.length - 1) {
    const temp = components.value[index]
    components.value[index] = components.value[index+1]
    components.value[index+1] = temp
    selectedIndex.value = index + 1
  }
}

const removeComponent = (index) => {
  components.value.splice(index, 1)
  if (selectedIndex.value === index) selectedIndex.value = null
}

const save = async () => {
  saving.value = true
  try {
    await request({
      url: `/decorate/pages/${route.params.id}/components`,
      method: 'post',
      data: { components: components.value }
    })
    ElMessage.success('保存成功')
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

const preview = () => {
  ElMessage.info('预览功能开发中')
}

onMounted(() => {
  loadPage()
  loadCategories()
})
</script>

<style scoped>
.decoration-editor { height: 100vh; display: flex; flex-direction: column; }
.editor-header { height: 50px; background: #fff; border-bottom: 1px solid #e6e6e6; display: flex; align-items: center; padding: 0 20px; gap: 20px; }
.editor-header .title { font-size: 16px; font-weight: bold; flex: 1; }
.editor-header .actions { display: flex; gap: 10px; }
.editor-body { flex: 1; display: flex; overflow: hidden; }
.component-panel { width: 200px; background: #fff; border-right: 1px solid #e6e6e6; overflow-y: auto; }
.panel-title { padding: 15px; font-weight: bold; border-bottom: 1px solid #f0f0f0; }
.component-list { padding: 10px; }
.component-item { display: flex; align-items: center; gap: 10px; padding: 12px; margin-bottom: 8px; background: #f5f7fa; border-radius: 4px; cursor: move; transition: all 0.2s; }
.component-item:hover { background: #ecf5ff; color: #409eff; }
.canvas-panel { flex: 1; background: #f0f2f5; padding: 20px; overflow-y: auto; display: flex; justify-content: center; }
.phone-frame { width: 375px; background: #fff; border-radius: 20px; box-shadow: 0 0 20px rgba(0,0,0,0.1); padding: 10px; min-height: 600px; }
.phone-screen { min-height: 580px; border-radius: 10px; overflow: hidden; }
.empty-tip { text-align: center; padding: 60px 20px; color: #909399; }
.canvas-component { border: 2px solid transparent; margin-bottom: 10px; position: relative; }
.canvas-component.active { border-color: #409eff; }
.component-toolbar { display: flex; justify-content: space-between; align-items: center; padding: 5px 10px; background: #f5f7fa; font-size: 12px; }
.component-preview { padding: 10px; }
.preview-banner { border-radius: 4px; overflow: hidden; }
.preview-nav { display: flex; flex-wrap: wrap; }
.preview-nav .nav-item { width: 20%; text-align: center; padding: 10px 0; }
.preview-nav .nav-icon { width: 40px; height: 40px; background: #f0f0f0; border-radius: 50%; margin: 0 auto 5px; display: flex; align-items: center; justify-content: center; font-size: 10px; }
.preview-nav .nav-text { font-size: 11px; }
.preview-goods .goods-title { font-weight: bold; margin-bottom: 10px; text-align: center; }
.preview-goods .goods-grid { display: grid; gap: 8px; }
.preview-goods .goods-item { background: #f9f9f9; border-radius: 4px; overflow: hidden; }
.preview-goods .goods-img { height: 80px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; font-size: 10px; }
.preview-goods .goods-name { font-size: 11px; padding: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preview-goods .goods-price { color: #f56c6c; font-size: 12px; font-weight: bold; padding: 0 5px 5px; }
.preview-search { padding: 5px; }
.preview-notice { display: flex; align-items: center; gap: 8px; padding: 10px; background: #fdf6ec; border-radius: 4px; font-size: 12px; }
.preview-coupon { display: flex; gap: 8px; }
.preview-coupon .coupon-item { flex: 1; background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: #fff; padding: 10px; border-radius: 4px; text-align: center; }
.preview-coupon .coupon-amount { font-size: 18px; font-weight: bold; }
.preview-coupon .coupon-info { font-size: 10px; }
.preview-default { text-align: center; padding: 20px; color: #909399; font-size: 12px; }
.property-panel { width: 280px; background: #fff; border-left: 1px solid #e6e6e6; overflow-y: auto; }
.property-form { padding: 15px; }
.img-item { display: flex; gap: 5px; margin-bottom: 5px; }
</style>
