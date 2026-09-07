<template>
  <div class="address-edit">
    <!-- 自动获取地理位置 -->
    <div class="location-section">
      <el-button
        type="primary"
        :loading="locating"
        @click="getLocation"
        class="location-btn"
      >
        <el-icon><Location /></el-icon>
        {{ locating ? '定位中...' : '自动获取当前位置' }}
      </el-button>
      <el-button
        type="success"
        :loading="autoSaving"
        @click="getLocationAndSave"
        class="location-btn"
        style="margin-top: 10px"
      >
        <el-icon><LocationFilled /></el-icon>
        {{ autoSaving ? '定位并保存中...' : '一键定位并自动保存地址' }}
      </el-button>
      <div v-if="locationResult" class="location-result">
        <el-icon><LocationFilled /></el-icon>
        <span>{{ locationResult }}</span>
      </div>
      <div v-if="locationError" class="location-error">
        <el-icon><Warning /></el-icon>
        <span>{{ locationError }}</span>
      </div>
    </div>

    <el-form :model="form" label-position="top">
      <el-form-item label="收货人">
        <el-input v-model="form.name" placeholder="请输入收货人姓名" />
      </el-form-item>

      <el-form-item label="手机号">
        <el-input v-model="form.mobile" placeholder="请输入手机号" type="tel" maxlength="11" />
      </el-form-item>

      <el-form-item label="所在地区">
        <el-input
          v-model="form.region"
          placeholder="省/市/区（点击上方按钮自动获取）"
        />
      </el-form-item>

      <el-form-item label="详细地址">
        <el-input
          v-model="form.detail"
          type="textarea"
          :rows="3"
          placeholder="请输入详细地址（街道、门牌号等）"
        />
      </el-form-item>

      <el-form-item label="设为默认">
        <el-switch v-model="form.is_default" :active-value="1" :inactive-value="0" />
      </el-form-item>
    </el-form>

    <div class="footer">
      <el-button type="primary" @click="save" style="width:100%" :loading="saving">
        保存
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Location, LocationFilled, Warning } from '@element-plus/icons-vue'
import request from '@/utils/request'

const route = useRoute()
const router = useRouter()

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

const locating = ref(false)
const saving = ref(false)
const autoSaving = ref(false)
const locationResult = ref('')
const locationError = ref('')
const amapLoaded = ref(false)
const amapConfig = ref({ key: '', securityCode: '' })

// 加载高德地图配置
const loadAmapConfig = async () => {
  try {
    const res = await request({ url: '/location/map-config', method: 'get' })
    if (res.code === 0 || res.success) {
      amapConfig.value = res.data || {}
    }
  } catch (e) {
    console.error('加载地图配置失败:', e)
  }
}

// 动态加载高德JavaScript API
const loadAmapScript = () => {
  return new Promise((resolve, reject) => {
    if (window.AMap) {
      resolve(window.AMap)
      return
    }
    if (!amapConfig.value.key) {
      reject(new Error('未配置高德地图Key'))
      return
    }

    // 设置安全密钥
    window._AMapSecurityConfig = {
      securityJsCode: amapConfig.value.securityCode || ''
    }

    const script = document.createElement('script')
    script.src = `https://webapi.amap.com/maps?v=2.0&key=${amapConfig.value.key}&plugin=AMap.Geolocation,AMap.Geocoder`
    script.onload = () => {
      amapLoaded.value = true
      resolve(window.AMap)
    }
    script.onerror = () => reject(new Error('高德地图API加载失败'))
    document.head.appendChild(script)
  })
}

// 使用高德JavaScript API精确定位
const locateWithAmap = () => {
  return new Promise((resolve, reject) => {
    loadAmapScript().then(AMap => {
      const geolocation = new AMap.Geolocation({
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
        convert: true
      })

      geolocation.getCurrentPosition((status, result) => {
        if (status === 'complete') {
          // 逆地理编码获取详细地址
          const geocoder = new AMap.Geocoder({
            radius: 1000,
            extensions: 'all'
          })

          geocoder.getAddress([result.position.lng, result.position.lat], (geoStatus, geoResult) => {
            if (geoStatus === 'complete' && geoResult.regeocode) {
              const addressComponent = geoResult.regeocode.addressComponent
              resolve({
                provider: 'amap_js',
                province: addressComponent.province || '',
                city: addressComponent.city || addressComponent.province || '',
                district: addressComponent.district || '',
                address: geoResult.regeocode.formattedAddress || '',
                latitude: result.position.lat,
                longitude: result.position.lng,
                accuracy: 'high'
              })
            } else {
              // 逆地理编码失败，返回经纬度
              resolve({
                provider: 'amap_js',
                province: '',
                city: '',
                district: '',
                address: '',
                latitude: result.position.lat,
                longitude: result.position.lng,
                accuracy: 'medium'
              })
            }
          })
        } else {
          reject(new Error(result.message || '定位失败'))
        }
      })
    }).catch(reject)
  })
}

// 使用后端IP定位（降级方案）
const locateWithIp = async () => {
  const res = await request({ url: '/location/get', method: 'get' })
  if (res.code === 0 || res.success) {
    return res.data || res
  }
  throw new Error(res.message || 'IP定位失败')
}

