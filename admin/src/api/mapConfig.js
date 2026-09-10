import request from '@/utils/request'

// 获取地图配置
export function getMapConfig() {
  return request({
    url: '/admin/map-config',
    method: 'get'
  })
}

// 保存地图配置
export function saveMapConfig(data) {
  return request({
    url: '/admin/map-config',
    method: 'post',
    data
  })
}
