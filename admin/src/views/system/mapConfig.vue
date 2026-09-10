<template>
  <div class="map-config">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>地图服务配置</span>
          <el-tag type="info">配置后用户可精准定位并自动保存地址</el-tag>
        </div>
      </template>

      <el-form :model="form" label-width="150px" style="max-width: 800px">
        <el-form-item label="地图服务商">
          <el-radio-group v-model="form.map_provider">
            <el-radio value="amap">高德地图</el-radio>
            <el-radio value="tencent">腾讯地图</el-radio>
            <el-radio value="baidu">百度地图</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="高德地图Key" v-if="form.map_provider === 'amap'">
          <el-input v-model="form.amap_key" placeholder="请输入高德地图Web服务Key" show-password />
          <div class="tip">申请地址：<a href="https://lbs.amap.com/" target="_blank">https://lbs.amap.com/</a></div>
        </el-form-item>

        <el-form-item label="腾讯地图Key" v-if="form.map_provider === 'tencent'">
          <el-input v-model="form.tencent_key" placeholder="请输入腾讯地图Key" show-password />
          <div class="tip">申请地址：<a href="https://lbs.qq.com/" target="_blank">https://lbs.qq.com/</a></div>
        </el-form-item>

        <el-form-item label="百度地图Key" v-if="form.map_provider === 'baidu'">
          <el-input v-model="form.baidu_key" placeholder="请输入百度地图AK" show-password />
          <div class="tip">申请地址：<a href="https://lbsyun.baidu.com/" target="_blank">https://lbsyun.baidu.com/</a></div>
        </el-form-item>

        <el-form-item label="IP定位">
          <el-switch v-model="form.ip_location_enabled" :active-value="'1'" :inactive-value="'0'" />
          <span class="tip" style="margin-left: 10px">未配置地图Key时使用IP定位（精度较低）</span>
        </el-form-item>

        <el-form-item label="地图定位">
          <el-switch v-model="form.map_location_enabled" :active-value="'1'" :inactive-value="'0'" />
          <span class="tip" style="margin-left: 10px">配置地图Key后使用地图精确定位</span>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="save" :loading="saving">保存配置</el-button>
          <el-button @click="testLocation" :loading="testing">测试定位</el-button>
        </el-form-item>
      </el-form>

      <!-- 测试结果 -->
      <el-dialog v-model="testVisible" title="定位测试结果" width="500px">
        <div v-if="testResult" class="test-result">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="定位方式">{{ testResult.provider }}</el-descriptions-item>
            <el-descriptions-item label="省份">{{ testResult.province || '-' }}</el-descriptions-item>
            <el-descriptions-item label="城市">{{ testResult.city || '-' }}</el-descriptions-item>
            <el-descriptions-item label="区县">{{ testResult.district || '-' }}</el-descriptions-item>
            <el-descriptions-item label="详细地址">{{ testResult.address || '-' }}</el-descriptions-item>
            <el-descriptions-item label="经纬度">{{ testResult.latitude }}, {{ testResult.longitude }}</el-descriptions-item>
            <el-descriptions-item label="精度">{{ testResult.accuracy }}</el-descriptions-item>
          </el-descriptions>
        </div>
      </el-dialog>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getMapConfig, saveMapConfig } from '@/api/mapConfig'
import request from '@/utils/request'

const form = reactive({
  map_provider: 'amap',
  amap_key: '',
  tencent_map_key: '',
  baidu_map_key: '',
  ip_location_enabled: '1',
  map_location_enabled: '1'
})

const saving = ref(false)
const testing = ref(false)
const testVisible = ref(false)
const testResult = ref(null)

// 获取配置
const fetchConfig = async () => {
  try {
    const res = await getMapConfig()
    const list = res.data?.list || []
    list.forEach(item => {
      if (form.hasOwnProperty(item.key)) {
        form[item.key] = item.value
      }
    })
  } catch (e) {
    console.error('获取地图配置失败:', e)
  }
}

// 保存配置
const save = async () => {
  saving.value = true
  try {
    await saveMapConfig({ configs: form })
    ElMessage.success('保存成功')
  } catch (e) {
    ElMessage.error('保存失败: ' + (e.message || '未知错误'))
  } finally {
    saving.value = false
  }
}

// 测试定位
const testLocation = async () => {
  testing.value = true
  try {
    const res = await request({
      url: '/location/get',
      method: 'get'
    })
    if (res.code === 0 || res.success) {
      testResult.value = res.data
      testVisible.value = true
    } else {
      ElMessage.error(res.message || '定位失败')
    }
  } catch (e) {
    ElMessage.error('测试失败: ' + (e.message || '未知错误'))
  } finally {
    testing.value = false
  }
}

onMounted(() => {
  fetchConfig()
})
</script>

<style scoped>
.map-config {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tip {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}

.tip a {
  color: #409eff;
}

.test-result {
  padding: 10px 0;
}
</style>
