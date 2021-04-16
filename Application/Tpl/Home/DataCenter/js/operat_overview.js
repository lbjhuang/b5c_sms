  var language = 'cn'
  var today = new Date()
  var $year = today.getFullYear()
  var $quarter = Math.floor((today.getMonth() + 3) / 3)
  var $lastQuarter = $quarter - 1
  if ($lastQuarter === 0) {
    $lastQuarter = 4
    $year -= 1
  }

  if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
    language = 'en'
  };

  /**
   * 全局过滤器
   * 数值千分位表示，考虑小数情况
   *
   * @param number 数值(Number或者String)
   * @param {boolean} addSymbol 返回的结果是否加上%(boolean)
   * @param {boolean} dot2 返回的结果中，小数是否统一保留两位
   * @return 金额格式的字符串,如'1,234,567.45'
   * @type String
   */
  Vue.filter('formatNum', function (number, addSymbol, dot2) {
    let hasMinus = false
    if (number === '-') {
      return number
    } else {
      let numStr = dot2 ? parseFloat(number).toFixed(2) : number.toString()
      if (numStr.startsWith('-')) {
        hasMinus = true
        numStr = numStr.slice(1)
      }
      const numArr = numStr.split('.')
      let [num, dotNum] = numArr
      let result = []
      const operateNum = num.split('').reverse()
      const length = operateNum.length
      for (let i = 0; i < length; i++) {
        result.push(operateNum[i])
        if (((i + 1) % 3 === 0) && (i !== length - 1)) {
          result.push(',')
        }
      }
      if (dotNum) {
        result.reverse().push('.', ...dotNum)
      } else {
        result.reverse()
      }

      if (addSymbol) {
        result = result.join('').concat('%')
      } else {
        result = result.join('')
      }

      if (hasMinus) {
        result = '-' + result
      }

      return result
    }
  })

  var TitleIncludeTip = {
    props: {
      title: {
        type: String,
        required: true
      },
      tipContent: {
        type: String,
        default: '单击统计图中色块可查看商品数据'
      }
    },
    template: `
        <header class="chart__head">
          <section class="title-wrapper">
            <h3>{{$lang(title)}}</h3>
            <div class="tip">
              <el-popover popper-class="erp-el-popover" :visible-arrow="false" placement="right-end" trigger="hover"
                :content="$lang(tipContent)">
                <el-image class="tip__img" slot="reference" src="./Application/Tpl/Home/DataCenter/img/tip.png"></el-image>
              </el-popover>
            </div>
          </section>
          
          <slot name="operate"></slot>
        </header>
      `
  }
  var ModuleWrapper = {
    components: {
      TitleIncludeTip
    },
    props: {
      title: {
        type: String,
        required: true
      },
      qoqTableData: {
        required: true
      },
      yoyTableData: {
        required: true
      },
      qoqTableLoading: {
        type: Boolean,
        required: true
      },
      yoyTableLoading: {
        type: Boolean,
        required: true
      },
      tableColumnTitle: {
        type: String,
        required: true
      },

    },
    template: `
        <section class="module">
          <title-include-tip :title="title">
          </title-include-tip>
          <section class="module__item">
            <section style="width: 48.17%;">
              <el-table :data="qoqTableData" :height="250" v-loading="qoqTableLoading" class="erp-el-table" @sort-change="$emit('sort-table', 'Qoq')">
                <el-table-column prop="businessType" :label="$lang(tableColumnTitle)" show-overflow-tooltip>
                </el-table-column>
                <el-table-column :label="$parent.selectedQuarterLast + ' ($)'" align="right" :sort-orders="['ascending', 'descending']" prop="comparisonAmount" show-overflow-tooltip>
                  <template slot-scope="scope">
                    {{ scope.row.comparisonAmount | formatNum }}
                  </template>
                </el-table-column>
                <el-table-column :label="$parent.formatSelectedQuarter + ' ($)'" align="right" :sort-orders="['ascending', 'descending']" prop="currentAmount" show-overflow-tooltip>
                  <template slot-scope="scope">
                    {{ scope.row.currentAmount | formatNum }}
                  </template>
                </el-table-column>
                <el-table-column :label="$lang('增长率')" align="right" :sort-orders="['ascending', 'descending']" prop="increaseRatio" show-overflow-tooltip>
                  <template slot-scope="scope">
                    <span :class="$parent.textColor(scope.row.increaseRatio)">{{ scope.row.increaseRatio | formatNum(true) }}</span>
                  </template>
                </el-table-column>
              </el-table>
            </section>
            <div ref="qoqChart" style="width: 48.17%; height: 100%;"></div>
          </section>
          <section class="module__item">
            <section style="width: 48.17%;">
              <el-table :data="yoyTableData" :height="250" v-loading="yoyTableLoading"
                class="erp-el-table"
                @sort-change="$emit('sort-table', 'Yoy')">
                <el-table-column prop="businessType" :label="$lang(tableColumnTitle)" show-overflow-tooltip>
                </el-table-column>
                <el-table-column :label="$parent.selectedQuarterYoy + ' ($)'" align="right" :sort-orders="['ascending', 'descending']" prop="comparisonAmount" show-overflow-tooltip>
                  <template slot-scope="scope">
                    {{ scope.row.comparisonAmount | formatNum }}
                  </template>
                </el-table-column>
                <el-table-column :label="$parent.formatSelectedQuarter + ' ($)'" align="right" :sort-orders="['ascending', 'descending']" prop="currentAmount" show-overflow-tooltip>
                  <template slot-scope="scope">
                    {{ scope.row.currentAmount | formatNum }}
                  </template>
                </el-table-column>
                <el-table-column :label="$lang('增长率')" align="right" :sort-orders="['ascending', 'descending']" prop="increaseRatio" show-overflow-tooltip>
                  <template slot-scope="scope">
                    <span :class="$parent.textColor(scope.row.increaseRatio)">{{ scope.row.increaseRatio | formatNum(true) }}</span>
                  </template>
                </el-table-column>
              </el-table>
            </section>
            <div ref="yoyChart" style="width: 48.17%; height: 100%;"></div>
          </section>
        </section>
        `
  }
  var vm = new Vue({
    el: '#vm',
    components: {
      TitleIncludeTip,
      ModuleWrapper
    },
    filters: {},
    data: {
      apiHost: GlobalConstAndFunc.api(),
      colorTwoToEight: ['#03315B', '#0375DE', '#13C2C2', '#FFC30F', '#FA8C16', '#F14E22', '#BF2741', '#8D34A7'],
      colorNineToSixteen: ['#03315B', '#284F73', '#0375DE', '#2889E2', '#13C2C2', '#36CBCB', '#FFC30F', '#FFCB32', '#FA8C16', '#FA9D38', '#F14E22', '#F36842', '#BF2741', '#C8475D', '#8D34A7', '#9D52B4'],
      pickerOptions: {
        disabledDate(date) {
          if ($lastQuarter === 4) {
            return (date.getFullYear - 1) === year
          }
        }
      },
      year: $year.toString(),
      quarter: `Q${$lastQuarter}`,
      quarters: [{
          label: 'Q1',
          value: 1,
          disabled: false
        },
        {
          label: 'Q2',
          value: 2,
          disabled: false
        },
        {
          label: 'Q3',
          value: 3,
          disabled: false
        },
        {
          label: 'Q4',
          value: 4,
          disabled: false
        }
      ],
      profitLossChart: null,
      profitLossYoyChart: null,
      profitLossChainChart: null,
      profitLossChartData: {},
      incomePlanChart: null,
      incomePlanTableData: [],
      incomeBusinessYoyChart: null,
      incomeBusinessQoqChart: null,
      incomeBusinessData: {},

      goodsSourceBusyType: '0',
      goodsSourceBusyTypeDisabled: false,
      goodsSourceBusyTypeAll: '0',
      goodsSourceBusyTypeArr: [],
      goodsSourceChainLoading: false,
      goodsSourceChainData: {
        datas: {
          periodInfo: []
        },
        totalCount: 0
      },
      goodsSourceQoqChart: null,
      goodsSourceYoyLoading: false,
      goodsSourceYoyData: {
        datas: {
          basicInfo: []
        },
        totalCount: 0
      },
      goodsSourceYoyChart: null,
      goodsSourceData: {},

      salesAreaBusyType: '0',
      salesAreaBusyTypeDisabled: false,
      salesAreaBusyTypeAll: '0',
      salesAreaBusyTypeArr: [],
      salesAreaChainData: {
        datas: {
          periodInfo: []
        },
        totalCount: 0
      },
      salesAreaChainLoading: false,
      salesAreaQoqChart: null,
      salesAreaYoyChart: null,
      salesAreaYoyData: {
        datas: {
          basicInfo: []
        },
        totalCount: 0
      },
      salesAreaYoyLoading: false,

      b2bByClientQoqData: {},
      b2bByClientQoqTableLoading: false,
      b2bByClientQoqChart: null,
      b2bByClientYoyData: {},
      b2bByClientYoyTableLoading: false,
      b2bByClientYoyChart: null,

      b2bByCategoryQoqData: {},
      b2bByCategoryQoqTableLoading: false,
      b2bByCategoryQoqChart: null,
      b2bByCategoryYoyData: {},
      b2bByCategoryYoyTableLoading: false,
      b2bByCategoryYoyChart: null,

      b2cTppByPlatformQoqData: {},
      b2cTppByPlatformQoqTableLoading: false,
      b2cTppByPlatformQoqChart: null,
      b2cTppByPlatformYoyData: {},
      b2cTppByPlatformYoyTableLoading: false,
      b2cTppByPlatformYoyChart: null,

      b2cTppByCategoryQoqData: {},
      b2cTppByCategoryQoqTableLoading: false,
      b2cTppByCategoryQoqChart: null,
      b2cTppByCategoryYoyData: {},
      b2cTppByCategoryYoyTableLoading: false,
      b2cTppByCategoryYoyChart: null,

      b2cTppByBrandQoqData: {},
      b2cTppByBrandQoqTableLoading: false,
      b2cTppByBrandQoqChart: null,
      b2cTppByBrandYoyData: {},
      b2cTppByBrandYoyTableLoading: false,
      b2cTppByBrandYoyChart: null,

      odmGoodsChart: null,
      dialogTableVisible: false,
      goodsData: [],
      // 各个模块中，同比、环比饼图series统一设置
      echartPieSeries: [{
          type: 'pie',
          radius: 95,
          center: ['25%', '38%'],
          label: {
            show: false
          },
          encode: {
            itemName: 'dateTime',
            value: 2
          },
        },
        {
          type: 'pie',
          radius: 95,
          center: ['75%', '38%'],
          label: {
            show: false
          },
          encode: {
            itemName: 'dateTime',
            value: 1
          },
        }
      ]
    },
    computed: {
      dateTime() {
        return this.year + this.quarter
      },
      // 当前选中季度的上一个季度
      selectedQuarterLast() {
        let selectedQuarterNum = Number(this.quarter.slice(1))
        if (selectedQuarterNum > 1) {
          return `${this.year} Q${selectedQuarterNum - 1}`
        } else {
          return `${this.year - 1} Q4`
        }
      },
      // 当前选中季度的同比季度，即上一年的当前季度
      selectedQuarterYoy() {
        return `${this.year - 1} ${this.quarter}`
      },
      // 格式化当前选中的季度：2019 Q4
      formatSelectedQuarter() {
        return `${this.year} ${this.quarter}`
      },
      // 各个模块中环比饼图的title统一设置
      echartsQoqPieTitle() {
        let temp = [{
            text: this.selectedQuarterLast,
            left: '23%',
            bottom: 35,
            textStyle: {
              color: 'rgba(0, 0, 0, 0.65)',
              fontSize: 12,
              fontWeight: 400
            }
          },
          {
            text: this.formatSelectedQuarter,
            left: '73%',
            bottom: 35,
            textStyle: {
              color: 'rgba(0, 0, 0, 0.65)',
              fontSize: 12,
              fontWeight: 400
            }
          }
        ]
        return temp
      },
      // 各个模块中同比饼图的title统一设置
      echartsYoyPieTitle() {
        let temp = [{
            text: this.selectedQuarterYoy,
            left: '23%',
            bottom: 35,
            textStyle: {
              color: 'rgba(0, 0, 0, 0.65)',
              fontSize: 12,
              fontWeight: 400
            }
          },
          {
            text: this.formatSelectedQuarter,
            left: '73%',
            bottom: 35,
            textStyle: {
              color: 'rgba(0, 0, 0, 0.65)',
              fontSize: 12,
              fontWeight: 400
            }
          }
        ]
        return temp
      }
    },
    watch: {
      year(newValue) {
        this.disableQuarter()
      }
    },
    created() {
      this.disableQuarter()
    },
    mounted: function () {
      this.profitLossChart = echarts.init(this.$refs.profitLossChart)
      this.profitLossYoyChart = echarts.init(this.$refs.profitLossYoyChart)
      this.profitLossChainChart = echarts.init(this.$refs.profitLossChainChart)
      this.incomePlanChart = echarts.init(this.$refs.incomePlanChart)

      this.incomeBusinessQoqChart = echarts.init(this.$refs.incomeBusinessQoqChart)
      this.incomeBusinessQoqChart.on('click', this.pieChartClick.bind(this, 'incomeBusiness', 'Qoq'))
      this.incomeBusinessYoyChart = echarts.init(this.$refs.incomeBusinessYoyChart)
      this.incomeBusinessYoyChart.on('click', this.pieChartClick.bind(this, 'incomeBusiness', 'Yoy'))


      this.goodsSourceQoqChart = echarts.init(this.$refs.goodsSourceQoqChart)
      this.goodsSourceQoqChart.on('click', this.pieChartClick.bind(this, 'goodsSource', 'Qoq'))
      this.goodsSourceYoyChart = echarts.init(this.$refs.goodsSourceYoyChart)
      this.goodsSourceYoyChart.on('click', this.pieChartClick.bind(this, 'goodsSource', 'Yoy'))

      this.salesAreaQoqChart = echarts.init(this.$refs.salesAreaQoqChart)
      this.salesAreaQoqChart.on('click', this.pieChartClick.bind(this, 'salesArea', 'Qoq'))
      this.salesAreaYoyChart = echarts.init(this.$refs.salesAreaYoyChart)
      this.salesAreaYoyChart.on('click', this.pieChartClick.bind(this, 'salesArea', 'Yoy'))

      this.b2bByClientQoqChart = echarts.init(this.$refs.b2bByClient.$refs.qoqChart)
      this.b2bByClientQoqChart.on('click', this.pieChartClick.bind(this, 'b2bByClient', 'Qoq'))
      this.b2bByClientYoyChart = echarts.init(this.$refs.b2bByClient.$refs.yoyChart)
      this.b2bByClientYoyChart.on('click', this.pieChartClick.bind(this, 'b2bByClient', 'Yoy'))

      this.b2bByCategoryQoqChart = echarts.init(this.$refs.b2bByCategory.$refs.qoqChart)
      this.b2bByCategoryQoqChart.on('click', this.pieChartClick.bind(this, 'b2bByCategory', 'Qoq'))
      this.b2bByCategoryYoyChart = echarts.init(this.$refs.b2bByCategory.$refs.yoyChart)
      this.b2bByCategoryYoyChart.on('click', this.pieChartClick.bind(this, 'b2bByCategory', 'Yoy'))

      this.b2cTppByPlatformQoqChart = echarts.init(this.$refs.b2cTppByPlatform.$refs.qoqChart)
      this.b2cTppByPlatformQoqChart.on('click', this.pieChartClick.bind(this, 'b2cTppByPlatform', 'Qoq'))
      this.b2cTppByPlatformYoyChart = echarts.init(this.$refs.b2cTppByPlatform.$refs.yoyChart)
      this.b2cTppByPlatformYoyChart.on('click', this.pieChartClick.bind(this, 'b2cTppByPlatform', 'Yoy'))

      this.b2cTppByCategoryQoqChart = echarts.init(this.$refs.b2cTppByCategory.$refs.qoqChart)
      this.b2cTppByCategoryQoqChart.on('click', this.pieChartClick.bind(this, 'b2cTppByCategory', 'Qoq'))
      this.b2cTppByCategoryYoyChart = echarts.init(this.$refs.b2cTppByCategory.$refs.yoyChart)
      this.b2cTppByCategoryYoyChart.on('click', this.pieChartClick.bind(this, 'b2cTppByCategory', 'Yoy'))

      this.b2cTppByBrandQoqChart = echarts.init(this.$refs.b2cTppByBrand.$refs.qoqChart)
      this.b2cTppByBrandQoqChart.on('click', this.pieChartClick.bind(this, 'b2cTppByBrand', 'Qoq'))
      this.b2cTppByBrandYoyChart = echarts.init(this.$refs.b2cTppByBrand.$refs.yoyChart)
      this.b2cTppByBrandYoyChart.on('click', this.pieChartClick.bind(this, 'b2cTppByBrand', 'Yoy'))

      this.odmGoodsChart = echarts.init(this.$refs.odmGoodsChart)
      this.odmGoodsChart.on('click', this.pieChartClick.bind(this, 'odmGoods', null))

      this.getAllChartData()
    },
    methods: {
      // 根据业务类型的名字获取业务对应的id
      getBusyTypeByName(name) {
        let id = null
        switch (name) {
          case 'B2C TPP':
            id = '1003'
            break;
          case 'B2C GP':
            id = '1004'
            break;
          case 'B2B':
            id = '1001'
            break;
        }
        return id
      },
      // 文字的颜色
      textColor(ratio) {
        return {
          'red-text': ratio < 0,
          'green-text': ratio > 0
        }
      },
      // 格式化x轴标签，如2017Q1改为2017 Q1
      formatAxisLabel(value) {
        let str = value.toString()
        let year = str.slice(0, 4)
        let quarter = str.slice(-2)
        return `${year} ${quarter}`
      },

      // 格式化饼图气泡，返回数据名+币种+数据值+百分比
      formatPieTooltip(params) {
        let {
          name,
          percent,
          marker
        } = params
        let value = params.value[params.encode.value[0]]
        value = this.$options.filters['formatNum'](value)
        percent = percent.toFixed(2)
        // return `${marker}${name} \$${value} (${percent}%)`
        return `<div style="display: flex;">
                  <div>${marker}${name}</div>
                  <div style="margin-left: 8px;">\$${value}</div>
                  <div style="margin-left:8px;">(${percent}%)</div>
                </div>`
      },
      // 格式化柱状图、折线图气泡，
      formatLineTooltip(params) {
        const length = params.length
        let obj = {}
        for (let i = 0; i < length; i++) {
          obj['item' + i] = params[i]
          obj['item' + i].actualValue = params[i].value[params[i].encode.y[0]]
          obj['item' + i].formatValue = this.$options.filters['formatNum'](obj['item' + i].actualValue)
          obj['item' + i].showValue = obj['item' + i].formatValue === '-' ? '-' : ('$' + obj['item' + i].formatValue)
        }

        let name = params[0].name
        name = this.formatAxisLabel(name)
        let str = `${name}<br/>`
        for (let i = 0; i < length; i++) {
          str += `${obj['item' + i].marker} ${obj['item' + i].showValue}<br/>`
        }
        return str
      },

      // 筛选条件，日期中的季度不可以选择本季及之后的季度
      disableQuarter() {
        let curYearStr = new Date().getFullYear().toString()
        for (let item of this.quarters) {
          if (this.year === curYearStr && item.value >= $quarter) {
            item.disabled = true
          } else {
            item.disabled = false
          }
        }
      },
      getAllChartData() {
        this.goodsSourceBusyTypeAll = '0'
        this.goodsSourceBusyTypeArr = []
        this.goodsSourceBusyType = '0'
        this.salesAreaBusyTypeAll = '0'
        this.salesAreaBusyTypeArr = []
        this.salesAreaBusyType = '0'

        this.getProfitLossChartData()
        this.getIncomePlanData()
        this.getIncomeBusinessData()
        this.getGoodsSourceData()
        this.getSalesAreaData()

        this.getModuleData('b2bByClient', 'Qoq')
        this.getModuleData('b2bByClient', 'Yoy')

        this.getModuleData('b2bByCategory', 'Qoq')
        this.getModuleData('b2bByCategory', 'Yoy')

        this.getModuleData('b2cTppByPlatform', 'Qoq')
        this.getModuleData('b2cTppByPlatform', 'Yoy')

        this.getModuleData('b2cTppByCategory', 'Qoq')
        this.getModuleData('b2cTppByCategory', 'Yoy')

        this.getModuleData('b2cTppByBrand', 'Qoq')
        this.getModuleData('b2cTppByBrand', 'Yoy')

        this.getOdmGoodsData()

      },

      // 整体收入及盈亏情况图表、整体收入及盈亏对比（同比）图表、整体收入及盈亏对比（环比）图表数据获取
      getProfitLossChartData() {
        this.profitLossChart.showLoading()
        this.profitLossYoyChart.showLoading()
        this.profitLossChainChart.showLoading()

        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          dateTime: this.dateTime,
          language
        }
        this.queryPost('/OperationOverview/profitAndLossOverall', postData).then(res => {
          if (res.data.success) {
            this.profitLossChartData = res.data.datas
            this.initProfitLossChart(res.data.datas.graphInfo)
            this.initProfitLossYoyChart(res.data.datas.histogramBasicInfo)
            this.initProfitLossChainChart(res.data.datas.histogramPeriodInfo)
          } else {
            this.$message.error(res.data.msg)
          }
          this.profitLossChart.hideLoading()
          this.profitLossYoyChart.hideLoading()
          this.profitLossChainChart.hideLoading()
        }).catch(err => {
          console.log(err)
        });
      },

      // 整体收入及盈亏情况图表配置
      initProfitLossChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            trigger: 'axis',
            formatter: this.formatLineTooltip
          },
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 20,
            icon: 'rect',
          },
          grid: {
            left: 10,
            right: 10,
            top: 40,
            bottom: 54,
            containLabel: true
          },
          color: ['#0375DE', '#FFC30F'],
          dataset: {
            sourceHeader: true,
            source: data
          },
          xAxis: {
            type: 'category',
            axisLabel: {
              formatter: this.formatAxisLabel,
              showMaxLabel: true
            },
            axisTick: {
              show: false
            },
          },
          yAxis: {
            type: 'value',
            name: '$',
            axisLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            splitLine: {
              lineStyle: {
                type: 'dotted',
                color: '#F0F0F0'
              }
            }
          },
          series: [{
              type: 'line',
              seriesLayoutBy: 'row',
            },
            {
              type: 'line',
              seriesLayoutBy: 'row',
            }
          ]
        }

        this.profitLossChart.setOption(option, true)
      },

      // 整体收入及盈亏对比（同比），图表配置
      initProfitLossYoyChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            trigger: 'axis',
            formatter: this.formatLineTooltip
          },
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 20,
            icon: 'rect',
          },
          grid: {
            left: 10,
            right: 10,
            top: 40,
            bottom: 54,
            containLabel: true
          },
          color: ['#0375DE', '#FFC30F'],
          dataset: {
            sourceHeader: true,
            source: data
          },
          xAxis: {
            type: 'category',
            axisLabel: {
              formatter: this.formatAxisLabel,
              showMaxLabel: true
            },
            axisTick: {
              show: false
            },
          },
          yAxis: {
            type: 'value',
            name: '$',
            axisLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            splitLine: {
              lineStyle: {
                type: 'dotted',
                color: '#F0F0F0'
              }
            }
          },
          series: [{
              type: 'bar',
              seriesLayoutBy: 'row',
            },
            {
              type: 'bar',
              seriesLayoutBy: 'row',
            }
          ]
        }

        this.profitLossYoyChart.setOption(option, true)
      },

      // 整体收入及盈亏对比（环比），图表配置
      initProfitLossChainChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            trigger: 'axis',
            formatter: this.formatLineTooltip
          },
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 20,
            icon: 'rect',
          },
          grid: {
            left: 10,
            right: 10,
            top: 40,
            bottom: 54,
            containLabel: true
          },
          color: ['#0375DE', '#FFC30F'],
          dataset: {
            sourceHeader: true,
            source: data
          },
          xAxis: {
            type: 'category',
            axisLabel: {
              formatter: this.formatAxisLabel,
              showMaxLabel: true
            },
            axisTick: {
              show: false
            },
          },
          yAxis: {
            type: 'value',
            name: '$',
            axisLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            splitLine: {
              lineStyle: {
                type: 'dotted',
                color: '#F0F0F0'
              }
            }
          },
          series: [{
              type: 'bar',
              seriesLayoutBy: 'row',
            },
            {
              type: 'bar',
              seriesLayoutBy: 'row',
            }
          ]
        }

        this.profitLossChainChart.setOption(option, true)
      },

      // 整体收入计划完成情况数据获取
      getIncomePlanData() {
        this.incomePlanChart.showLoading()

        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          dateTime: this.dateTime,
          language
        }
        this.queryPost('/OperationOverview/profitOverall', postData).then(res => {
          if (res.data.success) {
            this.incomePlanTableData = res.data.datas.profitOverallInfo
            this.initIncomePlanChart(res.data.datas.histogramInfo)
          } else {
            this.$message.error(res.data.msg)
          }
          this.incomePlanChart.hideLoading()
        }).catch(err => {
          console.log(err)
        });
      },

      // 整体收入计划完成情况，图表配置
      initIncomePlanChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            trigger: 'axis',
            formatter: this.formatLineTooltip
          },
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 20,
            icon: 'rect',
          },
          grid: {
            left: 10,
            right: 10,
            top: 40,
            bottom: 54,
            containLabel: true
          },
          color: ['#0375DE', '#FFC30F'],
          dataset: {
            sourceHeader: true,
            source: data
          },
          xAxis: {
            type: 'category',
            axisLabel: {
              showMaxLabel: true
            },
            axisTick: {
              show: false
            },
          },
          yAxis: {
            type: 'value',
            name: '$',
            axisLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            splitLine: {
              lineStyle: {
                type: 'dotted',
                color: '#F0F0F0'
              }
            }
          },
          series: [{
              type: 'bar',
              seriesLayoutBy: 'row',
            },
            {
              type: 'bar',
              seriesLayoutBy: 'row',
            }
          ]
        }

        this.incomePlanChart.setOption(option, true)
      },

      // 整体收入业务构成数据获取
      getIncomeBusinessData() {
        this.incomeBusinessYoyChart.showLoading()
        this.incomeBusinessQoqChart.showLoading()

        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: '0',
          operateType: 'busy',
          dateTime: this.dateTime,
          language
        }
        this.queryPost('/OperationOverview/profitBusinessComposition', postData).then(res => {
          if (res.data.success) {
            this.incomeBusinessData = res.data.datas
            this.initIncomeBusinessQoqChart(res.data.datas.periodPieInfo)
            this.initIncomeBusinessYoyChart(res.data.datas.basicPieInfo)
          } else {
            this.$message.error(res.data.msg)
          }
          this.incomeBusinessYoyChart.hideLoading()
          this.incomeBusinessQoqChart.hideLoading()
        }).catch(err => {
          console.log(err)
        });
      },

      // 整体收入业务构成，环比图表配置
      initIncomeBusinessQoqChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          title: this.echartsQoqPieTitle,
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 0,
          },
          color: ['#03315B', '#0375DE', '#13C2C2'],
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this.incomeBusinessQoqChart.setOption(option, true)
      },
      // 整体收入业务构成，同比图表配置
      initIncomeBusinessYoyChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          title: this.echartsYoyPieTitle,
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 0,
          },
          color: ['#03315B', '#0375DE', '#13C2C2'],
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this.incomeBusinessYoyChart.setOption(option, true)
      },
      // 商品来源分布，'全部'按钮变化
      goodsSourceBusyTypeAllChange() {
        this.goodsSourceBusyTypeArr = []
        this.goodsSourceBusyType = '0'
        this.getGoodsSourceData()
      },
      // 商品来源分布，多选框组变化
      goodsSourceBusyTypeArrChange(arr) {
        if (arr.length === 0) {
          this.goodsSourceBusyTypeAll = '0'
          this.goodsSourceBusyType = '0'
        } else if (arr.length === 3) {
          this.goodsSourceBusyTypeAll = null
          this.goodsSourceBusyType = '0'
        } else {
          this.goodsSourceBusyTypeAll = null
          this.goodsSourceBusyType = arr.join(',')
        }
        this.getGoodsSourceData()
      },
      // 商品来源分布，同、环比数据获取，分页器重置
      getGoodsSourceData() {
        this.goodsSourceBusyTypeDisabled = true
        axios.all([this.getGoodsSourceChainData(), this.getGoodsSourceYoyData()]).then(axios.spread((chainData, yoyData) => {
          this.goodsSourceBusyTypeDisabled = false
          if (chainData.data.success) {
            this.goodsSourceChainData = chainData.data
            this.initGoodsSourceQoqChart(chainData.data.datas.periodPieInfo)
          } else {
            this.$message.error(chainData.data.msg)
          }
          this.goodsSourceQoqChart.hideLoading()
          this.goodsSourceChainLoading = false

          if (yoyData.data.success) {
            this.goodsSourceYoyData = yoyData.data
            this.initGoodsSourceYoyChart(yoyData.data.datas.basicPieInfo)
          } else {
            this.$message.error(yoyData.data.msg)
          }
          this.goodsSourceYoyChart.hideLoading()
          this.goodsSourceYoyLoading = false
        })).catch(err => {
          console.log(err)
        })
      },
      // 商品来源分布环比数据获取
      getGoodsSourceChainData(pageTurn) {
        this.goodsSourceQoqChart.showLoading()
        this.goodsSourceChainLoading = true
        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: this.goodsSourceBusyType,
          operateType: 'src_country',
          dateTime: this.dateTime,
          // page: this.goodsSourceChainPage,
          // pageSize: this.goodsSourceChainPageSize,
          language
        }
        return this.queryPost('/OperationOverview/saleBasicInformation', postData)
      },

      // 商品来源分布同比数据获取
      getGoodsSourceYoyData(pageTurn) {
        this.goodsSourceYoyChart.showLoading()
        this.goodsSourceYoyLoading = true
        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: this.goodsSourceBusyType,
          operateType: 'src_country',
          dateTime: this.dateTime,
          // page: this.goodsSourceYoyPage,
          // pageSize: this.goodsSourceYoyPageSize,
          language
        }
        // if (pageTurn) {
        //   this.queryPost('/OperationOverview/sourceBasicInformation', postData).then((yoyData) => {
        //     if (yoyData.data.success) {
        //       this.goodsSourceYoyData = yoyData.data
        //       this.initGoodsSourceYoyChart(yoyData.data.datas.basicPieInfo)
        //     } else {
        //       this.$message.error(yoyData.data.msg)
        //     }
        //     this.goodsSourceYoyChart.hideLoading()
        //     this.goodsSourceYoyLoading = false
        //   }).catch(err => {
        //     console.log(err)
        //   })
        // } else {
        //   return this.queryPost('/OperationOverview/sourceBasicInformation', postData)
        // }

        return this.queryPost('/OperationOverview/sourceBasicInformation', postData)
      },

      // 商品来源分布，环比图表配置
      initGoodsSourceQoqChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          title: this.echartsQoqPieTitle,
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            type: 'scroll',
            bottom: 0,
          },
          color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this.goodsSourceQoqChart.setOption(option, true)
      },

      // 商品来源分布，同比图表配置
      initGoodsSourceYoyChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          title: this.echartsYoyPieTitle,
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            type: 'scroll',
            bottom: 0,
          },
          color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this.goodsSourceYoyChart.setOption(option, true)
      },

      // 销售区域分布，'全部'按钮变化
      salesAreaBusyTypeAllChange() {
        this.salesAreaBusyTypeArr = []
        this.salesAreaBusyType = '0'
        this.getSalesAreaData()
      },
      // 销售区域分布，多选框组变化
      salesAreaBusyTypeArrChange(arr) {
        if (arr.length === 0) {
          this.salesAreaBusyTypeAll = '0'
          this.salesAreaBusyType = '0'
        } else if (arr.length === 3) {
          this.salesAreaBusyTypeAll = null
          this.salesAreaBusyType = '0'
        } else {
          this.salesAreaBusyTypeAll = null
          this.salesAreaBusyType = arr.join(',')
        }
        this.getSalesAreaData()
      },
      // 销售区域分布，同、环比数据获取，分页器重置
      getSalesAreaData() {
        this.salesAreaBusyTypeDisabled = true
        axios.all([this.getSalesAreaChainData(), this.getSalesAreaYoyData()]).then(axios.spread((chainData, yoyData) => {
          this.salesAreaBusyTypeDisabled = false
          if (chainData.data.success) {
            this.salesAreaChainData = chainData.data
            this.initSalesAreaQoqChart(chainData.data.datas.periodPieInfo)
          } else {
            this.$message.error(chainData.data.msg)
          }
          this.salesAreaQoqChart.hideLoading()
          this.salesAreaChainLoading = false

          if (yoyData.data.success) {
            this.salesAreaYoyData = yoyData.data
            this.initSalesAreaYoyChart(yoyData.data.datas.basicPieInfo)
          } else {
            this.$message.error(yoyData.data.msg)
          }
          this.salesAreaYoyChart.hideLoading()
          this.salesAreaYoyLoading = false
        })).catch(err => {
          console.log(err)
        })
      },
      // 销售区域分布环比数据获取
      getSalesAreaChainData(pageTurn) {
        this.salesAreaQoqChart.showLoading()
        this.salesAreaChainLoading = true
        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: this.salesAreaBusyType,
          operateType: 'dst_country',
          dateTime: this.dateTime,
          language
        }

        return this.queryPost('/OperationOverview/saleBasicInformation', postData)
      },

      // 销售区域分布同比数据获取
      getSalesAreaYoyData(pageTurn) {
        this.salesAreaYoyChart.showLoading()
        this.salesAreaYoyLoading = true
        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: this.salesAreaBusyType,
          operateType: 'dst_country',
          dateTime: this.dateTime,
          language
        }

        return this.queryPost('/OperationOverview/sourceBasicInformation', postData)
      },

      // 销售区域分布，环比图表配置
      initSalesAreaQoqChart(data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          title: this.echartsQoqPieTitle,
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 0,
          },
          color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this.salesAreaQoqChart.setOption(option, true)
      },

      // 销售区域分布，同比图表配置
      initSalesAreaYoyChart(data) {
        let option = {
          title: this.echartsYoyPieTitle,
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 0
          },
          color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this.salesAreaYoyChart.setOption(option, true)
      },

      /**模块(环比/同比)数据获取：
       * B2B 收入情况（按客户）、B2B 收入情况（按类目）、B2C TPP 收入情况（按平台）、TPP收入情况（按类目）、B2C TPP收入情况（按品牌）
       * @param {string} module 模块的名称
       * @param {string} type 模块中环比还是同比的数据：'Qoq'环比，'Yoy'环比
       * @param {boolean} rederChart 是否渲染模块中饼图
       */
      getModuleData(module, type, rederChart = true) {
        this[module + type + 'TableLoading'] = true

        if (rederChart) this[module + type + 'Chart'].showLoading()

        let api = ''
        let operateType = ''

        switch (module) {
          case 'b2bByClient':
            operateType = 'client_name'
            break;
          case 'b2bByCategory':
            operateType = 'category_name'
            break;
          case 'b2cTppByPlatform':
            operateType = 'platform_name'
            break;
          case 'b2cTppByCategory':
            operateType = 'category_name_b2c'
            break;
          case 'b2cTppByBrand':
            operateType = 'trademark_name'
            break;
          default:
            break;
        }

        api = type === 'Qoq' ? 'saleBasicInformation' : 'sourceBasicInformation'

        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: '0',
          operateType,
          dateTime: this.dateTime,
          language
        }
        this.queryPost('/OperationOverview/' + api, postData).then(res => {
          if (res.data.success) {
            this[module + type + 'Data'] = res.data.datas
            const chartData = type === 'Qoq' ? res.data.datas.periodPieInfo : res.data.datas.basicPieInfo
            if (rederChart) this.initModuleChart(module, type, chartData)
          } else {
            this.$message.error(res.data.msg)
          }
          this[module + type + 'TableLoading'] = false
          if (rederChart) this[module + type + 'Chart'].hideLoading()
        }).catch(err => {
          console.log(err)
        });
      },

      /**模块(环比/同比)饼图配置：
       * B2B 收入情况（按客户）、B2B 收入情况（按类目）、B2C TPP 收入情况（按平台）、TPP收入情况（按类目）、B2C TPP收入情况（按品牌）
       * @param {string} module 模块的名称
       * @param {string} type 'Qoq'环比，'Yoy'环比
       * @param {string} data 饼图的数据
       */
      initModuleChart(module, type, data) {
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            formatter: this.formatPieTooltip,
          },
          title: this['echarts' + type + 'PieTitle'],
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            bottom: 0,
          },
          color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
          dataset: {
            sourceHeader: true,
            source: data
          },
          series: this.echartPieSeries
        }

        this[module + type + 'Chart'].setOption(option, true)
      },

      /**模块图表点击：B2B 收入情况（按客户）、B2B 收入情况（按类目）、B2C TPP 收入情况（按平台）、TPP收入情况（按类目）、B2C TPP收入情况（按品牌）、ODM商品销售情况
       * @param {string} module 模块的名称
       * @param {string} type 模块中环比还是同比的数据：'Qoq'环比，'Yoy'环比
       * @param {object} params 具体见各图表  label formatter 回调函数的 params
       */
      pieChartClick(module, type, params) {
        let {
          name,
          encode,
          dimensionNames,
          seriesName
        } = params

        let dateTime = null
        // ODM商品销售情况 模块图表不是饼图，特殊处理
        if (module !== 'odmGoods') {
          dateTime = dimensionNames[encode.value[0]]
        }
        
        // 特殊情况:只针对以下模块：
        // B2B 收入情况（按客户）、B2B 收入情况（按类目）、B2C TPP 收入情况（按平台）、TPP收入情况（按类目）、B2C TPP收入情况（按品牌）
        // 当传’其他’名称的时候,传入除’其他’名称之外的所有名称并且以逗号隔开
        if (name === '其他' || name === 'Other') {
          const temp = ['b2bByClient', 'b2bByCategory', 'b2cTppByPlatform', 'b2cTppByCategory', 'b2cTppByBrand']
          if (temp.includes(module)) {
            const pieChartData = type === 'Qoq' ? this[module + type + 'Data'].periodInfo : this[module + type + 'Data'].basicInfo
            name = this.getName(name, pieChartData)
          }
        }

        let busyType = 'all'
        switch (module) {
          case 'incomeBusiness':
            busyType = this.getBusyTypeByName(name)
            this.getGoodsData(dateTime, busyType, {})
            break;
          case 'goodsSource':
            busyType = this.goodsSourceBusyType === '0' ? 'all' : this.goodsSourceBusyType
            this.getGoodsData(dateTime, busyType, {
              src_country: name
            })
            break;
          case 'salesArea':
            busyType = this.salesAreaBusyType === '0' ? 'all' : this.salesAreaBusyType
            this.getGoodsData(dateTime, busyType, {
              dst_country: name
            })
            break;
          case 'b2bByClient':
            this.getGoodsData(dateTime, '1001', {
              client_name: name
            })
            break;
          case 'b2bByCategory':
            this.getGoodsData(dateTime, '1001', {
              category_name: name
            })
            break;
          case 'b2cTppByPlatform':
            this.getGoodsData(dateTime, '1003', {
              platform_name: name
            })
            break;
          case 'b2cTppByCategory':
            this.getGoodsData(dateTime, '1003', {
              category_name: name
            })
            break;
          case 'b2cTppByBrand':
            this.getGoodsData(dateTime, '1003', {
              brand_name: name
            })
            break;
          case 'odmGoods':
            this.getGoodsData(name, 'all', {
              brand_name: seriesName,
              is_odm: 'Y'
            })
          break;
          default:
            break;
        }
      },

      /**获取模块(环比/同比)饼图点击时，所点击色块的name值：B2B 收入情况（按客户）、B2B 收入情况（按类目）、B2C TPP收入情况（按平台）
       * @param {object} params 具体见饼图  label formatter 回调函数的 params
       * @param {string} module 模块的名称
       * @param {string} type 模块中环比还是同比的数据：'Qoq'环比，'Yoy'环比
       */
      getName(name, data) {
        // 特殊情况:当传’其他’名称的时候,传入除’其他’名称之外的所有名称并且以逗号隔开
        // if (name === '其他' || name === 'Other') {
        let arr = []
        for (let item of data) {
          if (item.businessType !== '其他' && item.businessType !== 'Other') {
            arr.push(item.businessType)
          }
        }
        name = arr.join(',')
        // }
        return name
      },
      // 获取销售额TOP5商品数据
      getGoodsData(dateTime, busyType = 'all', {
        src_country = 'all',
        dst_country = 'all',
        client_name = 'all',
        category_name = 'all',
        platform_name = 'all',
        brand_name = 'all',
        is_odm = ''
      }) {
        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          dateTime,
          language,
          busyType,
          src_country,
          dst_country,
          client_name,
          category_name,
          platform_name,
          brand_name,
          is_odm
        }

        this.queryPost('/OperationOverview/goodsTopFive', postData).then(res => {
          if (res.data.success) {
            this.goodsData = res.data.datas
            this.dialogTableVisible = true
          } else {
            this.$message.error(res.data.msg)
          }
        }).catch(err => {
          console.log(err)
        });

      },

      // ODM商品销售情况数据获取
      getOdmGoodsData() {
        this.odmGoodsChart.showLoading()

        let postData = {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          busyType: '0',
          dateTime: this.dateTime,
          language
        }
        this.queryPost('/OperationOverview/ODMCellInformation', postData).then(res => {
          if (res.data.success) {
            this.initOdmGoodsChart(res.data.datas.dateInfo)
          } else {
            this.$message.error(res.data.msg)
          }
          this.odmGoodsChart.hideLoading()
        }).catch(err => {
          console.log(err)
        });
      },

      // ODM商品销售情况，图表配置
      initOdmGoodsChart(data) {
        let brandNum = (data.length - 2) / 2
        let series = new Array(brandNum).fill({
          type: 'bar',
          stack: 'sum',
          seriesLayoutBy: 'row',
        })
        let option = {
          tooltip: {
            ...GlobalConstAndFunc.Echarts.tooltipStyle,
            trigger: 'axis',
            formatter: (params) => {
              if (params.length < 1) return
              let barObj = {},
                strObj = {}
              for (let i = 0; i < params.length; i++) {
                barObj['bar' + i] = params[i]
                barObj['bar' + i].actualValue = params[i].value[params[i].encode.y[0]]
                barObj['bar' + i].formatValue = this.$options.filters['formatNum'](barObj['bar' + i].actualValue)
                barObj['bar' + i].showValue = barObj['bar' + i].formatValue === '-' ? '-' : ('$' + barObj['bar' + i].formatValue)

                barObj['bar' + i].percent = params[i].value[params[i].encode.y[0] + brandNum]
                barObj['bar' + i].showPercent = barObj['bar' + i].percent === '-' ? `(-)` : `(${barObj['bar' + i].percent}%)`
              }

              let name = language === 'cn' ? '收入' : 'Revenue'
              let total = this.$options.filters['formatNum'](barObj.bar0.value[data.length - 1])
              let showTotal = total === '-' ? `-` : `$ ${total}`
              let str = `${name}: ${showTotal}<br/>`

              for (let i = 0; i < params.length; i++) {
                let bar = barObj['bar' + i]
                if (bar.showValue !== '-') {
                  str += `${bar.marker}${bar.seriesName} ${bar.showValue}${bar.showPercent}<br/>`
                }
              }

              return str
            },
          },
          legend: {
            ...GlobalConstAndFunc.Echarts.legendStyle,
            icon: 'rect',
            bottom: 20,
          },
          grid: {
            left: 10,
            right: 10,
            top: 40,
            bottom: 54,
            containLabel: true
          },
          color: this.colorTwoToEight,
          dataset: {
            sourceHeader: true,
            source: data
          },
          xAxis: {
            type: 'category',
            axisLabel: {
              showMaxLabel: true
            },
            axisTick: {
              show: false
            },
          },
          yAxis: {
            type: 'value',
            name: '$',
            axisLine: {
              show: false
            },
            axisTick: {
              show: false
            },
            splitLine: {
              lineStyle: {
                type: 'dotted',
                color: '#F0F0F0'
              }
            }
          },
          series
        }

        this.odmGoodsChart.setOption(option, true)
      },


      queryPost: function (url, param) {
        var headers = {
          headers: {
            'erp-req': true,
            'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
          }
        }
        // 初始化时间展示
        return axios.post(this.apiHost + url, Qs.stringify(param), headers);

      },
    }
  })