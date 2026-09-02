import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: JSON.parse(localStorage.getItem('tllos_cart') || '[]')
  }),
  getters: {
    count: state => state.items.reduce((sum, i) => sum + i.quantity, 0),
    totalPrice: state => state.items.reduce((sum, i) => sum + i.price * i.quantity, 0),
    selectedItems: state => state.items.filter(i => i.selected)
  },
  actions: {
    addItem(product) {
      const existing = this.items.find(i => i.id === product.id)
      if (existing) existing.quantity++
      else this.items.push({ ...product, quantity: 1, selected: true })
      this.save()
    },
    removeItem(id) {
      this.items = this.items.filter(i => i.id !== id)
      this.save()
    },
    updateQuantity(id, quantity) {
      const item = this.items.find(i => i.id === id)
      if (item) { item.quantity = Math.max(1, quantity); this.save() }
    },
    toggleSelect(id) {
      const item = this.items.find(i => i.id === id)
      if (item) { item.selected = !item.selected; this.save() }
    },
    clear() { this.items = []; this.save() },
    save() { localStorage.setItem('tllos_cart', JSON.stringify(this.items)) }
  }
})
