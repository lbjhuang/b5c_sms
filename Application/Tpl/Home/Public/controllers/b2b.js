/**===============================================================================
 * b2b 控制器
 * ==============================================================================*/
myApp.controller('b2b', ['$scope', '$Ajax', function ($, $Ajax) {
    $.orderNum = [{
        orderId: 'THR_PO_ID',
        orderName: 'PO单号 '
    }, {
        orderId: 'PO_ID',
        orderName: 'B2B订单号'
    }];
    $.testdata = ["Emil", "Tobias", "Linus"];
    $.tempValue = '';
    $.salesTeamTemp = {};
    $.initData = {
        isFocus:false,
        searchValue:'',
        order_fh: [],
        warehouse_state: [],
        order_sk: [],
        return_status_cd: [],
        order_ts: 0,
        now_state: 0,
        PO_ID: '',
        CLIENT_NAME: '',
        SALES_TEAM: '',
        BILLING_CYCLE_STATE: '',
        goods_title_info: '',
        po_time_action: '',
        po_time_end: '',
        page: 10,
        count: 100,
        submit_state: 0,
        orderId: 'THR_PO_ID',
        search_type: 'SKU_ID',
        typeState: 'procurement_po',
        typeValue: ''
    };
    $.data = [];
    $.summary = {
        showData:false
    }
    $.page = {
        currentPage:1
    };
    $.exportB2BorderList = function(){
        var postdata = $.initData
        if (postdata) {
            var po_time_action = document.getElementById('po_time_action').value
            var po_time_end = document.getElementById('po_time_end').value
            if (po_time_action) postdata.po_time_action = this.formatDate(po_time_action)
            if (po_time_end) postdata.po_time_end = this.formatDate(po_time_end)
        }

        var tmep = document.createElement('form');
        tmep.action = 'index.php?m=B2b&a=orderExcelExport';
        tmep.method = "post";
        tmep.style.display = "none";
        var opt = document.createElement("input");
        opt.name = 'export_params';
        opt.value = JSON.stringify(postdata);
        tmep.appendChild(opt);
        document.body.appendChild(tmep);
        tmep.submit();
        jQuery(tmep).remove();
    };
    $.selectLi = function(id,value){
        $.initData.SALES_TEAM = id;
        $.tempValue = value;
        $.initData.searchValue = value;
    };
    $.blurInput = function(){
        $.initData.isFocus = false;
    };
    $.focus = function(){
        $.initData.isFocus = true;
    };
    $.doBlur = function(){
        setTimeout(function () {
            $.initData['isFocus']= false;
            $.$apply()
        },200);
        if(!$.initData.SALES_TEAM){
            $.initData.searchValue = ''
        }else if($.initData.searchValue !== $.tempValue){
            $.initData.searchValue = $.tempValue
        }
    };
    $.change =function(val){
        let temp = $.salesTeamTemp;
        if(val){
            let obj = {};
            for (var key in temp){
                if(temp[key]['CD_VAL'].toLocaleLowerCase().indexOf(val.toLocaleLowerCase()) !== -1){
                    obj[key] = $.salesTeamTemp[key]
                }
            }
            $.data.sales_team = obj;
        }else{
            $.data.sales_team = temp
        }
    };
    // 查询汇总数据
    $.getSummary = function () {
        var postdata = JSON.parse(JSON.stringify($.initData));
            postdata.is_show_summary = true;
        delete postdata['procurement_number'];
        delete postdata['procurement_po'];
        if($.initData.typeValue){
            postdata[$.initData.typeState] = $.initData.typeValue;
        }
        $Ajax.post("/index.php?m=b2b&a=show_list&p=1",postdata, function (result) {
            $.summary.sum_current_receivable_cny = result.data.sum_current_receivable_cny;
            $.summary.sum_shipping_cost_excludeing_tax = result.data.sum_shipping_cost_excludeing_tax;
            $.summary.sum_shipping_revenue_excludeing_tax_cny = result.data.sum_shipping_revenue_excludeing_tax_cny;
            $.summary.showData = true;
        })
    }
    $.search = function (data, p) {
        p = typeof p !== 'undefined' ? p : 1;
        var postdata = $.initData;
        delete postdata['procurement_number'];
        delete postdata['procurement_po'];
        if($.initData.typeValue){
            postdata[$.initData.typeState] = $.initData.typeValue;
        }
        if (data == 1) postdata = null;
        if (postdata) {
            var po_time_action = document.getElementById('po_time_action').value
            var po_time_end = document.getElementById('po_time_end').value
            if (po_time_action) postdata.po_time_action = this.formatDate(po_time_action)
            if (po_time_end) postdata.po_time_end = this.formatDate(po_time_end)
        }
        $Ajax.post("/index.php?m=b2b&a=show_list&p=" + p, postdata, function (result) {
            console.log(result)
            if (typeof(result.data) == 'undefined') {
                return null;
            }
            if (data == 1) {
                $.data.goods = []
                $.data = result.data;
                if(result.data.sales_team){
                    $.salesTeamTemp = result.data.sales_team;
                }
                //默认初始值选择全部
                $.data.order_fh[0].checked = true;
                $.data.warehouse_state[0].checked = true;
                $.data.order_sk[0].checked = true;
                $.data.return_status_cd = [
                    {name:'全部',checked:true,no:0},
                    {name:$.data.return_goods_status['N002770001'],checked:false,no:'N002770001'},
                    {name:$.data.return_goods_status['N002770002'],checked:false,no:'N002770002'},
                    {name:$.data.return_goods_status['N002770003'],checked:false,no:'N002770003'},
                    {name:$.data.return_goods_status['N002770004'],checked:false,no:'N002770004'}
                ]
                if (!result.data.order) $.data.order = []
            } else {
                $.data.order = result.data.order
                $.data.goods = result.data.goods
                $.data.count = result.data.count
                $.data.sum_current_receivable_cny = result.data.sum_current_receivable_cny
                $.data.sum_shipping_cost_excludeing_tax = result.data.sum_shipping_cost_excludeing_tax
                $.data.sum_shipping_revenue_excludeing_tax_cny = result.data.sum_shipping_revenue_excludeing_tax_cny
                $.summary.showData = false;
            }
            if (result.data.action.orderId){
                $.initData.orderId = result.data.action.orderId
            }
            $.initData.count = result.data.count
            showAndHide('loaddiv','hide');
        });

    };
    $.reset = function () {
        var _this = this
        $.initData = {
            isFocus:false,
                searchValue:'',
            order_fh: [],
            warehouse_state: [],
            order_sk: [],
            return_status_cd: [],
            order_ts: 0,
            now_state: 0,
            PO_ID: '',
            CLIENT_NAME: '',
            SALES_TEAM: '',
            BILLING_CYCLE_STATE: '',
            goods_title_info: '',
            po_time_action: '',
            po_time_end: '',
            page: 10,
            count: 100,
            submit_state: 0,
            orderId: 'THR_PO_ID',
            search_type: 'SKU_ID',
            typeState: 'procurement_po',
            typeValue: ''
        }
        set('order_fh',0)
        set('warehouse_state',0)
        set('order_sk',0)
        set('return_status_cd',0)
        document.getElementById('po_time_action').value = null
        document.getElementById('po_time_end').value = null

        function set(e, i,thrid) {
            //索引为0时代表全部，故作判断
            if(i){
                // thrid 第三个状态参数与前俩个不一致
                _this.data[e][0].checked = false;
                if(thrid){
                    _this.data[e][thrid].checked = !_this.data[e][thrid].checked;
                }else{
                    _this.data[e][i].checked = !_this.data[e][i].checked;
                }

                var flag = _this.initData[e].some(function(item){
                    return item == i;
                })
                if(flag){
                    _this.initData[e] = _this.initData[e].filter(function(item){
                        return item != i;
                    })
                }else{
                    _this.initData[e].push(i);
                }
            }else{
                _this.data[e].forEach(function(item,index){
                    item.checked = !index;
                })
                _this.initData[e] = [];
            }
        }
        $.search()
    }
    $.toDetail = function (orderId, title) {
        var dom = document.createElement('a');
        var _href="/index.php?m=b2b&a=order_list&order_id=" + orderId + "#/b2bsend"
        dom.setAttribute("onclick", "opennewtab(this,'"+this.$lang(title) + "')");
        dom.setAttribute("_href", _href);
        dom.click();
    }
    // action button
    $.upd_date = function (e, i,thrid) {
        //索引为0时代表全部，故作判断
        if(i){
            // thrid 第三个状态参数与前俩个不一致
            this.data[e][0].checked = false;
            if(thrid){
                this.data[e][thrid].checked = !this.data[e][thrid].checked;
            }else{
                this.data[e][i].checked = !this.data[e][i].checked;
            }

            var flag = this.initData[e].some(function(item){
                return item == i;
            })
            if(flag){
                this.initData[e] = this.initData[e].filter(function(item){
                    return item != i;
                })
            }else{
                this.initData[e].push(i);
            }
        }else{
            this.data[e].forEach(function(item,index){
                item.checked = !index;
            })
            this.initData[e] = [];
        }
        if(this.initData.return_status_cd.length === 0 && !this.data.return_status_cd[0].checked){
            this.data.return_status_cd[0].checked = true
        }
        if(this.initData.order_fh.length === 0 && !this.data.order_fh[0].checked){
            this.data.order_fh[0].checked = true
        }
        if(this.initData.order_sk.length === 0 && !this.data.order_sk[0].checked){
            this.data.order_sk[0].checked = true
        }
        if(this.initData.warehouse_state.length === 0 && !this.data.warehouse_state[0].checked){
            this.data.warehouse_state[0].checked = true
        }
        $.search()
    }

    $.formatDate = function (date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }
    $.king = function (e) {
        if (e) {
            var k = e.toString().split('.')
            if (e.toString().indexOf('.') > 0) {
                var s = '.' + k[1]
            } else {
                var s = ''
            }
            return k[0].toString().replace(/\d{1,3}(?=(\d{3})+(\.\d*)?$)/g, '$&,') + s;
        } else {
            if(e == 'NaN') return 0
            return e
        }
    }
    $.search(1)
    $.handleChange = function (e) {
        var ele = document.getElementById('textarea');
        var height = calcTextareaHeight(ele, 1, null);
        ele.style.height = height.height;
        ele.style['min-height'] = '50px';
    }
}]);

