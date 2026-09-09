<template>
  <div class="decoration-editor">
    <!-- 顶部工具栏 -->
    <div class="editor-header">
      <div class="header-left">
        <el-button text @click="goBack">
          <el-icon><ArrowLeft /></el-icon> 返回
        </el-button>
        <el-divider direction="vertical" />
        <span class="page-title">{{ pageInfo.title }}</span>
        <el-tag :type="pageInfo.is_published ? 'success' : 'warning'" size="small">
          {{ pageInfo.is_published ? '已发布 v' + pageInfo.version : '未发布' }}
        </el-tag>
        <el-tag v-if="hasUnsavedChanges" type="danger" size="small">未保存</el-tag>
      </div>
      <div class="header-center">
        <el-radio-group v-model="previewDevice" size="small">
          <el-radio-button value="pc"><el-icon><Monitor /></el-icon> PC</el-radio-button>
          <el-radio-button value="tablet"><el-icon><Iphone /></el-icon> 平板</el-radio-button>
          <el-radio-button value="mobile"><el-icon><Cellphone /></el-icon> 手机</el-radio-button>
        </el-radio-group>
      </div>
      <div class="header-right">
        <el-button @click="showGlobalSettings = true"><el-icon><Setting /></el-icon> 全局设置</el-button>
        <el-button @click="showVersionDialog = true"><el-icon><Clock /></el-icon> 版本</el-button>
        <el-button @click="exportConfig"><el-icon><Download /></el-icon> 导出</el-button>
        <el-button type="primary" plain @click="saveDraft" :loading="saving"><el-icon><EditPen /></el-icon> 保存草稿</el-button>
        <el-button type="primary" @click="publishPage" :loading="publishing"><el-icon><Promotion /></el-icon> 发布</el-button>
      </div>
    </div>

    <!-- 主体区域 -->
    <div class="editor-body">
      <!-- 左侧组件库 -->
      <div class="panel-left">
        <div class="panel-title"><el-icon><Grid /></el-icon> 组件库</div>
        <div class="component-list">
          <div v-for="comp in componentTypes" :key="comp.type" class="component-item" @click="addComponent(comp.type)">
            <el-icon :size="24" color="#409eff"><component :is="comp.icon" /></el-icon>
            <span class="comp-name">{{ comp.name }}</span>
            <span class="comp-desc">{{ comp.description }}</span>
          </div>
        </div>
      </div>

      <!-- 中间预览区 -->
      <div class="panel-center">
        <div class="preview-wrapper" :class="'preview-' + previewDevice">
          <div class="preview-frame" :style="previewFrameStyle">
            <div class="preview-content" :style="previewContentStyle">
              <div v-if="visibleComponents.length === 0" class="empty-preview">
                <el-icon :size="64" color="#c0c4cc"><Picture /></el-icon>
                <p>暂无组件，从左侧组件库点击添加</p>
              </div>
              <div v-for="(comp, index) in visibleComponents" :key="comp.id"
                class="preview-component" :class="{ selected: selectedComponentId === comp.id }"
                :style="comp.styles" @click="selectComponent(comp.id)">
                <div class="component-toolbar">
                  <span class="component-label">{{ getComponentName(comp.type) }}</span>
                  <div class="component-actions">
                    <el-button size="small" text @click.stop="moveUp(index)" :disabled="index === 0"><el-icon><ArrowUp /></el-icon></el-button>
                    <el-button size="small" text @click.stop="moveDown(index)" :disabled="index === visibleComponents.length - 1"><el-icon><ArrowDown /></el-icon></el-button>
                    <el-button size="small" text type="danger" @click.stop="removeComponent(comp.id)"><el-icon><Delete /></el-icon></el-button>
                  </div>
                </div>
                <div class="component-preview-body">
                  <!-- 轮播图预览 -->
                  <div v-if="comp.type === 'banner'" class="preview-banner" :style="{height: (previewDevice === 'mobile' ? comp.props.mobileHeight : comp.props.height) + 'px'}">
                    <el-carousel :interval="comp.props.interval" :autoplay="comp.props.autoplay" height="100%">
                      <el-carousel-item v-for="(img, i) in comp.props.images" :key="i">
                        <div class="banner-item" :style="{backgroundImage: 'url(' + img.src + ')', backgroundSize: 'cover', backgroundPosition: 'center'}">
                          <span v-if="img.title" class="banner-title">{{ img.title }}</span>
                        </div>
                      </el-carousel-item>
                    </el-carousel>
                  </div>
                  <!-- 搜索框预览 -->
                  <div v-else-if="comp.type === 'search'" class="preview-search">
                    <el-input :placeholder="comp.props.placeholder" size="large">
                      <template #append><el-button>搜索</el-button></template>
                    </el-input>
                    <div v-if="comp.props.showHotWords && comp.props.hotWords?.length" class="hot-words">
                      <el-tag v-for="word in comp.props.hotWords" :key="word" size="small" style="margin-right:8px">{{ word }}</el-tag>
                    </div>
                  </div>
                  <!-- 分类导航预览 -->
                  <div v-else-if="comp.type === 'category_nav'" class="preview-category-nav">
                    <el-row :gutter="12">
                      <el-col :span="previewDevice === 'mobile' ? 24 / (comp.props.mobileColumns || 4) : 24 / (comp.props.columns || 8)"
                        v-for="(cat, i) in (comp.props.categories?.length ? comp.props.categories : [1,2,3,4,5,6,7,8])" :key="i">
                        <div class="category-item">
                          <el-icon :size="28" color="#409eff"><Grid /></el-icon>
                          <span>{{ cat.name || '分类' + (i+1) }}</span>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <!-- 商品网格预览 -->
                  <div v-else-if="comp.type === 'product_grid'" class="preview-product-grid">
                    <div class="grid-header">
                      <span class="grid-title">{{ comp.props.title }}</span>
                      <span v-if="comp.props.subTitle" class="grid-subtitle">{{ comp.props.subTitle }}</span>
                    </div>
                    <el-row :gutter="12">
                      <el-col :span="previewDevice === 'mobile' ? 24 / (comp.props.mobileColumns || 2) : 24 / (comp.props.columns || 5)"
                        v-for="i in Math.min(comp.props.limit, (previewDevice === 'mobile' ? comp.props.mobileColumns || 2 : comp.props.columns || 5))" :key="i">
                        <div class="product-card">
                          <div class="product-img" :style="{backgroundImage: 'url(https://picsum.photos/200/200?random=' + i + ')'}"></div>
                          <div class="product-name">商品名称示例{{ i }}</div>
                          <div v-if="comp.props.showPrice" class="product-price">¥99.00</div>
                          <div v-if="comp.props.showSales" class="product-sales">已售{{ i * 100 }}</div>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <!-- 优惠券预览 -->
                  <div v-else-if="comp.type === 'coupon'" class="preview-coupon">
                    <el-row :gutter="12">
                      <el-col :span="previewDevice === 'mobile' ? 24 : 8" v-for="(coupon, i) in comp.props.coupons" :key="i">
                        <div class="coupon-item">
                          <div class="coupon-amount">¥{{ coupon.amount }}</div>
                          <div class="coupon-info">
                            <div class="coupon-name">{{ coupon.name }}</div>
                            <div class="coupon-condition">{{ coupon.condition }}</div>
                          </div>
                          <el-button v-if="comp.props.showReceiveBtn" size="small" type="primary" plain>领取</el-button>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <!-- 限时秒杀预览 -->
                  <div v-else-if="comp.type === 'seckill'" class="preview-seckill">
                    <div class="seckill-header">
                      <span class="seckill-title">{{ comp.props.title }}</span>
                      <span v-if="comp.props.showCountdown" class="seckill-countdown">02:30:45</span>
                    </div>
                    <el-row :gutter="8">
                      <el-col :span="previewDevice === 'mobile' ? 24 / (comp.props.mobileColumns || 3) : 24 / (comp.props.columns || 6)"
                        v-for="i in Math.min(comp.props.limit, 6)" :key="i">
                        <div class="seckill-item">
                          <div class="seckill-img" :style="{backgroundImage: 'url(https://picsum.photos/150/150?random=' + (i+10) + ')'}"></div>
                          <div class="seckill-price">¥{{ 19 + i * 10 }}</div>
                          <div class="seckill-origin">¥{{ 99 + i * 20 }}</div>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <!-- 品牌专区预览 -->
                  <div v-else-if="comp.type === 'brand'" class="preview-brand">
                    <div class="brand-title">{{ comp.props.title }}</div>
                    <el-row :gutter="12">
                      <el-col :span="24 / (comp.props.columns || 4)" v-for="i in (comp.props.brands?.length ? comp.props.brands.length : 4)" :key="i">
                        <div class="brand-item">
                          <div class="brand-logo" :style="{backgroundImage: 'url(https://picsum.photos/120/60?random=' + (i+20) + ')'}"></div>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <!-- 公告栏预览 -->
                  <div v-else-if="comp.type === 'notice'" class="preview-notice">
                    <el-icon><Bell /></el-icon>
                    <span>{{ comp.props.notices?.[0] || '公告内容' }}</span>
                  </div>
                  <!-- 图片导航预览 -->
                  <div v-else-if="comp.type === 'image_nav'" class="preview-image-nav">
                    <el-row :gutter="12">
                      <el-col :span="previewDevice === 'mobile' ? 24 / (comp.props.mobileColumns || 2) : 24 / (comp.props.columns || 4)"
                        v-for="i in (comp.props.items?.length ? comp.props.items.length : 4)" :key="i">
                        <div class="image-nav-item">
                          <div class="image-nav-img" :style="{backgroundImage: 'url(https://picsum.photos/300/200?random=' + (i+30) + ')'}"></div>
                          <div class="image-nav-title">导航{{ i+1 }}</div>
                        </div>
                      </el-col>
                    </el-row>
                  </div>
                  <!-- 富文本预览 -->
                  <div v-else-if="comp.type === 'text'" class="preview-text" v-html="comp.props.content"></div>
                  <!-- 默认预览 -->
                  <div v-else class="preview-default">
                    <el-icon :size="32" color="#c0c4cc"><Picture /></el-icon>
                    <span>{{ getComponentName(comp.type) }}组件</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 右侧属性面板 -->
      <div class="panel-right">
        <div class="panel-title"><el-icon><EditPen /></el-icon> 属性设置</div>
        <div v-if="!selectedComponent" class="empty-props">
          <el-icon :size="48" color="#c0c4cc"><Pointer /></el-icon>
          <p>点击中间预览区的组件进行编辑</p>
        </div>
        <div v-else class="props-editor">
          <div class="props-section">
            <div class="props-section-title">基础信息</div>
            <el-form label-width="80px" size="small">
              <el-form-item label="组件类型"><el-input :value="getComponentName(selectedComponent.type)" disabled /></el-form-item>
              <el-form-item label="排序"><el-input-number v-model="selectedComponent.sort" :min="1" :max="999" @change="markUnsaved" /></el-form-item>
            </el-form>
          </div>
          <div class="props-section">
            <div class="props-section-title">可见设备</div>
            <el-checkbox-group v-model="visibleDevices">
              <el-checkbox value="pc">PC端</el-checkbox>
              <el-checkbox value="tablet">平板端</el-checkbox>
              <el-checkbox value="mobile">手机端</el-checkbox>
            </el-checkbox-group>
          </div>
          <div class="props-section">
            <div class="props-section-title">样式设置</div>
            <el-form label-width="80px" size="small">
              <el-form-item label="外边距"><el-input v-model="styleMargin" placeholder="如: 0 0 20px 0" /></el-form-item>
              <el-form-item label="内边距"><el-input v-model="stylePadding" placeholder="如: 16px" /></el-form-item>
              <el-form-item label="背景色"><el-color-picker v-model="styleBgColor" /></el-form-item>
              <el-form-item label="圆角"><el-input v-model="styleBorderRadius" placeholder="如: 8px" /></el-form-item>
            </el-form>
          </div>
          <div class="props-section">
            <div class="props-section-title">组件属性</div>
            <!-- 轮播图属性 -->
            <el-form v-if="selectedComponent.type === 'banner'" label-width="80px" size="small">
              <el-form-item label="轮播图">
                <div v-for="(img, i) in selectedComponent.props.images" :key="i" class="banner-edit-item">
                  <el-input v-model="img.src" placeholder="图片URL" size="small" @change="markUnsaved" />
                  <el-input v-model="img.link" placeholder="跳转链接" size="small" style="margin-top:4px" @change="markUnsaved" />
                  <el-input v-model="img.title" placeholder="标题" size="small" style="margin-top:4px" @change="markUnsaved" />
                  <el-button size="small" type="danger" text @click="selectedComponent.props.images.splice(i, 1); markUnsaved()">删除</el-button>
                </div>
                <el-button size="small" @click="selectedComponent.props.images.push({src:'',link:'',title:''}); markUnsaved()">添加图片</el-button>
              </el-form-item>
              <el-form-item label="自动播放"><el-switch v-model="selectedComponent.props.autoplay" @change="markUnsaved" /></el-form-item>
              <el-form-item label="间隔(ms)"><el-input-number v-model="selectedComponent.props.interval" :min="1000" :max="10000" @change="markUnsaved" /></el-form-item>
              <el-form-item label="PC高度"><el-input-number v-model="selectedComponent.props.height" :min="100" :max="800" @change="markUnsaved" /></el-form-item>
              <el-form-item label="手机高度"><el-input-number v-model="selectedComponent.props.mobileHeight" :min="80" :max="400" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 搜索框属性 -->
            <el-form v-else-if="selectedComponent.type === 'search'" label-width="80px" size="small">
              <el-form-item label="占位文字"><el-input v-model="selectedComponent.props.placeholder" @change="markUnsaved" /></el-form-item>
              <el-form-item label="显示热词"><el-switch v-model="selectedComponent.props.showHotWords" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 分类导航属性 -->
            <el-form v-else-if="selectedComponent.type === 'category_nav'" label-width="80px" size="small">
              <el-form-item label="PC列数"><el-input-number v-model="selectedComponent.props.columns" :min="2" :max="12" @change="markUnsaved" /></el-form-item>
              <el-form-item label="手机列数"><el-input-number v-model="selectedComponent.props.mobileColumns" :min="2" :max="6" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 商品网格属性 -->
            <el-form v-else-if="selectedComponent.type === 'product_grid'" label-width="80px" size="small">
              <el-form-item label="标题"><el-input v-model="selectedComponent.props.title" @change="markUnsaved" /></el-form-item>
              <el-form-item label="副标题"><el-input v-model="selectedComponent.props.subTitle" @change="markUnsaved" /></el-form-item>
              <el-form-item label="分类ID"><el-input-number v-model="selectedComponent.props.categoryId" :min="0" @change="markUnsaved" /></el-form-item>
              <el-form-item label="数量"><el-input-number v-model="selectedComponent.props.limit" :min="1" :max="50" @change="markUnsaved" /></el-form-item>
              <el-form-item label="PC列数"><el-input-number v-model="selectedComponent.props.columns" :min="2" :max="10" @change="markUnsaved" /></el-form-item>
              <el-form-item label="手机列数"><el-input-number v-model="selectedComponent.props.mobileColumns" :min="1" :max="4" @change="markUnsaved" /></el-form-item>
              <el-form-item label="显示价格"><el-switch v-model="selectedComponent.props.showPrice" @change="markUnsaved" /></el-form-item>
              <el-form-item label="显示销量"><el-switch v-model="selectedComponent.props.showSales" @change="markUnsaved" /></el-form-item>
              <el-form-item label="排序方式">
                <el-select v-model="selectedComponent.props.sortBy" @change="markUnsaved">
                  <el-option label="销量" value="sales" /><el-option label="新品" value="new" /><el-option label="价格" value="price" />
                </el-select>
              </el-form-item>
            </el-form>
            <!-- 优惠券属性 -->
            <el-form v-else-if="selectedComponent.type === 'coupon'" label-width="80px" size="small">
              <el-form-item label="显示领取"><el-switch v-model="selectedComponent.props.showReceiveBtn" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 限时秒杀属性 -->
            <el-form v-else-if="selectedComponent.type === 'seckill'" label-width="80px" size="small">
              <el-form-item label="标题"><el-input v-model="selectedComponent.props.title" @change="markUnsaved" /></el-form-item>
              <el-form-item label="数量"><el-input-number v-model="selectedComponent.props.limit" :min="1" :max="20" @change="markUnsaved" /></el-form-item>
              <el-form-item label="显示倒计时"><el-switch v-model="selectedComponent.props.showCountdown" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 品牌专区属性 -->
            <el-form v-else-if="selectedComponent.type === 'brand'" label-width="80px" size="small">
              <el-form-item label="标题"><el-input v-model="selectedComponent.props.title" @change="markUnsaved" /></el-form-item>
              <el-form-item label="列数"><el-input-number v-model="selectedComponent.props.columns" :min="2" :max="8" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 公告栏属性 -->
            <el-form v-else-if="selectedComponent.type === 'notice'" label-width="80px" size="small">
              <el-form-item label="标题"><el-input v-model="selectedComponent.props.title" @change="markUnsaved" /></el-form-item>
              <el-form-item label="滚动"><el-switch v-model="selectedComponent.props.scroll" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 图片导航属性 -->
            <el-form v-else-if="selectedComponent.type === 'image_nav'" label-width="80px" size="small">
              <el-form-item label="PC列数"><el-input-number v-model="selectedComponent.props.columns" :min="2" :max="8" @change="markUnsaved" /></el-form-item>
              <el-form-item label="手机列数"><el-input-number v-model="selectedComponent.props.mobileColumns" :min="2" :max="4" @change="markUnsaved" /></el-form-item>
            </el-form>
            <!-- 富文本属性 -->
            <el-form v-else-if="selectedComponent.type === 'text'" label-width="80px" size="small">
              <el-form-item label="HTML内容">
                <el-input v-model="selectedComponent.props.content" type="textarea" :rows="6" @change="markUnsaved" />
              </el-form-item>
            </el-form>
            <!-- 默认提示 -->
            <div v-else class="props-default">该组件暂无可编辑属性</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 全局设置弹窗 -->
    <el-dialog v-model="showGlobalSettings" title="全局样式设置" width="600px">
      <el-form :model="globalConfig" label-width="100px">
        <el-form-item label="页面背景色"><el-color-picker v-model="globalConfig.bg_color" /></el-form-item>
        <el-form-item label="字体族"><el-input v-model="globalConfig.font_family" /></el-form-item>
        <el-form-item label="自定义CSS">
          <el-input v-model="globalConfig.custom_css" type="textarea" :rows="8" placeholder="输入自定义CSS样式" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showGlobalSettings = false">取消</el-button>
        <el-button type="primary" @click="showGlobalSettings = false; markUnsaved()">确定</el-button>
      </template>
    </el-dialog>

    <!-- 版本历史弹窗 -->
    <el-dialog v-model="showVersionDialog" title="版本历史" width="600px">
      <el-table :data="versionList" border>
        <el-table-column prop="version" label="版本" width="100"><template #default="{row}">v{{ row.version }}</template></el-table-column>
        <el-table-column prop="published_at" label="发布时间" width="180"><template #default="{row}">{{ formatTime(row.published_at) }}</template></el-table-column>
        <el-table-column label="操作" width="120">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="rollbackVersion(row)" :disabled="row.version === pageInfo.version">回滚</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  ArrowLeft, Monitor, Iphone, Cellphone, Setting, Clock, Download,
  EditPen, Promotion, Grid, Picture, Pointer, ArrowUp, ArrowDown, Delete, Bell
} from '@element-plus/icons-vue'
import {
  getPageTemplate, savePageDraft, publishPageTemplate,
  getPageVersions, rollbackPageVersion, exportPageTemplate
} from '@/api/pageTemplate'
import { componentTypes, getEmptyConfig } from './defaultTemplates'

