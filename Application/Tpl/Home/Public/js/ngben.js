var myApp = angular.module('myApp', ['ngAnimate','ui.bootstrap','ui.router']);

myApp.config(function ($stateProvider,$httpProvider) {
    $httpProvider.defaults.transformRequest = [function (data) {
        /**
         * 传输参数时的格式化
         * @param {Object} obj
         * @return {String}
         */
        var param = function (obj) {
            var query = '';
            var name, value, fullSubName, subName, subValue, innerObj, i;
            for (name in obj) {
                value = obj[name];
                if (value instanceof Array) {
                    for (i = 0; i < value.length; ++i) {
                        subValue = value[i];
                        fullSubName = name + '[]';
                        innerObj = {};
                        innerObj[fullSubName] = subValue;
                        query += param(innerObj) + '&';
                    }
                } else if (value instanceof Object) {
                    for (subName in value) {
                        subValue = value[subName];
                        fullSubName = subName;
                        innerObj = {};
                        innerObj[fullSubName] = subValue;
                        query += param(innerObj) + '&';
                    }
                } else if (value !== undefined && value !== null) {
                    query += encodeURIComponent(name) + '='
                        + encodeURIComponent(value) + '&';
                }
            }
            return query.length ? query.substr(0, query.length - 1) : query;
        };
        return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
    }];

    var dynamicpinState = {
        name: 'dynamicpin',
        url: '/dynamicpin',
        templateUrl: 'index.php?m=dynamic&a=ngpin',
        controller: "dynamicpin"
    }
    var b2bState = {
        name: 'b2b',
        url: '/b2b',
        templateUrl: 'index.php?m=b2b&a=ngorder_list',
        controller: "b2b"
    }
    var b2baddState = {
        name: 'b2badd',
        url: '/b2badd',
        templateUrl: 'index.php?m=b2b&a=ngorder_add_v2',
        controller: "b2bAdd"
    }
    var b2bSendState = {
        name: 'b2bsend',
        url: '/b2bsend',
        templateUrl: 'index.php?m=b2b&a=ngsend_list_v2',
        controller: "b2bSend"
    }

    $stateProvider.state(dynamicpinState)
        .state(b2bState)
        .state(b2baddState)
        .state(b2bSendState);

});


myApp.service('$Ajax', function ($http) {
        return {
            post: function (path, params, successFn, failureFn,flieType,head) {
                var load = layer.load(2);
                if (typeof failureFn != "function") {
                    failureFn = function () {
                        layer.close(load)
                    };
                }
                var responseType = flieType ? 'arraybuffer':null;
                var headers =  head ||'application/x-www-form-urlencoded;charset=utf8'
                $http({
                    url: path,
                    method: 'POST',
                    timeout: 60000,
                    // withCredentials: false,
                    headers: {
                        'Content-Type':headers
                    },
                    responseType: responseType,
                    data: params
                }).success(function (result) {
                        successFn.call(this, result);
                        layer.close(load)
                }).error(failureFn);
            },
            get: function (path, param, successFn, errorFn) {
                var load = layer.load(2);
                $http({
                    method: 'GET',
                    url: path,
                    params: param
                }).success(function (data, status, header, config) {
                    layer.close(load)
                    if (successFn && typeof (successFn) == 'function') {
                        successFn(data, status, header, config);
                    }
                }).error(function (data, status, header, config) {
                    layer.close(load)
                    if (errorFn && typeof (errorfun) == 'function') {
                        errorFn(data, status, header, config);
                    }
                })
            }
        };
    }
);


/**
 * Func格式化
 * 
 * myApp.factory('MathService', function() {
 *     var factory = {};
 *     factory.multiply = function(a, b) {
 *         return a * b;
 *     }
 *     return factory;
 * });
 * 
 */
