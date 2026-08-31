<template>
  <el-card shadow="never">
    <template #header><span>地区管理</span></template>
    <el-row :gutter="16">
      <el-col :span="8">
        <h4>省份</h4>
        <el-table :data="provinceList" border highlight-current-row @current-change="handleProvinceChange" size="small">
          <el-table-column prop="name" label="省份" />
          <el-table-column prop="area_code" label="编码" width="100" />
        </el-table>
      </el-col>
      <el-col :span="8">
        <h4>城市</h4>
        <el-table :data="cityList" border highlight-current-row @current-change="handleCityChange" size="small">
          <el-table-column prop="name" label="城市" />
          <el-table-column prop="area_code" label="编码" width="100" />
        </el-table>
      </el-col>
      <el-col :span="8">
        <h4>区县</h4>
        <el-table :data="districtList" border size="small">
          <el-table-column prop="name" label="区县" />
          <el-table-column prop="area_code" label="编码" width="100" />
        </el-table>
      </el-col>
    </el-row>
  </el-card>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { getAreaList } from '@/api/systemConfig'
const provinceList = ref([]); const cityList = ref([]); const districtList = ref([])
const currentProvince = ref(null); const currentCity = ref(null)
const fetchProvinces = async () => { const res = await getAreaList({level:1}); provinceList.value = res.data.list||[] }
const handleProvinceChange = async row => { currentProvince.value=row; cityList.value=[]; districtList.value=[]; const res = await getAreaList({parent_id:row.id}); cityList.value = res.data.list||[] }
const handleCityChange = async row => { currentCity.value=row; districtList.value=[]; const res = await getAreaList({parent_id:row.id}); districtList.value = res.data.list||[] }
onMounted(fetchProvinces)
</script>
