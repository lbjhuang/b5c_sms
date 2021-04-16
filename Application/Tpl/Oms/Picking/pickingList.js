'use strict';

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var VM = new Vue({
    el: '#picking-list',
    components: {},
    data: {
        active: 3,
        form: {
            'use_remarks': ''
        },
        iTimer: null,
        exportData: {},
        tableLoading: true,
        dialogTableVisible: false,
        dialogTableVisible01: false,
        gridData: [],
        gridData01: [],
        backGridData: [],
        printData: {},
        gridDataTemp: {},
        searchData: {
            data: {
                query: _defineProperty({
                    after_sale_type: '',
                    platForm: [],
                    ordId: [],
                    platCd: [],
                    sort: [],
                    warehouseCode: [],
                    b5cLogisticsCd: [],
                    surface_method: '',
                    logisticModel: [],
                    saleTeamCd: [],
                    pageSize: 20,
                    country: [],
                    remarkMsg: 0,
                    msgCd1: 0,
                    search_time_type: 'orderTime',
                    search_condition: 'orderNo'
                }, 'sort', 'orderTime')
            }
        }, //查询条件
        channels: {}, //平台渠道
        channelData: [],
        siteData: [],
        sites: [],
        showMore: false,
        isChannelALL: true,
        isSiteAll: true,
        packStatus: {}, //订单状态
        packData: [],
        isPackALL: true,
        surfaceWayStatus: [], //面单获取方式
        selectedSurface: '',
        sort: {}, //排序方式
        sortData: '',
        countryStatus: [], //国家
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
        downQueryStatus: {}, //查询条件
        selectedDownQuery: 'orderNo',
        searchKeywords: '',
        timeSort: 'orderTime',
        remarkMsg: 0, //含备注
        msgCd1: 0, // 拣货异常
        time: '',
        currentPage: 1, //当前页数
        totalCount: 0, //总条数
        tableData: [], //查询返回数据
        multipleSelection: [],
        dialogVisible: false,
        operateRemark: '', //运营备注
        // 日期选择
        dateRange: '',
        skuArr: [],
        backDialogVisible: false,
        backResultDialogVisible: false,
        backToStatus: 'N001821000',
        selectaftersalesTypeStatus: '',
        pickerOptions: {

        },
        innerVisible: false
    },
    created: function created() {
        // window.onscroll = function() {
        //     if (document.documentElement.scrollTop != 0) {
        //         sessionStorage.setItem("pickingListOffsetTop", document.documentElement.scrollTop);
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
        this.getPickingListMenu();
        this.getSite();
        this.getPickingList(this.searchData);
        this.flowJump();
    },
    methods: {
        // 商品名称拆分及翻译
        transName(name) {
            const arr = name.split(' X')
            return this.$lang(arr[0]) + ' X' + arr[1]
        },
        getSite: function getSite(type, val) {
            var _this = this;
            axios.post("/index.php?g=oms&m=order&a=getSite", { plat_cd: type === 'search' ? val : [] }).then(function(res) {
                if (res.data && res.data.code == 2000) {
                    _this.sites = res.data.data ? res.data.data : [];
                } else {
                    _this.$message.error(res.data.msg || '获取站点异常');
                }
                _this.getPlatformShop('notFirst')
                if (type === 'search') {
                    _this.getPickingList(_this.searchData, 'all')
                }
            }).catch(function (error) {
                _this.$message.error('获取站点异常');
                _this.getPlatformShop('notFirst')
                if (type === 'search') {
                    _this.getPickingList(_this.searchData, 'all')
                }
            });
        },
        toOrderDetail: function toOrderDetail(order_no, orderId, platCD) {
            var dom = document.createElement('a');
            var _href = "/index.php?g=OMS&m=Order&a=orderDetail&order_no=" + order_no + "&thrId=" + orderId + "&platCode=" + platCD+"&isShowEditButtonByOmsListEntry="+false;
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('订单详情') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        toDetail: function toDetail() {
            var _this = this;
            if (this.multipleSelection.length < 1) {
                this.$message.warning(this.$lang('请先选择一个订单'));
                return false;
            }

            var temp = this.multipleSelection[0]['warehouseNm'],
                ordIds = [],
                sameWarehouse = this.multipleSelection.every(function(ele) {
                    ordIds.push(ele.b5cOrderNo);
                    return ele.warehouseNm == temp
                });

            if (!sameWarehouse) {
                this.$message.warning(this.$lang('不同仓库订单不能同时拣货'));
                return false;
            }

            if(new Set(ordIds).size !== ordIds.length){
                this.$message.warning(this.$lang('存在重复的订单ID'));
                return false;
            }


            
            var param = {
                data: {
                    query: {
                        ordId: ordIds
                    }
                }
            }
            
            axios.post('/index.php?g=oms&m=commonData&a=preCheckAndDone', param)
            .then(function(res) {
                if (res.data.code === 3000) {
                    _this.$alert(res.data.msg, '拣货提示', {
                        showCancelButton: true,
                        cancelButtonText: '取消',
                        confirmButtonText: '确定',

                        callback: function(action) {
                            if (action == 'confirm') {
                                param.data.query.preDone = 1
                                axios.post('/index.php?g=oms&m=commonData&a=preCheckAndDone', param)
                                    .then(function(response) {
                                        if (response.data.code === 2000) {
                                            setTimeout(function() {
                                                _this.getPickingList(_this.searchData);
                                            }, 2000)
                                            sessionStorage.setItem('pickingResult', JSON.stringify(response.data.data));
                                            var route = document.createElement('a');
                                            route.setAttribute('style', 'display: none');
                                            route.setAttribute('onclick', "opennewtab(this,'" + _this.$lang('拣货单结果页') + "')");
                                            route.setAttribute('_href', '/index.php?g=oms&m=picking&a=pickingResult');
                                            route.onclick();
                                        }
                                    })
                            }
                        }
                    });
                } else {
                    var couponUrl = '/index.php?g=oms&m=picking&a=printOrder&ids=' + _this.ids;
                    var route = document.createElement('a');
                    route.setAttribute('style', 'display: none');
                    route.setAttribute('onclick', "opennewtab(this,'" + _this.$lang('打印拣货单') + "')");
                    route.setAttribute('_href', couponUrl);
                    route.onclick();
                }
            })
            

        },
        printOrder: function() {

        },
        //搜索条件数据
        getPickingListMenu: function getPickingListMenu() {
            var _query3;

            var _this = this;
            _this.tableData = []
            var postData = {
                data: {
                    query: (_query3 = {
                            platform: true,
                            site_cd: true,
                            gudsType: true,
                            countries: true
                        }, _defineProperty(_query3, 'countries', true), _defineProperty(_query3, 'stores', true), _defineProperty(_query3, 'warehouses', true),
                        _defineProperty(_query3, 'logisticsCompany', true), _defineProperty(_query3, 'logisticsType', true),
                        _defineProperty(_query3, 'saleTeams', true), _defineProperty(_query3, 'pickingSortType', true),
                        _defineProperty(_query3, 'pickingTimeRangeIndex', true), _defineProperty(_query3, 'surfaceWayGet', true), _query3)
                }
            };
            axios.post('/index.php?g=oms&m=CommonData&a=commonData', postData).then(function(response) {
                console.log(response)
                var data = response.data;
                if (data.code != 2000) {
                    this.$message.error(_this.$lang(data.msg));
                } else {
                    _this.channels = data.data.site_cd; //平台渠道
                    _this.packStatus = data.data.gudsType; //订单状态
                    data.data.countries.forEach(function(ele) {
                        if (ele.NAME) {
                            _this.countryStatus.push(ele);
                        }
                    });
                    _this.shopStatus = data.data.stores; //店铺
                    _this.warehouseStatus = data.data.warehouses; //仓库
                    _this.logisticsCompanyStatus = data.data.logisticsCompany; //物流公司
                    data.data.logisticsType.forEach(function(ele) {
                        _this.shippingMethodsStatus.push(ele);
                    }); //物流方式
                    _this.shippingMethodsStatusTemp = JSON.parse(JSON.stringify(_this.shippingMethodsStatus));
                    _this.salesTeamStatus = data.data.saleTeams; //销售团队
                    _this.downQueryStatus = data.data.pickingTimeRangeIndex.keyWordInput[0]; //查询条件
                    _this.time = data.data.pickingTimeRangeIndex.keyWordRange[0]; //查询条件
                    _this.sort = data.data.pickingSortType; //排序方式
                    _this.surfaceWayStatus = data.data.surfaceWayGet; //面单获取方式
                    $('#picking-list').css('visibility', 'visible');
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        getPickingList: function getPickingList(data, type) {
            var _this = this;
            var data = JSON.parse(JSON.stringify(data))
            _this.tableLoading = true;
            data.data.query.pageIndex = this.currentPage;
            if (type == 'all') {
                data.data.query.platForm = [];
                this.sites.forEach(function(element) {
                    data.data.query.platForm.push(element.CD)
                })
            }
            axios.post('/index.php?g=oms&m=picking&a=pickingListData', data).then(function(response) {
                console.log(response)
                if (response.data.code == 2000) {
                    if (response.data.data.pageData != null) {
                        _this.tableData = _this.sites.length || !type ? response.data.data.pageData : [];
                        _this.totalCount = _this.sites.length || !type ? parseInt(response.data.data.totalCount) : 0;
                    } else {
                        _this.tableData = [];
                        _this.totalCount = parseInt(response.data.data.totalCount);
                    }
                    setTimeout(function() {
                        _this.tableLoading = false;
                    }, 1000);
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        exportOrder: function exportOrder() {
            var _this = this;
            var tmep = document.createElement('form');
            tmep.action = '/index.php?g=oms&m=Picking&a=export';
            tmep.method = "post";
            // tmep.style.display = "none";
            var opt = document.createElement("input");
            opt.name = 'post_data';

            this.exportData = JSON.parse(JSON.stringify(this.searchData));
            this.exportData.data.query.ordId = this.multipleSelection.reduce(function(res, item) {
                res.push(item.b5cOrderNo);
                return res;
            }, []);
            if (!this.siteData.length) {
                _this.exportData.data.query.platForm = [];
                this.sites.forEach(function(el) {
                    _this.exportData.data.query.platForm.push(el.CD)
                })
            }
            opt.value = JSON.stringify(this.exportData);
            tmep.appendChild(opt);
            document.body.appendChild(tmep);
            tmep.submit();
            $(tmep).remove();
            tmep = null
        },
        // 选择售后类型
        selectaftersalesType: function(item) {
            var _this = this;
            _this.selectaftersalesTypeStatus = item
            _this.searchData.data.query.after_sale_type = item
        },
        channelALL: function channelALL() {
            $('.channel-item').each(function() {
                $(this).removeClass('active');
            });
            this.isChannelALL = true;
            this.channelData = [];
        },
        siteAll: function siteAll(type) {
            $('.site-item').each(function() {
                $(this).removeClass('active');
            });
            this.isSiteAll = true;
            this.siteData = [];

            if (!type) {
                this.getPlatformShop('notFirst');
                this.getPickingList(this.searchData, 'all');
            }
        },
        selectSite: function selectSite(key) {
            this.isSiteAll = false;
            $(event.srcElement).toggleClass('active');
            if (this.isNone($('.site-item'))) {
                this.isSiteAll = true;
                this.siteData = [];
                this.getPickingList(this.searchData, 'all');
            } else {
                this.doCheck(this.siteData, key);
                this.searchData.data.query.platForm = this.siteData;
            }
            this.getPlatformShop('notFirst');

        },
        selectChannel: function selectChannel(key) {
            // var channel = event.currentTarget.getAttribute('data-cd');
            this.isChannelALL = false;
            $(event.srcElement).toggleClass('active');
            if (this.isNone($('.channel-item'))) {
                this.isChannelALL = true;
                this.channelData = [];
            } else {
                this.doCheck(this.channelData, key);
            }
        },
        packALL: function packALL() {
            $('.pack-item').each(function() {
                $(this).removeClass('active');
            });
            this.isPackALL = true;
            this.packData = [];
        },
        selectPack: function selectPack(key) {
            // var pack = event.currentTarget.getAttribute('data-cd');
            this.isPackALL = false;
            $(event.srcElement).toggleClass('active');
            if (this.isNone($('.pack-item'))) {
                this.isPackALL = true;
                this.packData = [];
            } else {
                this.doCheck(this.packData, key);
            }
        },
        selectSort: function selectSort(key) {
            $(event.srcElement).siblings().removeClass('active').end().addClass('active');
            // this.sortData = event.currentTarget.getAttribute('data-cd');
            this.sortData = key;
        },
        toggleRemark: function toggleRemark() {
            $(event.srcElement).toggleClass('isActive');
            this.remarkMsg = this.remarkMsg == 0 ? 1 : 0;
        },
        toggleError: function toggleError() {
            $(event.srcElement).toggleClass('isActive');
            this.msgCd1 = this.msgCd1 == 0 ? 1 : 0;
        },
        // 查询功能
        doSearch: function doSearch() {
            var value = $.trim(this.searchKeywords).indexOf(',') > 0 ? $.trim(this.searchKeywords).split(',') : $.trim(this.searchKeywords);
            this.$set(this.searchData.data.query, 'search_condition', this.selectedDownQuery);
            this.$set(this.searchData.data.query, "search_value", value);
            if (this.siteData.length) {
                this.getPickingList(this.searchData);
            } else {
                this.getPickingList(this.searchData, 'all');
            }

        },

        //重置功能
        doReset: function doReset() {
            window.location.reload()
                // this.packALL();
                // this.channelALL();
                // this.siteAll();
                // this.$set(this.searchData.data.query, "search_value", '');
                // this.$set(this.searchData.data.query, 'search_condition', 'orderNo');
                // this.selectedCountries = [];
                // this.selectedShops = [];
                // this.selectedWarehouses = [];
                // this.selectedLogisticsCompanys = [];
                // this.selectedShippingMethods = [];
                // this.selectedSalesTeam = [];
                // this.remarkMsg = 0;
                // this.msgCd1 = 0;
                // this.timeSort = 'orderTime';
                // this.searchKeywords = '';
                // this.dateRange = [];
                // this.selectedDownQuery = 'orderNo';
                // $('.contain-remark,.contain-error').removeClass("isActive");
                //
                // $('.sort-type')[0].click();
                // this.getPickingListMenu();
        },
        selectCompany: function selectCompany() {
            var _this = this;
            axios.post('index.php?g=Oms&m=OrderPresent&a=get_log_company', {
                company_code: _this.selectedLogisticsCompanys
            }).then(function(res) {
                if (res.data.code == 200) {
                    if (res.data.data == null) {
                        _this.shippingMethodsStatus = _this.shippingMethodsStatusTemp;
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
        changeArr: function changeArr(arr) {
            var newAObj = {};
            arr.forEach(function(el) {
                if (!newAObj.hasOwnProperty(el)) {
                    newAObj[el] = 1;
                } else {
                    newAObj[el]++;
                }
            });
            var newArr = [];
            for (var key in newAObj) {
                newArr.push({
                    sku: key,
                    num: newAObj[key]
                });
            }
            return newArr;
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
                'thr_order_id': _this.form.orderId,
                'plat_cd': _this.form.platCd,
                'remarks_type': 'operate',
                'remarks_msg': _this.form.remarkMsg
            };

            axios.post('/index.php?g=oms&m=order&a=orderRemarks', data).then(function(response) {
                if (response.data.status == 200000) {
                    _this.dialogVisible = !_this.dialogVisible;
                    _this.getPickingList(_this.searchData);
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
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
            arr.splice(arr.indexOf(value), 1);
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
        handleSizeChange: function handleSizeChange(val) {
            this.searchData.data.query.pageSize = val;
        },
        handleCurrentChange: function handleCurrentChange(val) {
            this.currentPage = val;
            if (this.channelData.length) {
                this.getPickingList(this.searchData, 'all');
            } else if (this.channelData.length && this.siteData.length) {
                this.getPickingList(this.searchData);
            } else {
                this.getPickingList(this.searchData);
            }
        },
        handleSelectionChange: function handleSelectionChange(val) {
            var ordIdArr = [];
            this.multipleSelection = val;
            this.multipleSelection.forEach(function(ele) {
                ordIdArr.push(ele.b5cOrderNo);
            });
            this.exportData = JSON.parse(JSON.stringify(this.searchData));
            this.exportData.data.query.ordId = ordIdArr;
        },
        //退回捡货单
        showDoBack: function showDoBack() {
            if (this.multipleSelection.length < 1) {
                this.$message.error(this.$lang('请先选择要退回的订单'));
            } else {
                this.backDialogVisible = true;
            }
        },
        handleClose: function handleClose(done) {
            done();
        },
        cancelStatus: function cancelStatus() {
            this.backDialogVisible = false;
        },
        doStatus: function doStatus() {
            var _this = this;
            // var ordIdArr = [];
            // _this.multipleSelection.forEach(function(ele) {
            //     ordIdArr.push(ele.b5cOrderNo);
            // });
            // var postData = {
            //     "data": {
            //         "query": {
            //             "ordId": ordIdArr,
            //             "state": _this.backToStatus,
            //             from: 'N001820500'
            //         }
            //     }
            // };
            if (!_this.backToStatus) {
                _this.$message.error(_this.$lang('请先选择要退回的状态'));
            } else {
                _this.innerVisible = true
                    // axios.post('/index.php?g=oms&m=commonData&a=orderReBack', postData).then(function(response) {
                    //     if (response.data.code == 2000) {
                    //         var result = response.data.data.pageData;
                    //         _this.backResultDialogVisible = true;
                    //         _this.backGridData = [];
                    //         for (var i in result) {
                    //             _this.backGridData.push(result[i])
                    //         }
                    //     } else {
                    //         _this.$message.error(_this.$lang(response.data.msg));
                    //     }
                    // }).catch(function(err) {
                    //     console.log(err);
                    // });
            }
        },
        batchFn: function() {
            var _this = this;
            var ordIdArr = [];
            _this.multipleSelection.forEach(function(ele) {
                ordIdArr.push(ele.b5cOrderNo);
            });
            var postData = {
                "data": {
                    "query": {
                        "ordId": ordIdArr,
                        "state": _this.backToStatus,
                        from: 'N001820500'
                    }
                }
            };
            axios.post('/index.php?g=oms&m=commonData&a=orderReBack', postData).then(function(response) {
                if (response.data.code == 2000) {
                    var result = response.data.data.pageData;
                    _this.backResultDialogVisible = true;
                    _this.innerVisible = false;
                    _this.backGridData = [];
                    for (var i in result) {
                        _this.backGridData.push(result[i])
                    }
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        finishedStatus: function finishedStatus() {
            this.backDialogVisible = false,
                this.backResultDialogVisible = false,
                setTimeout(function() {
                    // VM.getPickingList(VM.searchData);
                    VM.doSearch()
                }, 800);
        },
        //打印预览
        toDoPrint: function toDoPrint() {
            var _this = this;
            var orderIds = [];
            this.multipleSelection.forEach(function(ele, index) {
                if (ele.b5cOrderNo) {
                    orderIds.push(ele.b5cOrderNo);
                }
            });
            var postData = {
                data: {
                    query: {
                        ordId: orderIds
                    }
                }
            };

            axios.post('/index.php?g=oms&m=picking&a=previewOrder', postData).then(function(response) {
                if (response.data.code == 2000) {
                    _this.dialogTableVisible = true;

                    _this.gridDataTemp = response.data.data;
                    _this.gridData = [];
                    for (var key in _this.gridDataTemp.pageData) {
                        _this.gridData.push(_this.gridDataTemp.pageData[key]);
                    }
                    _this.printData = {
                        data: {
                            query: {
                                ordId: orderIds,
                                pickingNo: _this.gridDataTemp.pickingNo
                            }
                        }
                    };
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        //打印
        doPrint: function doPrint() {},
        checkIsNone: function checkIsNone() {
            if (!this.multipleSelection) {
                return false;
            }
        },
        finishedStatus01: function finishedStatus01() {
            this.dialogTableVisible01 = false;
            setTimeout(function() {
                // VM.getPickingList(VM.searchData);
                VM.doSearch()
            }, 800);
        },

        //一键通过
        oneKeyToPass: function oneKeyToPass() {
            var _this = this;
            if (this.multipleSelection.length < 1) {
                this.$message.error(_this.$lang('请先选择要一键通过的订单'));
            } else {
                _this.$confirm(_this.$lang('确认将选中订单当前步骤一键通过吗？'), _this.$lang('一键通过'), {
                    confirmButtonText: _this.$lang('确定'),
                    cancelButtonText: _this.$lang('取消')
                }).then(function() {
                    var ordIdArr = [];
                    _this.multipleSelection.forEach(function(ele) {
                        ordIdArr.push(ele.b5cOrderNo);
                    });
                    var postData = {
                        "data": {
                            "query": {
                                "ordId": ordIdArr,
                                from: 'N001820500'
                            }
                        }
                    };
                    axios.post('/index.php?g=oms&m=commonData&a=oneKeyThrough', postData).then(function(response) {
                        if (response.data.code == 2000) {
                            _this.dialogTableVisible01 = true;
                            _this.gridData01 = response.data.data.pageData;
                        } else {
                            _this.$message.error(_this.$lang(response.data.msg));
                        }
                    }).catch(function(err) {
                        console.log(err);
                    });
                }).catch(function() {
                    _this.$message({
                        type: 'info',
                        message: _this.$lang('已取消一键通过')
                    });
                });
            }
        },
        getPlatformShop: function getPlatformShop(type) {
            var _this = this;
            var postData = { "plat_form": [] };
            if (type == "first") {
                postData.plat_form = [];
            } else if (type == "notFirst") {
                postData.plat_form = this.siteData;
            }
            if (!this.siteData.length) {
                postData.plat_form = [];
                this.sites.forEach(function(element) {
                    postData.plat_form.push(element.CD)
                })
            } else {
                postData.plat_form = this.siteData
            }

            axios.post('/index.php?g=Oms&m=Order&a=storesGet', postData).then(function(response) {
                if (response.data.status == 200000) {
                    VM.shopStatus = response.data.data;
                } else {
                    VM.$message.error(_this.$lang(response.data.info));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },

        flowJump: function flowJump(e) {
            var _this2 = this;

            var path = [{ m: 'order_present', a: 'OrderPresentList', title: this.$lang('待预派') }, { m: 'patch', a: 'lists', title: this.$lang('待派单') }, { m: 'pending_order', a: 'pending_list', title: this.$lang('待获取运单号') }, { m: 'picking', a: 'pickingList', title: this.$lang('待拣货') }, { m: 'pick_apart', a: 'PickApartList', title: this.$lang('待分拣') }, { m: 'check_order', a: 'checkingList', title: this.$lang('待核单') }, { m: 'out_going', a: 'outList', title: this.$lang('待出库') }, { m: 'out_storage', a: 'listPage', title: this.$lang('已出库') }];

            setTimeout(function() {
                var url = 'index.php?g=oms&m=order_present&a=OrderPresentList';
                $('.el-step__head').each(function(i, e) {
                    $(e).click(function() {
                        if (_this2.active == i) {
                            window.location.reload();
                        } else {
                            url = '/index.php?g=oms&m=' + path[i].m + '&a=' + path[i].a;
                            newTab(url, path[i].title);
                        }
                    });
                });
            }, 1000);
        }
    },
    watch: {
        //自动监测搜索条件的修改
        channelData: {
            handler: function handler(newValue, oldValue) {
                this.sites = [];
                this.getSite('search', newValue)
                this.siteAll('notClick');
            },

            deep: true
        },
        packData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'packageType', newValue);
            },

            deep: true
        },
        sortData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'sort', newValue);
            },

            deep: true
        },
        remarkMsg: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'remarkMsg', newValue);
            },

            deep: true
        },
        msgCd1: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'msgCd1', newValue);
            },

            deep: true
        },

        selectedCountries: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'country', newValue);
            },

            deep: true
        },
        selectedSurface: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "surface_method", newValue);
            },

            deep: true
        },
        selectedShops: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'platCd', newValue);
            },

            deep: true
        },
        selectedWarehouses: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'warehouseCode', newValue);
            },

            deep: true
        },
        selectedLogisticsCompanys: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'b5cLogisticsCd', newValue);
            },

            deep: true
        },
        selectedShippingMethods: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'logisticModel', newValue);
            },

            deep: true
        },
        selectedSalesTeam: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'saleTeamCd', newValue);
            },

            deep: true
        },
        timeSort: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, 'search_time_type', newValue);
            },

            deep: true
        },
        dateRange: {
            handler: function handler(newValue, oldValue) {
                newValue = newValue || [];
                this.$set(this.searchData.data.query, 'search_time_left', newValue[0]);
                this.$set(this.searchData.data.query, 'search_time_right', newValue[1]);
            },

            deep: true
        },
        //搜索条件数据修改，自动请求接口
        searchData: {
            handler: function handler(newValue, oldValue) {
                this.currentPage = 1;
                if (!this.siteData.length) {
                    this.getPickingList(newValue, 'all');
                } else if (this.channelData.length && this.siteData.length) {
                    clearTimeout(VM.iTimer)
                    VM.iTimer = setTimeout(function() {
                        VM.getPickingList(newValue);
                    }, 500)
                } else {
                    clearTimeout(VM.iTimer)
                    VM.iTimer = setTimeout(function() {
                        VM.getPickingList(newValue);
                    }, 500)

                }
            },

            deep: true
        }
    },
    filters: {
        formatDate: function formatDate(time) {
            var date = new Date(time);
            return _formatDate(date, 'yyyy-MM-dd hh:mm:ss');
        }
    },
    computed: {
        ids: function ids() {
            var orderIds = [];
            this.multipleSelection.forEach(function(ele, index) {
                if (ele.b5cOrderNo) {
                    orderIds.push(ele.b5cOrderNo);
                }
            });
            return orderIds.join('-');
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
}