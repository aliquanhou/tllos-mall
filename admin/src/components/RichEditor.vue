<template>
  <div class="rich-editor">
    <Toolbar
      :editor="editorRef"
      :defaultConfig="toolbarConfig"
      :mode="mode"
      style="border-bottom:1px solid #ccc"
    />
    <Editor
      v-model="valueHtml"
      :defaultConfig="editorConfig"
      :mode="mode"
      style="height:300px;overflow-y:hidden"
      @onCreated="handleCreated"
      @onChange="handleChange"
    />
  </div>
</template>
<script setup>
import { onBeforeUnmount, ref, shallowRef, watch } from 'vue'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import '@wangeditor/editor/dist/css/style.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  mode: { type: String, default: 'default' },
  placeholder: { type: String, default: '请输入内容...' }
})
const emit = defineEmits(['update:modelValue'])

const editorRef = shallowRef()
const valueHtml = ref(props.modelValue)

watch(() => props.modelValue, val => {
  if (val !== valueHtml.value) valueHtml.value = val
})

const toolbarConfig = { excludeKeys: [] }
const editorConfig = { placeholder: props.placeholder, MENU_CONF: {} }

const handleCreated = editor => { editorRef.value = editor }
const handleChange = editor => { emit('update:modelValue', editor.getHtml()) }

onBeforeUnmount(() => {
  const editor = editorRef.value
  if (editor) editor.destroy()
})
</script>
<style scoped>
.rich-editor{border:1px solid #ccc;border-radius:4px;z-index:100}
</style>
