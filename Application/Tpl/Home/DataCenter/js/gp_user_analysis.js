var vm = new Vue({
  el: '#vm',
  components: {
    'chart-wrapper': ChartWrapper,
    'erp-tooltip': ErpTooltip
  },
  filters: {
    /**
      * 数值千分位表示，考虑小数情况
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
    buyDate: {
      date: '',
      preDate: ''
    },
    conuntryTableData: [],
    popoverTableShow: false,
    activeNames: ['1'],
    dates: [
      {
        name: '今日',
        value: 'D'
      },
      {
        name: '近7日',
        value: 'W'
      },
      {
        name: '近30日',
        value: 'M'
      }
    ],
    rangeDate: '',
    pickerMinDate: '',
    pickerOptions: {
      onPick: ({ maxDate, minDate }) => {
        vm.pickerMinDate = minDate.getTime()
        if (maxDate) {
          vm.pickerMinDate = ''
        }
      },

      disabledDate: (time) => {
        const dayTime = 24 * 3600 * 1000
        const yearTime = 365 * dayTime
        const minTime = new Date(2019, 7, 1).getTime()
        const maxTime = Date.now()
        if (vm.pickerMinDate !== '') {
          let pickerMaxDate = vm.pickerMinDate + yearTime
          if (pickerMaxDate > maxTime) {
            pickerMaxDate = maxTime
          }
          return time.getTime() > pickerMaxDate || time.getTime() < minTime
        }
        return time.getTime() < minTime || time.getTime() > maxTime
      }
    },
    storeData: [],
    storeChecked: [],
    storeOptionsShow: false,
    allStoreChecked: true,
    invertStore: false,
    invertStoreDisabled: true,
    dateTime: 'D',
    userStatisticData: {
      visit_user: 0,
      purchase_user: 0,
      accumulate_register_user: 0,
      accumulate_purchase_user: 0
    },
    countryName: '',
    channelName: null, //	访问用户来源
    system: null, //	操作系统
    countryCode: null, //	国家
    siteCode: null,
    siteName: '全部',
    dimensionalityOptions: [
      {
        label: '默认',
        value: 'lineChar'
      },
      {
        label: '按国家',
        value: 'country'
      },
      {
        label: '按渠道',
        value: 'channel'
      },
      {
        label: '按客户端',
        value: 'system'
      },
      {
        label: '按站点',
        value: 'site'
      },
    ],
    chartLabel: 'lineChar',
    granularityDisabled: true,
    granularityOptions: [
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
    dateLabel: 'D',
    userTrendChartData: {
      total: 0
    },
    userTrendGatherData: {},
    searchTrendGatherData: {
      data: {},
      date: ''
    },
    searchTrendDateLabel: 'D',
    userTrendChart: null,
    userCompareShow: true,
    buyCompareShow: true,
    keywordCompareShow: true,
    keywordSearchTransComparisonData: {},
    keywordSearchComparisonTransferDataOverall: '0.00',
    keywordSearchTransferDataOverall: '0.00',
    buyTransDataOverall: '0.00',
    userConuntryChartData: [],
    userConuntryChart: null,
    userConuntryLoading: false,
    userConuntryOperate: false,
    userSourceChart: null,
    userSourceOperate: false,
    channelDistributeChartData: [],
    channelDistributeLoading: false,
    clientDistributionChart: null,
    clientDistributionChartData: [],
    clientDistributionLoading: false,
    clientDistributionOperate: false,
    siteDistributionChart: null,
    siteDistributionChartData: [],
    siteDistributionOperate: false,
    siteDistributionLoading: false,
    storeName: '全部',
    storeDistributionChart: null,
    storeDistributionChartData: [],
    storeDistributionTableHidden: true,
    storeDistributionTableData: {},
    storeDistributionTablePage: 1,
    storeDistributionTableSortWord: 'current_uv',
    storeDistributionTableSortRule: 'desc',
    storeDistributionLoading: false,
    buyConversionRateDateLabel: 'D',
    buyTransData: {},
    buyTransLineData: {},
    buyTransLineChart: null,
    keyword: '', // 搜索关键词
    searchKeyword: '',
    keywordSearchTransferLineChart: null,
    buyTransChart: null,
    userGroupTableType: '1027',
    userGroupData: {
      datas: {
        date: '',
      },
      totalCount: 0
    },
    userGroupTableLoading: false,
    userGroupTablePage: 1,
    tableSortOrders: ['ascending', 'descending'],
    userGroupTableDefaultSort: {
      prop: 'visitwebsite',
      order: 'descending'
    },
    userGroupTableSortWord: 'visitwebsite',
    userGroupTableSortRule: 'desc',
    userGroupDialogVisible: false,
    userGroupTableSelectedRow: {},
    searchTrendChartType: 'all_rate',
    searchTrendChartTypes: [
      {
        label: '整体转化率',
        value: 'all_rate'
      },
      {
        label: '搜索量',
        value: 'searchs'
      },
      {
        label: '人均搜索量',
        value: 'search_per'
      },
    ],
    goodsAnalysisTablePage: 1,
    goodsAnalysisTableSortWord: 'pageview',
    goodsAnalysisTableSortRule: 'desc',
    goodsAnalysisData: {
      datas: {
        validatedUserTotal: '',
        pageViewTotal: '',
        averageTimeTotal: '',
        dataInfo: []
      }
    },
    goodsAnalysisTableLoading: false,
    // keywordSearchTrendDialogVisible: false,
    searchesTrendKeyword: '',
    keywordSearchDistributeData: {
      datas: {
        date: '',
        total: '',
        sum_keyword: '',
        average_num: '',
        dataInfo: []
      },
      totalCount: 0
    },
    keywordSearchDistributeLoading: false,
    keywordSearchDistributeTablePage: 1,
    keywordSearchDistributeTableDefaultSort: {
      prop: 'keyword_account',
      order: 'descending'
    },
    keywordSearchDistributeTableSortWord: 'keyword_account',
    keywordSearchDistributeTableSortRule: 'desc',
    // keywordSearchTrendData: {},
    // keywordSearchTrendChart: null,
    keywordSearchTransferData: {},
    keywordSearchTransferChart: null,
    keywordSearchTransDialogVisible: false,
    keywordSearchTransDialogPage: 1,
    keywordSearchTransDialogData: [],
    keywordSearchTransDialogTableSortWord: 'look_num',
    keywordSearchTransDialogTableSortRule: 'desc',
    keywordSearchTransDialogTableUrl: '/gpUserAnalysis/keywordSearchDistributeGoods',
    exportKeywordSearchTransDialogTableUrl: '/gpUserAnalysis/keywordSearchDistributeGoodsExport',
    keywordSearchTransDialogTableLoading: false,
    language: 'cn',
    countryCodeList: null,
    lengthwaysNode: '5',
    acrossNode: '5',
    userTrackChart: null,
    userTrackGather: [],
    userTrackChartData: [],
  },
  computed: {
    // 各图表模块中展示的日期范围说明
    rangeDateInfo() {
      let str = null
      switch (this.dateTime) {
        case 'D':
          str = '今日'
          break;
        case 'W':
          if (this.dateLabel !== 'M') {
            str = '近7日'
          }
          break
        case 'M':
          if (this.dateLabel !== 'M') {
            str = '近30日'
          }
          break
        default:
          break;
      }
      return str
    },
    // 流量分布及用户行为跟踪模块，汇总信息子项宽度
    infoItemWidth() {
      return this.lengthwaysNode === '5' ? 140 : 120
    },
    // 当前的周期筛选日期是不是单天
    isSingleDay() {
      if (this.keywordSearchDistributeData.datas.date.length > 11) {
        const arr = this.keywordSearchDistributeData.datas.date.split('-')
        return arr[0] === arr[1]
      }
      return true
    },
    // “店铺”筛选条件中选中的店铺id字符串，提供给接口使用
    shopIds() {
      return this.storeChecked.join()
    },
    // 筛选框中，店铺名字
    storeNameForSelect() {
      const store = this.storeData.find(
        (item) => item.store_id == this.storeChecked[0]
      )
      return store.store_name
    },
    // 店铺分布饼图，返回按钮（用于重置饼图），展示与否
    storeDistributionOperate() {
      return !this.allStoreChecked
    },
  },
  watch: {
    dateTime(newValue) {
      switch (newValue) {
        case 'D':
          this.dateLabel = 'D'
          this.rangeDate = ''
          this.granularityDisabled = true
          this.resetPieChart()
          break
        case 'W':
        case 'M':
          this.dateLabel = 'D'
          this.rangeDate = ''
          this.granularityDisabled = false
          this.resetPieChart()
          break
        case '':
          this.granularityDisabled = false
          break
        default:
          this.granularityDisabled = false
          break;
      }
    },
    // 监听店铺筛选条件中，“全部”状态
    allStoreChecked(newVal) {
      if (newVal) {
        this.invertStore = false
        this.invertStoreDisabled = true

        let checkedIds = []
        for (const item of this.storeData) {
          item.checked = false
          checkedIds.push(item.store_id)
        }
        this.storeChecked = checkedIds
        this.getAllChartData()
      } else {
        this.invertStore = false
        this.invertStoreDisabled = false
      }
    },
  },
  created: function () {
    if (getCookie('think_language') !== "zh-cn") {
      ELEMENT.locale(ELEMENT.lang.en)
      this.language = 'us'
    };
    this.countryName = this.$lang('全部')
    this.getAllChartDataDebounced = _.debounce(this.getAllChartDataTurly, 700)
    this.getUserStatisticData()
    this.storeDataGet()
  },
  mounted: function () {
    this.userTrendChart = echarts.init(this.$refs.userTrendChart.$refs.chart)

    this.userConuntryChart = echarts.init(this.$refs.userConuntryChart.$refs.chart)
    this.userConuntryChart.on('click', (event) => {
      this.countryName = event.name
      if (event.name === '其他' || event.name === 'Others') {
        let arr = []
        for (let item of this.userConuntryChartData) {
          if (item.countryName !== event.name) {
            arr.push(item.countryCode)
            this.userConuntryOperate = true
          }
        }
        this.countryCodeList = arr.join(',')
        this.countryCode = arr.join(',')
        this.getAllChartData()
      } else {
        for (let item of this.userConuntryChartData) {
          if (item.countryName === event.name) {
            this.countryCode = item.countryCode
            this.userConuntryOperate = true
            this.getAllChartData()
          }
        }
      }
    })

    this.userSourceChart = echarts.init(this.$refs.userSourceChart.$refs.chart)
    this.userSourceChart.on('click', (event) => {
      this.channelName = event.name
      this.userSourceOperate = true
      this.getAllChartData()
    })

    this.clientDistributionChart = echarts.init(this.$refs.clientDistributionChart.$refs.chart)
    this.clientDistributionChart.on('click', (event) => {
      this.system = event.name
      this.clientDistributionOperate = true
      this.getAllChartData()
    })

    this.siteDistributionChart = echarts.init(this.$refs.siteDistributionChart.$refs.chart)
    this.siteDistributionChart.on('click', (event) => {
      this.siteName = event.name
      for (let item of this.siteDistributionChartData) {
        if (item.name === event.name) {
          this.siteCode = item.siteCode
          this.siteDistributionOperate = true
        }
      }
      this.getAllChartData()
    })

    this.storeDistributionChart = echarts.init(this.$refs.storeDistributionChart.$refs.chart)
    this.storeDistributionChart.on('click', (event) => {
      this.storeName = event.name
      const pieItem = this.storeDistributionChartData.find(item => item.name === event.name)
      pieItem.store_id.includes('-') ? this.storeChecked = pieItem.store_id.split('-') : this.storeChecked = [pieItem.store_id]

      for (const item of this.storeData) {
        item.checked = this.storeChecked.includes(item.store_id)
      }

      this.allStoreChecked = false
      this.getAllChartData()
    })
    this.buyTransLineChart = echarts.init(this.$refs.buyTransLineChart.$refs.chart)

    this.keywordSearchTransferLineChart = echarts.init(this.$refs.keywordSearchTransferLineChart.$refs.chart)

    // 用户分组统计模块，DOM大小变化时，左侧的“购买转化漏斗”模块中的漏斗图大小也要变化
    this.buyTransChart = echarts.init(this.$refs.buyTransChart.$refs.chart)

    this.keywordSearchTransferChart = echarts.init(this.$refs.keywordSearchTransferChart.$refs.chart)
    this.keywordSearchTransferChart.on('click', (params) => {
      console.log(params)
      if (params.dataIndex === 1) {
        this.keywordSearchTransDialogPage = 1
        this.keywordSearchTransDialogTableSortWord = 'look_num'
        this.keywordSearchTransDialogTableSortRule = 'desc'
        if (params.seriesIndex === 1) {
          this.keywordSearchTransDialogTableUrl = '/gpUserAnalysis/keywordSearchComparisonDistributeGoods';
          this.exportKeywordSearchTransDialogTableUrl = '/gpUserAnalysis/keywordSearchComparisonDistributeGoodsExport';

        } else {
          this.keywordSearchTransDialogTableUrl = '/gpUserAnalysis/keywordSearchDistributeGoods';
          this.exportKeywordSearchTransDialogTableUrl = '/gpUserAnalysis/keywordSearchDistributeGoodsExport';
        }
        this.getKeywordSearchTransDialogData()
        this.keywordSearchTransDialogVisible = true
      }
    })

    this.userTrackChart = echarts.init(this.$refs.userTrackChart)

    this.getAllChartData()
  },
  methods: {
    renderTableHeader(h, { column, $index }, isLastHeader, hasTooltip) {
      return GlobalConstAndFunc.Element.renderTableHeader(h, { column, $index }, isLastHeader, hasTooltip)
    },
    // 百分数转小数
    percentToPoint(percent) {
      var str = percent.replace("%", "");
      str = str / 100;
      return str;
    },
    /**
     * 日期范围选择器的绑定数据转化为接口要求的格式
     * @param {number} type 1为开始时间，2为结束时间
     */
    formatRangeDate(type) {
      if (Array.isArray(this.rangeDate)) {
        return type === 1 ? this.rangeDate[0] : this.rangeDate[1]
      } else {
        return null
      }
    },
    // 自定义饼图的tooltip
    formatPieTooltip(params) {
      let { value, percent, seriesName, name, marker } = params
      let formatValue = this.$options.filters['formatNum'](value)
      percent = percent.toFixed(2)
      return `${marker}${name}： ${formatValue}(${percent}%)`
    },
    // 关闭筛选区域  店铺的下拉面板
    closeQueryPanel() {
      if (this.storeOptionsShow) {
        this.storeOptionsShow = false
      }
    },
    // 获取店铺
    storeDataGet() {
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        languageName: this.language,
      }
      this.queryPost('gpUserAnalysis/storeInfo', postData)
        .then((res) => {
          if (res.data.success) {
            // 给数据的每一项添加一个checked属性，用以控制复选框
            this.storeData = res.data.datas.map((item) => {
              item.checked = false
              item.store_id = item.store_id.toString()
              return item
            })
          } else {
            this.$message.error(res.data.msg)
          }
        })
        .catch((err) => {
          console.log(err)
        })
    },
    // 店铺下拉框，删除店铺
    storeCheckedDel() {
      const id = this.storeChecked[0]
      this.storeData.forEach((item) => {
        if (item.store_id === id) {
          this.storeChecked.shift()
          item.checked = !item.checked
          return
        }
      })
      let len = this.storeChecked.length

      this.allStoreChecked = len === this.storeData.length || len === 0

      if (!this.allStoreChecked) {
        this.getAllChartData()
      }
    },

    //店铺 反选
    invertStoreChange(val) {
      let checkedIds = []
      this.storeData.forEach((item) => {
        item.checked = !item.checked
        if (item.checked) {
          checkedIds.push(item.store_id)
        }
      })
      this.storeChecked = checkedIds

      this.getAllChartData()
    },
    // 具体店铺改变
    storeCheckedChange() {
      let checkedIds = []
      this.storeData.forEach((item) => {
        if (item.checked) {
          checkedIds.push(item.store_id)
        }
      })
      this.storeChecked = checkedIds
      let len = checkedIds.length

      this.allStoreChecked = len === this.storeData.length || len === 0

      if (!this.allStoreChecked) {
        this.getAllChartData()
      }
    },

    // getAllChartData方法，防抖调用
    getAllChartData() {
      this.getAllChartDataDebounced()
    },
    // 获取页面所有图表数据
    getAllChartDataTurly() {

      this.buyCompareShow = true;
      this.keywordCompareShow = true;

      this.chartLabel = 'lineChar'
      this.dateLabel = 'D'
      this.userCompareShow = true;
      this.getUserTrendChartData()
      this.getUserTrendGatherData()

      this.getSearchTrendGatherData()
      this.getUserConuntryChartData()
      this.getUserSourceChartData()
      this.getClientDistributionChartData()
      this.getSiteDistributionChartData()

      this.storeDistributionTableHidden = true
      this.storeDistributionChartDataGet()
      

      this.buyConversionRateDateLabel = 'D'
      this.getBuyConversionRatesTrendData();


      this.getBuyTransData()
      this.userGroupTableReset()

      this.searchTrendChartType = 'all_rate'
      this.searchTrendDateLabel = 'D'
      this.getSearchTrendData();

      this.popoverTableShow = false;
      this.keyword = '';
      this.searchKeyword = '';
      this.keywordSearchDistributeTableSortRule = 'desc'
      this.keywordSearchDistributeTableSortWord = 'keyword_account'
      this.keywordSearchDistributeTablePage = 1
      this.$refs.keywordSearchDistributeTable.sort('keyword_account', 'descending')
      this.getKeywordSearchTransferData()

      // this.getUserTrackGather()
      // this.getUserTrackChartData()
    },

    computeStr(str) {
      str = this.$lang(str)
      if (str.length > 10) {
        return str
      } else {
        return ''
      }
    },
    rangeDateChange(value) {
      this.dateLabel = 'D'
      if (value) {
        this.dateTime = ''
        this.resetPieChart()
      } else {
        this.dateTime = 'D'
      }
    },

    // 页面顶部用户统计信息数据获取
    getUserStatisticData() {
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';'
      }
      this.queryPost('/gpUserAnalysis/userStatistics', postData).then(res => {
        if (res.data.success) {
          this.userStatisticData = res.data.datas[0]
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 搜索趋势指标数据获取
    getSearchTrendGatherData() {
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        name: this.keyword,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        language: this.language,
        storeId: this.shopIds,
      }
      this.queryPost('/gpUserAnalysis/searchTrendIndex', postData).then(res => {
        if (res.data.success) {
          this.searchTrendGatherData = res.data.datas
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 访问用户趋势图表数据获取
    getUserTrendChartData() {
      this.userTrendChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/visitUserTrend', postData).then(res => {
        if (res.data.success) {
          this.userTrendChartData = res.data.datas

          this.initUserTrendChart(res.data.datas.info)
        } else {
          this.$message.error(res.data.msg)
        }
        this.userTrendChart.hideLoading()
      }).catch(err => {
        console.log(err)
      });
    },

    // 访问用户趋势汇总信息数据获取
    getUserTrendGatherData() {
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/visitUserTrendGather', postData).then(res => {
        if (res.data.success) {
          this.userTrendGatherData = res.data.datas;
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 访问用户趋势图表配置
    initUserTrendChart(data) {
      let time = this.language === 'cn' ? '时' : 'Hour';
      let series = []
      if (this.chartLabel === 'lineChar') {
        series = [
          {
            type: 'line',
            name: this.$lang('访问用户'),
            seriesLayoutBy: 'row',
            encode: { x: 0, y: 2 }
          }
        ]
      } else {
        for (let index = 2; index < data.length; index++) {
          series.push({
            type: 'line',
            name: this.$lang(data[index][0]),
            seriesLayoutBy: 'row',
            encode: { x: 0, y: index }
          })
        }
      }
      lineOption = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          confine: true,
          formatter: (params) => {
            let contentArr = [], title = params[0].name
            if (this.dateLabel !== 'D') {
              title = params[0].value[1]
            }
            for (let index = 0; index < params.length; index++) {
              const formatValue = this.$options.filters['formatNum'](params[index].value[params[index].encode.y[0]])
              const contentItem = `
                  <div class="item">
                    ${params[index].marker}${params[index].seriesName}<span class="value">${formatValue}</span>
                  </div>`
              contentArr.push(contentItem)
            }

            if (this.dateTime === 'D') {
              title += time
            }
            return GlobalConstAndFunc.Echarts.tooltipFormatter(contentArr, title)
          }
        },
        grid: GlobalConstAndFunc.Echarts.gridSetStyle,
        color: ['#0375DE', '#13C2C2', '#FFC30F', '#FA8C16', '#F14E22'],
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
        series,
      }
      option = lineOption;
      this.userTrendChart.setOption(option, true)
    },
    // 访问用户趋势,维度、粒度切换
    userTrendDateLabelChanged() {
      this.userCompareShow ? this.getUserTrendChartData() : this.userComparison()
    },
    // 访问用户趋势,对比返回
    userBack() {
      this.userCompareShow = true;
      this.initUserTrendChart(this.userTrendChartData.info);
    },
    // 访问用户趋势，对比数据获取
    userComparison() {
      this.userTrendChart.showLoading({
        text: ''
      })
      this.userCompareShow = false;
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/visitUserComparisonTrend', postData).then(res => {
        this.userTrendChart.hideLoading();
        if (res.data.success) {
          this.initUserComparisonChart(res.data.datas.info);
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 访问用户趋势，导出图表
    exportChart() {
      var param = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        language: this.language,
        storeId: this.shopIds,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
      }
      let url = '/gpUserAnalysis/visitUserTrendImport';
      if (!this.userCompareShow) {
        url = '/gpUserAnalysis/visitUserComparisonTrendImport'
      }
      axios.post(this.apiHost + url, Qs.stringify(param), {
        headers: {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        responseType: 'blob'
      }).then((res) => {
        let blob = res.data
        let reader = new FileReader()
        reader.readAsDataURL(blob)
        reader.onload = (e) => {
          let a = document.createElement('a')
          let fileName = `GP访问用户数(${this.userTrendChartData.date}).xlsx`
          if (this.language !== 'cn') {
            fileName = `Number of GP's visiting users (${this.userTrendChartData.date}).xlsx`
          }
          a.download = fileName
          a.href = e.target.result
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 访问用户趋势，对比图表配置
    initUserComparisonChart(datas) {
      let series1 = [], series2 = []
      for (let index = 2; index < datas.comparison.length; index++) {
        series1.push({
          type: 'line',
          name: this.chartLabel === 'lineChar' ? this.$lang('上期访问用户') : this.$lang('上期 ' + datas.comparison[index][0]),
          lineStyle: { width: 1, type: 'dashed' },
          seriesLayoutBy: 'row',
          color: GlobalConstAndFunc.Echarts.colorList(datas.comparison.length - 2)[index - 2],
          xAxisIndex: 0,
          datasetIndex: 0,
          encode: { x: 0, y: index }
        })
      }
      for (let index = 2; index < datas.current.length; index++) {
        series2.push({
          type: 'line',
          name: this.chartLabel === 'lineChar' ? this.$lang('本期访问用户') : this.$lang('本期 ' + datas.current[index][0]),
          seriesLayoutBy: 'row',
          color: GlobalConstAndFunc.Echarts.colorList(datas.current.length - 2)[index - 2],
          xAxisIndex: 1,
          datasetIndex: 1,
          encode: { x: 0, y: index }
        })
      }

      let time = this.language === 'cn' ? '时' : 'Hour';
      let option = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
          selectedMode: false
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          confine: true,
          formatter: (params) => {
            let str = '';
            let arr1 = [], arr2 = []
            for (let index = 0; index < params.length; index++) {
              const param = params[index]
              const value = param.value[param.encode.y[0]]
              let formatValue = this.$options.filters['formatNum'](value)
              const contentItem = `
                  <div class="item">
                    ${params[index].marker}${params[index].seriesName}<span class="value">${formatValue}</span>
                  </div>`

              if (index < params.length / 2) {
                arr1.push(contentItem)
              } else {
                arr2.push(contentItem)
              }

            }
            let title1 = '', title2 = ''
            title1 = (this.dateLabel !== 'D') ? params[0].value[1] : params[0].value[0]
            title2 = (this.dateLabel !== 'D') ? params[params.length / 2].value[1] : params[params.length / 2].value[0]

            if (this.dateTime === 'D') {
              title1 += time
              title2 += time
            }
            str = GlobalConstAndFunc.Echarts.tooltipFormatter(arr1, title1) + GlobalConstAndFunc.Echarts.tooltipFormatter(arr2, title2)

            return str
          }
        },
        dataset: [{
          source: datas.comparison,
        },
        {
          source: datas.current,
        }],
        grid: GlobalConstAndFunc.Echarts.gridSetStyle,
        color: GlobalConstAndFunc.Echarts.colorList(datas.current.length + datas.comparison.length - 4),
        xAxis: [
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            show: false,
            position: 'bottom',
            boundaryGap: false
          },
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            position: 'bottom',
            boundaryGap: false
          }
        ],
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          type: 'value',
        },
        series: series1.concat(series2),
      }
      this.userTrendChart.setOption(option, true)
    },
    // 访问用户国家分布图表数据获取
    getUserConuntryChartData() {
      this.userConuntryLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        storeId: this.shopIds,
        language: this.language,
      }
      this.queryPost('/gpUserAnalysis/visitUserCountryDistribute', postData).then(res => {
        if (res.data.success) {
          this.userConuntryChartData = res.data.datas
          this.initUserConuntryChart(res.data.datas || [])
          this.userConuntryLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 点击行
    rowChange(row) {
      console.log(row)
      this.countryCode = row.countryCode;
      this.userConuntryOperate = true;
      this.popoverTableShow = false;
      this.getAllChartData()
    },
    // 排序
    countryUserChange(order) {
      console.log(order)
      if (order.order == 'ascending') {
        this.getCountryMore('asc');
      } else {
        this.getCountryMore('desc');
      }
    },
    // 点击更多显示图表
    clickMore() {
      this.popoverTableShow = true;
      this.getCountryMore();
    },
    // 国家分布, 更多数据获取
    getCountryMore(order) {
      this.userConuntryLoading = true;
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        language: this.language,
        out_type: 'country_sum_all',
        storeId: this.shopIds,
        sordRule: order
      }
      this.queryPost('/gpUserAnalysis/visitUserCountryDistribute', postData).then(res => {
        if (res.data.success) {
          let total = 0;
          for (let i = 0; i < res.data.datas.length; i++) {
            total += Number(res.data.datas[i].countryUserCount);
          }
          this.conuntryTableData = res.data.datas.map(item => {
            return {
              countryCode: item.countryCode,
              countryName: item.countryName,
              countryUserCount: item.countryUserCount,
              percentage: (Number(item.countryUserCount) / total * 100).toFixed(2) + '%'
            }
          });
          this.userConuntryLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 国家分布图表配置
    initUserConuntryChart(resData) {
      let series = [], legendData = [], option
      for (let item of resData) {
        let set = {
          value: item.countryUserCount,
          name: item.countryName
        }
        series.push(set)
        legendData.push(item.countryName)
      }
      option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: this.formatPieTooltip
        },
        color: GlobalConstAndFunc.Echarts.colorList(resData.length),
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name}, ${showVal}`
          },
        },
        series: [
          {
            type: 'pie',
            radius: 70,
            minAngle: 5,
            center: ['50%', '60%'],
            data: series
          }
        ]
      }
      this.userConuntryChart.setOption(option, true)
    },

    // 渠道分布图表数据获取
    getUserSourceChartData() {
      this.channelDistributeLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/visitUserSource', postData).then(res => {
        if (res.data.success) {
          this.channelDistributeChartData = res.data.datas
          this.initUserSourceChart(res.data.datas || [])
          this.channelDistributeLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 渠道分布图表配置
    initUserSourceChart(resData) {
      let series = [], legendData = [], option
      for (let item of resData) {
        let set = {
          value: item.channelUserCount,
          name: item.channelName
        }
        series.push(set)
        legendData.push(item.channelName)
      }
      option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: this.formatPieTooltip
        },
        color: GlobalConstAndFunc.Echarts.colorList(resData.length),
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name}, ${showVal}`
          },
        },
        series: [
          {
            type: 'pie',
            radius: 70,
            minAngle: 5,
            center: ['50%', '60%'],
            data: series
          }
        ]
      }
      this.userSourceChart.setOption(option, true)
    },

    // 客户端分布图表数据获取
    getClientDistributionChartData() {
      this.clientDistributionLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/clientDistribute', postData).then(res => {
        if (res.data.success) {
          this.clientDistributionChartData = res.data.datas
          this.initClientDistributionChart(res.data.datas || [])
          this.clientDistributionLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 客户端分布图表配置
    initClientDistributionChart(resData) {
      let series = [], legendData = [], option
      for (let item of resData) {
        let set = {
          value: item.systemUserCount,
          name: item.systemName
        }
        series.push(set)
        legendData.push(item.systemName)
      }
      option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: this.formatPieTooltip
        },
        color: GlobalConstAndFunc.Echarts.colorList(resData.length),
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name}, ${showVal}`
          },
        },
        series: [
          {
            type: 'pie',
            radius: 70,
            minAngle: 5,
            center: ['50%', '60%'],
            data: series
          }
        ]
      }
      this.clientDistributionChart.setOption(option, true)
    },

    // 站点分布图表数据获取
    getSiteDistributionChartData() {
      this.siteDistributionLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        storeId: this.shopIds,
        language: this.language,
      }
      this.queryPost('/gpUserAnalysis/siteDistribute', postData).then(res => {
        if (res.data.success) {
          this.siteDistributionChartData = res.data.datas
          this.initSiteDistributionChart(res.data.datas || [])
          this.siteDistributionLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 站点分布图表配置
    initSiteDistributionChart(resData) {
      let series = [], legendData = [], option
      for (let item of resData) {
        let set = {
          value: item.gatherCount,
          name: item.name
        }
        series.push(set)
        legendData.push(item.name)
      }
      option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: this.formatPieTooltip
        },
        color: GlobalConstAndFunc.Echarts.colorList(resData.length),
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name}, ${showVal}`
          },
        },
        series: [
          {
            type: 'pie',
            radius: 70,
            minAngle: 5,
            center: ['50%', '60%'],
            data: series
          }
        ]
      }
      this.siteDistributionChart.setOption(option, true)
    },
    // 店铺分布图表数据获取
    storeDistributionChartDataGet() {
      this.storeDistributionLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        storeId: this.shopIds,
        language: this.language,
      }
      this.queryPost('/gpUserAnalysis/storeDistribute', postData).then(res => {
        if (res.data.success) {
          this.storeDistributionChartData = res.data.datas
          this.storeDistributionChartInit(res.data.datas || [])
          this.storeDistributionLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 店铺分布图表配置
    storeDistributionChartInit(resData) {
      let option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: this.formatPieTooltip
        },
        color: GlobalConstAndFunc.Echarts.colorList(resData.length),
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name}, ${showVal}`
          },
        },
        series: [
          {
            type: 'pie',
            radius: 70,
            minAngle: 5,
            center: ['50%', '60%'],
            data: resData
          }
        ]
      }
      this.storeDistributionChart.setOption(option, true)
    },
    // 店铺分布,表格和饼图展示切换
    storeDistributionTableHiddenChange() {
      if (this.storeDistributionTableHidden) {
        this.storeDistributionTableSortWord = 'current_uv'
        this.storeDistributionTableSortRule = 'desc'
        this.storeDistributionTablePage = 1
        this.storeDistributionTableDataGet()
      }
      this.storeDistributionTableHidden = !this.storeDistributionTableHidden
    },
    // 店铺分布,表格数据获取
    storeDistributionTableDataGet() {
      this.storeDistributionLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        storeId: this.shopIds,
        language: this.language,
        page: this.storeDistributionTablePage,
        pageSize: 4,
        sortWord: this.storeDistributionTableSortWord,
        sortRule: this.storeDistributionTableSortRule
      }
      this.queryPost('/gpUserAnalysis/storeDistributeTable', postData).then(res => {
        if (res.data.success) {
          this.storeDistributionTableData = res.data
          this.storeDistributionLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 店铺分布,表格排序
    storeDistributionTableSort({
      column,
      prop,
      order
    }) {
      this.storeDistributionTableSortWord = prop
      this.storeDistributionTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.storeDistributionTableDataGet()
    },

    // 重置点击饼图触发的筛选条件
    resetPieChart(type) {
      switch (type) {
        case 1:
          this.countryCode = null
          this.userConuntryOperate = false
          this.countryCodeList = null
          this.getAllChartData()
          break;
        case 2:
          this.channelName = null
          this.userSourceOperate = false
          this.getAllChartData()
          break;
        case 3:
          this.system = null
          this.clientDistributionOperate = false
          this.getAllChartData()
          break;
        case 4:
          this.siteCode = null
          this.siteName = '全部'
          this.siteDistributionOperate = false
          this.getAllChartData()
          break;
        case 5:
          this.allStoreChecked = true
          break;
        default:
          this.countryCode = null
          this.userConuntryOperate = false
          this.countryCodeList = null
          this.channelName = null
          this.userSourceOperate = false
          this.system = null
          this.clientDistributionOperate = false
          this.siteCode = null
          this.siteName = '全部'
          this.siteDistributionOperate = false
          this.allStoreChecked = true
          this.getAllChartData()
      }

    },

    // 购买转化率趋势,数据获取
    getBuyConversionRatesTrendData() {
      this.buyTransLineChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        system: this.system,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        language: this.language,
        storeId: this.shopIds,
        dateLabel: this.buyConversionRateDateLabel,
      }
      this.queryPost('/gpUserAnalysis/purchaseTrend', postData).then(res => {
        if (res.data.success) {
          this.buyTransLineData = res.data.datas
          this.initBuyConversionRatesTrendChart(res.data.datas.current)
        } else {
          this.$message.error(res.data.msg)
        }
        this.buyTransLineChart.hideLoading()
      }).catch(err => {
        console.log(err)
      });
    },
    // 购买转化率趋势,图表配置
    initBuyConversionRatesTrendChart(data) {
      let lineOption = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
          icon: 'circle',
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          formatter: (params) => {
            let contentArr = []
            let { seriesName, name: title, marker, value, dimensionNames } = params[0]
            if (this.buyConversionRateDateLabel !== 'D') {
              title = value[1]
            }
            const contentItem = `
                  <div class="item">
                    ${marker}${seriesName}<span class="value">${value[2]}%</span>
                  </div>`
            contentArr.push(contentItem)
            return GlobalConstAndFunc.Echarts.tooltipFormatter(contentArr, title)
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          right: 30
        },
        color: ['#0375DE', '#13C2C2', '#FFC30F', '#FA8C16', '#F14E22'],
        dataset: {
          source: data,
          sourceHeader: false
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
          axisLine: {
            show: false
          },
          axisTick: {
            show: false
          },
          axisLabel: {
            show: true,
            interval: 'auto',
            formatter: function (value) {
              return value.toFixed(2) + '%'
            }
          },
          splitLine: {
            lineStyle: {
              type: 'dotted',
              color: '#F0F0F0'
            }
          }
        },
        series: [
          {
            type: 'line',
            name: this.$lang('购买转化率'),
            encode: { x: 0, y: 2 },
            seriesLayoutBy: 'row'
          }
        ]
      }
      this.buyTransLineChart.setOption(lineOption, true)
    },
    // 购买转化率趋势,日期粒度改变
    buyConversionRateDateLabelChanged(value) {
      this.buyCompareShow ? this.getBuyConversionRatesTrendData() : this.getBuyLineComparison()
    },
    buyBack() {
      this.buyCompareShow = true;
      this.userGroupTablePage = 1;
      this.userGroupTableSortWord = 'visitwebsite';
      this.userGroupTableSortRule = 'desc'
      this.$refs.userGroupTable.sort('visitwebsite', 'descending')
      this.getBuyConversionRatesTrendData();
      this.getBuyTransData();
      // this.getUserGroupData();
    },
    // 购买转化率模块对比
    buyComparison() {
      this.buyCompareShow = false;
      this.userGroupTablePage = 1;
      this.userGroupTableSortWord = 'uv';
      this.userGroupTableSortRule = 'desc';
      this.getBuyLineComparison();
      this.getBuyFuunelComparison();
      this.getUserGroupCompareData();
      // this.$refs.userGroupTable2.sort('uv', 'descending');
    },
    // 购买转化率趋势,对比数据获取
    getBuyLineComparison() {
      this.buyTransLineChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.buyConversionRateDateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/purchaseTrendCompare', postData).then(res => {
        this.buyTransLineChart.hideLoading();
        if (res.data.success) {
          this.initBuyComparisonChart(res.data.datas)
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 购买转化趋势，对比图表配置
    initBuyComparisonChart(datas) {
      let gridSet = {
        left: 10,
        right: 40,
        top: 20,
        bottom: 40,
        containLabel: true
      }
      let option = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
          icon: 'circle',
          selectedMode: false,
          bottom: 10,
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          formatter: (params) => {
            let str = '';
            for (let index = 0; index < params.length; index++) {
              const param = params[index]
              const value = param.value[param.encode.y[0]]
              let formatValue = this.$options.filters['formatNum'](value, true)
              let title = '', seriesName = ''
              title = (this.buyConversionRateDateLabel !== 'D') ? param.value[1] : param.value[0]
              seriesName = param.seriesName
              str += `<div class="title">${title}</div>
                      <section class="content-lt12" style="margin-bottom: 10px">
                        <div class="item">
                          ${param.marker}${seriesName}<span class="value">${formatValue}</span>
                        </div>
                      </section>`
            }
            return `<section class="echarts-tooltip">
                      ${str}
                    </section>`
          }
        },
        grid: gridSet,
        dataset: [{
          source: datas.comparison,
          sourceHeader: false
        },
        {
          source: datas.current,
          sourceHeader: false
        }],
        color: GlobalConstAndFunc.Echarts.colorList(2),
        xAxis: [
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            show: false,
            position: 'bottom',
            boundaryGap: false
          },
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            position: 'bottom',
            boundaryGap: false
          }
        ],
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          type: 'value',
          axisLabel: {
            show: true,
            interval: 'auto',
            formatter: (value) => {
              return this.$options.filters['formatNum'](value, true)
            }
          }
        },
        series: [
          {
            type: 'line',
            name: this.$lang('上期购买转化率'),
            lineStyle: { width: 1, type: 'dashed' },// 往期数据加虚线
            seriesLayoutBy: 'row',
            xAxisIndex: 0,
            datasetIndex: 0,
            encode: {
              x: 0,
              y: 2
            }
          },
          {
            type: 'line',
            name: this.$lang('本期购买转化率'),
            xAxisIndex: 1,
            datasetIndex: 1,
            seriesLayoutBy: 'row',
            encode: {
              x: 0,
              y: 2
            }
          }
        ]
      }
      this.buyTransLineChart.setOption(option, true)
    },
    // 获取漏斗图对比趋势
    getBuyFuunelComparison() {
      this.buyTransChart.clear();
      this.buyTransChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/purchaseFunnelCompare', postData).then(res => {
        this.buyTransChart.hideLoading();
        const info = res.data.datas;
        if (res.data.success) {
          const data = {
            chartData: info.current.chartData,
            chartPercent: info.current.chartPercent,
            comparisonChartData: info.comparison.chartData,
            comparisonChartPercent: info.comparison.chartPercent,
          }
          this.buyDate = {
            date: info.date,
            preDate: info.preDate
          }
          this.buyTransDataOverall = info.comparison.overall
          this.initBuyFuunelComparisonChart(data)
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 购买转化漏斗对比,图表配置
    initBuyFuunelComparisonChart(resData) {
      let { chartData, chartPercent, comparisonChartData, comparisonChartPercent } = resData;
      var rightArrow = "image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMEAAAAaCAYAAAF5vAjmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAwaADAAQAAAABAAAAGgAAAAAJ2JVxAAABUklEQVR4Ae2ZQQqDMBBFG28hXqVdteeqSM+q3kKbCFk4m4AmDmaeUGS0mcm8z7dpdA9/jOM4hHOJo+u6vkTeXc7QQLO7UihwJVHFOV9SJBa75xlEKd0us0RqImfvX+Lrs5NMjXfhC1c8O1IT4T4EIGCUAL+aRoXP1nY1q4psRJQSVbEqUmKXrSwiZEN5PBEiHGeXbSQiZEN5PNH2J3Oapte6rp/jaRgJgRsSmOe55zGkLBwCIIAyAeXyOAABlAkol8cBCKBMQLk8DkAAZQLK5XGAsgC86tMV4LttxsU5+E25p3PuHWPOEKiZgN+A7v3ntzNBzQ3TGwQkgbAhvSxLw1pIkiE2RwATmJOchiUBTCCJEJsjgAnMSU7DkgAmkESIzRHABOYkp2FJABNIIsTmCGACc5LTsCSACSQRYnMEMIE5yWlYEnDh1bG8SAwBKwTath3+D9M/NovemzEAAAAASUVORK5CYII=";
      var markLineSetting = {
        show: true,
        backgroundColor: '#f4f4f4',
        position: 'inside',
        color: 'rgba(0, 0, 0, 0.85)',
        width: 52,
        height: 20,
        lineHeight: 20,
        offset: [100, 0],
        align: 'center',
        fontSize: 13,
        formatter: function (d) {
          if (d.value) {
            var ins = '{words|' + d.data.itemValue + '}';
            return ins
          }
        },
        rich: {
          words: {
            color: '#333',
            fontSize: 13,
            lineHeight: 20,
          }
        }
      };
      let funnelData = [];
      let comparisonFunnelData = []
      for (var i = 0; i < chartData.length; i++) {
        let obj1 = {
          value: (100 / chartData.length) * (chartData.length - i),
          num: chartData[i].value,
          name: chartData[i].name
        };
        funnelData.push(obj1);
      }
      for (var i = 0; i < comparisonChartData.length; i++) {
        let obj1 = {
          value: (100 / comparisonChartData.length) * (comparisonChartData.length - i),
          num: comparisonChartData[i].value,
          name: comparisonChartData[i].name
        };
        comparisonFunnelData.push(obj1);
      }
      let arrowData = []
      let comparisonArrowData = []
      for (var i = 0; i < chartPercent.length; i++) {
        var _objdd = {
          value: 270,
          itemValue: chartPercent[i].value + '%',
          label: markLineSetting,
        }
        arrowData.push(_objdd);
      }
      for (var i = 0; i < comparisonChartPercent.length; i++) {
        var _objdd = {
          value: 370,
          itemValue: comparisonChartPercent[i].value + '%',
          label: markLineSetting,
        }
        comparisonArrowData.push(_objdd);
      }
      let option = {
        backgroundColor: '#ffffff',
        grid: {
          top: 20,
          left: 20,
          right: 0,
          height: 176,
        },
        xAxis: [
          {
            show: false,
            min: 100,
            max: 200,
          }
        ],
        yAxis: [{
          show: false,
          inverse: true,
          data: [1, 2, 3, 4]
        }],
        series: [
          {
            top: 0,
            type: 'funnel',
            height: '210',
            gap: 10,
            minSize: 150,
            left: '10%',
            width: '30%',
            itemStyle: {
              color: '#0375DE'
            },
            z: 3,
            label: {
              show: true,
              position: 'inside',
              fontSize: '12',
              formatter: (d) => {
                var ins = d.name + '{aa|} ' + this.$options.filters['formatNum'](d.data.num);
                return ins
              },
              rich: {
                aa: {
                  padding: [8, 0, 6, 0]
                }
              }
            },
            data: funnelData
          },
          {
            top: 0,
            type: 'funnel',
            height: '210',
            gap: 10,
            minSize: 150,
            left: '60%',
            width: '30%',
            z: 3,
            itemStyle: {
              color: '#FA8C16'
            },
            label: {
              show: true,
              position: 'inside',
              fontSize: '12',
              formatter: (d) => {
                var ins = d.name + '{aa|} ' + this.$options.filters['formatNum'](d.data.num);
                return ins
              },
              rich: {
                aa: {
                  padding: [8, 0, 6, 0]
                }
              }
            },
            data: comparisonFunnelData
          },
          {
            top: '0',
            name: 'youcejiantou',
            type: 'pictorialBar',
            symbolPosition: 'center',
            symbolSize: ['200', '24'],
            symbol: rightArrow,
            symbolClip: true,
            z: 1,
            data: arrowData
          },
          {
            top: '0',
            name: 'youcejiantou',
            type: 'pictorialBar',
            symbolPosition: 'center',
            symbolSize: ['200', '24'],
            symbol: rightArrow,
            symbolClip: true,
            z: 1,
            data: comparisonArrowData
          },
        ]
      };

      this.buyTransChart.setOption(option, true)
    },

    // 获取搜索关键词对比表格数据
    getKeywordTableCompariso() {
      this.keywordSearchDistributeLoading = true
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
        language: this.language,
        storeId: this.shopIds,
        name: this.searchKeyword.replace(/(^\s*)|(\s*$)/g, ""), // 搜索关键词去除左右空格
        page: this.keywordSearchDistributeTablePage,
        pageSize: 5,
        sortWord: this.keywordSearchDistributeTableSortWord,
        sordRule: this.keywordSearchDistributeTableSortRule
      }
      this.queryPost('/gpUserAnalysis/keywordSearchDistributeComparisonInfo', postData).then(res => {
        this.keywordSearchDistributeLoading = false;
        console.log(res.data);
        if (res.data.success) {
          this.keywordSearchDistributeData = res.data
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 对比表格数据排序
    keywordSearchComparisoTableSort({
      column,
      prop,
      order
    }) {
      this.keywordSearchDistributeTableSortWord = prop
      this.keywordSearchDistributeTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.getKeywordTableCompariso()
    },

    // 购买转化漏斗,数据获取
    getBuyTransData() {
      this.buyTransChart.clear();
      this.buyTransChart.showLoading({
        text: ''
      })

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        system: this.system,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        language: this.language,
        dateLabel: this.dateLabel,
        storeId: this.shopIds,
        chartLabel: this.chartLabel,
      }
      this.queryPost('/gpUserAnalysis/purchaseFunnel', postData).then(res => {
        if (res.data.success) {
          this.buyTransData = res.data.datas
          this.initBuyTransChart(res.data.datas)
        } else {
          this.$message.error(res.data.msg)
        }
        this.buyTransChart.hideLoading()
      }).catch(err => {
        console.log(err)
      });
    },

    // 购买转化漏斗,图表配置
    initBuyTransChart(resData) {
      let { chartData, chartPercent } = resData;
      var rightArrow = "image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMEAAAAaCAYAAAF5vAjmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAwaADAAQAAAABAAAAGgAAAAAJ2JVxAAABUklEQVR4Ae2ZQQqDMBBFG28hXqVdteeqSM+q3kKbCFk4m4AmDmaeUGS0mcm8z7dpdA9/jOM4hHOJo+u6vkTeXc7QQLO7UihwJVHFOV9SJBa75xlEKd0us0RqImfvX+Lrs5NMjXfhC1c8O1IT4T4EIGCUAL+aRoXP1nY1q4psRJQSVbEqUmKXrSwiZEN5PBEiHGeXbSQiZEN5PNH2J3Oapte6rp/jaRgJgRsSmOe55zGkLBwCIIAyAeXyOAABlAkol8cBCKBMQLk8DkAAZQLK5XGAsgC86tMV4LttxsU5+E25p3PuHWPOEKiZgN+A7v3ntzNBzQ3TGwQkgbAhvSxLw1pIkiE2RwATmJOchiUBTCCJEJsjgAnMSU7DkgAmkESIzRHABOYkp2FJABNIIsTmCGACc5LTsCSACSQRYnMEMIE5yWlYEnDh1bG8SAwBKwTath3+D9M/NovemzEAAAAASUVORK5CYII=";
      var markLineSetting = {
        show: true,
        backgroundColor: '#f4f4f4',
        position: 'inside',
        color: 'rgba(0, 0, 0, 0.85)',
        width: 52,
        height: 20,
        lineHeight: 20,
        offset: [135, 0],
        align: 'center',
        fontSize: 13,
        formatter: function (d) {
          if (d.value) {
            var ins = '{words|' + d.data.itemValue + '}';
            return ins
          }
        },
        rich: {
          words: {
            color: '#333',
            fontSize: 13,
            lineHeight: 20,
          }
        }
      };
      var data1 = [];
      for (var i = 0; i < chartData.length; i++) {
        var obj1 = {
          value: (100 / chartData.length) * (chartData.length - i),
          num: chartData[i].value,
          name: chartData[i].name
        };
        data1.push(obj1);
      }
      var arrowData = []
      for (var i = 0; i < chartPercent.length; i++) {
        var _objdd = {
          value: 277,
          itemValue: chartPercent[i].value + '%',
          label: markLineSetting,
        }
        arrowData.push(_objdd);
      }
      let option = {
        backgroundColor: '#ffffff',
        grid: {
          top: 20,
          left: 20,
          right: 0,
          height: 176,
        },
        xAxis: [
          {
            show: false,
            min: 0,
            max: 200,
          }
        ],
        yAxis: [{
          show: false,
          inverse: true,
          data: [1, 2, 3, 4]
        }],
        series: [
          {
            top: 0,
            type: 'funnel',
            height: '210',
            gap: 10,
            minSize: 150,
            left: '20%',
            width: '60%',
            itemStyle: {
              color: '#0375DE'
            },
            label: {
              show: true,
              position: 'inside',
              fontSize: '12',
              formatter: (d) => {
                var ins = d.name + '{aa|} ' + this.$options.filters['formatNum'](d.data.num);
                return ins
              },
              rich: {
                aa: {
                  padding: [8, 0, 6, 0]
                }
              }
            },
            data: data1
          },
          {
            top: '0',
            name: 'youcejiantou',
            type: 'pictorialBar',
            symbolPosition: 'center',
            symbolSize: ['275', '24'],
            symbol: rightArrow,
            symbolClip: true,
            z: 1,
            data: arrowData
          },
        ]
      };

      this.buyTransChart.setOption(option, true)
    },

    // 用户分组统计,对比数据获取
    getUserGroupCompareData() {
      this.userGroupTableLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
        language: this.language,
        storeId: this.shopIds,
        page: this.userGroupTablePage,
        pageSize: 6,
        sortWord: this.userGroupTableSortWord,
        sordRule: this.userGroupTableSortRule,
        queryType: this.userGroupTableType,
      }
      this.queryPost('/gpUserAnalysis/userGroupCompare', postData).then(res => {
        if (res.data.success) {
          this.userGroupData = res.data
          this.userGroupTableLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 用户分组统计,数据获取
    getUserGroupData() {
      this.userGroupTableLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
        language: this.language,
        storeId: this.shopIds,
        page: this.userGroupTablePage,
        pageSize: 6,
        sortWord: this.userGroupTableSortWord,
        sordRule: this.userGroupTableSortRule,
        queryType: this.userGroupTableType,
      }
      this.queryPost('/gpUserAnalysis/userGroup', postData).then(res => {
        if (res.data.success) {
          this.userGroupData = res.data
          this.userGroupTableLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 用户分组统计,表格翻页
    userGroupTablePageChange(value) {
      this.userGroupTablePage = value
      if (this.buyCompareShow) {
        this.getUserGroupData()
      } else {
        this.getUserGroupCompareData();
      }
    },
    // 用户分组统计,表格数据排序
    userGroupTableSort({
      column,
      prop,
      order
    }) {
      this.userGroupTableSortWord = prop
      this.userGroupTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      if (this.buyCompareShow) {
        this.getUserGroupData()
      } else {
        this.getUserGroupCompareData();
      }
    },

    // 用户分组统计,重置表格参数
    userGroupTableReset(type = '1027') {
      this.userGroupTableType = type
      this.userGroupTablePage = 1
      this.userGroupTableSortRule = 'desc'
      if (this.buyCompareShow) {
        this.userGroupTableSortWord = 'visitwebsite'
        this.$refs.userGroupTable.sort('visitwebsite', 'descending')
      } else {
        this.getUserGroupCompareData();
      }
    },

    // 用户分组统计,鼠标悬浮“浏览商品”列显示小手
    userGroupTableCellStyle({ row, column, rowIndex, columnIndex }) {
      if (column.property === 'browsegoods' || column.property === 'look_spus' || column.property === 'pre_look_spu') {
        return 'cursor: pointer; color: #0375DE;'
      }
    },
    // 用户分组统计,点击“浏览商品”列的某一格
    showUserGroupDialog(row, column, cell, event) {
      if (column.property === 'browsegoods' || column.property === 'look_spus') {
        this.userGroupTableSelectedRow = row

        this.userGroupDialogVisible = true

        this.goodsAnalysisTablePage = 1
        this.goodsAnalysisTableSortWord = 'pageview'
        this.goodsAnalysisTableSortRule = 'desc'

        this.getGoodsAnalysisData()
      } else if (column.property === 'pre_look_spu') {
        this.userGroupTableSelectedRow = row

        this.userGroupDialogVisible = true

        this.goodsAnalysisTablePage = 1
        this.goodsAnalysisTableSortWord = 'pageview'
        this.goodsAnalysisTableSortRule = 'desc'

        this.getGoodsAnalysisData('/gpUserAnalysis/goodsAnalysisComparison')
      }
    },
    // 用户分组统计弹框——商品分析,数据获取
    getGoodsAnalysisData(url) {
      this.goodsAnalysisTableLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
        language: this.language,
        storeId: this.shopIds,
        page: this.goodsAnalysisTablePage,
        pageSize: 6,
        queryType: this.userGroupTableType,
        code: this.userGroupTableSelectedRow.code,
        sortWord: this.goodsAnalysisTableSortWord,
        sordRule: this.goodsAnalysisTableSortRule,
      }
      this.queryPost(url ? url : '/gpUserAnalysis/goodsAnalysis', postData).then(res => {
        if (res.data.success) {
          this.goodsAnalysisData = res.data
          this.goodsAnalysisTableLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 用户分组统计弹框——商品分析,表格数据排序
    goodsAnalysisTableSort({
      column,
      prop,
      order
    }) {
      this.goodsAnalysisTableSortWord = prop
      this.goodsAnalysisTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.getGoodsAnalysisData()
    },
    // 用户分组统计弹框——商品分析,表格翻页
    goodsAnalysisTablePageChange(value) {
      this.goodsAnalysisTablePage = value
      this.getGoodsAnalysisData()
    },

    // 搜索趋势,数据获取
    getSearchTrendData() {
      this.keywordSearchTransferLineChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        countryCode: this.countryCode,
        name: this.keyword,
        siteCode: this.siteCode,
        system: this.system,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        language: this.language,
        dateLabel: this.searchTrendDateLabel,
        storeId: this.shopIds,
        dataType: this.searchTrendChartType
      }
      this.queryPost('/gpUserAnalysis/searchCurrentTrendLineChart', postData).then(res => {
        this.keywordSearchTransferLineChart.hideLoading()
        if (res.data.success) {
          this.initSearchTrendChart(res.data.datas.data)
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 搜索趋势,图表配置
    initSearchTrendChart(datas) {
      let series = []
      switch (this.searchTrendChartType) {
        case 'all_rate':
          series = [
            {
              type: 'line',
              name: this.$lang('整体转化率'),
              seriesLayoutBy: 'row',
              encode: {
                x: 0,
                y: 2
              },
            }
          ]
          break;
        case 'searchs':
          series = [
            {
              type: 'line',
              name: this.$lang('搜索量'),
              seriesLayoutBy: 'row',
              encode: {
                x: 0,
                y: 2
              },
            }
          ]
          break;
        case 'search_per':
          series = [
            {
              type: 'line',
              name: this.$lang('人均搜索量'),
              seriesLayoutBy: 'row',
              encode: {
                x: 0,
                y: 2
              },
            }
          ]
          break;

        default:
          break;
      }
      let lineOption = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
          icon: 'circle',
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          formatter: (params) => {
            let contentArr = [];
            const param = params[0]
            let title = param.name;
            if (this.searchTrendDateLabel !== 'D') {
              title = param.value[1]
            }
            const value = param.value[param.encode.y[0]]
            let formatValue = this.searchTrendChartType === 'all_rate' ? this.$options.filters['formatNum'](value, true) : this.$options.filters['formatNum'](value)
            const contentItem = `
                  <div class="item">
                    ${param.marker}${param.seriesName}<span class="value">${formatValue}</span>
                  </div>`
            contentArr.push(contentItem)

            return GlobalConstAndFunc.Echarts.tooltipFormatter(contentArr, title)
          }
        },
        grid: GlobalConstAndFunc.Echarts.gridSetStyle,
        dataset: {
          source: datas,
          sourceHeader: false
        },
        color: ['#0375DE', '#13C2C2', '#FFC30F', '#FA8C16', '#F14E22'],
        xAxis: {
          ...GlobalConstAndFunc.Echarts.xAxisStyle,
          type: 'category',
        },
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          type: 'value',
          axisLabel: {
            formatter: (value) => {
              return (this.searchTrendChartType === 'all_rate' ? value.toFixed(2) + '%' : value)
            }
          }
        },
        series
      }
      this.keywordSearchTransferLineChart.setOption(lineOption, true)
    },
    // 搜索趋势，图表类型修改
    searchTrendTypeChanged(val) {
      this.searchTrendChartType = val
      this.keywordCompareShow ? this.getSearchTrendData() : this.getKeywordLineComparison()
    },
    // 搜索趋势模块对比
    keywordComparison() {
      this.keywordCompareShow = false;
      this.keyword = '';
      this.getKeywordLineComparison();
      this.getKeyworFuunelComparison();
      this.getSearchTrendGatherData();
      this.keywordSearchDistributeTableSortRule = 'desc'
      this.keywordSearchDistributeTableSortWord = 'searchs'
      this.keywordSearchDistributeTablePage = 1
      // this.$refs.keywordSearchDistributeTable2.sort('searchs', 'descending');
      this.getKeywordTableCompariso()
    },
    keywordBack() {
      this.keywordCompareShow = true;
      this.keyword = '';
      this.getSearchTrendData();
      this.getKeywordSearchTransferData();
      this.getSearchTrendGatherData();
      this.keywordSearchDistributeTableSortRule = 'desc'
      this.keywordSearchDistributeTableSortWord = 'keyword_account'
      this.keywordSearchDistributeTablePage = 1
      this.$refs.keywordSearchDistributeTable.sort('keyword_account', 'descending')
      // this.getKeywordSearchDistributeData();
    },
    // 搜索趋势, 日期粒度更改
    searchTrendDateLabelChanged() {
      this.keywordCompareShow ? this.getSearchTrendData() : this.getKeywordLineComparison()
    },
    // 搜索趋势，对比数据获取
    getKeywordLineComparison() {
      this.keywordSearchTransferLineChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        name: this.keyword,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.searchTrendDateLabel,
        dateType: this.dateTime || null,
        language: this.language,
        storeId: this.shopIds,
        dataType: this.searchTrendChartType
      }
      this.queryPost('/gpUserAnalysis/searchComparisonTrendLineChart', postData).then(res => {
        this.keywordSearchTransferLineChart.hideLoading();
        if (res.data.success) {
          this.initKeywordComparisonChart(res.data.datas);
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 搜索趋势，对比图表配置
    initKeywordComparisonChart(datas) {
      let series = []
      switch (this.searchTrendChartType) {
        case 'all_rate':
          series = [
            {
              type: 'line',
              name: this.$lang('上期整体转化率'),
              seriesLayoutBy: 'row',
              xAxisIndex: 0,
              lineStyle: { width: 1, type: 'dashed' },
              datasetIndex: 0,
              encode: {
                x: 0,
                y: 2
              }
            },
            {
              type: 'line',
              name: this.$lang('本期整体转化率'),
              seriesLayoutBy: 'row',
              xAxisIndex: 1,
              datasetIndex: 1,
              encode: {
                x: 0,
                y: 2
              }
            }
          ]
          break;
        case 'searchs':
          series = [
            {
              type: 'line',
              name: this.$lang('上期搜索量'),
              seriesLayoutBy: 'row',
              xAxisIndex: 0,
              lineStyle: { width: 1, type: 'dashed' },
              datasetIndex: 0,
              encode: {
                x: 0,
                y: 2
              }
            },
            {
              type: 'line',
              name: this.$lang('本期搜索量'),
              seriesLayoutBy: 'row',
              xAxisIndex: 1,
              datasetIndex: 1,
              encode: {
                x: 0,
                y: 2
              }
            }
          ]
          break;
        case 'search_per':
          series = [
            {
              type: 'line',
              name: this.$lang('上期人均搜索量'),
              seriesLayoutBy: 'row',
              xAxisIndex: 0,
              lineStyle: { width: 1, type: 'dashed' },
              datasetIndex: 0,
              encode: {
                x: 0,
                y: 2
              }
            },
            {
              type: 'line',
              name: this.$lang('本期人均搜索量'),
              seriesLayoutBy: 'row',
              xAxisIndex: 1,
              datasetIndex: 1,
              encode: {
                x: 0,
                y: 2
              }
            }
          ]
          break;

        default:
          break;
      }

      let option = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
          selectedMode: false,
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          formatter: (params) => {
            let str = '';
            for (let index = 0; index < params.length; index++) {
              const param = params[index]
              const value = param.value[param.encode.y[0]]
              let formatValue = this.searchTrendChartType === 'all_rate' ? this.$options.filters['formatNum'](value, true) : this.$options.filters['formatNum'](value)
              let title = '', seriesName = ''
              title = (this.searchTrendDateLabel !== 'D') ? param.value[1] : param.value[0]
              seriesName = param.seriesName
              str += `<div class="title">${title}</div>
                      <section class="content-lt12" style="margin-bottom: 10px">
                        <div class="item">
                          ${param.marker}${seriesName}<span class="value">${formatValue}</span>
                        </div>
                      </section>`
            }
            return `<section class="echarts-tooltip">
                      ${str}
                    </section>`
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          right: 40
        },
        dataset: [{
          source: datas.preData,
          sourceHeader: false
        },
        {
          source: datas.data,
          sourceHeader: false
        }],
        color: GlobalConstAndFunc.Echarts.colorList(2),
        xAxis: [
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            show: false,
            position: 'bottom',
            boundaryGap: false
          },
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            position: 'bottom',
            boundaryGap: false
          }
        ],
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          type: 'value',
          axisLabel: {
            formatter: (value) => {
              return (this.searchTrendChartType === 'all_rate' ? this.$options.filters['formatNum'](value, true) : this.$options.filters['formatNum'](value))
            }
          },
        },
        series
      }
      this.keywordSearchTransferLineChart.setOption(option, true)
    },
    // 获取搜索漏斗图对比趋势
    getKeyworFuunelComparison() {
      this.keywordSearchTransferChart.clear();
      this.keywordSearchTransferChart.showLoading({
        text: ''
      })
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        name: this.keyword,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateLabel: this.dateLabel,
        dateType: this.dateTime || null,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language
      }
      this.queryPost('/gpUserAnalysis/keywordSearchDistributeComparisonFunnel', postData).then(res => {
        this.keywordSearchTransferChart.hideLoading();
        console.log(res.data.datas);
        const info = res.data.datas;
        if (res.data.success) {
          const data = {
            chartData: info.current.chartData,
            chartPercent: info.current.chartPercent,
            comparisonChartData: info.comparison.chartData,
            comparisonChartPercent: info.comparison.chartPercent,
          }
          this.keywordSearchTransComparisonData = res.data.datas
          this.keywordSearchComparisonTransferDataOverall = info.comparison.overall
          this.keywordSearchTransferDataOverall = info.current.overall
          this.initKeywordFuunelComparisonChart(data)
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 搜索转化漏斗对比,图表配置
    initKeywordFuunelComparisonChart(resData) {
      let { chartData, chartPercent, comparisonChartData, comparisonChartPercent } = resData;
      var rightArrow = "image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMEAAAAaCAYAAAF5vAjmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAwaADAAQAAAABAAAAGgAAAAAJ2JVxAAABUklEQVR4Ae2ZQQqDMBBFG28hXqVdteeqSM+q3kKbCFk4m4AmDmaeUGS0mcm8z7dpdA9/jOM4hHOJo+u6vkTeXc7QQLO7UihwJVHFOV9SJBa75xlEKd0us0RqImfvX+Lrs5NMjXfhC1c8O1IT4T4EIGCUAL+aRoXP1nY1q4psRJQSVbEqUmKXrSwiZEN5PBEiHGeXbSQiZEN5PNH2J3Oapte6rp/jaRgJgRsSmOe55zGkLBwCIIAyAeXyOAABlAkol8cBCKBMQLk8DkAAZQLK5XGAsgC86tMV4LttxsU5+E25p3PuHWPOEKiZgN+A7v3ntzNBzQ3TGwQkgbAhvSxLw1pIkiE2RwATmJOchiUBTCCJEJsjgAnMSU7DkgAmkESIzRHABOYkp2FJABNIIsTmCGACc5LTsCSACSQRYnMEMIE5yWlYEnDh1bG8SAwBKwTath3+D9M/NovemzEAAAAASUVORK5CYII=";
      var markLineSetting = {
        show: true,
        backgroundColor: '#f4f4f4',
        position: 'inside',
        color: 'rgba(0, 0, 0, 0.85)',
        width: 52,
        height: 20,
        lineHeight: 20,
        offset: [100, 0],
        align: 'center',
        fontSize: 13,
        formatter: function (d) {
          if (d.value) {
            var ins = '{words|' + d.data.itemValue + '}';
            return ins
          }
        },
        rich: {
          words: {
            color: '#333',
            fontSize: 13,
            lineHeight: 20,
          }
        }
      };
      let funnelData = [];
      let comparisonFunnelData = []
      for (var i = 0; i < chartData.length; i++) {
        let obj1 = {
          value: (100 / chartData.length) * (chartData.length - i),
          num: chartData[i].value,
          name: chartData[i].name
        };
        funnelData.push(obj1);
      }
      for (var i = 0; i < comparisonChartData.length; i++) {
        let obj1 = {
          value: (100 / comparisonChartData.length) * (comparisonChartData.length - i),
          num: comparisonChartData[i].value,
          name: comparisonChartData[i].name
        };
        comparisonFunnelData.push(obj1);
      }
      let arrowData = []
      let comparisonArrowData = []
      for (var i = 0; i < chartPercent.length; i++) {
        var _objdd = {
          value: 270,
          itemValue: chartPercent[i].value + '%',
          label: markLineSetting,
        }
        arrowData.push(_objdd);
      }
      for (var i = 0; i < comparisonChartPercent.length; i++) {
        var _objdd = {
          value: 370,
          itemValue: comparisonChartPercent[i].value + '%',
          label: markLineSetting,
        }
        comparisonArrowData.push(_objdd);
      }
      let option = {
        backgroundColor: '#ffffff',
        grid: {
          top: 16,
          left: 20,
          right: '20',
          height: 220,
        },
        tooltip: {
          trigger: 'item',
          formatter: (param) => {
            if (param.dataIndex == 1) {
              return this.$lang('点击查看详情')
            }
          }
        },
        xAxis: [
          {
            show: false,
            min: 100,
            max: 200,
          }
        ],
        yAxis: [{
          show: false,
          inverse: true,
          data: [1, 2, 3, 4, 5]
        }],
        series: [
          {
            top: 0,
            type: 'funnel',
            height: '248',
            gap: 10,
            minSize: 150,
            left: '10%',
            width: '30%',
            z: 3,
            itemStyle: {
              color: '#0375DE'
            },
            label: {
              show: true,
              position: 'inside',
              fontSize: '12',
              formatter: (d) => {
                var ins = d.name + '{aa|} ' + this.$options.filters['formatNum'](d.data.num);
                return ins
              },
              rich: {
                aa: {
                  padding: [8, 0, 6, 0]
                }
              }
            },
            data: funnelData
          },
          {
            top: 0,
            type: 'funnel',
            height: '248',
            gap: 10,
            minSize: 150,
            left: '60%',
            width: '30%',
            z: 3,
            itemStyle: {
              color: '#FA8C16'
            },
            label: {
              show: true,
              position: 'inside',
              fontSize: '12',
              formatter: (d) => {
                var ins = d.name + '{aa|} ' + this.$options.filters['formatNum'](d.data.num);
                return ins
              },
              rich: {
                aa: {
                  padding: [8, 0, 6, 0]
                }
              }
            },
            data: comparisonFunnelData
          },
          {
            top: '0',
            name: 'youcejiantou',
            type: 'pictorialBar',
            symbolPosition: 'center',
            symbolSize: ['200', '24'],
            symbol: rightArrow,
            symbolClip: true,
            z: 1,
            data: arrowData
          },
          {
            top: '0',
            name: 'youcejiantou',
            type: 'pictorialBar',
            symbolPosition: 'center',
            symbolSize: ['200', '24'],
            symbol: rightArrow,
            symbolClip: true,
            z: 1,
            data: comparisonArrowData
          },
        ]
      };

      this.keywordSearchTransferChart.setOption(option, true)
    },
    doSearchKeyword() {
      this.keywordSearchDistributeTablePage = 1;
      if (this.keywordCompareShow) {
        this.getKeywordSearchDistributeData()
      } else {
        this.getKeywordTableCompariso();
      }
    },
    // 关键词搜索量统计,数据获取
    getKeywordSearchDistributeData() {
      this.keywordSearchDistributeLoading = true

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
        storeId: this.shopIds,
        language: this.language,
        name: this.searchKeyword.replace(/(^\s*)|(\s*$)/g, ""), // 搜索关键词去除左右空格
        page: this.keywordSearchDistributeTablePage,
        pageSize: 5,
        sortWord: this.keywordSearchDistributeTableSortWord,
        sordRule: this.keywordSearchDistributeTableSortRule
      }
      this.queryPost('/gpUserAnalysis/keywordSearchDistributeInfo', postData).then(res => {
        if (res.data.success) {
          this.keywordSearchDistributeData = res.data
          this.keywordSearchDistributeLoading = false
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 关键词搜索量统计,根据被点击的“漏斗分析”按钮设置改行的样式
    setRowClass({ row, rowIndex }) {
      if (this.keyword === row.name) {
        return 'current-row'
      }
    },
    // 关键词搜索量统计,设置表格中被点击的单元格的样式
    setCellClass({ row, column, rowIndex, columnIndex }) {
      row.index = rowIndex;
      column.index = columnIndex;
      if (this.keyword === row.name) {
        return 'keyword-search__operate--active'
      }
      return 'keyword-search__operate'
    },
    // 关键词搜索量统计,导出
    keywordSearchTableExport() {
      var param = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        language: this.language,
        name: this.searchKeyword.replace(/(^\s*)|(\s*$)/g, ""), // 搜索关键词去除左右空格
        dateLabel: this.dateLabel,
        storeId: this.shopIds,
        chartLabel: this.chartLabel,
        sortWord: this.keywordSearchDistributeTableSortWord,
        sordRule: this.keywordSearchDistributeTableSortRule
      }
      let url = '/gpUserAnalysis/keywordSearchDistributeImport';
      if (!this.keywordCompareShow) {
        url = '/gpUserAnalysis/keywordSearchDistributeComparisonImport'
      }
      axios.post(this.apiHost + url, Qs.stringify(param), {
        headers: {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        responseType: 'blob'
      }).then((res) => {
        let blob = res.data
        let reader = new FileReader()
        reader.readAsDataURL(blob)
        reader.onload = (e) => {
          let a = document.createElement('a')
          let fileName = `${this.keywordSearchDistributeData.datas.date} ${this.siteName}站搜索关键词统计.xlsx`
          if (this.language !== 'cn') {
            fileName = `${this.keywordSearchDistributeData.datas.date} keyword statistics from ${this.$lang(this.siteName)}.xlsx`
          }
          a.download = fileName
          a.href = e.target.result
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
        }
      }).catch(err => {
        console.log(err)
      });
    },
    // 关键词搜索量统计,表格翻页
    keywordSearchDistributeTablePageChange(value) {
      this.keywordSearchDistributeTablePage = value
      if (this.keywordCompareShow) {
        this.getKeywordSearchDistributeData();
      } else {
        this.getKeywordTableCompariso();
      }
    },
    // 关键词搜索量统计,表格数据排序
    keywordSearchTableSort({
      column,
      prop,
      order
    }) {
      this.keywordSearchDistributeTableSortWord = prop
      this.keywordSearchDistributeTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.getKeywordSearchDistributeData()
    },

    // 关键词搜索转化漏斗,切换
    changeKeywordSearchTransferChart(keyword) {
      if (this.keyword === keyword) {
        this.keyword = ''
      } else {
        this.keyword = keyword
      }
      if (this.keywordCompareShow) {
        this.getKeywordSearchTransferData();
        this.getSearchTrendData();
      } else {
        this.getKeyworFuunelComparison();
        this.getKeywordLineComparison();
      }
      this.getSearchTrendGatherData();
    },
    // 关键词搜索转化漏斗,数据获取
    getKeywordSearchTransferData() {
      this.keywordSearchTransferChart.clear();
      this.keywordSearchTransferChart.showLoading({
        text: ''
      })

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        name: this.keyword,
        channelName: this.channelName,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        system: this.system,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        language: this.language,
        dateLabel: this.dateLabel,
        storeId: this.shopIds,
        chartLabel: this.chartLabel,
      }
      this.queryPost('/gpUserAnalysis/keywordSearchDistributeFunnel', postData).then(res => {
        if (res.data.success) {
          this.keywordSearchTransferData = res.data.datas
          this.initKeywordSearchTransferChart(res.data.datas)
        } else {
          this.$message.error(res.data.msg)
        }
        this.keywordSearchTransferChart.hideLoading()
      }).catch(err => {
        console.log(err)
      });
    },

    // 关键词搜索转化漏斗,图表配置
    initKeywordSearchTransferChart(resData) {
      let { chartData, chartPercent } = resData;
      var rightArrow = "image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMEAAAAaCAYAAAF5vAjmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAwaADAAQAAAABAAAAGgAAAAAJ2JVxAAABUklEQVR4Ae2ZQQqDMBBFG28hXqVdteeqSM+q3kKbCFk4m4AmDmaeUGS0mcm8z7dpdA9/jOM4hHOJo+u6vkTeXc7QQLO7UihwJVHFOV9SJBa75xlEKd0us0RqImfvX+Lrs5NMjXfhC1c8O1IT4T4EIGCUAL+aRoXP1nY1q4psRJQSVbEqUmKXrSwiZEN5PBEiHGeXbSQiZEN5PNH2J3Oapte6rp/jaRgJgRsSmOe55zGkLBwCIIAyAeXyOAABlAkol8cBCKBMQLk8DkAAZQLK5XGAsgC86tMV4LttxsU5+E25p3PuHWPOEKiZgN+A7v3ntzNBzQ3TGwQkgbAhvSxLw1pIkiE2RwATmJOchiUBTCCJEJsjgAnMSU7DkgAmkESIzRHABOYkp2FJABNIIsTmCGACc5LTsCSACSQRYnMEMIE5yWlYEnDh1bG8SAwBKwTath3+D9M/NovemzEAAAAASUVORK5CYII=";
      var markLineSetting = {
        show: true,
        backgroundColor: '#f4f4f4',
        position: 'inside',
        color: 'rgba(0, 0, 0, 0.85)',
        width: 52,
        height: 20,
        lineHeight: 20,
        offset: [135, 0],
        align: 'center',
        fontSize: 13,
        formatter: function (d) {
          if (d.value) {
            var ins = '{words|' + d.data.itemValue + '}';
            return ins
          }
        },
        rich: {
          words: {
            color: '#333',
            fontSize: 13,
            lineHeight: 20,
          }
        }
      };
      let data1 = [];
      for (let i = 0; i < chartData.length; i++) {
        let obj1 = {
          value: (100 / chartData.length) * (chartData.length - i),
          num: chartData[i].value,
          name: chartData[i].name
        };
        data1.push(obj1);
      }
      let arrowData = []
      for (let i = 0; i < chartPercent.length; i++) {
        let _objdd = {
          value: 277,
          itemValue: chartPercent[i].value + '%',
          label: markLineSetting,
        }
        arrowData.push(_objdd);
      }
      let option = {
        backgroundColor: '#ffffff',
        grid: {
          top: 16,
          left: 20,
          right: 0,
          height: 220,
        },
        xAxis: [
          {
            show: false,
            min: 0,
            max: 200,
          }
        ],
        yAxis: [{
          show: false,
          inverse: true,
          data: [1, 2, 3, 4, 5]
        }],
        tooltip: {
          trigger: 'item',
          formatter: (param) => {
            if (param.dataIndex == 1) {
              return this.$lang('点击查看详情')
            }
          }
        },
        series: [
          {
            top: 0,
            type: 'funnel',
            height: '248',
            gap: 12,
            minSize: 150,
            left: '20%',
            width: '60%',
            itemStyle: {
              color: '#0375DE'
            },
            label: {
              show: true,
              position: 'inside',
              fontSize: '12',
              formatter: (d) => {
                var ins = d.name + '{aa|} ' + this.$options.filters['formatNum'](d.data.num);
                return ins
              },
              rich: {
                aa: {
                  padding: [8, 0, 6, 0]
                }
              }
            },
            data: data1
          },
          {
            top: '0',
            name: 'youcejiantou',
            type: 'pictorialBar',
            symbolPosition: 'center',
            symbolSize: ['275', '24'],
            symbol: rightArrow,
            symbolClip: true,
            z: 1,
            data: arrowData
          },
        ]
      };
      this.keywordSearchTransferChart.setOption(option, true)
    },
    // 关键词搜索转化漏斗,点击漏斗后搜索转化弹框，数据获取
    getKeywordSearchTransDialogData() {
      this.keywordSearchTransDialogTableLoading = true
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.dateTime || null,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        channelName: this.channelName,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        system: this.system,
        keyWord: this.keyword,
        storeId: this.shopIds,
        page: this.keywordSearchTransDialogPage,
        pageSize: 6,
        language: this.language,
        sortWord: this.keywordSearchTransDialogTableSortWord,
        sortRule: this.keywordSearchTransDialogTableSortRule,
        dateLabel: this.dateLabel,
        chartLabel: this.chartLabel,
      }
      this.queryPost(this.keywordSearchTransDialogTableUrl, postData).then(res => {
        if (res.data.success) {
          this.keywordSearchTransDialogData = res.data
        } else {
          this.$message.error(res.data.msg)
        }
        this.keywordSearchTransDialogTableLoading = false
      }).catch(err => {
        console.log(err)
      });
    },

    // 关键词搜索转化漏斗,点击漏斗后搜索转化弹框,表格中带tooltip的表头渲染
    keywordSearchTransDialogTableHeader(h, { column, $index }, isLastHeader = false) {
      let {
        width,
        realWidth,
        minWidth,
        label,
        sortable,
        property
      } = column
      // cellContentWidth 为当前表头单元格th的实际宽度(padding+width)
      const cellContentWidth = realWidth ? realWidth : width
      // cellWidth 为当前表头单元格th的实际宽度(width)
      let cellWidth
      // debugger
      // 根据UI规范，表头两侧要内缩进40px，所以两侧的th>div.cell分别在css中修改了相应方向padding的值为40,其余的th>div.cell的左右padding各10
      if ($index === 0 || isLastHeader) {
        cellWidth = cellContentWidth - 40 - 10
      } else {
        cellWidth = cellContentWidth - 10 - 10
      }

      const length = label.length
      // labelWidth为表头label完全展示需要的实际宽度。14是字体大小,如果支持排序，还要加上排序图标的宽度24,tooltip图标宽度14
      let labelWidth = sortable ? (14 * length + 24 + 14) : (14 * length + 14)

      let tooltipContent = ''
      switch (property) {
        case 'look_num':
          tooltipContent = this.$lang('指统计时间内，搜索关键词后，浏览该商品的有效用户数（按用户标识去重）')
          break;
        case 'pay_num':
          tooltipContent = this.$lang('指统计时间内，按商品统计包含该商品的订单数量')
          break;
        case 'add_cart_num':
          tooltipContent = this.$lang('指统计时间内，有商品加入购物车行为的有效用户数')
          break;
        case 'orders_uv':
          tooltipContent = this.$lang('指统计时间内，生成了包含某一商品的订单的用户数占该商品的浏览用户数的百分比')
          break;
        case 'pay_orders_uv':
          tooltipContent = this.$lang('指统计时间内，提交并支付了包含某一商品的订单的用户数占该商品的浏览用户数的百分比')
          break;

        default:
          break;
      }
      if (labelWidth > cellWidth) {
        // showLength 为实际可以展示几个字符，不包括省略号（占14px）和排序图标的宽度24,tooltip图标宽度14
        let showLength = sortable ? Math.floor((cellWidth - 14 - 24 - 14) / 14) : Math.floor((cellWidth - 14 - 14) / 14)
        const showLabel = label.slice(0, showLength) + '...'
        return h('div', {
          style: {
            display: 'inline-block'
          }
        }, [
          h('el-tooltip', {
            props: {
              content: label,
              placement: 'top'
            }
          }, [
            h('span', [showLabel])
          ]),
          h(ErpTooltip, {
            props: {
              content: tooltipContent
            }
          })
        ])
      } else {
        return h('div', {
          style: {
            display: 'inline-block'
          }
        }, [
          h('span', [label]),
          h(ErpTooltip, {
            props: {
              content: tooltipContent
            }
          })
        ])
      }
    },

    // 关键词搜索转化漏斗,点击漏斗后,搜索转化弹框,导出数据
    keywordSearchTransDialogTableExport() {
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.dateTime || null,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        channelName: this.channelName,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        system: this.system,
        keyWord: this.keyword,
        language: this.language,
        sortWord: this.keywordSearchTransDialogTableSortWord,
        sortRule: this.keywordSearchTransDialogTableSortRule,
        dateLabel: this.dateLabel,
        storeId: this.shopIds,
      }

      axios.post(this.apiHost + this.exportKeywordSearchTransDialogTableUrl, Qs.stringify(postData), {
        headers: {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        responseType: 'blob'
      }).then((res) => {
        let blob = res.data
        const fileName = res.headers['content-disposition'].match(/filename=(.*)/)[1]
        let reader = new FileReader()
        reader.readAsDataURL(blob)
        reader.onload = (e) => {
          let a = document.createElement('a')
          a.href = e.target.result
          a.download = decodeURIComponent(fileName)
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 关键词搜索转化漏斗,点击漏斗后,搜索转化弹框,表格数据排序
    keywordSearchTransDialogTableSort({
      column,
      prop,
      order
    }) {
      this.keywordSearchTransDialogTableSortWord = prop
      this.keywordSearchTransDialogTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.getKeywordSearchTransDialogData()
    },

    // 关键词搜索转化漏斗,点击漏斗后,搜索转化弹框,表格翻页
    keywordSearchTransDialogTablePageChange(value) {
      this.keywordSearchTransDialogPage = value
      this.getKeywordSearchTransDialogData()
    },

    // 用户行为跟踪模块数据获取
    getUserTrackMoudleData() {
      this.getUserTrackGather()
      this.getUserTrackChartData()
    },

    // 用户行为跟踪汇总数据获取
    getUserTrackGather() {
      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        storeId: this.shopIds,
        language: this.language,
        acrossNode: this.acrossNode,
        lengthwaysNode: this.lengthwaysNode
      }
      this.queryPost('/gpUserAnalysis/userBehaviorTraceGather', postData).then(res => {
        if (res.data.success) {
          this.userTrackGather = res.data.datas
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      });
    },

    // 用户行为跟踪图表数据获取
    getUserTrackChartData() {
      this.userTrackChart.showLoading({
        text: ''
      })

      let postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        channelName: this.channelName,
        system: this.system,
        countryCode: this.countryCode,
        siteCode: this.siteCode,
        startDate: this.formatRangeDate(1),
        endDate: this.formatRangeDate(2),
        dateType: this.dateTime || null,
        dateLabel: this.dateLabel,
        storeId: this.shopIds,
        language: this.language,
        acrossNode: this.acrossNode,
        lengthwaysNode: this.lengthwaysNode
      }
      this.queryPost('/gpUserAnalysis/userBehaviorTrace', postData).then(res => {
        if (res.data.success) {
          this.userTrackChartData = res.data.datas
          this.initUserTrackChart(res.data.datas)
        } else {
          this.$message.error(res.data.msg)
        }
        this.userTrackChart.hideLoading()
      }).catch(err => {
        console.log(err)
      });
    },

    // 用户行为跟踪图表配置
    initUserTrackChart({ data, links }) {
      data.forEach(item => {
        if (item.sessionType === 'current') {
          item.itemStyle = {
            color: {
              type: 'linear',
              x: 0,
              y: 0,
              x2: 0,
              y2: 1,
              colorStops: [{
                offset: 1 - vm.percentToPoint(item.outPercent), color: 'rgba(3, 117, 222, 1)'
              }, {
                offset: 1 - vm.percentToPoint(item.outPercent), color: 'rgba(255, 195, 15, 1)'
              }],
            }
          }
        } else if (item.sessionType === 'out') {
          item.itemStyle = {
            opacity: 0
          }
        }
      })
      links.forEach(item => {
        if (item.sessionType === 'out') {
          item.lineStyle = {
            opacity: 0
          }
          item.emphasis = {
            lineStyle: {
              opacity: 0
            }
          }
        }
      })
      let option = {
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: (params) => {
            let { value, name, data, dataType } = params
            if (dataType === 'node') {
              if (data.sessionType === 'out') return ''
              const formatSessionNum = this.$options.filters['formatNum'](data.sessionNum)
              const formatSessionOut = this.$options.filters['formatNum'](data.sessionOut)
              return `\u3010${data.pageName}\u3011<br />${data.pageUrl}<br />${this.$lang('会话数')}：${formatSessionNum}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;${this.$lang('跳出会话')}：${formatSessionOut}(${data.outPercent})`
            } else {
              if (data.sessionType === 'out') return ''
              const valueFormatted = this.$options.filters['formatNum'](value)
              let { source, target } = data
              source = source.split('_').pop()
              target = target.split('_').pop()
              return `【${source}】到【${target}】<br />${this.$lang('会话数')}：${valueFormatted}`
            }
          }
        },
        color: GlobalConstAndFunc.UI.colors9To16,
        series: [
          {
            type: 'sankey',
            left: 20,
            right: 20,
            top: 0,
            bottom: 10,
            nodeWidth: this.infoItemWidth,
            nodeGap: 12,
            nodeAlign: 'left',
            draggable: false,
            focusNodeAdjacency: false,
            layoutIterations: 0,
            label: {
              position: 'insideTopLeft',
              // color: 'fff',
              formatter: ({ data }) => {
                let pageUrl = data.pageUrl
                if (pageUrl.length > 10) {
                  pageUrl = pageUrl.slice(0, 10) + '...'
                }
                return `{b|【${data.pageName}】}\n{a|${pageUrl}}`
              },
              rich: {
                a: {
                  padding: [4, 0],
                  color: 'rgba(255, 255, 255, 0.65)',
                  fontWeight: 400
                },
                b: {
                  color: '#fff'
                }
              }
            },
            lineStyle: {
              color: '#0375DE',
              opacity: 0.1
            },
            itemStyle: {
              borderWidth: 0
            },
            data,
            links,
          }
        ]
      }
      this.userTrackChart.setOption(option, true)
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
});
