<template>
  <div class="page-header">
    <div class="page-header-bg">
      <div class="header-glow glow-1"></div>
      <div class="header-glow glow-2"></div>
      <div class="header-grid"></div>
      <div class="header-line line-1"></div>
      <div class="header-line line-2"></div>
    </div>
    <div class="page-header-content tllos-container">
      <div class="header-breadcrumb" v-if="breadcrumb && breadcrumb.length">
        <span
          v-for="(item, index) in breadcrumb"
          :key="index"
          class="breadcrumb-item"
        >
          <router-link v-if="item.path && index < breadcrumb.length - 1" :to="item.path" class="breadcrumb-link">
            {{ item.name }}
          </router-link>
          <span v-else class="breadcrumb-current">{{ item.name }}</span>
          <span v-if="index < breadcrumb.length - 1" class="breadcrumb-separator">/</span>
        </span>
      </div>
      <h1 class="header-title">
        <span class="title-text">{{ title }}</span>
        <span class="title-accent"></span>
      </h1>
      <p v-if="subtitle" class="header-subtitle">{{ subtitle }}</p>
    </div>
    <div class="header-bottom-border">
      <div class="border-gradient"></div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  breadcrumb: {
    type: Array,
    default: () => [],
  },
})
</script>

<style scoped>
.page-header {
  position: relative;
  width: 100%;
  min-height: 160px;
  overflow: hidden;
  background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0c1929 100%);
}

.page-header-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.header-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.4;
}

.glow-1 {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, #06b6d4 0%, transparent 70%);
  top: -100px;
  right: 10%;
  animation: glowFloat 8s ease-in-out infinite;
}

.glow-2 {
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, #f97316 0%, transparent 70%);
  bottom: -80px;
  left: 5%;
  opacity: 0.25;
  animation: glowFloat 10s ease-in-out infinite reverse;
}

@keyframes glowFloat {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(30px, -20px); }
}

.header-grid {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image:
    linear-gradient(rgba(6, 182, 212, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(6, 182, 212, 0.03) 1px, transparent 1px);
  background-size: 40px 40px;
}

.header-line {
  position: absolute;
  height: 1px;
  background: linear-gradient(90deg, transparent, #06b6d4, transparent);
  opacity: 0.5;
}

.line-1 {
  width: 60%;
  top: 30%;
  left: 0;
}

.line-2 {
  width: 40%;
  bottom: 25%;
  right: 0;
}

.page-header-content {
  position: relative;
  z-index: 2;
  padding: 36px 0 28px;
}

.header-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
  font-size: 13px;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.breadcrumb-link {
  color: rgba(6, 182, 212, 0.7);
  text-decoration: none;
  transition: color 0.3s;
}

.breadcrumb-link:hover {
  color: #06b6d4;
}

.breadcrumb-current {
  color: rgba(255, 255, 255, 0.6);
}

.breadcrumb-separator {
  color: rgba(255, 255, 255, 0.3);
}

.header-title {
  display: flex;
  align-items: center;
  gap: 16px;
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 1px;
}

.title-text {
  position: relative;
}

.title-accent {
  display: inline-block;
  width: 60px;
  height: 3px;
  background: linear-gradient(90deg, #06b6d4, #f97316);
  border-radius: 2px;
}

.header-subtitle {
  margin: 10px 0 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 0.5px;
}

.header-bottom-border {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 2px;
}

.border-gradient {
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent 0%, #06b6d4 20%, #f97316 50%, #06b6d4 80%, transparent 100%);
  opacity: 0.6;
}

@media (max-width: 768px) {
  .page-header {
    min-height: 120px;
  }

  .page-header-content {
    padding: 24px 0 20px;
  }

  .header-title {
    font-size: 22px;
  }

  .title-accent {
    width: 40px;
  }
}
</style>
