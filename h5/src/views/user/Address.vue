<template>
  <div class="address-page">
    <div class="address-list">
      <div v-for="addr in list" :key="addr.id" class="address-item">
        <div class="addr-top"><span class="name">{{addr.name}}</span><span class="mobile">{{addr.mobile}}</span><span v-if="addr.is_default" class="tag-default">默认</span></div>
        <div class="addr-detail">{{addr.province_name}}{{addr.city_name}}{{addr.district_name}}{{addr.detail}}</div>
        <div class="addr-actions"><button class="btn btn-sm" @click="handleEdit(addr)">编辑</button><button class="btn btn-sm btn-danger" @click="handleDelete(addr)">删除</button></div>
      </div>
      <div v-if="list.length===0" class="empty">暂无收货地址</div>
    </div>
    <div class="footer"><button class="btn btn-primary btn-block" @click="handleAdd">新增地址</button></div>
    <div v-if="dialogVisible" class="modal-mask" @click.self="dialogVisible=false">
      <div class="modal">
        <div class="modal-header">{{isEdit?'编辑地址':'新增地址'}}</div>
        <div class="modal-body">
          <div class="form-item"><label>收货人</label><input v-model="form.name" /></div>
          <div class="form-item"><label>手机号</label><input v-model="form.mobile" /></div>
          <div class="form-item"><label>省份</label><input v-model="form.province_name" /></div>
          <div class="form-item"><label>城市</label><input v-model="form.city_name" /></div>
          <div class="form-item"><label>区县</label><input v-model="form.district_name" /></div>
          <div class="form-item"><label>详细地址</label><textarea v-model="form.detail"></textarea></div>
          <div class="form-item"><label>设为默认</label><input type="checkbox" v-model="form.is_default" :true-value="1" :false-value="0" /></div>
        </div>
        <div class="modal-footer"><button class="btn" @click="dialogVisible=false">取消</button><button class="btn btn-primary" @click="handleSubmit">保存</button></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import request from '@/utils/request'
const list = ref([]); const dialogVisible = ref(false); const isEdit = ref(false)
const form = ref({ id:null, name:'', mobile:'', province_name:'', city_name:'', district_name:'', detail:'', is_default:0 })
const fetchList = async () => { const res = await request({url:'/user/addresses'}); list.value = res.data || [] }
const handleAdd = () => { isEdit.value=false; form.value={id:null,name:'',mobile:'',province_name:'',city_name:'',district_name:'',detail:'',is_default:0}; dialogVisible.value=true }
const handleEdit = row => { isEdit.value=true; form.value={...row}; dialogVisible.value=true }
const handleSubmit = async () => { if(isEdit.value){await request({url:`/user/addresses/${form.value.id}`,method:'put',data:form.value})}else{await request({url:'/user/addresses',method:'post',data:form.value})}; alert('保存成功'); dialogVisible.value=false; fetchList() }
const handleDelete = async row => { if(confirm('确定删除？')){await request({url:`/user/addresses/${row.id}`,method:'delete'}); alert('删除成功'); fetchList()} }
onMounted(fetchList)
</script>
<style scoped>.address-page{min-height:100vh;background:#f5f5f5;padding-bottom:80px}.address-list{padding:12px}.address-item{background:#fff;border-radius:12px;padding:16px;margin-bottom:12px}.addr-top{display:flex;align-items:center;gap:12px;margin-bottom:8px}.addr-top .name{font-weight:600;font-size:16px}.addr-top .mobile{color:#666}.tag-default{background:#f56c6c;color:#fff;font-size:11px;padding:2px 6px;border-radius:4px}.addr-detail{color:#666;font-size:14px;line-height:1.6}.addr-actions{display:flex;gap:12px;margin-top:12px;justify-content:flex-end}.empty{text-align:center;padding:60px 0;color:#999}.footer{position:fixed;bottom:0;left:0;right:0;padding:12px 16px;background:#fff;box-shadow:0 -2px 10px rgba(0,0,0,.05)}.btn{padding:8px 16px;border:1px solid #ddd;background:#fff;border-radius:6px;cursor:pointer;font-size:14px}.btn-sm{padding:4px 12px;font-size:12px}.btn-primary{background:#667eea;color:#fff;border-color:#667eea}.btn-danger{background:#f56c6c;color:#fff;border-color:#f56c6c}.btn-block{width:100%;padding:12px}.modal-mask{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:999}.modal{width:90%;max-width:400px;background:#fff;border-radius:12px;overflow:hidden}.modal-header{padding:16px;font-weight:600;border-bottom:1px solid #eee}.modal-body{padding:16px;max-height:60vh;overflow-y:auto}.modal-footer{padding:12px 16px;border-top:1px solid #eee;display:flex;gap:12px;justify-content:flex-end}.form-item{margin-bottom:12px}.form-item label{display:block;margin-bottom:6px;font-size:14px;color:#666}.form-item input,.form-item textarea{width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box}.form-item textarea{min-height:60px}</style>
