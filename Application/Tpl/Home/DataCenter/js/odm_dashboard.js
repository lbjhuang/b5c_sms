var vm = new Vue({
  el: '#vm',
  components: {
    'chart-wrapper': ChartWrapper,
    'erp-tooltip': ErpTooltip
  },
  filters: {
    /**
      * 数值千分位表示，考虑小数情况,小数为0不显示
      *
      * @param number 数值(Number或者String)
      * @param {boolean} addSymbol 返回的结果是否加上%(boolean)
      * @param {boolean} dot2 返回的结果中，小数是否统一保留两位
      * @return 金额格式的字符串,如'1,234,567.45'
      */
    formatNum(number, addSymbol, dot2) {
      let hasMinus = false
      if (number === '-' || number == 0) {
        return number
      } else if (number != null) {
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
          let i = dotNum.length - 1
          while (dotNum[i] === '0') {
            i--
          }
          i >= 0 ? result.reverse().push('.', ...dotNum.slice(0, i + 1)) : result.reverse()
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
    },
    ratioZero(value) {
      if (value === '0%' || value === '0.00%') {
        return '-'
      } else {
        return value
      }
    },
    /**
    * 将数值四舍五入(保留2位小数)后格式化成金额形式
    *
    * @param num 数值(Number或者String)
    * @return 金额格式的字符串,如'1,234,567.45'
    * @type String
    */
    formatCurrency(num) {
      num = num.toString().replace(/\$|\,/g, '');
      if (isNaN(num))
        num = "0";
      sign = (num == (num = Math.abs(num)));
      num = Math.floor(num * 100 + 0.50000000001);
      cents = num % 100;
      num = Math.floor(num / 100).toString();
      if (cents < 10)
        cents = "0" + cents;
      for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++)
        num = num.substring(0, num.length - (4 * i + 3)) + ',' +
          num.substring(num.length - (4 * i + 3));
      return (((sign) ? '' : '-') + num + '.' + cents);
    },


  },
  data: {
    apiHost: GlobalConstAndFunc.api(),
    language: 'cn',
    tableSortOrders: ['ascending', 'descending'],
    dateType: '30',
    timeOptions: [
      {
        value: '30',
        label: '近30天'
      },
      {
        value: '60',
        label: '近60天'
      },
      {
        value: '90',
        label: '近90天'
      },
      {
        value: '180',
        label: '近180天'
      },
    ],
    rangeDate: [],
    pickerOptions: {
      disabledDate: (time) => {
        var dayTime = 24 * 3600 * 1000
        var minTime = new Date(2000, 0, 1).getTime()
        var maxTime = Date.now() - dayTime
        return time.getTime() < minTime || time.getTime() > maxTime
      }
    },
    saleTeamData: [],
    saleTeam: ['-1'],
    saleSubTeamData: [],
    saleSubTeam: ['-1'],
    saleAreaData: [],
    saleArea: ['-1'],
    saleCountryData: [],
    saleCountry: ['-1'],
    saleTrendData: [],
    saleTrendLoading: false,
    saleTrendChart: null,
    saleTrendType: '2',
    saleTrendDateLabel: 'W',
    dateLabelOptions: [
      {
        label: '按天',
        value: 'D'
      },
      {
        label: '按周',
        value: 'W'
      },
      {
        label: '按月',
        value: 'M'
      },
      {
        label: '按季',
        value: 'Q'
      },
    ],
    totalData: [],
    totalLoading: false,
    distributeTypeOptions: [
      {
        label: '按销售额',
        value: '2'
      },
      {
        label: '按销量',
        value: '1'
      },
    ],
    AREADistributeData: [],
    AREADistributeLoading: false,
    AREADistributeChart: null,
    AREADistributeType: '2',
    COUNTRYDistributeData: [],
    COUNTRYDistributeLoading: false,
    COUNTRYDistributeChart: null,
    COUNTRYDistributeType: '2',
    BUSY_TYPEDistributeData: [],
    BUSY_TYPEDistributeLoading: false,
    BUSY_TYPEDistributeChart: null,
    BUSY_TYPEDistributeType: '2',
    PLATFORMDistributeData: [],
    PLATFORMDistributeLoading: false,
    PLATFORMDistributeChart: null,
    PLATFORMDistributeType: '2',
    CATEGORYDistributeData: [],
    CATEGORYDistributeLoading: false,
    CATEGORYDistributeChart: null,
    CATEGORYDistributeType: '2',
    BRANDDistributeData: [],
    BRANDDistributeLoading: false,
    BRANDDistributeChart: null,
    BRANDDistributeType: '2',
    SALE_TEAMDistributeData: [],
    SALE_TEAMDistributeLoading: false,
    SALE_TEAMDistributeChart: null,
    SALE_TEAMDistributeType: '2',
    saleRankData: [],
    saleRankLoading: false,
    saleRankChart: null,
    saleRankType: '2',
    goodsListLoading: false,
    goodListPage: 1,
    goodListData: {},
    goodListSortRule: 'desc',
    goodListSortWord: 'total_sales_usd',
    goodsDetailDialogVisible: false,
    goodsDetailsData: {},
    goodsDetailsLoading: false,
    goodsSaleTrendData: [],
    goodsSaleTrendLoading: false,
    goodsSaleTrendChart: null,
    goodsSaleTrendType: '2',
    COUNTRYgoodsDistributeData: [],
    COUNTRYgoodsDistributeLoading: false,
    COUNTRYgoodsDistributeChart: null,
    COUNTRYgoodsDistributeType: '2',
    PLATFORMgoodsDistributeData: [],
    PLATFORMgoodsDistributeLoading: false,
    PLATFORMgoodsDistributeChart: null,
    PLATFORMgoodsDistributeType: '2',
  },
  computed: {
  },
  watch: {
  },
  created() {
    if (getCookie('think_language') !== "zh-cn") {
      ELEMENT.locale(ELEMENT.lang.en)
      this.language = 'us'
    };
    this.getAllChartData = _.debounce(this.getAllChartDataTurly, 700)

    var arr = ['saleTeam', 'saleSubTeam', 'saleArea', 'saleCountry']
    arr.forEach(item => {
      this.multiSelectDataGet(item)
    })

    this.getAllChartData()
  },
  mounted() { },
  methods: {
    // axios请求封装
    queryPost(url, param, cb) {
      var common = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';'
      }
      var config = {
        headers: {
          ...common,
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        }
      }

      var postData = {
        ...common,
        dateType: this.dateType,
        startDate: this.rangeDate[0],
        endDate: this.rangeDate[1],
        teamId: this.saleTeam.join(),
        subTeamId: this.saleSubTeam.join(),
        areaId: this.saleArea.join(),
        countryId: this.saleCountry.join(),
        language: this.language,
        ...param
      }
      // debugger
      axios.post(this.apiHost + 'gpODMDashboard/' + url, Qs.stringify(postData), config).then(res => {
        if (res.data.success) {
          cb(res)
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      })
    },
    renderTableHeader(h, { column, $index }, isLastHeader, hasTooltip) {
      return GlobalConstAndFunc.Element.renderTableHeader(h, { column, $index }, isLastHeader, hasTooltip)
    },

    // 获取页面所有图表数据
    getAllChartDataTurly() {
      this.totalDataGet()

      this.saleTrendType = '2'
      this.saleTrendDateLabel = 'D'
      this.saleTrendDataGet()

      var arr = ['AREA', 'COUNTRY', 'PLATFORM', 'CATEGORY', 'BRAND', 'SALE_TEAM', 'BUSY_TYPE']
      arr.forEach(item => {
        this[item + 'DistributeType'] = '2'
        this.distributeDataGet(item)
      })

      this.saleRankType = '2'
      this.saleRankDataGet()

      this.goodsListSortRule = 'desc'
      this.goodsListSortWord = 'total_sales_usd'
      this.goodsListPage = 1
      this.$refs.goodsList.sort('total_sales_usd', 'descending')
    },

    // 时间快捷筛选项变动
    dateTypeChange(value) {
      this.rangeDate = []
      this.getAllChartData()
    },
    // 时间范围选择器变动
    rangeDateChange(value) {
      this.dateType = ''
      this.getAllChartData()
    },
    // 重置筛选条件
    reset() {
      this.dateType = '30'
      this.rangeDate = []
      this.saleTeam = ['-1']
      this.saleSubTeam = ['-1']
      this.saleArea = ['-1']
      this.saleCountry = ['-1']
      this.getAllChartData()
    },

    // 销售团队、销售小团队、销售区域、销售国家（地区）数据获取
    multiSelectDataGet(url) {
      var postData = {}
      this.queryPost(url, postData, (res) => {
        for (const item of res.data.datas) {
          item.disabled = item.value === '-1' ? true : false
        }
        this[url + 'Data'] = res.data.datas
      })
    },

    //  销售团队、销售小团队、销售区域、销售国家（地区）中“全部”选项与其他选项互斥
    multiSelectChange(val, name) {
      var len = val.length
      if (len > 1) {
        if (val[0] === '-1') {
          this[name].shift()
          for (const item of this[name + 'Data']) {
            if (item.value === '-1') {
              item.disabled = false
            }
          }
        } else if (val[len - 1] === '-1') {
          this[name] = [val[len - 1]]
          for (const item of this[name + 'Data']) {
            if (item.value === '-1') {
              item.disabled = true
            }
          }
        }
      } else if (len === 0) {
        this[name] = ['-1']
        for (const item of this[name + 'Data']) {
          if (item.value === '-1') {
            item.disabled = true
          }
        }
      }
      this.getAllChartData()
    },

    // 页面指标卡数据获取
    totalDataGet() {
      this.totalLoading = true
      var postData = {}
      this.queryPost('saleIndex', postData, (res) => {
        this.totalData = res.data.datas || {}
        this.totalLoading = false
      })
    },

    // 销售趋势数据获取
    saleTrendDataGet() {
      this.saleTrendLoading = true
      var postData = {
        dateLabel: this.saleTrendDateLabel,
        type: this.saleTrendType
      }
      this.queryPost('saleTrend', postData, (res) => {
        this.saleTrendData = res.data.datas
        res.data.datas && this.saleTrendChartInit(res.data.datas)
        this.saleTrendLoading = false
      })
    },

    // 销售趋势图表配置
    saleTrendChartInit(data) {
      var seriesName = '', currency = '', symbol = ''
      switch (this.saleTrendType) {
        case '1':
          seriesName = this.$lang('销量')
          break;
        case '2':
          seriesName = this.$lang('销售额')
          currency = '$'
          break;
        case '3':
          seriesName = this.$lang('毛利')
          currency = '$'
          break;
        case '4':
          seriesName = this.$lang('毛利率')
          symbol = '%'
          break;
        default:
          break;
      }
      !this.saleTrendChart && (this.saleTrendChart = echarts.init(this.$refs.saleTrend.$refs.chart))
      var option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          formatter: (params) => {
            var { name, marker, seriesName } = params[0]
            var val = params[0].value[params[0].encode.y[0]]
            var formatVal = this.$options.filters['formatNum'](val)
            return `${name}<br/>${marker} ${seriesName} ${currency}${formatVal}${symbol}`
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          bottom: 20
        },
        color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
        dataset: {
          sourceHeader: false,
          dimensions: ['date', seriesName],
          source: data
        },
        xAxis: GlobalConstAndFunc.Echarts.xAxisStyle,
        yAxis: GlobalConstAndFunc.Echarts.yAxisStyle,
        series: [{
          type: 'line',
          seriesLayoutBy: 'row'
        }]
      }

      this.saleTrendChart.setOption(option, true)
    },

    // 分布饼图数据获取
    distributeDataGet(name) {
      this[name + 'DistributeLoadingLoading'] = true
      var postData = {
        type: this[name + 'DistributeType'],
        queryType: name
      }
      this.queryPost('commonDistribute', postData, (res) => {
        this[name + 'DistributeData'] = res.data.datas
        res.data.datas && this.distributeChartInit(name, res.data.datas)
        this[name + 'DistributeLoading'] = false
      })
    },

    // 分布饼图图表配置
    distributeChartInit(name, data) {
      !this[name + 'DistributeChart'] && (this[name + 'DistributeChart'] = echarts.init(this.$refs[name + 'Distribute'].$refs.chart))
      var currency = this[name + 'DistributeType'] === '2' ? '$' : ''
      var option = {
        color: GlobalConstAndFunc.Echarts.colorList(data.length),
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: (params) => {
            var { marker, name, value, percent } = params
            var formatVal = this.$options.filters['formatNum'](value)
            return `${marker} ${name} ${currency}${formatVal} ${percent}%`
          }
        },
        series: [
          {
            type: 'pie',
            radius: 90,
            minAngle: 5,
            // center: ['50%', '60%'],
            label: {
              formatter: (params) => {
                let showVal = this.$options.filters.formatNum(params.value, false, true)
                return `${params.name}{a||}${currency}${showVal}{a||}${params.percent}%
                  `
              },
              position: 'outer',
              alignTo: 'labelLine',
              rich: {
                a: {
                  color: '#E5E5E5',
                  padding: [0, 4]
                }
              }

            },
            data: data
          }
        ]
      }

      this[name + 'DistributeChart'].setOption(option, true)
    },

    // 商品销售Top10数据获取
    saleRankDataGet() {
      this.saleRankLoading = true
      var postData = {
        type: this.saleRankType
      }
      this.queryPost('goodsSaleRank', postData, (res) => {
        this.saleRankData = res.data.datas
        res.data.datas && this.saleRankChartInit(res.data.datas)
        this.saleRankLoading = false
      })
    },

    // 商品销售Top10图表配置
    saleRankChartInit(data) {
      !this.saleRankChart && (this.saleRankChart = echarts.init(this.$refs.saleRank.$refs.chart))
      var richSet = {}
      var currency = this.saleRankType === '2' ? '$' : ''
      data.forEach((item, index) => {
        richSet[index] = {
          height: 60,
          align: 'center',
          backgroundColor: {
            image: item.item_img,
          },
        }
      })
      var option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          formatter: (params) => {
            var { marker, seriesName, value: { value_info, sku_id, item_name } } = params[0]
            var formatVal = this.$options.filters['formatNum'](value_info)
            return `${item_name}: ${currency}${formatVal}<br />SKU ID: ${sku_id}`
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          bottom: 20
        },
        color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
        dataset: {
          dimensions: ['item_img', 'item_name', 'rn', 'sku_id', 'value_info'],
          sourceHeader: false,
          source: data
        },
        xAxis: {
          ...GlobalConstAndFunc.Echarts.xAxisStyle,
          axisLabel: {
            interval: 0,
            color: 'rgba(0, 0, 0, 0.65)',
            formatter: (value, index) => {
              var valueShow = data[index].item_name
              if (valueShow.length > 10) {
                valueShow = valueShow.substring(0, 10) + '...'
              }
              return `{${index}|}\n\n${valueShow}`
            },
            rich: richSet,
          },
        },
        yAxis: GlobalConstAndFunc.Echarts.yAxisStyle,
        series: [{
          type: 'bar',
          encode: {
            x: 'sku_id',
            y: 'value_info'
          }
        }]
      }

      this.saleRankChart.setOption(option, true)
    },

    // 商品列表,数据获取
    goodListDataGet() {
      this.goodListLoading = true
      var postData = {
        skuId: '-1',
        sortWord: this.goodListSortWord,
        sortRule: this.goodListSortRule,
        page: this.goodListPage,
        pageSize: 10
      }
      this.queryPost('goodsTable', postData, (res) => {
        this.goodListData = res.data
        this.goodListLoading = false
      })
    },
    // 商品列表,表格翻页
    keywordSearchDistributeTablePageChange(value) {
      this.keywordSearchDistributeTablePage = value
      if (this.keywordCompareShow) {
        this.getKeywordSearchDistributeData();
      } else {
        this.getKeywordTableCompariso();
      }
    },
    // 商品列表,表格数据排序
    goodListSort({
      column,
      prop,
      order
    }) {
      this.goodListSortWord = prop
      this.goodListSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.goodListDataGet()
    },

    // 商品详情弹窗展示
    goodsDetailDialogOpen(data) {
      this.goodsDetailsData = data

      this.goodsDetailsDataGet()

      this.goodsSaleTrendType = '2'
      this.goodsSaleTrendDataGet()

      var arr = ['COUNTRY', 'PLATFORM']
      arr.forEach(item => {
        this[item + 'goodsDistributeType'] = '2'
        this.goodsDistributeDataGet(item)
      })

      this.goodsDetailDialogVisible = true
    },
    // 商品详情数据获取
    goodsDetailsDataGet() {
      this.goodsDetailsLoading = true
      var postData = {
        skuId: this.goodsDetailsData.sku_id
      }
      this.queryPost('saleIndexBySku', postData, (res) => {
        this.goodsDetailsData = { ...this.goodsDetailsData, ...res.data.datas }
        this.goodsDetailsLoading = false
      })
    },

    // 商品详情销售趋势数据获取
    goodsSaleTrendDataGet() {
      this.goodsSaleTrendLoading = true
      var postData = {
        skuId: this.goodsDetailsData.sku_id,
        type: this.goodsSaleTrendType
      }
      this.queryPost('saleTrendBySku', postData, (res) => {
        this.goodsSaleTrendData = res.data.datas
        res.data.datas && this.goodsSaleTrendChartInit(res.data.datas)
        this.goodsSaleTrendLoading = false
      })
    },

    // 商品详情销售趋势图表配置
    goodsSaleTrendChartInit(data) {
      !this.goodsSaleTrendChart && (this.goodsSaleTrendChart = echarts.init(this.$refs.goodsSaleTrend.$refs.chart))
      var seriesName = '', currency = ''
      switch (this.goodsSaleTrendType) {
        case '1':
          seriesName = this.$lang('销量')
          break;
        case '2':
          seriesName = this.$lang('销售额')
          currency = '$'
          break;
        case '3':
          seriesName = this.$lang('毛利')
          currency = '$'
          break;
        default:
          break;
      }
      var option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          confine: true,
          formatter: (params) => {
            var { name, marker, seriesName } = params[0]
            var val = params[0].value[params[0].encode.y[0]]
            var formatVal = this.$options.filters['formatNum'](val)
            return `${name}<br/>${marker} ${seriesName} ${currency}${formatVal}`
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          left: 20,
          bottom: 20
        },
        color: GlobalConstAndFunc.Echarts.colorList(data.length - 1),
        dataset: {
          sourceHeader: false,
          dimensions: ['date', seriesName],
          source: data
        },
        xAxis: GlobalConstAndFunc.Echarts.xAxisStyle,
        yAxis: GlobalConstAndFunc.Echarts.yAxisStyle,
        series: [{
          type: 'line',
          seriesLayoutBy: 'row'
        }]
      }

      this.goodsSaleTrendChart.setOption(option, true)
    },

    // 商品详情分布饼图数据获取
    goodsDistributeDataGet(name) {
      this[name + 'goodsDistributeLoadingLoading'] = true
      var postData = {
        type: this[name + 'goodsDistributeType'],
        queryType: name,
        skuId: this.goodsDetailsData.sku_id
      }
      this.queryPost('commonDistributeBySku', postData, (res) => {
        this[name + 'goodsDistributeData'] = res.data.datas
        res.data.datas && this.goodsDistributeChartInit(name, res.data.datas)
        this[name + 'goodsDistributeLoading'] = false
      })
    },

    // 商品详情分布饼图图表配置
    goodsDistributeChartInit(name, data) {
      !this[name + 'goodsDistributeChart'] && (this[name + 'goodsDistributeChart'] = echarts.init(this.$refs[name + 'goodsDistribute'].$refs.chart))
      var currency = this[name + 'goodsDistributeType'] === '2' ? '$' : ''
      var option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          confine: true,
          formatter: (params) => {
            var { marker, name, value, percent } = params
            var formatVal = this.$options.filters['formatNum'](value)
            return `${marker} ${name} ${currency}${formatVal} ${percent}%`
          }
        },
        color: GlobalConstAndFunc.Echarts.colorList(data.length),

        series: [
          {
            type: 'pie',
            radius: 50,
            minAngle: 5,
            label: {
              formatter: (params) => {
                let showVal = this.$options.filters.formatNum(params.value, false, true)
                return `${params.name}{a||}${currency}${showVal}{a||}${params.percent}%
                  `
              },
              // position: 'outer',
              // alignTo: 'edge',
              // edgeDistance: 10,
              rich: {
                a: {
                  color: '#E5E5E5',
                  padding: [0, 4]
                }
              }

            },
            data: data
          }
        ]
      }

      this[name + 'goodsDistributeChart'].setOption(option, true)
    },    

  }
});
