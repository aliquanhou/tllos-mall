<template>
  <div class="action-buttons">
    <el-button v-if="showView" size="small" @click="$emit('view', row)">查看</el-button>
    <el-button v-if="showEdit" size="small" @click="$emit('edit', row)">编辑</el-button>
    <el-button v-if="showDelete" size="small" type="danger" @click="$emit('delete', row)">删除</el-button>
    <slot :row="row" />
  </div>
</template>
<script setup>
defineProps({
  row: { type: Object, required: true },
  showView: { type: Boolean, default: false },
  showEdit: { type: Boolean, default: true },
  showDelete: { type: Boolean, default: true }
})
defineEmits(['view', 'edit', 'delete'])
</script>
<style scoped>.action-buttons{display:flex;gap:8px;justify-content:center}</style>
