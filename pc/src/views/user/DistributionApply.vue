<template>
  <div class="distribution-page">
    <div class="page-header">
      <span class="back" @click="$router.back()">‹</span>
      <span class="title">申请分销</span>
    </div>

    <div v-if="isAgent" class="success-card">
      <div class="success-icon">✅</div>
      <div class="success-title">您已是分销商</div>
      <div class="success-desc">可以开始推广商品赚取佣金了</div>
    </div>

    <div v-else-if="hasApply && applyStatus === 0" class="pending-card">
      <div class="pending-icon">⏳</div>
      <div class="pending-title">申请审核中</div>
      <div class="pending-desc">您的分销申请正在审核中，请耐心等待</div>
      <div class="apply-time">提交时间：{{ applyInfo.created_at }}</div>
    </div>

    <div v-else-if="hasApply && applyStatus === 2" class="reject-card">
      <div class="reject-icon">❌</div>
      <div class="reject-title">申请被拒绝</div>
      <div class="reject-reason">拒绝原因：{{ applyInfo.refuse_reason || '暂无' }}</div>
      <button class="retry-btn" @click="showForm = true">重新申请</button>
    </div>

    <div v-else class="apply-form">
      <div class="form-tip">
        <div class="tip-icon">💡</div>
        <div class="tip-text">成为分销商后，推广商品可获得佣金奖励</div>
      </div>

      <div class="form-item">
        <label>手机号</label>
        <input v-model="form.mobile" type="text" placeholder="请输入手机号" disabled />
      </div>

      <div class="form-item">
        <label>微信号 <span class="optional">（选填）</span></label>
        <input v-model="form.wechat" type="text" placeholder="请输入微信号，方便联系" />
      </div>

      <div class="form-item">
        <label>申请理由 <span class="optional">（选填）</span></label>
        <textarea v-model="form.reason" placeholder="简单描述您的推广渠道和经验" rows="4"></textarea>
      </div>

      <button class="submit-btn" :disabled="submitting" @click="submitApply">
        {{ submitting ? '提交中...' : '提交申请' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const userStore = useUserStore()

const hasApply = ref(false)
const applyStatus = ref(0)
const applyInfo = ref(null)
const isAgent = ref(false)
const showForm = ref(true)
const submitting = ref(false)

const form = ref({
  mobile: userStore.userInfo?.mobile || '',
  wechat: '',
  reason: ''
})

const loadStatus = async () => {
  try {
    const token = localStorage.getItem('tllos_pc_token')
    const res = await fetch('/api/v1/distribution/apply-status', {
      headers: { 'Authorization': 'Bearer ' + token }
    })
    const data = await res.json()
    if (data.code === 200) {
      hasApply.value = data.data.has_apply
      applyStatus.value = data.data.apply_status
      applyInfo.value = data.data.apply_info
      isAgent.value = data.data.is_agent
    }
  } catch (e) {
    console.error('加载分销状态失败', e)
  }
}

const submitApply = async () => {
  if (submitting.value) return
  submitting.value = true
  try {
    const token = localStorage.getItem('tllos_pc_token')
    const res = await fetch('/api/v1/distribution/apply', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify(form.value)
    })
    const data = await res.json()
    if (data.code === 200) {
      alert('申请已提交，请等待审核')
      await loadStatus()
    } else {
      alert(data.message || '提交失败')
    }
  } catch (e) {
    alert('提交失败，请重试')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadStatus()
})
</script>

<style scoped>
.distribution-page { min-height: 100vh; background: #f5f5f5; }
.page-header { display: flex; align-items: center; padding: 15px 16px; background: #fff; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 10; }
.back { font-size: 24px; color: #333; margin-right: 12px; cursor: pointer; }
.title { font-size: 17px; font-weight: 500; color: #333; }
.success-card, .pending-card, .reject-card { margin: 20px 16px; padding: 40px 20px; background: #fff; border-radius: 12px; text-align: center; }
.success-icon, .pending-icon, .reject-icon { font-size: 48px; margin-bottom: 16px; }
.success-title, .pending-title, .reject-title { font-size: 18px; font-weight: 500; color: #333; margin-bottom: 8px; }
.success-desc, .pending-desc { font-size: 14px; color: #999; }
.apply-time { font-size: 12px; color: #bbb; margin-top: 12px; }
.reject-reason { font-size: 14px; color: #666; margin-bottom: 20px; }
.retry-btn { padding: 10px 30px; background: var(--primary); color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; }
.apply-form { padding: 16px; }
.form-tip { display: flex; align-items: flex-start; gap: 10px; padding: 14px; background: #fff8e6; border-radius: 8px; margin-bottom: 16px; }
.tip-icon { font-size: 18px; }
.tip-text { font-size: 13px; color: #b8860b; line-height: 1.5; }
.form-item { margin-bottom: 16px; }
.form-item label { display: block; font-size: 14px; color: #333; margin-bottom: 8px; }
.optional { color: #999; font-size: 12px; }
.form-item input, .form-item textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; background: #fff; }
.form-item input:disabled { background: #f5f5f5; color: #999; }
.form-item textarea { resize: vertical; }
.submit-btn { width: 100%; padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-top: 10px; }
.submit-btn:disabled { opacity: 0.6; }
</style>