myApp.service('CalcService', function(){
    this.square = function(a) {
        return 'square test';
    }
    this.load_show = function(){
        initLoadDiv('loaddiv');showAndHide('loaddiv','show');
    }
    this.load_hide = function(){
        initLoadDiv('loaddiv');showAndHide('loaddiv','hide');
    }
    this.seeprice = function (num) {
        var a = keep_float(num);
        a = keep_float_fmt(a);
        return a;
    }
    this.king = function(e){
        if (e) {
            var k = e.toString().split('.')
            if (e.toString().indexOf('.') > 0) {
                var s = '.' + k[1]
            } else {
                var s = ''
            }
            return k[0].toString().replace(/\d{1,3}(?=(\d{3})+(\.\d*)?$)/g, '$&,') + s;
        } else {
            return e
        }
    }
    this.unking = function (num) {
        if (isNaN(num) && typeof(num) == 'string') {
            var x = num.split(',');
            return parseFloat(x.join(""));
        } else {
            return num
        }
    }
    this.ratio = function(n){
        n = keep_float_fmt(n*100, 2);
        return n+'%';
    }
    /**
     *  Replace obj data 
     *
     */
    this.obj_replace_obj = function(obj1,obj2){
        for(var i in obj2){
            obj1[i] = obj2[i];
        }
        return obj1;
    }
    /**
     *  Check err msg of result
     */
    this.checkErrRes = function(result){
        var ret = '';
        if(typeof(result)=='string'){
            var reg = /script/;
            var is_script = reg.test(result);
            var reg = /m=public\&a=login/;
            var is_login = reg.test(result);
            if(is_script && is_login){
                ret = 'Need login';
            }
        }
        if(!ret){
            if(typeof(result)=='string'){
                ret = result;
            }
        }
        if(!ret){
            ret = JSON.stringify(result);
        }
        if(!ret) ret = 'Error';
        return ret;
    }
    this.testB2bPo = function(){
        var d = {
            "poData": {
                "poNum": "qq",
                "clientName": "京东商城贸易",
                "clientNameEN": "",
                "busLice": "",
                "contract": "546546",
                "ourCompany": "iZENEhk,Limited",
                "poAmount": "104",
                "BZ": "N000590100",
                "poScanner": "",
                "poTime": "2017-12-14",
                "targetCity": "",
                "shipping": "N001530500",
                "cycleNum": "1",
                "poPaymentNode": [{
                    "nodei": 0,
                    "nodeType": "N001390100",
                    "nodeDate": "N001420100",
                    "nodeWorkday": 0,
                    "nodeProp": "100"
                }],
                "country": "1",
                "province": "48",
                "city": "49",
                "street": "",
                "detailAdd": "11111111111111111111111111112",
                "saleTeam": "N001281500",
                "Remarks": "",
                "invioce": "N001350400",
                "tax_point": "N001340100",
                "backend_currency": "N000590100",
                "backend_estimat": "100",
                "logistics_currency": "N000590100",
                "logistics_estimat": "10",
                "otherIncome": "0",
                "drawback_estimate": "0",
                "sale_tax": "15.11",
                "cur_tuishui": "N000590100",
                "cur_saletax": "N000590100",
                "cur_other": "N000590100",
                "clientNameEn": ["Jingdong mall trade"],
                "tax_rebate_income": 0,
                "business_type": "N001160200",
                "lastname": "huaxin",
                "IMAGEFILENAME": "show.png",
                "po_erp_path": "BR_SYS_FILE_20171214105308_8189.png",
                "remarks": "test",
                "deducting_tax_currency": "N000590100",
                "side_taxed_currency": "N000590100",
                "deducting_tax": "0.68",
                "side_taxed": "17.00"
            },
            "skuData": [{
                "skuId": "8000367301",
                "gudsName": "Korea Eundan维他命C 1000 300粒",
                "skuInfo": "标配",
                "warehouse": null,
                "selCurrency": "",
                "gudsPrice": "104",
                "demand": "1",
                "subTotal": "104",
                "drawback": 0,
                "estimateDrawback": "0.00",
                "purchasing_team": "N001291200",
                "introduce_team": "N001301100",
                "GUDS_OPT_ORG_PRC": "21368.0000",
                "STD_XCHR_KIND_CD": "N000590200",
                "percent_sale": "",
                "percent_purchasing": "100",
                "percent_introduce": "",
                "toskuid": "8000367301"
            }]
        };
        return d;
    }
    this.in_array = function (array, search) {
        var err = null
        if (!array[search]) err = search
        return err;
    }
    this.checkRate = function (input) {
        var re = /^[1-9]+[0-9]*]*$/
        return re.test(input)
    }
    this.checkRateDecimal = function (input) {
        var re = /^[0-9]*[.0-9]*]*$/
        return re.test(input)
    }
    this.edit_po_part = function(po_data){
        var o_id = po_data.ORDER_ID;
        if(o_id){
            var url = '/index.php?m=b2b&a=order_list&is_edit=1&edit_id='+o_id+'#/b2badd';
            window.location.href = url;
        }
    }
    this.edit2del_part = function(po_data){
        var o_id = po_data.ORDER_ID;
        var del_url = '/index.php?m=b2b&a=del_o_b2b&is_del=1&del_id='+o_id;
        var can_del_published = this.canDelAsPublished(po_data);
        if(can_del_published){
            del_url = '/index.php?m=b2b&a=del_o_b2b_published&is_del=1&del_id='+o_id;
        }
        if(o_id){
            utils.modal(true, {
                title: '提示',
                content: "你点击了删除订单，确认后该订单将被删除，请确认。",
                confirmText: '确认删除',
                cancelText: '不删除',
                width: 480,
                contentClass: 'text-center',
                confirmFn: function () {
                    var url = del_url;
                    window.location.href = url;
                }
            });
        }
    }
    /**
     *  Reset button in add or edit page
     *
     */
    this.btn_add_reset = function(){
        window.location.reload();
    }
    /**
     *
     *
     */
    this.b2b_po_check_only = function($){
        if ((this.unking($.initall.allSubtotal) * 100 + this.unking($.poData.otherIncome) * 100).toFixed(8) != (this.unking($.poData.poAmount) * 100).toFixed(8)) {
            utils.modal(true, {width:500,btnClass:'btn-primary',content:'PO金额不相同[等于其他收入加上销售总额]'});
            return false;
        }
        $.poData.poPaymentNode = $.poData.poPaymentNode?$.poData.poPaymentNode:[];
        $.poData.poPaymentNode_edit = $.poData.poPaymentNode;
        $.poData.poPaymentNode = [];
        if( $.paymentNodeArray.length ){
            $.poData.poPaymentNode = [];
            $.poData.poPaymentNode_edit = [];
        }
        var nodeProp = 0;
        var check_empty = '';
        for (var i = 0; i < $.paymentNodeArray.length; i++) {

            nodeProp += parseInt($.paymentNodeArray[i].nodeProp);
            paymentNode = {
                nodei: i,
                nodeType: $.paymentNodeArray[i].nodeType,
                nodeDate: $.paymentNodeArray[i].nodeDate,
                nodeWorkday: 0,
                nodeProp: $.paymentNodeArray[i].nodeProp
            };
            $.poData.poPaymentNode.push(paymentNode);
            if(!$.paymentNodeArray[i].nodeType){
                check_empty = 'none';
            }
            if(!$.paymentNodeArray[i].nodeDate){
                check_empty = 'none';
            }
            if(!$.paymentNodeArray[i].nodeProp){
                check_empty = 'none';
            }
        }
        if (check_empty) {
            utils.modal(true, {width:500,btnClass:'btn-primary',content:'请检查收款节点'})
            return false;
        }
        if (nodeProp != 100) {
            var nodeProp = 0;
            for(var i in $.poData.poPaymentNode_edit){
                nodeProp += parseInt($.poData.poPaymentNode_edit[i]["nodeProp"]);
            }
        }
        if (nodeProp != 100) {
            utils.modal(true, {width:500,btnClass:'btn-primary',content:'请检查收款节点比例'})
            return false;
        }
        $.poData['tax_rebate_income'] = $.initall.allEsBack
        if ($.poData.shipping == 'N001530500' || $.poData.shipping == 'N001530700') {
            $.poData['deducting_tax_currency'] = $.poData.BZ
            $.poData['side_taxed_currency'] = $.poData.backend_currency
        }
        if ($.poData.shipping == 'N001530500') {
            $.poData['deducting_tax'] = ((this.unking($.poData.poAmount) - (this.unking($.poData.backend_estimat)) * $.deducting_tax) * $.initData.tax_point[$.poData.tax_point].CD_VAL / 100).toFixed(2)
            $.poData['side_taxed'] = (this.unking($.poData.backend_estimat) * $.initData.tax_point[$.poData.tax_point].CD_VAL / 100).toFixed(2)
        }
        if ($.poData.shipping == 'N001530700') {
            $.poData['deducting_tax'] = ((this.unking($.poData.poAmount) - (this.unking($.poData.poAmount) / (1 + ($.initData.tax_point[$.poData.tax_point].CD_VAL / 100)))) - (this.unking($.poData.backend_estimat) * $.deducting_tax - ((this.unking($.poData.backend_estimat) * $.deducting_tax) / (1 + ($.initData.tax_point[$.poData.tax_point].CD_VAL / 100))))).toFixed(2)
            $.poData['side_taxed'] = (this.unking($.poData.backend_estimat) - (this.unking($.poData.backend_estimat) / (1 + ($.initData.tax_point[$.poData.tax_point].CD_VAL / 100)))).toFixed(2)
        }

        var param = {
            poData: $.poData,
            skuData: $.skuData
        };
        //  check data
        if (!this.check_data(param)) {
            return false
        }
        return true;
    }
    /**
     *
     *
     */
    this.b2b_po_check_submit = function($, $Ajax){
        var status = this.b2b_po_check_only($);
        if(!status){
            return false;
        }
        var param = {
            poData: $.poData,
            skuData: $.skuData
        };
        // check edit - check publish
        var do_url = '/index.php?m=b2b&a=save_order';
        if($.edit_id){
            do_url = '/index.php?m=b2b&a=save_o_publish';
            do_url += '&edit_id='+$.edit_id;
        }
        if($.edit_is_publish){
            do_url += '&edit_is_publish='+$.edit_is_publish;
        }
        var this_obj = this;
        $Ajax.post(do_url, JSON.stringify(param), function (result) {
            if (200 == result.status) {
                utils.modal(true, {width:500,btnClass:'btn-primary',content:result.info,id:'show_success_btn'});
                var id = result.data.order_id
                setTimeout(function () {
                    window.location.href = '/index.php?m=b2b&a=order_list&t='+Math.random()+'&order_id=' + id + '#/b2bsend'
                }, 3000)
            } else {
                var msg = '';
                msg = result.info?result.info:msg;
                if(!msg) msg = this_obj.checkErrRes(result);
                if(!msg) msg = 'Error';
                utils.modal(true, {width:500,btnClass:'btn-primary',content:msg})
            }
        }, function(msg){
            console.log(msg);
        }
        );
    }
    /**
     *
     *
     */
    this.check_data = function (e) {
        var info_check = ['poNum', 'cycleNum', 'invioce', 'tax_point', 'shipping', 'country', 'province', 'detailAdd', 'saleTeam', 'backend_estimat', 'backend_currency', 'lastname', 'BZ', 'contract', 'clientName', 'ourCompany', 'poTime']

        info_check.push('IMAGEFILENAME');
        info_check.push('drawback_estimate');
        info_check.push('sale_tax');
        // info_check.push('otherIncome');
        info_check.push('business_type');
        info_check.push('poAmount');

        var goods_check = ['skuId', 'gudsPrice', 'demand']
        goods_check.push('purchasing_team');
        goods_check.push('introduce_team');
        
        var res_info = this.check_required(e.poData, info_check)
        if (res_info.info.length) {
            var msg = ''
            for (var i = 0; i < res_info.info.length; i++) {
                msg += res_info.info[i] + "<br/>"
            }
            utils.modal(true, {width:500,btnClass:'btn-primary',content:msg})
            return false
        }
        var res_goods = this.check_required(e.skuData, goods_check, 'sku')
        if (res_goods.info.length) {
            var msg = ''
            for (var i = 0; i < res_goods.info.length; i++) {
                msg += res_goods.info[i] + "<br/>"
            }
            utils.modal(true, {width:500,btnClass:'btn-primary',content:msg});
            return false
        }
        //check fencheng
        var res_fc = this.check_fencheng(e.skuData);
        if (res_fc.info.length) {
            var msg = ''
            for (var i = 0; i < res_fc.info.length; i++) {
                msg += res_fc.info[i] + "<br/>"
            }
            utils.modal(true, {width:500,btnClass:'btn-primary',content:msg});
            return false
        }
        return true
    }
    /**
     *
     *
     */
    this.check_required = function (data, check, type) {
        var err = []
        var err_info = []
        for (c in check) {
            if ('sku' == type) {
                for (var i = 0; i < data.length; i++) {
                    res = this.in_array(data[i], check[c])
                    if (err[i] == 'undefined' || !err[i]) err[i] = []
                    if (res) {
                        err_info.push('第' + (parseInt(i) + 1) + '行；' + this.tocn[check[c]] + '>为空')
                    }
                    if (check[c] == 'gudsPrice') {
                        if (!data[i][check[c]] || this.unking(data[i][check[c]]) <= 0) {
                            err_info.push('第' + (parseInt(i) + 1) + '行；' + this.tocn[check[c]] + '>格式错误[需为大于零的正数]')
                        }
                    }
                    if (check[c] == 'demand') {
                        var rate_data = this.checkRate(this.unking(data[i][check[c]]))
                        if (!data[i][check[c]] || this.unking(data[i][check[c]]) < 0 || !rate_data) {
                            err_info.push('第' + (parseInt(i) + 1) + '行；' + this.tocn[check[c]] + '>格式错误[需为正整数]')
                        }
                    }
                }
                err.info = err_info
            } else {
                res = this.in_array(data, check[c]);
                if (res) {
                    // check zero number
                    var checkArr = ['drawback_estimate','otherIncome','sale_tax','logistics_estimat'];
                    if (js_in_array(check[c],checkArr)) {
                        if(data[check[c]]===0){
                            res = null;
                        }
                    }
                }
                if (res) {
                    if(false){

                    }else{
                        err.push(res)
                        err_info.push(this.tocn[check[c]] + '>为空')
                    }
                }
                if (check[c] == 'backend_estimat' || check[c] == 'logistics_estimat') {
                    var rate_data = this.checkRateDecimal(this.unking(data[check[c]]))
                    if(check[c] == 'logistics_estimat' && !data[check[c]]){

                    }else if (!data[check[c]] || data[check[c]] < 0 || !rate_data) {
                        err_info.push(this.tocn[check[c]] + '>格式错误[需为有效小数]')
                    }
                }
                err.info = err_info
            }
        }
        return err
    }
    /**
     * check fc
     *
     */
    this.check_fencheng = function(skuData){
        var err = [];
        var err_info = [];
        var n=0;
        var total = 0;
        for(var i in skuData){
            ++n;
            var a = skuData[i]['percent_sale'];
            var b = skuData[i]['percent_purchasing'];
            var c = skuData[i]['percent_introduce'];
            a = keep_float_comma(a);
            b = keep_float_comma(b);
            c = keep_float_comma(c);
            var is100 = 0;
            if(a>=0 && b>=0 && c>=0){
                total = a+b+c;
                if(total==100){
                    is100 = 1;
                }
            }else{
                err_info.push('Line:'+n+':'+'分成比例(只允许0≤X≤100间的正整数)');
            }
            if(is100==0){
                err_info.push('Line:'+n+':'+'(SKU销售团队、采购团队、介绍团队，三个值之和必须为100%)');
            }
        }
        err.info = err_info;
        return err;
    }
    /**
     *
     *
     */
    this.tocn = {
        poNum: 'PO编号',
        cycleNum: '付款周期',
        invioce: '发票',
        tax_point: '税点',
        shipping: '发货方式',
        country: '目标城市',
        province: '省',
        detailAdd: '详细地址',
        saleTeam: '销售团队',
        remarks: '备注',
        IMAGEFILENAME: 'PO扫描件',
        backend_currency: '预估商品币种',
        logistics_currency: '预估物流币种',
        backend_estimat: '商品含税成本',
        logistics_estimat: '预估物流成本',
        skuId: 'SKUID',
        gudsPrice: '商品售价',
        demand: '需求数量',
        purchasing_team: '采购团队',
        introduce_team: '介绍团队',
        drawback: '退税比例',
        drawback_estimate: '退税金额',
        sale_tax: '销售端应缴税',
        otherIncome: '其他收入',
        business_type: '业务类型',
        poAmount: 'PO金额',
        lastname: '销售同事',
        BZ: 'PO金额币种',
        contract: '适用合同',
        clientName: '客户名称',
        ourCompany: '我方公司',
        poTime: 'PO时间'
    }
    /**
     *
     *
     */
    this.all_count_sku_price = function($){
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
    /**
     *
     *
     */
    this.map_po_fields = function(onePo){
        var ret = {}
        ret.poNum = onePo.PO_ID;
        ret["clientName"] = onePo["CLIENT_NAME"];
        ret["clientNameEn"] = onePo["CLIENT_NAME_EN"];
        ret["clientNameEn"] = [onePo["CLIENT_NAME_EN"]];
        ret["busLice"] = onePo["Business_License_No"];
        ret["contract"] = onePo["contract"];
        ret["ourCompany"] = onePo["our_company"];
        ret["poAmount"] = onePo["po_amount"];
        ret["BZ"] = onePo["po_currency"];
        ret["poScanner"] = "";
        ret["poTime"] = onePo["po_time"];
        ret["targetCity"] = onePo["addrs"]["targetCity"];
        ret["shipping"] = onePo["DELIVERY_METHOD"];
        ret["cycleNum"] = onePo["BILLING_CYCLE_STATE"];
        ret["poPaymentNode"] = onePo["PAYMENT_NODE"];
        ret["poPaymentNode"] = eval("(" + ret["poPaymentNode"] + ")");
        ret["country"] = onePo["addrs"]["country"];
        ret["province"] = onePo["addrs"]["province"];
        ret["city"] = onePo["addrs"]["city"];
        ret["street"] = "";
        ret["detailAdd"] = onePo["addrs"]["targetCity"];
        ret["saleTeam"] = onePo["SALES_TEAM"];
        ret["Remarks"] = onePo["remarks"];
        ret["invioce"] = onePo["INVOICE_CODE"];
        ret["tax_point"] = onePo["TAX_POINT"];
        ret["backend_currency"] = onePo["backend_currency"];
        ret["backend_estimat"] = onePo["backend_estimat"];
        ret["logistics_currency"] = onePo["logistics_currency"];
        ret["logistics_estimat"] = onePo["logistics_estimat"];
        ret["otherIncome"] = onePo["other_income"];
        ret["drawback_estimate"] = onePo["drawback_estimate"];
        ret["sale_tax"] = onePo["sale_tax"];
        ret["cur_tuishui"] = onePo["backend_currency"];
        ret["cur_saletax"] = onePo["po_currency"];
        ret["cur_other"] = onePo["po_currency"];
        ret["tax_rebate_income"] = onePo["tax_rebate_income"];
        ret["business_type"] = onePo["business_type"];
        ret["lastname"] = onePo["PO_USER"];
        ret["IMAGEFILENAME"] = onePo["PO_FILFE_PATH"];
        ret["po_erp_path"] = onePo["po_erp_path"];
        ret["remarks"] = onePo["remarks"];
        ret["deducting_tax_currency"] = onePo["deducting_tax_currency"];
        ret["side_taxed_currency"] = onePo["side_taxed_currency"];
        ret["deducting_tax"] = onePo["deducting_tax"];
        ret["side_taxed"] = onePo["side_taxed"];
        ret["submit_state"] = onePo["submit_state"];
        return ret;
    }
    /**
     *
     *
     */
    this.map_po_sku_fields = function(skuList){
        var ret = new Array;
        for(var i in skuList){
            var data = skuList[i];
            var sku = {};
            sku["skuId"] = data["SKU_ID"];
            sku["gudsName"] = data["goods_title"];
            sku["skuInfo"] = data["goods_info"];
            sku["warehouse"] = null,
            sku["selCurrency"] = "",
            sku["gudsPrice"] = data["price_goods"];
            sku["demand"] = data["required_quantity"];
            sku["subTotal"] = parseFloat(data["price_goods"]*data["required_quantity"]).toFixed(2),
            sku["drawback"] = data["tax_rebate_ratio"];
            sku["estimateDrawback"] = "0",
            sku["purchasing_team"] = data["purchasing_team"];
            sku["introduce_team"] = data["introduce_team"];
            sku["GUDS_OPT_ORG_PRC"] = "",
            sku["STD_XCHR_KIND_CD"] = "",
            sku["percent_sale"] = data["percent_sale"];
            sku["percent_purchasing"] = data["percent_purchasing"];
            sku["percent_introduce"] = data["percent_introduce"];
            sku["toskuid"] = data["sku_show"];
            ret.push(sku);
        }
        return ret;
    }
    /**
     *  Init the selector data of po
     *
     */
    this.po_init_data = {
        country: [],                     //国家
        city: [],                        //城市
        allWarehouse: [],                //所有仓库
        currency: [],                    //币种
        taxRebateRatio: [],              //退税比例
        salesTeam: [],                   //销售团队
        paymentNode: [],                 //付款节点
        paymentCycle: [],                //付款周期
        invoicePoint: [],                //发表和税点
        shipping: {                      //发货方式
            //TODO:暂无数据  data需要初始化值
            active: 0,
            data: []
        },
        invioce: [],
        tax_point: [],
        contracts: [],
        business_direction:[],
        business_type:[]
    }
    /**
     *
     *
     */
    this.po_edit_fill_in = function($){
        if($.poData){
            // 收款周期 - 收款节点
            $.changeCycle();
            if($.poData.poPaymentNode){
                for(var i in $.poData.poPaymentNode){
                    var tmp = $.poData.poPaymentNode[i];
                    if($.paymentNodeArray[i]){
                        $.paymentNodeArray[i].nodeType = tmp.nodeType;
                        $.paymentNodeArray[i].nodeDate = tmp.nodeDate;
                        $.paymentNodeArray[i].nodeWorkday = tmp.nodeWorkday;
                        $.paymentNodeArray[i].nodeProp = tmp.nodeProp;
                    }
                }
            }
            // about - 客户名称 - about
            if($.poData.clientName){
                // need hide showclient
                $.get_this_ht(1);
                // if(typeof($.poData.clientNameEn)=='object'){
                //     $.poData.clientNameEn = ($.poData.clientNameEn).pop();
                // }
                this.clean_get_ht($);
            }
            // about - country
            if($.poData.country){
                $.changeCounty($.poData.country,'');
            }
            if($.poData.province){
                $.changeCounty($.poData.province,'end');
            }
            // abcout - all data
            this.all_count_sku_price($);
        }
    }
    this.clean_get_ht = function ($) {
        var ajax_obj = $.ajaxObj;
        ajax_obj.post("/index.php?m=b2b&a=get_ht", {sp_charter_no: $.poData.clientName, like_no: 1}, function (result) {
            $.initData.contracts = result.data.contract
        });
    }
    /**
     *  Check publish btn which can be show
     *
     */
    this.isCanPublish = function(info){
        var ret = 0;
        if(!info) return ret;
        if(info.submit_state){
            if(info.submit_state==1){
                ret = 1;
            }
        }
        return ret;
    }
    /**
     *  Check publish edit btn which can be show
     *
     */
    this.isCanEditPub = function(info){
        var ret = 0;
        if(!info) return ret;
        if(typeof(info.is_shipped)!='undefined' && typeof(info.is_receipts)!='undefined' && typeof(info.is_tax)!='undefined'){
            if(info.is_shipped==0 && info.is_receipts==0 && info.is_tax==0){
                ret = 1;
            }
        }
        return ret;
    }
    /**
     *
     */
    this.canDelAsDraft = function(info){
        var is_no_publish = this.isCanPublish(info);
        var is_can_edit = this.isCanEditPub(info);
        var ret = 0;
        if(is_no_publish==1){
            if(is_can_edit==1){
                ret = 1;
            }
        }
        return ret;
    }
    /**
     *
     */
    this.canDelAsPublished = function(info){
        var is_no_publish = this.isCanPublish(info);
        var is_can_edit = this.isCanEditPub(info);
        var ret = 0;
        if(is_no_publish==0){
            if(is_can_edit==1){
                ret = 1;
            }
        }
        return ret;
    }
});



