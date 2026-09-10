// 内置默认装修模板配置
// 每套模板包含完整的global配置和components组件列表
// 组件类型：banner(轮播图)、search(搜索框)、category_nav(分类导航)、product_grid(商品网格)、coupon(优惠券)、seckill(限时秒杀)、brand(品牌专区)、notice(公告)、image_nav(图片导航)、text(富文本)

export const defaultTemplates = [
  {
    key: 'minimal_ecommerce',
    name: '简约电商首页',
    description: '简洁大气的电商首页风格，适合综合类商城',
    thumbnail: '',
    config: {
      global: {
        bg_color: '#f5f5f5',
        font_family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
        custom_css: '.page-container{max-width:1200px;margin:0 auto;padding:0 16px;}\n@media(max-width:768px){.page-container{padding:0 12px;}}'
      },
      components: [
        {
          id: 'comp_search',
          type: 'search',
          props: { placeholder: '搜索商品', showHotWords: true, hotWords: ['智能手表', '箱包', '跨境好物'] },
          styles: { margin: '0 0 16px 0', padding: '12px 0' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 1
        },
        {
          id: 'comp_banner1',
          type: 'banner',
          props: {
            images: [
              { src: 'https://picsum.photos/1200/400?random=1', link: '/product/list?category=1', title: '新品上市' },
              { src: 'https://picsum.photos/1200/400?random=2', link: '/product/list?category=2', title: '限时特惠' },
              { src: 'https://picsum.photos/1200/400?random=3', link: '/product/list?category=3', title: '跨境精选' }
            ],
            autoplay: true,
            interval: 4000,
            showDots: true,
            height: 400,
            mobileHeight: 180
          },
          styles: { margin: '0 0 20px 0', borderRadius: '8px', overflow: 'hidden' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 2
        },
        {
          id: 'comp_category',
          type: 'category_nav',
          props: {
            categories: [
              { name: '智能手表', icon: 'Watch', link: '/product/list?category=1' },
              { name: '箱包配饰', icon: 'Briefcase', link: '/product/list?category=2' },
              { name: '数码电子', icon: 'Iphone', link: '/product/list?category=3' },
              { name: '家居生活', icon: 'HomeFilled', link: '/product/list?category=4' },
              { name: '美妆个护', icon: 'MagicStick', link: '/product/list?category=5' },
              { name: '运动户外', icon: 'Football', link: '/product/list?category=6' },
              { name: '母婴玩具', icon: 'IceCream', link: '/product/list?category=7' },
              { name: '更多分类', icon: 'MoreFilled', link: '/category' }
            ],
            columns: 8,
            mobileColumns: 4
          },
          styles: { margin: '0 0 20px 0', padding: '16px', background: '#fff', borderRadius: '8px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 3
        },
        {
          id: 'comp_coupon',
          type: 'coupon',
          props: {
            coupons: [
              { amount: 10, condition: '满99可用', name: '新人专享券' },
              { amount: 30, condition: '满299可用', name: '品类优惠券' },
              { amount: 50, condition: '满499可用', name: '会员专享券' }
            ],
            showReceiveBtn: true
          },
          styles: { margin: '0 0 20px 0', padding: '16px', background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', borderRadius: '8px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 4
        },
        {
          id: 'comp_products1',
          type: 'product_grid',
          props: {
            title: '为你推荐',
            subTitle: '精选好物 品质保障',
            categoryId: 0,
            limit: 10,
            columns: 5,
            mobileColumns: 2,
            showPrice: true,
            showSales: true,
            sortBy: 'sales'
          },
          styles: { margin: '0 0 20px 0', padding: '16px', background: '#fff', borderRadius: '8px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 5
        },
        {
          id: 'comp_seckill',
          type: 'seckill',
          props: {
            title: '限时秒杀',
            subTitle: '每日10点开抢',
            activityId: 0,
            limit: 6,
            columns: 6,
            mobileColumns: 3,
            showCountdown: true
          },
          styles: { margin: '0 0 20px 0', padding: '16px', background: '#fff', borderRadius: '8px' },
          visible: { pc: true, tablet: false, mobile: true },
          sort: 6
        },
        {
          id: 'comp_brand',
          type: 'brand',
          props: {
            title: '品牌专区',
            brands: [
              { name: '品牌A', logo: 'https://picsum.photos/120/60?random=10', link: '/brand/1' },
              { name: '品牌B', logo: 'https://picsum.photos/120/60?random=11', link: '/brand/2' },
              { name: '品牌C', logo: 'https://picsum.photos/120/60?random=12', link: '/brand/3' },
              { name: '品牌D', logo: 'https://picsum.photos/120/60?random=13', link: '/brand/4' }
            ],
            columns: 4
          },
          styles: { margin: '0 0 20px 0', padding: '16px', background: '#fff', borderRadius: '8px' },
          visible: { pc: true, tablet: true, mobile: false },
          sort: 7
        }
      ]
    }
  },
  {
    key: 'fashion_trend',
    name: '时尚潮流首页',
    description: '年轻时尚的视觉风格，适合服饰箱包类商城',
    thumbnail: '',
    config: {
      global: {
        bg_color: '#fafafa',
        font_family: '"PingFang SC", "Microsoft YaHei", sans-serif',
        custom_css: '.section-title{font-size:20px;font-weight:600;margin-bottom:16px;}\n.product-card{transition:transform .3s;}\n.product-card:hover{transform:translateY(-4px);}'
      },
      components: [
        {
          id: 'comp_banner_fashion',
          type: 'banner',
          props: {
            images: [
              { src: 'https://picsum.photos/1200/500?random=20', link: '/product/list?category=2', title: '秋冬新品' },
              { src: 'https://picsum.photos/1200/500?random=21', link: '/product/list?category=2', title: '潮流箱包' }
            ],
            autoplay: true,
            interval: 5000,
            height: 500,
            mobileHeight: 220
          },
          styles: { margin: '0 0 24px 0' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 1
        },
        {
          id: 'comp_image_nav',
          type: 'image_nav',
          props: {
            items: [
              { image: 'https://picsum.photos/300/200?random=30', title: '新品首发', link: '/product/list?tag=new' },
              { image: 'https://picsum.photos/300/200?random=31', title: '热销榜单', link: '/product/list?tag=hot' },
              { image: 'https://picsum.photos/300/200?random=32', title: '限时折扣', link: '/product/list?tag=sale' },
              { image: 'https://picsum.photos/300/200?random=33', title: '跨境精选', link: '/product/list?tag=crossborder' }
            ],
            columns: 4,
            mobileColumns: 2
          },
          styles: { margin: '0 0 24px 0' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 2
        },
        {
          id: 'comp_products_fashion',
          type: 'product_grid',
          props: {
            title: '潮流好物',
            subTitle: 'TRENDING NOW',
            categoryId: 2,
            limit: 8,
            columns: 4,
            mobileColumns: 2,
            showPrice: true,
            showOriginalPrice: true,
            sortBy: 'new'
          },
          styles: { margin: '0 0 24px 0', padding: '20px', background: '#fff', borderRadius: '12px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 3
        },
        {
          id: 'comp_notice',
          type: 'notice',
          props: {
            title: '商城公告',
            notices: [
              '全场满199包邮，偏远地区除外',
              '新用户注册即送50元优惠券礼包',
              '欧盟认证商品，品质保障放心购'
            ],
            scroll: true,
            interval: 3000
          },
          styles: { margin: '0 0 24px 0', padding: '12px 16px', background: '#fff7e6', borderRadius: '8px', color: '#d46b08' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 4
        }
      ]
    }
  },
  {
    key: 'tech_digital',
    name: '数码科技首页',
    description: '科技感十足的深色风格，适合数码电子类商城',
    thumbnail: '',
    config: {
      global: {
        bg_color: '#0a0a0a',
        font_family: '"Helvetica Neue", Arial, sans-serif',
        custom_css: 'body{background:#0a0a0a;color:#fff;}\n.product-card{background:#1a1a1a;border-radius:12px;}\n.price{color:#00d4ff;}'
      },
      components: [
        {
          id: 'comp_banner_tech',
          type: 'banner',
          props: {
            images: [
              { src: 'https://picsum.photos/1200/450?random=40', link: '/product/list?category=3', title: '智能穿戴' },
              { src: 'https://picsum.photos/1200/450?random=41', link: '/product/list?category=3', title: '数码新品' }
            ],
            autoplay: true,
            interval: 4000,
            height: 450,
            mobileHeight: 200
          },
          styles: { margin: '0 0 20px 0', borderRadius: '12px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 1
        },
        {
          id: 'comp_category_tech',
          type: 'category_nav',
          props: {
            categories: [
              { name: '智能手表', icon: 'Watch', link: '/product/list?category=1' },
              { name: '智能手机', icon: 'Iphone', link: '/product/list?category=3' },
              { name: '耳机音频', icon: 'Headset', link: '/product/list?category=8' },
              { name: '电脑办公', icon: 'Monitor', link: '/product/list?category=9' },
              { name: '智能家居', icon: 'HomeFilled', link: '/product/list?category=10' },
              { name: '配件周边', icon: 'Connection', link: '/product/list?category=11' }
            ],
            columns: 6,
            mobileColumns: 3
          },
          styles: { margin: '0 0 20px 0', padding: '20px', background: '#1a1a1a', borderRadius: '12px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 2
        },
        {
          id: 'comp_products_tech',
          type: 'product_grid',
          props: {
            title: '科技臻选',
            subTitle: 'TECH SELECTED',
            categoryId: 3,
            limit: 8,
            columns: 4,
            mobileColumns: 2,
            showPrice: true,
            showSales: true,
            sortBy: 'sales'
          },
          styles: { margin: '0 0 20px 0', padding: '20px', background: '#1a1a1a', borderRadius: '12px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 3
        }
      ]
    }
  },
  {
    key: 'bag_accessory',
    name: '箱包配饰首页',
    description: '优雅精致的风格，适合箱包配饰类商城',
    thumbnail: '',
    config: {
      global: {
        bg_color: '#f8f5f0',
        font_family: '"Georgia", "Times New Roman", serif',
        custom_css: '.section-title{font-family:Georgia,serif;font-size:22px;letter-spacing:1px;}\n.product-card{background:#fff;border:1px solid #e8e0d5;}'
      },
      components: [
        {
          id: 'comp_banner_bag',
          type: 'banner',
          props: {
            images: [
              { src: 'https://picsum.photos/1200/420?random=50', link: '/product/list?category=2', title: '经典箱包' },
              { src: 'https://picsum.photos/1200/420?random=51', link: '/product/list?category=2', title: '新品上市' }
            ],
            autoplay: true,
            interval: 5000,
            height: 420,
            mobileHeight: 190
          },
          styles: { margin: '0 0 24px 0' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 1
        },
        {
          id: 'comp_category_bag',
          type: 'category_nav',
          props: {
            categories: [
              { name: '手提包', icon: 'Briefcase', link: '/product/list?category=21' },
              { name: '双肩包', icon: 'Backpack', link: '/product/list?category=22' },
              { name: '钱包', icon: 'Wallet', link: '/product/list?category=23' },
              { name: '行李箱', icon: 'Suitcase', link: '/product/list?category=24' },
              { name: '皮带', icon: 'Aim', link: '/product/list?category=25' },
              { name: '丝巾', icon: 'Flag', link: '/product/list?category=26' },
              { name: '眼镜', icon: 'View', link: '/product/list?category=27' },
              { name: '全部', icon: 'MoreFilled', link: '/category' }
            ],
            columns: 8,
            mobileColumns: 4
          },
          styles: { margin: '0 0 24px 0', padding: '20px', background: '#fff', borderRadius: '8px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 2
        },
        {
          id: 'comp_products_bag',
          type: 'product_grid',
          props: {
            title: '臻选箱包',
            subTitle: 'SELECTED BAGS',
            categoryId: 2,
            limit: 10,
            columns: 5,
            mobileColumns: 2,
            showPrice: true,
            showOriginalPrice: true,
            sortBy: 'new'
          },
          styles: { margin: '0 0 24px 0', padding: '20px', background: '#fff', borderRadius: '8px' },
          visible: { pc: true, tablet: true, mobile: true },
          sort: 3
        }
      ]
    }
  }
]

// 获取空配置模板
export const getEmptyConfig = () => ({
  global: {
    bg_color: '#ffffff',
    font_family: 'system-ui, sans-serif',
    custom_css: ''
  },
  components: []
})

// 组件类型定义（用于组件库面板）
export const componentTypes = [
  { type: 'banner', name: '轮播图', icon: 'Picture', description: '图片轮播展示，支持自动播放' },
  { type: 'search', name: '搜索框', icon: 'Search', description: '商品搜索入口，支持热词' },
  { type: 'category_nav', name: '分类导航', icon: 'Grid', description: '图标+文字的分类入口' },
  { type: 'product_grid', name: '商品网格', icon: 'Goods', description: '商品列表展示，支持排序' },
  { type: 'coupon', name: '优惠券', icon: 'Ticket', description: '优惠券领取区域' },
  { type: 'seckill', name: '限时秒杀', icon: 'AlarmClock', description: '限时抢购活动展示' },
  { type: 'brand', name: '品牌专区', icon: 'Shop', description: '品牌Logo展示区域' },
  { type: 'notice', name: '公告栏', icon: 'Bell', description: '滚动公告通知' },
  { type: 'image_nav', name: '图片导航', icon: 'PictureFilled', description: '图片+标题的导航入口' },
  { type: 'text', name: '富文本', icon: 'EditPen', description: '自定义HTML内容' }
]
