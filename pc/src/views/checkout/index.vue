<template>
  <div class="checkout-page">
    <PageHeader title="确认订单" subtitle="核对信息 安心支付" />
    <div class="container">
      <h2 class="page-title">确认订单</h2>
      <div class="checkout-wrapper">
        <div class="checkout-left">
          <!-- 收货地址 -->
          <div class="checkout-section">
            <h3 class="section-title">收货地址</h3>
            <div class="address-list" v-if="addresses.length">
              <div class="address-card" v-for="addr in addresses" :key="addr.id" :class="{active: selectedAddressId === addr.id}" @click="selectedAddressId = addr.id">
                <div class="address-info">
                  <span class="receiver">{{ addr.receiver_name }}</span>
                  <span class="mobile">{{ addr.receiver_mobile }}</span>
                  <span class="default-tag" v-if="addr.is_default">默认</span>
                </div>
                <div class="address-detail">{{ addr.province }}{{ addr.city }}{{ addr.district }}{{ addr.detail }}</div>
              </div>
            </div>
            <div class="no-address" v-else>
              <p>暂无收货地址</p>
              <el-button type="primary" @click="$router.push('/address/edit')">添加地址</el-button>
            </div>
          </div>
          <!-- 商品清单 -->
          <div class="checkout-section">
            <h3 class="section-title">商品清单</h3>
            <div class="order-items">
              <div class="order-item" v-for="item in orderItems" :key="item.id || item.product_id">
                <div class="item-image"><img loading="lazy" :src="getImageUrl(item.main_image || item.image)" :alt="item.name" @error="handleImgError($event)" /></div>
                <div class="item-info">
                  <div class="item-name">{{ item.name }}</div>
                  <div class="item-spec" v-if="item.specs">{{ item.specs }}</div>
                </div>
                <div class="item-price">¥{{ item.price }}</div>
                <div class="item-quantity">x{{ item.quantity }}</div>
                <div class="item-subtotal">¥{{ (item.price * item.quantity).toFixed(2) }}</div>
              </div>
            </div>
          </div>
          <!-- 支付方式 -->
          <div class="checkout-section">
            <h3 class="section-title">支付方式</h3>
            <div class="payment-methods">
              <div class="payment-method" v-for="m in paymentMethods" :key="m.value" :class="{active: paymentMethod === m.value}" @click="paymentMethod = m.value">
                <el-icon size="24"><component :is="m.icon" /></el-icon>
                <span>{{ m.label }}</span>
              </div>
            </div>
          </div>
          <!-- 订单备注 -->
          <div class="checkout-section">
            <h3 class="section-title">订单备注</h3>
            <el-input v-model="remark" type="textarea" :rows="3" placeholder="选填，可填写您的特殊需求" maxlength="200" show-word-limit />
          </div>
        </div>
        <div class="checkout-right">
          <div class="order-summary">
            <h3>订单摘要</h3>
            <div class="summary-row"><span>商品件数</span><span>{{ totalCount }} 件</span></div>
            <div class="summary-row"><span>商品总额</span><span>¥{{ totalAmount.toFixed(2) }}</span></div>
            <div class="summary-row"><span>运费</span><span class="free">免运费</span></div>
            <div class="summary-total"><span>应付总额</span><span class="total-price">¥{{ totalAmount.toFixed(2) }}</span></div>
            <el-button type="danger" size="large" class="submit-btn" :disabled="!selectedAddressId || submitting" @click="submitOrder">{{ submitting ? '提交中...' : '提交订单' }}</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { ChatDotRound, Wallet, CreditCard } from '@element-plus/icons-vue'
import { getCart } from '@/api/cart'
import { getProductDetail } from '@/api/product'
import { getAddressList } from '@/api/user'
import request from '@/utils/request'
const router = useRouter()
const addresses = ref([])
const selectedAddressId = ref(null)
const orderItems = ref([])
const paymentMethod = ref('alipay')
const remark = ref('')
const submitting = ref(false)
const isBuyNow = ref(false)
const paymentMethods = [
  { value: 'alipay', label: '支付宝', icon: Wallet },
  { value: 'balance', label: '余额支付', icon: CreditCard },
]
const totalCount = computed(() => orderItems.value.reduce((sum, i) => sum + (i.quantity || 1), 0))
const totalAmount = computed(() => orderItems.value.reduce((sum, i) => sum + (i.price || 0) * (i.quantity || 1), 0))
const selectedAddress = computed(() => addresses.value.find(a => a.id === selectedAddressId.value))

