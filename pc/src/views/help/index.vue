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
            <p>客服热线：0532-85501573（周一至周日 9:00-22:00），或联系在线客服</p>
            <el-button type="primary" size="large"><el-icon><Service /></el-icon> 联系在线客服</el-button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import PageHeader from "@/components/PageHeader.vue"
import { ref, computed, onMounted } from 'vue'
import request from '@/utils/request'
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
const allQuestions = ref(defaultQuestions.map(q => ({...q})))
const defaultQuestions = [
  { id: 1, category: 'shopping', title: '如何下单购买商品？', answer: '浏览商品选择心仪的商品，加入购物车后去结算，填写收货地址并选择支付方式完成支付即可。', open: false },
  { id: 2, category: 'shopping', title: '如何修改订单信息？', answer: '订单未支付前可在订单详情页修改收货地址；订单支付后如需修改请联系客服 0532-85501573 处理。', open: false },
  { id: 3, category: 'payment', title: '支持哪些支付方式？', answer: '目前支持支付宝支付（含电脑网站支付和手机网站支付），后续将开通微信支付等更多支付方式。', open: false },
  { id: 4, category: 'payment', title: '支付成功后订单未更新怎么办？', answer: '支付成功后订单状态可能有延迟，请耐心等待1-2分钟；如仍未更新请联系客服 0532-85501573 并提供支付凭证。', open: false },
  { id: 5, category: 'delivery', title: '发货时间是多久？', answer: '一般商品在付款后24-48小时内发货，预售商品以商品详情页说明为准。发货后可在订单详情中查看物流信息。', open: false },
  { id: 6, category: 'delivery', title: '配送范围和运费？', answer: '全国大部分地区均可配送，偏远地区可能加收运费。具体运费以结算页显示为准，部分商品满额包邮。', open: false },
  { id: 7, category: 'aftersale', title: '如何申请退换货？', answer: '在订单详情页点击"申请售后"，选择退换货类型并填写原因，提交后等待商家审核。支持7天无理由退换货（特殊商品除外）。', open: false },
  { id: 8, category: 'aftersale', title: '退款多久到账？', answer: '退款审核通过后，将原路退回至您的支付账户，支付宝退款一般1-3个工作日到账，具体以支付机构处理时间为准。', open: false },
  { id: 9, category: 'account', title: '如何修改收货地址？', answer: '登录后进入"个人中心-收货地址"，可添加、编辑或删除收货地址，最多可保存10个收货地址。', open: false },
  { id: 10, category: 'account', title: '忘记密码怎么办？', answer: '在登录页点击"忘记密码"，通过注册手机号验证后重置密码。如手机号已更换请联系客服 0532-85501573 处理。', open: false },
  { id: 11, category: 'other', title: '客服联系方式？', answer: '客服热线：0532-85501573，工作时间：周一至周日 9:00-22:00。也可在网站联系在线客服。', open: false },
  { id: 12, category: 'other', title: '商城的经营主体是谁？', answer: 'TLLOS商城由惠州市大亚湾福多多信息科技中心运营，ICP备案号：鲁ICP备2025191415号-2。', open: false },
]
const loading = ref(false)

// 获取帮助文章列表
const fetchArticles = async () => {
  loading.value = true
  try {
    const res = await request({ url: '/articles', method: 'get', params: { category: 'help', limit: 50 } })
    const list = res.data?.list || res.data || []
    allQuestions.value = list.map(item => ({
      id: item.id,
      category: item.category || item.category_name || 'other',
      title: item.title || item.name || '',
      answer: item.content || item.description || item.summary || '',
      open: false
    }))
  } catch (e) {
    console.error('获取帮助文章失败:', e)
    allQuestions.value = defaultQuestions.map(q => ({...q}))
  } finally {
    loading.value = false
  }
}

onMounted(() => { fetchArticles() })
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

/* 移动端适配 - 内容页 */
@media (max-width: 768px) {
  .container { padding: 0 12px; }
  .content-wrapper { padding: 16px; }
  .content-title { font-size: 18px; }
  .content-body { font-size: 14px; line-height: 1.8; }
  .content-body img { max-width: 100%; height: auto; }
  .article-list { gap: 12px; }
  .article-item { padding: 12px; }
  .article-title { font-size: 14px; }
  .article-meta { font-size: 12px; }
}

</style>
