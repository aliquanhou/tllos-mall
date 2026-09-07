<template>
  <div class="config-center">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>系统配置中心</span>
          <el-tag type="info">共 {{ filteredConfigs.length }} 项配置</el-tag>
        </div>
      </template>

      <!-- 搜索框 -->
      <div class="search-section">
        <el-input
          v-model="searchKeyword"
          placeholder="搜索配置项（如：支付、地图、短信、物流、存储...）"
          clearable
          size="large"
          class="search-input"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        <div class="hot-tags">
          <span class="tag-label">热门搜索：</span>
          <el-tag
            v-for="tag in hotTags"
            :key="tag"
            class="hot-tag"
            @click="searchKeyword = tag"
          >
            {{ tag }}
          </el-tag>
        </div>
      </div>

      <!-- 配置分类 -->
      <div class="config-categories">
        <div v-for="category in filteredCategories" :key="category.name" class="category-section">
          <div class="category-header">
            <el-icon :size="20" :color="category.color">
              <component :is="category.icon" />
            </el-icon>
            <span class="category-title">{{ category.name }}</span>
            <el-tag size="small">{{ category.items.filter(item => matchKeyword(item)).length }} 项</el-tag>
          </div>
          <div class="config-grid">
            <div
              v-for="item in category.items.filter(i => matchKeyword(i))"
              :key="item.path"
              class="config-card"
              @click="goToConfig(item)"
            >
              <div class="config-icon" :style="{ background: category.color + '15', color: category.color }">
                <el-icon :size="24"><component :is="item.icon" /></el-icon>
              </div>
              <div class="config-info">
                <div class="config-title">{{ item.title }}</div>
                <div class="config-desc">{{ item.description }}</div>
              </div>
              <el-icon class="config-arrow"><ArrowRight /></el-icon>
            </div>
          </div>
        </div>
      </div>

      <!-- 无结果 -->
      <div v-if="filteredConfigs.length === 0" class="empty-result">
        <el-icon :size="64" color="#ddd"><Search /></el-icon>
        <p>未找到匹配的配置项</p>
        <el-button @click="searchKeyword = ''">清除搜索</el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  Search, ArrowRight, Setting, Wallet, LocationFilled, Van, Message,
  Folder, User, Monitor, Bell, Clock, DataAnalysis, Files, Goods,
  Refresh, ShoppingCart, Lock, Tools
} from '@element-plus/icons-vue'

const router = useRouter()
const searchKeyword = ref('')

const hotTags = ['支付', '地图', '短信', '物流', '存储', '订单', '用户', '网站']

