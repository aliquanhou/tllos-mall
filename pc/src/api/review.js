import request from '@/utils/request'

// 评价（商品评论）
export const getProductComments = (productId, params) => request({ url: `/products/${productId}/comments`, method: 'get', params })
export const addComment = data => request({ url: '/comments', method: 'post', data })
export const getMyComments = params => request({ url: '/comments/my', method: 'get', params })

export const getProductReviewList = params => request({ url: '/comments/product', method: 'get', params })
