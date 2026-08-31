<template>
  <el-card shadow="never">
    <template #header><span>代码生成器</span></template>
    <el-alert title="选择数据表，自动生成控制器、模型、迁移文件代码" type="info" :closable="false" style="margin-bottom:16px" />
    <el-form :inline="true">
      <el-form-item label="选择表">
        <el-select v-model="selectedTable" placeholder="请选择数据表" style="width:300px" @change="handleTableChange">
          <el-option v-for="t in tables" :key="t.name" :value="t.name" :label="`${t.name} (${t.columns}列)`" />
        </el-select>
      </el-form-item>
      <el-form-item><el-button type="primary" @click="handleGenerate" :disabled="!selectedTable">生成代码</el-button></el-form-item>
    </el-form>
    <el-table v-if="columns.length" :data="columns" border style="margin-top:16px">
      <el-table-column prop="Field" label="字段名" width="200" />
      <el-table-column prop="Type" label="类型" width="150" />
      <el-table-column prop="Null" label="允许空" width="100" align="center" />
      <el-table-column prop="Key" label="键" width="100" align="center" />
      <el-table-column prop="Default" label="默认值" width="150" />
    </el-table>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getGeneratorTables, getGeneratorTable, generateCode } from '@/api/tools'
const tables = ref([]); const columns = ref([]); const selectedTable = ref('')
const fetchTables = async () => { const res = await getGeneratorTables(); tables.value = res.data.list||[] }
const handleTableChange = async table => { const res = await getGeneratorTable(table); columns.value = res.data.columns||[] }
const handleGenerate = async () => { const res = await generateCode({table:selectedTable.value}); ElMessage.success('代码生成成功(模拟)'); console.log(res.data) }
onMounted(fetchTables)
</script>
