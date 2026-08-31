<template>
  <div class="image-upload">
    <div class="image-list">
      <div v-for="(img, idx) in modelValue" :key="idx" class="image-item">
        <el-image :src="img" fit="cover" class="image-preview" :preview-src-list="modelValue" :initial-index="idx" />
        <div class="image-actions">
          <el-button size="small" type="danger" link @click="removeImage(idx)">删除</el-button>
        </div>
        <div v-if="idx === 0" class="main-tag">主图</div>
      </div>
      <div v-if="modelValue.length < max" class="upload-btn" @click="triggerUpload">
        <el-icon><Plus /></el-icon>
        <span>上传图片</span>
      </div>
    </div>
    <input ref="fileInput" type="file" accept="image/*" multiple style="display:none" @change="handleFileChange" />
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { uploadFile } from '@/api/upload'
const props = defineProps({ modelValue: { type: Array, default: () => [] }, max: { type: Number, default: 9 } })
const emit = defineEmits(['update:modelValue'])
const fileInput = ref(null)
const triggerUpload = () => fileInput.value.click()
const handleFileChange = async (e) => {
  const files = Array.from(e.target.files)
  for (const file of files) {
    if (props.modelValue.length >= props.max) { ElMessage.warning(`最多上传${props.max}张`); break }
    try {
      const res = await uploadFile(file)
      emit('update:modelValue', [...props.modelValue, res.data.url])
    } catch (err) { ElMessage.error('上传失败') }
  }
  e.target.value = ''
}
const removeImage = (idx) => { const list = [...props.modelValue]; list.splice(idx,1); emit('update:modelValue', list) }
</script>
<style scoped>
.image-list{display:flex;flex-wrap:wrap;gap:12px}
.image-item{position:relative;width:100px;height:100px;border:1px solid #dcdfe6;border-radius:4px;overflow:hidden}
.image-preview{width:100%;height:100%}
.image-actions{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.5);text-align:center}
.image-actions .el-button{color:#fff}
.main-tag{position:absolute;top:0;left:0;background:#409eff;color:#fff;font-size:10px;padding:2px 6px;border-radius:0 0 4px 0}
.upload-btn{width:100px;height:100px;border:1px dashed #dcdfe6;border-radius:4px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;color:#909399;transition:border-color .3s}
.upload-btn:hover{border-color:#409eff;color:#409eff}
.upload-btn span{font-size:12px;margin-top:4px}
</style>