/**===============================================================================
 * b2bAdd  新建订单控制器      $Ajax：封装的公共 $http方法
 * ==============================================================================*/
myApp.filter('showzero', function () {
    return function (e) {
        if (e == NaN || e == null || e == '' || e == 'NaN') {
            e = 0
        }
        return e
    }
})


myApp.controller('b2bAdd', ['$scope', '$Ajax', 'CalcService', '$rootScope', function ($, $Ajax, CalcService, $rootScope) {
    $.CalcService = CalcService;
    $.ajaxObj = $Ajax;
    /**=========================================初始加载数据===============================================*/

    $.initData = clone(CalcService.po_init_data);
    $.err_goods = [];
    $.getData = {
        c2c_data: [],
        cd_company: []
    }
    $.deducting_tax = 1
    $.allclient = []
    $.showclient = 0
    $.get_ht = function () {
        $Ajax.post("/index.php?m=b2b&a=get_ht", {sp_charter_no: $.poData.clientName, like_no: 1}, function (result) {
            $.initData.contracts = result.data.contract
            if ($.initData.contracts) $.poData.contract = $.initData.contracts[0].CON_NO
            $.getData.c2c_data = result.data.c2c_data
            $.getData.cd_company = result.data.cd_company
            $.poData.clientNameEn = result.data.contract_en_name
            $.poData.ourCompany = ''
            if ($.poData.contract && $.getData.c2c_data) $.upd_company()
        });
    }
    $.get_this_ht = function (type_is_show) {
        $Ajax.post("/index.php?m=b2b&a=get_ht", {sp_charter_no: $.poData.clientName}, function (result) {
            $.allclient = result.data.contract_key
            if (!$.allclient) {
                $.allclient = [];
            }
            if (Object.keys($.allclient).length > 0) $.showclient = 1
            if (type_is_show == 1) {
                $.showclient = 0;
            }
        });
    }
    $.upd_this_client = function (e) {
        $.poData.clientName = e
        $.get_ht()
        $.showclient = 0
    }
    $.upd_company = function () {
        $.poData.ourCompany = $.getData.cd_company[$.getData.c2c_data[$.poData.contract]]
    }
    $.init = function () {
        $Ajax.post("/index.php?m=b2b&a=init", null, function (result) {
            // need merge param
            $.initData.country = result.data.Country;
            $.initData.business_type = result.data.business_type;
            $.initData.business_direction = result.data.business_direction;
            $.initData.allWarehouse = result.data.all_warehouse;
            $.initData.currency = result.data.currency;
            $.initData.taxRebateRatio = result.data.tax_rebate_ratio;
            $.initData.salesTeam = result.data.sales_team;
            $.initData.paymentCycle = result.data.payment_cycle;
            $.initData.paymentNode = result.data.payment_node;
            $.initData.invoicePoint = result.data.invoice_point;
            $.initData.shipping.data = result.data.shipping;
            $.initData.invioce = result.data.invioce;
            $.initData.tax_point = result.data.tax_point;
            $.initData.number_th = result.data.number_th;
            $.initData.currency_bz = result.data.currency_bz;
            $.initData.allPurchasingArr = result.data.allPurchasingArr
            $.initData.allIntroduceArr = result.data.allIntroduceArr
            $.initData.wfgs = result.data.wfgs
            $.initData.user = result.data.user
        });

    };
    $.init();
    $.checkLastNameStr = function () {
        var pattern = /^[A-Za-z]+$/g,
            str = '';
        if (!pattern.test($.poData.lastname)) {
            alert($rootScope('销售同事处，请填写花名拼音'));
            $.poData.lastname = null
        }
    };

    //目标城市联动
    $.changeCounty = function (poData, end) {
        $Ajax.post("/index.php?m=stock&a=getCity", {provinces: poData, end: end}, function (result) {
            if (end == 'end') {
                $.initData.city = result.data;
            } else {
                $.initData.province = result.data;
            }
        })
    };

    $.updSkuData = function () {
        for (var i in this.skuData) {
            this.countDrawback(this.skuData[i])
        }
    }

    $.get_deducting_tax = function () {
        if (this.poData.BZ) this.updSkuData()
        if ((this.poData.shipping != 'N001530500' && this.poData.shipping != 'N001530700') || !this.poData.BZ) return false
        var currency = this.poData.backend_currency
        var date = this.poData.poTime
        var dst_currency = this.initData.currency_bz[this.poData.BZ].CD_VAL
        if (!currency || !date || !dst_currency) return false
        $Ajax.post("/index.php?m=b2b&a=get_currency_backend", {
            currency: currency,
            date: date,
            dst_currency: dst_currency
        }, function (result) {
            if (result) {
                $.deducting_tax = result
            }
        })

    }

    $.sync_sku = function (e) {
        $.skuData = [clone($.skuDataDefault)];
        for (var i = 1; i < e.length; i++) {
            $.add();
        }
        for (var i = 0; i < e.length; i++) {
            $.skuData[i]['skuId'] = e[i]['search']
            $.skuData[i]['toskuid'] = e[i]['sku']
            $.skuData[i]['gudsName'] = e[i]['goods_name']
            $.skuData[i]['skuInfo'] = e[i]['val_str']
            $.skuData[i]['warehouse'] = e[i]['warehouse']
            $.skuData[i]['gudsPrice'] = e[i]['price']
            $.skuData[i]['demand'] = e[i]['number']
            $.skuData[i]['drawback'] = e[i]['drawback']
            $.skuData[i]['drawback'] = 0
            $.skuData[i]['STD_XCHR_KIND_CD'] = e[i]['STD_XCHR_KIND_CD']
            $.skuData[i]['GUDS_OPT_ORG_PRC'] = e[i]['GUDS_OPT_ORG_PRC']
        }
        for (var i = 0; i < e.length; i++) {
            $.searchSku($.skuData[i])
            $.countSubTotal($.skuData[i])
            if ($.skuData[i]['estimateDrawback'] == 'NaN') $.skuData[i]['estimateDrawback'] = (0).toFixed(2)
        }
    }

    $.addgoods = function () {
        uploader = WebUploader.create({
            swf: '/Application/Tpl/Home/Public/lib/webuploader/0.1.5/Uploader.swf',
            server: '/index.php?m=b2b&a=importGoods',
            pick: {id: '#import-goods'},
            auto: true,
            duplicate: true,
        });
        uploader.on('uploadSuccess', function (file, res) {
            utils.lazy_loading()
            if (!$.poData.BZ) {
                utils.modal(true, {width: 380, title: "请先处理PO币种"}, false)
                return false
            }
            if (res.status && res.status == 1) {
                utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '已成功导入商品信息'})
                $.sync_sku(res.info)
            } else {
                $.err_goods = res.info
                $.importError()
            }
        });
        uploader.on("uploadError", function (file) {
            utils.modal(true, {btnClass: 'btn-primary', title: "Error"}, false);
        });
        uploader.on('uploadStart', function (file) {
            utils.lazy_loading(true)
        })

    }
    setTimeout($.addgoods, 600);
    $.addpo = function () {
        // $.switchNode(0)
        uploader = WebUploader.create({
            swf: '/Application/Tpl/Home/Public/lib/webuploader/0.1.5/Uploader.swf',
            server: '/index.php?m=b2b&a=importPo',
            fileSingleSizeLimit: 20 * 1024 * 1024,
            pick: {id: '#import-po'},
            auto: true,
            duplicate: true,
        });
        uploader.on('uploadSuccess', function (file, res) {
            if (res.status && res.status == 1) {
                utils.modal(true, {
                    width: 380,
                    title: "上传成功",
                    content: "上传成功",
                    id: "show_up_pic_close",
                    delay: 2000
                }, false);
                // setTimeout(utils.modal(false), 3000);
                $.poData.IMAGEFILENAME = res.info.file_name
                $.poData.po_erp_path = res.info.VOUCHER_ADDRESS
                $.$apply();//需要手动刷新
            } else {
                utils.modal(true, {width: 380, title: "上传失败", content: res.info}, false)
            }
            $.CalcService.load_hide();
        });
        uploader.on('uploadStart', function (file) {
            $.CalcService.load_show();
        })
        uploader.on("error", function (type) {
            if (type == "F_EXCEED_SIZE") {
                utils.modal(true, {width: 380, title: "上传失败", content: '文件大小不能超过20M'}, false)

            }
            $.CalcService.load_hide();
        });
    }
    setTimeout($.addpo, 800);
    /**=========================================po信息模块===============================================*/
    $.poData = {
        poNum: '',               //po编号
        clientName: '',          //客户名称
        clientNameEN: '',          //客户名称EN
        clientNameEn: '',
        busLice: '',             //客户营业执照号
        contract: '',            //适用合同
        ourCompany: '',          //我方公司
        poAmount: '',            //PO金额
        BZ: '',
        poScanner: '',           //PO扫描件
        poTime: '',              //PO时间
        targetCity: '',          //目标城市
        shipping: '',         //发货方式
        cycleNum: '',            //付款周期
        poPaymentNode: [],       //付款节点
        country: '',              //国家
        province: '',              //省市
        city: '',                 //城市
        street: '',               //街道
        detailAdd: '',            //详细地址
        saleTeam: '',             //销售团队
        Remarks: '',               //备注
        invioce: '',
        tax_point: '',
        backend_currency: '',
        backend_estimat: '',
        logistics_currency: '',
        logistics_estimat: '',
        otherIncome: '0',
        drawback_estimate: 0
    };
    $.poData.sale_tax = 0;    //销售端应缴税
    $.poData.cur_tuishui = '';    //币种退税金额
    $.poData.cur_saletax = '';    //币种销售端应缴税
    $.poData.cur_other = '';    //币种其他收入
    $.initall = {
        allEsBack: 0,
        allSubtotal: 0,
        allnum: 0,
        allPrice: 0
    }
    $.paymentNodeArray = [];      //付款节点数组

    $.searchPo = function () {
        var param = {CON_NO: $.poData.poNum};
        $Ajax.post("/index.php?m=b2b&a=get_po_data", param, function (result) {
            if (result.status == 1) {
                $.poData.clientName = result.data.YF;
                $.poData.busLice = result.data.CGBUSINESSLICENSE2;
                $.poData.contract = result.data.SUITABLE_CONTRACT;
                $.poData.ourCompany = result.data.GSMC;
                $.poData.poAmount = result.data.AMOUNT2;
                $.poData.poScanner = result.data.FJSC2;
                $.poData.poTime = result.data.SQRQ;
                $.poData.lastname = result.data.LASTNAME;
                $.poData.BZ = result.data.BZ;
                $.get_ht()
                // $.switchNode(0)
            } else {
                alert(result.data)
            }
        });
    };

    //重组付款节点数组
    function regArray(arr, num, args) {
        var regArr = [];

        function sliceArr(obj, num, args, index) {
            var newObj = {};
            for (var key in arr) {
                newObj[key] = obj[key]
                if (key == args && 1 != 1) {
                    newObj[args] = obj[args].slice(index, arr[args].length - (num - index - 1))
                } else {
                    newObj[key] = obj[key]
                }
            }
            return newObj;
        }

        for (var i = 0; i < num; i++) {
            regArr[i] = sliceArr(arr, num, args, i);
        }
        return regArr;

    }


    //选择付款周期
    $.changeCycle = function () {
        $.paymentNodeArray = [];
        if ($.poData.cycleNum == 4) {
            $.paymentNodeArray = regArray($.initData.paymentNode, +1, 'node_type');
        } else if ($.poData.cycleNum) {
            $.paymentNodeArray = regArray($.initData.paymentNode, +$.poData.cycleNum, 'node_type');
        }
    };
    //切换发货方式
    $.switchNode = function (index) {
        $.initData.shipping.active = index;
        $.poData.shipping = $.initData.shipping.data[index].CD_VAL;
    };

    /**=========================================sku信息模块===============================================*/

    /**
     * 商品SKU信息
     * @type {[*]}  相对应的值
     */
    $.skuDataDefault = {
        skuId: '',         //skuID
        gudsName: '',                //商品名称
        skuInfo: '',                 //sku信息
        warehouse: '',               //仓库
        selCurrency: '',             //币种
        gudsPrice: 0,                //商品售价
        demand: 0,                   //需求数量
        subTotal: 0,                 //小计
        drawback: '0',               //退税比例
        estimateDrawback: 0,          //预计退税金额
        purchasing_team: '',
        introduce_team: '',
        GUDS_OPT_ORG_PRC: '',
        STD_XCHR_KIND_CD: '',
        percent_sale: '',
        percent_purchasing: '',
        percent_introduce: ''
    };
    $.skuData = [clone($.skuDataDefault)];

    $.rate = 1

    /**
     * 查询SKU信息
     */
    $.searchSku = function (item) {
        if (!item.toskuid) {
            utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '请输入正确的 SKUID'})
            return false;
        }

        var countId = -1;
        var countIdsku = -1;
        angular.forEach($.skuData, function (data) {
            if (data.toskuid == item.toskuid) countId++;
        });

        if (countId) {
            utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '该SKU信息已经存在'})
            return false;
        }

        var param = {GSKU: item.toskuid, warehouse_id: null};
        $Ajax.post('/index.php?m=stock&a=searchguds', param, function (result) {
            if (typeof result.info == "object" && result.info != null) {
                item.skuId = result.info['opt_val'][0]['GUDS_OPT_ID'];
                angular.forEach($.skuData, function (data) {
                    if (data.skuId == item.skuId) countIdsku++;
                });
                if (countIdsku) {
                    utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '该SKU信息已经存在'});
                    return false;
                }
                item.gudsName = result.info[0]['Guds']['GUDS_NM'];
                item.skuInfo = result.info['opt_val'][0]['val'];
                item.GUDS_OPT_ORG_PRC = result.info[0]['GUDS_OPT_ORG_PRC'];
                item.STD_XCHR_KIND_CD = result.info[0]['Guds']['STD_XCHR_KIND_CD'];

                angular.forEach($.initData.allWarehouse, function (child) {
                    if (child.CD == result.info[0]['Guds']['DELIVERY_WAREHOUSE']) {
                        item.warehouse = child.warehouse;
                    }
                });

            } else {
                utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '请检查 SKUID 是否正确'})
            }
        })


    };

    //计算小计金额
    $.countSubTotal = function (item) {
        if (typeof (item.gudsPrice) == 'string' && item.gudsPrice.indexOf('\.', item.gudsPrice.length - 1) > 0) return item
        var check = checkCutPointNum(item.gudsPrice, 4);
        if (check.iscut) {
            item.gudsPrice = check.num;
            $.popMsgPoint(4, check.old, check.num);
        }
        item.gudsPrice = this.unking(item.gudsPrice)
        item.demand = this.unking(item.demand)
        item.subTotal = (parseFloat(item.gudsPrice) * parseFloat(item.demand)).toFixed(4);
        this.countDrawback(item)
        item.gudsPrice = this.king((item.gudsPrice))
        item.demand = this.king((item.demand))
        item.subTotal = this.king((item.subTotal))
    };

    //计算预计退税金额
    $.countDrawback = function (item) {
        item.subTotal = this.unking(item.subTotal)
        item.drawback = this.unking(item.drawback)
        item.demand = this.unking(item.demand)
        item.estimateDrawback = this.get_ts(item);
    };
    $.upd_potime = function (e) {
        if (e) this.updSkuData()
        var date_val = document.getElementById('actual-receipt-date').value
        $.poData.poTime = date_val
        this.get_deducting_tax()
    }
    //采用backend金额
    $.get_ts = function (item, bz) {
        if (this.poData.BZ) {
            var dst_currency = this.initData.currency_bz[this.poData.BZ].CD_VAL
            var dates = this.poData.poTime
            var currency = item.STD_XCHR_KIND_CD
            $Ajax.get("/index.php?m=b2b&a=get_currency_backend", {
                currency: currency,
                date: dates,
                dst_currency: dst_currency
            }, function (result) {
                if (result) {
                    $.rate = result
                    item.estimateDrawback = ((parseFloat(item.GUDS_OPT_ORG_PRC) * $.rate * parseFloat(item.demand) * parseFloat(item.drawback)) / 100).toFixed(2)
                    $.all()
                    item.demand = $.king(parseFloat($.unking(item.demand)))
                    item.subTotal = $.king(parseFloat($.unking(item.subTotal)))
                    item.estimateDrawback = $.king($.unking(item.estimateDrawback))
                }
            })
        }
    }

    $.all = function () {
        $.initall = {
            allEsBack: 0,
            allSubtotal: 0,
            allnum: 0,
            allPrice: 0
        }
        for (s in $.skuData) {
            $.initall.allPrice += isNaN(this.unking($.skuData[s].gudsPrice)) ? 0 : parseFloat(this.unking($.skuData[s].gudsPrice))
            $.initall.allnum += isNaN(this.unking($.skuData[s].demand)) ? 0 : parseFloat(this.unking($.skuData[s].demand))
            $.initall.allSubtotal += isNaN(this.unking($.skuData[s].subTotal)) ? 0 : parseFloat(this.unking($.skuData[s].subTotal))
            $.initall.allEsBack += isNaN(this.unking($.skuData[s].estimateDrawback)) ? 0 : parseFloat(this.unking($.skuData[s].estimateDrawback))
        }
        for (a in  $.initall) {
            $.initall[a] = parseFloat($.initall[a]) ? this.king(parseFloat($.initall[a]).toFixed(2)) : parseFloat($.initall[a]);
        }
        $.initall.allnum = keep_float_fmt(Math.round(keep_float($.initall.allnum)));
    }
    //添加新的一行sku信息
    $.add = function () {
        var addData = clone($.skuDataDefault);
        $.skuData.push(addData)
    };

    //删除一行SKU信息
    $.del = function (item) {
        angular.forEach($.skuData, function (element, index) {
            if (element == item) {
                if ($.skuData.length > 1) {
                    $.skuData.splice(index, 1)
                } else {
                    utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '不可删除最后一条'})
                }

            }
        });
        this.all()
    };

    //选择币种
    $.changeCurrency = function (item) {
        angular.forEach($.skuData, function (data) {
            data.selCurrency = item.selCurrency;
        })
    };

    // 切换团队
    $.changeAllTeam = function (key, val) {
        var sku_data = this.skuData
        if (key == 'drawback') val = this.initData.taxRebateRatio[val].CD_VAL
        for (v in sku_data) {
            this.skuData[v][key] = val
            if (key == 'drawback') {
                this.countDrawback(this.skuData[v])
            }
        }
    };
    //保存提交
    $.submit = function () {
        var tmpStatus = CalcService.b2b_po_check_submit($, $Ajax);
        if (tmpStatus == false) {
            return false;
        }
        return null;
    };
    $.btn_save_draft = function () {
        $.poData.poPaymentNode = [];
        for (var i = 0; i < $.paymentNodeArray.length; i++) {
            paymentNode = {
                nodei: i,
                nodeType: $.paymentNodeArray[i].nodeType,
                nodeDate: $.paymentNodeArray[i].nodeDate,
                nodeWorkday: 0,
                nodeProp: $.paymentNodeArray[i].nodeProp
            };
            $.poData.poPaymentNode.push(paymentNode);
        }
        if ($.poData.submit_state == 2) {
            var status = CalcService.b2b_po_check_only($);
            if (!status) {
                return false;
            }
        }
        var param = {
            poData: $.poData,
            skuData: $.skuData
        };
        $Ajax.post('/index.php?m=b2b&a=save_draft', JSON.stringify(param), function (result) {
            if (200 == result.status) {
                var msg = result.info ? result.info : 'ok';
                var id = result.order_id;
                if (id) {
                    utils.modal(true, {width: 500, btnClass: 'btn-primary', content: msg, id: 'show_success_btn'});
                    setTimeout(function () {
                        window.location.href = '/index.php?m=b2b&a=order_list&order_id=' + id + '#/b2bsend'
                    }, 1000);
                }
            } else {
                var msg = result.info ? result.info : '';
                if (!msg) msg = CalcService.checkErrRes(result);
                utils.modal(true, {width: 500, btnClass: '', content: msg});
            }
        });
    }
    $.checkRate = function (input) {
        var re = /^[1-9]+[0-9]*]*$/
        return re.test(input)
    }
    $.checkRateDecimal = function (input) {
        var re = /^[0-9]*[.0-9]*]*$/
        return re.test(input)
    }

    $.in_array = function (array, search) {
        var err = null
        if (!array[search]) err = search
        return err;
    }

    //重置表单
    $.reset = function () {
        $.skuData = [clone($.skuDataDefault)];
        $.initall = {
            allEsBack: 0,
            allSubtotal: 0,
            allnum: 0,
            allPrice: 0
        }
    }
    $.toking = function (e, f, d) {
        $[d][f] = this.king(this.unking(e))
    },
        $.king = function (e) {
            if (e) {
                var k = e.toString().split('.')
                if (e.toString().indexOf('.') > 0) {
                    var s = '.' + k[1]
                } else {
                    var s = ''
                }
                return k[0].toString().replace(/\d{1,3}(?=(\d{3})+(\.\d*)?$)/g, '$&,') + s;
            } else {
                if(e == 'NaN') return 0
                return e
            }
        }

    $.unking = function (num) {
        if (isNaN(num) && typeof(num) == 'string') {
            var x = num.split(',');
            return parseFloat(x.join(""));
        } else {
            return num
        }
    }
    $.importError = function () {
        utils.modal(true, {
            title: '提示',
            content: "商品导入失败，你可以下载错误报告，查看具体原因",
            confirmText: '下载',
            width: 480,
            contentClass: 'text-center',
            confirmFn: function () {
                document.getElementById("json_res").value = $.err_goods
                setTimeout(function () {
                    document.getElementById("sub_res").submit()
                }, 500)
            }
        })
    }

    /*  ========================================= 7232 优化B2B订单利润计算 ===============================================  */
    $.poDataEstimate = {};
    /**
     *
     *
     */
    $.seeprice = function (num) {
        var a = keep_float(num);
        a = keep_float_fmt(a);
        return a;
    }
    /**
     *  check fahuo type
     *
     *
     */
    $.check_fahuo_type = function (fahuofangshi) {
        // default
        var fahuo_type = 0;
        var checkArr = ['N001530500', 'N001530700', 'N001530900'];
        var res = js_in_array(fahuofangshi, checkArr);
        if (res) {
            // Domestic
            fahuo_type = 1;
        }
        return fahuo_type;
    }
    /**
     *  gain the poData tax rate
     *
     */
    $.gainTaxRate = function () {
        var tax_p = 0;
        var tax_p_code = $.poData.tax_point;
        if (tax_p_code) {
            if (typeof($.initData.tax_point[tax_p_code]) != 'undefined') {
                tax_p = $.initData.tax_point[tax_p_code].CD_VAL;
            }
        }
        tax_p = keep_float(tax_p);
        tax_p = tax_p / 100;
        return tax_p;
    }
    /**
     *  change tuishui or saletax
     *
     */
    $.change_fahuo_domestic = function (e) {
        var to_do = 0;
        var to_do_2 = 0;
        if (e && e.target) {
            var tar = e.target;
            var tar2 = e.target.parentNode;
            var tar = tar.getAttribute('ng-model');
            var tar2 = tar2.getAttribute('ng-model');
            var checkArr = ['poData.BZ', 'poData.poAmount', 'poData.tax_point', 'poData.shipping', 'poData.backend_currency', 'poData.backend_estimat'];
            var res = js_in_array(tar, checkArr);
            var res2 = js_in_array(tar2, checkArr);
            if (res || res2) {
                to_do = 1;
            }
            var checkArr = ['poData.logistics_currency', 'poData.logistics_estimat'];
            var res = js_in_array(tar, checkArr);
            var res2 = js_in_array(tar2, checkArr);
            if (res || res2) {
                to_do_2 = 1;
            }
        }
        if (to_do) {
            $.change_tuishui();
            $.change_saletax();
            $.calcu_forecast();
        }
        if (to_do_2) {
            $.calcu_forecast();
        }
    }
    /**
     *  try to calcu yugu
     *
     */
    $.change_fahuo_yugu = function (isCalcu) {
        isCalcu = isCalcu ? isCalcu : 0;
        if (isCalcu == 1) {
            $.change_tuishui();
            $.change_saletax();
            $.calcu_forecast();
        } else {
            $.calcu_forecast();
        }
    }
    /**
     *  change - 退税金额
     *  含税成本-含税成本/（1+税率） 【当发货方式为CIF/FOB/DDP等时】
     *  0【当发货方式为CN Domestic/KR Domestic/JP Domestic时】
     */
    $.change_tuishui = function () {
        var show_tuishui = 0;
        // fahuo
        var fahuofangshi = $.poData.shipping;
        // 税率
        var tax_p = $.gainTaxRate();
        // chengben
        var cb = $.poData.backend_estimat;
        cb = keep_float(cb);
        if (fahuofangshi) {
            var res = $.check_fahuo_type(fahuofangshi);
            if (!res) {
                show_tuishui = cb - (cb / (1 + tax_p));
            }
        }
        $.poData.drawback_estimate = String(keep_float_fmt(show_tuishui));
    }
    /**
     *  change - 销售端应缴税
     *   PO金额-PO金额/（1+税率）【当发货方式为CN Domestic/KR Domestic/JP Domestic时】
     *   0【当发货方式为CIF/FOB/DDP等时】
     */
    $.change_saletax = function () {
        var show_saletax = 0;
        // fahuo
        var fahuofangshi = $.poData.shipping;
        // 税率
        var tax_p = $.gainTaxRate();
        // jin e
        var po_jin = $.poData.poAmount;
        po_jin = keep_float(po_jin);
        if (fahuofangshi) {
            var res = $.check_fahuo_type(fahuofangshi);
            if (res == 1) {
                show_saletax = po_jin - (po_jin / (1 + tax_p));
            }
        }
        $.poData.sale_tax = String(keep_float_fmt(show_saletax));
    }
    /**
     *  calcu forecast [USD]
     *
     */
    $.calcu_forecast = function () {
        var f = {};
        f.kpi = '';
        // date
        f.day = $.poData.poTime;
        // fahuo
        f.fahuofangshi = $.poData.shipping;
        f.fh_type = $.check_fahuo_type(f.fahuofangshi);
        //含税成本
        f.backend_currency = $.poData.backend_currency;
        f.backend_estimat = $.poData.backend_estimat;
        //tuishui
        f.cur_tuishui = $.poData.cur_tuishui;
        f.drawback_estimate = $.poData.drawback_estimate;
        //含税收入
        f.poAmountBz = $.poData.BZ;
        f.poAmount = $.poData.poAmount;
        //销售端应缴税
        f.cur_saletax = $.poData.cur_saletax;
        f.sale_tax = $.poData.sale_tax;
        // tax
        f.tax_p_code = $.poData.tax_point;
        f.tax_p = $.gainTaxRate();
        //物流费用
        f.logistics_currency = $.poData.logistics_currency;
        f.logistics_estimat = $.poData.logistics_estimat;


        $Ajax.get("/index.php?m=b2b&a=calcu_forecast",
            f,
            function (result) {
                if (result.code) {
                    $.poDataEstimate = result.info;
                }
            },
            function (result) {

            }
        );


    }
    /**
     *    验证输入
     *    -PO金额（含税）、商品含税成本、退税金额、销售端应缴税、物流费用、其他收入，均保留两位小数，用户不能输入超过两位的小数；
     *    （如果用户输入超过两位小数弹窗提示用户，并且不做向上或向下取整直接帮用户截断，比如输入123.1181，提示之后，截断成：123.11）
     */
    $.chkPointLen = function (numstr) {
        var n = keep_float(numstr);
        var lenArr = (n.toString()).split(".");
        var len = (lenArr[1]) ? lenArr[1].length : 0;
        if (len > 2) {
            n = (n.toString()).match(/^\d+(?:\.\d{0,2})?/);
        }
        var ret = {};
        ret.iscut = (len > 2) ? 1 : 0;
        ret.num = keep_float(n);
        ret.old = keep_float(numstr);
        return ret;
    }
    // pop msg and limit point num to the field
    $.alertMsgLen = function (newValue, dPre, dPre2) {
        var check = $.chkPointLen(newValue);
        if (check.iscut) {
            var old = check.old;
            var num = check.num;
            old = price_king(keep_float(old));
            num = price_king(keep_float(num));
            utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '仅支持两位小数(输入：' + old + '，截断：' + num + ')'});
            if (dPre && dPre2) {
                $[dPre][dPre2] = $.CalcService.seeprice(num);
            }
        }
        return null;
    }
    $.popMsgPoint = function (numpos, old, num) {
        old = price_king(keep_float(old));
        num = price_king(keep_float(num));
        utils.modal(true, {
            width: 500,
            btnClass: 'btn-primary',
            content: '仅支持' + numpos + '位小数(输入：' + old + '，截断：' + num + ')'
        });
    }
    $.popMsgPoint2 = function (numpos, old, num) {
        old = price_king(keep_float(old));
        num = price_king(keep_float(num));
        utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '仅支持两位小数(输入：' + old + '，截断：' + num + ')'});
    }
    $.relatedCurry = function (curreny, amount) {
        if (!curreny) {
            $.poData.logistics_estimat = 0
            utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '请填写物流费用币种'});
        }
    }
    $.checkNum2 = function (newValue, dPre, dPre2) {
        var check = checkCutPointNum(newValue, 2);
        if (check.iscut) {
            $[dPre][dPre2] = $.CalcService.seeprice(check.num);
            $.popMsgPoint2(2, check.old, check.num);
        }
    }
    $.checkPosIntOfItem = function (item, key) {
        if (typeof(item[key]) != 'undefined') {
            var n = item[key];
            var nn = keep_float(n);
            if (nn < 0) nn = 0;
            nn = fomatFloat(nn, 0);
            item[key] = price_king(nn);
        }
    }
    $.checkInt0to100 = function (item, key) {
        if (typeof(item[key]) != 'undefined') {
            var n = item[key];
            var nn = keep_float(n);
            if (nn < 0) nn = 0;
            nn = fomatFloat(nn, 0);
            if (nn > 100) nn = 100;
            item[key] = price_king(nn);
        }
    }
    /*  ========================================= 7232 end ===============================================  */
    // 切换分成
    $.changeAllFencheng = function (key, val) {
        var d = 'initData';
        var f = key;
        $.toking(val, f, d);
        val = $[d][f];
        var sku_data = $.skuData
        for (v in sku_data) {
            $.skuData[v][key] = val
        }
    };
    // check fencheng of intro
    $.isOkIntro = function (item) {
        var ret = 0;
        if (item.introduce_team == 'N001301200') {
            ret = 1;
            item.percent_introduce = 0;
        }
        return ret;
    }
    // test po
    if (0) {
        var testPo = $.CalcService.testB2bPo();
        $.poData = testPo.poData;
    }
    /**
     *  submit to publish from add page
     *
     */
    $.formal_submit_from_add = function () {
        $.edit_id = $.poData.edit_id;
        $.edit_is_publish = 1;
        var tmpStatus = CalcService.b2b_po_check_submit($, $Ajax);
        if (tmpStatus == false) {
            return false;
        }
        return null;
    }
    /**
     *
     *
     */
    $.initEditPo = function (edit_id) {
        var po_data = {};
        var po_goods = {};
        var data_params = {};
        data_params.order_id = edit_id;
        $Ajax.post('/index.php?m=b2b&a=order_content', data_params, function (result) {
            if (200 == result.status) {
                po_data = result.data['info'][0];
                po_goods = result.data['goods'];
                po_data = CalcService.map_po_fields(po_data);
                po_goods = CalcService.map_po_sku_fields(po_goods);
                $.poData = CalcService.obj_replace_obj($.poData, po_data);
                $.skuData = po_goods;
                CalcService.po_edit_fill_in($);
                $.change_fahuo_yugu();
            } else {
                alert('wrong: can not edit.');
            }
        });
    }
    // edit or not
    var reqArr = wwwHelpCommJs.getUrlSearchObj();
    var edit_id = reqArr.edit_id ? reqArr.edit_id : null;
    $.poData.edit_id = edit_id;
    if (edit_id) {
        // edit page
        setTimeout($.initEditPo(edit_id), 500);
    }

    /**
     *
     * @param data
     * @returns {*}
     */
    $.checkProportionParams = function (data) {
        try {
            if (!data.saleTeam) {
                throw 'saleTeam is null'
            }
            if (!data.po_date) {
                throw 'po_date is null'
            }
            for (d in data.skuData) {
                if (!data.skuData[d].purchasing_team || !data.skuData[d].introduce_team) {
                    throw (parseInt(d) + 1) + ' 行有采购团队或介绍团队未填写完整，无法获取分成比例'
                }
            }
        } catch (err) {
            var res = []
            res['msg'] = err
            res['state'] = 400
            return res;
        }
    }


    /**
     * get proportion
     */
    $.getProportion = function () {
        var params = {
            saleTeam: $.poData.saleTeam,
            po_date: $.poData.poTime,
            skuData: $.skuData,
        }
        var paramState = $.checkProportionParams(params);
        if (paramState) {
            utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '信息缺失:' + paramState['msg']})
            return false
        }
        $Ajax.post('/index.php?m=b2b&a=getProportion', JSON.stringify({params: params}), function (result) {
            var err_str = tmp_data = ''
            for (v in result) {
                if (200 == result[v]['state']) {
                    tmp_data = result[v]['data'];
                    $.skuData[v]['percent_sale'] = tmp_data['team_1']
                    $.skuData[v]['percent_purchasing'] = tmp_data['team_2']
                    $.skuData[v]['percent_introduce'] = tmp_data['team_3']
                } else {
                    err_str += (parseInt(v) + 1) + ', '
                    $.skuData[v]['percent_sale'] = $.skuData[v]['percent_purchasing'] = $.skuData[v]['percent_introduce'] = '-'
                }
            }
            if (err_str) utils.modal(true, {width: 500, btnClass: 'btn-primary', content: '分成比例获取异常:' + err_str + ' 行'})
        })
    }


}]);

