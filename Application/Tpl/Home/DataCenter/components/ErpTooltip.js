// /* 根据UI提供设计稿，将一些常用的 模块 抽象成组件 */
// /* 主要应用于erp中的数据看板模块下的页面 */

// 对ElementUI中的“Tooltip文字提示”组件，进一步封装

var ErpTooltip = {
  name: "ErpTooltip",
  props: {
    content: {
      type: String,
      required: true
    },
    maxLength: {
      type: Number
    }
  },
  data() {
    return {
      
    }
  },
  computed: {
    disabled() {
      return this.maxLength ? this.content.length <= this.maxLength : false
    }
  },
  template:
    `<el-tooltip 
      effect="light"
      :content=content 
      placement="right-end" 
      :visible-arrow="false"
      :disabled="disabled"
      popper-class="erp-el-tooltip">
      <slot>
        <i class="el-icon-question"></i>
      </slot>
     </el-tooltip>
    `
}