import request from '@/utils/request'
export function getProductList(params) { return request({ url: '/admin/products', method: 'get', params }) }
export function getProduct(id) { return request({ url: `/admin/products/${id}`, method: 'get' }) }
export function createProduct(data) { return request({ url: '/admin/products', method: 'post', data }) }
export function updateProduct(id, data) { return request({ url: `/admin/products/${id}`, method: 'put', data }) }
export function deleteProduct(id) { return request({ url: `/admin/products/${id}`, method: 'delete' }) }
export function toggleProductStatus(id, data) { return request({ url: `/admin/products/${id}/status`, method: 'put', data }) }
export function batchUpdateProducts(data) { return request({ url: '/admin/products/batch', method: 'put', data }) }
export function batchDeleteProducts(data) { return request({ url: '/admin/products/batch', method: 'delete', data }) }
export function getBrandList(params) { return request({ url: '/admin/brands', method: 'get', params }) }