const getImageUrl = (url) => {
  if (!url) return '/placeholder.svg'
  if (url.startsWith('http')) return url
  return 'https://mall.tllos.com' + (url.startsWith('/') ? '' : '/') + url
}

const handleImgError = (event) => {
  event.target.src = '/placeholder.svg'
}

const fetchData = async () => {
  try {
    try {
      const addrRes = await getAddressList()
      addresses.value = addrRes.data?.list || addrRes.data || []
      if (addresses.value.length > 0) {
        const defaultAddr = addresses.value.find(a => a.is_default) || addresses.value[0]
        selectedAddressId.value = defaultAddr.id
      }
    } catch (addrError) {
      console.error('获取地址列表失败:', addrError)
    }
    const buyNowStr = sessionStorage.getItem('buy_now_item')
    if (buyNowStr) {
      isBuyNow.value = true
      const buyNowItem = JSON.parse(buyNowStr)
      orderItems.value = [{
        id: 'buynow_' + buyNowItem.product_id,
        product_id: buyNowItem.product_id,
        name: buyNowItem.name,
        price: buyNowItem.price,
        main_image: buyNowItem.main_image,
        quantity: buyNowItem.quantity,
        specs: buyNowItem.specs ? Object.entries(buyNowItem.specs).map(([k,v]) => `${k}:${v}`).join(' ') : ''
      }]
      return
    }
    
    isBuyNow.value = false
    const cartRes = await getCart()
    orderItems.value = (cartRes.data?.list || cartRes.data || []).filter(i => i.selected !== false)
    if (orderItems.value.length === 0) { ElMessage.warning('请先选择商品'); router.push('/cart') }
  } catch (e) { console.error(e) }
}
const submitOrder = async () => {
  if (!selectedAddressId.value) { ElMessage.warning('请选择收货地址'); return }
  if (!selectedAddress.value) { ElMessage.warning('收货地址信息不完整'); return }
  if (orderItems.value.length === 0) { ElMessage.warning('请先选择商品'); return }
  submitting.value = true
  try {
    const addr = selectedAddress.value
    const items = orderItems.value.map(item => ({
      product_id: item.product_id || item.id,
      quantity: item.quantity || 1,
      sku_id: item.sku_id || null
    }))
    const orderRes = await request({
      url: '/orders',
      method: 'post',
      data: {
        items,
        receiver_name: addr.receiver_name || addr.name || '匿名用户',
        receiver_mobile: addr.receiver_mobile || addr.mobile || '00000000000',
        province_id: addr.province_id || 1,
        city_id: addr.city_id || 1,
        district_id: addr.district_id || 1,
        province_name: addr.province_name || addr.province || '广东省',
        city_name: addr.city_name || addr.city || '惠州市',
        district_name: addr.district_name || addr.district || '大亚湾区',
        receiver_address: addr.receiver_address || addr.detail || addr.address || '未填写详细地址',
        payment_method: paymentMethod.value,
        remark: remark.value
      }
    })
    if (isBuyNow.value) {
      sessionStorage.removeItem('buy_now_item')
    }
    const orderId = orderRes.data?.order_id || orderRes.data?.id
    const orderNo = orderRes.data?.order_no
    ElMessage.success('订单提交成功')
    setTimeout(() => {
      if (orderNo) {
        router.push(`/pay/${orderNo}`)
      } else {
        router.push('/orders')
      }
    }, 800)
  } catch (e) {
    console.error('提交订单失败:', e)
    ElMessage.error(e.response?.data?.message || '提交失败，请稍后重试')
  } finally {
    submitting.value = false
  }
}
onMounted(fetchData)
</script>
<style scoped>
.checkout-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.page-title { font-size: 22px; color: #333; margin: 0 0 20px 0; }
.checkout-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.checkout-left { flex: 1; }
.checkout-section { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
.section-title { font-size: 16px; color: #333; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.address-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.address-card { border: 2px solid #eee; border-radius: 8px; padding: 16px; cursor: pointer; transition: all 0.2s; }
.address-card:hover { border-color: #1677ff; }
.address-card.active { border-color: #1677ff; background: #e6f4ff; }
.address-info { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.receiver { font-size: 15px; font-weight: bold; color: #333; }
.mobile { font-size: 13px; color: #666; }
.default-tag { background: #1677ff; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
.address-detail { font-size: 13px; color: #666; line-height: 1.5; }
.no-address { text-align: center; padding: 20px; color: #999; }
.order-items { }
.order-item { display: grid; grid-template-columns: 60px 1fr 80px 60px 80px; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; align-items: center; }
.order-item:last-child { border-bottom: none; }
.item-image { width: 60px; height: 60px; border-radius: 4px; overflow: hidden; background: #f5f5f5; }
.item-image img { width: 100%; height: 100%; object-fit: cover; }
.item-name { font-size: 14px; color: #333; margin-bottom: 4px; }
.item-spec { font-size: 12px; color: #999; }
.item-price { font-size: 14px; color: #333; text-align: right; }
.item-quantity { font-size: 14px; color: #666; text-align: center; }
.item-subtotal { font-size: 15px; color: #f56c6c; font-weight: bold; text-align: right; }
.payment-methods { display: flex; gap: 12px; }
.payment-method { display: flex; align-items: center; gap: 8px; padding: 12px 20px; border: 2px solid #eee; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
.payment-method:hover { border-color: #1677ff; }
.payment-method.active { border-color: #1677ff; background: #e6f4ff; color: #1677ff; }
.checkout-right { width: 300px; flex-shrink: 0; position: sticky; top: 20px; }
.order-summary { background: #fff; border-radius: 8px; padding: 20px; }
.order-summary h3 { font-size: 16px; color: #333; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
.summary-row { display: flex; justify-content: space-between; font-size: 14px; color: #666; margin-bottom: 12px; }
.summary-row .free { color: #67c23a; }
.summary-total { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-top: 1px solid #f0f0f0; margin-top: 8px; }
.total-price { font-size: 24px !important; color: #f56c6c !important; font-weight: bold; }
.submit-btn { width: 100%; margin-top: 16px; background: #1677ff; border-color: #1677ff; }
.submit-btn:hover { background: #4096ff; border-color: #4096ff; }

@media (max-width: 768px) {
  .checkout-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  .page-title { font-size: 18px; margin-bottom: 12px; }
  .checkout-wrapper { flex-direction: column; gap: 10px; }
  .checkout-left { width: 100%; }
  .checkout-right { width: 100%; position: static; order: -1; }
  .checkout-section { padding: 14px; border-radius: 6px; margin-bottom: 10px; }
  .section-title { font-size: 15px; margin-bottom: 10px; padding-bottom: 8px; }
  .address-list { grid-template-columns: 1fr; gap: 8px; }
  .address-card { padding: 12px; border-radius: 6px; }
  .address-info { gap: 8px; margin-bottom: 6px; flex-wrap: wrap; }
  .receiver { font-size: 14px; }
  .mobile { font-size: 12px; }
  .default-tag { font-size: 10px; padding: 1px 6px; }
  .address-detail { font-size: 12px; line-height: 1.5; }
  .no-address { padding: 16px; font-size: 13px; }
  .order-item {
    display: flex !important;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px 0;
    align-items: flex-start;
  }
  .item-image { width: 60px; height: 60px; flex-shrink: 0; border-radius: 4px; }
  .item-info { flex: 1; min-width: 0; }
  .item-name { font-size: 13px; margin-bottom: 2px; }
  .item-spec { font-size: 11px; }
  .item-price { font-size: 12px; color: #666; text-align: left; }
  .item-price::before { content: "单价: "; color: #999; }
  .item-quantity { font-size: 12px; color: #666; text-align: left; }
  .item-quantity::before { content: "数量: "; color: #999; }
  .item-subtotal { font-size: 14px; text-align: left; margin-left: auto; }
  .item-subtotal::before { content: "小计: "; color: #999; font-size: 12px; font-weight: normal; }
  .payment-methods { flex-wrap: wrap; gap: 8px; }
  .payment-method { padding: 8px 14px; font-size: 13px; border-radius: 6px; }
  .order-summary { padding: 14px; border-radius: 6px; }
  .order-summary h3 { font-size: 15px; margin-bottom: 10px; padding-bottom: 8px; }
  .summary-row { font-size: 13px; margin-bottom: 8px; }
  .summary-total { padding: 12px 0; margin-top: 6px; }
  .total-price { font-size: 20px !important; }
  .submit-btn { margin-top: 12px; font-size: 14px !important; padding: 10px !important; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .item-image { width: 50px; height: 50px; }
  .item-name { font-size: 12px; }
  .page-title { font-size: 16px; }
}
</style>