const route = useRoute()
const router = useRouter()
const pageId = route.params.id

const pageInfo = ref({ title: '', version: 1, is_published: 0 })
const draftConfig = reactive(getEmptyConfig())
const selectedComponentId = ref(null)
const previewDevice = ref('pc')
const saving = ref(false)
const publishing = ref(false)
const hasUnsavedChanges = ref(false)
const showGlobalSettings = ref(false)
const showVersionDialog = ref(false)
const versionList = ref([])

const globalConfig = computed({
  get: () => draftConfig.global,
  set: (val) => { draftConfig.global = val }
})

const selectedComponent = computed(() => draftConfig.components.find(c => c.id === selectedComponentId.value))

const visibleComponents = computed(() => draftConfig.components
  .filter(c => { const v = c.visible || { pc: true, tablet: true, mobile: true }; return v[previewDevice.value] !== false })
  .sort((a, b) => (a.sort || 0) - (b.sort || 0)))

const visibleDevices = computed({
  get: () => {
    if (!selectedComponent.value) return []
    const v = selectedComponent.value.visible || { pc: true, tablet: true, mobile: true }
    return Object.keys(v).filter(k => v[k])
  },
  set: (val) => {
    if (!selectedComponent.value) return
    selectedComponent.value.visible = { pc: val.includes('pc'), tablet: val.includes('tablet'), mobile: val.includes('mobile') }
    markUnsaved()
  }
})

