<template>
  <div class="banner-page">
    <el-card>
      <template #header>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:16px;font-weight:bold">轮播图管理</span>
          <el-button type="primary" @click="showDialog=true">新增轮播图</el-button>
        </div>
      </template>
      <el-table :data="list" border>
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="图片" width="150">
          <template #default="{row}"><el-image :src="row.image" style="width:120px;height:50px" fit="cover" /></template>
        </el-table-column>
        <el-table-column prop="title" label="标题" />
        <el-table-column prop="link_type" label="链接类型" width="100" />
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{row}"><el-tag :type="row.status===1?'success':'info'">{{ row.status===1?'启用':'禁用' }}</el-tag></template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{row}">
            <el-button size="small" @click="edit(row)">编辑</el-button>
            <el-button size="small" type="danger" @click="remove(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="showDialog" :title="form.id?'编辑轮播图':'新增轮播图'" width="500px">
      <el-form :model="form" label-width="100px">
        <el-form-item label="标题"><el-input v-model="form.title" /></el-form-item>
        <el-form-item label="图片">
          <el-input v-model="form.image" placeholder="图片URL" />
          <el-image v-if="form.image" :src="form.image" style="width:100%;height:100px;margin-top:10px" fit="cover" />
        </el-form-item>
        <el-form-item label="链接类型">
          <el-select v-model="form.link_type">
            <el-option label="无链接" value="none" />
            <el-option label="商品详情" value="goods" />
            <el-option label="商品分类" value="category" />
            <el-option label="自定义URL" value="url" />
          </el-select>
        </el-form-item>
        <el-form-item label="链接ID" v-if="form.link_type!=='none'&&form.link_type!=='url'">
          <el-input-number v-model="form.link_id" :min="1" />
        </el-form-item>
        <el-form-item label="链接URL" v-if="form.link_type==='url'">
          <el-input v-model="form.link_url" />
        </el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态"><el-switch v-model="form.status" :active-value="1" :inactive-value="0" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog=false">取消</el-button>
        <el-button type="primary" @click="save">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import request from '@/utils/request'

const list = ref([])
const showDialog = ref(false)
const form = reactive({ id: null, title: '', image: '', link_type: 'none', link_id: 0, link_url: '', sort: 0, status: 1 })

const loadList = async () => {
  const res = await request({ url: '/decorate/banners' })
  list.value = res.data?.list || res.data || []
}

const edit = (row) => { Object.assign(form, row); showDialog.value = true }

const save = async () => {
  if (form.id) {
    await request({ url: `/decorate/banners/${form.id}`, method: 'put', data: form })
  } else {
    await request({ url: '/decorate/banners', method: 'post', data: form })
  }
  ElMessage.success('保存成功')
  showDialog.value = false
  loadList()
}

const remove = async (row) => {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  await request({ url: `/decorate/banners/${row.id}`, method: 'delete' })
  ElMessage.success('删除成功')
  loadList()
}

onMounted(() => loadList())
</script>

<style scoped>
.banner-page { padding: 20px; }
</style>
