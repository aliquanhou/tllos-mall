<template>
  <el-drawer :modal="false" append-to-body
    v-model="visible"
    :title="title"
    direction="rtl"
    size="520px"
    :with-header="true"
  >
    <div v-if="loading" class="help-loading">
      <el-icon class="is-loading"><Loading /></el-icon>
      <span>加载帮助文档...</span>
    </div>
    
    <div v-else-if="doc" class="help-content">
      <!-- 页面说明 -->
      <section class="help-section">
        <h4><el-icon><Reading /></el-icon> 页面说明</h4>
        <p class="help-overview">{{ doc.content.overview }}</p>
      </section>
      
      <!-- API 接口 -->
      <section class="help-section" v-if="doc.content.api && doc.content.api.length">
        <h4><el-icon><Connection /></el-icon> API 接口</h4>
        <el-table :data="doc.content.api" size="small" border>
          <el-table-column prop="method" label="方法" width="70">
            <template #default="{ row }">
              <el-tag :type="methodTag(row.method)" size="small">{{ row.method }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="path" label="路径" min-width="180" show-overflow-tooltip />
          <el-table-column prop="desc" label="说明" min-width="120" show-overflow-tooltip />
        </el-table>
      </section>
      
      <!-- 字段说明 -->
      <section class="help-section" v-if="doc.content.fields && doc.content.fields.length">
        <h4><el-icon><Grid /></el-icon> 字段说明</h4>
        <el-table :data="doc.content.fields" size="small" border>
          <el-table-column prop="label" label="显示字段" width="100" />
          <el-table-column prop="field" label="数据来源" min-width="140" show-overflow-tooltip />
          <el-table-column prop="calc" label="计算方式" min-width="100" show-overflow-tooltip />
        </el-table>
      </section>
      
      <!-- 关联模块 -->
      <section class="help-section" v-if="doc.content.relations">
        <h4><el-icon><Link /></el-icon> 关联模块</h4>
        <div class="relations">
          <div v-if="doc.content.relations.depends_on && doc.content.relations.depends_on.length">
            <span class="rel-label">依赖:</span>
            <el-tag v-for="m in doc.content.relations.depends_on" :key="m" size="small" class="rel-tag">{{ m }}</el-tag>
          </div>
          <div v-if="doc.content.relations.used_by && doc.content.relations.used_by.length">
            <span class="rel-label">被依赖:</span>
            <el-tag v-for="m in doc.content.relations.used_by" :key="m" size="small" type="success" class="rel-tag">{{ m }}</el-tag>
          </div>
        </div>
      </section>
      
      <!-- 当前状态 -->
      <section class="help-section" v-if="doc.content.status && doc.content.status.items">
        <h4><el-icon><CircleCheck /></el-icon> 当前状态</h4>
        <ul class="status-list">
          <li v-for="(item, idx) in doc.content.status.items" :key="idx">
            <span :class="item.includes('⚠️') || item.includes('开发中') ? 'status-warn' : 'status-ok'">
              {{ item.includes('⚠️') || item.includes('开发中') ? '🟡' : '✅' }} {{ item.replace('✅ ', '').replace('⚠️ ', '') }}
            </span>
          </li>
        </ul>
      </section>
      
      <!-- 快速操作 -->
      <section class="help-section">
        <h4><el-icon><Operation /></el-icon> 快速操作</h4>
        <div class="quick-actions">
          <el-button size="small" @click="editDoc">📝 编辑此文档</el-button>
          <el-button size="small" type="warning" @click="reportIssue">🐛 报告问题</el-button>
          <el-button size="small" type="primary" @click="viewFull">📚 查看完整文档</el-button>
        </div>
      </section>
      
      <!-- 完整Markdown文档（可展开） -->
      <section class="help-section" v-if="showFull && doc.content.markdown">
        <h4>📖 完整技术文档</h4>
        <div class="markdown-body" v-html="renderedMarkdown"></div>
      </section>
    </div>
    
    <div v-else class="help-empty">
      <el-empty description="暂无帮助文档" />
    </div>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Loading, Reading, Connection, Grid, Link, CircleCheck, Operation, QuestionFilled, FullScreen, Aim, Close } from '@element-plus/icons-vue'
import request from '@/utils/request'

const props = defineProps({
  modelValue: Boolean,
  module: String,
  page: { type: String, default: '_index' },
  title: { type: String, default: '❓ 帮助文档' }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
})

const loading = ref(false)
const doc = ref(null)
const isFullscreen = ref(false)
const showFull = ref(false)
const renderedMarkdown = ref('')

const methodTag = (method) => {
  const map = { GET: 'success', POST: 'primary', PUT: 'warning', DELETE: 'danger', PATCH: 'info' }
  return map[method] || 'info'
}

const loadDoc = async () => {
  if (!props.module) return
  loading.value = true
  try {
    const pagePath = props.page && props.page !== '_index' ? `/${props.page}` : ''
    const res = await request.get(`/admin/help/${props.module}${pagePath}`)
    doc.value = res.data
    showFull.value = false
  } catch (e) {
    doc.value = null
  } finally {
    loading.value = false
  }
}

watch(() => [props.modelValue, props.module, props.page], ([val]) => {
  if (val && props.module) {
    loadDoc()
  }
})

const editDoc = () => {
  ElMessage.info('文档编辑功能开发中')
}

const reportIssue = () => {
  ElMessage.info('问题报告功能开发中')
}

const viewFull = () => {
  showFull.value = !showFull.value
  if (showFull.value && doc.value?.content?.markdown) {
    // 简单的Markdown渲染
    let md = doc.value.content.markdown
    md = md.replace(/^### (.+)$/gm, '<h5>$1</h5>')
    md = md.replace(/^## (.+)$/gm, '<h4>$1</h4>')
    md = md.replace(/^# (.+)$/gm, '<h3>$1</h3>')
    md = md.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    md = md.replace(/`([^`]+)`/g, '<code>$1</code>')
    md = md.replace(/^- (.+)$/gm, '<li>$1</li>')
    md = md.replace(/\n\n/g, '</p><p>')
    renderedMarkdown.value = '<p>' + md + '</p>'
  }
}
</script>

<style scoped>
.help-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  gap: 12px;
  color: #909399;
}
.help-content {
  padding: 0 8px;
}
.help-section {
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #ebeef5;
}
.help-section:last-child {
  border-bottom: none;
}
.help-section h4 {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0 0 12px 0;
  font-size: 15px;
  color: #303133;
}
.help-overview {
  margin: 0;
  color: #606266;
  line-height: 1.6;
  font-size: 13px;
}
.relations {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.rel-label {
  display: inline-block;
  width: 60px;
  color: #909399;
  font-size: 13px;
}
.rel-tag {
  margin-right: 6px;
}
.status-list {
  margin: 0;
  padding-left: 20px;
}
.status-list li {
  margin-bottom: 6px;
  font-size: 13px;
  color: #606266;
}
.status-ok { color: #67c23a; }
.status-warn { color: #e6a23c; }
.quick-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.markdown-body {
  font-size: 12px;
  line-height: 1.6;
  color: #606266;
  max-height: 400px;
  overflow-y: auto;
  padding: 12px;
  background: #f5f7fa;
  border-radius: 4px;
}
.markdown-body h3, .markdown-body h4, .markdown-body h5 {
  margin: 12px 0 8px;
  color: #303133;
}
.markdown-body code {
  background: #e4e7ed;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
}
.help-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 300px;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #ebeef5;
  margin: -20px -20px 16px -20px;
  background: #fafafa;
}
.drawer-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}
.drawer-actions {
  display: flex;
  gap: 4px;
}

</style>
