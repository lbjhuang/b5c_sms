'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var VM = new Vue({
    el: '#order-list',
    components: {},
    data: {
        //查询条件
        search: {
            platform_cd: "",
            after_sale_no: "",
            after_sale_status: {},
            sku_id: "",
            order_no: "",
            after_sale_type: [],
            created_at: {
                start: "",
                end: ""
            },
            audit_status_cd: '',
            selectedShops: [],
        },
        plat: [],
        platData: [],
        pages: {
            per_page: 10,
            current_page: 1
        },
        channels: {}, //平台渠道
        channelData: [],
        reissueData: [],
        returnsData: [],
        refundData: [],
        baseData: [],
        afterSaleData: [],
        after_sale_no: '',
        after_sale_type: {},
        shopStatus: {}, //店铺
        showMore: false,
        isCheck: 0,
        multipleSelection: [],
        sku_id: '',
        order_no: '',
        totalCount: 0, //总条数
        tableData: [], //查询返回数据
        // 日期选择
        dateRange: '',
        pickerOptions: {},
        turnOver: [],
        reissue: [],
        returns: [],
        refund: [],
        reissueStatus: false,
        returnsStatus: false,
        refundStatus: false,
        afterSaleList: {},
        tableLoading:false,
        auditLoading:false,
        isDisabledBatchThrough:false,
        isDisabledBatchRefused:false,
        queryPost: function (url, param) {
            var headers = {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
            return axios.post(url, Qs.stringify(param), headers);
        }
    },
    methods: {
        download: function () {
            var _this = this;
            var data = this.search;
            data.platform_country_code = this.platData
            var param = {
                search: data,
                pages: this.pages,
            }
            axios({
                method: 'post',
                data: param,
                responseType: 'blob',
                url: '/index.php?g=OMS&m=afterSale&a=listExport'
            }).then(data => {
                let blob = data.data
                let reader = new FileReader()
                reader.readAsDataURL(blob)
                reader.onload = (e) => {
                    let a = document.createElement('a')
                    let fileName = `售后单列表.csv`
                    a.download = fileName
                    a.href = e.target.result
                    document.body.appendChild(a)
                    a.click()
                    document.body.removeChild(a)
                }
            })

        },
        commonData: function () {
            var _this = this;
            axios.post('/index.php?g=common&m=index&a=get_cd', {

                cd_type: {
                    refund_audit_status: "false"
                }

            }).then(function (res) {
                if (res.data.code === 2000) {
                    _this.turnOver = res.data.data.refund_audit_status
                    var all = {"CD": '', "CD_VAL": "全部"}
                    var none = {"CD": 1, "CD_VAL": "无"}
                    _this.turnOver.unshift(all, none);
                }
            })

            axios.post('/index.php?g=OMS&m=afterSale&a=afterSaleStatus').then(function (res) {
                if (res.data.code == 200) {
                    _this.reissue = res.data.data.补发
                    _this.returns = res.data.data.退货
                    _this.refund = res.data.data.退款
                }
            })

            // axios.get('/index.php?g=OMS&m=Order&a=listMenu').then(function(response) {
            //     var data = response.data;
            //     if (data.status != 200000) {
            //         this.$message.error(_this.$lang(data.msg));
            //     } else {
            //         _this.shopStatus = data.data.shop_status;
            //     }
            // }).catch(function(err) {
            //     console.log(err);
            // });

        },
        handleSelectionChange: function handleSelectionChange(val) {
            this.isCheck = val.length;
            this.multipleSelection = val;
        },
        inverseSelection: function () {
            var _this = this;
            _this.$refs.multipleTable.data.forEach(function (item) {
                _this.$refs.multipleTable.toggleRowSelection(item);
            })
        },
        //搜索条件数据
        getAfterOrderListMenu: function getAfterOrderListMenu() {
            var _this = this;
            axios.get('/index.php?g=OMS&m=Order&a=listMenu').then(function (response) {
                var data = response.data;
                if (data.status != 200000) {
                    this.$message.error(_this.$lang(data.msg));
                } else {
                    _this.channels = data.data.site_cd; //平台渠道
                    $('#order-list').css('visibility', 'visible');
                }
            })
        },
        getAfterOrderList: function getAfterOrderList(data) {
            var _this = this;
            _this.tableData = []
            data.platform_country_code = this.platData
            var param = {
                search: data,
                pages: this.pages,
            }
            _this.tableLoading = true;
            axios.post('/index.php?g=OMS&m=afterSale&a=lists', param).then(function (res) {
                if (res.data.code == 200) {
                    _this.tableLoading = false;

                    for (var x = 0; x < res.data.data.data.length; x++) {
                        if (!res.data.data.data[x].product_info) {
                            res.data.data.data[x].product_info = []
                        }
                    }
                    _this.tableData = res.data.data.data;
                    _this.tableData.forEach((obj) => {
                        if(obj.audit_status_cd === "N003170003"){
                            _this.$set(obj,"isDisabledAuditOpinion",true)

                            _this.$set(obj,"isDisabledApprove",false)
                            _this.$set(obj,"isDisabledRefused",false)


                            _this.$set(obj,"isDisabledSave",true)
                            _this.$set(obj,"isDisabledClose",true)

                        }else {
                            _this.$set(obj,"isDisabledAuditOpinion",true)

                            _this.$set(obj,"isDisabledApprove",true)
                            _this.$set(obj,"isDisabledRefused",true)


                            _this.$set(obj,"isDisabledSave",true)
                            _this.$set(obj,"isDisabledClose",true)
                        }
                        _this.$set(obj,"attachment",obj.attachment?JSON.parse(obj.attachment):"")

                    });
                    console.log("售后单列表数据", _this.tableData);

                    _this.totalCount = parseInt(res.data.data.pages.total);
                    setTimeout(function () {
                        _this.tableLoading = false;
                    }, 1000);
                } else {
                    _this.$message.error(_this.$lang(res.data.info));
                }
            })
        },
        after_sale_type_change: function (val) {
            if (val.includes(1)) {
                this.returnsStatus = false
            } else {
                this.returnsStatus = true
                // this.returnsData = []
                this.selectReturns()
                // this.search.after_sale_status.N003140001 = this.returnsData = []

            }
            if (val.includes(2)) {
                this.reissueStatus = false
            } else {
                this.reissueStatus = true
                // this.reissueData = []
                this.selectReissue()
                // this.search.after_sale_status.N003130001 = this.reissueData = []
            }
            if (val.includes(3)) {
                this.refundStatus = false
            } else {
                this.refundStatus = true
                // this.refundData = []
                this.selectRefund()
                // this.search.after_sale_status.N003150001 = this.refundData = []
            }
            if (val.length == 0) {
                this.returnsStatus = false
                this.reissueStatus = false
                this.refundStatus = false
            }

        },
        getStore: function getStore(val) {
            var _this = this;
            var postData = [];
            if (val) {
                postData = val
            } else {
                _this.plat.forEach(function (element) {
                    postData.push(element.CD)
                })
            }

            axios.post('/index.php?g=Oms&m=Order&a=storesGet', {
                "plat_form": postData
            }).then(function (response) {
                if (response.data.status == 200000) {
                    _this.shopStatus = response.data.data;
                } else {
                    _this.$message.error(_this.$lang(response.data.info));
                }
            }).catch(function (err) {
                console.log(err);
            });
        },
        // 平台渠道
        selectChannel: function selectChannel(item) {
            var _this = this;
            _this.plat = [];
            _this.platData = [];
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
                    this.channelData.forEach(function (val, index) {
                        if (val == item.CD) {
                            _this.channelData.splice(index, 1);
                        }
                    });
                }
                this.plat = [];
                axios.post('/index.php?g=oms&m=order&a=getSite', {plat_cd: _this.channelData}).then(function (res) {
                    var data = res.data;
                    if (data && data.code === 2000) {
                        for (var x = 0; x < data.data.length; x++) {
                            data.data[x].checked = false
                        }
                        _this.plat = data.data
                    } else {
                        _this.$message.error(res.data.msg || '获取站点异常');
                    }
                    _this.getStore();

                }).catch(function (error) {
                    _this.$message.error('获取站点异常');
                    _this.getStore();
                });
            }
            _this.search.platform_cd = _this.channelData


        },
        // 选择站点
        selectPlat: function (item) {
            var _this = this;

            item.checked = !item.checked;
            if (item.checked) {
                this.platData.push(item.CD);
            } else {
                this.platData.forEach(function (val, index) {
                    if (val == item.CD) {
                        _this.platData.splice(index, 1);
                    }
                });
            }
            /*            axios.post('/index.php?g=OMS&m=afterSale&a=lists', {plat_cd:_this.channelData}).then(function (res) {
             var data = res.data;
             if(data.code === 2000){
             _this.plat = data.data
             }

             }).catch(function (error) {

             });*/


            _this.search.platform_cd = _this.channelData
            _this.getStore(_this.platData)
        },
        // 补发
        selectReissue: function (item) {
            var _this = this;
            if (!item) {
                _this.reissueData = [];
                for (var i in _this.reissue) {
                    _this.reissue[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    _this.reissueData.push(item.CD);
                } else {
                    _this.reissueData.forEach(function (val, index) {
                        if (val == item.CD) {
                            _this.reissueData.splice(index, 1);
                        }
                    });
                }
            }
            _this.search.after_sale_status.N003130001 = _this.reissueData
            _this.search.after_sale_status.N003140001 = _this.returnsData
            _this.search.after_sale_status.N003150001 = _this.refundData
            // console.log(_this.after_sale_status);
        },
        // 退货
        selectReturns: function (item) {
            var _this = this;
            if (!item) {
                _this.returnsData = [];
                for (var i in _this.returns) {
                    _this.returns[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    _this.returnsData.push(item.CD);
                } else {
                    _this.returnsData.forEach(function (val, index) {
                        if (val == item.CD) {
                            _this.returnsData.splice(index, 1);
                        }
                    });
                }
            }
            _this.search.after_sale_status.N003130001 = _this.reissueData
            _this.search.after_sale_status.N003140001 = _this.returnsData
            _this.search.after_sale_status.N003150001 = _this.refundData

        },
        // 退款
        selectRefund: function (item) {
            var _this = this;
            if (!item) {
                _this.refundData = [];
                for (var i in _this.refund) {
                    _this.refund[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    _this.refundData.push(item.CD);
                } else {
                    _this.refundData.forEach(function (val, index) {
                        if (val == item.CD) {
                            _this.refundData.splice(index, 1);
                        }
                    });
                }
            }
            _this.search.after_sale_status.N003130001 = _this.reissueData
            _this.search.after_sale_status.N003140001 = _this.returnsData
            _this.search.after_sale_status.N003150001 = _this.refundData

        },
        // 售后状态
        selectAfterStatus: function selectAfterStatus(item) {
            var _this = this;
            if (!item) {
                this.afterSaleData = [];
                for (var i in this.baseData.after_sale_status) {
                    this.baseData.after_sale_status[i].checked = false;
                }
            } else {
                item.checked = !item.checked;
                if (item.checked) {
                    this.afterSaleData.push(item.CD);
                } else {
                    this.afterSaleData.forEach(function (val, index) {
                        if (val == item.CD) {
                            _this.afterSaleData.splice(index, 1);
                        }
                    });
                }
            }
            _this.search.after_sale_status = _this.afterSaleData
        },
        //跳转售后详情页
        toDetail: function toDetail(after_sale_no, order_no, type_name, val) {
            // console.log(val.order_id);
            // console.log(val.platform_country_code);

            // sessionStorage.setItem("aftersalelistOffsetTop", document.documentElement.scrollTop);
            if (type_name == '退货') {
                this.router(this.$lang('退货详情'), 'return_detail', after_sale_no, order_no)
            } else if (type_name == '补发') {
                this.router(this.$lang('补发详情'), 'reissue_detail', after_sale_no, order_no)
            } else if (type_name == '退款') {
                var dom = document.createElement('a');
                var _href = "/index.php?g=OMS&m=AfterSale&a=refund_detail&after_sale_no=" + after_sale_no + "&order_no=" + order_no + "&order_id=" + val.order_id + "&platform_country_code=" + val.platform_country_code;
                dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('退款详情') + "')");
                dom.setAttribute("_href", _href);
                dom.click();
            }

        },
        router: function router(title, _html, after_sale_no, order_no) {
            var dom = document.createElement('a');
            var _href = "/index.php?g=OMS&m=AfterSale&a=" + _html + "&after_sale_no=" + after_sale_no + "&order_no=" + order_no;
            dom.setAttribute("onclick", "opennewtab(this,'" + title + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // 查询功能
        doSearch: function doSearch() {
            this.getAfterOrderList(this.search);
        },

        //重置功能
        doReset: function doReset() {
            window.location.reload();
        },
        submit_keywords: function () {
            this.doSearch()
        },
        //是否为空
        isNone: function isNone(dom) {
            var num = 0;
            dom.each(function () {
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
        isShow: function (index, val) {
            if (index == 0) {
                return true
            } else if (index != 0 && val.is_show_all_sku == true) {
                return true
            } else {
                return false
            }
        },
        iconClick(index, type) {
            console.log(index)
            this.$set(this.tableData[index], 'is_show_all_sku', type)
        },
        handleSizeChange: function handleSizeChange(val) {
            this.pages = {
                per_page: val,
                current_page: 1
            },
                this.getAfterOrderList(this.search);
        },
        handleCurrentChange: function handleCurrentChange(val) {
            this.pages.current_page = val;
            this.isDisabledBatchThrough = false;
            this.isDisabledBatchRefused = false;
            this.getAfterOrderList(this.search);
        },
        getAfterSaleStatus() {
            let param = {
                cd_type: {
                    after_sale_status: true,
                }
            }
            this.queryPost("/index.php?g=common&m=index&a=get_cd", param).then((res) => {
                var _data = res.data;
                if (_data.code === 2000) {
                    this.baseData = _data.data;
                } else {
                    this.$message.error(this.$lang(_data.msg));
                }
            }).catch(function (err) {
                console.log(err);
            });
        },
        loading: function () {
            var show_iframe = window.parent.document.querySelectorAll('#iframe_box .show_iframe')
            for (var item in show_iframe) {
                if (show_iframe[item].style) {
                    // if(show_iframe[item].style.display == "block"){
                    show_iframe[item].querySelectorAll('.loading')[0].style.display = "none"
                    // }
                }

            }
        },
        //获取url参数
        getQueryVariable: function (variable) {
            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split("=");
                if (pair[0] == variable) {
                    return pair[1];
                }
            }
            return false;
        },
        //跳转详情页
        toOrderDetail: function toOrderDetail(order_no, thirdId, platCD) {
            // sessionStorage.setItem("aftersalelistOffsetTop", document.documentElement.scrollTop);
            var dom = document.createElement('a');
            var _href = "/index.php?g=OMS&m=Order&a=orderDetail&order_no=" + order_no + "&thrId=" + thirdId + "&platCode=" + platCD;
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('订单详情') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        refundDetail: function (val) {
            console.log(val);

            var dom = document.createElement('a');
            // var _href = "/index.php?g=OMS&m=Order&a=applyAfterSales&order_no=" + val.third_party_order_number + "&thrId=" + val.third_order_number + "&platCode=" + val.plat_cd + "&paymentDate=" + val.payment_time + "&pay_the_total_price=" + val.pay_the_total_price + "&currency=" + val.currency;
            var _href = "/index.php?g=OMS&m=Order&a=applyAfterSales&order_no=" + val.order_id + "&thrId=" + val.order_no + "&platCode=" + val.platform_country_code + "&after_sale_no=" + val.after_sale_no + "&audit_status_cd_val=" + val.audit_status_cd_val + "&type=approval";
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('退款审核') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        onSelectionChange(selection, row){
            console.log("selection",selection);
         console.log("用户手动操作", row);
        },
        onApprove(row){
            if(row.approved){
                this.$set(row,"isDisabledAuditOpinion",false);
                this.$set(row,"isDisabledSave",false);
                this.$set(row,"isDisabledClose",false);
                this.$set(row,"refused",false);
            }else {
                this.$set(row,"isDisabledAuditOpinion",true);
                this.$set(row,"isDisabledSave",true);
                this.$set(row,"isDisabledClose",true);
            }

            console.log("同意",row);
        },
        onRefused(row){

            if(row.refused){
                this.$set(row,"isDisabledAuditOpinion",false);
                this.$set(row,"isDisabledSave",false);
                this.$set(row,"isDisabledClose",false);
                this.$set(row,"approved",false);

            }else {
                this.$set(row,"isDisabledAuditOpinion",true);
                this.$set(row,"isDisabledSave",true);
                this.$set(row,"isDisabledClose",true);
            }

             console.log("拒绝",row);

            // row.isDisabledAuditOpinion = false;

            // obj.isDisabledApprove = false;
            // obj.isDisabledRefused = false;

            // row.isDisabledSave = false;
            // row.isDisabledClose = false;
        },
        onBatchThrough() {
            if(this.multipleSelection.length!==0){
                this.multipleSelection.forEach((obj)=>{
                    if(obj.audit_status_cd ==="N003170003"){
                        this.$set(obj,"isDisabledAuditOpinion",false)

                        this.$set(obj,"isDisabledApprove",true)
                        this.$set(obj,"isDisabledRefused",true)

                        this.$set(obj,"isDisabledSave",true)
                        this.$set(obj,"isDisabledClose",true)

                        this.$set(obj,"approved",true);
                        this.$set(obj,"refused",false);

                    }

                })
                this.isDisabledBatchRefused = true;
            } else {
                this.$message.error(this.$lang("请先勾选表格数据"));
            }

        },
        onBatchRefused(){
            if(this.multipleSelection.length!==0){
                this.multipleSelection.forEach((obj)=>{
                    if(obj.audit_status_cd ==="N003170003"){
                        this.$set(obj,"isDisabledAuditOpinion",false)

                        this.$set(obj,"isDisabledApprove",true)
                        this.$set(obj,"isDisabledRefused",true)

                        this.$set(obj,"isDisabledSave",true)
                        this.$set(obj,"isDisabledClose",true)

                        this.$set(obj,"approved",false);
                        this.$set(obj,"refused",true);
                    }
                })
                this.isDisabledBatchThrough = true;
            } else {
                this.$message.error(this.$lang("请先勾选表格数据"));
            }

        },
        onBatchClose(){
            if(this.multipleSelection.length!==0){
                this.multipleSelection.forEach((obj)=>{
                    if(obj.audit_status_cd ==="N003170003"){
                        this.$set(obj,"isDisabledAuditOpinion",true)

                        this.$set(obj,"isDisabledApprove",false)
                        this.$set(obj,"isDisabledRefused",false)

                        this.$set(obj,"isDisabledSave",true)
                        this.$set(obj,"isDisabledClose",true)

                        this.$set(obj,"approved",false);
                        this.$set(obj,"refused",false);
                    }

                    this.$refs.multipleTable.toggleRowSelection(obj,false)
                })
            } else {
                this.$message.error(this.$lang("请先勾选表格数据"));
            }

            this.isDisabledBatchThrough = false;
            this.isDisabledBatchRefused = false;
        },
        onBatchConfirm(){

            if(this.multipleSelection.length===0){
                this.$message.error(this.$lang("没有处于待审核的数据"))
                return;
            }

            if(!this.isDisabledBatchThrough){ // 批量通过
                this.auditLoading = true;
                let batchValues = [];
                this.multipleSelection.forEach((obj)=>{
                    if(obj.audit_status_cd ==="N003170003"){ // 注意：只有处于待审核状态的才能操作
                        batchValues.push({
                            after_sale_id: obj.id,
                            audit_status_cd: "N003170004",
                            audit_opinion: obj.audit_opinion,
                            order_no:obj.order_no
                        })
                    }

                })
                if(batchValues.length===0){
                    this.$message.error(this.$lang("没有处于待审核的数据"))
                    this.auditLoading = false;

                    return;
                }
                // let audit_status_cd= row.approved?"N003170004":"N003170005";

                axios.post('/index.php?g=OMS&m=afterSale&a=batchAuditRefund', batchValues).then((res) => {
                    let info = res.data.data;
                    console.log("批量通过",res.data.data);

                    if(res.data.code===200){
                        console.log("批量通过成功消息",res.data.data);
                        this.auditLoading = false;

                        this.isDisabledBatchThrough = false;
                        this.isDisabledBatchRefused = false;

                        let element = "";
                        info.forEach((obj)=>{
                           element+=`<p style="word-break: normal;white-space: nowrap;">${this.$lang('订单号：')}<span>"${obj.order_no}"</span><span>"${this.$lang(obj.audit_status_cd_val)}"！</span></p>`;
                        })
                        let html = `
                                 <div>
                                  ${element}
                                 </div>
                                 `;
                         console.log("html代码",html);
                        // this.$message.success(this.$lang("批量通过成功"));

                        console.log("执行完毕");
                        this.$message({
                            type: 'success',
                            dangerouslyUseHTMLString: true,
                            message:`${html}`
                        })
                    }else {
                        console.log("这里执行了");
                        this.$message.error(this.$lang(res.data.msg));
                        this.auditLoading = false;

                    }
                    this.getAfterOrderList(this.search);


                }).catch((error)=>{
                    console.log("错误消息",error);
                    this.auditLoading = false;
                    this.$message.error(this.$lang(error.msg));

                })
            }
            else { // 批量不通过
                this.auditLoading = true;
                let batchValues = [];
                this.multipleSelection.forEach((obj)=>{
                    if(obj.audit_status_cd ==="N003170003"){
                        batchValues.push({
                            after_sale_id: obj.id,
                            audit_status_cd: "N003170005",
                            audit_opinion: obj.audit_opinion,
                            order_no:obj.order_no
                        })
                    }

                })
                if(batchValues.length===0){
                    this.$message.error(this.$lang("没有处于待审核的数据"))
                    this.auditLoading = false;
                    return;
                }
                // let audit_status_cd= row.approved?"N003170004":"N003170005";

                axios.post('/index.php?g=OMS&m=afterSale&a=batchAuditRefund', batchValues).then((res) => {
                    let info = res.data.data;

                    if(res.data.code===200){
                        this.isDisabledBatchThrough = false;
                        this.isDisabledBatchRefused = false;
                        // this.$message.success(this.$lang("批量不通过成功"));
                        this.auditLoading = false;
                        console.log("第三步");

                        let element = "";
                        info.forEach((obj)=>{
                            element+=`<p style="word-break: normal;white-space: nowrap;">${this.$lang('订单号：')}<span>"${obj.order_no}"</span><span>"${this.$lang(obj.audit_status_cd_val)}"！</span></p>`;
                        })
                        let html = `
                                 <div>
                                  ${element}
                                 </div>
                                 `;
                        console.log("html代码",html);
                        this.$message({
                            type: 'success',
                            dangerouslyUseHTMLString: true,
                            message:`${html}`
                        })

                        this.getAfterOrderList(this.search);

                    }else{
                        console.log("第四步");
                        this.$message.error(this.$lang(res.data.msg));

                        this.auditLoading = false;


                    }

                }).catch((error)=>{
                    this.auditLoading = false;
                    this.$message.error(this.$lang(error.msg));

                })
            }

        },
        onConfirm(row) {
            console.log("当前行", row);
            this.auditLoading = true;
            let audit_status_cd= row.approved?"N003170004":"N003170005"; //判断时勾选了同意还是拒绝

            axios.post('/index.php?g=OMS&m=afterSale&a=batchAuditRefund', [
                {
                    after_sale_id: row.id,
                    audit_status_cd: audit_status_cd,
                    audit_opinion: row.audit_opinion,
                    order_no: row.order_no
                }
            ]).then((res) => {
                if(res.data.code===200){
                    let info = res.data.data;

                    this.auditLoading = false;

                    this.$set(row,"isDisabledAuditOpinion",true);
                    this.$set(row,"isDisabledApprove",true);
                    this.$set(row,"isDisabledRefused",true);
                    this.$set(row,"isDisabledSave",true);
                    this.$set(row,"isDisabledClose",true);
                    this.$set(row,"approved",false);
                    this.$set(row,"refused",false);
                    if(audit_status_cd==="N003170004"){ // 审核通过成功
                        // this.$message.success(this.$lang("审核通过成功"));
                        let element = "";
                        info.forEach((obj)=>{
                            element+=`<p style="word-break: normal;white-space: nowrap;">${this.$lang('订单号：')}<span>"${obj.order_no}"</span><span>"${this.$lang(obj.audit_status_cd_val)}"！</span></p>`;
                        })
                        let html = `
                                 <div>
                                  ${element}
                                 </div>
                                 `;
                        console.log("html代码",html);
                        this.$message({
                            type: 'success',
                            dangerouslyUseHTMLString: true,
                            message:`${html}`
                        })
                        this.getAfterOrderList(this.search);

                    }else { // 审核不通过成功
                        // this.$message.success(this.$lang("审核不通过成功"));
                        let element = "";
                        info.forEach((obj)=>{
                            element+=`<p style="word-break: normal;white-space: nowrap;">${this.$lang('订单号：')}<span>"${obj.order_no}"</span><span>"${this.$lang(obj.audit_status_cd_val)}"！</span></p>`;
                        })
                        let html = `
                                 <div>
                                  ${element}
                                 </div>
                                 `;
                        console.log("html代码",html);
                        this.$message({
                            type: 'success',
                            dangerouslyUseHTMLString: true,
                            message:`${html}`
                        })
                        this.getAfterOrderList(this.search);

                    }
                }else {
                    this.auditLoading = false;
                    this.$set(row,"approved",false);
                    this.$set(row,"refused",false);

                    if(audit_status_cd==="N003170004"){ //审核通过 不成功
                        this.$message.error(this.$lang(res.data.msg));

                    }else { //审核不通过 不成功
                        this.$message.error(this.$lang(res.data.msg));

                    }
                }

            }).catch((error)=>{
                this.auditLoading = false;
                if(audit_status_cd==="N003170004"){
                    this.$message.error(this.$lang(error.msg));

                }else {
                    this.$message.error(this.$lang(error.msg));

                }
                this.$set(row,"approved",false);
                this.$set(row,"refused",false);
            })
        },
        onClose(row) {
                this.$set(row,"isDisabledAuditOpinion",true);
                this.$set(row,"isDisabledApprove",false);
                this.$set(row,"isDisabledRefused",false);
                this.$set(row,"isDisabledSave",true);
                this.$set(row,"isDisabledClose",true);
                this.$set(row,"approved",false);
                this.$set(row,"refused",false);
            }
    },
    created: function created() {
        // window.onscroll = function () {
        //     if (document.documentElement.scrollTop != 0) {
        //         sessionStorage.setItem("aftersalelistOffsetTop", document.documentElement.scrollTop);
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
                    pay_order_detail
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
        this.commonData()
        this.getAfterOrderListMenu();
        this.getAfterSaleStatus();
        this.getAfterOrderList(this.search);
        this.getStore()

    },
    mounted() {
        this.loading()
    },
    watch: {
        //     //自动监测搜索条件的修改
        //     channelData: {
        //         handler: function handler(newValue, oldValue) {
        //             this.$set(this.search, "platform_cd", newValue);
        //         },
        //         deep: true
        //     },
        //     afterSaleData: {
        //         handler: function handler(newValue, oldValue) {
        //             this.$set(this.search, "after_sale_status", newValue);
        //         },
        //         deep: true
        //     },
        //     reissueData:{
        //         handler: function handler(newValue, oldValue) {
        //             console.log(this.after_sale_type);
        //             this.$set(this.search, "after_sale_type", this.after_sale_type);
        //         },
        //         deep: true
        //     },
        //     returnsData: {
        //         handler: function handler(newValue, oldValue) {
        //             console.log(this.after_sale_type);
        //             this.$set(this.search, "after_sale_type", this.after_sale_type);
        //         },
        //         deep: true
        //     },
        after_sale_no: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.search, "after_sale_no", newValue);
            },
            deep: true
        },
        // audit_status_cd: {
        //     handler: function handler(newValue, oldValue) {
        //         this.$set(this.search, "audit_status_cd", newValue);
        //     },
        //     deep: true
        // },
        sku_id: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.search, "sku_id", newValue);
            },
            deep: true
        },
        order_no: {
            handler: function handler(newValue, oldValue) {
                this.$set(this.search, "order_no", newValue);
            },
            deep: true
        },
        dateRange: {
            handler: function handler(newValue, oldValue) {
                if (newValue) {
                    this.$set(this.search.created_at, "start", newValue[0]);
                    this.$set(this.search.created_at, "end", newValue[1]);
                } else {
                    this.$set(this.search.created_at, "start", newValue);
                    this.$set(this.search.created_at, "end", newValue);
                }
            },
            deep: true
        },

        //     //搜索条件数据修改，自动请求接口
        //     search: {
        //         handler: function handler(newValue, oldValue) {
        //             this.pages.current_page = 1;
        //             this.getAfterOrderList(newValue);
        //         },
        //         deep: true
        //     }

    },
    filters: {
        formatDate: function formatDate(time) {
            var date = new Date(time);
            return _formatDate(date, 'yyyy-MM-dd hh:mm');
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