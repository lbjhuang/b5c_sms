"use strict";

var _extends =
    Object.assign ||
    function(target) {
        for (var i = 1; i < arguments.length; i++) {
            var source = arguments[i];
            for (var key in source) {
                if (Object.prototype.hasOwnProperty.call(source, key)) {
                    target[key] = source[key];
                }
            }
        }
        return target;
    };

var _data;
if (getCookie("think_language") !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en);
}

function _defineProperty(obj, key, value) {
    if (key in obj) {
        Object.defineProperty(obj, key, {
            value: value,
            enumerable: true,
            configurable: true,
            writable: true
        });
    } else {
        obj[key] = value;
    }
    return obj;
}

var VM = new Vue({
    el: "#list-page",
    components: {},
    data: ((_data = {
            active: 7,
            form: {},
            trackData: [], //物流轨迹数据
            trackIndex: 4, // 物流轨迹显示多少条标识
            exportData: {},
            pageSize: 20,
            surfaceWayVal: "",
            isEditState: false,
            surfaceWayGet: [],
            tableLoading: true,
            dialogTableVisible: false,
            downloadDialog: false,
            gridData: [],
            printData: {},
            gridDataTemp: {},
            systemDock: [],
            isFinished01: false,
            isFinished02: false,
            innerVisible: false,
            selectaftersalesTypeStatus: '',
            TemplateDownloadDialog: false, // 模板下载弹窗
            TemplateDownRadio: '1',
            bathloadDialog: false,
            percentage: 0,
            timeout: null,
            percentageShow: false ,
            filePercentageShow: false,
        }),
        _defineProperty(_data, "dialogTableVisible", false),
        _defineProperty(_data, "dialogVisible01", false),
        _defineProperty(_data, "backToStatus", ""),
        _defineProperty(_data, "searchData", {
            data: {
                query: _defineProperty({
                        after_sale_type: '',
                        platForm: [],
                        ordId: "",
                        platCd: "",
                        sort: "",
                        warehouseCode: "",
                        b5cLogisticsCd: "",
                        logisticModel: "",
                        saleTeamCd: "",
                        pageSize: 20,
                        search_time_type: "orderTime",
                        search_condition: "orderNo",
                        freightType: "",
                        logistics_abnormal_status:[]
                    },
                    "sort",
                    "orderTime"
                )
            }
        }),
        _defineProperty(_data, "channels", {}),
        _defineProperty(_data, "selectedFreightType", []),
        _defineProperty(_data, "selectedFreight", {}),
        _defineProperty(_data, 'siteData', []),
        _defineProperty(_data, 'sites', []),
        _defineProperty(_data, 'showMore', false),
        _defineProperty(_data, 'isSiteAll', true),
        _defineProperty(_data, "channelData", []),
        _defineProperty(_data, "isChannelALL", true),
        _defineProperty(_data, "packData", []),
        _defineProperty(_data, "isPackALL", true),
        _defineProperty(_data, "deliveryStatus", ""),
        _defineProperty(_data, "selectLog", []),
        _defineProperty(_data, "selectLogFail", []),
        _defineProperty(_data, "sort", {}),
        _defineProperty(_data, "sortData", ""),
        _defineProperty(_data, "countryStatus", []),
        _defineProperty(_data, "selectedCountries", []),
        _defineProperty(_data, "shopStatus", {}),
        _defineProperty(_data, "selectedShops", []),
        _defineProperty(_data, "warehouseStatus", {}),
        _defineProperty(_data, "selectedWarehouses", []),
        _defineProperty(_data, "logicSuccess", []),
        _defineProperty(_data, "logicFail", []),
        _defineProperty(_data, "logisticsCompanyStatus", {}),
        _defineProperty(_data, "selectedLogisticsCompanys", []),
        _defineProperty(_data, "shippingMethodsStatus", []),
        _defineProperty(_data, "shippingMethodsStatusTemp", []),
        _defineProperty(_data, "selectedShippingMethods", []),
        _defineProperty(_data, "selectedSystem", []),
        _defineProperty(_data, "salesTeamStatus", {}),
        _defineProperty(_data, "selectedSalesTeam", []),
        _defineProperty(_data, "downQueryStatus", {}),
        _defineProperty(_data, "selectedDownQueryTemp", "orderNo"),
        _defineProperty(_data, "selectedDownQuery", "orderNo"),
        _defineProperty(_data, "searchKeywords", ""),
        _defineProperty(_data, "searchKeywordsTemp", ""),
        _defineProperty(_data, "timeSort", "orderTime"),
        _defineProperty(_data, "remarkMsg", 0),
        _defineProperty(_data, "msgCd1", 0),
        _defineProperty(_data, "time", ""),
        _defineProperty(_data, "currentPage", 1),
        _defineProperty(_data, "totalCount", 0),
        _defineProperty(_data, "tableData", []),
        _defineProperty(_data, "tableDataTemp", []),
        _defineProperty(_data, "multipleSelection", []),
        _defineProperty(_data, "multipleSelectionTemp", []),
        _defineProperty(_data, "dialogVisible", false),
        _defineProperty(_data, "operateRemark", ""),
        _defineProperty(_data, "dateRange", ""),
        _defineProperty(_data,"logistics_abnormal_status","")
    ),
methods: {
        createCompensation(row) {
            this.$confirm(this.$lang('确认对此出库订单申请赔付？'), '', {
                confirmButtonText: this.$lang('确认'),
                cancelButtonText: this.$lang('取消'),
            }).then(() => {
                newTab("/index.php?g=Oms&m=compensation&a=create&pid=" + row.b5cOrderNo, this.$lang('新建赔付单'));
            })
        },
        hideChange() {
            this.trackIndex = 4;
        },
        handleScroll() {
            // console.log(1,document.documentElement.scrollTop);
            if (document.documentElement.scrollTop === 0) {
                // console.log(document.getElementsByClassName('el-popover'));
                // document.getElementsByClassName('el-popover').style.display = 'none';
                for (var i = 0; i < document.getElementsByClassName('el-popover').length; i++) {
                    document.getElementsByClassName('el-popover')[i].style.display = 'none';
                }
            }
        },
        getSite: function getSite(type, val) {
            var _this = this;
            axios.post("/index.php?g=oms&m=order&a=getSite", {
                plat_cd: type === 'search' ? val : []
            }).then(function(res) {
                if (res.data && res.data.code == 2000) {
                    _this.sites = res.data.data ? res.data.data : [];
                } else {
                    _this.$message.error(res.data.msg || '获取站点异常');
                }
                _this.getPlatformShop('notFirst');

                if (type === 'search') {
                    _this.getOutGolingList(_this.searchData, 'all')
                }
            }).catch(function (error) {
                _this.$message.error('获取站点异常');
                _this.getPlatformShop('notFirst');

                if (type === 'search') {
                    _this.getOutGolingList(_this.searchData, 'all')
                }
            });
        },
        getTracking: function getTracking() {
            var _this = this;
            var orderIdsArr = [];
            _this.multipleSelection.forEach(function(ele) {
                orderIdsArr.push({
                    thr_order_id: ele.b5cOrderNo,
                    b5c_logistics_cd: ele.expeCompanyCd,
                    plat_cd: ele.platCd,
                    service_code: ele.logisticModelId
                });
            });
            var postData = {
                data: {
                    orders: orderIdsArr
                }
            };
            if (this.multipleSelection.length < 1) {
                this.$message.warning(this.$lang("请先选择一个订单"));
            } else {
                axios
                    .post("/index.php?g=OMS&m=Patch&a=electronicOrder", postData)
                    .then(function(response) {
                        var data = response.data;
                        if (data.status != 200000) {
                            VM.$message.error(data.info);
                        } else {
                            VM.$message.warning(_this.$lang("面单获取中...请稍候"));
                        }
                    })
                    .catch(function(err) {
                        console.log(err);
                    });
            }
        },
        clickUploadFile: function clickUploadFile() {
            $(".excel-delivery")
                .find(".update-file-content")
                .click();
        },
        updatePic: function updatePic(val) {
            //创建FormData对象
            var data = new FormData();
            var _this = this;
            var name = $(event.currentTarget)[0]["files"][0].name.split('.')[1];
            if(name !== 'xls' && name !== 'xlsx' && name !== 'csv'){
                VM.$message.error(_this.$lang('请上传后缀为.xls、xlsx的文件'));
                return false
            }
            //为FormData对象添加数据
            data.append("file", $(event.currentTarget)[0]["files"][0]);
            this.filePercentageShow = true;
            this.setSteps();
            $.ajax({
                    url: "/index.php?g=oms&m=OutStorage&a=import",
                    type: "POST",
                    dataType: "JSON",
                    contentType: false,
                    processData: false,
                    data: data,
                    cache: false
                })
                .success(function(data) {
                    clearTimeout(_this.timeout);
                    _this.percentage = 100;
                    setTimeout(() => {
                        
                        _this.filePercentageShow = false;
                    }, 500);
                    if (data.code == 2000) {
                        $(".update-file-content").val("");
                        VM.$message.success(_this.$lang(data.msg));
                        VM.doSearch();
                    } else {
                        VM.$message.error(_this.$lang(data.msg));
                        $(".update-file-content").val("");
                    }
                })
                .error(function() {
                    console.log("error");
                })
                .complete(function() {
                    $(".update-file-content").val("");
                });
        },
        clickReturnGoodsUploadFile() {
            this.$refs['return-goods-upload'].click();
        },
        returnGoodsChange() {
            //创建FormData对象
            var data = new FormData();
            var _this = this;
            var name = $(event.currentTarget)[0]["files"][0].name.split('.')[1];
            if(name !== 'xls' && name !== 'xlsx' && name !== 'csv'){
                VM.$message.error(_this.$lang('请上传后缀为.xls、xlsx的文件'));
                return false
            }
            //为FormData对象添加数据
            data.append("file", $(event.currentTarget)[0]["files"][0]);
            this.percentageShow = true;
            this.setSteps();
            $.ajax({
                    url: "/index.php?g=OMS&m=AfterSale&a=applySubmitBatch",
                    type: "POST",
                    dataType: "JSON",
                    contentType: false,
                    processData: false,
                    data: data,
                    cache: false
                })
                .success(function(data) {
                    clearTimeout(_this.timeout);
                    _this.percentage = 100;
                    setTimeout(() => {
                        
                    _this.percentageShow = false;
                    }, 500);
                    if (data.code == 200) {
                        $(".return-file-content").val("");
                        VM.$message.success(_this.$lang(data.msg));
                        _this.bathloadDialog = true;
                        // VM.doSearch();
                    } else if (data.info == '批量退货导入错误') {
                        _this.$confirm('是否下载错误报告', '批量退货导入错误', {
                            confirmButtonText: '确定',
                            cancelButtonText: '取消',
                            type: 'warning'
                        }).then(() => {
                            document.querySelector('#download').click();
                    }).catch(() => {

                    });

    $(".return-file-content").val("");
                    } else {
                        if(data.data.length>0){
                            var str = '';
                            for(var i=0;i<data.data.length;i++) {
                                str += `<p>${data.data[i]}</p>`;
                                // (function(i) {
                                //     setTimeout(function() {
                                //         console.log(data.data[i]);
                                // VM.$message.error(_this.$lang(data.data[i]));
                                
                                //     },i*1000);
                                // })(i)
                            }
                            
                            VM.$message({
                                dangerouslyUseHTMLString: true,
                                message: str,
                                type: 'warning'
                            })
                        } else {
                            VM.$message.warning(_this.$lang(data.msg)); 
                        }

                    }
                })
                .error(function() {
                    console.log("error");
                })
                .complete(function() {
                    $(".return-file-content").val("");
                });
        },
        setSteps() {
            let _this = this;
            _this.percentage = 0;
            _this.timeout = setInterval(() =>{
        
                _this.percentage+=5 ;
                if(_this.percentage>94) {
                    
                    clearTimeout(_this.timeout);
                }
            },100);
        },
        //搜索条件数据
        getOutGolingListMenu: function getOutGolingListMenu() {
            var _query3;

            var _this = this;
            _this.tableData = []
            var postData = {
                data: {
                    type: 'outstorage_sort',
                    query: ((_query3 = {
                            platform: true,
                            gudsType: true,
                            countries: true,
                            site_cd: true,
                            freightType: true
                        }),
                        _defineProperty(_query3, "countries", true),
                        _defineProperty(_query3, "stores", true),
                        _defineProperty(_query3, "warehouses", true),
                        _defineProperty(_query3, "logisticsCompany", true),
                        _defineProperty(_query3, "logisticsType", true),
                        _defineProperty(_query3, "saleTeams", true),
                        _defineProperty(_query3, "pickingSortType", true),
                        _defineProperty(_query3, "pickingTimeRangeIndex", true),
                        _defineProperty(_query3, "surfaceWayGet", true),
                        _defineProperty(_query3, "systemDocking", true),
                        _defineProperty(_query3, "logicStatus", true),
                        _query3)
                }
            };
            axios
                .post("/index.php?g=oms&m=CommonData&a=commonData", postData)
                .then(function(response) {
                    var data = response.data;
                    if (data.code != 2000) {
                        this.$message.error(_this.$lang(data.msg));
                    } else {
                        _this.surfaceWayGet = data.data.surfaceWayGet;
                        _this.channels = data.data.site_cd; //平台渠道
                        data.data.countries.forEach(function(ele) {
                            if (ele.NAME) {
                                _this.countryStatus.push(ele);
                            }
                        });

                        _this.systemDock = data.data.systemDocking; //发货系统
                        _this.shopStatus = data.data.stores; //店铺
                        _this.warehouseStatus = data.data.warehouses; //仓库
                        _this.logisticsCompanyStatus = data.data.logisticsCompany; //物流公司
                        _this.shippingMethodsStatus = data.data.logisticsType; //物流方式
                        _this.shippingMethodsStatusTemp = JSON.parse(
                            JSON.stringify(_this.shippingMethodsStatus)
                        );
                        _this.salesTeamStatus = data.data.saleTeams; //销售团队
                        _this.downQueryStatus = data.data.pickingTimeRangeIndex.keyWordInput[0]; //查询条件
                        _this.time = data.data.pickingTimeRangeIndex.keyWordRange[0]; //查询条件
                        _this.sort = data.data.pickingSortType; //排序方式
                        _this.logicFail = data.data.logicStatus.fail; //排序方式
                        _this.logicSuccess = data.data.logicStatus.success; //排序方式
                        _this.selectedFreight = data.data.freightType;
                        _this.isFinished01 = true;
                        $("#list-page").css("visibility", "visible");
                    }
                })
                .catch(function(err) {
                    console.log(err);
                });
        },
        getOutGolingList: function getOutGolingList(data, type) {
            var _this = this;
            var data = JSON.parse(JSON.stringify(data))

            _this.tableLoading = true;
            data.data.query.pageIndex = this.currentPage;
            if (this.isSiteAll === true) {
                data.data.query.platForm = [];
                this.sites.forEach(function(element) {
                    data.data.query.platForm.push(element.CD)
                })
            }
            axios
                .post("/index.php?g=oms&m=OutStorage&a=listPageData", data)
                .then(function(response) {
                    console.log(response)
                    if (response.data.code == 2000) {
                        if (response.data.data.pageData != null) {
                            _this.tableData = _this.sites.length || !type ? response.data.data.pageData : [];
                        } else {
                            _this.tableData = [];
                        }
                        _this.totalCount = _this.sites.length || !type ? parseInt(response.data.data.totalCount) : 0;

                        if (_this.tableData) {
                            _this.tableData.forEach(function(ele, ind) {
                                _this.$set(ele, "edit", false);
                            });
                        }

                        _this.isFinished02 = true;
                        _this.tableLoading = false;
                    } else {
                        _this.$message.error(_this.$lang(response.data.msg));
                    }
                })
                .catch(function(err) {
                    console.log(err);
                });
        },
        // 选择售后类型
        selectaftersalesType: function(item) {
            var _this = this;
            _this.selectaftersalesTypeStatus = item
            _this.searchData.data.query.after_sale_type = item
        },
        //退回捡货单
        showDoBack: function showDoBack() {
            if (this.multipleSelection.length < 1) {
                this.$message.error(this.$lang('请先选择要退回的订单'));
            } else {
                this.backDialogVisible = true;
            }
        },
        channelALL: function channelALL() {
            $(".channel-item").each(function() {
                $(this).removeClass("active");
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
                this.getOutGolingList(this.searchData, 'all');
            }
        },
        selectSite: function selectSite(key) {
            this.isSiteAll = false;
            $(event.srcElement).toggleClass('active');
            if (this.isNone($('.site-item'))) {
                this.isSiteAll = true;
                this.siteData = [];
                this.getOutGolingList(this.searchData, 'all');

            } else {
                this.doCheck(this.siteData, key);
                this.searchData.data.query.platForm = this.siteData;
            }
            this.getPlatformShop('notFirst');

        },
        selectChannel: function selectChannel(key) {
            // var channel = event.currentTarget.getAttribute('data-cd');
            this.isChannelALL = false;
            $(event.srcElement).toggleClass("active");
            if (this.isNone($(".channel-item"))) {
                this.isChannelALL = true;
                this.channelData = [];
            } else {
                this.doCheck(this.channelData, key);
            }
        },
        //checked 设为false
        setCheck: function setCheck(data) {
            for (var key in data) {
                data[key].checked = false;
            }
        },
        //选择平台渠道
        selectSuccess: function selectSuccess(item) {
            var _this2 = this;

            if (!item) {
                this.selectLog = [];
                this.setCheck(this.logicSuccess);
            } else {
                this.$set(item, "checked", !item.checked);
                if (item.checked) {
                    this.selectLog.push(item.cd);
                } else {
                    this.selectLog.forEach(function(val, index) {
                        if (val == item.cd) {
                            _this2.selectLog.splice(index, 1);
                        }
                    });
                }
            }
        },
        //选择平台渠道
        selectFail: function selectFail(item) {
            var _this3 = this;

            if (!item) {
                this.selectLogFail = [];
                this.setCheck(this.logicFail);
            } else {
                this.$set(item, "checked", !item.checked);
                if (item.checked) {
                    this.selectLogFail.push(item.cd);
                } else {
                    this.selectLogFail.forEach(function(val, index) {
                        if (val == item.cd) {
                            _this3.selectLogFail.splice(index, 1);
                        }
                    });
                }
            }
        },
        selectSort: function selectSort(key) {
            $(event.srcElement)
                .siblings()
                .removeClass("active")
                .end()
                .addClass("active");
            // this.sortData = event.currentTarget.getAttribute('data-cd');
            this.sortData = key;
        },
        toggleRemark: function toggleRemark() {
            $(event.srcElement).toggleClass("isActive");
            this.remarkMsg = this.remarkMsg == 0 ? 1 : 0;
        },
        toggleError: function toggleError() {
            $(event.srcElement).toggleClass("isActive");
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
        // 前往退货待入库页面
        toBathload() {
            this.downloadDialog = false;
            var dom = document.createElement('a');
            var _href = "/index.php?g=oms&m=after_sale&a=wait_warehouse_list";
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('退货待入库') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // //跳转详情页
        toDetail: function toDetail() {
            var dom = document.createElement("a");
            var _href = "/index.php?g=oms&m=outGoing&a=outListDetail";
            dom.setAttribute("onclick", "opennewtab(this,'出库结果页')");
            dom.setAttribute("_href", _href);
            dom.onclick();
        },
        toOrderDetail: function toOrderDetail(orderId, platCD, orderNo) {
            // sessionStorage.setItem("outStorageOffsetTop", document.documentElement.scrollTop);
            var dom = document.createElement("a");
            // var platCD = dom.getAttribute('data-plat');
            var _href =
                "/index.php?g=OMS&m=Order&a=orderDetail&thrId=" +
                orderId +
                "&platCode=" +
                platCD +
                "&order_no=" +
                orderNo+"&isShowEditButtonByOmsListEntry="+false;
            dom.setAttribute(
                "onclick",
                "opennewtab(this,'" + this.$lang("订单详情") + "')"
            );
            dom.setAttribute("_href", _href);
            dom.click();
        },
        //跳转申请售后页
        toAfterSale: function toAfterSale(order_no, warehouse, order_id, platCd) {
            var dom = document.createElement('a');
            var _href = "/index.php?g=OMS&m=AfterSale&a=after_sale_apply&order_no=" + order_no + "&warehouse=" + warehouse + "&order_id=" + order_id + "&platCd=" + platCd;
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('申请售后') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // 查询功能
        doSearch: function doSearch() {
            this.searchKeywordsTemp = this.searchKeywords;
            this.selectedDownQueryTemp = this.selectedDownQuery;
            var value =
                $.trim(this.searchKeywordsTemp).indexOf(",") > 0 ?
                $.trim(this.searchKeywordsTemp).split(",") :
                $.trim(this.searchKeywordsTemp);
            this.$set(
                this.searchData.data.query,
                "search_condition",
                this.selectedDownQueryTemp
            );
            this.$set(this.searchData.data.query, "search_value", value);
            if (this.siteData.length) {
                this.getOutGolingList(this.searchData);
            } else {
                this.getOutGolingList(this.searchData, 'all');
            }
            this.isEditState = false;
        },
        //导出
        exportOrder: function exportOrder() {
            var _this = this;
            _this.tableLoading = true;
            var param = _extends({}, this.searchData.data.query);

            param.ordId = this.multipleSelection.reduce(function(res, item) {
                res.push(item.b5cOrderNo);
                return res;
            }, []);
            if (!_this.siteData.length) {
                param.platForm = [];
                _this.sites.forEach(function(el) {
                    param.platForm.push(el.CD)
                })
            }

            axios.post('/index.php?g=oms&m=OutStorage&a=checkExport', param).then(function(res) {
                _this.tableLoading = false;
                console.log(res)
                if (res.data.code == 200) {
                    if (res.data.is_hint) {
                        _this.downloadDialog = true;
                    } else {
                        console.log(1)
                        var tmep = document.createElement("form");
                        tmep.action = "/index.php?g=oms&m=OutStorage&a=export";
                        tmep.method = "post";
                        var opt = document.createElement("input");
                        opt.name = "post_data";

                        opt.value = JSON.stringify(param);
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
        //重置功能
        doReset: function doReset() {
            window.location.reload()
                // this.channelALL();
                // this.siteAll();
                // this.$set(this.searchData.data.query, "search_value", "");
                // this.$set(this.searchData.data.query, "search_condition", "orderNo");
                // this.selectedCountries = [];
                // this.selectedShops = [];
                // this.selectedWarehouses = [];
                // this.selectedFreightType = [];
                // this.selectedLogisticsCompanys = [];
                // this.selectedShippingMethods = [];
                // this.selectedSalesTeam = [];
                // this.selectLog = [];
                // this.selectLogFail = [];
                // this.remarkMsg = 0;
                // this.msgCd1 = 0;
                // this.timeSort = "orderTime";
                // this.searchKeywords = "";
                // this.dateRange = [];
                // this.selectedDownQuery = "orderNo";
                // this.deliveryStatus = "";
                // $(".contain-remark,.contain-error").removeClass("isActive");
                // $(".sort-type")[0].click();
                // this.getOutGolingListMenu();
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
                thr_order_id: _this.form.orderId,
                plat_cd: _this.form.platCd,
                remarks_type: "operate",
                remarks_msg: _this.form.remarkMsg
            };

            axios
                .post("/index.php?g=oms&m=order&a=orderRemarks", data)
                .then(function(response) {
                    if (response.data.status == 200000) {
                        _this.dialogVisible = !_this.dialogVisible;
                        _this.getOutGolingList(_this.searchData);
                    } else {
                        _this.$message.error(_this.$lang(response.data.msg));
                    }
                })
                .catch(function(err) {
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
                if ($(this).hasClass("active")) num += 1;
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
            console.log(arr, val)
                //如果有删除，没有则添加
            if (this.isInArray(arr, val)) {
                this.deleteEle(arr, val);
            } else {
                arr.push(val);
            }
        },
        //关闭订单
        closeOrder: function closeOrder() {},
        handleSizeChange: function handleSizeChange(val) {
            this.searchData.data.query.pageSize = val;
        },
        handleCurrentChange: function handleCurrentChange(val) {
            this.currentPage = val;
            if (this.channelData.length) {
                this.getOutGolingList(this.searchData, 'all');
            } else if (this.channelData.length && this.siteData.length) {
                this.getOutGolingList(this.searchData);
            } else {
                this.getOutGolingList(this.searchData);
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

            axios
                .post("/index.php?g=oms&m=outGoing&a=previewOrder", postData)
                .then(function(response) {
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
                                    outGoingNo: _this.gridDataTemp.outGoingNo
                                }
                            }
                        };
                    } else {
                        _this.$message.error(_this.$lang(response.data.msg));
                    }
                })
                .catch(function(err) {
                    console.log(err);
                });
        },
        //批量编辑
        mutiEdit: function mutiEdit() {
            var _this = this;
            if (_this.multipleSelection.length > 0) {
                _this.isEditState = true;
                _this.tableDataTemp = JSON.parse(JSON.stringify(_this.tableData));
                _this.tableData.forEach(function(ele) {
                    _this.multipleSelection.forEach(function(e) {
                        if (ele == e) {
                            _this.$set(ele, "disabled", e.surfaceWayGetCd == "N002010100");
                            _this.$set(ele, "edit", true);
                            _this.$set(ele, "expeCompanyDatas", [{
                                logisticsCode: ele.expeCompanyCd,
                                logisticsName: ele.expeCompany
                            }]);
                            _this.$set(ele, "logisticDatas", [{
                                id: ele.logisticModelId,
                                logisticsMode: ele.logisticModel
                            }]);
                            ele.findOrderJson &&
                                ele.findOrderJson.data.warehouse.forEach(function(item) {
                                    if (item.isShow) {
                                        _this.$set(ele, "expeCompanyDatas", item.lgtModel);
                                    }
                                });
                        }
                    });
                });
            } else {
                _this.$message.warning(_this.$lang("请先选择要编辑的订单。"));
            }
        },

        //取消编辑
        cancelEdit: function cancelEdit() {
            var _this = this;
            _this.isEditState = false;
            _this.$refs.multipleTable.clearSelection();
            // _this.tableData.forEach(function (e) {
            //     _this.$set(e, 'edit', false)
            // })
            _this.tableData = JSON.parse(JSON.stringify(_this.tableDataTemp));
        },

        //保存编辑
        saveEdit: function saveEdit() {
            var _this = this;
            var arrTemp = [];
            _this.multipleSelection.forEach(function(param) {
                arrTemp.push({
                    thr_order_id: param.orderId,
                    plat_cd: param.platCd,
                    delivery_warehouse_code: param.warehouse,
                    logistics_company_code: param.expeCompanyCd,
                    shipping_methods_code: param.logisticModelId,
                    electronic_single_code: param.surfaceWayGetCd,
                    waybill_number: param.trackingNumber
                });
            });
            var postData = {
                orders: arrTemp
            };
            axios
                .post("/index.php?g=OMS&m=Patch&a=logisticsUpdate", postData)
                .then(function(response) {
                    if (response.data.status == 200000) {
                        _this.isEditState = false;
                        _this.multipleSelection.forEach(function(e) {
                            _this.$set(e, "edit", false);
                        });
                        _this.$refs.multipleTable.clearSelection();
                        _this.getOutGolingList(_this.searchData);
                    } else {
                        _this.$message.error(_this.$lang(response.data.msg));
                    }
                })
                .catch(function(err) {
                    console.log(err);
                });
        },

        //切换物流公司
        changeExpeCom: function changeExpeCom(row) {
            var _this = this;
            row.expeCompanyDatas.forEach(function(e) {
                if (e.logisticsCode == row.expeCompanyCd) {
                    _this.$set(row, "logisticDatas", e.logisticsMethod);
                }
            });
        },
        //切换面单获取方式
        changeSurface: function changeSurface(row) {
            this.$set(row, "disabled", row.surfaceWayGetCd == "N002010100");
        },
        selectCompany: function selectCompany() {
            var _this = this;
            axios
                .post("index.php?g=Oms&m=OrderPresent&a=get_log_company", {
                    company_code: _this.selectedLogisticsCompanys
                })
                .then(function(res) {
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
        downloadTemp: function downloadTemp() {
            this.TemplateDownloadDialog = true;
        },
        TemplateDownQuery: function TemplateDownQuery() {
            this.TemplateDownloadDialog = false;
            if(this.TemplateDownRadio == 1) {
                window.open("/index.php?g=oms&m=outStorage&a=downloadTemplate");
            } else {
                window.open("/index.php?g=oms&m=AfterSale&a=downloadBatchAfterSaleTemplate");
            }
        },
        delivery: function delivery() {
            var _this = this;
            var ordIdArr = [];
            _this.multipleSelection.forEach(function(ele) {
                ordIdArr.push(ele.b5cOrderNo);
            });
            var postData = {
                data: {
                    query: {
                        ordId: ordIdArr
                    }
                }
            };
            if (ordIdArr.length < 1) {
                _this.$message.warning(this.$lang("清先选择要发货的订单"));
            } else {
                axios
                    .post("/index.php?g=oms&m=OutGoing&a=mulDeliverGoods", postData)
                    .then(function(response) {
                        if (response.data.code == 2000) {
                            sessionStorage.setItem(
                                "outGoingOrders",
                                JSON.stringify(response.data.data)
                            );
                            _this.toDetail();
                        } else {
                            _this.$message.error(_this.$lang(response.data.msg));
                        }
                    })
                    .catch(function(err) {
                        console.log(err);
                    });
            }
        },
        checkIsNone: function checkIsNone() {
            if (!this.multipleSelection) {
                return false;
            }
        },
        handleClose: function handleClose(done) {
            done();
        },
        showDoBack: function showDoBack() {
            if (this.multipleSelection.length < 1) {
                this.$message.error(this.$lang("请先选择要退回的订单"));
            } else {
                this.dialogVisible01 = true;
            }
        },
        cancelStatus: function cancelStatus() {
            this.dialogVisible01 = false;
        },
        finishedStatus: function finishedStatus() {
            this.dialogTableVisible = false;
            this.dialogVisible01 = false;
            setTimeout(function() {
                VM.getOutGolingList(VM.searchData);
            }, 800);
        },
        doStatus: function doStatus() {
            var _this = this;
            var ordIdArr = [];
            _this.multipleSelection.forEach(function(ele) {
                ordIdArr.push(ele.b5cOrderNo);
            });
            var postData = {
                data: {
                    query: {
                        ordId: ordIdArr,
                        state: _this.backToStatus,
                        from: 'N001820900'
                    }
                }
            };
            console.log(ordIdArr)
            console.log(_this.backToStatus)
            if (!_this.backToStatus) {
                _this.$message.error(this.$lang("请先选择要退回的状态"));
            } else if (_this.backToStatus == 'N001821000') {
                _this.innerVisible = true
            } else {
                // axios
                //   .post("/index.php?g=oms&m=commonData&a=orderReBack", postData)
                //   .then(function (response) {
                //     if (response.data.code == 2000) {
                //       if ($.isArray(response.data.data.pageData)) {
                //         _this.dialogTableVisible = true;
                //         _this.gridData = response.data.data.pageData;
                //       } else {
                //         _this.$message.error(_this.$lang(response.data.data.pageData));
                //       }
                //     } else {
                //       _this.$message.error(_this.$lang(response.data.msg));
                //     }
                //   })
                //   .catch(function (err) {
                //     console.log(err);
                //   });
            }
        },
        backPatch: function() {
            var _this = this;
            var ordIdArr = [];
            _this.multipleSelection.forEach(function(ele) {
                ordIdArr.push(ele.b5cOrderNo);
            });
            var postData = {
                data: {
                    query: {
                        ordId: ordIdArr,
                        state: _this.backToStatus,
                        from: 'N001820900'
                    }
                }
            };
            console.log(ordIdArr)
            console.log(_this.backToStatus)
            axios.post('/index.php?g=oms&m=commonData&a=orderReBack', postData)
                .then(function(response) {
                    if (response.data.code == 2000) {
                        // var result = response.data.data.pageData;
                        // _this.dialogTableVisible = true;
                        _this.innerVisible = false;
                        // _this.gridData = [];
                        // for (var i in result) {
                        //     _this.gridData.push(result[i])
                        // }
                    } else {
                        _this.$message.error(_this.$lang(response.data.msg));
                    }
                }).catch(function(err) {
                    console.log(err);
                });
        },
        getPlatformShop: function getPlatformShop(type) {
            var postData = {
                plat_form: []
            };
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
            var _this = this;
            axios
                .post("/index.php?g=Oms&m=Order&a=storesGet", postData)
                .then(function(response) {
                    if (response.data.status == 200000) {
                        VM.shopStatus = response.data.data;
                    } else {
                        VM.$message.error(_this.$lang(response.data.info));
                    }
                })
                .catch(function(err) {
                    console.log(err);
                });
        },

        flowJump: function flowJump(e) {
            var _this4 = this;

            var path = [{
                    m: "order_present",
                    a: "OrderPresentList",
                    title: this.$lang("待预派")
                },
                {
                    m: "patch",
                    a: "lists",
                    title: this.$lang("待派单")
                },
                {
                    m: 'pending_order',
                    a: 'pending_list',
                    title: this.$lang('待获取运单号')
                },
                {
                    m: "picking",
                    a: "pickingList",
                    title: this.$lang("待拣货")
                },
                {
                    m: "pick_apart",
                    a: "PickApartList",
                    title: this.$lang("待分拣")
                },
                {
                    m: "check_order",
                    a: "checkingList",
                    title: this.$lang("待核单")
                },
                {
                    m: "out_going",
                    a: "outList",
                    title: this.$lang("待出库")
                },
                {
                    m: "out_storage",
                    a: "listPage",
                    title: this.$lang("已出库")
                }
            ];

            setTimeout(function() {
                var url = "index.php?g=oms&m=order_present&a=OrderPresentList";
                $(".el-step__head").each(function(i, e) {
                    $(e).click(function() {
                        if (_this4.active == i) {
                            window.location.reload();
                        } else {
                            url = "/index.php?g=oms&m=" + path[i].m + "&a=" + path[i].a;
                            newTab(url, path[i].title);
                        }
                    });
                });
            }, 1000);
        },
        getTrack: function(item) {
            this.trackData = [];
            var _this = this;
            var param = {
                data: {
                    query: {
                        orderId: item.orderId,
                        trackingNumber: item.trackingNumber,
                        platCd: item.platCd
                    }
                }
            };
            axios
                .post("/index.php?g=oms&m=OutStorage&a=feeding", param)
                .then(function(res) {
                    console.log(res);
                    // let aaa = [
                    //   {date: '2019-09-25 11:11:54',remark: '啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大大大啊大大'},
                    //   {date: '2019-09-25 11:11:54',remark: '啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大大大啊大大'},
                    //   {date: '2019-09-25 11:11:54',remark: '啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大大大啊大大'},
                    //   {date: '2019-09-25 11:11:54',remark: '啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大大大啊大大'},
                    //   {date: '2019-09-25 11:11:54',remark: '啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大啊大大大大啊大大'},
                    // ]
                    // _this.trackData = aaa;
                    // var weekArray = new Array("星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六");

                    // // var week = weekArray[new Date(date).getDay()];//注意此处必须是先new一个Date
                    // if (_this.trackData) {
                    //   _this.trackData.forEach(function (ele, ind) {
                    //     _this.$set(ele, "week", weekArray[new Date(ele.date.split(' ')[0]).getDay()]);
                    //     _this.$set(ele, "day", ele.date.split(' ')[0]);
                    //     _this.$set(ele, "time", ele.date.split(' ')[1]);
                    //   });
                    // }

                    var weekArray = new Array("星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六");
                    if (res.data.code == 2000) {
                        _this.trackData = res.data.data.pageData;
                        if (_this.trackData) {
                            _this.trackData.forEach(function(ele, ind) {
                                _this.$set(ele, "week", weekArray[new Date(ele.date.split(' ')[0]).getDay()]);
                                _this.$set(ele, "day", ele.date.split(' ')[0]);
                                _this.$set(ele, "time", ele.date.split(' ')[1]);
                            });
                            _this.trackData.reverse();
                        }
                    }
                });
        },
        seeTrack: function() {
            this.trackIndex = this.trackData.length;
        },
        closeTrack: function() {
            this.trackIndex = 4;
        },
        
        // 商品名称拆分及翻译
        transName(name) {
            const arr = name.split(' X')
            return this.$lang(arr[0]) + ' X' + arr[1]
        }
    },
    destroyed() {
        console.log(1);
    },
    created: function created() {
        console.log('created');
        // window.onscroll = function() {
        //     if (document.documentElement.scrollTop != 0) {
        //         sessionStorage.setItem("outStorageOffsetTop", document.documentElement.scrollTop);
        //     }
        // }
        this.pickerOptions = {
            shortcuts: [{
                    text: this.$lang("最近一周"),
                    onClick: function onClick(picker) {
                        var end = new Date();
                        var start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                        picker.$emit("pick", [start, end]);
                    }
                },
                {
                    text: this.$lang("最近一个月"),
                    onClick: function onClick(picker) {
                        var end = new Date();
                        var start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                        picker.$emit("pick", [start, end]);
                    }
                },
                {
                    text: this.$lang("最近三个月"),
                    onClick: function onClick(picker) {
                        var end = new Date();
                        var start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                        picker.$emit("pick", [start, end]);
                    }
                }
            ]
        };
        setTimeout(function() {
            VM.getOutGolingList(VM.searchData);
        }, 300);
        this.getOutGolingListMenu();
        this.getSite();
        this.flowJump();
    },
    watch: {
        //自动监测搜索条件的修改
        channelData: {
            handler: function handler(newValue, oldValue) {
                this.sites = [];
                this.getSite('search', newValue)
                this.siteAll('notClick')
            },

            deep: true
        },
        packData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "gudsType", newValue);
            },

            deep: true
        },
        sortData: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "sort", newValue);
            },

            deep: true
        },
        remarkMsg: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "remarkMsg", newValue);
            },

            deep: true
        },
        msgCd1: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "msgCd1", newValue);
            },

            deep: true
        },
        selectedCountries: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "country", newValue);
            },

            deep: true
        },
        selectedShops: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "platCd", newValue);
            },

            deep: true
        },
        selectedWarehouses: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "warehouseCode", newValue);
            },

            deep: true
        },
        selectedLogisticsCompanys: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "b5cLogisticsCd", newValue);
            },

            deep: true
        },
        selectedShippingMethods: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "logisticModel", newValue);
            },

            deep: true
        },
        selectedFreightType: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "freightType", newValue);
            },

            deep: true
        },
        selectedSalesTeam: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "saleTeamCd", newValue);
            },

            deep: true
        },
        deliveryStatus: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "deliveryStatus", newValue);
            },

            deep: true
        },
        selectLog: {
            handler: function handler(newValue, oldValue) {
                var data = this.selectLogFail.concat(this.selectLog);
                this.$set(this.searchData.data.query, "loginStatus", data);
            },

            deep: true
        },
        selectLogFail: {
            handler: function handler(newValue, oldValue) {
                var data = this.selectLogFail.concat(this.selectLog);
                this.$set(this.searchData.data.query, "loginStatus", data);
            },

            deep: true
        },
        timeSort: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "search_time_type", newValue);
            },

            deep: true
        },
        logistics_abnormal_status: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "logistics_abnormal_status", newValue);
            },
            deep: true
        },
        dateRange: {
            handler: function handler(newValue, oldValue) {
                newValue = newValue || [];
                this.$set(this.searchData.data.query, "search_time_left", newValue[0]);
                this.$set(this.searchData.data.query, "search_time_right", newValue[1]);
            },

            deep: true
        },
        //搜索条件数据修改，自动请求接口
        searchData: {
            handler: function handler(newValue, oldValue) {
                this.currentPage = 1;
                this.exportData = JSON.parse(JSON.stringify(newValue));
                if (!this.siteData.length) {
                    this.getOutGolingList(newValue, 'all');
                } else if (this.channelData.length && this.siteData.length) {
                    clearTimeout(VM.iTimer)
                    VM.iTimer = setTimeout(function() {
                        VM.getOutGolingList(newValue);
                    }, 500)
                } else {
                    clearTimeout(VM.iTimer)
                    VM.iTimer = setTimeout(function() {
                        VM.getOutGolingList(newValue);
                    }, 500)
                }
                this.isEditState = false;
            },

            deep: true
        }
    },
    beforeCreate: function beforeCreate() {},

    mounted: function mounted() {
        // window.addEventListener('scroll', this.handleScroll)
    },
    filters: {
        formatDate: function formatDate(time) {
            var date = new Date(time);
            return _formatDate(date, "yyyy-MM-dd hh:mm:ss");
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
            return orderIds.join("-");
        }
    },
});

//时间戳转日期
function _formatDate(date, fmt) {
    if (/(y+)/.test(fmt)) {
        fmt = fmt.replace(
            RegExp.$1,
            (date.getFullYear() + "").substr(4 - RegExp.$1.length)
        );
    }
    var o = {
        "M+": date.getMonth() + 1,
        "d+": date.getDate(),
        "h+": date.getHours(),
        "m+": date.getMinutes(),
        "s+": date.getSeconds()
    };
    for (var k in o) {
        if (new RegExp("(" + k + ")").test(fmt)) {
            var str = o[k] + "";
            fmt = fmt.replace(
                RegExp.$1,
                RegExp.$1.length === 1 ? str : padLeftZero(str)
            );
        }
    }
    return fmt;
}

function padLeftZero(str) {
    return ("00" + str).substr(str.length);
}