const styleMargin = computed({
  get: () => selectedComponent.value?.styles?.margin || '',
  set: (val) => { if (selectedComponent.value) { selectedComponent.value.styles.margin = val; markUnsaved() } }
})
const stylePadding = computed({
  get: () => selectedComponent.value?.styles?.padding || '',
  set: (val) => { if (selectedComponent.value) { selectedComponent.value.styles.padding = val; markUnsaved() } }
})
const styleBgColor = computed({
  get: () => selectedComponent.value?.styles?.background || '',
  set: (val) => { if (selectedComponent.value) { selectedComponent.value.styles.background = val; markUnsaved() } }
})
const styleBorderRadius = computed({
  get: () => selectedComponent.value?.styles?.borderRadius || '',
  set: (val) => { if (selectedComponent.value) { selectedComponent.value.styles.borderRadius = val; markUnsaved() } }
})

const previewFrameStyle = computed(() => {
  const widths = { pc: '100%', tablet: '768px', mobile: '375px' }
  return { maxWidth: widths[previewDevice.value], width: widths[previewDevice.value] }
})

const previewContentStyle = computed(() => ({
  background: draftConfig.global.bg_color,
  fontFamily: draftConfig.global.font_family,
  minHeight: '600px'
}))

const loadPage = async () => {
  try {
    const res = await getPageTemplate(pageId, 1)
    pageInfo.value = { id: res.data.id, title: res.data.title, slug: res.data.slug, version: res.data.version, is_published: res.data.is_published }
    if (res.data.draft_config) Object.assign(draftConfig, res.data.draft_config)
    hasUnsavedChanges.value = false
  } catch (e) { ElMessage.error('加载页面失败') }
}

