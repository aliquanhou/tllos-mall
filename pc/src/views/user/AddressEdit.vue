<template>
  <div class="address-edit">
    <div class="container">
      <div class="page-header">
        <el-button @click="router.back()" link>
          <el-icon><ArrowLeft /></el-icon> 返回
        </el-button>
        <h2>{{ form.id ? '编辑地址' : '新增地址' }}</h2>
      </div>

      <!-- 定位提示 -->
      <div v-if="locationMsg" class="location-msg" :class="locationMsgType">
        <el-icon><LocationFilled /></el-icon>
        <span>{{ locationMsg }}</span>
      </div>

      <!-- 地图区域 -->
      <div class="map-section">
        <div class="map-header">
          <span class="map-title">选择收货地址</span>
          <el-button type="primary" size="small" :loading="locating" @click="doLocate">
            <el-icon><Location /></el-icon> 重新定位
          </el-button>
        </div>
        <div ref="mapContainer" class="map-container"></div>
        <div class="map-center-marker">
          <el-icon color="#f56c6c" size="32"><LocationFilled /></el-icon>
        </div>
      </div>

      <!-- 当前选中地址 -->
      <div class="current-address" v-if="currentAddress">
        <el-icon color="#67c23a" size="18"><LocationFilled /></el-icon>
        <span class="address-text">{{ currentAddress }}</span>
      </div>
      <div class="current-address" v-else>
        <el-icon color="#909399" size="18"><Location /></el-icon>
        <span class="address-text">拖动地图或搜索地址选择位置</span>
      </div>

      <!-- 搜索地址 -->
      <div class="search-section">
        <el-input v-model="searchKeyword" placeholder="搜索地址（如：大亚湾西区）" @keyup.enter="doSearch">
          <template #append>
            <el-button @click="doSearch" :loading="searching">搜索</el-button>
          </template>
        </el-input>
        <div class="search-results" v-if="searchResults.length > 0">
          <div v-for="(item, index) in searchResults" :key="index" class="search-result-item" @click="selectSearchResult(item)">
            <div class="result-name">{{ item.name }}</div>
            <div class="result-address">{{ item.address }}</div>
          </div>
        </div>
      </div>

      <!-- 表单 -->
      <el-form :model="form" label-position="top" class="address-form">
        <el-form-item label="收货人">
          <el-input v-model="form.name" placeholder="请输入收货人姓名" />
        </el-form-item>
        <el-form-item label="手机号">
          <el-input v-model="form.mobile" placeholder="请输入手机号" type="tel" maxlength="11" />
        </el-form-item>
        <el-form-item label="所在地区">
          <el-input v-model="form.region" placeholder="省/市/区" />
        </el-form-item>
        <el-form-item label="详细地址">
          <el-input v-model="form.detail" type="textarea" :rows="2" placeholder="街道、门牌号等" />
        </el-form-item>
        <el-form-item label="设为默认">
          <el-switch v-model="form.is_default" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" size="large" :loading="saving" @click="save" class="save-btn">
            保存地址
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { reactive, ref, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Location, LocationFilled } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const route = useRoute()

const form = reactive({
  id: null,
  name: '',
  mobile: '',
  region: '',
  province_name: '',
  city_name: '',
  district_name: '',
  detail: '',
  is_default: 0
})

const mapContainer = ref(null)
const locating = ref(false)
const saving = ref(false)
const searching = ref(false)
const searchKeyword = ref('')
const searchResults = ref([])
const currentAddress = ref('')
const locationMsg = ref('')
const locationMsgType = ref('success')
const amapConfig = ref({ key: '', securityCode: '' })

let mapInstance = null
let geocoderInstance = null
let placeSearchInstance = null

// 加载高德地图配置
const loadAmapConfig = async () => {
  try {
    const res = await request({ url: '/location/map-config', method: 'get' })
    if (res.code === 200 || res.success) {
      amapConfig.value = res.data || {}
      console.log('[地图] 配置加载成功，Key:', amapConfig.value.key ? '已配置' : '未配置')
    }
  } catch (e) {
    console.error('[地图] 配置加载失败:', e)
  }
}

