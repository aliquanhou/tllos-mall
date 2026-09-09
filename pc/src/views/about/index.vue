<template>
  <div class="about-page">
    <div class="container">
      <div class="about-hero">
        <h1>关于TLLOS商城</h1>
        <p>致力于为用户提供优质商品和卓越购物体验</p>
      </div>
      <div class="about-content">
        <div class="section">
          <h2>公司简介</h2>
          <p>TLLOS商城是一家综合性电商平台，致力于为广大消费者提供品质优良、价格实惠的商品和便捷高效的购物服务。平台涵盖服装、数码、美妆、家居、食品等多个品类，满足用户一站式购物需求。</p>
          <p>我们秉承"用户至上、品质第一"的经营理念，严格把控商品质量，优化购物流程，提升服务水平，努力打造值得信赖的电商品牌。</p>
        </div>
        <div class="section">
          <h2>发展历程</h2>
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-year">2024</div>
              <div class="timeline-content">
                <h3>平台创立</h3>
                <p>TLLOS商城正式成立，开始搭建电商平台基础设施</p>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-year">2025</div>
              <div class="timeline-content">
                <h3>业务扩展</h3>
                <p>平台商品品类扩展至全品类，商家入驻数量突破1000家</p>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-year">2026</div>
              <div class="timeline-content">
                <h3>全新升级</h3>
                <p>平台全面升级，推出PC端响应式商城，优化用户购物体验</p>
              </div>
            </div>
          </div>
        </div>
        <div class="section">
          <h2>核心优势</h2>
          <div class="advantages">
            <div class="advantage-item">
              <el-icon :size="40" color="#e6a23c"><Medal /></el-icon>
              <h3>品质保障</h3>
              <p>严格商家入驻审核，正品保障，假一赔十</p>
            </div>
            <div class="advantage-item">
              <el-icon :size="40" color="#409eff"><Van /></el-icon>
              <h3>极速配送</h3>
              <p>与多家物流公司合作，24-48小时内发货</p>
            </div>
            <div class="advantage-item">
              <el-icon :size="40" color="#67c23a"><Service /></el-icon>
              <h3>贴心服务</h3>
              <p>7天无理由退换，专业客服团队在线服务</p>
            </div>
            <div class="advantage-item">
              <el-icon :size="40" color="#f56c6c"><Wallet /></el-icon>
              <h3>价格实惠</h3>
              <p>厂家直供，减少中间环节，让利用户</p>
            </div>
          </div>
        </div>
        <div class="section">
          <h2>联系我们</h2>
          <div class="contact-info">
            <div class="contact-item">
              <el-icon><Phone /></el-icon>
              <div><h4>客服热线</h4><p>400-888-8888</p></div>
            </div>
            <div class="contact-item">
              <el-icon><Message /></el-icon>
              <div><h4>电子邮箱</h4><p>service@tllos.com</p></div>
            </div>
            <div class="contact-item">
              <el-icon><Location /></el-icon>
              <div><h4>公司地址</h4><p>广东省惠州市大亚湾</p></div>
            </div>
            <div class="contact-item">
              <el-icon><Clock /></el-icon>
              <div><h4>工作时间</h4><p>周一至周日 9:00-22:00</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
</script>
<style scoped>
.about-page { background: #f5f5f5; min-height: calc(100vh - 200px); padding: 0 0 40px 0; }
.container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }
.about-hero { background: linear-gradient(135deg, #1a1a2e, #16213e); color: #fff; padding: 60px 40px; text-align: center; border-radius: 0 0 16px 16px; margin-bottom: 32px; }
.about-hero h1 { font-size: 36px; margin: 0 0 12px 0; }
.about-hero p { font-size: 16px; opacity: 0.8; margin: 0; }
.about-content { }
.section { background: #fff; border-radius: 12px; padding: 32px; margin-bottom: 24px; }
.section h2 { font-size: 22px; color: #333; margin: 0 0 20px 0; padding-bottom: 12px; border-bottom: 2px solid #f5f5f5; }
.section p { font-size: 15px; color: #666; line-height: 1.8; margin: 0 0 12px 0; }
.timeline { position: relative; padding-left: 40px; }
.timeline::before { content: ''; position: absolute; left: 12px; top: 0; bottom: 0; width: 2px; background: #f0f0f0; }
.timeline-item { position: relative; margin-bottom: 28px; }
.timeline-item:last-child { margin-bottom: 0; }
.timeline-year { position: absolute; left: -40px; top: 0; width: 26px; height: 26px; background: #e6a23c; color: #fff; border-radius: 50%; font-size: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.timeline-content h3 { font-size: 16px; color: #333; margin: 0 0 6px 0; }
.timeline-content p { font-size: 14px; color: #666; margin: 0; }
.advantages { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.advantage-item { text-align: center; padding: 20px; }
.advantage-item h3 { font-size: 16px; color: #333; margin: 12px 0 8px 0; }
.advantage-item p { font-size: 13px; color: #999; margin: 0; line-height: 1.6; }
.contact-info { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
.contact-item { display: flex; gap: 16px; align-items: flex-start; }
.contact-item .el-icon { font-size: 28px; color: #e6a23c; margin-top: 4px; }
.contact-item h4 { font-size: 15px; color: #333; margin: 0 0 4px 0; }
.contact-item p { font-size: 14px; color: #666; margin: 0; }
</style>
