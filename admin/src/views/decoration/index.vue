<template>
  <div class="decoration-page">
    <el-card>
      <template #header>
        <div class="page-header">
          <div class="header-left">
            <span class="page-title">页面装修</span>
            <span class="page-desc">管理商城所有页面的装修配置，支持草稿/发布/版本管理</span>
          </div>
          <div class="header-right">
            <el-button @click="showTemplatePicker = true">
              <el-icon><MagicStick /></el-icon> 从模板创建
            </el-button>
            <el-button type="primary" @click="showCreateDialog = true">
              <el-icon><Plus /></el-icon> 新建页面
            </el-button>
          </div>
        </div>
      </template>

      <!-- 搜索筛选 -->
      <div class="filter-bar">
        <el-input v-model="searchKeyword" placeholder="搜索页面名称/标识" clearable style="width:240px" @keyup.enter="loadList">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="filterPublished" placeholder="发布状态" clearable style="width:140px" @change="loadList">
          <el-option label="已发布" :value="1" />
          <el-option label="未发布" :value="0" />
        </el-select>
        <el-button type="primary" @click="loadList">查询</el-button>
      </div>

      <!-- 页面卡片列表 -->
      <el-row :gutter="20" class="page-grid">
        <el-col :span="8" v-for="page in pageList" :key="page.id">
          <el-card class="page-card" shadow="hover">
            <div class="card-header">
              <div class="page-icon">
                <el-icon :size="32" :color="page.is_default ? '#e6a23c' : '#409eff'"><Monitor /></el-icon>
              </div>
              <div class="page-info">
                <div class="page-name">{{ page.title }}</div>
                <div class="page-slug">/{{ page.slug }}</div>
              </div>
              <div class="page-badges">
                <el-tag v-if="page.is_default" type="warning" size="small">默认首页</el-tag>
                <el-tag :type="page.is_published ? 'success' : 'info'" size="small">
                  {{ page.is_published ? '已发布' : '草稿' }}
                </el-tag>
              </div>
            </div>
            <div class="card-meta">
              <span>版本: v{{ page.version }}</span>
              <span>组件: {{ getComponentCount(page) }}</span>
              <span>更新: {{ formatTime(page.updated_at) }}</span>
            </div>
            <div class="card-actions">
              <el-button type="primary" size="small" @click="goEditor(page)">
                <el-icon><Edit /></el-icon> 装修
              </el-button>
              <el-button size="small" @click="showVersions(page)">
                <el-icon><Clock /></el-icon> 版本
              </el-button>
              <el-button size="small" @click="exportPage(page)">
                <el-icon><Download /></el-icon> 导出
              </el-button>
              <el-dropdown @command="(cmd) => handleMore(cmd, page)">
                <el-button size="small">
                  更多 <el-icon><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="publish" :disabled="page.is_published">发布页面</el-dropdown-item>
                    <el-dropdown-item command="duplicate">复制页面</el-dropdown-item>
                    <el-dropdown-item command="import">导入配置</el-dropdown-item>
                    <el-dropdown-item command="delete" :disabled="page.is_default" divided>删除页面</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- 空状态 -->
      <el-empty v-if="pageList.length === 0 && !loading" description="暂无装修页面，点击右上角创建" />

      <!-- 分页 -->
      <div class="pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="total"
          :page-sizes="[9, 18, 36]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadList"
          @current-change="loadList"
        />
      </div>
    </el-card>

    <!-- 新建页面弹窗 -->
    <el-dialog v-model="showCreateDialog" title="新建装修页面" width="500px">
      <el-form :model="createForm" label-width="100px">
        <el-form-item label="页面名称" required>
          <el-input v-model="createForm.title" placeholder="如：关于我们" />
        </el-form-item>
        <el-form-item label="页面标识" required>
          <el-input v-model="createForm.slug" placeholder="如：about，用于URL访问" />
          <div class="form-tip">页面标识唯一，用于前台访问 /page/{slug}</div>
        </el-form-item>
        <el-form-item label="初始内容">
          <el-radio-group v-model="createForm.initType">
            <el-radio value="empty">空白页面</el-radio>
            <el-radio value="template">从模板创建</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="createForm.initType === 'template'" label="选择模板">
          <el-select v-model="createForm.templateKey" placeholder="选择默认模板" style="width:100%">
            <el-option v-for="tpl in defaultTemplates" :key="tpl.key" :label="tpl.name" :value="tpl.key">
              {{ tpl.name }} - {{ tpl.description }}
            </el-option>
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showCreateDialog = false">取消</el-button>
        <el-button type="primary" @click="confirmCreate" :loading="creating">创建</el-button>
      </template>
    </el-dialog>

    <!-- 从模板创建弹窗 -->
    <el-dialog v-model="showTemplatePicker" title="选择默认模板" width="700px">
      <div class="template-grid">
        <div v-for="tpl in defaultTemplates" :key="tpl.key" class="template-item" @click="selectTemplate(tpl)">
          <div class="template-preview">
            <el-icon :size="48" color="#409eff"><Picture /></el-icon>
          </div>
          <div class="template-name">{{ tpl.name }}</div>
          <div class="template-desc">{{ tpl.description }}</div>
          <div class="template-components">{{ tpl.config.components.length }} 个组件</div>
        </div>
      </div>
      <template #footer>
        <el-button @click="showTemplatePicker = false">取消</el-button>
      </template>
    </el-dialog>

    <!-- 历史版本弹窗 -->
    <el-dialog v-model="showVersionDialog" :title="`版本历史 - ${currentPageInfo?.title}`" width="600px">
      <el-table :data="versionList" border>
        <el-table-column prop="version" label="版本号" width="100">
          <template #default="{row}">v{{ row.version }}</template>
        </el-table-column>
        <el-table-column prop="published_at" label="发布时间" width="180">
          <template #default="{row}">{{ formatTime(row.published_at) }}</template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{row}">{{ formatTime(row.created_at) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="120">
          <template #default="{row}">
            <el-button size="small" type="primary" @click="rollbackVersion(row)" :disabled="row.version === currentPageInfo?.version">
              回滚
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="version-tip">当前版本: v{{ currentPageInfo?.version }}，回滚会将指定版本恢复到草稿，需手动发布后生效</div>
    </el-dialog>

    <!-- 导入配置弹窗 -->
    <el-dialog v-model="showImportDialog" title="导入装修配置" width="500px">
      <el-upload
        drag
        action="#"
        :auto-upload="false"
        :on-change="handleImportFile"
        accept=".json"
      >
        <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
        <div class="el-upload__text">将JSON文件拖到此处，或<em>点击上传</em></div>
        <template #tip>
          <div class="el-upload__tip">只能上传JSON格式的装修配置文件</div>
        </template>
      </el-upload>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Monitor, Plus, Search, Edit, Clock, Download, ArrowDown,
  MagicStick, Picture, UploadFilled
} from '@element-plus/icons-vue'
import {
  getPageTemplateList, createPageTemplate, publishPageTemplate,
  getPageVersions, rollbackPageVersion, exportPageTemplate,
  importPageTemplate, deletePageTemplate
} from '@/api/pageTemplate'
import { defaultTemplates, getEmptyConfig } from '@/views/decoration/defaultTemplates'

