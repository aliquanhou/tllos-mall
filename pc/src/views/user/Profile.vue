<template>
  <div class="profile-page">
    <div class="container">
      <div class="profile-wrapper">
        <!-- 左侧菜单 -->
        <aside class="profile-sidebar">
          <div class="user-card">
            <el-avatar :size="64">{{ userInfo?.nickname?.charAt(0) || 'U' }}</el-avatar>
            <div class="user-name">{{ userInfo?.nickname || '用户' }}</div>
            <div class="user-level" v-if="userInfo?.level_name">{{ userInfo.level_name }}</div>
          </div>
          <div class="menu-list">
            <div class="menu-group">
              <div class="group-title">我的订单</div>
              <div class="menu-item" :class="{active: activeMenu === 'orders'}" @click="activeMenu = 'orders'">
                <el-icon><List /></el-icon> 全部订单
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'after_sale'}" @click="activeMenu = 'after_sale'">
                <el-icon><RefreshLeft /></el-icon> 售后管理
              </div>
            </div>
            <div class="menu-group">
              <div class="group-title">我的资产</div>
              <div class="menu-item" :class="{active: activeMenu === 'coupons'}" @click="goMenu('/coupons')">
                <el-icon><Ticket /></el-icon> 优惠券
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'points'}" @click="activeMenu = 'points'">
                <el-icon><Medal /></el-icon> 我的积分
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'balance'}" @click="activeMenu = 'balance'">
                <el-icon><Wallet /></el-icon> 账户余额
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'collects'}" @click="goMenu('/collects')">
                <el-icon><Star /></el-icon> 我的收藏
              </div>
            </div>
            <div class="menu-group">
              <div class="group-title">推广中心</div>
              <div class="menu-item" :class="{active: activeMenu === 'distribution'}" @click="goMenu('/distribution/apply')">
                <el-icon><Money /></el-icon> 申请分销
              </div>
            </div>
            <div class="menu-group">
              <div class="group-title">账户设置</div>
              <div class="menu-item" :class="{active: activeMenu === 'profile'}" @click="activeMenu = 'profile'">
                <el-icon><User /></el-icon> 个人资料
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'address'}" @click="goMenu('/address')">
                <el-icon><Location /></el-icon> 收货地址
              </div>
              <div class="menu-item" :class="{active: activeMenu === 'password'}" @click="activeMenu = 'password'">
                <el-icon><Lock /></el-icon> 修改密码
              </div>
            </div>
            <div class="menu-group">
              <div class="menu-item logout" @click="handleLogout">
                <el-icon><SwitchButton /></el-icon> 退出登录
              </div>
            </div>
          </div>
        </aside>

        <!-- 右侧内容 -->
        <div class="profile-content">
          <!-- 数据概览 -->
          <div class="stats-card" v-if="activeMenu === 'profile'">
            <div class="stats-grid">
              <div class="stat-item" @click="goMenu('/orders')">
                <div class="stat-icon" style="background:#fef0f0;color:#f56c6c"><el-icon><List /></el-icon></div>
                <div class="stat-info"><div class="stat-value">{{ stats.orders || 0 }}</div><div class="stat-label">全部订单</div></div>
              </div>
              <div class="stat-item">
                <div class="stat-icon" style="background:#fdf6ec;color:#e6a23c"><el-icon><Ticket /></el-icon></div>
                <div class="stat-info"><div class="stat-value">{{ stats.coupons || 0 }}</div><div class="stat-label">优惠券</div></div>
              </div>
              <div class="stat-item">
                <div class="stat-icon" style="background:#f0f9eb;color:#67c23a"><el-icon><Medal /></el-icon></div>
                <div class="stat-info"><div class="stat-value">{{ stats.points || 0 }}</div><div class="stat-label">积分</div></div>
              </div>
              <div class="stat-item">
                <div class="stat-icon" style="background:#ecf5ff;color:#409eff"><el-icon><Wallet /></el-icon></div>
                <div class="stat-info"><div class="stat-value">¥{{ stats.balance || '0.00' }}</div><div class="stat-label">账户余额</div></div>
              </div>
            </div>
          </div>

          <!-- 订单快捷入口 -->
          <div class="quick-orders" v-if="activeMenu === 'profile'">
            <h3 class="section-title">我的订单</h3>
            <div class="order-tabs">
              <div class="order-tab" @click="goMenu('/orders')"><el-icon size="28"><Wallet /></el-icon><span>待付款</span></div>
              <div class="order-tab" @click="goMenu('/orders')"><el-icon size="28"><Box /></el-icon><span>待发货</span></div>
              <div class="order-tab" @click="goMenu('/orders')"><el-icon size="28"><Van /></el-icon><span>待收货</span></div>
              <div class="order-tab" @click="goMenu('/orders')"><el-icon size="28"><CircleCheck /></el-icon><span>已完成</span></div>
              <div class="order-tab" @click="goMenu('/orders')"><el-icon size="28"><RefreshLeft /></el-icon><span>售后</span></div>
            </div>
          </div>

          <!-- 个人资料 -->
          <div class="content-card" v-if="activeMenu === 'profile'">
            <h3 class="section-title">个人资料</h3>
            <el-form :model="profileForm" label-width="100px">
              <el-form-item label="昵称"><el-input v-model="profileForm.nickname" /></el-form-item>
              <el-form-item label="手机号"><el-input v-model="profileForm.mobile" disabled /></el-form-item>
              <el-form-item label="邮箱"><el-input v-model="profileForm.email" placeholder="请输入邮箱" /></el-form-item>
              <el-form-item label="性别">
                <el-radio-group v-model="profileForm.gender"><el-radio :value="1">男</el-radio><el-radio :value="2">女</el-radio><el-radio :value="0">保密</el-radio></el-radio-group>
              </el-form-item>
              <el-form-item label="生日"><el-date-picker v-model="profileForm.birthday" type="date" placeholder="选择日期" style="width:100%" /></el-form-item>
              <el-form-item><el-button type="primary" @click="saveProfile">保存修改</el-button></el-form-item>
            </el-form>
          </div>

          <!-- 积分 -->
          <div class="content-card" v-if="activeMenu === 'points'">
            <h3 class="section-title">我的积分</h3>
            <div class="points-overview"><div class="points-balance">{{ stats.points || 0 }} <span>积分</span></div><div class="points-desc">积分可用于兑换优惠券、抵扣现金</div></div>
            <h4 class="sub-title">积分明细</h4>
            <el-table :data="pointLogs" border size="small">
              <el-table-column prop="created_at" label="时间" width="180" />
              <el-table-column prop="type" label="类型" width="120" />
              <el-table-column prop="points" label="积分变动" width="120" />
              <el-table-column prop="remark" label="说明" />
            </el-table>
          </div>

          <!-- 余额 -->
          <div class="content-card" v-if="activeMenu === 'balance'">
            <h3 class="section-title">账户余额</h3>
            <div class="balance-overview"><div class="balance-amount">¥{{ stats.balance || '0.00' }}</div><el-button type="primary" size="large">充值</el-button></div>
            <h4 class="sub-title">余额明细</h4>
            <el-table :data="balanceLogs" border size="small">
              <el-table-column prop="created_at" label="时间" width="180" />
              <el-table-column prop="type" label="类型" width="120" />
              <el-table-column prop="amount" label="金额变动" width="120" />
              <el-table-column prop="balance_after" label="变动后余额" width="120" />
              <el-table-column prop="remark" label="说明" />
            </el-table>
          </div>

          <!-- 修改密码 -->
          <div class="content-card" v-if="activeMenu === 'password'">
            <h3 class="section-title">修改密码</h3>
            <el-form :model="passwordForm" label-width="100px" style="max-width:500px">
              <el-form-item label="原密码"><el-input v-model="passwordForm.old_password" type="password" show-password /></el-form-item>
              <el-form-item label="新密码"><el-input v-model="passwordForm.new_password" type="password" show-password /></el-form-item>
              <el-form-item label="确认密码"><el-input v-model="passwordForm.confirm_password" type="password" show-password /></el-form-item>
              <el-form-item><el-button type="primary" @click="changePassword">确认修改</el-button></el-form-item>
            </el-form>
          </div>

          <!-- 售后 -->
          <div class="content-card" v-if="activeMenu === 'after_sale'">
            <h3 class="section-title">售后管理</h3>
            <el-empty description="暂无售后记录" />
          </div>

          <!-- 订单 -->
          <div class="content-card" v-if="activeMenu === 'orders'">
            <h3 class="section-title">全部订单</h3>
            <el-button type="primary" @click="goMenu('/orders')">查看全部订单</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'