/**===============================================================================
 * b2bSend 控制器
 * ==============================================================================*/
myApp.filter('to_trusted', ['$sce', function ($sce) {
    return function (text) {
        return $sce.trustAsHtml(text);
    };
}]);
myApp.controller('b2bSend', ['$scope', '$Ajax', '$location', 'CalcService', '$rootScope', function ($, $Ajax, $location, CalcService, $rootScope) {
    $.CalcService = CalcService;
    $.initData = clone(CalcService.po_init_data);
    $.initForBase = function () {
        $Ajax.post("/index.php?m=b2b&a=init", null, function (result) {
            $.initData.tax_point = result.data.tax_point;
        });

    };
    //
    $.submitCheckSales = function(id,type){
        var param = {
            order_id:id,
            submit_check:type
        }
        $Ajax.post("/index.php?m=B2b&a=submitCheckSales", JSON.stringify(param), function (result) {
            layer.msg(result.msg)
            if(result.info == 'success'){
                setTimeout(function(){
                    window.location.reload();
                },800)
            }
        });
    }

    $.initForBase();
    /**
     *  btn 正式提交
     *
     */
    $.formal_submit = function () {
        // the data which it need like the data of add page.
        $.poData = clone($.initDataPo);
        $.skuData = clone($.data.goods);
        // map po data
        $.poData = CalcService.map_po_fields($.poData);
        $.skuData = CalcService.map_po_sku_fields($.skuData);
        CalcService.all_count_sku_price($);
        $.edit_id = $.data['order_id'];
        $.edit_is_publish = 1;
        $.poData.edit_id = $.initDataPo.ORDER_ID;
        var tmpStatus = CalcService.b2b_po_check_submit($, $Ajax);
        if (tmpStatus == false) {
            return false;
        }
        return null;
    }
    $.showThumbnail = function (key, type) {
        if ('act' == type) {
            this.data.goods[key].show_img = true
        } else {
            this.data.goods[key].show_img = false
        }
    }

    /**
     * 付款节点数组
     *
     */
    $.paymentNodeArray = [];
    /**
     *  Edit id
     *
     */
    $.edit_id = '';
    /**
     *  Is publish or not
     *
     */
    $.edit_is_publish = 0;
    $.initDataPo = {}
    $.data = [];
    $.goods_sum = {
        neednum: 0,
        shipnum: 0,
        warehousing: 0,
        allnum: 0.00,
        difference:0,
        totalNormalGoods:0,
        totalBormalGargo:0,
    }
    $.datas = []
    $.init_data = []
    $.data['order_id'] = $location.$$absUrl.split('order_id=')[1].split('#')[0]
    $.init = function () {
        $Ajax.post('/index.php?m=b2b&a=order_content', $.data, function (result) {
            if (200 == result.status) {
                //新增
                $.data['status'] = result.data['status']
                $.datas['receipt_information'] = result.data['receipt_information']
                $.datas['deduction_information'] = result.data['deduction_information']
                $.datas['estimated_profit'] = result.data['estimated_profit']
                $.datas['receivable_information'] = result.data['receivable_information']
                $.datas['info'] = result.data['info'][0]

                //原有
                $.initDataPo = result.data['info'][0]
                $.data['sales_team'] = result.data['sales_team']
                $.data['receivable_status_arr'] = result.data['receivable_status_arr']
                $.data['goods'] = result.data['goods']
                $.data['business_direction'] = result.data['business_direction']
                $.data['business_type'] = result.data['business_type']
                $.datas['ship'] = result.data['ship']
                $.datas['return'] = result.data['return']
                $.datas['receipt'] = result.data['receipt']
                $.datas['profit'] = result.data['profit']
                $.sum_compute($.data['goods']);
                $.init_data['area'] = result.data['area']
                $.init_data['number_th'] = result.data['number_th']
                $.init_data['node_is_workday'] = result.data['node_is_workday']
                $.init_data['node_type'] = result.data['node_type']
                $.init_data['node_date'] = result.data['node_date']
                $.init_data['invioce'] = result.data['invioce']
                $.init_data['tax_point'] = result.data['tax_point']
                $.init_data['period'] = result.data['period']
                $.init_data['or_invoice_arr'] = result.data['or_invoice_arr']
                $.init_data['warehousing_state'] = result.data['warehousing_state']
                $.init_data['currency_bz'] = result.data['currency_bz']
                $.init_data['shipping'] = result.data['shipping']
            }
        })
    }
    $.ProductKeyData = [];
    $.bool = false;
    /**
     * 数组分割方法
     */
    $.spilt = function(arr){
        var result = [];
        for(var i=0,len=arr.length;i<len;i+=5){
            result.push(arr.slice(i,i+5));
        }
        return result
    },
    $.searchProductKey = function (data) {
        $.ProductKeyData = [];
        $.bool = true;
        var prouductUrl = location.host === 'erp.gshopper.com'? '//data.gshopper.com/search/b2bProductKey':'//data.gshopper.stage.com/search/b2bProductKey'
        window.$.ajax({
            type: "POST",
            url: prouductUrl,
            data: {
                "search":{
                    "sku_id":data.SKU_ID,
                    "order_id":data.ORDER_ID
                }
            },
            success: function (res) {
                if (200 == res.code) {
                    var arr = res.data.product_keys
                    $.ProductKeyData = $.spilt(arr)
                    $.$apply()
                }
            }
        });
/*        $Ajax.post(prouductUrl, JSON.stringify({
            "search":{
                "sku_id":data.SKU_ID,
                "order_id":data.ORDER_ID
            }
        }), function (result) {
            if (200 == result.status) {
                var arr = result.data.data.product_keys
                $.ProductKeyData = $.spilt(arr)
            }
        })*/
    };
    $.setBool = function(bool){
        $.bool = bool;
    }
    $.showDetail = function (index, item, data) {
        item.min =  !item.min;
        var key = index + 1,
            obj = {
                addTag: true,
                delivery_prices: item.delivery_prices
            }
        if (data[key] && data[key].addTag) {
            data.splice(key, 1)
        } else {
            data.splice(key, 0, obj)
        }
    };
    $.recall = function(number){
        document.querySelector('#dialog').click();
        //alert(1)
        var query = {
            out_bill_id:number,
            type:1
        }
        document.querySelector('#yes').onclick = function(){
            $Ajax.post('/index.php?m=b2b&a=b2bVirtualWarehouseRevoke', JSON.stringify(query), function (res) {
                if (res.code === 0) {
                    layer.msg('撤销成功')
                    setTimeout(function(){
                        location.reload()
                    },1000)
                } else {
                    layer.msg(res.msg)
                }
            }, null, null, 'application/json;charset=UTF-8');
        }

    };
    $.jupmPage = function (orderId, title, id,type,type2) {
        var dom = document.createElement('a');
        var _href;
        switch (title) {
            case '理货详情':
                _href = "/index.php?m=b2b&a=warehousing_confirm&ORDER_ID=" + orderId + "&ID=" + id;
                break;
            case '发起退货':
                _href = "/index.php?m=b2b&a=b2b_return&order_id=" + orderId;
                break;
            case '发货详情页':
                _href = "/index.php?m=b2b&a=do_ship_show&order_id=" + orderId;
                break;
            case '理货详情页':
                /*if(type2 === '已确认'){
                    $.jupmPage(orderId, '理货详情', id,type)
                    return
                }else{*/
                    _href = "/index.php?m=b2b&a=warehousing_detail&ORDER_ID=" + orderId + "&ID=" + id;
               // }
                break;
            case '理货确认页':
                if(type2 === '已确认'){
                    $.jupmPage(orderId, '发起退货', id,type)
                    return
                }else{
                    _href = "/index.php?m=b2b&a=warehousing_confirm&ORDER_ID=" + orderId + "&ID=" + id +'&type='+type;
                }
                break;
            case '收款认领列表':
                _href = "/index.php?m=b2b&a=receipt_claim_list";
                break;
            case '收款认领详情页':
                _href = '/index.php?m=b2b&a=receipt_claim_detail&account_transfer_no=' + orderId;
                break;
            case 'B2B退货详情':
                _href = "/index.php?m=b2b&a=sales_return&order_id="+orderId;
                break;
        }
        dom.setAttribute("onclick", "opennewtab(this,'"+this.$lang(title) + "')");
        dom.setAttribute("_href", _href);
        dom.click();
    };
    $.updateData = function (param, data) {
        // 请求参数
        var query = { data: [], info: {} };

        param.forEach(function (item) {
            query.data.push({ order_id: item.ORDER_ID, good_id: item.ID, type: 'divide' });
        })
        query.info = { sales_team: data.SALES_TEAM, date: data.po_time };
        //请求接口

        $Ajax.post('/index.php?m=b2b&a=updateSkuInfo', JSON.stringify(query), function (res) {
            if (res.code == 200) {
                res.body.forEach(function (e) {
                    param.forEach(function (k) {
                        if (k.ID == e.good_id) {
                            k.percent_purchasing = e.percent_purchasing
                            k.percent_introduce = e.percent_introduce
                            k.percent_sale = e.percent_sale
                        }
                    })
                })
            } else {
                alert($rootScope.$lang(res.msg))
            }
        }, null, null, 'application/json;charset=UTF-8');
    };

    $.getdatestr = function (times) {
        if (times == '' || times == null || times == 'undefined') return times
        var dd = new Date(times);
        dd.setDate(dd.getDate());
        var y = dd.getFullYear();
        var m = dd.getMonth() + 1;
        var d = dd.getDate();
        return y + "-" + m + "-" + d;
    }
    $.sum_compute = function ($e) {
        for (s in $e) {
            $.goods_sum.neednum += parseInt($e[s].required_quantity)
            $.goods_sum.shipnum += parseInt($e[s].SHIPPED_NUM)
            $.goods_sum.totalNormalGoods += parseInt($e[s].normal_goods)
            $.goods_sum.totalBormalGargo += parseInt($e[s].normal_cargo)

            $.goods_sum.allnum += (parseFloat($e[s].price_goods) * parseFloat($e[s].required_quantity))
            $.goods_sum.difference += parseFloat($e[s].inbound_difference) || 0
        }
        $.goods_sum.allnum = $.goods_sum.allnum.toFixed(2)
    }


    $.init();
    $.king = function (e) {
        if (e) {
            var k = parseFloat(e).toFixed(2).toString().split('.')
            if (e.toString().indexOf('.') > 0) {
                var s = '.' + k[1]
            } else {
                var s = ''
            }
            return k[0].toString().replace(/\d{1,3}(?=(\d{3})+(\.\d*)?$)/g, '$&,') + s;
        } else {
            if(e == 'NaN') return 0
            return e
        }
    }

    $.join_ares = function (e) {
        if (e != null) {
            e_data = JSON.parse(e)
            var initdata = this.init_data
            var area = ''
            if (e_data.country) area = initdata.area[e_data.country]
            if (e_data.stareet) area += '-' + initdata.area[e_data.stareet]
            if (e_data.city) area += '-' + initdata.area[e_data.city]
            if (e_data.targetCity) area += '-' + e_data.targetCity
            return area
        }
        return e
    },
        $.show_node_arr = function (e) {
            if (typeof(e) == 'undefined') return null;
            var arr = JSON.parse(e);
            if (!arr[0]) return null;
            var run = ''
            if (!arr[0].nodeType) return null
            for (var a in arr) {
                if (arr[a]) run += (this.show_node(JSON.stringify(arr[a])) + '; ')
            }
            return run

        },
        $.node_to_code = function (e, type) {
            return this.init_data[type][e]
        },
        $.show_node = function (e) {
            var d = JSON.parse(e)
            var init_data = this.init_data
            if (!d) return '-(退税)'
            if (!d.nodeType) return null
            if (this.node_to_code(d.nodeType, 'node_type')) {
                if (!d.nodeDate) {
                    return null;
                }
                if (d.nodeType in init_data.node_type && d.nodeWorkday in init_data.node_is_workday) {
                    var run_e = init_data.number_th[d.nodei] + ':' + init_data.node_type[d.nodeType].CD_VAL + init_data.node_date[d.nodeDate].CD_VAL + init_data.node_is_workday[d.nodeWorkday].CD_VAL + '-' + d.nodeProp + '%'
                    return run_e
                }
            }
        },
        $.gather_date = function (k) {
            var gather_key = k
            if (!gather_key) return null
            var d = JSON.parse(gather_key.receiving_code)
            if (!d) return null
            var times = null
            switch (parseInt(d.nodeType)) {
                case 0:
//                        合同
                    times = gather_key.po_time
                    break;
                case 1:
//                        发货
                    times = gather_key.DELIVERY_TIME
                    break;
                case 2:
//                      入港
                    times = gather_key.Estimated_arrival_DATE
                    break;
                case 3:
//                        入库
                    times = gather_key.WAREING_DATE
                    break;

                default:
            }
            if (!times) return null
            var gather_date_string = this.GetDateStr(times, this.init_data.node_date[d.nodeDate].CD_VAL)
            return gather_date_string
        },
        $.GetDateStr = function (times, AddDayCount) {
            if (!times) return null
            var dd = new Date(times);
            dd.setDate(dd.getDate() + AddDayCount);
            var y = dd.getFullYear();
            var m = dd.getMonth() + 1;
            var d = dd.getDate();
            return y + "-" + m + "-" + d;
        },
        $.overdue = function (e, t_date) {
            if (!e || e.transaction_type || this.gather_date(e) == null) return null
            var overdue_msg = '未逾期'
            var overdue_text = 0
            var gather_date_t = new Date(this.gather_date(e))
            var today_time = Math.round(new Date().getTime() / 1000)
            gather_date_t = Math.round(Date.parse(gather_date_t) / 1000)
            if (t_date) today_time = Math.round(Date.parse(new Date(t_date)) / 1000)
            if (gather_date_t < today_time) overdue_text = Math.floor(Math.abs(gather_date_t - today_time) / 60 / 60 / 24)
            if (overdue_text) overdue_msg = '<span style="color: #f00">逾期' + overdue_text + 'Day</span>'
            return overdue_msg
        },
        $.run_invioce = function (e) {
            if (e) return this.init_data.invioce[e].CD_VAL
        },
        $.run_tax_point = function (e) {
            if (e) return this.init_data.tax_point[e].CD_VAL
        },
        $.run_period = function (e) {
            if (e) return this.init_data.period[e - 1].CD_VAL
        }

        $.delOrder = function(id){
            utils.modal(true, {
                title: '提示',
                content: "如果有发货、收款、占用、预订、关联采购单，删除可能导致未知的后果。确定要删除吗？",
                confirmText: '确认',
                cancelText: '取消',
                width: 480,
                contentClass: 'text-center',
                confirmFn: function () {
                    $Ajax.post("/index.php?m=B2b&a=delPoOrderAllInfo", JSON.stringify({po_id:id}),
                        function(res){
                            if(res.code == 200){
                                layer.msg(res.msg);
                                setTimeout(function () {
                                    backTab('/index.php?m=b2b&a=order_list','订单列表')
                                }, 1800)
                            }else{
                                layer.msg(res.msg);
                            }
                        },null,null,"application/json");
                }
            });
        }



}])


