<template>
  <el-dialog
    v-model="visible"
    :title="null"
    :width="isMaximized ? '95%' : '800px'"
    :top="isMaximized ? '2vh' : '8vh'"
    :close-on-click-modal="false"
    :modal="false"
    :append-to-body="true"
    :draggable="!isMaximized"
    :destroy-on-close="false"
    class="doc-dialog"
    :class="{ 'doc-dialog-maximized': isMaximized }"
  >
    <!-- 自定义标题栏 -->
    <template #header>
      <div class="doc-dialog-header" @mousedown="startDrag">
        <div class="doc-dialog-title">
          <el-icon><Document /></el-icon>
          <span>{{ title }}</span>
          <el-tag size="small" type="info" style="margin-left:8px">技术文档</el-tag>
        </div>
        <div class="doc-dialog-actions" @mousedown.stop>
          <el-tooltip content="最小化" placement="bottom">
            <el-button text @click="minimize">
              <el-icon><Minus /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip :content="isMaximized ? '还原' : '最大化'" placement="bottom">
            <el-button text @click="toggleMaximize">
              <el-icon><FullScreen v-if="!isMaximized" /><Aim v-else /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="关闭" placement="bottom">
            <el-button text @click="close">
              <el-icon><Close /></el-icon>
            </el-button>
          </el-tooltip>
        </div>
      </div>
    </template>

    <!-- 文档内容 -->
    <div class="doc-dialog-body" ref="bodyRef">
      <div v-if="loading" class="doc-loading">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>加载技术文档...</span>
      </div>
      <div v-else-if="content" class="markdown-body" v-html="renderedContent"></div>
      <div v-else class="doc-empty">
        <el-empty description="暂无技术文档" />
      </div>
    </div>

    <!-- 底部状态栏 -->
    <template #footer>
      <div class="doc-dialog-footer">
        <span class="doc-path">模块: {{ module }} / 页面: {{ page }}</span>
        <div class="doc-footer-actions">
          <el-button size="small" @click="copyContent">📋 复制文档</el-button>
          <el-button size="small" type="primary" @click="close">关闭</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { Document, FullScreen, Aim, Close, Minus, Loading } from '@element-plus/icons-vue'
import { marked } from 'marked'
import hljs from 'highlight.js'
import 'highlight.js/styles/github.css'
import request from '@/utils/request'

const props = defineProps({
  modelValue: Boolean,
  module: String,
  page: { type: String, default: '_index' },
  title: { type: String, default: '技术文档' }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
})

const loading = ref(false)
const content = ref('')
const renderedContent = ref('')
const isMaximized = ref(false)
const bodyRef = ref(null)

// 配置marked
marked.setOptions({
  highlight: function(code, lang) {
    if (lang && hljs.getLanguage(lang)) {
      try { return hljs.highlight(code, { language: lang }).value } catch (e) {}
    }
    return hljs.highlightAuto(code).value
  },
  breaks: true,
  gfm: true
})

const loadDoc = async () => {
  if (!props.module) return
  loading.value = true
  try {
    const pagePath = props.page && props.page !== '_index' ? `/${props.page}` : ''
    const res = await request.get(`/admin/docs/${props.module}${pagePath}`)
    content.value = res.data?.content || res.data || ''
    renderedContent.value = marked.parse(content.value)
  } catch (e) {
    content.value = '文档加载失败'
    renderedContent.value = '<p>文档加载失败，请检查网络连接。</p>'
  } finally {
    loading.value = false
  }
}

watch(() => [props.modelValue, props.module, props.page], ([val]) => {
  if (val && props.module) {
    loadDoc()
  }
})

const toggleMaximize = () => {
  isMaximized.value = !isMaximized.value
}

const minimize = () => {
  visible.value = false
}

const close = () => {
  visible.value = false
  isMaximized.value = false
}

const copyContent = async () => {
  try {
    await navigator.clipboard.writeText(content.value)
    ElMessage.success('文档内容已复制到剪贴板')
  } catch (e) {
    ElMessage.error('复制失败')
  }
}

// 拖拽支持（el-dialog内置draggable，这里补充标题栏拖拽区域）
const startDrag = (e) => {
  // el-dialog的draggable会处理，这里只阻止事件冒泡到按钮
}
</script>

<style scoped>
.doc-dialog :deep(.el-dialog__header) {
  padding: 0;
  margin: 0;
}
.doc-dialog :deep(.el-dialog__body) {
  padding: 0;
  max-height: 70vh;
  overflow: hidden;
}
.doc-dialog-maximized :deep(.el-dialog__body) {
  max-height: 82vh;
}
.doc-dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  cursor: move;
  user-select: none;
}
.doc-dialog-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 600;
}
.doc-dialog-title .el-tag {
  background: rgba(255,255,255,0.2);
  border-color: rgba(255,255,255,0.3);
  color: white;
}
.doc-dialog-actions {
  display: flex;
  gap: 4px;
}
.doc-dialog-actions .el-button {
  color: white;
  padding: 4px 8px;
}
.doc-dialog-actions .el-button:hover {
  background: rgba(255,255,255,0.15);
}
.doc-dialog-body {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
  overflow-x: auto;
  resize: both;
  min-height: 300px;
}
.doc-dialog-maximized .doc-dialog-body {
  max-height: 82vh;
  resize: none;
}
.doc-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  gap: 12px;
  color: #909399;
}
.doc-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 300px;
}
.doc-dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 20px;
  border-top: 1px solid #ebeef5;
}
.doc-path {
  font-size: 12px;
  color: #909399;
}
.doc-footer-actions {
  display: flex;
  gap: 8px;
}
/* Markdown样式 */
.markdown-body {
  font-size: 14px;
  line-height: 1.7;
  color: #303133;
}
.markdown-body h1, .markdown-body h2, .markdown-body h3 {
  margin-top: 24px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #ebeef5;
}
.markdown-body h1 { font-size: 22px; }
.markdown-body h2 { font-size: 18px; }
.markdown-body h3 { font-size: 16px; }
.markdown-body table {
  width: 100%;
  border-collapse: collapse;
  margin: 12px 0;
  display: block;
  overflow-x: auto;
}
.markdown-body th, .markdown-body td {
  border: 1px solid #dcdfe6;
  padding: 8px 12px;
  text-align: left;
  white-space: nowrap;
}
.markdown-body th {
  background: #f5f7fa;
  font-weight: 600;
}
.markdown-body pre {
  background: #f5f7fa;
  padding: 12px;
  border-radius: 4px;
  overflow-x: auto;
  margin: 12px 0;
}
.markdown-body code {
  background: #f0f2f5;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 13px;
}
.markdown-body pre code {
  background: none;
  padding: 0;
}
.markdown-body ul, .markdown-body ol {
  padding-left: 24px;
}
.markdown-body li {
  margin: 4px 0;
}
.markdown-body blockquote {
  border-left: 4px solid #667eea;
  padding-left: 16px;
  margin: 12px 0;
  color: #606266;
  background: #f8f9ff;
}
</style>