// 动态加载高德JavaScript API
const loadAmapScript = () => {
  return new Promise((resolve, reject) => {
    if (window.AMap) {
      console.log('[地图] AMap已存在，直接使用')
      resolve(window.AMap)
      return
    }
    if (!amapConfig.value.key) {
      reject(new Error('地图Key未配置'))
      return
    }
    console.log('[地图] 开始加载JS API，Key:', amapConfig.value.key.substring(0, 10) + '...')
    
    const script = document.createElement('script')
    script.type = 'text/javascript'
    script.src = `https://webapi.amap.com/maps?v=1.4.15&key=${amapConfig.value.key}&plugin=AMap.Geolocation,AMap.Geocoder,AMap.PlaceSearch,AMap.ToolBar,AMap.Scale`
    
    script.onload = () => {
      console.log('[地图] JS API加载完成，window.AMap:', typeof window.AMap)
      if (window.AMap) {
        resolve(window.AMap)
      } else {
        reject(new Error('高德地图API初始化失败'))
      }
    }
    script.onerror = (e) => {
      console.error('[地图] JS API加载失败:', e)
      reject(new Error('高德地图JS文件加载失败'))
    }
    document.head.appendChild(script)
  })
}

// 逆地理编码，填充地址
const reverseGeocode = (lng, lat) => {
  console.log('[逆地理编码] 开始，坐标:', lng, lat)
  
  // 优先使用JS API逆地理编码
  if (geocoderInstance) {
    try {
      geocoderInstance.getAddress([lng, lat], (status, result) => {
        console.log('[逆地理编码-JSAPI] 状态:', status, '结果:', result)
        
        if (status === 'complete' && result && result.regeocode) {
          fillAddressFromRegeocode(result.regeocode)
        } else {
          console.warn('[逆地理编码-JSAPI] 失败，降级到Web服务API')
          reverseGeocodeByWebService(lng, lat)
        }
      })
    } catch (e) {
      console.error('[逆地理编码-JSAPI] 异常:', e)
      reverseGeocodeByWebService(lng, lat)
    }
  } else {
    console.warn('[逆地理编码] geocoderInstance未初始化，使用Web服务API')
    reverseGeocodeByWebService(lng, lat)
  }
}

// 使用Web服务API逆地理编码（备选方案）
const reverseGeocodeByWebService = async (lng, lat) => {
  console.log('[逆地理编码-Web服务] 开始，坐标:', lng, lat)
  
  try {
    // 使用高德地图Web服务API
    const webServiceKey = amapConfig.value.web_service_key || ''
    if (!webServiceKey) {
      console.warn('[逆地理编码-Web服务] Web服务Key未配置')
      currentAddress.value = '地址解析失败，请手动填写或搜索地址'
      return
    }
    
    const url = `https://restapi.amap.com/v3/geocode/regeo?key=${webServiceKey}&location=${lng},${lat}&extensions=all`
    console.log('[逆地理编码-Web服务] 请求URL:', url.substring(0, 80) + '...')
    
    const response = await fetch(url)
    const data = await response.json()
    console.log('[逆地理编码-Web服务] 响应:', data.status, data.info)
    
    if (data.status === '1' && data.regeocode) {
      fillAddressFromRegeocode(data.regeocode)
    } else {
      console.warn('[逆地理编码-Web服务] 失败:', data.info)
      currentAddress.value = '地址解析失败，请手动填写或搜索地址'
    }
  } catch (e) {
    console.error('[逆地理编码-Web服务] 异常:', e)
    currentAddress.value = '地址解析失败，请手动填写或搜索地址'
  }
}

// 从逆地理编码结果填充地址
const fillAddressFromRegeocode = (regeocode) => {
  const ac = regeocode.addressComponent
  const formatted = regeocode.formattedAddress
  
  console.log('[填充地址] 省:', ac.province, '市:', ac.city, '区:', ac.district)
  console.log('[填充地址] 格式化地址:', formatted)
  
  // 填充当前地址显示
  currentAddress.value = formatted
  
  // 填充表单
  form.province_name = ac.province || ''
  form.city_name = ac.city || ac.province || ''
  form.district_name = ac.district || ''
  form.region = [ac.province, ac.city, ac.district].filter(Boolean).join(' ')
  
  // 详细地址 = 格式化地址 - 省市区
  let detail = formatted
  if (ac.province) detail = detail.replace(ac.province, '')
  if (ac.city) detail = detail.replace(ac.city, '')
  if (ac.district) detail = detail.replace(ac.district, '')
  form.detail = detail.trim() || formatted
  
  console.log('[填充地址] 完成，region:', form.region, 'detail:', form.detail)
}

