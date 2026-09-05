import request from '@/utils/request'

// 页面模板列表（分页+搜索+筛选）
export const getPageTemplateList = (params) => request({
  url: '/admin/decorate/page-templates',
  method: 'get',
  params
})

// 创建页面
export const createPageTemplate = (data) => request({
  url: '/admin/decorate/page-templates',
  method: 'post',
  data
})

// 页面详情（draft=1返回草稿，否则返回已发布）
export const getPageTemplate = (id, draft = 0) => request({
  url: `/admin/decorate/page-templates/${id}`,
  method: 'get',
  params: { draft }
})

// 保存草稿
export const savePageDraft = (id, config) => request({
  url: `/admin/decorate/page-templates/${id}/draft`,
  method: 'put',
  data: { config }
})

// 发布页面
export const publishPageTemplate = (id) => request({
  url: `/admin/decorate/page-templates/${id}/publish`,
  method: 'post'
})

// 历史版本列表
export const getPageVersions = (id) => request({
  url: `/admin/decorate/page-templates/${id}/versions`,
  method: 'get'
})

// 版本回滚（恢复到草稿）
export const rollbackPageVersion = (id, versionId) => request({
  url: `/admin/decorate/page-templates/${id}/rollback`,
  method: 'post',
  data: { version_id: versionId }
})

// 导出配置（返回JSON文件下载URL）
export const exportPageTemplate = (id) => request({
  url: `/admin/decorate/page-templates/${id}/export`,
  method: 'get',
  responseType: 'blob'
})

// 导入配置
export const importPageTemplate = (formData) => request({
  url: '/admin/decorate/page-templates/import',
  method: 'post',
  data: formData,
  headers: { 'Content-Type': 'multipart/form-data' }
})

// 删除页面
export const deletePageTemplate = (id) => request({
  url: `/admin/decorate/page-templates/${id}`,
  method: 'delete'
})

// 前台渲染接口（按设备过滤）
export const renderPageTemplate = (slug, device = 'pc') => request({
  url: `/page-templates/${slug}/render`,
  method: 'get',
  params: { device }
})