$(window).on("storage", function (e) {
    if (e.originalEvent.key === 'f5') {
        window.location.reload();
        sessionStorage.removeItem('f5');
    }
});


let hiddenTextarea;


const HIDDEN_STYLE = `
  height:0 !important;
  visibility:hidden !important;
  overflow:hidden !important;
  position:absolute !important;
  z-index:-1000 !important;
  top:0 !important;
  right:0 !important
`;

const CONTEXT_STYLE = [
  'letter-spacing',
  'line-height',
  'padding-top',
  'padding-bottom',
  'font-family',
  'font-weight',
  'font-size',
  'text-rendering',
  'text-transform',
  'width',
  'text-indent',
  'padding-left',
  'padding-right',
  'border-width',
  'box-sizing'
];

function calculateNodeStyling(targetElement) {
  const style = window.getComputedStyle(targetElement);

  const boxSizing = style.getPropertyValue('box-sizing');

  const paddingSize = (
    parseFloat(style.getPropertyValue('padding-bottom')) +
    parseFloat(style.getPropertyValue('padding-top'))
  );

  const borderSize = (
    parseFloat(style.getPropertyValue('border-bottom-width')) +
    parseFloat(style.getPropertyValue('border-top-width'))
  );

  const contextStyle = CONTEXT_STYLE
    .map(name => `${name}:${style.getPropertyValue(name)}`)
    .join(';');

  return { contextStyle, paddingSize, borderSize, boxSizing };
}