// 初始化地图
const initMap = async () => {
  try {
    console.log('[地图] 开始初始化...')
    const AMap = await loadAmapScript()
    
    await nextTick()
    await new Promise(resolve => setTimeout(resolve, 200))
    
    if (!mapContainer.value) {
      throw new Error('地图容器DOM元素不存在')
    }
    
    console.log('[地图] 容器已就绪，开始创建地图实例')
    
    mapInstance = new AMap.Map(mapContainer.value, {
      zoom: 16,
      center: [114.416, 22.746],
      resizeEnable: true
    })

    // 初始化逆地理编码
    geocoderInstance = new AMap.Geocoder({
      radius: 1000,
      extensions: 'all'
    })
    console.log('[地图] 逆地理编码器初始化完成')

    // 初始化地点搜索
    placeSearchInstance = new AMap.PlaceSearch({
      pageSize: 10,
      pageIndex: 1,
      city: '全国',
      extensions: 'all'
    })
    console.log('[地图] 地点搜索初始化完成')

    // 添加控件
    mapInstance.addControl(new AMap.ToolBar({ position: 'RB' }))
    mapInstance.addControl(new AMap.Scale())

    // 地图移动结束后，获取中心位置的地址
    mapInstance.on('moveend', () => {
      const center = mapInstance.getCenter()
      console.log('[地图] 移动结束，中心:', center.lng, center.lat)
      reverseGeocode(center.lng, center.lat)
    })
    
    console.log('[地图] 初始化完成，开始自动定位')
    locationMsg.value = '地图加载完成，正在定位...'
    locationMsgType.value = 'info'
    
    // 自动定位
    doLocate()
  } catch (e) {
    console.error('[地图] 初始化失败:', e)
    locationMsg.value = '地图加载失败: ' + e.message + '，请手动填写地址'
    locationMsgType.value = 'error'
  }
}

// 执行定位
const doLocate = () => {
  if (!mapInstance) {
    ElMessage.warning('地图未加载完成')
    return
  }

  locating.value = true
  locationMsg.value = '正在定位...'
  locationMsgType.value = 'info'
  console.log('[定位] 开始定位...')

  // 优先使用浏览器原生定位
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lng = position.coords.longitude
        const lat = position.coords.latitude
        console.log('[定位] 浏览器原生定位成功:', lng, lat, '精度:', position.coords.accuracy, '米')
        
        // 移动地图到当前位置
        mapInstance.setCenter([lng, lat])
        mapInstance.setZoom(17)
        
        locating.value = false
        locationMsg.value = '定位成功，精度约' + Math.round(position.coords.accuracy) + '米，请在地图上确认位置'
        locationMsgType.value = 'success'
        
        // 手动触发一次逆地理编码
        setTimeout(() => {
          reverseGeocode(lng, lat)
        }, 500)
      },
      (error) => {
        console.warn('[定位] 浏览器原生定位失败:', error.code, error.message)
        // 降级到高德地图定位
        locateWithAmap()
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
      }
    )
  } else {
    console.warn('[定位] 浏览器不支持地理定位')
    locateWithAmap()
  }
}

// 使用高德地图定位
const locateWithAmap = () => {
  if (!window.AMap) {
    locating.value = false
    locationMsg.value = '定位失败，请手动拖动地图选择位置'
    locationMsgType.value = 'error'
    return
  }

  const geolocation = new AMap.Geolocation({
    enableHighAccuracy: true,
    timeout: 15000,
    maximumAge: 0,
    convert: true
  })

  geolocation.getCurrentPosition((status, result) => {
    locating.value = false
    
    if (status === 'complete') {
      console.log('[定位] 高德地图定位成功:', result.position.lng, result.position.lat)
      mapInstance.setCenter([result.position.lng, result.position.lat])
      mapInstance.setZoom(17)
      locationMsg.value = '定位成功，请在地图上确认位置'
      locationMsgType.value = 'success'
      
      setTimeout(() => {
        reverseGeocode(result.position.lng, result.position.lat)
      }, 500)
    } else {
      console.warn('[定位] 高德地图定位失败:', result.message)
      locationMsg.value = '定位失败，请手动拖动地图选择位置，或搜索地址'
      locationMsgType.value = 'error'
    }
  })
}

// 搜索地址
const doSearch = () => {
  if (!searchKeyword.value.trim()) {
    ElMessage.warning('请输入搜索关键词')
    return
  }
  if (!placeSearchInstance) {
    ElMessage.warning('地图未加载完成')
    return
  }

  searching.value = true
  console.log('[搜索] 开始搜索:', searchKeyword.value)
  
  placeSearchInstance.search(searchKeyword.value, (status, result) => {
    searching.value = false
    console.log('[搜索] 状态:', status, '结果数:', result.poiList ? result.poiList.pois.length : 0)
    
    if (status === 'complete' && result.poiList && result.poiList.pois) {
      searchResults.value = result.poiList.pois.map(poi => ({
        name: poi.name,
        address: poi.address,
        location: poi.location
      }))
      if (searchResults.value.length === 0) {
        ElMessage.info('未找到相关地址')
      }
    } else {
      searchResults.value = []
      ElMessage.info('未找到相关地址')
    }
  })
}

