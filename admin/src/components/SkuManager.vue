<template>
  <div class="sku-manager">
    <el-card shadow="never">
      <template #header>
        <div class="sku-header">
          <span>SKU规格管理</span>
          <el-switch v-model="enabled" active-text="启用多规格" @change="handleToggle" />
        </div>
      </template>
      <div v-if="enabled">
        <div class="spec-section">
          <div class="spec-title">规格名称</div>
          <div class="spec-names">
            <el-tag v-for="(name, idx) in specNames" :key="idx" closable @close="removeSpecName(idx)" style="margin-right:8px">{{ name }}</el-tag>
            <el-input v-model="newSpecName" placeholder="如：颜色、尺寸" size="small" style="width:140px" @keyup.enter="addSpecName" />
            <el-button size="small" @click="addSpecName">添加</el-button>
          </div>
        </div>
        <div v-for="(name, nIdx) in specNames" :key="nIdx" class="spec-section">
          <div class="spec-title">{{ name }}值</div>
          <div class="spec-values">
            <el-tag v-for="(val, vIdx) in specValues[nIdx] || []" :key="vIdx" closable @close="removeSpecValue(nIdx, vIdx)" style="margin-right:8px">{{ val }}</el-tag>
            <el-input v-model="newSpecValues[nIdx]" :placeholder="`添加${name}值`" size="small" style="width:120px" @keyup.enter="addSpecValue(nIdx)" />
            <el-button size="small" @click="addSpecValue(nIdx)">添加</el-button>
          </div>
        </div>
        <el-table :data="skuList" border style="margin-top:16px" v-if="skuList.length > 0">
          <el-table-column label="规格组合" min-width="200">
            <template #default="{row}">{{ row.spec_text }}</template>
          </el-table-column>
          <el-table-column label="SKU编码" width="160">
            <template #default="{row}"><el-input v-model="row.sku_no" size="small" /></template>
          </el-table-column>
          <el-table-column label="价格" width="120">
            <template #default="{row}"><el-input-number v-model="row.price" :min="0" :precision="2" size="small" style="width:100%" /></template>
          </el-table-column>
          <el-table-column label="库存" width="120">
            <template #default="{row}"><el-input-number v-model="row.stock" :min="0" size="small" style="width:100%" /></template>
          </el-table-column>
          <el-table-column label="重量" width="120">
            <template #default="{row}"><el-input-number v-model="row.weight" :min="0" :precision="2" size="small" style="width:100%" /></template>
          </el-table-column>
        </el-table>
        <el-empty v-else description="请先添加规格名称和规格值" :image-size="60" />
      </div>
      <el-empty v-else description="未启用多规格，商品将使用统一价格和库存" :image-size="60" />
    </el-card>
  </div>
</template>
<script setup>
import { ref, watch, computed } from 'vue'
const props = defineProps({ modelValue: { type: Array, default: () => [] }, isSku: { type: Number, default: 0 } })
const emit = defineEmits(['update:modelValue', 'update:isSku'])
const enabled = ref(props.isSku === 1)
const specNames = ref([])
const specValues = ref([])
const newSpecName = ref('')
const newSpecValues = ref([])
const skuList = computed(() => {
  if (specNames.value.length === 0 || specValues.value.every(v => !v || v.length === 0)) return []
  const combinations = cartesian(specValues.value.filter(v => v && v.length > 0))
  return combinations.map(combo => {
    const specText = combo.map((v, i) => `${specNames.value[i]}:${v}`).join(' / ')
    const existing = props.modelValue.find(s => s.spec_text === specText)
    return existing || { spec_text: specText, sku_no: '', price: 0, stock: 0, weight: 0 }
  })
})
const cartesian = (arrays) => arrays.reduce((a, b) => a.flatMap(x => b.map(y => [...x, y])), [[]])
const handleToggle = (val) => { emit('update:isSku', val ? 1 : 0); if (!val) { specNames.value = []; specValues.value = []; emit('update:modelValue', []) } }
const addSpecName = () => { if (!newSpecName.value.trim()) return; specNames.value.push(newSpecName.value.trim()); specValues.value.push([]); newSpecValues.value.push(''); newSpecName.value = '' }
const removeSpecName = (idx) => { specNames.value.splice(idx, 1); specValues.value.splice(idx, 1); emitSkus() }
const addSpecValue = (nIdx) => { if (!newSpecValues.value[nIdx]?.trim()) return; if (!specValues.value[nIdx]) specValues.value[nIdx] = []; specValues.value[nIdx].push(newSpecValues.value[nIdx].trim()); newSpecValues.value[nIdx] = ''; emitSkus() }
const removeSpecValue = (nIdx, vIdx) => { specValues.value[nIdx].splice(vIdx, 1); emitSkus() }
const emitSkus = () => { emit('update:modelValue', skuList.value) }
watch(skuList, (val) => emit('update:modelValue', val), { deep: true })
watch(() => props.isSku, (val) => { enabled.value = val === 1 })
</script>
<style scoped>
.sku-header{display:flex;justify-content:space-between;align-items:center}
.spec-section{margin-bottom:16px}
.spec-title{font-weight:500;margin-bottom:8px;color:#606266}
.spec-names,.spec-values{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
</style>
