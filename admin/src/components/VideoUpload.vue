<template>
  <div class="video-upload">
    <div v-if="modelValue" class="video-preview">
      <video :src="modelValue" controls style="width:200px;height:120px;object-fit:cover;border-radius:4px" />
      <el-button size="small" type="danger" @click="removeVideo" style="margin-top:8px">删除视频</el-button>
    </div>
    <div v-else class="upload-btn" @click="triggerUpload">
      <el-icon><VideoPlay /></el-icon>
      <span>上传视频</span>
    </div>
    <input ref="fileInput" type="file" accept="video/*" style="display:none" @change="handleFileChange" />
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { VideoPlay } from '@element-plus/icons-vue'
import { uploadVideo } from '@/api/upload'
const props = defineProps({ modelValue: { type: String, default: '' } })
const emit = defineEmits(['update:modelValue'])
const fileInput = ref(null)
const triggerUpload = () => fileInput.value.click()
const handleFileChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  try {
    const res = await uploadVideo(file)
    emit('update:modelValue', res.data.url)
    ElMessage.success('视频上传成功')
  } catch (err) { ElMessage.error('视频上传失败') }
  e.target.value = ''
}
const removeVideo = () => emit('update:modelValue', '')
</script>
<style scoped>
.upload-btn{width:200px;height:120px;border:1px dashed #dcdfe6;border-radius:4px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;color:#909399}
.upload-btn:hover{border-color:#409eff;color:#409eff}
.upload-btn span{font-size:12px;margin-top:8px}
</style>