// 自动获取地理位置（优先高德JS API，失败降级IP定位）
const getLocation = async () => {
  locating.value = true
  locationResult.value = ''
  locationError.value = ''

  try {
    let data = null
    let provider = ''

    // 优先使用高德JavaScript API精确定位
    try {
      data = await locateWithAmap()
      provider = '高德地图精确定位'
    } catch (amapError) {
      console.warn('高德JS API定位失败，降级到IP定位:', amapError.message)
      // 降级到IP定位
      data = await locateWithIp()
      provider = data.provider === 'ip-api' || data.provider === 'ipinfo' ? 'IP定位' : '定位'
    }

    if (!data) {
      throw new Error('定位失败')
    }

    const province = data.province || ''
    const city = data.city || ''
    const district = data.district || ''
    const address = data.address || ''

    form.region = [province, city, district].filter(Boolean).join(' ')
    form.province_name = province
    form.city_name = city
    form.district_name = district

    if (address && !form.detail) {
      form.detail = address.replace(province, '').replace(city, '').replace(district, '').trim()
    }

    locationResult.value = `${provider}成功：${form.region || '未知位置'}`
    ElMessage.success('定位成功，已自动填充地区')
  } catch (e) {
    console.error('定位失败:', e)
    locationError.value = '定位服务暂时不可用，请手动输入'
    ElMessage.error('定位失败，请手动输入地址')
  } finally {
    locating.value = false
  }
}

// 一键定位并自动保存地址
const getLocationAndSave = async () => {
  autoSaving.value = true
  locationResult.value = ''
  locationError.value = ''

  try {
    // 1. 定位（优先高德JS API，失败降级IP定位）
    let data = null
    let provider = ''
    try {
      data = await locateWithAmap()
      provider = '高德地图精确定位'
    } catch (amapError) {
      console.warn('高德JS API定位失败，降级到IP定位:', amapError.message)
      data = await locateWithIp()
      provider = data.provider === 'ip-api' || data.provider === 'ipinfo' ? 'IP定位' : '定位'
    }

    if (!data) {
      throw new Error('定位失败')
    }

    const province = data.province || ''
    const city = data.city || ''
    const district = data.district || ''
    const address = data.address || ''

    // 2. 自动填充
    form.region = [province, city, district].filter(Boolean).join(' ')
    form.province_name = province
    form.city_name = city
    form.district_name = district

    if (address && !form.detail) {
      form.detail = address.replace(province, '').replace(city, '').replace(district, '').trim()
    }

    // 3. 如果没有填写收货人和手机号，提示用户填写
    if (!form.name) {
      ElMessage.warning('请先填写收货人姓名')
      autoSaving.value = false
      return
    }
    if (!form.mobile) {
      ElMessage.warning('请先填写手机号')
      autoSaving.value = false
      return
    }
    if (!/^1[3-9]\d{9}$/.test(form.mobile)) {
      ElMessage.warning('请输入正确的手机号')
      autoSaving.value = false
      return
    }
    if (!form.detail) {
      ElMessage.warning('请填写详细地址')
      autoSaving.value = false
      return
    }

    // 4. 自动保存
    const submitData = {
      name: form.name,
      mobile: form.mobile,
      province_name: form.province_name,
      city_name: form.city_name,
      district_name: form.district_name,
      detail: form.detail,
      is_default: form.is_default ? 1 : 0
    }

    if (form.id) {
      await request({ url: `/user/addresses/${form.id}`, method: 'put', data: submitData })
    } else {
      await request({ url: '/user/addresses', method: 'post', data: submitData })
    }

    locationResult.value = `${provider}成功并自动保存：${form.region}`
    ElMessage.success('定位成功，地址已自动保存')
    setTimeout(() => router.back(), 1000)
  } catch (e) {
    console.error('定位并保存失败:', e)
    locationError.value = '定位或保存失败，请手动操作'
    ElMessage.error('失败: ' + (e.message || '未知错误'))
  } finally {
    autoSaving.value = false
  }
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
  if (!form.region && !form.province_name && !form.detail) {
    ElMessage.warning('请输入所在地区或填写详细地址')
    return
  }
  if (!form.detail) {
    ElMessage.warning('请输入详细地址')
    return
  }

  // 如果用户手动输入了region但没有分开的province/city/district，自动拆分
  if (form.region && !form.province_name) {
    const parts = form.region.split(/[\s,，/]+/).filter(Boolean)
    if (parts.length >= 1) form.province_name = parts[0]
    if (parts.length >= 2) form.city_name = parts[1]
    if (parts.length >= 3) form.district_name = parts[2]
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
    ElMessage.error('保存失败: ' + (e.message || '未知错误'))
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  // 加载高德地图配置
  loadAmapConfig()

  if (route.query.id) {
    Object.assign(form, JSON.parse(route.query.data || '{}'))
    form.region = [form.province_name, form.city_name, form.district_name].filter(Boolean).join(' ')
  }
})
</script>

<style scoped>
.address-edit {
  padding: 20px;
  padding-bottom: 100px;
  min-height: 100vh;
  background: #f5f5f5;
}

.location-section {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.location-btn {
  width: 100%;
  height: 44px;
  font-size: 15px;
}

.location-result {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 10px 12px;
  background: #f0f9eb;
  border-radius: 6px;
  font-size: 13px;
  color: #67c23a;
}

.location-error {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 10px 12px;
  background: #fef0f0;
  border-radius: 6px;
  font-size: 13px;
  color: #f56c6c;
}

:deep(.el-form-item) {
  background: #fff;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 12px;
}

:deep(.el-form-item__label) {
  font-size: 14px;
  color: #333;
  font-weight: 500;
  padding-bottom: 8px;
}

:deep(.el-input__wrapper) {
  border-radius: 6px;
}

.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 15px 20px;
  background: #fff;
  border-top: 1px solid #eee;
  z-index: 100;
}

@media (max-width: 768px) {
  .address-edit {
    padding: 12px;
    padding-bottom: 90px;
  }
  .location-section {
    padding: 12px;
  }
  .location-btn {
    height: 42px;
    font-size: 14px;
  }
  :deep(.el-form-item) {
    padding: 10px 12px;
  }
}
</style>