const getComponentName = (type) => {
  const comp = componentTypes.find(c => c.type === type)
  return comp ? comp.name : type
}

const addComponent = (type) => {
  const newComp = {
    id: 'comp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
    type: type,
    props: getDefaultProps(type),
    styles: { margin: '0 0 20px 0', padding: '16px', background: '#fff', borderRadius: '8px' },
    visible: { pc: true, tablet: true, mobile: true },
    sort: draftConfig.components.length + 1
  }
  draftConfig.components.push(newComp)
  selectedComponentId.value = newComp.id
  markUnsaved()
}

const getDefaultProps = (type) => {
  const defaults = {
    banner: { images: [{ src: 'https://picsum.photos/1200/400?random=' + Date.now(), link: '', title: '' }], autoplay: true, interval: 4000, height: 400, mobileHeight: 180 },
    search: { placeholder: '搜索商品', showHotWords: false, hotWords: [] },
    category_nav: { categories: [], columns: 8, mobileColumns: 4 },
    product_grid: { title: '商品列表', subTitle: '', categoryId: 0, limit: 10, columns: 5, mobileColumns: 2, showPrice: true, showSales: false, sortBy: 'sales' },
    coupon: { coupons: [{ amount: 10, condition: '满99可用', name: '优惠券' }], showReceiveBtn: true },
    seckill: { title: '限时秒杀', subTitle: '', activityId: 0, limit: 6, columns: 6, mobileColumns: 3, showCountdown: true },
    brand: { title: '品牌专区', brands: [], columns: 4 },
    notice: { title: '公告', notices: ['公告内容'], scroll: true, interval: 3000 },
    image_nav: { items: [], columns: 4, mobileColumns: 2 },
    text: { content: '<p>自定义内容</p>' }
  }
  return defaults[type] || {}
}

