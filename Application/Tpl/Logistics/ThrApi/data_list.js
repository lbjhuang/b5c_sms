if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var VM = new Vue({
    el: '#statistics',
    data() {
        return { 
            search: {
                api_platform_cd: 'N002850001', // api对接平台cd
                date: [], // 查询数据的时间
                type: 'order_no', // 类型，order_no为订单号，logistics_no为物流运单号
                value: '', // 订单号
                logistics_status_cd:'',//物流转态
                company:'',//物流公司
            },
            pageIndex: 1,
            pageSize: 10,
            tableData: [],
            total: 0,
            platformOption: null, // 平台下拉选项
            loading: true,
            average: 0, // 日均订单量
            apiTotal: '', // api合计次数
            trackIndex: 4,  // 物流轨迹显示多少条标识
            trackData: [], // 物流轨迹数据
            title: '新增手工运单',
            manualAdd: false,
            formData: {
                plat_cd: '',
                order_id: '',
                tracking_number: '',
                b5c_logistics_status: '',
                logistics_cd: '',
                remark: '',
                content_cn: [],
                content_en: []
            },
            rules: {
                plat_cd: [{ required: true, message: this.$lang('请选择所属店铺'), trigger: 'change' }],
                order_id: [{ required: true, message: this.$lang('请输入第三方订单id'), trigger: 'blur' }],
                tracking_number: [{ required: true, message: this.$lang('请输入物流运单号'), trigger: 'blur' }],
                b5c_logistics_status: [{ required: true, message: this.$lang('请选择物流状态'), trigger: 'change' }],
                logistics_cd: [{ required: true, message: this.$lang('物流公司为空，请检查对应的 订单id 和 平台'), trigger: 'change' }],
            },
            stores: [],
            logic_status: [],
            logics_platform_friend_status: [],
            logisticsCompany: [],
            logicStatus: [],
            textradio: 'chineseTxt',
            chineseTxt: '',
            englishTxt: '',
            popoverValue: false,
            orderStop: true,
            trackingStop: true,
            logicStatusType:'1'
        }
    },
    watch: {
        'search.type': function() {
            this.search.value = '';
        }
    },
    methods: { 
        // 获取关联的物流公司
        changeBlur() {
            if(this.formData.plat_cd && this.formData.order_id){
                let param = {
                    thr_order_id: this.formData.order_id,
                    plat_code:this.formData.plat_cd
                    // thr_order_id:'GSL240034203',
                    // plat_code:'N000837403'
                }
                axios.post('/index.php?g=OMS&m=Order&a=orderDetail', param).then(res => {
                    console.log(res);
                    // this.orderStop = true;
                    if(res.data.status === 200000 && res.data.data.data){
                        let datas = res.data.data.data;
                        let logistics_company = datas[0].patch_data[0].logistics_company;
                        console.log(logistics_company);
                        this.formData.logistics_cd = this.logisticsCompany.find(res => res.cdVal === logistics_company).cd;
                    }
                    
                })
            }
            
        },
        api_platform_change:function(val){
            var _this = this
            _this.search.logistics_status_cd = ''
            if(val == 'N002850003'){
                _this.logicStatusType = '2'
            }else{
                _this.logicStatusType = '1'
            }
        },
        enterOrderNo() {
            if(this.formData.plat_cd && this.orderStop){
                let param = {
                    type: "1",
                    search_data: this.formData.order_id,
                    plat_cd: this.formData.plat_cd
                }
                this.orderStop = false;
                // let param = {
                //     "type":"1",
                //     "search_data":"GSL190034215_1",
                //     "plat_cd":"N000837403"
                // }
                axios.post('index.php?g=logistics&m=ThrApi&a=get_order_associate', param).then(res => {
                    console.log(res);
                    this.orderStop = true;
                    if(res.data.code === 200 && res.data.data){
                        this.formData.tracking_number = res.data.data
                    } else {
                        this.$message({
                            type: 'error',
                            message: '当前店铺和第三方订单id不匹配'
                        });
                    }
                })
            }
        },
        enterTrackingNo() {
            if(this.formData.plat_cd && this.trackingStop){
                let param = {
                    type: "2",
                    search_data: this.formData.tracking_number,
                    plat_cd: this.formData.plat_cd
                }
                this.trackingStop = false;
                axios.post('index.php?g=logistics&m=ThrApi&a=get_order_associate', param).then(res => {
                    console.log(res);
                    this.trackingStop = true;
                    if(res.data.code === 200 && res.data.data){
                        this.formData.order_id = res.data.data
                    } else {
                        this.$message({
                            type: 'error',
                            message: '当前店铺和物流运单号不匹配'
                        });
                    }
                })
            }
        },
        formClose() {
            this.$refs['ruleForm'].resetFields();
            this.formData.remark = '';
            this.chineseTxt = '';
            this.englishTxt = '';
        },
        query() {
            this.$refs['ruleForm'].validate((valid) => {
                if (valid) {
                  if(this.formData.plat_cd === 'N000834100' && !this.chineseTxt) {
                    this.$message.error('当前店铺中文数据类型必填');
                    return;
                  }
                  if(this.formData.plat_cd !== 'N000834100' && !this.englishTxt) {
                    this.$message.error('当前店铺英文数据类型必填');
                    return;
                  }
                    var data1 = this.chineseTxt.split(/[\r\n]+/).filter(res => res);
                    var data2 = this.englishTxt.split(/[\r\n]+/).filter(res => res);
                    this.formData.content_cn = data1.map(res => {
                        return {
                            date: res.split('+')[0],
                            status_description: res.split('+')[1],
                        }
                    })
                    this.formData.content_en = data2.map(res => {
                        return {
                            date: res.split('+')[0],
                            status_description: res.split('+')[1],
                        }
                    })
                    var reg = new RegExp(/^[1-9]\d{3}(-|\/)(0[1-9]|1[0-2])(-|\/)(0[1-9]|[1-2][0-9]|3[0-1])\s+(20|21|22|23|[0-1]\d):[0-5]\d:[0-5]\d$/);
                    var isstop = true;
                    this.formData.content_cn.forEach(element => {
                        
                        if(!reg.test(element.date)){
                            this.$message.error('中文数据类型时间格式错误');
                            isstop = false;
                            return false;
                        }
                    });
                    this.formData.content_en.forEach(element => {
                        if(!reg.test(element.date)){
                            this.$message.error('英文数据类型时间格式错误');
                            isstop = false;
                            return false;
                        }
                    });
                    if(isstop) {
                        console.log(this.formData);
                        axios.post('index.php?g=logistics&m=ThrApi&a=manual_create', this.formData).then(res => {
                            console.log(res);
                            if(res.data.code === 200) {
                                this.$message({
                                    type: 'success',
                                    message: '新增成功'
                                });
                                
                                this.manualAdd = false;
                                this.onSubmit();
                            } else {
                                this.$message({
                                    type: 'error',
                                    message: res.data.msg
                                });
                            }
                        })
                        // 订单号物流号验证
                        // let param = {
                        //     type: "2",
                        //     search_data: this.formData.tracking_number,
                        //     plat_cd: this.formData.plat_cd
                        // }
                        // axios.post('index.php?g=logistics&m=ThrApi&a=get_order_associate', param).then(res => {
                        //     console.log(res);
                        //     if(res.data.code === 200 && res.data.data && this.formData.order_id === res.data.data){
                                
                        //     } else {
                        //         this.$message({
                        //             type: 'error',
                        //             message: '当前店铺和物流运单号、第三方订单id不匹配'
                        //         });
                        //     }
                        // })
                    }
                } else {
                  return false;
                }
            });
        },
        add() {
            this.manualAdd = true;
        },
        dele(row) {
            let param = {
                id: row.id
            }
            this.$confirm('此操作将永久删除该条数据, 是否继续?', '提示', {
                confirmButtonText: '确定',
                cancelButtonText: '取消',
                type: 'warning'
            }).then(() => {
                axios.post('index.php?g=logistics&m=ThrApi&a=delete_data', param).then(res => {
                    console.log(res);
                    if(res.data.code === 200) {
                        this.$message({
                            type: 'success',
                            message: '删除成功!'
                        });
                        this.onSubmit();
                    } else {
                        this.$message({
                            type: 'error',
                            message: res.data.msg
                        }); 
                    }
                })
            }).catch(() => {
                this.$message({
                    type: 'info',
                    message: '已取消删除'
                });          
            });
        },
        // 获取物流平台和物流状态码
        getCommonData() {
            var _this = this;
            axios.post('/index.php?g=oms&m=CommonData&a=commonData', {
                'data': {
                    'query': {
                        'logisticsCompany': true,
                    },
                    "type": "sorting"
                }
            }).then(function(res) {
                console.log(res);
                if (res.data.code == 2000) {
                    _this.logisticsCompany = res.data.data.logisticsCompany;
                    // _this.logicStatus = res.data.data.logicStatus.success;
                }
            })
        },
        getStatus() {
            let _this = this;
            axios.get('/index.php?g=universal&m=dictionary&a=getDictionaryList&&prefix=N00127&&need_count=1').then(function(res) {
                console.log(res);
                if (res.data.code == 200) {
                    var arr = []
                    for (let i in res.data.data.N00127) {
                        // let o = {};
                        // o[i] = res.data.data.N00127[i];
                        arr.push(res.data.data.N00127[i])
                    }
                    _this.logicStatus = arr;
                    _this.logicStatusType = '1'
                    console.log(_this.logicStatus);
                }
            })
        },
        // 查看物流
        hideChange() {
            this.trackIndex = 4;
        },
        // 展开/收起物流轨迹
        seeTrack: function() {
            this.trackIndex = this.trackData.length;
        },
        closeTrack: function() {
            this.trackIndex = 4;
        },
        // 切换页面是关闭弹窗
        handleScroll() {
            console.log(document.documentElement.scrollTop)
            // if (document.documentElement.scrollTop === 0) {
            //     for (var i = 0;i<document.getElementsByClassName('el-popover').length; i++) {
            //     document.getElementsByClassName('el-popover')[i].style.display = 'none';
            //     }
            // }
        },
        // 获取物流轨迹
        getTrack: function (item) {
            this.trackData = [];
            var _this = this;
            var param = {
                data: {
                    query: {
                        orderId: item.order_no,
                        trackingNumber: item.logistics_no,
                        platCd: item.plat_cd
                    }
                }
            };
            axios.post("/index.php?g=oms&m=OutStorage&a=feeding", param).then(function (res) {
                console.log(res);
                
                var weekArray = new Array("星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六");
                if (res.data.code == 2000) {
                    _this.trackData = res.data.data.pageData;
                    if (_this.trackData) {
                    _this.trackData.forEach(function (ele, ind) {
                        _this.$set(ele, "week", weekArray[new Date(ele.date.split(' ')[0]).getDay()]);
                        _this.$set(ele, "day", ele.date.split(' ')[0]);
                        _this.$set(ele, "time", ele.date.split(' ')[1]);
                    });
                    _this.trackData.reverse();
                    }
                }
            });
        },
        onSearch:function(){
            this.pageIndex = 1
            this.pageSize = 10
            this.onSubmit()
        },
        // 查询
        onSubmit() {
            this.loading = true;
            let param = {
                search: {
                    api_platform_cd: this.search.api_platform_cd, // api对接平台cd
                    logistics_status_cd: this.search.logistics_status_cd, // 物流状态
                    company: this.search.company, // 物流公司
                    updated_at: { // 查询数据的时间
                        start: this.search.date[0] ? this.search.date[0] : '', // 开始时间
                        end: this.search.date[1] ? this.search.date[1] : '' // 结束时间
                    },
                    type: this.search.type, // 类型，order_no为订单号，logistics_no为物流运单号
                    value: this.search.value.length > 0 ? this.search.value.split(",") : [], // 订单号
                },
                pages: { // 分页
                    current_page: this.pageIndex, // 当前页码
                    per_page: this.pageSize, // 每页显示数量
                }
            };
            // console.log(param);
            var _this = this;
            axios.post("index.php?g=logistics&m=ThrApi&a=get_list_data", param).then(function(res) {
                console.log(res);
                _this.loading = false;
                if (res.status === 200 && res.data.code === 200) {
                    _this.tableData = res.data.data.data;
                    _this.total = Number(res.data.data.pages.total);
                    _this.average = res.data.data.amount.average;
                    _this.apiTotal = res.data.data.amount.total;
                    // console.log(_this.tableData)
                };
            })
        },
        //导出功能
        exportOrder: function () {
            var _this = this
            var param = {
                search: {
                    api_platform_cd: this.search.api_platform_cd, // api对接平台cd
                    logistics_status_cd: this.search.logistics_status_cd, // 物流状态
                    company: this.search.company, // 物流公司
                    updated_at: { // 查询数据的时间
                        start: this.search.date[0] ? this.search.date[0] : '', // 开始时间
                        end: this.search.date[1] ? this.search.date[1] : '' // 结束时间
                    },
                    type: this.search.type, // 类型，order_no为订单号，logistics_no为物流运单号
                    value: this.search.value.length > 0 ? this.search.value.split(",") : [], // 订单号
                },
                pages: { // 分页
                    current_page: this.pageIndex, // 当前页码
                    per_page: this.pageSize, // 每页显示数量
                }
            };

            var tmep = document.createElement('form');
             tmep.action = '/index.php?g=logistics&m=ThrApi&a=get_report_data';
             tmep.method = "post";
             tmep.style.display = "none";
             var opt = document.createElement("input");
             opt.name = 'post_data';
             opt.value = JSON.stringify(param);
             tmep.appendChild(opt);
             document.body.appendChild(tmep);
             tmep.submit();
             tmep.remove();

        },
        // 重置
        reast() {
            // this.search =  {
            //     api_platform_cd: 'N002850001', // api对接平台cd
            //     date: [], // 查询数据的时间
            //     type: 'order_no', // 类型，order_no为订单号，logistics_no为物流运单号
            //     value: '', // 订单号
            // };
            this.search.value = ''
            this.search = {
                api_platform_cd: 'N002850001', // api对接平台cd
                type: 'order_no', // 类型，order_no为订单号，logistics_no为物流运单号
                value: '', // 订单号
                logistics_status_cd:'',//物流状态
                company:'',//物流公司
            }
            this.search.date = this.setDefultDate();
            this.pageIndex = 1
            this.pageSize = 10

            this.onSubmit();
        },
        // 回车键触发
        keyup(event) {
            this.pageIndex = 1
            this.pageSize = 10
            this.onSubmit();
        },
        // 翻页
        SizeChange(size) {
            // console.log(size)
            this.pageSize = size;
            this.onSubmit();
        },
        CurrentChange(index) {
            // console.log(index);
            this.pageIndex = index;
            this.onSubmit();
        },
        // 获取平台筛选选项和店铺选项
        getOpetion() {
            let _this = this;
            axios.post("/index.php?g=common&m=index&a=get_cd", {
                cd_type: {
                    logics_platform_type: "true",
                    plat: "true",
                    logic_status: "true",
                    logics_platform_friend_status: "true",
                }
            }).then(function(res) {
                // console.log(res);
                if (res.status === 200 && res.data.code === 2000) {
                    _this.platformOption = res.data.data.logics_platform_type;
                    _this.stores = res.data.data.plat;
                    _this.logic_status = res.data.data.logic_status;
                    var logics_platform_friend_status_data = res.data.data.logics_platform_friend_status
                    for (var item in logics_platform_friend_status_data) {
                        if(logics_platform_friend_status_data[item].ETC2 == 'state'){
                            _this.logics_platform_friend_status.push(logics_platform_friend_status_data[item])
                        }
                    }
                    // _this.logics_platform_friend_status = res.data.data.logics_platform_friend_status;
                    // console.log(_this.platformOption);
                }
            })
        },
        // 设置搜索的默认时间
        setDefultDate() {
            var day = new Date();
            var start = "";
            var end = "";
            day.setTime(day.getTime());
            console.log(day.getMonth())
            if (day.getDate() > 26) {
                start = day.getFullYear() + "-" + (day.getMonth() + 1) + '-27';
                end = day.getFullYear() + "-" + (day.getMonth() + 2) + '-26';
            } else {
                start = day.getFullYear() + "-" + day.getMonth() + '-27';
                end = day.getFullYear() + "-" + (day.getMonth()+1) + '-26';
            }

            // var start = day.getFullYear() + "-" + day.getMonth() + '-27';
            // var end = day.getFullYear() + "-" + (day.getMonth()+1) + '-26';
            return [start, end];
        },
        // 自定义合计
        getSummaries(param) {
            var { columns, data } = param;
            var sums = [];
            let _this = this;
            columns.forEach(function(column, index){
            if (index === 0) {
                sums[index] = _this.$lang('合计') + '：';
                }
              if (index === 6) {
                sums[index] = _this.apiTotal;
              }
            //   const values = data.map(item => Number(item[column.property]));
            //   if (!values.every(value => isNaN(value))) {
            //     sums[index] = values.reduce((prev, curr) => {
            //       const value = Number(curr);
            //       if (!isNaN(value)) {
            //         return prev + curr;
            //       } else {
            //         return prev;
            //       }
            //     }, 0);
            //     sums[index] += ' 元';
            //   } else {
            //     sums[index] = 'N/A';
            //   }
            });
    
            return sums;
          }
    },
    mounted: function mounted() {
        // window.addEventListener('scroll', this.handleScroll)
        // window.addEventListener('style', this.handleScroll)
    },
    created () {
        console.log(this.setDefultDate());
        this.search.date = this.setDefultDate();
        this.getOpetion();
        this.onSubmit();
        this.getCommonData();
        this.getStatus();
    }
})