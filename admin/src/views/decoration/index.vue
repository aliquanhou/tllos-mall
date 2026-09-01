<template>
  <div class="decoration-page">
    <el-card>
      <template #header>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:16px;font-weight:bold">装修管理</span>
          <el-button type="primary" @click="$router.push('/decoration/template')">模板管理</el-button>
        </div>
      </template>
      <el-row :gutter="20">
        <el-col :span="8" v-for="page in pages" :key="page.id">
          <el-card class="page-card" shadow="hover" @click="editPage(page)">
            <div class="page-icon">
              <el-icon :size="48" color="#409eff"><Monitor /></el-icon>
            </div>
            <div class="page-name">{{ page.name }}</div>
            <div class="page-type">{{ pageTypeText[page.page_type] }}</div>
            <div class="page-status">
              <el-tag :type="page.status===1?'success':'info'" size="small">
                {{ page.status===1?'启用中':'已禁用' }}
              </el-tag>
              <el-tag v-if="page.is_default" type="warning" size="small" style="margin-left:5px">默认</el-tag>
            </div>
            <div class="page-actions">
              <el-button type="primary" size="small" @click.stop="editPage(page)">装修</el-button>
              <el-button size="small" @click.stop="applyTemplate(page)">应用模板</el-button>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </el-card>

    <!-- 应用模板弹窗 -->
    <el-dialog v-model="showTemplate" title="选择模板" width="600px">
      <el-radio-group v-model="selectedTemplate">
        <el-row :gutter="15">
          <el-col :span="12" v-for="tpl in templates" :key="tpl.id">
            <el-radio :label="tpl.id" style="width:100%">
              <div class="tpl-item">
                <div class="tpl-name">{{ tpl.name }}</div>
                <div class="tpl-desc">{{ tpl.description }}</div>
                <el-tag v-if="tpl.status===1" type="success" size="small">使用中</el-tag>
              </div>
            </el-radio>
          </el-col>
        </el-row>
      </el-radio-group>
      <template #footer>
        <el-button @click="showTemplate=false">取消</el-button>
        <el-button type="primary" @click="confirmApplyTemplate">确定应用</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { Monitor } from '@element-plus/icons-vue'
import request from '@/utils/request'

const router = useRouter()
const pages = ref([])
const templates = ref([])
const showTemplate = ref(false)
const selectedTemplate = ref(null)
const currentPage = ref(null)

const pageTypeText = { home: '首页', category: '分类页', member: '会员中心' }

const loadPages = async () => {
  const res = await request({ url: '/admin/decorate/pages' })
  pages.value = res.data?.list || res.data || []
}

const loadTemplates = async () => {
  const res = await request({ url: '/admin/decorate/templates' })
  templates.value = res.data?.list || res.data || []
}

const editPage = (page) => {
  router.push(`/decoration/editor/${page.id}`)
}

const applyTemplate = (page) => {
  currentPage.value = page
  selectedTemplate.value = page.template_id
  showTemplate.value = true
}

const confirmApplyTemplate = async () => {
  if (!selectedTemplate.value) {
    ElMessage.warning('请选择模板')
    return
  }
  await request({
    url: `/admin/decorate/pages/${currentPage.value.id}/apply-template`,
    method: 'post',
    data: { template_id: selectedTemplate.value }
  })
  ElMessage.success('模板应用成功')
  showTemplate.value = false
  loadPages()
}

onMounted(() => {
  loadPages()
  loadTemplates()
})
</script>

<style scoped>
.decoration-page { padding: 20px; }
.page-card { cursor: pointer; text-align: center; margin-bottom: 20px; }
.page-icon { padding: 20px 0; }
.page-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
.page-type { font-size: 13px; color: #909399; margin-bottom: 10px; }
.page-status { margin-bottom: 15px; }
.page-actions { display: flex; gap: 10px; justify-content: center; }
.tpl-item { padding: 10px; border: 1px solid #ebeef5; border-radius: 4px; }
.tpl-name { font-weight: bold; margin-bottom: 5px; }
.tpl-desc { font-size: 12px; color: #909399; margin-bottom: 8px; }
</style>
