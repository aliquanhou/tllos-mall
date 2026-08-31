<template>
  <div class="shop-setting">
    <el-card>
      <template #header>店铺设置</template>
      <el-form :model="form" label-width="120px" style="max-width:600px">
        <el-form-item label="店铺名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="店铺Logo"><el-input v-model="form.logo" /></el-form-item>
        <el-form-item label="店铺描述"><el-input v-model="form.description" type="textarea" :rows="3" /></el-form-item>
        <el-form-item label="联系人"><el-input v-model="form.contact_name" /></el-form-item>
        <el-form-item label="联系电话"><el-input v-model="form.contact_phone" /></el-form-item>
        <el-form-item label="店铺地址"><el-input v-model="form.address" /></el-form-item>
        <el-form-item label="营业时间"><el-input v-model="form.business_hours" /></el-form-item>
        <el-form-item><el-button type="primary" @click="handleSave">保存</el-button></el-form-item>
      </el-form>
    </el-card>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/utils/request'
const form = ref({ name:'', logo:'', description:'', contact_name:'', contact_phone:'', address:'', business_hours:'' })
onMounted(async () => { const res = await request({ url:'/merchant/shop' }); form.value = res.data || form.value })
const handleSave = async () => { await request({ url:'/merchant/shop', method:'put', data:form.value }); ElMessage.success('保存成功') }
</script>
