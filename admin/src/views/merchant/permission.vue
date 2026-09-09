<template>
  <el-card shadow="never">
    <template #header><span>商家端权限</span></template>
    <el-tabs v-model="activeTab">
      <el-tab-pane label="商家管理员" name="admins">
        <el-table :data="admins" border>
          <el-table-column prop="id" label="ID" width="80" align="center" />
          <el-table-column prop="shop_id" label="店铺ID" width="100" align="center" />
          <el-table-column prop="username" label="用户名" width="150" />
          <el-table-column prop="nickname" label="昵称" width="120" />
          <el-table-column prop="role_name" label="角色" width="120" />
          <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="商家角色" name="roles">
        <el-table :data="roles" border>
          <el-table-column prop="id" label="ID" width="80" align="center" />
          <el-table-column prop="shop_id" label="店铺ID" width="100" align="center" />
          <el-table-column prop="name" label="角色名称" width="150" />
          <el-table-column prop="permissions" label="权限" min-width="200" show-overflow-tooltip />
          <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="商家部门" name="depts">
        <el-table :data="depts" border>
          <el-table-column prop="id" label="ID" width="80" align="center" />
          <el-table-column prop="name" label="部门名称" width="150" />
          <el-table-column prop="sort" label="排序" width="100" align="center" />
          <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="商家岗位" name="jobs">
        <el-table :data="jobs" border>
          <el-table-column prop="id" label="ID" width="80" align="center" />
          <el-table-column prop="name" label="岗位名称" width="150" />
          <el-table-column prop="dept_id" label="部门ID" width="100" align="center" />
          <el-table-column prop="sort" label="排序" width="100" align="center" />
          <el-table-column label="状态" width="90" align="center"><template #default="{row}"><el-tag :type="row.status===1?'success':'info'" size="small">{{row.status===1?'启用':'禁用'}}</el-tag></template></el-table-column>
        </el-table>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { getShopAdminList, getShopRoleList, getShopDeptList, getShopJobList } from '@/api/tools'
const activeTab = ref('admins')
const admins = ref([]); const roles = ref([]); const depts = ref([]); const jobs = ref([])
const fetchData = async () => {
  admins.value = (await getShopAdminList()).data.list||[]
  roles.value = (await getShopRoleList()).data.list||[]
  depts.value = (await getShopDeptList()).data.list||[]
  jobs.value = (await getShopJobList()).data.list||[]
}
onMounted(fetchData)
</script>
