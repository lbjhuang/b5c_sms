'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var VM = new Vue({
    el: '#order-list',
    components: {},
    data: {
        remarkHave: 0,
        form: {
            'third_order_number': ''
        },
        tableLoading: true,
        //查询条件
        searchData: {
            search_condition: "thr_order_no",
            search_value: '',
            search_time_type: "order_time",
            search_time_left: '',
            search_time_right: '',
            sort: 'order_time',
            sort_type: 'desc',
            is_apply_after_sale: '',
            is_remote_area_val: '',
            after_sale_status: [],
            buyer_user_id:'',
            brand_type: [],
            logistics_abnormal_status:'' //发货状态即物流状态
        },
        page: {
            page_count: 10,
            this_page: 1
        },
        channels: {}, //平台渠道
        channelData: [],
        siteData: [],
        showMore: false,
        sites: [],
        isChannelALL: true,
        orderStatus: {}, //订单状态
        orderData: [],
        isOrderALL: true,
        dispatchStatus: {}, //派单状态
        dispatchData: [],
        orderSourceStatus: {}, //订单来源
        orderSourceData: [],
        afterSalesTypeData: [],
        isDispatchALL: true,
        sort: {}, //排序方式
        sortData: '',
        countryStatus: {}, //国家
        selectedCountries: [],
        shopStatus: {}, //店铺
        selectedShops: [],
        warehouseStatus: {}, //仓库
        selectedWarehouses: [],
        logisticsCompanyStatus: {}, //物流公司
        selectedLogisticsCompanys: [],
        shippingMethodsStatus: [], //物流方式
        shippingMethodsStatusTemp: [],
        selectedShippingMethods: [],
        salesTeamStatus: {}, //销售团队
        selectedSalesTeam: [],
        brandTypeCodes: [
            {CD: '1',CD_VAL: 'ODM'},
            {CD: '2',CD_VAL: '非ODM'},
            {CD: '3',CD_VAL: '包含ODM'},
            {CD: '4',CD_VAL: '包含非ODM'},
            {CD: '5',CD_VAL: '其他'},
        ], // 品牌类型
        brandType: [],
        downQueryStatus: {}, //查询条件
        selectedDownQueryTemp: 'thr_order_no', //查询条件 临时
        selectedDownQuery: 'thr_order_no',
        searchKeywords: '',
        buyer_user_id:'',
        logistics_abnormal_status:'',
        searchKeywordsTemp: '',
        timeSort: 'order_time',
        checkKpi: [], //是否算kpi
        oldCheckKpi: [],
        totalCount: 0, //总条数
        tableData: [], //查询返回数据
        tableDataTemp: [],
        multipleSelection: [],
        dialogVisible: false,
        downloadDialog: false,
        operateRemark: '', //运营备注
        sortType: 'desc',
        msgCd1: '',
        is_apply_after_sale: '',
        is_remote_area_val: '',
        // 日期选择
        dateRange: '',
        pickerOptions: {

        },
        afterSalesMore: true,
        afterSalesArr: [],
        dataSum: '', // 汇总usd数据
        isdataSum: true,
        sumLoding: false,
        loading: false,
        exportOrderDialog: false, // 按模板导出订单弹窗
        InvoiceDialog: false, // 选择生成发票格式弹窗
        invoiceFormatOne: true,
        invoiceFormatTwo: true, // pdf
        queryLoading: false,
        downloadInvoiceDialog: false,
        commitIds: [], // 支持生成发票的id
        invoiceIds: [], // 已生成发票的id
        templateExportData: {
            export_type: '1',
            template_id: ''
        },
        templateData: [{name:'请选择模板',children: [{name: '我创建的模板',children: []},{name: '他人创建的模板', children: []}]}],
        defaultProps: {
            children: 'children',
            label: 'name'
        },
        field_array: [],
        fieldLength: 0,
        erport_btn_loading: false
    },
    methods: {
        // 显示按模板导出弹窗
        exportTemplateShow() {
            this.exportOrderDialog = true;
            this.getTemplateData();
        },
        // 获取导出模板
        getTemplateData() {
            this.fieldLength = '';
            this.field_array = '';
            this.templateExportData.template_id = '';
            axios.get('/index.php?g=OMS&m=OrderExportTemplate&a=get_export_template').then(res => {
                console.log(res)
                if(res.data.code == 2000) {
                    // 手动赋值树形数据
                    this.templateData = [
                        {
                            name: this.$lang('请选择模板'),
                            children: [
                                {
                                    name: this.$lang('我创建的模板'),
                                    children: res.data.data.oneself // 我的模板
                                },
                                {
                                    name: this.$lang('他人创建的模板'),
                                    children: res.data.data.other_people // 他人模板
                                }
                            ]
                        }      
                    ]
                } else {
                    this.$message.error(this.$lang(res.data.msg))
                }
            }).catch(err => {
                console.log(err)
            })
        },
        // 新增导出模板
        addTemplate() {
            newTab("/index.php?g=oms&m=order&a=export_template_list&type=1", this.$lang('订单导出模板'));
        },
        // 选择导出模板
        handleNodeClick(item) {
            console.log(item)
            if(item.id) {
                let rows = Math.ceil(item.field_json.length/5);
                this.fieldLength = rows *5;
                this.field_array = item.field_json;
                this.templateExportData.template_id = item.id;
            }
        },
        // 按模板导出弹窗关闭
        exportOrderClose() {
            this.templateExportData.template_id = '';
            this.fieldLength = '';
            this.field_array = '';
        },
        // 按模板导出
        tmeplateExport() {
            if(!this.templateExportData.template_id ) {
                this.$message.warning(this.$lang('请先选择导出模板'));
                return false;
            }
            var siteData = [];
            if (!this.siteData.length) {
                this.sites.forEach(function(el) {
                    siteData.push(el.CD)
                })
            } else {
                siteData = this.siteData
            }
            var where = {
                wheres: {
                    platform: siteData,
                    order_status: this.orderData,
                    dispatch_status: this.dispatchData,
                    logistics_status: this.dispatchData,
                    sort: this.sortData,
                    remark_have: this.remarkHave,
                    country: this.selectedCountries,
                    shop: this.selectedShops,
                    warehouse: this.selectedWarehouses,
                    logistics_company: this.selectedLogisticsCompanys,
                    logistics_method: this.selectedShippingMethods,
                    order_source_status: this.orderSourceData,
                    sales_team: this.selectedSalesTeam,
                    search_time_type: this.timeSort,
                    search_time_left: this.dateRange[0],
                    search_time_right: this.dateRange[1],
                    search_condition: this.selectedDownQuery,
                    search_value: this.searchKeywords,
                    buyer_user_id: this.buyer_user_id,
                    sku_is_null: this.searchData.sku_is_null,
                    is_apply_after_sale: this.is_apply_after_sale,
                    is_remote_area_val: this.is_remote_area_val,
                    after_sale_status: this.searchData.after_sale_status,
                    // brand_type: this.searchData.brand_type,
                },
                ids: [],
                export_template_id: this.templateExportData.template_id
            };
            for (var i = 0; i < this.multipleSelection.length; i++) {
                where.ids.push(this.multipleSelection[i].id);
            }
            this.erport_btn_loading = true;
            axios.post('/index.php?g=OMS&m=OrderExportTemplate&a=export',where).then(res => {
                console.log(res)
                this.exportOrderDialog = false;
                this.erport_btn_loading = false;
                if(res.data.code == 2000) {
                    this.downloadDialog = true;
                } else {
                    this.$message.warning(this.$lang(res.data.msg));
                }
            }).catch(err => {
                console.log(err)
            })
        },
        // 查询数据汇总
        seeDataSum() {
            let _this = this;
            let param = {
                data: _this.searchData
            }

            console.log(param);
            _this.sumLoding = true;
            axios.post('/index.php?g=OMS&m=Order&a=getDollarAmount', param).then(function(res) {
                console.log(res);
                _this.sumLoding =  false;
                if (res.data.status == 200000) {
                    _this.isdataSum = false;
                    _this.dataSum  =  res.data.data  ||  '0.00';
                    _this.dataSum = _this.format(Number(_this.dataSum).toFixed(2));
                } else {
                    _this.$message.error(res.data.info);
                }

            });
        },
        commonData: function() {
            var _this = this;
            axios.post('/index.php?g=OMS&m=afterSale&a=mixAfterSaleStatus').then(function(res) {

                if (res.data.code == 200) {
                    _this.afterSalesArr = res.data.data.status
                }
            })
        },
        getFloat(num, n) {
            n = n ? parseInt(n) : 0;
            if(n <= 0) {
                return Math.round(num);
            }
            num = Math.round(num * Math.pow(10, n)) / Math.pow(10, n); //四舍五入
            num = Number(num).toFixed(n); //补足位数
            return num;
        },
        // 数字加上千分位
        format(num) {
            var num1 = num.split('.')[0] + ''; //数字转字符串
            var str = ""; //字符串累加
            for (var i = num1.length - 1, j = 1; i >= 0; i--, j++) {
                if (j % 3 == 0 && i != 0) { //每隔三位加逗号，过滤正好在第一个数字的情况
                    str += num1[i] + ","; //加千分位逗号
                    continue;
                }
                str += num1[i]; //倒着累加数字
            }
            return str.split('').reverse().join("") + '.' + num.split('.')[1]; //字符串=>数组=>反转=>字符串
        },
        //搜索条件数据
        getOrderListMenu: function getOrderListMenu() {
            var _this = this;
            axios.get('/index.php?g=OMS&m=Order&a=listMenu').then(function(response) {
                console.log(response)
                var data = response.data;
                if (data.status != 200000) {
                    this.$message.error(_this.$lang(data.msg));
                } else {
                    _this.channels = data.data.site_cd; //平台渠道
                    _this.orderStatus = data.data.order_status; //订单状态
                    _this.logisticsStatus = data.data.logistics_status; //物流状态
                    // _this.dispatchStatus = data.data.dispatch_status; //派单状态
                    _this.orderSourceStatus = data.data.order_source_status; //订单来源
                    _this.aftermarketStatus = data.data.aftermarket_status; //售后状态
                    _this.countryStatus = data.data.country_status; //国家
                    _this.shopStatus = data.data.shop_status; //店铺
                    _this.warehouseStatus = data.data.warehouse_status; //仓库
                    _this.logisticsCompanyStatus = data.data.logistics_company_status; //物流公司
                    data.data.shipping_methods_status.forEach(function(ele) {
                        _this.shippingMethodsStatus.push(ele);
                    });
                    _this.shippingMethodsStatusTemp = JSON.parse(JSON.stringify(_this.shippingMethodsStatus));
                    //物流方式
                    _this.salesTeamStatus = data.data.sales_team_status; //销售团队
                    _this.downQueryStatus = data.data.down_query_status; //查询条件
                    _this.sort = data.data.sort; //排序方式
                    $('#order-list').css('visibility', 'visible');
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        getOrderList: function getOrderList(data, type) {
            console.log("查询条件参数",data);
            if(!this.searchKeywords){
                data.search_value="";
            }
            // 发货状态
            // let logisticStatus = [];
            // if(this.logistics_abnormal_status){
            //     logisticStatus.push(this.logistics_abnormal_status);
            // }
            // data.logistics_abnormal_status = logisticStatus;
            var _this = this;
            _this.tableData = []
            var param = {
                data: data,
                page: this.page,
            }
            _this.tableLoading = true;
            // _this.loading = true;
            if (type == 'all') {
                data.platform = [];
                this.sites.forEach(function(element) {
                    data.platform.push(element.CD)
                })
            }
            this.isdataSum = true; // 查询数据是隐藏数据汇总
            console.log(param)
            axios.post('/index.php?g=OMS&m=Order&a=lists', param).then(function(response) {
                // _this.loading = false;
                if (response.data.status == 200000) {
                    _this.tableData = _this.sites.length || !type ? response.data.data.data : [];

                    _this.totalCount = _this.sites.length || !type ? parseInt(response.data.data.page.count) : 0;
                    setTimeout(function() {
                        _this.tableLoading = false;
                    }, 1000);
                } else {
                    _this.$message.error(_this.$lang(response.data.info));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        btnIsshow: function(dispatch, order_status, child_order,row) {
            switch (dispatch) {
                case 'N001820100':
                case 'N001820900':
                case 'N001821100':
                    //待预派
                    //已出库
                    //订单取消
                    var status = ['N000550300', 'N000550400', 'N000550500', 'N000550600', 'N000550800', 'N000550900', 'N000551000', 'N000551004'];
                    if (status.indexOf(order_status) === -1) {
                        return false;
                    }
                    break;
                case 'N001821000':
                    //待派单
                    var status = ['N000550400', 'N000550900', 'N000551000', 'N000551004'];
                    if (status.indexOf(order_status) === -1) {
                        return false;
                    }
                    break;
                case 'N001820500':
                case 'N001820600':
                case 'N001820700':
                case 'N001820800':
                    //待拣货
                    //待分拣
                    //待核单
                    //待出库
                    var status = ['N000550400', 'N000550500', 'N000551004'];
                    if (status.indexOf(order_status) === -1) {
                        return false;
                    }
                    break;
            }
            if (child_order && row.platform.indexOf('Gshopper') !== -1 ) {
                return false
            }
            // 待付款不需要展示
            if(order_status == 'N000550300' || order_status == 'N000551001') {
                return false
            }
            // shopnc订单且是待付款、待发货、交易超时自动取消状态不展示
            if(row.is_shopnc_order == 1 && (order_status == 'N000550300' || order_status == 'N000551001' || order_status == 'N000550400')) {
                return false
            }
            return true;
        },
        // select_is_apply_after_sale:function(val){

        // },
        selectCompany: function selectCompany() {
            var _this = this;
            axios.post("index.php?g=Oms&m=OrderPresent&a=get_log_company", { company_code: _this.selectedLogisticsCompanys }).then(function(res) {
                if (res.data.code == 200) {
                    if (res.data.data == null) {
                        _this.shippingMethodsStatus = _this.shippingMethodsStatusTemp;
                        console.log(_this.shippingMethodsStatus);
                    } else {
                        res.data.data.forEach(function(ele) {
                            ele.id = ele.ID;
                            ele.logisticsMode = ele.CD_VAL;
                        });
                        _this.shippingMethodsStatus = res.data.data;
                    }
                } else {
                    _this.shippingMethodsStatus = _this.shippingMethodsStatusTemp;
                }
            });
        },
        getStore: function getStore() {
            var _this = this;
            var postData = [];
            if (!this.siteData.length) {
                this.sites.forEach(function(element) {
                    postData.push(element.CD)
                })
            } else {
                postData = this.siteData
            }
            axios.post('/index.php?g=Oms&m=Order&a=storesGet', {
                "plat_form": postData
            }).then(function(response) {
                if (response.data.status == 200000) {
                    VM.shopStatus = response.data.data;
                } else {
                    VM.$message.error(_this.$lang(response.data.info));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        selectSite: function selectSite(item) {
            console.log("选择站点");
            var _this = this;
            if (!item) {
                this.siteData = [];
                for (var i in this.sites) {
                    this.sites[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    this.siteData.push(item.CD);
                } else {
                    this.siteData.forEach(function(val, index) {
                        if (val == item.CD) {
                            _this.siteData.splice(index, 1);
                        }
                    });
                }
            }
            if (!this.siteData.length) {
                this.getOrderList(this.searchData, 'all');
            } else {
                this.searchData.platform = this.siteData;
                this.doSearch();
            }
            this.getStore();
        },
        submit_keywords: function() {
            this.doSearch()
        },
        selectChannel: function selectChannel(item) {
            var _this = this;
            if (!item) {
                this.channelData = [];
                for (var i in this.channels) {
                    this.channels[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    this.channelData.push(item.CD);
                } else {
                    this.channelData.forEach(function(val, index) {
                        if (val == item.CD) {
                            _this.channelData.splice(index, 1);
                        }
                    });
                }
            }
            this.sites = [];
        },
        getSite: function getSite(type, val) {
            var _this = this;
            axios.post("/index.php?g=oms&m=order&a=getSite", { plat_cd: type === 'search' ? val : [] }).then(function(res) {
                if (res.data && res.data.code == 2000) {
                    _this.sites = res.data.data ? res.data.data : [];
                } else {
                    _this.$message.error(res.data.msg || '获取站点异常');
                }
                if (type === 'search') {
                    _this.getOrderList(_this.searchData, 'all');
                }
                _this.getStore();
            }).catch(()=>{
                _this.$message.error('获取站点异常');
                if (type === 'search') {
                    _this.getOrderList(_this.searchData, 'all');
                }
                _this.getStore();
            });
        },
        orderALL: function orderALL() {
            $('.order-item').each(function() {
                $(this).removeClass('active');
            });
            this.isOrderALL = true;
            this.orderData = [];
        },
        selectOrder: function selectOrder(item) {
            var _this = this;
            if (!item) {
                this.orderData = [];
                for (var i in this.orderStatus) {
                    this.orderStatus[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    this.orderData.push(item.CD);
                } else {
                    this.orderData.forEach(function(val, index) {
                        if (val == item.CD) {
                            _this.orderData.splice(index, 1);
                        }
                    });
                }
            }
        },
        dispatchALL: function dispatchALL() {
            $('.dispatch-item').each(function() {
                $(this).removeClass('active');
            });
            this.isDispatchALL = true;
            this.dispatchData = [];
        },
        selectOrderSource: function selectOrderSource(item) {
            var _this = this;
            if (!item) {
                this.orderSourceData = [];
                for (var i in this.orderSourceStatus) {
                    this.orderSourceStatus[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    this.orderSourceData.push(item.CD);
                } else {
                    this.orderSourceData.forEach(function(val, index) {
                        if (val == item.CD) {
                            _this.orderSourceData.splice(index, 1);
                        }
                    });
                }
            }
        },
        // 售后类型
        afterSalesType: function afterSalesType(item) {

            var _this = this;
            var afterSalesArr = _this.afterSalesArr

            if (!item) {
                _this.afterSalesTypeData = [];
                for (var i in afterSalesArr) {
                    for (var j in afterSalesArr[i]) {
                        afterSalesArr[i][j].checked = false;
                    }
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    _this.afterSalesTypeData.push(item.CD);
                } else {
                    _this.afterSalesTypeData.forEach(function(val, index) {
                        if (val == item.CD) {
                            _this.afterSalesTypeData.splice(index, 1);
                        }
                    });
                }
            }

        },
        applyAfterSales: function(val) {
            console.log(val);
            
            var dom = document.createElement('a');
            var _href = "/index.php?g=OMS&m=Order&a=applyAfterSales&order_no=" + val.order_id + "&thrId=" + val.third_party_order_number + "&platCode=" + val.plat_cd + "&paymentDate=" + val.payment_time + "&pay_the_total_price=" + val.pay_the_total_price + "&currency=" + val.currency + "&type=apply";
            // var _href = "/index.php?g=OMS&m=Order&a=applyAfterSales&order_no=" + val.third_party_order_number + "&thrId=" + val.third_order_number + "&platCode=" + val.plat_cd + "&paymentDate=" + val.payment_time + "&pay_the_total_price=" + val.pay_the_total_price + "&currency=" + val.currency + "&type=apply";
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('申请售后') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        selectSort: function selectSort() {
            if ($(event.srcElement).hasClass('active')) {
                this.sortType = this.sortType == 'desc' ? 'asc' : 'desc';
            }
            $(event.srcElement).siblings().removeClass('active').end().addClass("active");
            this.sortData = event.srcElement.getAttribute('data-cd');
        },
        toggleRemark: function toggleRemark() {
            $(event.srcElement).toggleClass('isActive');
            this.remarkHave = this.remarkHave == 0 ? 1 : 0;
        },
        toggleError: function toggleError() {
            $(event.srcElement).toggleClass('isActive');
            this.msgCd1 = this.msgCd1 == 0 ? 1 : 0;
        },
        //前往下载列表
        toDownload: function toDownload() {
            this.downloadDialog = false;
            var dom = document.createElement('a');
            var _href = "/index.php?g=Home&m=Excel&a=excel_list";
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('下载列表') + "')");
            dom.setAttribute("_href", _href);
            dom.click();

        },
        //跳转详情页
        toDetail: function toDetail(order_no, thirdId, platCD) {
            // sessionStorage.setItem("orderListOffsetTop", document.documentElement.scrollTop);
            var dom = document.createElement('a');
            var _href = "/index.php?g=OMS&m=Order&a=orderDetail&order_no=" + order_no + "&thrId=" + thirdId + "&platCode=" + platCD+"&isShowEditButtonByOmsListEntry="+false;
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('订单详情') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // 查询功能
        doSearch: function doSearch() {
            this.searchKeywordsTemp = this.searchKeywords;
            this.selectedDownQueryTemp = $.trim(this.selectedDownQuery);
            console.log("searchKeywords值",this.searchKeywordsTemp);
            console.log("selectedDownQuery值",$.trim(this.selectedDownQuery));
            console.log("-------------------------------------");
            this.$set(this.searchData, "search_value", $.trim(this.searchKeywordsTemp));
            this.$set(this.searchData, 'search_condition', this.selectedDownQueryTemp);
            console.log("搜索数据",this.searchData);
            this.getOrderList(this.searchData);
        },

        //重置功能
        doReset: function doReset() {
            window.location.reload();
        },
        //备注
        doRemark: function doRemark(row) {
            this.form = JSON.parse(JSON.stringify(row));
            this.formTemp = JSON.parse(JSON.stringify(this.form));
            this.dialogVisible = !this.dialogVisible;
        },
        saveRemark: function saveRemark(ind, row) {
            var _this = this;
            var data = {
                'thr_order_id': _this.form.third_order_number,
                'plat_cd': _this.form.plat_cd,
                'remarks_type': 'operate',
                'remarks_msg': _this.form.remarks
            };

            axios.post('/index.php?g=oms&m=order&a=orderRemarks', data).then(function(response) {
                if (response.data.status == 200000) {
                    _this.dialogVisible = !_this.dialogVisible;
                    setTimeout(function() {
                        _this.getOrderList(_this.searchData);
                    }, 1000);
                } else {
                    _this.$message.error(_this.$lang(response.data.info));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        cancelRemark: function cancelRemark() {

            this.dialogVisible = !this.dialogVisible;
            // this.form = JSON.parse(JSON.stringify(this.formTemp))
            this.form = Object.assign({}, this.formTemp);
        },
        //是否为空
        isNone: function isNone(dom) {
            var num = 0;
            dom.each(function() {
                if ($(this).hasClass('active')) num += 1;
            });
            return num == 0 ? true : false;
        },
        isShow: function(index, val) {
            if (index == 0) {
                return true
            } else if (index != 0 && val.is_show_all_sku == true) {
                return true
            } else {
                return false
            }
        },
        //是否在数组中
        isInArray: function isInArray(arr, value) {
            var index = $.inArray(value, arr);
            if (index >= 0) {
                return true;
            }
            return false;
        },
        //删除未知下标元素
        deleteEle: function deleteEle(arr, value) {
            arr.splice($.inArray(value, arr), 1);
        },

        // 搜索条件选择
        doCheck: function doCheck(arr, val) {

            //如果有删除，没有则添加
            if (this.isInArray(arr, val)) {
                this.deleteEle(arr, val);
            } else {
                arr.push(val);
            }
        },
        // 下载删除模板
        exportDelete() {
            var downUrl = window.location.host === 'erp.gshopper.com' ? 'http://erp.gshopper.com/index.php?m=order_detail&a=download&file=BR_SYS_FILE_20200219121102_5354.xlsx' : 'http://erp.gshopper.stage.com/index.php?m=order_detail&a=download&file=BR_SYS_FILE_20200219100611_8789.xlsx'
            window.open(downUrl);
        },
        //导出功能
        exportOrder: function exportOrder() {
            var _this = this;
            var siteData = [];
            if (!this.siteData.length) {
                this.sites.forEach(function(el) {
                    siteData.push(el.CD)
                })
            } else {
                siteData = this.siteData
            }
            var where = {
                wheres: {
                    platform: siteData,
                    order_status: this.orderData,
                    dispatch_status: this.dispatchData,
                    logistics_status: this.dispatchData,
                    sort: this.sortData,
                    remark_have: this.remarkHave,
                    country: this.selectedCountries,
                    shop: this.selectedShops,
                    warehouse: this.selectedWarehouses,
                    logistics_company: this.selectedLogisticsCompanys,
                    logistics_method: this.selectedShippingMethods,
                    order_source_status: this.orderSourceData,
                    sales_team: this.selectedSalesTeam,
                    search_time_type: this.timeSort,
                    search_time_left: this.dateRange[0],
                    search_time_right: this.dateRange[1],
                    search_condition: this.selectedDownQuery,
                    search_value: this.searchKeywords,
                    buyer_user_id: this.buyer_user_id,
                    sku_is_null: this.searchData.sku_is_null,
                    is_apply_after_sale: this.is_apply_after_sale,
                    is_remote_area_val: this.is_remote_area_val,
                    after_sale_status: this.searchData.after_sale_status,
                    brand_type: this.searchData.brand_type,
                },
                ids: []
            };
            for (var i = 0; i < this.multipleSelection.length; i++) {
                where.ids.push(this.multipleSelection[i].id);
            }
            axios.post('/index.php?g=OMS&m=Order&a=checkExportOrder', where).then(function(res) {
                _this.loading = false;

                if (res.data.code == 200) {
                    if (res.data.is_hint) {
                        _this.downloadDialog = true;
                    } else {
                        var tmep = document.createElement('form');
                        tmep.action = '/index.php?g=OMS&m=Order&a=exportOrder';
                        tmep.method = "post";
                        tmep.style.display = "none";
                        var opt = document.createElement("input");
                        opt.name = 'post_data';
                        opt.value = JSON.stringify(where);
                        tmep.appendChild(opt);
                        document.body.appendChild(tmep);
                        tmep.submit();
                        $(tmep).remove();
                        tmep = null
                    }
                } else {
                    _this.$message.error(res.data.info);
                }
            }).catch(function(err) {
                console.log(err);
            });


        },
        // 批量生成发票
        batchGenerateInvoice() {
            if(this.multipleSelection.length === 0) {
                this.$message.warning(this.$lang('请选择需要生成发票的数据'));
                 return false;
            }
            const array = this.multipleSelection.filter(item => item.can_export_invoice == 1);
            if(array.length === 0) {
                this.$message.warning(this.$lang('选择的订单暂不支持批量生成发票'));
                this.$refs['multipleTable'].clearSelection();
                 return false;
            }
            const ids = array.map(item => {
                return item.id;
            })
            this.commitIds = ids;
            this.InvoiceDialog = true;
        },
        // 关闭生成发票弹窗
        invoiceClose() {
            this.$refs['multipleTable'].clearSelection();
        },
        // 确认生成发票
        queryInvoice() {
            if(!this.invoiceFormatOne && !this.invoiceFormatTwo) {
                this.$message.warning(this.$lang('请选择需要生成发票的格式'));
                return false;
            }
            let arr = [];
            if(this.invoiceFormatOne) { // 选择xls格式
                arr.push(0); 
            }
            if(this.invoiceFormatTwo) { // 选择PDF格式
                arr.push(1); 
            }
            let param = {
                "ids":this.commitIds,
                "types":arr
            };
            this.queryLoading = true;
            axios.post('/index.php?g=OMS&m=Order&a=exportOrderInvoiceTask', param).then((res) => {
                console.log(res);
                this.InvoiceDialog = false; // 关闭当前弹窗
                this.queryLoading = false;
                if(res.data.code === 2000) {
                    this.downloadInvoiceDialog = true; // 开启下一个弹窗
                    this.invoiceIds = res.data.data.ids;
                } else {
                    this.$message.warning(this.$lang(res.data.msg)); 
                }
            })
        },
        downClose() {
            this.$refs['multipleTable'].clearSelection();
        },
        // 前往发票页
        toInvoice() {
            this.downloadInvoiceDialog = false;
            var dom = document.createElement('a');
            var _href = "/index.php?m=excel&a=invoice_list";
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('发票列表') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // 导出销售报表
        exportSalesReport: function() {
            this.searchKeywordsTemp = this.searchKeywords;
            this.selectedDownQueryTemp = $.trim(this.selectedDownQuery);
            this.$set(this.searchData, "search_value", $.trim(this.searchKeywordsTemp));
            this.$set(this.searchData, 'search_condition', this.selectedDownQueryTemp);
            var param = {
                post_data:JSON.stringify({data: this.searchData})
            }
            var config = {
                headers : {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}
            }
            axios.post('/index.php?g=OMS&m=Order&a=exportSalesReport',Qs.stringify(param),config).then(res => {
                if(res.data.code == 200) {
                    this.downloadDialog = true;
                } else {
                    this.$message.error(this.$lang(res.data.msg || '系统错误！'))
                }
            })
            // var tmep = document.createElement('form');
            // tmep.action = '/index.php?g=OMS&m=Order&a=exportSalesReport';
            // tmep.method = "post";
            // tmep.style.display = "none";
            // var opt = document.createElement("input");
            // opt.name = 'post_data';
            // opt.value = JSON.stringify(param);
            // tmep.appendChild(opt);
            // document.body.appendChild(tmep);
            // tmep.submit();
            // $(tmep).remove();
            // tmep = null
                // }






        },
        //删除订单
        exportDelOrder: function() {
            document.getElementById('activeImport').click();
        },
        handleSizeChange: function handleSizeChange(val) {
            this.page = {
                page_count: val,
                this_page: 1
            },
            this.getOrderList(this.searchData);
        },
        handleCurrentChange: function handleCurrentChange(val) {
            
            this.page.this_page = val
            this.getOrderList(this.searchData);
        },
        handleSelectionChange: function handleSelectionChange(val) {
            this.multipleSelection = val;
        },
        afterSalesMoreClick: function(type) {
            this.afterSalesMore = !this.afterSalesMore
        }

    },
    created: function created() {
        // window.onscroll = function() {
        //     if (document.documentElement.scrollTop != 0) {
        //         sessionStorage.setItem("orderListOffsetTop", document.documentElement.scrollTop);
        //     }
        // }
        this.pickerOptions = {
            shortcuts: [{
                text: this.$lang('最近一周'),
                onClick: function onClick(picker) {
                    var end = new Date();
                    var start = new Date();
                    start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                    picker.$emit('pick', [start, end]);
                }
            }, {
                text: this.$lang('最近一个月'),
                onClick: function onClick(picker) {
                    var end = new Date();
                    var start = new Date();
                    start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                    picker.$emit('pick', [start, end]);
                }
            }, {
                text: this.$lang('最近三个月'),
                onClick: function onClick(picker) {
                    var end = new Date();
                    var start = new Date();
                    start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                    picker.$emit('pick', [start, end]);
                }
            }]
        }
        this.commonData();
        this.getOrderListMenu();
        this.getSite()
        this.getOrderList(this.searchData);
    },
    watch: {
        sortType: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "sort_type", newValue);
            },
            deep: true
        },
        //自动监测搜索条件的修改
        channelData: {
            handler: function handler(newValue, oldValue) {
                this.getSite('search', newValue);
                this.siteData = [];
            },

            deep: true
        },
        brandType: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "brand_type", newValue);
            },
            deep: true
        },
        afterSalesTypeData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "after_sale_status", newValue);
            },
            deep: true
        },
        orderData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "order_status", newValue);
            },

            deep: true
        },
        dispatchData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "dispatch_status", newValue);
            },

            deep: true
        },
        orderSourceData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "order_source_status", newValue);
            },

            deep: true
        },
        sortData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "sort", newValue);
            },

            deep: true
        },
        remarkHave: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "remark_have", newValue);
            },

            deep: true
        },

        selectedCountries: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "country", newValue);
            },

            deep: true
        },
        msgCd1: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, 'sku_is_null', newValue);
            },

            deep: true
        },
        selectedShops: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "shop", newValue);
            },

            deep: true
        },
        selectedWarehouses: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "warehouse", newValue);
            },

            deep: true
        },
        selectedLogisticsCompanys: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "logistics_company", newValue);
            },

            deep: true
        },
        selectedShippingMethods: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "logistics_method", newValue);
            },

            deep: true
        },
        selectedSalesTeam: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "sales_team", newValue);
            },
            deep: true
        },
        buyer_user_id: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "buyer_user_id", newValue);
            },
            deep: true
        },
        logistics_abnormal_status: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "logistics_abnormal_status", newValue);
            },
            deep: true
        },
        is_apply_after_sale: {
            handler: function handler(newValue, oldValue) {
                if (newValue == -1) {
                    this.$set(this.searchData, "is_apply_after_sale", '');
                } else {
                    this.$set(this.searchData, "is_apply_after_sale", newValue);
                }

            },
            deep: true
        },
        is_remote_area_val: {
            handler: function handler(newValue, oldValue) {
                if (newValue == -1) {
                    this.$set(this.searchData, "is_remote_area_val", '');
                } else {
                    this.$set(this.searchData, "is_remote_area_val", newValue);
                }
            },
            deep: true
        },
        checkKpi: {
            handler: function handler(newValue, oldValue) {
                console.log(newValue);
                console.log(oldValue);
                if (newValue.includes('-1') && !oldValue.includes('-1')) {
                    this.checkKpi = ['-1', '0', '1', '2']
                    this.$set(this.searchData, "is_count_kpi", []);
                }
                if (!newValue.includes('-1') && oldValue.includes('-1')) {
                    this.checkKpi = []
                    this.$set(this.searchData, "is_count_kpi", []);
                }
                if (newValue.includes('-1') && oldValue.includes('-1') && newValue.length != 4) {
                    var index = newValue.indexOf('-1')
                    this.checkKpi.splice(index, 1)
                    this.$set(this.searchData, "is_count_kpi", this.checkKpi);
                }
                if (!newValue.includes('-1') && !oldValue.includes('-1') && newValue.length == 3) {
                    this.checkKpi = ['-1', '0', '1', '2']
                    this.$set(this.searchData, "is_count_kpi", []);
                }
                if (!newValue.includes('-1') && !oldValue.includes('-1') && newValue.length != 3) {
                    this.checkKpi = newValue
                    this.$set(this.searchData, "is_count_kpi", this.checkKpi);
                }


            },
            deep: true
        },
        timeSort: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData, "search_time_type", newValue);
            },
            deep: true
        },
        dateRange: {
            handler: function handler(newValue, oldValue) {
                if (newValue) {
                    this.$set(this.searchData, "search_time_left", newValue[0]);
                    this.$set(this.searchData, "search_time_right", newValue[1]);
                } else {
                    this.$set(this.searchData, "search_time_left", newValue);
                    this.$set(this.searchData, "search_time_right", newValue);
                }
            },
            deep: true
        },

        //搜索条件数据修改，自动请求接口
        searchData: {
            handler: function handler(newValue, oldValue) {

                this.page.this_page = 1;
                this.getOrderList(newValue);
            },
            deep: true
        }

    },
    filters: {
        formatDate: function formatDate(time) {
            var date = new Date(time);
            return _formatDate(date, 'yyyy-MM-dd hh:mm:ss');
        }
    }
});

//时间戳转日期
function _formatDate(date, fmt) {
    if (/(y+)/.test(fmt)) {
        fmt = fmt.replace(RegExp.$1, (date.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    var o = {
        'M+': date.getMonth() + 1,
        'd+': date.getDate(),
        'h+': date.getHours(),
        'm+': date.getMinutes(),
        's+': date.getSeconds()
    };
    for (var k in o) {
        if (new RegExp('(' + k + ')').test(fmt)) {
            var str = o[k] + '';
            fmt = fmt.replace(RegExp.$1, RegExp.$1.length === 1 ? str : padLeftZero(str));
        }
    }
    return fmt;
}

function padLeftZero(str) {
    return ('00' + str).substr(str.length);
};