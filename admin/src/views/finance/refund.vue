<template>
  <el-card shadow="never">
    <template #header><span>{{ title }}</span></template>
    <el-empty :description="title + ' 模块开发中...'" />
  </el-card>
</template>
<script setup>
const title = '退款记录'
</script>