function calcTextareaHeight(
  targetElement,
  minRows = 1,
  maxRows = null
) {
  if (!hiddenTextarea) {
    hiddenTextarea = document.createElement('textarea');
    document.body.appendChild(hiddenTextarea);
  }

  let {
    paddingSize,
    borderSize,
    boxSizing,
    contextStyle
  } = calculateNodeStyling(targetElement);

  hiddenTextarea.setAttribute('style', `${contextStyle};${HIDDEN_STYLE}`);
  hiddenTextarea.value = targetElement.value || targetElement.placeholder || '';

  let height = hiddenTextarea.scrollHeight;
  const result = {};

  if (boxSizing === 'border-box') {
    height = height + borderSize;
  } else if (boxSizing === 'content-box') {
    height = height - paddingSize;
  }

  hiddenTextarea.value = '';
  let singleRowHeight = hiddenTextarea.scrollHeight - paddingSize;

  if (minRows !== null) {
    let minHeight = singleRowHeight * minRows;
    if (boxSizing === 'border-box') {
      minHeight = minHeight + paddingSize + borderSize;
    }
    height = Math.max(minHeight, height);
    result.minHeight = `${ minHeight }px`;
  }
  if (maxRows !== null) {
    let maxHeight = singleRowHeight * maxRows;
    if (boxSizing === 'border-box') {
      maxHeight = maxHeight + paddingSize + borderSize;
    }
    height = Math.min(maxHeight, height);
  }
  result.height = `${ height }px`;
  hiddenTextarea.parentNode && hiddenTextarea.parentNode.removeChild(hiddenTextarea);
  hiddenTextarea = null;
  return result;
};