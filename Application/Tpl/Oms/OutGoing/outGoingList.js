"use strict";

var _data;

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
    el: "#outGoing-list",
    components: {},
    data: ((_data = {
            fullscreenLoading: false,
            selectedStatus: [],
            bwcOrderStatus: [],
            active: 6,
            isClicked: false,
            isWeighted: "",
            thirdDeliverStatus: [],
            isWeightedList: [],
            iTimer: null,
            form: {},
            exportData: {},
            surfaceWayVal: "",
            isEditState: false,
            surfaceWayGet: [],
            tableLoading: true,
            dialogTableVisible: false,
            gridData: [],
            printData: {},
            gridDataTemp: {},
            systemDock: [],
            isFinished01: false,
            isFinished02: false,
            selectaftersalesTypeStatus: '',
            innerVisible: false
        }),
        _defineProperty(_data, "dialogTableVisible", false),
        _defineProperty(_data, "dialogVisible01", false),
        _defineProperty(_data, "backToStatus", ""),
        _defineProperty(_data, "searchData", {
            data: {
                query: _defineProperty({
                        after_sale_type: '',
                        platForm: [],
                        ordId: [],
                        platCd: "",
                        sort: "",
                        warehouseCode: "",
                        b5cLogisticsCd: "",
                        logisticModel: "",
                        saleTeamCd: "",
                        third_deliver_status: "",
                        pageSize: 20,
                        search_time_type: "orderTime",
                        search_condition: "orderNo"
                    },
                    "sort",
                    "orderTime"
                )
            }
        }),
        _defineProperty(_data, "channels", {}),
        _defineProperty(_data, "channelData", []),
        _defineProperty(_data, 'siteData', []),
        _defineProperty(_data, 'sites', []),
        _defineProperty(_data, 'showMore', false),
        _defineProperty(_data, 'isSiteAll', true),
        _defineProperty(_data, "isChannelALL", true),
        _defineProperty(_data, "packData", []),
        _defineProperty(_data, "isPackALL", true),
        _defineProperty(_data, "sort", {}),
        _defineProperty(_data, "sortData", ""),
        _defineProperty(_data, "countryStatus", []),
        _defineProperty(_data, "selectedCountries", []),
        _defineProperty(_data, "shopStatus", {}),
        _defineProperty(_data, "selectedShops", []),
        _defineProperty(_data, "warehouseStatus", {}),
        _defineProperty(_data, "selectedWarehouses", []),
        _defineProperty(_data, "logisticsCompanyStatus", {}),
        _defineProperty(_data, "selectedLogisticsCompanys", []),
        _defineProperty(_data, "shippingMethodsStatus", []),
        _defineProperty(_data, "shippingMethodsStatusTemp", []),
        _defineProperty(_data, "selectedShippingMethods", []),
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
        _defineProperty(_data, "dateRange", "")),
    methods: {
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
            }).catch(()=>{
                _this.$message.error('获取站点异常');
                _this.getPlatformShop('notFirst');

                if (type === 'search') {
                    _this.getOutGolingList(_this.searchData, 'all')
                }
            });
        },
        focusSurface: function focusSurface(row) {
            axios
                .post("index.php?g=OMS&m=Patch&a=faceOrderGet", {
                    logistic_model_id: row.logisticModelId
                })
                .then(function(res) {
                    if (res.data.status == 200000) {
                        Vue.set(row, "surfaceWayGet", res.data.data);
                    } else {
                        Vue.set(row, "surfaceWayGet", [{
                            CD: row.surfaceWayGetCd,
                            CD_VAL: row.surfaceWayGetCdNm
                        }]);
                    }
                });
        },
        changeMethod: function changeMethod(row) {
            if (row.logisticModelId) {
                axios
                    .post("index.php?g=OMS&m=Patch&a=faceOrderGet", {
                        logistic_model_id: row.logisticModelId
                    })
                    .then(function(res) {
                        if (res.data.status == 200000) {
                            var data = res.data;
                            if (data.data.length) {
                                Vue.set(row, "surfaceWayGetCdNm", data.data[0].CD_VAL);
                                Vue.set(row, "surfaceWayGetCd", data.data[0].CD);
                            } else {
                                Vue.set(row, "surfaceWayGetCdNm", "");
                                Vue.set(row, "surfaceWayGetCd", "");
                            }
                        } else {
                            Vue.set(row, "surfaceWayGet", [{
                                CD: row.surfaceWayGetCd,
                                CD_VAL: row.surfaceWayGetCdNm
                            }]);
                        }
                    });
            }
        },
        getTracking: function getTracking() {
            var _this = this;
            var orderIdsArr = [];
            _this.multipleSelection.forEach(function(ele) {
                orderIdsArr.push({
                    thr_order_id: ele.orderId,
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
                this.$message.warning("请先选择一个订单");
            } else {
                axios
                    .post("/index.php?g=OMS&m=Patch&a=electronicOrder", postData)
                    .then(function(response) {
                        var data = response.data;
                        if (data.status != 200000) {
                            VM.$message.error(_this.$lang(data.info));
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
            var _this = this;
            $(".excel-delivery")
                .find(".update-file-content")
                .click();


        },
        updatePic: function updatePic(val) {
            //创建FormData对象
            var data = new FormData();
            var _this = this;
            //为FormData对象添加数据
            data.append("file", $(event.srcElement)[0]["files"][0]);
            $.ajax({
                    url: "/index.php?g=oms&m=OutGoing&a=uploadExcel",
                    type: "POST",
                    dataType: "JSON",
                    contentType: false,
                    processData: false,
                    data: data,
                    cache: false
                })
                .success(function(data) {
                    if (data.code == 2000) {
                        sessionStorage.setItem("outGoingOrders", JSON.stringify(data.data));
                        setTimeout(function() {
                            _this.getOutGolingList(_this.searchData);
                        }, 3000)
                        VM.toDetail();
                    } else {
                        VM.$message.error(_this.$lang('上传文件异常'));
                    }
                })
                .error(function() {
                    console.log("error");
                })
                .complete(function() {
                    $("#excel-btn-content")
                        .find("input")
                        .val("");
                });
        },

        //搜索条件数据
        getOutGolingListMenu: function getOutGolingListMenu() {
            var _query3;
            var _this = this;
            var postData = {
                data: {
                    query: ((_query3 = {
                            platform: true,
                            gudsType: true,
                            countries: true,
                            site_cd: true,
                            weight: true
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
                        _defineProperty(_query3, "bwcOrderStatus", true),

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
                        _this.surfaceWayGet = [];
                        data.data.surfaceWayGet.forEach(function(el) {
                            _this.surfaceWayGet.push({
                                CD: el.cd,
                                CD_VAL: el.cdVal
                            });
                        });

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
                        _this.bwcOrderStatus = data.data.bwcOrderStatus;
                        _this.salesTeamStatus = data.data.saleTeams; //销售团队
                        _this.downQueryStatus =
                            data.data.pickingTimeRangeIndex.keyWordInput[0]; //查询条件
                        _this.time = data.data.pickingTimeRangeIndex.keyWordRange[0]; //查询条件
                        _this.sort = data.data.pickingSortType; //排序方式
                        _this.isWeightedList = data.data.weight;
                        _this.isFinished01 = true;
                        $("#outGoing-list").css("visibility", "visible");
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
        getOutGolingList: function getOutGolingList(data, type) {
            var _this = this;
            _this.tableData = []
            var data = JSON.parse(JSON.stringify(data))

            _this.tableLoading = true;
            data.data.query.pageIndex = this.currentPage;
            if (type == 'all') {
                data.data.query.platForm = [];
                this.sites.forEach(function(element) {
                    data.data.query.platForm.push(element.CD)
                })
            }
            axios
                .post("/index.php?g=oms&m=OutGoing&a=outGoingListData", data)
                .then(function(response) {
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
                                _this.$set(ele, "surfaceWayGet", _this.surfaceWayGet);
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
        selectSort: function selectSort(key) {
            $(event.srcElement)
                .siblings()
                .removeClass("active")
                .end()
                .addClass("active");
            // this.sortData = event.currentTarget.getAttribute('data-cd');
            this.sortData = key;

            function countDay(begin, end) {
                var duration = +new Date(end) - +new Date(begin);
                return duration / 1000 / 60 / 60 / 24;
            }
        },
        toggleRemark: function toggleRemark() {
            $(event.srcElement).toggleClass("isActive");
            this.remarkMsg = this.remarkMsg == 0 ? 1 : 0;
        },
        toggleError: function toggleError() {
            $(event.srcElement).toggleClass("isActive");
            this.msgCd1 = this.msgCd1 == 0 ? 1 : 0;
        },
        // //跳转详情页
        toDetail: function toDetail() {
            var dom = document.createElement("a");
            var _href = "/index.php?g=oms&m=outGoing&a=outListDetail";
            // dom.setAttribute("onclick", "opennewtab(this,'出库结果页')");
            dom.setAttribute(
                "onclick",
                "opennewtab(this,'" + this.$lang("出库结果页") + "')"
            );
            dom.setAttribute("_href", _href);
            dom.onclick();
        },
        weightSend: function weightSend() {
            // _href="/index.php?g=oms&m=out_going&a=weightShipping"
            var dom = document.createElement("a");
            var _href = "/index.php?g=oms&m=out_going&a=weightShipping";
            // dom.setAttribute("onclick", "opennewtab(this,'出库结果页')");
            dom.setAttribute(
                "onclick",
                "opennewtab(this,'" + this.$lang("称重发货") + "')"
            );
            dom.setAttribute("_href", _href);
            dom.onclick();
        },
        toOrderDetail: function toOrderDetail(order_no, orderId, platCD) {
            var dom = document.createElement("a");
            var _href =
                "/index.php?g=OMS&m=Order&a=orderDetail&order_no=" +
                order_no +
                "&thrId=" +
                orderId +
                "&platCode=" +
                platCD+"&isShowEditButtonByOmsListEntry="+false;
            dom.setAttribute(
                "onclick",
                "opennewtab(this,'" + this.$lang("订单详情") + "')"
            );
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
            var param = JSON.parse(JSON.stringify(_this.searchData));
            param.data.query.ordId = this.multipleSelection.reduce(function(res, item) {
                res.push(item.b5cOrderNo);
                return res;
            }, []);

            var tmep = document.createElement("form");
            tmep.action = "/index.php?g=OMS&m=OutGoing&a=export";
            tmep.method = "post";
            var opt = document.createElement("input");
            opt.name = "post_data";
            if (!this.siteData.length) {
                param.data.query.platForm = [];
                this.sites.forEach(function(el) {
                    param.data.query.platForm.push(el.CD)
                })
            }
            console.log(param)
            opt.value = JSON.stringify(param);
            tmep.appendChild(opt);
            document.body.appendChild(tmep);
            tmep.submit();
            $(tmep).remove();
            tmep = null
            console.log(tmep)
        },
        //重置功能
        doReset: function doReset() {
            window.location.reload()
                // this.channelALL();
                // this.$set(this.searchData.data.query, "search_value", "");
                // this.$set(this.searchData.data.query, "search_condition", "orderNo");
                // this.selectedCountries = [];
                // this.selectedShops = [];
                // this.selectedWarehouses = [];
                // this.selectedLogisticsCompanys = [];
                // this.selectedShippingMethods = [];
                // this.selectedSalesTeam = [];
                // this.selectedStatus = [];
                // this.isWeighted = "";
                // this.remarkMsg = 0;
                // this.msgCd1 = 0;
                // this.timeSort = "orderTime";
                // this.searchKeywords = "";
                // this.dateRange = [];
                // this.selectedDownQuery = "orderNo";
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
                            _this.$set(ele, "edit", true);
                            _this.$set(ele, "expeCompanyDatas", [{
                                logisticsCode: ele.expeCompanyCd,
                                logisticsName: ele.expeCompany
                            }]);
                            _this.$set(ele, "logisticDatas", [{
                                id: ele.logisticModelId,
                                logisticsMode: ele.logisticModel
                            }]);
                            if (ele.findOrderJson && ele.findOrderJson.code == 2000) {
                                //匹配默认仓库
                                var warehouse = ele.findOrderJson.data.warehouse,
                                    wareMatch = false;
                                for (var i = 0, len = warehouse.length; i < len; i++) {
                                    if (e.warehouse == warehouse[i].cd) {
                                        var res = false;
                                        for (var v in warehouse[i].lgtModel) {
                                            if (
                                                warehouse[i].lgtModel[v].logisticsCode ==
                                                ele.expeCompanyCd
                                            ) {
                                                res = true;
                                                break;
                                            }
                                        }
                                        if (!res) {
                                            warehouse[i].lgtModel.push({
                                                logisticsCode: ele.expeCompanyCd,
                                                logisticsName: ele.expeCompany
                                            });
                                        }
                                        _this.$set(ele, "expeCompanyDatas", warehouse[i].lgtModel);
                                        wareMatch = true;
                                        break;
                                    }
                                }

                                //默认仓库无法匹配时 取推荐最优的
                                !wareMatch &&
                                    ele.findOrderJson.data.warehouse.forEach(function(item) {
                                        if (item.isShow) {
                                            var res = false;
                                            for (var v in item.lgtModel) {
                                                if (
                                                    item.lgtModel[v].logisticsCode == ele.expeCompanyCd
                                                ) {
                                                    res = true;
                                                    break;
                                                }
                                            }

                                            if (!res) {
                                                item.lgtModel.push({
                                                    logisticsCode: ele.expeCompanyCd,
                                                    logisticsName: ele.expeCompany
                                                });
                                            }
                                            _this.$set(ele, "expeCompanyDatas", item.lgtModel);
                                        }
                                    });
                            }
                        }
                    });
                });
            } else {
                _this.$message.warning(this.$lang("请先选择要编辑的订单。"));
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
                    waybill_number: param.trackingNumber,
                    id:param.id
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
                        _this.$message({
                            type: "warning",
                            message: _this.$lang("成功保存 ：") +
                                response.data.data.order +
                                _this.$lang("条}，保存失败} ： ") +
                                response.data.data.error +
                                _this.$lang("条"),
                            center: true
                        });
                        _this.$refs.multipleTable.clearSelection();
                        _this.getOutGolingList(_this.searchData);
                    } else {
                        _this.$message.error(_this.$lang(response.data.info));
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
            row.logisticModelId = "";
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
            window.open("/index.php?g=oms&m=outGoing&a=downloadTemplate");
        },

        checkOrder: function(type) {
            this.fullscreenLoading = true;
            var _this = this,
                param = {
                    data: {
                        query: {
                            ordId: []
                        }
                    }
                }
            _this.multipleSelection.forEach(function(ele) {
                param.data.query.ordId.push(ele.b5cOrderNo);
            });
            axios.post('/index.php?g=oms&m=commonData&a=preCheckAndDone', param)
                .then(function(res) {
                    if (res.data.code === 3000) {
                        _this.$alert(res.data.msg, '核单提示', {
                            showCancelButton: true,
                            cancelButtonText: '取消',
                            confirmButtonText: '确定',
                            callback: function(action) {
                                if (action == 'confirm') {
                                    _this.delivery(type, 1);
                                } else {
                                    _this.delivery(type);
                                }
                            }
                        });
                    } else {
                        _this.delivery(type);
                    }
                })
        },
        delivery: function delivery(type, done) {
            var _this = this;
            var ordIdArr = [];
            _this.multipleSelection.forEach(function(ele) {
                ordIdArr.push(ele.b5cOrderNo);
            });
            var postData = {
                data: {
                    query: {
                        ordId: ordIdArr,
                        preDone: done || 0
                    }
                }
            };
            if (ordIdArr.length < 1) {
                _this.$message.warning(_this.$lang("请先选择要发货的订单"));
                _this.fullscreenLoading = false;
            } else {
                _this.isClicked = true;
                _this.$message.warning(_this.$lang("正在批量发货中..."));
                var name = type ? "directOutgoing" : "mulDeliverGoods";
                axios.post("/index.php?g=oms&m=OutGoing&a=" + name, postData)
                    .then(function(response) {
                      _this.fullscreenLoading = false;
                        if (response.data.code == 2000) {
                            sessionStorage.setItem(
                                "outGoingOrders",
                                JSON.stringify(response.data.data)
                            );

                            setTimeout(function() {
                                // _this.getOutGolingList(_this.searchData);
                                _this.doSearch()
                            }, 5000)
                            _this.toDetail();
                            // window.location.reload();
                        } else {
                            _this.$message.error(_this.$lang(response.data.msg));
                        }
                        _this.isClicked = false;
                    })
                    .catch(function(err) {
                        console.log(err);
                        _this.isClicked = false;
                        _this.fullscreenLoading = false;
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
                // VM.getOutGolingList(VM.searchData);
                VM.doSearch()
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
                        from: 'N001820800'
                    }
                }
            };
            if (!_this.backToStatus) {
                _this.$message.error(_this.$lang("请先选择要退回的状态"));
            } else if (_this.backToStatus == 'N001821000') {
                _this.innerVisible = true
            } else {
                axios
                    .post("/index.php?g=oms&m=commonData&a=orderReBack", postData)
                    .then(function(response) {
                        if (response.data.code == 2000) {
                            var result = response.data.data.pageData;
                            _this.dialogTableVisible = true;
                            _this.gridData = [];
                            for (var i in result) {
                                _this.gridData.push(result[i]);
                            }
                        } else {
                            _this.$message.error(_this.$lang(response.data.msg));
                        }
                    })
                    .catch(function(err) {
                        console.log(err);
                    });
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
                        from: 'N001820800'
                    }
                }
            };
            axios.post('/index.php?g=oms&m=commonData&a=orderReBack', postData).then(function(
                response) {
                if (response.data.code == 2000) {
                    var result = response.data.data.pageData;
                    _this.dialogTableVisible = true;
                    _this.innerVisible = false;
                    _this.gridData = [];
                    for (var i in result) {
                        _this.gridData.push(result[i])
                    }
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },
        flowJump: function flowJump(e) {
            var _this2 = this;

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
                        if (_this2.active == i) {
                            window.location.reload();
                        } else {
                            url = "/index.php?g=oms&m=" + path[i].m + "&a=" + path[i].a;
                            newTab(url, path[i].title);
                        }
                    });
                });
            }, 1000);
        },
        getPlatformShop: function getPlatformShop(type) {
            var _this = this;
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
        }
    },
    created: function created() {
        // window.onscroll = function() {
        //     if (document.documentElement.scrollTop != 0) {
        //         sessionStorage.setItem("outListOffsetTop", document.documentElement.scrollTop);
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
        var _this = this;
        setTimeout(function() {
            VM.getOutGolingList(_this.searchData);
        }, 300);
        this.getSite();
        this.getOutGolingListMenu();
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
        selectedStatus: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "bwcOrderStatus", newValue);
            },

            deep: true
        },
        selectedSalesTeam: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "saleTeamCd", newValue);
            },

            deep: true
        },
        timeSort: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "search_time_type", newValue);
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
        isWeighted: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "weight", newValue);
            },
            deep: true
        },
        thirdDeliverStatus: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.searchData.data.query, "third_deliver_status", newValue);
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
    }
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