// 选择搜索结果
const selectSearchResult = (item) => {
  console.log('[搜索] 选择结果:', item.name, item.location)
  
  if (item.location && mapInstance) {
    const lng = item.location.lng || item.location.getLng()
    const lat = item.location.lat || item.location.getLat()
    
    mapInstance.setCenter([lng, lat])
    mapInstance.setZoom(18)
    
    // 手动触发逆地理编码
    setTimeout(() => {
      reverseGeocode(lng, lat)
    }, 500)
  }
  
  searchResults.value = []
  searchKeyword.value = item.name
  ElMessage.success('已定位到搜索结果')
}

// 保存地址
const save = async () => {
  if (!form.name) {
    ElMessage.warning('请输入收货人姓名')
    return
  }
  if (!form.mobile) {
    ElMessage.warning('请输入手机号')
    return
  }
  if (!/^1[3-9]\d{9}$/.test(form.mobile)) {
    ElMessage.warning('请输入正确的手机号')
    return
  }
  if (!form.detail) {
    ElMessage.warning('请在地图上选择位置或填写详细地址')
    return
  }

  const submitData = {
    name: form.name,
    mobile: form.mobile,
    province_name: form.province_name,
    city_name: form.city_name,
    district_name: form.district_name,
    detail: form.detail,
    is_default: form.is_default ? 1 : 0
  }

  console.log('[保存] 提交数据:', submitData)
  
  saving.value = true
  try {
    if (form.id) {
      await request({ url: `/user/addresses/${form.id}`, method: 'put', data: submitData })
    } else {
      await request({ url: '/user/addresses', method: 'post', data: submitData })
    }
    ElMessage.success('保存成功')
    setTimeout(() => router.back(), 500)
  } catch (e) {
    console.error('[保存] 失败:', e)
    ElMessage.error('保存失败: ' + (e.message || '未知错误'))
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  console.log('[页面] onMounted开始')
  await loadAmapConfig()
  await nextTick()
  await new Promise(resolve => setTimeout(resolve, 300))
  await initMap()
  
  // 如果是编辑模式，加载地址数据
  if (route.query.id) {
    form.id = route.query.id
    if (route.query.data) {
      try {
        const data = JSON.parse(route.query.data)
        form.name = data.name || ''
        form.mobile = data.mobile || ''
        form.region = [data.province_name, data.city_name, data.district_name].filter(Boolean).join(' ')
        form.province_name = data.province_name || ''
        form.city_name = data.city_name || ''
        form.district_name = data.district_name || ''
        form.detail = data.detail || ''
        form.is_default = data.is_default || 0
      } catch (e) {
        console.error('解析地址数据失败:', e)
      }
    }
  }
})
</script>

<style scoped>
.address-edit {
  min-height: 100vh;
  background: #f5f5f5;
  padding-bottom: 40px;
}
.container {
  max-width: 600px;
  margin: 0 auto;
  padding: 16px;
}
.page-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.page-header h2 {
  margin: 0;
  font-size: 18px;
}
.location-msg {
  padding: 10px 16px;
  border-radius: 8px;
  margin-bottom: 12px;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.location-msg.success {
  background: #f0f9eb;
  color: #67c23a;
}
.location-msg.error {
  background: #fef0f0;
  color: #f56c6c;
}
.location-msg.info {
  background: #ecf5ff;
  color: #409eff;
}
.map-section {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 12px;
  position: relative;
}
.map-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #eee;
}
.map-title {
  font-size: 15px;
  font-weight: 600;
}
.map-container {
  width: 100%;
  height: 280px;
}
.map-center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -100%);
  pointer-events: none;
  z-index: 10;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}
.current-address {
  background: #fff;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.current-address .address-text {
  flex: 1;
  font-size: 14px;
  color: #333;
  line-height: 1.5;
}
.search-section {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
}
.search-results {
  margin-top: 12px;
  max-height: 300px;
  overflow-y: auto;
}
.search-result-item {
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
}
.search-result-item:hover {
  background: #f5f7fa;
}
.result-name {
  font-size: 14px;
  color: #333;
  margin-bottom: 4px;
}
.result-address {
  font-size: 12px;
  color: #999;
}
.address-form {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
}
.save-btn {
  width: 100%;
}
</style>
