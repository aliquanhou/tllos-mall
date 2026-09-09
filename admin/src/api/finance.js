import request from '@/utils/request'
export const getIncomeList = params => request({ url: '/admin/finance/income', method: 'get', params })
export const getRefundList = params => request({ url: '/admin/finance/refund', method: 'get', params })
export const getWithdrawList = params => request({ url: '/admin/finance/withdraw', method: 'get', params })
export const auditWithdraw = (id, data) => request({ url: `/admin/finance/withdraw/${id}/audit`, method: 'post', data })
export const payWithdraw = id => request({ url: `/admin/finance/withdraw/${id}/pay`, method: 'post' })
export const getSettlementList = params => request({ url: '/admin/finance/settlement', method: 'get', params })
export const confirmSettlement = id => request({ url: `/admin/finance/settlement/${id}/confirm`, method: 'post' })
