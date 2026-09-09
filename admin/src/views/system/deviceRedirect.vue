<template>
  <div class="device-redirect-config">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>首页设备跳转配置</span>
          <el-tag type="info">老板控制台</el-tag>
        </div>
      </template>

      <el-alert
        title="配置说明：用户访问根目录 https://mall.tllos.com/ 时，系统自动识别设备类型并跳转到对应前端页面。"
        type="info"
        :closable="false"
        show-icon
        style="margin-bottom: 20px"
      />

      <el-form :model="form" label-width="140px" ref="formRef">
        <el-form-item label="启用自动跳转">
          <el-switch v-model="form.auto_redirect_enabled" active-value="1" inactive-value="0" />
          <span style="margin-left: 10px; color: #909399">开启后，访问根目录自动识别设备并跳转</span>
        </el-form-item>

        <el-form-item label="PC端跳转地址">
          <el-input v-model="form.pc_redirect_url" placeholder="/pc/" clearable>
            <template #prepend>https://mall.tllos.com</template>
          </el-input>
          <div style="color: #909399; font-size: 12px; margin-top: 5px">PC浏览器用户访问根目录时跳转的地址</div>
        </el-form-item>

        <el-form-item label="手机端跳转地址">
          <el-input v-model="form.mobile_redirect_url" placeholder="/pc/" clearable>
            <template #prepend>https://mall.tllos.com</template>
          </el-input>
          <div style="color: #909399; font-size: 12px; margin-top: 5px">手机浏览器用户访问根目录时跳转的地址（H5端）</div>
        </el-form-item>

        <el-form-item label="平板端跳转地址">
          <el-input v-model="form.tablet_redirect_url" placeholder="/pc/" clearable>
            <template #prepend>https://mall.tllos.com</template>
          </el-input>
          <div style="color: #909399; font-size: 12px; margin-top: 5px">平板浏览器用户访问根目录时跳转的地址</div>
        </el-form-item>

        <el-form-item label="默认跳转地址">
          <el-input v-model="form.default_redirect_url" placeholder="/pc/" clearable>
            <template #prepend>https://mall.tllos.com</template>
          </el-input>
          <div style="color: #909399; font-size: 12px; margin-top: 5px">无法识别设备类型时的默认跳转地址</div>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="saveConfig" :loading="loading">
            保存配置
          </el-button>
          <el-button @click="resetConfig">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card style="margin-top: 20px">
      <template #header>
        <span>当前访问设备测试</span>
      </template>
      <div style="text-align: center; padding: 20px">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="当前设备类型">{{ deviceType }}</el-descriptions-item>
          <el-descriptions-item label="UserAgent">{{ userAgent }}</el-descriptions-item>
          <el-descriptions-item label="将跳转到">{{ targetUrl }}</el-descriptions-item>
          <el-descriptions-item label="自动跳转状态">{{ form.auto_redirect_enabled === '1' ? '已启用' : '已禁用' }}</el-descriptions-item>
        </el-descriptions>
        <el-button type="primary" style="margin-top: 20px" @click="testRedirect">
          测试跳转（打开根目录）
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'

const formRef = ref(null)
const loading = ref(false)

const form = reactive({
  pc_redirect_url: '/pc/',
  mobile_redirect_url: '/pc/',
  tablet_redirect_url: '/pc/',
  auto_redirect_enabled: '1',
  default_redirect_url: '/pc/',
})

const userAgent = ref(navigator.userAgent)

const deviceType = computed(() => {
  const ua = navigator.userAgent.toLowerCase()
  if (/ipad|tablet|playbook|silk/i.test(ua)) return '平板'
  if (/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i.test(ua)) return '手机'
  return 'PC'
})

const targetUrl = computed(() => {
  if (form.auto_redirect_enabled !== '1') return '（自动跳转已禁用）'
  if (deviceType.value === 'PC') return form.pc_redirect_url
  if (deviceType.value === '手机') return form.mobile_redirect_url
  if (deviceType.value === '平板') return form.tablet_redirect_url
  return form.default_redirect_url
})

const loadConfig = async () => {
  try {
    const res = await request.get('/system/configs/device-redirect')
    if (res.data?.code === 200) {
      const data = res.data.data
      Object.keys(form).forEach(key => {
        if (data[key] !== undefined) form[key] = data[key]
      })
    }
  } catch (e) {
    console.error('加载配置失败', e)
  }
}

const saveConfig = async () => {
  loading.value = true
  try {
    const res = await request.post('/system/configs/device-redirect', form)
    if (res.data?.code === 200) {
      ElMessage.success('配置保存成功')
    } else {
      ElMessage.error(res.data?.message || '保存失败')
    }
  } catch (e) {
    ElMessage.error('保存失败')
  } finally {
    loading.value = false
  }
}

const resetConfig = () => {
  form.pc_redirect_url = '/pc/'
  form.mobile_redirect_url = '/pc/'
  form.tablet_redirect_url = '/pc/'
  form.auto_redirect_enabled = '1'
  form.default_redirect_url = '/pc/'
}

const testRedirect = () => {
  window.open('https://mall.tllos.com/', '_blank')
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.device-redirect-config {
  padding: 20px;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
