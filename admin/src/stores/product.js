import { defineStore } from 'pinia'
import { ref } from 'vue'
export const useProductStore = defineStore('product', () => {
  const categories = ref([])
  const products = ref([])
  const setCategories = data => categories.value = data
  const setProducts = data => products.value = data
  return { categories, products, setCategories, setProducts }
})