// 配置分类
const configCategories = ref([
  {
    name: '基础设置',
    icon: Setting,
    color: '#409eff',
    items: [
      { title: '基础配置', description: '系统基础参数设置', path: '/system/config', icon: Setting },
      { title: '网站设置', description: '网站名称、LOGO、备案信息', path: '/system/web-setting', icon: Monitor },
      { title: '系统信息', description: '服务器信息、PHP版本', path: '/system/info', icon: DataAnalysis },
      { title: '系统缓存', description: '缓存清理与管理', path: '/system/cache', icon: Refresh },
      { title: '系统日志', description: '操作日志查看', path: '/system/log', icon: Files },
      { title: '数据字典', description: '系统字典数据管理', path: '/system/dict', icon: Tools },
    ]
  },
  {
    name: '支付设置',
    icon: Wallet,
    color: '#67c23a',
    items: [
      { title: '支付配置', description: '微信、支付宝、余额、积分支付', path: '/system/payment', icon: Wallet },
      { title: '支付场景', description: '支付场景与方式配置', path: '/system/pay-scene', icon: ShoppingCart },
      { title: '交易设置', description: '交易规则与手续费配置', path: '/system/transaction-setting', icon: Lock },
    ]
  },
  {
    name: '地图与位置',
    icon: LocationFilled,
    color: '#e6a23c',
    items: [
      { title: '地图服务配置', description: '高德、腾讯、百度地图Key配置', path: '/system/map-config', icon: LocationFilled },
      { title: '地区管理', description: '省市区地区数据管理', path: '/system/area', icon: LocationFilled },
    ]
  },
  {
    name: '物流设置',
    icon: Van,
    color: '#f56c6c',
    items: [
      { title: '物流配置', description: '物流公司管理', path: '/system/express', icon: Van },
      { title: '快递模板', description: '运费模板配置', path: '/system/express-template', icon: Goods },
      { title: '配送方式', description: '配送方式管理', path: '/system/delivery-type', icon: Van },
    ]
  },
  {
    name: '消息通知',
    icon: Message,
    color: '#909399',
    items: [
      { title: '短信配置', description: '短信服务商配置', path: '/system/sms-config', icon: Message },
      { title: '通知设置', description: '系统通知模板配置', path: '/system/notice-setting', icon: Bell },
    ]
  },
  {
    name: '存储设置',
    icon: Folder,
    color: '#8e44ad',
    items: [
      { title: '存储设置', description: '存储方式配置', path: '/system/storage', icon: Folder },
      { title: '存储配置', description: 'OSS、COS、七牛云配置', path: '/system/storage-config', icon: Folder },
      { title: '文件管理', description: '上传文件管理', path: '/system/file', icon: Files },
    ]
  },
  {
    name: '订单与用户',
    icon: User,
    color: '#16a085',
    items: [
      { title: '订单设置', description: '订单规则与自动处理', path: '/system/order-setting', icon: ShoppingCart },
      { title: '用户设置', description: '用户注册与登录配置', path: '/system/user-setting', icon: User },
      { title: '热门搜索', description: '搜索关键词管理', path: '/system/hot-search', icon: Search },
      { title: '定时任务', description: '系统定时任务管理', path: '/system/crontab', icon: Clock },
    ]
  }
])

// 匹配关键词
const matchKeyword = (item) => {
  if (!searchKeyword.value) return true
  const keyword = searchKeyword.value.toLowerCase()
  return (
    item.title.toLowerCase().includes(keyword) ||
    item.description.toLowerCase().includes(keyword) ||
    item.path.toLowerCase().includes(keyword)
  )
}

// 过滤后的配置项总数
const filteredConfigs = computed(() => {
  let count = 0
  configCategories.value.forEach(cat => {
    cat.items.forEach(item => {
      if (matchKeyword(item)) count++
    })
  })
  return count
})

// 过滤后的分类（只显示有匹配项的分类）
const filteredCategories = computed(() => {
  return configCategories.value.filter(cat => {
    return cat.items.some(item => matchKeyword(item))
  })
})

// 跳转到配置页面
const goToConfig = (item) => {
  router.push(item.path)
}
</script>

<style scoped>
.config-center {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-section {
  margin-bottom: 24px;
}

.search-input {
  max-width: 600px;
}

.hot-tags {
  margin-top: 12px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.tag-label {
  font-size: 13px;
  color: #909399;
}

.hot-tag {
  cursor: pointer;
  transition: all 0.2s;
}

.hot-tag:hover {
  transform: scale(1.05);
}

.config-categories {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.category-section {
  border-top: 1px solid #ebeef5;
  padding-top: 20px;
}

.category-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.category-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.config-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 12px;
}

.config-card {
  display: flex;
  align-items: center;
  padding: 16px;
  background: #fff;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.config-card:hover {
  border-color: #409eff;
  box-shadow: 0 4px 12px rgba(64, 158, 255, 0.15);
  transform: translateY(-2px);
}

.config-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  flex-shrink: 0;
}

.config-info {
  flex: 1;
  min-width: 0;
}

.config-title {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}

.config-desc {
  font-size: 12px;
  color: #909399;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.config-arrow {
  color: #c0c4cc;
  flex-shrink: 0;
}

.empty-result {
  text-align: center;
  padding: 60px 20px;
  color: #909399;
}

.empty-result p {
  margin: 16px 0;
}

@media (max-width: 768px) {
  .config-center {
    padding: 12px;
  }

  .config-grid {
    grid-template-columns: 1fr;
  }

  .search-input {
    max-width: 100%;
  }
}
</style>
