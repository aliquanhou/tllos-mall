<template>
  <div class="help-page">
    <div class="container">
      <div class="page-header">
        <h2>帮助中心</h2>
        <p>常见问题解答，快速找到您需要的帮助</p>
      </div>
      <div class="help-wrapper">
        <!-- 左侧分类导航 -->
        <aside class="help-sidebar">
          <div class="sidebar-item" v-for="cat in categories" :key="cat.id" :class="{active: activeCategory === cat.id}" @click="activeCategory = cat.id">
            <el-icon :size="18"><component :is="cat.icon" /></el-icon>
            <span>{{ cat.name }}</span>
          </div>
        </aside>
        <!-- 右侧内容 -->
        <div class="help-content">
          <!-- 搜索框 -->
          <div class="help-search">
            <el-input v-model="searchKeyword" size="large" placeholder="搜索您遇到的问题" @keyup.enter="searchQuestions">
              <template #prefix><el-icon><Search /></el-icon></template>
              <template #append><el-button type="primary" @click="searchQuestions">搜索</el-button></template>
            </el-input>
          </div>
          <!-- 热门问题 -->
          <div class="hot-questions" v-if="!searchKeyword">
            <h3>热门问题</h3>
            <div class="question-list">
              <div class="question-item" v-for="q in hotQuestions" :key="q.id" @click="toggleQuestion(q)">
                <div class="question-title">
                  <el-icon><QuestionFilled /></el-icon>
                  <span>{{ q.title }}</span>
                  <el-icon class="arrow" :class="{open: q.open}"><ArrowDown /></el-icon>
                </div>
                <div class="question-answer" v-if="q.open">
                  {{ q.answer }}
                </div>
              </div>
            </div>
          </div>
          <!-- 分类问题 -->
          <div class="category-questions" v-if="!searchKeyword">
            <h3>{{ currentCategoryName }}</h3>
            <div class="question-list">
              <div class="question-item" v-for="q in filteredQuestions" :key="q.id" @click="toggleQuestion(q)">
                <div class="question-title">
                  <el-icon><QuestionFilled /></el-icon>
                  <span>{{ q.title }}</span>
                  <el-icon class="arrow" :class="{open: q.open}"><ArrowDown /></el-icon>
                </div>
                <div class="question-answer" v-if="q.open">{{ q.answer }}</div>
              </div>
            </div>
          </div>
          <!-- 搜索结果 -->
          <div class="search-results" v-if="searchKeyword">
            <h3>搜索结果（{{ searchResults.length }}条）</h3>
            <div class="question-list" v-if="searchResults.length">
              <div class="question-item" v-for="q in searchResults" :key="q.id" @click="toggleQuestion(q)">
                <div class="question-title">
                  <el-icon><QuestionFilled /></el-icon>
                  <span>{{ q.title }}</span>
                  <el-icon class="arrow" :class="{open: q.open}"><ArrowDown /></el-icon>
                </div>
                <div class="question-answer" v-if="q.open">{{ q.answer }}</div>
              </div>
            </div>
            <div class="no-result" v-else>
              <p>未找到相关问题，请尝试其他关键词</p>
              <el-button type="primary" @click="searchKeyword = ''">返回帮助中心</el-button>
            </div>
          </div>
          <!-- 联系客服 -->
          <div class="contact-support">
            <h3>没有找到答案？</h3>
            <p>您可以联系在线客服，我们将竭诚为您服务</p>
            <el-button type="primary" size="large"><el-icon><Service /></el-icon> 联系在线客服</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
