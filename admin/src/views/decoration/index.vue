<template>
  <el-card shadow="never">
    <template #header><span>装修管理</span></template>
    <el-row :gutter="16">
      <el-col :span="8"><el-card shadow="hover" class="deco-card" @click="handleDeco('home')"><el-icon :size="48" color="#409eff"><Picture /></el-icon><h3>首页装修</h3><p>轮播图、导航、商品推荐</p></el-card></el-col>
      <el-col :span="8"><el-card shadow="hover" class="deco-card" @click="handleDeco('category')"><el-icon :size="48" color="#67c23a"><Grid /></el-icon><h3>分类页装修</h3><p>分类导航、商品展示</p></el-card></el-col>
      <el-col :span="8"><el-card shadow="hover" class="deco-card" @click="handleDeco('member')"><el-icon :size="48" color="#e6a23c"><User /></el-icon><h3>会员中心装修</h3><p>个人中心、会员权益</p></el-card></el-col>
    </el-row>
    <el-divider>页面模板</el-divider>
    <el-table :data="templates" border>
      <el-table-column prop="name" label="模板名称" min-width="150" />
      <el-table-column prop="type" label="适用页面" width="120" align="center" />
      <el-table-column prop="description" label="描述" min-width="200" />
      <el-table-column label="状态" width="100" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'使用中':'未使用'}}</el-tag></template></el-table-column>
      <el-table-column label="操作" width="150" align="center"><template #default="{row}"><el-button size="small" type="primary" link>编辑</el-button><el-button size="small" link>预览</el-button></template></el-table-column>
    </el-table>
  </el-card>
</template>
<script setup>
import { Picture, Grid, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
const templates = [
  { id:1, name:'经典电商模板', type:'首页', description:'轮播图+分类导航+商品推荐', status:1 },
  { id:2, name:'简约风格模板', type:'首页', description:'简洁大气，适合高端商品', status:0 },
  { id:3, name:'活动促销模板', type:'首页', description:'突出促销活动，适合大促', status:0 },
]
const handleDeco = type => { ElMessage.info(`正在打开${type}页面装修编辑器...`) }
</script>
<style scoped>.deco-card{text-align:center;padding:20px 0;cursor:pointer;margin-bottom:16px}.deco-card h3{margin:12px 0 8px}.deco-card p{color:#909399;font-size:13px}</style>
