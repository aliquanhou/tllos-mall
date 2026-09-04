import { defineStore } from 'pinia'
import { ref } from 'vue'
export const useOrderStore = defineStore('order', () => {
  const orders = ref([])
  const orderDetail = ref(null)
  const stats = ref({ total: 0, today: 0, pending: 0, shipping: 0 })
  const setOrders = data => orders.value = data
  const setOrderDetail = data => orderDetail.value = data
  const setStats = data => stats.value = data
  return { orders, orderDetail, stats, setOrders, setOrderDetail, setStats }
})