const selectComponent = (id) => { selectedComponentId.value = id }

const removeComponent = (id) => {
  const index = draftConfig.components.findIndex(c => c.id === id)
  if (index > -1) {
    draftConfig.components.splice(index, 1)
    if (selectedComponentId.value === id) selectedComponentId.value = null
    markUnsaved()
  }
}

const moveUp = (index) => {
  const comps = visibleComponents.value
  if (index > 0) {
    const currentSort = comps[index].sort
    comps[index].sort = comps[index - 1].sort
    comps[index - 1].sort = currentSort
    markUnsaved()
  }
}

const moveDown = (index) => {
  const comps = visibleComponents.value
  if (index < comps.length - 1) {
    const currentSort = comps[index].sort
    comps[index].sort = comps[index + 1].sort
    comps[index + 1].sort = currentSort
    markUnsaved()
  }
}

const markUnsaved = () => { hasUnsavedChanges.value = true }

const saveDraft = async () => {
  saving.value = true
  try {
    await savePageDraft(pageId, JSON.parse(JSON.stringify(draftConfig)))
    hasUnsavedChanges.value = false
    ElMessage.success('草稿保存成功')
  } catch (e) { ElMessage.error('保存失败') } finally { saving.value = false }
}

const publishPage = async () => {
  try {
    await ElMessageBox.confirm('确定发布当前草稿？发布后前台将显示最新配置', '发布确认', { type: 'warning' })
    publishing.value = true
    await savePageDraft(pageId, JSON.parse(JSON.stringify(draftConfig)))
    const res = await publishPageTemplate(pageId)
    pageInfo.value.version = res.data?.version || pageInfo.value.version + 1
    pageInfo.value.is_published = 1
    hasUnsavedChanges.value = false
    ElMessage.success('发布成功')
  } catch (e) { if (e !== 'cancel') ElMessage.error('发布失败') } finally { publishing.value = false }
}

