var vm = new Vue({
  el: '#vm',
  components: {
    'chart-wrapper': ChartWrapper,
    'erp-tooltip': ErpTooltip
  },
  filters: {
    /**
     * 字符串超出部分用省略号表示
     *
     * @param {String} str 传入的字符串
     * @param {number} length 字符串的最多展示多少
     * @return 原字符串或截断后用省略号代替的字符串
     */
    strOmit(str, length = 12) {
      return str.length > length ? str.slice(0, length) + '...' : str
    },

    /**
      * 数值千分位表示，考虑小数情况
      * 小数位为0不展示
      * '-'不做处理
      *
      * @param number 数值(Number或者String)
      * @param {boolean} addSymbol 返回的结果是否加上%(boolean)
      * @param {boolean} dot2 返回的结果中，小数是否统一保留两位
      * @return 金额格式的字符串,如'1,234,567.45'
      */
    formatNum(number, addSymbol, dot2) {
      let hasMinus = false
      if (number === '-') {
        return number
      } else if (number != null) {
        let numStr = dot2 ? parseFloat(number).toFixed(2) : parseFloat(number).toString()
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
          result = result == 0 ? result.join('') : result.join('').concat('%')
        } else {
          result = result.join('')
        }

        if (hasMinus) {
          result = '-' + result
        }

        return result
      }
    },

    // 添加币种符号
    addCurrency(str) {
      if (str === '-' || str == null) return
      return `$ ${str}`
    },

    // 根据code获取名称
    codeGetName(store_id) {
      let obj = vm.storeData.find(item => {
        return store_id == item.store_id
      });
      return obj.store_name
    },
  },
  data: {
    apiHost: GlobalConstAndFunc.api(),
    zoom: 1,
    shows: false,
    scrollTop: 0,
    locationOrigin: location.origin,
    language: 'cn',
    tableSortOrders: ['ascending', 'descending'],
    queryTime: '30D',
    queryTimeOptions: [
      {
        name: '近30日',
        value: '30D'
      },
      {
        name: '近60日',
        value: '60D'
      },
      {
        name: '近90日',
        value: '90D'
      },
      {
        name: '近180日',
        value: '180D'
      },
    ],
    rangeDate: '',
    leaderData: [],
    operationData: [],
    siteData: [],
    storeData:[],
    // querySitesLoading: true,
    allSiteChecked: true,
    invert: false,
    invertDisabled: true,
    leaderChecked: [],
    operationChecked: [],
    sitesChecked: [],
    sitesCheckedData: [],
    storeChecked: [],
    storeCheckedData: [],
    indexCardData: {},
    indexCardLoading: true,
    saleIsOdm: '0',
    saleTrendData: {},
    saleTrendLoading: true,
    saleTrendType: '1',
    saleTrendBySite: '1',
    saleTrendTimeGranularity: 'D',
    saleTrendChart: null,
    saleTrendTypeOptions: [
      {
        label: '订单',
        value: '1'
      },
      {
        label: '销售额',
        value: '2'
      },
      {
        label: '毛利',
        value: '3'
      },
      {
        label: '毛利率',
        value: '4'
      },
    ],
    timeGranularities: [
      {
        label: '按日',
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
    siteDistributeData: {},
    siteDistributeLoading: true,
    siteDistributeType: '1',
    isDistributeType: true,
    siteDistributeTableShow: false,
    siteDistributeChart: null,
    siteDistributeTableData: {
      datas: {}
    },
    siteDistributeTableSortWord: 'order_num',
    siteDistributeTableSortRule: 'desc',
    siteDistributeTablePage: 1,
    saleRankingsData: {},
    saleRankingsLoading: true,
    saleRankingsType: 'goods',
    saleRankingsChart: null,
    saleComparedShow:true,
    transferComparedShow:true,
    buyTransData: {},
    storeSalesData: {},
    storeVisitorData: {},
    storeSalesType: '1',
    storeSalesLoading: false,
    storeVisitorLoading: false,
    searchTransData: {},
    storeVisitorChart: null,
    storeSalesChart: null,
    buyTransChart: null,
    searchTransChart: null,
    transferIndexData: {},
    transferIndexLoading: true,
    transferTrendData: {},
    transferTrendLoading: true,
    transferTrendType: '4',
    transferTrendTimeGranularity: 'D',
    transferTrendChart: null,
    transferTrendTypeOptions: [
      {
        label: '访问用户',
        value: '4'
      },
      {
        label: '购买转化率',
        value: '3'
      },
      {
        label: '关键词搜索量',
        value: '2'
      },
      {
        label: '关键词搜索转化率',
        value: '1'
      },
    ],
    pickerOptions: {
        disabledDate(time) {
          return time.getTime() > Date.now() - 8.64e7;
        }
    }
  },
  computed: {
    // 各图表模块中展示的日期范围说明
    rangeDateInfo() {
      let str = ''
      switch (this.queryTime) {
        case '30D':
          str = '近30日'
          break;
        case '60D':
          str = '近60日'
          break
        case '90D':
          str = '近90日'
          break
        case '180D':
          str = '近180日'
          break
        default:
          break;
      }
      return str
    },
  },
  watch: {
    // 监听站点筛选条件中，“全部”状态
    // allSiteChecked(newVal) {
    //   if (newVal) {
    //     let arr = []
    //     this.invert = false
    //     this.invertDisabled = true
    //     for (const item of this.siteData) {
    //       item.checked = false
    //       arr.push(item.code)
    //     }
    //     this.sitesChecked = arr.join()
    //     this.getAllChartDataDebounced()
    //   } else {
    //     this.invert = false
    //     this.invertDisabled = false
    //   }
    // },
  },
  created: function () {
    if (getCookie('think_language') !== "zh-cn") {
      ELEMENT.locale(ELEMENT.lang.en)
      this.language = 'en'
    };
    this.getAllChartDataDebounced = _.debounce(this.getAllChartData, 700)
  },
  mounted: function () {
    this.getStoreData()
    this.buyTransChart = echarts.init(this.$refs.buyTransChart.$refs.chart)
    this.searchTransChart = echarts.init(this.$refs.searchTransChart.$refs.chart)
    window.addEventListener("scroll",this.handleScroll); 
  },
  methods: {
    setZoom(type){
      if(type == 'add') {
        if(this.zoom >= 99) {
          this.zoom = 99;
          return false
        }
        this.zoom += 0.9;
      } else if(type == 'minus') {
        if(this.zoom <= 1) {
          this.zoom = 1;
          return false;
        }
        this.zoom -= 0.9;
      } else {
        this.zoom = 1;
      }
      
      this.siteDistributeChartInit(this.siteDistributeData.info)
    },
    // 监听页面所有点击事件，除阻止冒泡事件  触发关闭选择站点弹窗
    onWatchClick() {
      if(this.shows) {
        this.shows = false;
      }
    },
    visibleChange(type) {
      console.log(type);
      if(type && this.shows) {
        this.shows = false;
      }
    },
    // 监听页面滚动
    handleScroll() {
      this.scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
      if(this.scrollTop > 100) {
        this.shows = false;
      }
      // console.log(this.scrollTop)
    },
    // axios 封装
    queryPost(url, params, cb) {
      url = this.apiHost + '/gpKPIDashboard/' + url
      const headers = {
        headers: {
          'erp-req': true,
          'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
          // 'erp-cookie': 'PHPSESSID=3b0irvsjq8j4gg2pgv9fuvtlr4;',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        }
      }

      axios.post(url, Qs.stringify(params), headers).then(res => {
        if (res.data.success === true) {
          cb(res)
        } else {
          this.$message.error(res.data.msg)
        }
      }).catch(err => {
        console.log(err)
      })
    },
    renderTableHeader(h, { column, $index }, isLastHeader) {
      return GlobalConstAndFunc.Element.renderTableHeader(h, { column, $index }, isLastHeader)
    },
    // 环比升降计算，返回相应图标
    qoqChange(value) {
      if (value == 0 || value === '-' || value == null) {
        return ''
      } else if (value > 0) {
        return 'el-icon-caret-top'
      } else {
        return 'el-icon-caret-bottom'
      }
    },
    // 所有图表数据获取
    getAllChartData() {
      this.getIndexCardData()

      this.getSiteData()

      this.saleComparedShow = true
      this.transferComparedShow = true

      this.saleIsOdm = '0'
      this.saleTrendBySite = '1'
      this.saleTrendTimeGranularity = 'D'
      this.saleTrendType = '1'
      this.getSaleTrendData()

      this.siteDistributeType = '1'
      this.siteDistributeTableShow = false
      this.getSiteDistributeData()

      this.saleRankingsType = 'goods'
      this.saleRankingsDataGet()
      this.transferIndexDataGet()

      this.transferTrendType = '4'
      this.transferTrendTimeGranularity = 'D'
      this.transferTrendDataGet()

      this.getBuyConversionRatesTrendData()
      this.getSearchConversionRatesTrendData()

			this.storeSalesDataGet()
			this.storeVisitorDataGet()

    },
    // 根据接口要求的格式，返回日期范围的值。type为1返回开始日期，为2则是结束日期
    formatRangeDate(type) {
      if (Array.isArray(this.rangeDate)) {
        return type === 1 ? this.rangeDate[0] : this.rangeDate[1]
      } else {
        return ''
      }
    },
     // 站点筛选条件改变
    querySiteChange() {
      this.getAllChartDataDebounced()
    },
    // 时间筛选条件改变
    queryTimeChanged() {
      this.rangeDate = ''
      this.getAllChartDataDebounced()
    },
    // 时间筛选条件,日期选择器改变
    rangeDateChanged(value) {
      if (value) {
        this.queryTime = ''
      } else {
        this.queryTime = '30D'
      }
      this.getAllChartDataDebounced()
    },
    reset(){
      this.storeChecked = [],
      this.storeCheckedData = [],
      this.sitesChecked = []
      this.leaderChecked = []
      this.operationChecked = []
      this.rangeDate = ''
      this.queryTime= '30D',
      this.getStoreData()
      // this.getAllChartDataDebounced()
    },
    getSiteData(){
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        language: this.language,
      }
      this.queryPost('countryInfo', postData, (res) => {
        this.siteData = res.data.datas
      })
    },
    // 获取店铺数据
    getStoreData(type) {
      if(type == 'leader' || type == 'operation'){
        this.storeChecked = [];
      }
      // this.querySitesLoading = true
      // console.log('storeChecked',this.storeChecked);
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        language: this.language,
        // storeId: '',
        // storeId: this.storeCheckedData,
        storeId: this.storeChecked.join(","),
        leaderId:this.leaderChecked.join(","),
        operatorId:this.operationChecked.join(","),
      }
      this.queryPost('storeInfo', postData, (res) => {

        if(type == 'leader'){
          this.operationData = res.data.datas.operation
          this.storeData = res.data.datas.store
          this.storeChecked = [];
        }else if(type == 'operation'){
          this.storeData = res.data.datas.store
          this.storeChecked = [];
        } else if(type == 'store') {

        } else{
          this.operationData = res.data.datas.operation
          this.leaderData = res.data.datas.leader
          this.storeData = res.data.datas.store
        }

        let storeDataArr = []
        if(type && this.storeChecked.length == 0 && (this.leaderChecked.length != 0 || this.operationChecked.length != 0)){
          let storeData = this.storeData
          for (const item in storeData) {
            storeDataArr.push(storeData[item].store_id)
          }
        }else{
          storeDataArr = this.storeChecked
        }
        console.log(storeDataArr)
        this.storeCheckedData = storeDataArr.join(","),
        
        // this.siteData = res.data.datas.map((item) => {
        //   item.checked = false
        //   return item
        // })
        // // this.querySitesLoading = false
        this.getAllChartDataDebounced()
      })
    },
    storeChange(val){
      // console.log(val);
      // this.sitesChecked = checkedSiteIds.join(',')
      // this.getAllChartDataDebounced()
      this.getStoreData(val)
    },
    showSite() {
      this.shows = !this.shows;
    },
    storeClose() {
      let store_id = this.storeChecked[0];
      var index = this.storeData.map(item => item.store_id).indexOf(store_id);
      let element = this.storeData[index];
      this.$set(element,'active', 0)
      this.storeChecked.shift();
      this.storeChange('store');
    },
    storeClick(code,index) {
      console.log('code',code)
      if(code !== '') {
        let element = this.storeData[index];
        if(element.active == 0) {
          this.$set(element,'active', 1)
          this.storeChecked.push(code);
        } else if(element.active == 1) {
          var index = this.storeChecked.indexOf(code);
          this.storeChecked.splice(index,1);
          this.$set(element,'active', 0)
        } else {
          this.$set(element,'active', 1)
          this.storeChecked.push(code);
        }
        
      } else {
        this.storeChecked = [];
        for (let i = 0; i < this.storeData.length; i++) {
          let element = this.storeData[i];
          console.log(element)
          this.$set(element,'active', 0)
        }
      }
      this.storeChange('store');
    },
    
    //站点 反选
    invertCheckedChange() {
      if(this.storeChecked.length == 0) {
        return false
      }
      let arr = []
      this.storeData.forEach(item => {
        if(item.active == 0 || !item.active) {
          item.active = 1;
        } else {
          item.active = 0;
        }
        if (item.active == 1) {
          arr.push(item.store_id)
        }
      })

      this.storeChecked = arr;
      this.storeChange('store');
    },
    // 具体站点改变
    // sitesCheckedChange(value) {
    //   let checkedSiteIds = []
    //   this.siteData.forEach(site => {
    //     if (site.checked) {
    //       checkedSiteIds.push(site.code)
    //     }
    //   })
    //   let checkedSiteLength = checkedSiteIds.length

    //   this.allSiteChecked = checkedSiteLength === this.siteData.length || checkedSiteLength === 0

    //   if (!this.allSiteChecked) {
    //     this.sitesChecked = checkedSiteIds.join(',')
    //     this.getAllChartDataDebounced()
    //   }
    // },

    // 获取销售KPI指标卡数据
    getIndexCardData() {
      this.indexCardLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
        language: this.language,
      }
      this.queryPost('saleIndex', postData, (res) => {
        this.indexCardData = res.data.datas
        this.indexCardLoading = false
      })
    },

    // 销售模块，全部商品 和 ODM商品 切换
    saleIsOdmChanged(index) {
      this.saleIsOdm = index
      this.getSaleTrendData()
      this.getSiteDistributeData()
      this.saleRankingsDataGet()
      this.storeSalesDataGet()
    },
    // 销售趋势对比
    saleTrendChartInitCompared(datas){
      console.log('datas',datas);
      
      let series1 = [],series2 = [],dataName = '', yName = '{value}'

      if (this.saleTrendType === '2' || this.saleTrendType === '3') {
        yName = '${value}'
      } else if (this.saleTrendType == '4') {
        yName = '{value}%'
      }
      for (const iterator of this.saleTrendTypeOptions) {
        if (iterator.value === this.saleTrendType) {
          dataName = iterator.label
        }
      }

 
      
      for (let index = 2; index < datas.compare.length; index++) {
            series1.push({
              type: 'line',
              name:  this.$lang('上期 ' + dataName),
              lineStyle: { width: 1, type: 'dashed' },
              seriesLayoutBy: 'row',
              xAxisIndex: 0,
              datasetIndex: 0,
              encode: { x: 0, y: index }
            })
        }
      for (let index = 2; index < datas.current.length; index++) {
        series2.push({
          type: 'line',
          name:  this.$lang('本期 ' + dataName),
          seriesLayoutBy: 'row',
          xAxisIndex: 1,
          datasetIndex: 1,
          encode: { x: 0, y: index }
        })
      }



      let option = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          confine: true,
          formatter: (params) => {
            // console.log('params',params);

            let str = '';
            let arr1 = [], arr2 = []
            for (let index = 0; index < params.length; index++) {
              // console.log('options',this.$options);
              const param = params[index]
              const value = param.value[param.encode.y[0]]
              // console.log('saleTrendType',this.saleTrendType);
              let formatValue = this.$options.filters['formatNum'](value)
              if (this.saleTrendType === '2' || this.saleTrendType === '3') {
                formatValue = '$' + formatValue
              } else if (this.saleTrendType == '4') {
                formatValue = formatValue + '%'
              }

              const contentItem = `
                  <div class="item">
                    ${params[index].marker}${params[index].seriesName}<span class="value">${formatValue}</span>
                  </div>`

              if (index < params.length/2) {
                arr1.push(contentItem)
              } else {
                arr2.push(contentItem)
              }
 
            }
            let title1 = '', title2 = ''
              title1 = (this.saleTrendTimeGranularity !== 'D') ? params[0].value[1] : params[0].value[0]
            title2 = (this.saleTrendTimeGranularity !== 'D') ? params[params.length / 2].value[1] : params[params.length / 2].value[0]
            
            if (this.dateTime === 'D') {
              title1 += time
              title2 += time 
            }
          
            str = GlobalConstAndFunc.Echarts.tooltipFormatter(arr1, title1) + GlobalConstAndFunc.Echarts.tooltipFormatter(arr2, title2)

            return str

            
          }
        },
        grid: GlobalConstAndFunc.Echarts.gridSetStyle,
        // color: GlobalConstAndFunc.Echarts.colorList(data.length - 2),
        color: GlobalConstAndFunc.Echarts.colorList(datas.current.length + datas.compare.length - 2),
        dataset: [{
          source: datas.compare,
        },
        {
          source: datas.current,
        }],
        xAxis: [
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            show: false,
            position: 'bottom',
            boundaryGap: false,
            axisLabel: {
              align: "center"
            }
          },
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            position: 'bottom',
            boundaryGap: false,
            axisLabel: {
              align: "center"
            }
          }
        ],
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          axisLabel: {
            formatter: yName
          }
        },
        series: series1.concat(series2),
      }
      this.saleTrendChart.setOption(option, true)





    },
    // 销售趋势切换日期
    saleTrendTimeGranularityChange(){
      if(this.saleComparedShow){
        this.getSaleTrendData()
      }else{
        this.saleCompared()
      }
    },
    // 销售趋势，数据获取
    getSaleTrendData() {
      this.saleTrendLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        dateLabel: this.saleTrendTimeGranularity,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
        language: this.language,
        isOdm: this.saleIsOdm,
        isSummarized: this.saleTrendBySite,
        type: this.saleTrendType
      }
      this.queryPost('saleTrend', postData, (res) => {
        this.saleTrendData = res.data.datas
        res.data.datas.info && this.$nextTick(() => {
          this.saleTrendChartInit(res.data.datas.info)
        })
        this.saleTrendLoading = false
      })
    },
    // 销售趋势，图表渲染
    saleTrendChartInit(data) {
      !this.saleTrendChart && (this.saleTrendChart = echarts.init(this.$refs.saleTrend.$refs.chart))
      let series = [], dataName = '', yName = '{value}'
      if (this.saleTrendType === '2' || this.saleTrendType === '3') {
        yName = '${value}'
      } else if (this.saleTrendType == '4') {
        yName = '{value}%'
      }
      for (const iterator of this.saleTrendTypeOptions) {
        if (iterator.value === this.saleTrendType) {
          dataName = iterator.label
        }
      }
      if (this.saleTrendBySite === '1') {
        series = [
          {
            type: 'line',
            name: this.$lang(dataName),
            seriesLayoutBy: 'row',
            encode: { x: 0, y: 2 }
          }
        ]
      } else {
        for (let index = 2; index < data.length; index++) {
          series.push({
            type: 'line',
            name: data[index][0],
            stack: 'sum',
            lineStyle: {
              opacity: 0
            },
            connectNulls: true,
            symbol: 'none',
            areaStyle: {},
            seriesLayoutBy: 'row',
            encode: { x: 0, y: index }
          })
        }
      }
      console.log()
      let legend = this.saleTrendBySite == '1' ?  {show: false} : {...GlobalConstAndFunc.Echarts.legendStyle,bottom: 0};
      console.log(legend)
      let option = {
        legend: legend,
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          confine: true,
          formatter: (params) => {
            let contentArr = [], title = params[0].name
            if (this.saleTrendTimeGranularity !== 'D') {
              title = params[0].value[1]
            }
            
            if(this.saleTrendBySite == '0'){
              const newParams = [];
              for (let index = 0; index < params.length; index++) {
                  let obj = {}
                  obj.marker = params[index].marker
                  obj.seriesName = params[index].seriesName
                  obj.formatValue = params[index].value[params[index].encode.y[0]] == '-' ? '-' : Number(params[index].value[params[index].encode.y[0]])
                  newParams.push(obj)
              }
              let newParams2 = _.orderBy(newParams, ['formatValue','seriesName'], ['desc', 'asc']);

              for (let index = 0; index < newParams2.length; index++) {
                let formatValue = this.$options.filters['formatNum'](newParams2[index].formatValue)
                if (this.saleTrendType === '2' || this.saleTrendType === '3') {
                  formatValue = '$' + formatValue
                } else if (this.saleTrendType == '4' && formatValue != '-') {
                  formatValue = formatValue + '%'
                }
                const contentItem = `
                    <div class="item">
                      ${newParams2[index].marker}${newParams2[index].seriesName}<span class="value">${formatValue}</span>
                    </div>`
                contentArr.push(contentItem)
              }
            }else{
              for (let index = 0; index < params.length; index++) {
                let formatValue = this.$options.filters['formatNum'](params[index].value[params[index].encode.y[0]])
                if (this.saleTrendType === '2' || this.saleTrendType === '3') {
                  formatValue = '$' + formatValue
                } else if (this.saleTrendType == '4') {
                  formatValue = formatValue + '%'
                }
  
                const contentItem = `
                    <div class="item">
                      ${params[index].marker}${params[index].seriesName}<span class="value">${formatValue}</span>
                    </div>`
                contentArr.push(contentItem)
              }
            }

            if (this.dateTime === 'D') {
              title += this.$lang('时')
            }

            return GlobalConstAndFunc.Echarts.tooltipFormatter(contentArr, title)
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          bottom: this.saleTrendBySite == '1' ? 0 : 30,
          right: 31
        },
        color: GlobalConstAndFunc.Echarts.colorList(data.length - 2),
        dataset: {
          // sourceHeader: true,
          source: data
        },
        xAxis: {
          ...GlobalConstAndFunc.Echarts.xAxisStyle,
          axisLabel: {
            align: 'center',
            margin: 12,
            showMinLabel: true,
            showMaxLabel: true,
            color: '#000000A6'
          },
          axisLine: {
            lineStyle: {
              color: '#00000040'
            }
          }
        },
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          axisLabel: {
            formatter: yName
          }
        },
        series,
      }
      this.saleTrendChart.setOption(option, true)
    },
    // 销售趋势，分站点查看切换
    saleTrendBySiteChanged() {
      this.saleTrendBySite = this.saleTrendBySite === '1' ? '0' : '1'
      if(this.saleTrendBySite === '0'){
        this.saleComparedShow = true
      }
      this.getSaleTrendData()
    },
    // 11136需求  去除对比功能
    // 销售趋势对比
    // saleCompared(){
    //   this.saleTrendBySite = '1'
    //   this.saleComparedShow = false;
    //   const postData = {
    //     'erp-req': true,
    //     'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
    //     dateType: this.queryTime,
    //     dateLabel: this.saleTrendTimeGranularity,
    //     startDate: this.rangeDate ? this.rangeDate[0] : '',
    //     endDate: this.rangeDate ? this.rangeDate[1] : '',
    //     countryCode: this.sitesCheckedData,
    //     language: this.language,
    //     isOdm: this.saleIsOdm,
    //     isSummarized: this.saleTrendBySite,
    //     type: this.saleTrendType
    //   }

    //   this.queryPost('saleTrendCompare', postData, (res) => {
    //     res.data.datas.info && this.$nextTick(() => {
    //       this.saleTrendChartInitCompared(res.data.datas.info)
    //     })
    //   })

    // },
    // // 销售趋势对比返回
    // saleBack(){
    //   this.saleComparedShow = true;
    //   this.saleTrendChartInit(this.saleTrendData.info);
    // },
    // 站点分布，数据获取
    getSiteDistributeData() {
      this.siteDistributeLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
        language: 'cn',
        isOdm: this.saleIsOdm,
        type: this.siteDistributeType,
      }
      this.queryPost('siteDistribute', postData, (res) => {
        this.siteDistributeData = res.data.datas
        res.data.datas.info && this.$nextTick(() => {
          this.siteDistributeChartInit(res.data.datas.info)
        })
        this.siteDistributeLoading = false
      })
    },
    // 站点分布，图表渲染
    siteDistributeChartInit(resData) {
      !this.siteDistributeChart && (this.siteDistributeChart = echarts.init(this.$refs.siteDistribute.$refs.chart));

      let chinaVal = 0
      var uae = ''

      for (let index = 0; index < resData.length; index++) {
        let item = resData[index]
        if(item.code === 'AE'){
          uae = item.name
        }
        if (item.name === '台湾' || item.name === '香港' || item.name === '澳门' || item.name === '中国大陆') {
          chinaVal += parseInt(item.value, 10)
          resData.splice(index, 1)
        }
        
      }
      resData.splice(0, 0, { name: '中国', value: chinaVal.toString() });
      let valData = resData.map(item => {
        return item.value
      });
      valData.sort(function(a,b){
        return b - a;
      })
      Math.ceil(valData[0]/100)*100
      let maxNumber = Math.ceil(valData[0]/100)*100;
      

      let option = {
        tooltip: {
          formatter: params => {
            let showVal = '';
            if (params.value) {
              showVal = this.$options.filters['formatNum'](params.value)
            } else {
              showVal = '-'
            }
            if(this.siteDistributeType == '2' && params.value) {
              showVal = '$' + showVal
            }
            return `${this.$lang(params.name)}: ${showVal}`
          },
        },
        visualMap: {
          min: 0,
          max: maxNumber,
          // text: ['High', 'Low'],
          realtime: false,
          calculable: true,
          orient: 'horizontal',
          bottom: 10,
          inRange: {
              color: ['#D9D9D9', '#0375DE', '#01284D']
          },
          formatter: (value) =>{
            return this.$options.filters['formatNum'](value)
          }
        },
        grid: {
          left:30,
          right: 30,
          bottom: 0
        },
        series: [{
          type: 'map',
          map: 'world',
          zoom: this.zoom,
          roam: true,
          scaleLimit: {
            min:1,
            max: 99
          },
          
          itemStyle: {
            normal: {
              borderColor: '#8C8C8C',
              borderWidth: 1,
              areaColor: '#D9D9D9'
            },
            
            emphasis: {
                areaColor: '#FFC30F',
                borderColor: '#8C8C8C',
                borderWidth: 1
            },
              
          },
          label: {
            formatter: (param) => {
              return this.$lang(param.name)
            }
          },
          nameMap: { "Afghanistan": "阿富汗", "Albania": "阿尔巴尼亚", "Algeria": "阿尔及利亚", "Angola": "安哥拉", "Argentina": "阿根廷", "Armenia": "亚美尼亚", "Australia": "澳大利亚", "Austria": "奥地利", "Azerbaijan": "阿塞拜疆", "Bahrain": "巴林", "Bangladesh": "孟加拉国", "Belarus": "白俄罗斯", "Belgium": "比利时", "Belize": "伯利兹", "Benin": "贝宁", "Bhutan": "不丹", "Bolivia": "玻利维亚", "Bosnia and Herz.": "波斯尼亚和黑塞哥维那", "Botswana": "博茨瓦纳", "Brazil": "巴西", "British Virgin Islands": "英属维京群岛", "Brunei": "文莱", "Bulgaria": "保加利亚", "Burkina Faso": "布基纳法索", "Burundi": "布隆迪", "Cambodia": "柬埔寨", "Cameroon": "喀麦隆", "Canada": "加拿大", "Cape Verde": "佛得角", "Cayman Islands": "开曼群岛", "Central African Rep.": "中非共和国", "Chad": "乍得", "Chile": "智利", "China": "中国", "Colombia": "哥伦比亚", "Comoros": "科摩罗", "Congo": "刚果", "Dem. Rep. Congo": "刚果民主共和国", "Costa Rica": "哥斯达黎加", "Croatia": "克罗地亚", "Cyprus": "塞浦路斯", "Czech Rep.": "捷克共和国", "Denmark": "丹麦", "Djibouti": "吉布提", "Dominican Rep.": "多米尼加共和国", "Ecuador": "厄瓜多尔", "Egypt": "埃及", "El Salvador": "萨尔瓦多", "Equatorial Guinea": "赤道几内亚", "Eritrea": "厄立特里亚", "Estonia": "爱沙尼亚", "Ethiopia": "埃塞俄比亚", "Fiji": "斐济", "Finland": "芬兰", "France": "法国", "Gabon": "加蓬", "Gambia": "冈比亚", "Georgia": "格鲁吉亚", "Germany": "德国", "Ghana": "加纳", "Greece": "希腊", "Greenland": "格陵兰", "Guatemala": "危地马拉", "Guinea": "几内亚",  "Guinea-Bissau": "几内亚比绍", "Guyana": "圭亚那", "Haiti": "海地", "Honduras": "洪都拉斯", "Hungary": "匈牙利", "Iceland": "冰岛", "India": "印度", "Indonesia": "印度尼西亚", "Iran": "伊朗", "Iraq": "伊拉克", "Ireland": "爱尔兰", "Isle of Man": "马恩岛", "Israel": "以色列", "Italy": "意大利", "Côte d'Ivoire": "科特迪瓦", "Jamaica": "牙买加", "Japan": "日本", "Jordan": "约旦", "Kazakhstan": "哈萨克斯坦", "Kenya": "肯尼亚", "Korea": "韩国", "Kuwait": "科威特", "Kyrgyzstan": "吉尔吉斯斯坦", "Lao PDR": "老挝", "Latvia": "拉脱维亚", "Lebanon": "黎巴嫩", "Lesotho": "莱索托", "Liberia": "利比里亚", "Libya": "利比亚", "Lithuania": "立陶宛", "Luxembourg": "卢森堡", "Macedonia": "马其顿", "Madagascar": "马达加斯加", "Malawi": "马拉维", "Malaysia": "马来西亚", "Maldives": "马尔代夫", "Mali": "马里", "Malta": "马耳他", "Mauritania": "毛利塔尼亚", "Mauritius": "毛里求斯", "Mexico": "墨西哥", "Moldova": "摩尔多瓦", "Monaco": "摩纳哥", "Mongolia": "蒙古", "Montenegro": "黑山共和国", "Morocco": "摩洛哥", "Mozambique": "莫桑比克", "Myanmar": "缅甸", "Namibia": "纳米比亚", "Nepal": "尼泊尔", "Netherlands": "荷兰", "New Zealand": "新西兰", "Nicaragua": "尼加拉瓜", "Niger": "尼日尔", "Nigeria": "尼日利亚", "Dem. Rep. Korea": "朝鲜", "Norway": "挪威", "Oman": "阿曼", "Pakistan": "巴基斯坦", "Panama": "巴拿马", "Paraguay": "巴拉圭", "Peru": "秘鲁", "Philippines": "菲律宾", "Poland": "波兰", "Portugal": "葡萄牙", "Puerto Rico": "波多黎各", "Qatar": "卡塔尔", "Reunion": "留尼旺", "Romania": "罗马尼亚", "Russia": "俄罗斯", "Rwanda": "卢旺达", "San Marino": "圣马力诺", "Saudi Arabia": "沙特阿拉伯", "Senegal": "塞内加尔", "Serbia": "塞尔维亚", "Sierra Leone": "塞拉利昂", "Singapore": "新加坡", "Slovakia": "斯洛伐克", "Slovenia": "斯洛文尼亚", "Somalia": "索马里", "South Africa": "南非", "Spain": "西班牙", "Sri Lanka": "斯里兰卡", "Sudan": "苏丹", "S. Sudan": "南苏丹", "Suriname": "苏里南", "Swaziland": "斯威士兰", "Sweden": "瑞典", "Switzerland": "瑞士", "Syria": "叙利亚", "Tajikistan": "塔吉克斯坦", "Tanzania": "坦桑尼亚", "Thailand": "泰国", "Togo": "多哥", "Tonga": "汤加", "Trinidad and Tobago": "特立尼达和多巴哥", "Tunisia": "突尼斯", "Turkey": "土耳其", "Turkmenistan": "土库曼斯坦", "U.S. Virgin Islands": "美属维尔京群岛", "Uganda": "乌干达", "Ukraine": "乌克兰", "United Arab Emirates": uae, "United Kingdom": "英国", "United States": "美国", "Uruguay": "乌拉圭", "Uzbekistan": "乌兹别克斯坦", "Vatican City": "梵蒂冈城", "Venezuela": "委内瑞拉", "Vietnam": "越南", "Yemen": "也门", "Yugoslavia": "南斯拉夫", "Zaire": "扎伊尔", "Zambia": "赞比亚", "Zimbabwe": "津巴布韦", "Papua New Guinea": "巴布亚新几内亚", "Solomon Is.": "所罗门群岛", "Fr. S. Antarctic Lands": "所罗门群岛", "Cuba": "古巴", "Bahamas": "巴哈马", "W. Sahara": "西撒哈拉", "Vanuatu": "瓦努阿图", "New Caledonia": "新喀里多尼亚", "S. Geo. and S. Sandw. Is.": "南乔治亚和南桑威奇群岛", "Falkland Is.": "福克兰群岛" },
          data: resData
        }]
      }
      this.siteDistributeChart.setOption(option, true)

      // this.siteDistributeChart.setOption({
      //   tooltip: {
      //     ...this.echartTooltipStyle,
      //     formatter: (params) => {
      //       let showVal = this.$options.filters.formatNum(params.value)

      //       if (this.siteDistributeType === '2') {
      //         showVal = '$' + showVal
      //       }
      //       return `${params.marker}${params.name}: ${showVal}`
      //     }
      //   },
      //   legend: {
      //     ...GlobalConstAndFunc.Echarts.legendStyle
      //   },
      //   color: GlobalConstAndFunc.Echarts.colorList(data.length),
      //   series: [{
      //     type: 'pie',
      //     center: ['50%', '40%'],
      //     radius: '55%',
      //     data: data,
      //   }]
      // }, true)
    },
    // <!-- 11136 去除更多功能 -->
    // 站点分布，表格展示切换
    // siteDistributeTableShowChanged() {
    //   this.siteDistributeTableShow = !this.siteDistributeTableShow
    //   if (this.siteDistributeTableShow) {
    //     this.isDistributeType = false
    //     this.siteDistributeTableSortRule = 'desc'
    //     this.siteDistributeTableSortWord = 'order_num'
    //     this.siteDistributeTablePage = 1
    //     this.$refs.siteDistributeTable.sort('order_num', 'descending')
    //   } else {
    //     this.isDistributeType = true
    //     this.getSiteDistributeData()
    //   }
    // },
    // 站点分布，表格数据获取
    siteDistributeTableDataGet() {
      this.siteDistributeLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        countryCode: this.sitesCheckedData,
        language: this.language,
        isOdm: this.saleIsOdm,
        type: this.siteDistributeType,
        sortWord: this.siteDistributeTableSortWord,
        sortRule: this.siteDistributeTableSortRule,
        page: this.siteDistributeTablePage,
        pageSize: 5
      }
      this.queryPost('siteDistributeTable', postData, (res) => {
        this.siteDistributeTableData = res.data
        this.siteDistributeLoading = false
      })
    },
    // 站点分布，表格翻页
    siteDistributeTablePageChange(value) {
      this.siteDistributeTablePage = value
      this.siteDistributeTableDataGet()
    },
    // 站点分布，表格排序
    siteDistributeTableSort({
      column,
      prop,
      order
    }) {
      this.siteDistributeTableSortWord = prop
      this.siteDistributeTableSortRule = order === 'ascending' ? 'asc' : 'desc'
      this.siteDistributeTableDataGet()
    },
    // 店铺访问用户分布饼图数据获取
    storeVisitorDataGet() {
      this.storeVisitorLoading = true
      var postData = {
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        'erp-req': true,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        dateType: this.queryTime,
        language: this.language,
        siteCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
      }
      this.queryPost('storeUserDistribute', postData, (res) => {

        if (res.data.success === true) {
          this.storeVisitorData = res.data.datas
          if (res.data.datas) {
            this.$nextTick(() => {
              this.storeVisitorChartInit(res.data.datas)
            })
          }
          this.storeVisitorLoading = false
        } else {
          this.$message.error(res.data.msg)
        }

      })
      
  
    },
    // 店铺访问用户分布饼图,图表渲染
    storeVisitorChartInit(chartData) {
      !this.storeVisitorChart &&
        (this.storeVisitorChart = echarts.init(
          this.$refs.storeVisitor.$refs.chart
        ))
      this.storeVisitorChart.setOption({
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name} ${this.$lang('访问用户')}：${showVal}`

          },
        },
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            return `${params.name
              },${showVal}`
          },
        },
        color: GlobalConstAndFunc.UI.colors9To16,
        series: [
          {
            type: 'pie',
            radius: '65%',
            data: chartData,
          },
        ],
      })
    },
    // 店铺销售分布饼图数据获取
    storeSalesDataGet() {
      this.storeSalesLoading = true
      var postData = {
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        'erp-req': true,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        dateType: this.queryTime,
        language: this.language,
        // countryCode: this.sitesCheckedData,
        // storeId:this.storeChecked.length == 0 ? '':this.storeCheckedData,
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
        type:this.storeSalesType,
        isOdm: this.saleIsOdm
      }
      this.queryPost('storeSaleDistribute', postData, (res) => {
        console.log('res',res);

        if (res.data.success === true) {
          this.storeSalesData = res.data.datas
          if (res.data.datas) {
            this.$nextTick(() => {
              this.storeSalesChartInit(res.data.datas)
            })
          }
          this.storeSalesLoading = false
        } else {
          this.$message.error(res.data.msg)
        }

      })
      
  
    },
    // 店铺销售分布饼图,图表渲染
    storeSalesChartInit(chartData) {
      !this.storeSalesChart &&
        (this.storeSalesChart = echarts.init(
          this.$refs.storeSales.$refs.chart
        ))
      this.storeSalesChart.setOption({
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)

            if (this.storeSalesType === '1') {
              return `${params.name} ${this.$lang('订单数')}：${showVal}`
            } else if (this.storeSalesType === '2') {
              return `${params.name} ${this.$lang('销售额')}：$${showVal}`
            }
          },
        },
        label: {
          formatter: (params) => {
            let showVal = this.$options.filters.formatNum(params.value)
            
            if (this.storeSalesType === '1') {
              return `${params.name},${showVal}`
            } else if (this.storeSalesType === '2') {
              return `${params.name},$${showVal}`
            }
          },
        },
        color: GlobalConstAndFunc.UI.colors9To16,
        series: [
          {
            type: 'pie',
            radius: '65%',
            data: chartData,
          },
        ],
      })
    },
    // 商品销售Top10，数据获取
    saleRankingsDataGet() {
      this.saleRankingsLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
        language: this.language,
        isOdm: this.saleIsOdm,
        queryType: this.saleRankingsType,
      }
      this.queryPost('saleRankings', postData, (res) => {
        this.saleRankingsData = res.data.datas
        res.data.datas.goodsInfo && this.$nextTick(() => {
          this.saleRankingsChartInit(res.data.datas.goodsInfo)
        })
        this.saleRankingsLoading = false
      })
    },
    // 商品销售Top10，图表渲染
    saleRankingsChartInit(data) {
      const obj = {}
      !this.saleRankingsChart && (this.saleRankingsChart = echarts.init(this.$refs.saleRankings.$refs.chart))
      // value 不展示
      // let yName = '{value}'
      // if (this.saleRankingsType === 'sales' || this.saleRankingsType === 'margin') {
      //   yName = '${value}'
      // } else if (this.saleRankingsType === 'marginRate') {
      //   yName = '{value}%'
      // }
      let minIndex = data.length - 1;
      let minVal = data[minIndex].value;
      let maxVal = data[0].value;
      let data1 = [];
      let data2 = [];
      data.forEach(element => {
        data1.push({
          value: maxVal,
          copyValue: element.value
        })
        data2.push({
          value: minVal,
          guds_name: element.guds_name
        })
      });
      for (const [index, item] of data.entries()) {
        obj[index] = {
          height: 26,
          width: 26,
          align: 'center',
          backgroundColor: {
            image: item.picture
          }
        }
      }
      this.saleRankingsChart.setOption({
        tooltip: {
          trigger: 'axis',
          confine: true,
          extraCssText: 'max-width:360px;word-break: break-all;word-wrap: break-word;white-space:pre-wrap',
          formatter: (params) => {
            params = params[2]
            let showVal = this.$options.filters.formatNum(params.data.value)

            if (this.saleRankingsType === 'sales' || this.saleRankingsType === 'margin') {
              showVal = '$' + showVal
            } else if (this.saleRankingsType === 'marginRate') {
              showVal += '%'
            }
            return `<div>${params.marker}${params.data.guds_name}: ${showVal}</div>`
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          bottom: 25,
          right: 0,
          left: 30
        },
        color: GlobalConstAndFunc.Echarts.colorList(1),
        // dataset: {
        //   source: data,
        //   dimensions: ['sku_id', 'value', 'guds_name', 'picture'],
        // },
        xAxis: {
          ...GlobalConstAndFunc.Echarts.xAxisStyle,
          type: 'value',
          axisLabel: {
            show: false
          },
          max: maxVal >= 0 ? maxVal : maxVal+ 1,
          // min: minVal >= 0 ? 0 : minVal- 1,
          show: false
        },
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          type: 'category',
          boundaryGap: false,
          inverse: true,
          data: [0,1,2,3,4,5,6,7,8,9],
          show: false,
          axisLabel: {
            interval: 0,
            formatter: (value, index) => {
              return ('{' + index + '| }')
            },
            rich: {
              ...obj,
            }
          }
        },
        series: [{
          type: 'bar',
          barWidth: 8,
          data: data2,
          itemStyle: {
            color: '#ffffff'
          },
          label: {
            show: true,
            position: [0, -2],
            color: '#8C8C8C',
            formatter: (params) => {
              const str = params.data.guds_name && params.data.guds_name.length > 20 ? (params.data.guds_name.slice(0, 19) + '...') : params.data.guds_name;
              return str;
            },
            rich: {
            }
          },
        },{
          type: 'bar',
          barWidth: 1,
          data: data1,
          itemStyle: {
            color: '#ffffff'
          },
          label: {
            show: true,
            position: 'insideBottomRight',
            color: '#262626',
            fontWeight: 500,
            formatter: (params) => {
              let showVal = this.$options.filters.formatNum(params.data.copyValue)

              if (this.saleRankingsType === 'sales' || this.saleRankingsType === 'margin') {
                showVal = '$' + showVal
              } else if (this.saleRankingsType === 'marginRate') {
                showVal += '%'
              }
              return showVal;
            },
            rich: {
            }
          },
        },{
          type: 'bar',
          barWidth: 8,
          data: data,
        }]
      }, true)
    },
    // 获取转化KPI指标卡数据
    transferIndexDataGet() {
      this.transferIndexLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        siteCode: this.sitesCheckedData,
        language: this.language,
        storeId: this.storeCheckedData,
      }
      this.queryPost('transformIndex', postData, (res) => {
        this.transferIndexData = res.data.datas
        this.transferIndexLoading = false
      })
    },
    // 11136 去除比对功能
    // 转化趋势对比
    // transferCompared(){
    //   this.transferComparedShow = false;
    //   const postData = {
    //     'erp-req': true,
    //     'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
    //     dateType: this.queryTime,
    //     dateLabel: this.transferTrendTimeGranularity,
    //     startDate: this.rangeDate ? this.rangeDate[0] : '',
    //     endDate: this.rangeDate ? this.rangeDate[1] : '',
    //     siteCode: this.sitesCheckedData,
    //     language: this.language,
    //     type: this.transferTrendType
    //   }
    //   this.queryPost('transformTrendCompare', postData, (res) => {
    //     res.data.datas.info && this.$nextTick(() => {
    //       this.transferTrendChartInitCompared(res.data.datas.info)
    //     })
    //   })
    // },
    // // 转化趋势返回
    // transferBack(){
    //   this.transferComparedShow = true;
    //   this.transferTrendDataGet()
    // },
    transferTrendChartInitCompared(datas){

      
      let series1 = [],series2 = [],dataName = ''

      for (const iterator of this.transferTrendTypeOptions) {
        if (iterator.value === this.transferTrendType) {
          dataName = iterator.label
        }
      }

 
      
      for (let index = 2; index < datas.compare.length; index++) {
            series1.push({
              type: 'line',
              name:  this.$lang('上期 ' + dataName),
              lineStyle: { width: 1, type: 'dashed' },
              seriesLayoutBy: 'row',
              xAxisIndex: 0,
              datasetIndex: 0,
              encode: { x: 0, y: index }
            })
        }
      for (let index = 2; index < datas.current.length; index++) {
        series2.push({
          type: 'line',
          name:  this.$lang('本期 ' + dataName),
          seriesLayoutBy: 'row',
          xAxisIndex: 1,
          datasetIndex: 1,
          encode: { x: 0, y: index }
        })
      }



      let option = {
        legend: {
          ...GlobalConstAndFunc.Echarts.legendStyle,
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
              if (this.transferTrendType !== '2') {
                formatValue += '%'
              } 
              const contentItem = `
                  <div class="item">
                    ${params[index].marker}${params[index].seriesName}<span class="value">${formatValue}</span>
                  </div>`

              if (index < params.length/2) {
                arr1.push(contentItem)
              } else {
                arr2.push(contentItem)
              }
 
            }
            let title1 = '', title2 = ''
              title1 = (this.transferTrendTimeGranularity !== 'D') ? params[0].value[1] : params[0].value[0]
            title2 = (this.transferTrendTimeGranularity !== 'D') ? params[params.length / 2].value[1] : params[params.length / 2].value[0]
            
            if (this.dateTime === 'D') {
              title1 += time
              title2 += time
            }
            str = GlobalConstAndFunc.Echarts.tooltipFormatter(arr1, title1) + GlobalConstAndFunc.Echarts.tooltipFormatter(arr2, title2)

            return str

            
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          top: 33
        },
        color: GlobalConstAndFunc.Echarts.colorList(datas.current.length + datas.compare.length - 2),
        dataset: [{
          source: datas.compare,
        },
        {
          source: datas.current,
        }],
        xAxis: [
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            show: false,
            position: 'bottom',
            boundaryGap: false,
            axisLabel: {
              align: "center"
            }
          },
          {
            ...GlobalConstAndFunc.Echarts.xAxisStyle,
            type: 'category',
            position: 'bottom',
            boundaryGap: false,
            axisLabel: {
              align: "center"
            }
          }
        ],
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          axisLabel: {
            formatter: this.transferTrendType !== '3' ? '{value}%' : '{value}'
          }
        },
        series: series1.concat(series2),
      }
      this.transferTrendChart.setOption(option, true)



    },
    // 搜索转化漏斗
    getSearchConversionRatesTrendData(){
      this.searchTransChart.clear();
      this.searchTransChart.showLoading({
        text: ''
      })
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        dateType: this.queryTime,
        language: this.language,
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
      }
      this.queryPost('searchDistributeFunnel', postData, (res) => {
        this.searchTransData = res.data.datas
        res.data.datas && this.$nextTick(() => {
          this.initSearchTransChart(res.data.datas)
        })
        this.searchTransChart.hideLoading()
      })
    },
    initSearchTransChart(resData) {
      // console.log('resData',resData);
      let {chartData,chartPercent} = resData;
      var rightArrow = "image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMEAAAAaCAYAAAF5vAjmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAwaADAAQAAAABAAAAGgAAAAAJ2JVxAAABUklEQVR4Ae2ZQQqDMBBFG28hXqVdteeqSM+q3kKbCFk4m4AmDmaeUGS0mcm8z7dpdA9/jOM4hHOJo+u6vkTeXc7QQLO7UihwJVHFOV9SJBa75xlEKd0us0RqImfvX+Lrs5NMjXfhC1c8O1IT4T4EIGCUAL+aRoXP1nY1q4psRJQSVbEqUmKXrSwiZEN5PBEiHGeXbSQiZEN5PNH2J3Oapte6rp/jaRgJgRsSmOe55zGkLBwCIIAyAeXyOAABlAkol8cBCKBMQLk8DkAAZQLK5XGAsgC86tMV4LttxsU5+E25p3PuHWPOEKiZgN+A7v3ntzNBzQ3TGwQkgbAhvSxLw1pIkiE2RwATmJOchiUBTCCJEJsjgAnMSU7DkgAmkESIzRHABOYkp2FJABNIIsTmCGACc5LTsCSACSQRYnMEMIE5yWlYEnDh1bG8SAwBKwTath3+D9M/NovemzEAAAAASUVORK5CYII=";
      var arrow = 'image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAARCAMAAACLgl7OAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAaVBMVEUAAADBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcEAAAD45xibAAAAInRSTlMAmT6WJYwSfBMGZAFHmEgtkBeCCW0KAlI1k5QeiA10A1tc7ah1owAAAAFiS0dEAIgFHUgAAAAJcEhZcwAACxIAAAsSAdLdfvwAAAB7SURBVCjPtZDZDoAgDAQXvA+8bwX1/3/SGKIBEd+cx07TdgtIiAF0/mygDvnAoYDr2b3nnjP8wOaDUG6J4ncfR9cdScpMzbJECZEXT1/kesyy0n1VPv6AulF908Kg6+9DWN/hjWGUfhpgYV5Ov8ywwgUhguODddtXvXIAjuUEs/70/t4AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTItMTZUMTU6MzM6MDkrMDg6MDCzL2BEAAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEyLTE2VDE1OjMzOjA5KzA4OjAwwnLY+AAAAABJRU5ErkJggg==';
      var markLineSetting = {
          normal: {
            show: true,
            backgroundColor: '#f4f4f4',
            borderRadius: 4,
            position: 'right',
            color: '#333',
            fontSize: 13,
            width: 56,
            height: 20,
            align: 'center',
            lineHeight: 20,
            offset: [-10,3],
            formatter: function(d) {
                if (d.value) {
                    var ins = '{words|' + d.data.itemValue + '}';
                    return ins
                }
            },
            rich: {
                words: {
                }
            }
          }
      };
      // let colors = ['#0375DE', '#13C2C2', '#FFC30F', '#FA8C16', '#F14E22'];
      var data1 = [];
      var ydata = [];
      var xiaojiantou_data = [];
      for (var i = 0; i < chartData.length; i++) {
          var obj1 = {
              value: (100 / chartData.length) * (chartData.length - i),
              num: chartData[i].value,
              name: chartData[i].name
          };
          var _jiantou = {
              value: 100,
          }
          data1.push(obj1);
          ydata.push(i + 1);
          xiaojiantou_data.push(_jiantou)
      }

      var arrowData = []
      for (var i = 0; i < chartPercent.length; i++) {
          var _objdd = {
              value: 185,
              itemValue: chartPercent[i].value + '%',
              label: markLineSetting,
          }
          arrowData.push(_objdd);
      }
      let option = {
        backgroundColor: '#ffffff',
        // color: colors,
        grid: {
          top: 55,
          left: 0,
          right: 0,
          bottom: 0,
          height: 245,
        },
        xAxis: [
            {
                position: 'bottom',
                show: false,
                min: 0,
                max: 200
            }
        ],
        yAxis: [{
            top: '120',
            show: false,
            boundaryGap: false,
            inverse: true,
            type: "category",
            data: [1,2,3,4,5]
        }],
        series: [
            {
                top: 0,
                right: 0,
                bottom: 0,
                left: 'center',
                type: 'funnel',
                height: '345',
                gap: 25,
                minSize: 100,
                width: '70%',
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
                data: data1
            },
            {
              top: '0',
              type: 'pictorialBar',
              name: 'xiaojiantou',
              symbolSize: ['32', '16'],
              symbolPosition: 'center',
              symbol: arrow,
              animation: true,
              symbolClip: true,
              symbolOffset: [0, -4],
              z: 10,
              data: [{
                value:200,
              },{
                value:200,
              },{
                value:200,
              },{
                value:200,
              },{
                value:200,
              }]
            },
            {
              top: '0',
              name: 'youcejiantou',
              type: 'pictorialBar',
              symbolPosition: 'end',
              symbolSize: ['300', '40'],
              symbol: rightArrow,
              symbolClip: true,
              symbolOffset: [0,-5],
              z: 1,
              data: arrowData
            },
        ]
    };

      this.searchTransChart.setOption(option, true)
    },
    // 购买转化漏斗
    getBuyConversionRatesTrendData(){
      this.buyTransChart.clear();
      this.buyTransChart.showLoading({
        text: ''
      })
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        dateType: this.queryTime,
        language: this.language,
        countryCode: this.sitesChecked.join(),
        storeId: this.storeCheckedData,
      }
      this.queryPost('purchaseFunnel', postData, (res) => {
        this.buyTransData = res.data.datas
        res.data.datas && this.$nextTick(() => {
          this.initBuyTransChart(res.data.datas)
        })
        this.buyTransChart.hideLoading()
      })
    },
    initBuyTransChart(resData) {
      // console.log('resData',resData);
      let {chartData,chartPercent} = resData;
      var rightArrow = "image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMEAAAAaCAYAAAF5vAjmAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAwaADAAQAAAABAAAAGgAAAAAJ2JVxAAABUklEQVR4Ae2ZQQqDMBBFG28hXqVdteeqSM+q3kKbCFk4m4AmDmaeUGS0mcm8z7dpdA9/jOM4hHOJo+u6vkTeXc7QQLO7UihwJVHFOV9SJBa75xlEKd0us0RqImfvX+Lrs5NMjXfhC1c8O1IT4T4EIGCUAL+aRoXP1nY1q4psRJQSVbEqUmKXrSwiZEN5PBEiHGeXbSQiZEN5PNH2J3Oapte6rp/jaRgJgRsSmOe55zGkLBwCIIAyAeXyOAABlAkol8cBCKBMQLk8DkAAZQLK5XGAsgC86tMV4LttxsU5+E25p3PuHWPOEKiZgN+A7v3ntzNBzQ3TGwQkgbAhvSxLw1pIkiE2RwATmJOchiUBTCCJEJsjgAnMSU7DkgAmkESIzRHABOYkp2FJABNIIsTmCGACc5LTsCSACSQRYnMEMIE5yWlYEnDh1bG8SAwBKwTath3+D9M/NovemzEAAAAASUVORK5CYII=";
      var arrow = 'image://data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAARCAMAAACLgl7OAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAaVBMVEUAAADBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcEAAAD45xibAAAAInRSTlMAmT6WJYwSfBMGZAFHmEgtkBeCCW0KAlI1k5QeiA10A1tc7ah1owAAAAFiS0dEAIgFHUgAAAAJcEhZcwAACxIAAAsSAdLdfvwAAAB7SURBVCjPtZDZDoAgDAQXvA+8bwX1/3/SGKIBEd+cx07TdgtIiAF0/mygDvnAoYDr2b3nnjP8wOaDUG6J4ncfR9cdScpMzbJECZEXT1/kesyy0n1VPv6AulF908Kg6+9DWN/hjWGUfhpgYV5Ov8ywwgUhguODddtXvXIAjuUEs/70/t4AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTItMTZUMTU6MzM6MDkrMDg6MDCzL2BEAAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEyLTE2VDE1OjMzOjA5KzA4OjAwwnLY+AAAAABJRU5ErkJggg==';
      var markLineSetting = {
          normal: {
              show: true,
              backgroundColor: '#f4f4f4',
              borderRadius: 4,
              position: 'right',
              color: '#333',
              fontSize: 13,
              width: 56,
              height: 20,
              align: 'center',
              lineHeight: 20,
              offset: [-10,0],
              formatter: function(d) {
                  if (d.value) {
                      let str = '{text|' + d.data.itemValue + '}'
                      return str
                  }
              },
              rich:{
                text:{}
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
              value: 185,
              itemValue: chartPercent[i].value + '%',
              label: markLineSetting,
          }
          arrowData.push(_objdd);
      }
      let option = {
        backgroundColor: '#ffffff',
        grid: {
            top: 60,
            left: 0,
            right: 0,
            height: 225,
            bottom: 0,
        },
        xAxis: [
            {
                show: false,
                min: 0,
                max: 200,
                z:10
            }
        ],
        yAxis: [{
            show: false,
            boundaryGap: false,
            inverse: true,
            type: "category",
            data: [1,2,3,4]
        }],
        series: [
            {
                top: 0,
                bottom: 0,
                right: 0,
                left: 'center',
                type: 'funnel',
                height: '345',
                gap: 25,
                minSize: 100,
                width: '70%',
                z: 3,
                itemStyle: {
                  color: '#0375DE'
                },
                label: {
                    show: true,
                    position: 'inside',
                    fontSize: '12',
                    color: '',
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
                type: 'pictorialBar',
                name: 'xiaojiantou',
                symbolSize: ['32', '16'],
                symbolPosition: 'center',
                symbol: arrow,
                animation: true,
                symbolClip: true,
                z: 10,
                data: [{
                  value:200,
                  symbolOffset: [0, 3],
                },{
                  value:200,
                  symbolOffset: [0, 3]
                },{
                  value:200,
                },{
                  value:200,
                }]

            },
            {
                top: '0',
                name: 'youcejiantou',
                type: 'pictorialBar',
                symbolPosition: 'end',
                symbolSize: ['300', '40'],
                symbol: rightArrow,
                symbolClip: true,
                z: 1,
                data: arrowData
            },
        ]
    };

      this.buyTransChart.setOption(option, true)
    },
    transferTrendDataGetChange(){
      if(this.transferComparedShow){
        this.transferTrendDataGet()
      }else{
        this.transferCompared()
      }
    },
    // 转化趋势，数据获取
    transferTrendDataGet() {
      this.transferTrendLoading = true
      const postData = {
        'erp-req': true,
        'erp-cookie': 'PHPSESSID=' + getCookie('PHPSESSID') + ';',
        dateType: this.queryTime,
        dateLabel: this.transferTrendTimeGranularity,
        startDate: this.rangeDate ? this.rangeDate[0] : '',
        endDate: this.rangeDate ? this.rangeDate[1] : '',
        siteCode: this.sitesCheckedData,
        language: this.language,
        type: this.transferTrendType,
        storeId: this.storeCheckedData,
      }
      this.queryPost('transformTrend', postData, (res) => {
        this.transferTrendData = res.data.datas
        res.data.datas.info && this.$nextTick(() => {
          this.transferTrendChartInit(res.data.datas.info)
        })
        this.transferTrendLoading = false
      })
    },
    // 转化趋势，图表渲染
    transferTrendChartInit(data) {
      !this.transferTrendChart && (this.transferTrendChart = echarts.init(this.$refs.transferTrend.$refs.chart))
      let dataName = ''
      for (const iterator of this.transferTrendTypeOptions) {
        if (iterator.value === this.transferTrendType) {
          dataName = iterator.label
        }
      }
      let option = {
        legend: {
          show: false,
        },
        tooltip: {
          ...GlobalConstAndFunc.Echarts.tooltipStyle,
          trigger: 'axis',
          confine: true,
          formatter: (params) => {
            let contentArr = [], title = params[0].name
            if (this.transferTrendTimeGranularity !== 'D') {
              title = params[0].value[1]
            }
            for (let index = 0; index < params.length; index++) {
              let formatValue = this.$options.filters['formatNum'](params[index].value[params[index].encode.y[0]])
              if (this.transferTrendType == '1' || this.transferTrendType == '3') {
                formatValue += '%'
              }
              const contentItem = `
                  <div class="item">
                    ${params[index].marker}${params[index].seriesName}<span class="value">${formatValue}</span>
                  </div>`
              contentArr.push(contentItem)
            }

            if (this.dateTime === 'D') {
              title += this.$lang('时')
            }
            return GlobalConstAndFunc.Echarts.tooltipFormatter(contentArr, title)
          }
        },
        grid: {
          ...GlobalConstAndFunc.Echarts.gridSetStyle,
          top: 20,
          right: 32,
          bottom: 0,
          left: 0
        },
        color: GlobalConstAndFunc.Echarts.colorList(data.length - 2),
        dataset: {
          // sourceHeader: true,
          source: data
        },
        xAxis: {
          ...GlobalConstAndFunc.Echarts.xAxisStyle,
          axisLabel: {
            align: 'center',
            margin: 12,
            showMaxLabel: true,
            showMinLabel: true,
            color: '#000000A6'
          },
          axisLine: {
            lineStyle: {
              color: '#00000040'
            }
          }
        },
        yAxis: {
          ...GlobalConstAndFunc.Echarts.yAxisStyle,
          axisLabel: {
            formatter: this.transferTrendType == '1' || this.transferTrendType == '3' ? '{value}%' : '{value}'
          }
        },
        series: [
          {
            type: 'line',
            name: this.$lang(dataName),
            seriesLayoutBy: 'row',
            encode: { x: 0, y: 2 },
          }
        ],
      }
      this.transferTrendChart.setOption(option, true)
    },
  }
});