const activeCategory = ref('shopping')
const searchKeyword = ref('')
const categories = [
  { id: 'shopping', name: '购物指南', icon: 'ShoppingCart' },
  { id: 'payment', name: '支付问题', icon: 'Wallet' },
  { id: 'delivery', name: '配送物流', icon: 'Van' },
  { id: 'aftersale', name: '售后服务', icon: 'RefreshLeft' },
  { id: 'account', name: '账户管理', icon: 'User' },
  { id: 'other', name: '其他问题', icon: 'MoreFilled' },
]
const allQuestions = ref([
  { id: 1, category: 'shopping', title: '如何下单购买商品？', answer: '浏览商品后点击"加入购物车"或"立即购买"，进入结算页确认收货地址和商品信息，选择支付方式后提交订单即可。', open: false },
  { id: 2, category: 'shopping', title: '如何搜索和筛选商品？', answer: '在顶部搜索框输入关键词，或在商品列表页使用左侧分类栏和排序功能进行筛选。', open: false },
  { id: 3, category: 'payment', title: '支持哪些支付方式？', answer: '目前支持微信支付、支付宝、余额支付三种方式。后续将支持更多支付渠道。', open: false },
  { id: 4, category: 'payment', title: '支付失败怎么办？', answer: '请检查网络连接和支付账户余额，如仍无法支付可联系客服处理。订单在30分钟内未支付将自动取消。', open: false },
  { id: 5, category: 'delivery', title: '发货时间是多久？', answer: '一般商品在付款后24-48小时内发货，预售商品以商品详情页说明为准。', open: false },
  { id: 6, category: 'delivery', title: '如何查询物流信息？', answer: '在"我的订单"中点击对应订单，可查看物流公司和运单号，点击可追踪物流轨迹。', open: false },
  { id: 7, category: 'aftersale', title: '如何申请售后？', answer: '在"我的订单"中找到对应订单，点击"申请售后"，选择售后类型（退货退款/仅退款/换货），填写原因后提交。', open: false },
  { id: 8, category: 'aftersale', title: '售后审核需要多久？', answer: '售后申请提交后，商家将在1-3个工作日内审核。如超时未处理，系统将自动通过。', open: false },
  { id: 9, category: 'account', title: '如何修改收货地址？', answer: '在"个人中心-地址管理"中可以新增、编辑、删除收货地址，并设置默认地址。', open: false },
  { id: 10, category: 'account', title: '忘记密码怎么办？', answer: '在登录页点击"忘记密码"，通过绑定的手机号验证后重置密码。', open: false },
])
const hotQuestions = computed(() => allQuestions.value.slice(0, 5).map(q => ({ ...q, open: false })))
const currentCategoryName = computed(() => categories.find(c => c.id === activeCategory.value)?.name || '')
const filteredQuestions = computed(() => allQuestions.value.filter(q => q.category === activeCategory.value))
const searchResults = computed(() => {
  if (!searchKeyword.value) return []
  const kw = searchKeyword.value.toLowerCase()
  return allQuestions.value.filter(q => q.title.toLowerCase().includes(kw) || q.answer.toLowerCase().includes(kw))
})
const toggleQuestion = (q) => { q.open = !q.open }
const searchQuestions = () => {}
</script>
<style scoped>
.help-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 20px 0; }
.container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
.page-header { margin-bottom: 20px; }
.page-header h2 { font-size: 24px; color: #333; margin: 0 0 8px 0; }
.page-header p { font-size: 14px; color: #999; margin: 0; }
.help-wrapper { display: flex; gap: 20px; align-items: flex-start; }
.help-sidebar { width: 180px; flex-shrink: 0; background: #fff; border-radius: 8px; padding: 8px; position: sticky; top: 20px; }
.sidebar-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; color: #666; transition: all 0.2s; }
.sidebar-item:hover { background: #fafafa; color: #e6a23c; }
.sidebar-item.active { background: #fdf6ec; color: #e6a23c; font-weight: bold; }
.help-content { flex: 1; min-width: 0; }
.help-search { margin-bottom: 24px; }
.help-content h3 { font-size: 18px; color: #333; margin: 0 0 16px 0; }
.question-list { background: #fff; border-radius: 8px; overflow: hidden; margin-bottom: 24px; }
.question-item { border-bottom: 1px solid #f5f5f5; cursor: pointer; }
.question-item:last-child { border-bottom: none; }
.question-title { display: flex; align-items: center; gap: 10px; padding: 16px 20px; font-size: 14px; color: #333; }
.question-title .el-icon { color: #e6a23c; flex-shrink: 0; }
.question-title span { flex: 1; }
.question-title .arrow { transition: transform 0.2s; color: #999; }
.question-title .arrow.open { transform: rotate(180deg); }
.question-answer { padding: 0 20px 16px 48px; font-size: 14px; color: #666; line-height: 1.6; }
.contact-support { background: #fff; border-radius: 8px; padding: 32px; text-align: center; }
.contact-support h3 { margin-bottom: 8px; }
.contact-support p { color: #999; margin: 0 0 20px 0; }
.no-result { background: #fff; border-radius: 8px; padding: 40px; text-align: center; }
.no-result p { color: #999; margin: 0 0 16px 0; }
</style>
