// 根据UI提供设计稿，将erp的数据看板菜单下的页面中常用的包裹图表的 模块 抽象成组件
// 该组件样式位于./Application/Tpl/Home/DataCenter/css/_base.scss

// 简介：
// 一般分为头部信息块、中间主要内容块和底部Echarts图表块
// 一、头部信息块：分为左右两块
// 1. -左块默认上部展示标题title，下部默认展示数据的日期范围dateRange和日期范围的解释dateRangeExplain
//    -左块提供具名插槽header-left完全自定义，以及具名插槽header-left-supplement在日期范围的解释后补充一些信息；
//    -右块一般是一些操作功能，通过具名插槽header-right插入
// 二、中间主要内容块：提供默认插槽，以方便加入其它内容
// 三、底部Echarts图表块：
//     Echarts图表块就是div,是用来供给Echarts.js绘制图表的DOM,提供了ref = chart 供外界访问，可传入div的宽chartWidth和高chartHeight

var ChartWrapper = {
  name: "ChartWrapper",
  props: {
    // 模块的标题
    title: String,
    titleTip: String,
    // 模块中展示的数据的日期范围
    dateRange: String,
    // 模块中展示的数据的日期范围的别名
    dateRangeExplain: String,
    // Echarts实例容器的宽
    chartWidth: {
      type: String,
      default: '100%'
    },
    // Echarts实例容器的高
    chartHeight: {
      type: String
    },
    // Echarts实例的数据
    chartData: {
      default: function () {
        return []
      }
    },
    // Echarts实例是否展示
    showChart: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      // Echarts实例容器的样式
      chartStyle: {
        width: this.chartWidth,
        height: this.chartHeight
      }
    }
  },
  template: 
  `<section class="erp-chart">
      <header class="erp-chart__header">
          <section>
              <slot name="header-left">
                <div class="erp-chart__title">
                  {{title}}
                  <el-tooltip v-if="titleTip" effect="light" :content="titleTip" placement="right-end"
                    :visible-arrow="false" popper-class="erp-el-tooltip">
                    <i class="el-icon-question"></i>
                  </el-tooltip>
                </div>
                <div class="erp-chart__date" v-show="dateRange">
                  {{dateRange}}
                  <span v-show="dateRangeExplain"> | </span>
                  {{ dateRangeExplain }}
                  <slot name="header-left-supplement"></slot>
                </div>
              </slot>
          </section>

          <section>
              <slot name="header-right"></slot>
          </section>
      </header>

      <main class="erp-chart__main">
        <slot></slot>
      </main>

      <section v-show="showChart">
        <div v-show="chartData" ref="chart" :style="chartStyle"></div>
        <div v-show="!chartData" class="erp-chart__no-data" :style="chartStyle">
            <img src="./Application/Tpl/Home/DataCenter/img/no-data-tip.png">
            <div>{{$lang('暂无数据')}}</div>
        </div>
      </section>
      <footer>
        <slot name="footer"></slot>
      </footer>
  </section>
  `
}