const exportConfig = async () => {
  try {
    const blob = await exportPageTemplate(pageId)
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', pageInfo.value.slug + '_draft.json')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (e) { ElMessage.error('导出失败') }
}

const showVersions = async () => {
  try {
    const res = await getPageVersions(pageId)
    versionList.value = res.data?.list || []
  } catch (e) { ElMessage.error('加载版本失败') }
}

const rollbackVersion = async (version) => {
  try {
    await ElMessageBox.confirm('确定回滚到 v' + version.version + '？', '提示', { type: 'warning' })
    await rollbackPageVersion(pageId, version.id)
    ElMessage.success('已回滚到草稿，正在重新加载...')
    showVersionDialog.value = false
    setTimeout(() => loadPage(), 500)
  } catch (e) { if (e !== 'cancel') ElMessage.error('回滚失败') }
}

const goBack = () => {
  if (hasUnsavedChanges.value) {
    ElMessageBox.confirm('有未保存的修改，确定离开？', '提示', { type: 'warning' })
      .then(() => router.push('/decoration/index'))
      .catch(() => {})
  } else { router.push('/decoration/index') }
}

const formatTime = (time) => {
  if (!time) return '-'
  return time.substring(0, 16).replace('T', ' ')
}

watch(showVersionDialog, (val) => { if (val) showVersions() })

onMounted(() => loadPage())
</script>

<style scoped>
.decoration-editor { height: 100vh; display: flex; flex-direction: column; background: #f0f2f5; }
.editor-header { height: 56px; background: #fff; border-bottom: 1px solid #e4e7ed; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; flex-shrink: 0; }
.header-left, .header-right { display: flex; align-items: center; gap: 12px; }
.page-title { font-size: 16px; font-weight: 600; }
.editor-body { flex: 1; display: flex; overflow: hidden; }
.panel-left { width: 240px; background: #fff; border-right: 1px solid #e4e7ed; overflow-y: auto; flex-shrink: 0; }
.panel-center { flex: 1; overflow-y: auto; padding: 20px; display: flex; justify-content: center; }
.panel-right { width: 320px; background: #fff; border-left: 1px solid #e4e7ed; overflow-y: auto; flex-shrink: 0; }
.panel-title { padding: 12px 16px; font-weight: 600; border-bottom: 1px solid #ebeef5; display: flex; align-items: center; gap: 8px; }
.component-list { padding: 12px; }
.component-item { padding: 12px; border: 1px solid #ebeef5; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: all .2s; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.component-item:hover { border-color: #409eff; background: #ecf5ff; }
.comp-name { font-size: 14px; font-weight: 500; }
.comp-desc { font-size: 11px; color: #909399; text-align: center; }
.preview-wrapper { width: 100%; display: flex; justify-content: center; }
.preview-frame { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.1); overflow: hidden; transition: max-width .3s; }
.preview-mobile .preview-frame { box-shadow: 0 0 0 8px #333, 0 2px 12px rgba(0,0,0,.2); border-radius: 24px; }
.preview-content { padding: 16px; }
.empty-preview { text-align: center; padding: 80px 20px; color: #909399; }
.preview-component { position: relative; border: 2px dashed transparent; border-radius: 8px; margin-bottom: 4px; transition: border-color .2s; }
.preview-component:hover { border-color: #c0c4cc; }
.preview-component.selected { border-color: #409eff; }
.component-toolbar { position: absolute; top: -2px; right: -2px; background: #409eff; color: #fff; padding: 4px 8px; border-radius: 0 8px 0 8px; display: none; align-items: center; gap: 8px; z-index: 10; font-size: 12px; }
.preview-component:hover .component-toolbar, .preview-component.selected .component-toolbar { display: flex; }
.component-actions { display: flex; gap: 4px; }
.component-actions .el-button { color: #fff; }
.component-preview-body { margin-top: 8px; }
.empty-props { text-align: center; padding: 60px 20px; color: #909399; }
.props-editor { padding: 16px; }
.props-section { margin-bottom: 20px; }
.props-section-title { font-size: 14px; font-weight: 600; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #ebeef5; color: #303133; }
.banner-edit-item { padding: 8px; background: #f5f7fa; border-radius: 4px; margin-bottom: 8px; }
.props-default { text-align: center; color: #909399; padding: 20px; }
.preview-default { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 20px; color: #909399; }
.banner-item { width: 100%; height: 100%; display: flex; align-items: flex-end; padding: 20px; }
.banner-title { color: #fff; font-size: 24px; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,.5); }
.hot-words { margin-top: 8px; }
.category-item { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 0; cursor: pointer; }
.category-item span { font-size: 12px; color: #606266; }
.grid-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.grid-title { font-size: 16px; font-weight: 600; }
.grid-subtitle { font-size: 12px; color: #909399; }
.product-card { padding: 8px; cursor: pointer; }
.product-img { width: 100%; padding-top: 100%; background-size: cover; background-position: center; border-radius: 8px; margin-bottom: 8px; }
.product-name { font-size: 13px; color: #303133; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; margin-bottom: 4px; }
.product-price { font-size: 16px; color: #f56c6c; font-weight: bold; }
.product-sales { font-size: 11px; color: #909399; }
.coupon-item { display: flex; align-items: center; gap: 12px; padding: 12px; background: linear-gradient(135deg, #ff6b6b, #ee5a5a); border-radius: 8px; color: #fff; }
.coupon-amount { font-size: 28px; font-weight: bold; }
.coupon-info { flex: 1; }
.coupon-name { font-size: 14px; font-weight: 600; }
.coupon-condition { font-size: 12px; opacity: .9; }
.seckill-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.seckill-title { font-size: 16px; font-weight: 600; color: #f56c6c; }
.seckill-countdown { font-size: 14px; color: #fff; background: #f56c6c; padding: 2px 8px; border-radius: 4px; }
.seckill-item { padding: 4px; }
.seckill-img { width: 100%; padding-top: 100%; background-size: cover; background-position: center; border-radius: 4px; margin-bottom: 4px; }
.seckill-price { font-size: 14px; color: #f56c6c; font-weight: bold; }
.seckill-origin { font-size: 11px; color: #c0c4cc; text-decoration: line-through; }
.brand-title { font-size: 16px; font-weight: 600; margin-bottom: 12px; }
.brand-item { padding: 8px; }
.brand-logo { width: 100%; height: 60px; background-size: contain; background-position: center; background-repeat: no-repeat; }
.preview-notice { display: flex; align-items: center; gap: 8px; padding: 12px; background: #fdf6ec; color: #e6a23c; border-radius: 8px; }
.image-nav-item { cursor: pointer; }
.image-nav-img { width: 100%; padding-top: 66%; background-size: cover; background-position: center; border-radius: 8px; margin-bottom: 8px; }
.image-nav-title { font-size: 13px; text-align: center; color: #303133; }
.preview-text { padding: 8px; }
</style>