const router = useRouter()
const userStore = useUserStore()
const activeMenu = ref('profile')
const userInfo = ref({})
const stats = ref({ orders: 0, coupons: 0, points: 0, balance: '0.00' })
const pointLogs = ref([])
const balanceLogs = ref([])
const profileForm = ref({ nickname: '', mobile: '', email: '', gender: 0, birthday: '' })
const passwordForm = ref({ old_password: '', new_password: '', confirm_password: '' })
const fetchUserInfo = () => { userInfo.value = userStore.userInfo || {}; profileForm.value.nickname = userInfo.value.nickname || ''; profileForm.value.mobile = userInfo.value.mobile || '' }
const goMenu = (path) => router.push(path)
const saveProfile = () => { ElMessage.success('资料保存成功') }
const changePassword = () => { if (!passwordForm.value.old_password || !passwordForm.value.new_password) { ElMessage.warning('请填写完整'); return }; if (passwordForm.value.new_password !== passwordForm.value.confirm_password) { ElMessage.error('两次密码不一致'); return }; ElMessage.success('密码修改成功'); passwordForm.value = { old_password: '', new_password: '', confirm_password: '' } }
const handleLogout = async () => { await userStore.logout(); ElMessage.success('已退出登录'); router.push('/home') }
onMounted(fetchUserInfo)
</script>
<style scoped>
.profile-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.profile-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.profile-sidebar { width: 220px; flex-shrink: 0; }
.user-card { background: #fff; border-radius: 8px; padding: 24px; text-align: center; margin-bottom: 16px; }
.user-name { font-size: 16px; color: #333; font-weight: bold; margin-top: 12px; }
.user-level { display: inline-block; background: #fdf6ec; color: #e6a23c; padding: 2px 10px; border-radius: 10px; font-size: 12px; margin-top: 8px; }
.menu-list { background: #fff; border-radius: 8px; padding: 8px 0; }
.menu-group { padding: 8px 0; border-bottom: 1px solid #f5f5f5; }
.menu-group:last-child { border-bottom: none; }
.group-title { font-size: 12px; color: #999; padding: 8px 20px; }
.menu-item { display: flex; align-items: center; gap: 10px; padding: 12px 20px; font-size: 14px; color: #333; cursor: pointer; transition: all 0.2s; }
.menu-item:hover { background: #fafafa; color: #e6a23c; }
.menu-item.active { background: #fdf6ec; color: #e6a23c; font-weight: bold; border-right: 3px solid #e6a23c; }
.menu-item.logout { color: #f56c6c; }
.profile-content { flex: 1; min-width: 0; }
.stats-card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 16px; }
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.stat-item { display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s; }
.stat-item:hover { background: #fafafa; }
.stat-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.stat-value { font-size: 22px; color: #333; font-weight: bold; }
.stat-label { font-size: 12px; color: #999; margin-top: 2px; }
.quick-orders { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 16px; }
.section-title { font-size: 16px; color: #333; margin: 0 0 16px 0; }
.sub-title { font-size: 14px; color: #333; margin: 20px 0 12px 0; }
.order-tabs { display: flex; justify-content: space-around; }
.order-tab { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 16px; cursor: pointer; color: #666; transition: color 0.2s; }
.order-tab:hover { color: #e6a23c; }
.order-tab span { font-size: 13px; }
.content-card { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 16px; }
.points-overview, .balance-overview { display: flex; align-items: center; justify-content: space-between; padding: 24px; background: linear-gradient(135deg, #fdf6ec, #faecd8); border-radius: 8px; margin-bottom: 16px; }
.points-balance, .balance-amount { font-size: 32px; color: #e6a23c; font-weight: bold; }
.points-balance span { font-size: 14px; color: #999; font-weight: normal; }
.points-desc { font-size: 13px; color: #999; }

/* ========== 移动端适配 ========== */
@media (max-width: 768px) {
  .profile-page { padding: 10px 0; min-height: calc(100vh - 120px); }
  .container { max-width: 100%; padding: 0 12px; }
  
  /* 两栏布局改为单列 */
  .profile-wrapper { flex-direction: column; gap: 10px; }
  .profile-sidebar { width: 100%; flex-shrink: 1; }
  .profile-content { width: 100%; flex: none; }
  
  /* 用户卡片 */
  .user-card { padding: 16px; margin-bottom: 10px; border-radius: 6px; }
  .user-card .el-avatar { width: 56px !important; height: 56px !important; }
  .user-name { font-size: 15px; margin-top: 8px; }
  .user-level { font-size: 11px; padding: 2px 8px; margin-top: 6px; }
  
  /* 菜单改为横向滚动 */
  .menu-list { border-radius: 6px; padding: 4px 0; overflow-x: auto; }
  .menu-group { padding: 4px 0; border-bottom: none; display: flex; gap: 4px; padding: 4px 8px; }
  .group-title { display: none; }
  .menu-item { padding: 8px 10px; font-size: 12px; white-space: nowrap; border-radius: 4px; gap: 4px; }
  .menu-item.active { border-right: none; border-bottom: 2px solid #e6a23c; background: #fdf6ec; }
  .menu-item .el-icon { font-size: 16px; }
  .menu-item.logout { color: #f56c6c; }
  
  /* 统计卡片 */
  .stats-card { padding: 14px; margin-bottom: 10px; border-radius: 6px; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .stat-item { gap: 8px; padding: 6px; }
  .stat-icon { width: 36px; height: 36px; font-size: 18px; border-radius: 6px; }
  .stat-value { font-size: 18px; }
  .stat-label { font-size: 11px; }
  
  /* 快捷订单 */
  .quick-orders { padding: 14px; margin-bottom: 10px; border-radius: 6px; }
  .section-title { font-size: 15px; margin-bottom: 10px; }
  .order-tabs { flex-wrap: wrap; gap: 4px; justify-content: flex-start; }
  .order-tab { padding: 10px 8px; gap: 4px; flex: 1; min-width: 60px; }
  .order-tab .el-icon { font-size: 20px; }
  .order-tab span { font-size: 11px; }
  
  /* 内容卡片 */
  .content-card { padding: 14px; margin-bottom: 10px; border-radius: 6px; }
  .sub-title { font-size: 13px; margin: 14px 0 8px 0; }
  
  /* 积分/余额概览 */
  .points-overview, .balance-overview { padding: 16px; margin-bottom: 10px; border-radius: 6px; flex-direction: column; gap: 8px; text-align: center; }
  .points-balance, .balance-amount { font-size: 24px; }
  .points-balance span { font-size: 12px; }
  .points-desc { font-size: 12px; }
}

@media (max-width: 480px) {
  .container { padding: 0 8px; }
  .stats-grid { gap: 8px; }
  .stat-value { font-size: 16px; }
  .user-card { padding: 12px; }
  .menu-item { padding: 6px 8px; font-size: 11px; }
  .order-tab { min-width: 50px; padding: 8px 4px; }
  .order-tab span { font-size: 10px; }
}
</style>
