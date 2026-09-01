<template>
  <el-drawer v-model="visible" :title="docTitle" direction="rtl" size="600px">
    <template #header>
      <div style="display:flex;align-items:center">
        <span style="font-size:20px;margin-right:8px">📘</span>
        <span style="font-size:16px;font-weight:bold">{{ docTitle }}</span>
        <el-tag size="small" type="info" style="margin-left:10px">技术文档</el-tag>
      </div>
    </template>
    <div style="padding:0 20px;min-height:400px" v-loading="loading">
      <el-empty v-if="error" :description="error" />
      <div class="md-body" v-else v-html="rendered"></div>
    </div>
    <template #footer>
      <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
        <span style="font-size:12px;color:#909399">模块: {{ module }} / 页面: {{ page }}</span>
        <el-button size="small" @click="visible=false">关闭</el-button>
      </div>
    </template>
  </el-drawer>
</template>
<script setup>
import { ref, computed, watch } from 'vue'
import { marked } from 'marked'
import hljs from 'highlight.js'
import 'highlight.js/styles/github.css'
import request from '@/utils/request'

const props = defineProps({ modelValue:Boolean, module:String, page:{type:String,default:'_index'}, title:{type:String,default:'技术文档'} })
const emit = defineEmits(['update:modelValue'])
const visible = computed({get:()=>props.modelValue,set:v=>emit('update:modelValue',v)})
const loading = ref(false), error = ref(''), content = ref('')
const docTitle = computed(()=>props.title)
const rendered = computed(()=>{
  if(!content.value) return ''
  marked.setOptions({highlight:(c,l)=>l&&hljs.getLanguage(l)?hljs.highlight(c,{language:l}).value:hljs.highlightAuto(c).value,breaks:true,gfm:true})
  return marked.parse(content.value)
})
const load = async()=>{
  if(!props.module) return
  loading.value=true; error.value=''
  try {
    const url = props.page&&props.page!=='_index'?`/docs/${props.module}/${props.page}`:`/docs/${props.module}`
    const res = await request({url})
    content.value = res.data?.content||''
    if(!content.value) error.value='文档内容为空'
  } catch(e) { error.value = e.response?.data?.message||'文档加载失败' }
  finally { loading.value=false }
}
watch(()=>[props.modelValue,props.module,props.page],([v])=>{if(v)load()},{immediate:true})
</script>
<style scoped>
.md-body{font-size:14px;line-height:1.8;color:#303133}
.md-body h1{font-size:22px;border-bottom:2px solid #e4e7ed;padding-bottom:10px;margin-top:0}
.md-body h2{font-size:18px;border-left:4px solid #409eff;padding-left:10px;margin-top:24px}
.md-body h3{font-size:16px;margin-top:20px}
.md-body table{width:100%;border-collapse:collapse;margin:16px 0;font-size:13px}
.md-body th{background:#f5f7fa;padding:10px 12px;border:1px solid #e4e7ed;text-align:left}
.md-body td{padding:8px 12px;border:1px solid #e4e7ed}
.md-body tr:nth-child(even){background:#fafafa}
.md-body code{background:#f5f7fa;padding:2px 6px;border-radius:4px;font-size:13px;color:#e6a23c}
.md-body pre{background:#282c34;padding:16px;border-radius:8px;overflow-x:auto;margin:16px 0}
.md-body pre code{background:none;color:#abb2bf;padding:0}
.md-body blockquote{border-left:4px solid #67c23a;padding:8px 16px;margin:16px 0;background:#f0f9eb;color:#67c23a}
.md-body ul,.md-body ol{padding-left:24px}
.md-body li{margin:6px 0}
</style>