const router = useRouter()
const loading = ref(false)
const pageList = ref([])
const total = ref(0)
const currentPage = ref(1)
const pageSize = ref(9)
const searchKeyword = ref('')
const filterPublished = ref(null)

const showCreateDialog = ref(false)
const showTemplatePicker = ref(false)
const showVersionDialog = ref(false)
const showImportDialog = ref(false)
const creating = ref(false)
const currentPageInfo = ref(null)
const versionList = ref([])

const createForm = reactive({
  title: '',
  slug: '',
  initType: 'empty',
  templateKey: ''
})

const loadList = async () => {
  loading.value = true
  try {
    const res = await getPageTemplateList({
      page: currentPage.value,
      page_size: pageSize.value,
      keyword: searchKeyword.value,
      is_published: filterPublished.value
    })
    pageList.value = res.data?.list || []
    total.value = res.data?.total || 0
  } catch (e) {
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const getComponentCount = (page) => {
  // 页面列表接口不返回config，显示0或从详情获取
  return '-'
}

const formatTime = (time) => {
  if (!time) return '-'
  return time.substring(0, 16).replace('T', ' ')
}

const goEditor = (page) => {
  router.push(`/decoration/editor/${page.id}`)
}

const confirmCreate = async () => {
  if (!createForm.title || !createForm.slug) {
    ElMessage.warning('请填写页面名称和标识')
    return
  }
  creating.value = true
  try {
    let config = getEmptyConfig()
    if (createForm.initType === 'template' && createForm.templateKey) {
      const tpl = defaultTemplates.find(t => t.key === createForm.templateKey)
      if (tpl) config = JSON.parse(JSON.stringify(tpl.config))
    }
    const res = await createPageTemplate({
      title: createForm.title,
      slug: createForm.slug,
      config: config,
      draft_config: config
    })
    ElMessage.success('创建成功')
    showCreateDialog.value = false
    createForm.title = ''
    createForm.slug = ''
    createForm.initType = 'empty'
    createForm.templateKey = ''
    loadList()
    // 跳转到编辑器
    if (res.data?.id) {
      setTimeout(() => router.push(`/decoration/editor/${res.data.id}`), 500)
    }
  } catch (e) {
    ElMessage.error('创建失败')
  } finally {
    creating.value = false
  }
}

const selectTemplate = (tpl) => {
  showTemplatePicker.value = false
  createForm.initType = 'template'
  createForm.templateKey = tpl.key
  createForm.title = tpl.name + '副本'
  createForm.slug = tpl.key + '_' + Date.now()
  showCreateDialog.value = true
}

const showVersions = async (page) => {
  currentPageInfo.value = page
  try {
    const res = await getPageVersions(page.id)
    versionList.value = res.data?.list || []
    showVersionDialog.value = true
  } catch (e) {
    ElMessage.error('加载版本失败')
  }
}

const rollbackVersion = async (version) => {
  try {
    await ElMessageBox.confirm(`确定回滚到 v${version.version}？回滚后会恢复到草稿，需手动发布`, '提示', { type: 'warning' })
    await rollbackPageVersion(currentPageInfo.value.id, version.id)
    ElMessage.success('已回滚到草稿')
    showVersionDialog.value = false
  } catch (e) {
    if (e !== 'cancel') ElMessage.error('回滚失败')
  }
}

const exportPage = async (page) => {
  try {
    const blob = await exportPageTemplate(page.id)
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${page.slug}_v${page.version}.json`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    ElMessage.success('导出成功')
  } catch (e) {
    ElMessage.error('导出失败')
  }
}

const handleMore = async (cmd, page) => {
  if (cmd === 'publish') {
    try {
      await ElMessageBox.confirm(`确定发布"${page.title}"？发布后前台将显示最新配置`, '提示', { type: 'warning' })
      await publishPageTemplate(page.id)
      ElMessage.success('发布成功')
      loadList()
    } catch (e) {
      if (e !== 'cancel') ElMessage.error('发布失败')
    }
  } else if (cmd === 'duplicate') {
    createForm.title = page.title + '副本'
    createForm.slug = page.slug + '_copy_' + Date.now()
    createForm.initType = 'empty'
    showCreateDialog.value = true
  } else if (cmd === 'import') {
    showImportDialog.value = true
  } else if (cmd === 'delete') {
    try {
      await ElMessageBox.confirm(`确定删除"${page.title}"？删除后不可恢复`, '警告', { type: 'error' })
      await deletePageTemplate(page.id)
      ElMessage.success('删除成功')
      loadList()
    } catch (e) {
      if (e !== 'cancel') ElMessage.error('删除失败')
    }
  }
}

const handleImportFile = async (file) => {
  try {
    const formData = new FormData()
    formData.append('file', file.raw)
    const res = await importPageTemplate(formData)
    ElMessage.success(res.message || '导入成功')
    showImportDialog.value = false
    loadList()
  } catch (e) {
    ElMessage.error('导入失败')
  }
}

onMounted(() => loadList())
</script>

<style scoped>
.decoration-page { padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; }
.header-left { display: flex; flex-direction: column; gap: 4px; }
.page-title { font-size: 18px; font-weight: bold; }
.page-desc { font-size: 12px; color: #909399; }
.header-right { display: flex; gap: 10px; }
.filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; }
.page-grid { margin-bottom: 20px; }
.page-card { margin-bottom: 20px; }
.card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.page-icon { width: 48px; height: 48px; background: #ecf5ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.page-info { flex: 1; }
.page-name { font-size: 16px; font-weight: 600; }
.page-slug { font-size: 12px; color: #909399; }
.page-badges { display: flex; gap: 6px; }
.card-meta { display: flex; justify-content: space-between; font-size: 12px; color: #909399; margin-bottom: 12px; padding: 8px 0; border-top: 1px solid #ebeef5; }
.card-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.pagination { display: flex; justify-content: center; margin-top: 20px; }
.form-tip { font-size: 12px; color: #909399; margin-top: 4px; }
.template-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.template-item { border: 2px solid #ebeef5; border-radius: 8px; padding: 16px; cursor: pointer; transition: all .3s; text-align: center; }
.template-item:hover { border-color: #409eff; background: #ecf5ff; }
.template-preview { margin-bottom: 12px; }
.template-name { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
.template-desc { font-size: 12px; color: #909399; margin-bottom: 8px; }
.template-components { font-size: 12px; color: #409eff; }
.version-tip { margin-top: 12px; font-size: 12px; color: #e6a23c; }
</style>
