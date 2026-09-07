<template>
  <div class="address-edit">
    <div class="container">
      <div class="page-header">
        <el-button @click="router.back()" link>
          <el-icon><ArrowLeft /></el-icon> 返回
        </el-button>
        <h2>{{ form.id ? '编辑地址' : '新增地址' }}</h2>
      </div>

      <!-- 定位按钮区 -->
      <div class="location-section">
        <el-button type="primary" size="large" :loading="locating" @click="getLocation" class="locate-btn">
          <el-icon><Location /></el-icon>
          {{ locating ? '定位中...' : '自动获取当前位置' }}
        </el-button>
        <el-button type="success" size="large" :loading="autoSaving" @click="getLocationAndSave" class="locate-btn">
          <el-icon><Location /></el-icon>
          {{ autoSaving ? '定位并保存中...' : '一键定位并自动保存地址' }}
        </el-button>
        <div v-if="locationResult" class="location-result success">
          <el-icon><SuccessFilled /></el-icon>
          <span>{{ locationResult }}</span>
        </div>
        <div v-if="locationError" class="location-result error">
          <el-icon><Warning /></el-icon>
          <span>{{ locationError }}</span>
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
          <el-input v-model="form.region" placeholder="省/市/区（点击上方按钮自动获取）" />
        </el-form-item>
        <el-form-item label="详细地址">
          <el-input v-model="form.detail" type="textarea" :rows="3" placeholder="请输入详细地址（街道、门牌号等）" />
        </el-form-item>
        <el-form-item label="设为默认">
          <el-switch v-model="form.is_default" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" size="large" :loading="saving" @click="save" class="save-btn">
            保存
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ArrowLeft, Location, SuccessFilled, Warning } from '@element-plus/icons-vue'
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

const locating = ref(false)
const autoSaving = ref(false)
const saving = ref(false)
const locationResult = ref('')
const locationError = ref('')
const amapConfig = ref({ key: '', securityCode: '' })

// 加载高德地图配置
const loadAmapConfig = async () => {
  try {
    const res = await request({ url: '/location/map-config', method: 'get' })
    if (res.code === 200 || res.success) {
      amapConfig.value = res.data || {}
      console.log('地图配置加载成功:', amapConfig.value.key ? '已配置' : '未配置')
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
      reject(new Error('地图Key未配置'))
      return
    }
    window._AMapSecurityConfig = {
      securityJsCode: amapConfig.value.securityCode || ''
    }
    const script = document.createElement('script')
    script.src = `https://webapi.amap.com/maps?v=2.0&key=${amapConfig.value.key}&plugin=AMap.Geolocation,AMap.Geocoder`
    script.onload = () => {
      if (window.AMap) {
        resolve(window.AMap)
      } else {
        reject(new Error('高德地图API加载失败'))
      }
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
              const ac = geoResult.regeocode.addressComponent
              resolve({
                provider: 'amap_js',
                province: ac.province || '',
                city: ac.city || ac.province || '',
                district: ac.district || '',
                address: geoResult.regeocode.formattedAddress || '',
                latitude: result.position.lat,
                longitude: result.position.lng
              })
            } else {
              resolve({
                provider: 'amap_js',
                province: '',
                city: '',
                district: '',
                address: '',
                latitude: result.position.lat,
                longitude: result.position.lng
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
  if (res.code === 200 || res.success) {
    return res.data || res
  }
  throw new Error(res.message || 'IP定位失败')
}

// 自动获取地理位置（只定位，不保存）
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
      provider = 'IP定位'
    }

    if (!data) {
      throw new Error('定位失败，未获取到位置信息')
    }

    const province = data.province || ''
    const city = data.city || ''
    const district = data.district || ''
    const address = data.address || ''

    // 自动填充表单
    form.region = [province, city, district].filter(Boolean).join(' ')
    form.province_name = province
    form.city_name = city
    form.district_name = district

    if (address && !form.detail) {
      form.detail = address.replace(province, '').replace(city, '').replace(district, '').trim()
    }
    // 兜底：如果detail还是为空，用region作为详细地址
    if (!form.detail && form.region) {
      form.detail = form.region
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
    // 1. 定位
    let data = null
    let provider = ''

    try {
      data = await locateWithAmap()
      provider = '高德地图精确定位'
    } catch (amapError) {
      console.warn('高德JS API定位失败，降级到IP定位:', amapError.message)
      data = await locateWithIp()
      provider = 'IP定位'
    }

    if (!data) {
      throw new Error('定位失败，未获取到位置信息')
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

    // 3. 校验必填字段
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
  loadAmapConfig()
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
.location-section {
  background: #fff;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}
.locate-btn {
  width: 100%;
  margin-bottom: 12px;
}
.location-result {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 6px;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.location-result.success {
  background: #f0f9eb;
  color: #67c23a;
}
.location-result.error {
  background: #fef0f0;
  color: #f56c6c;
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
