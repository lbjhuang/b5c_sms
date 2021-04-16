'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}

var VM = new Vue({
    el: '#companyDetail',
    data: {
        management_info: {}, //基本信息
        qualification_info: [], //资质信息
        contract_info: [], //	合同信息
        bankaccount_info: [], //账户信息
        store_info: [], //店铺信息
        urlIdd:''
    },
    created: function () {
        this.baseData();
    },
    methods: {
        // 编辑
        editButton :function getTabledata () {
            var route = document.createElement("a");
            var title = "opennewtab(this,'编辑我方公司')";
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", title);
            route.setAttribute("_href", '/index.php?m=company&a=create&type=edit&idd='+this.urlIdd);
            route.onclick();
        },
        baseData: function () {
            var _this = this;
            if(sessionStorage.getItem("ourCompany")){
                var tabId = sessionStorage.getItem("ourCompany");
                if(tabId == '新建我方公司'){
                    window.parent.document.querySelector('#min_title_list').querySelector('li[title="新建我方公司"] b').click()
                    $(window.parent.document.querySelector('#iframe_box')).find('.show_iframe:visible').find('.loading').hide()
                    sessionStorage.setItem("ourCompany", '');
                }else if(tabId == '编辑我方公司'){
                    window.parent.document.querySelector('#min_title_list').querySelector('li[title="编辑我方公司"] b').click()
                    $(window.parent.document.querySelector('#iframe_box')).find('.show_iframe:visible').find('.loading').hide()
                    sessionStorage.setItem("ourCompany", '');
                }
            }

            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i=0;i<vars.length;i++) {
                    var pair = vars[i].split("=");
                    if(pair[0] == 'idd'){
                        var urlIdd = pair[1]
                    }
            }
            _this.urlIdd = pair[1]
            axios.post('/index.php?m=company&a=detail', {
                "id": urlIdd,
                "only_show_edit":""
            }).then(function (res) {
                console.log(res)
                if (res.data.code == 200) {
                    if(res.data.data.management_info){
                        _this.management_info = res.data.data.management_info;
                    }
                    if(res.data.data.qualification_info){
                        _this.qualification_info = res.data.data.qualification_info;
                    }
                    if(res.data.data.bankaccount_info){
                        _this.bankaccount_info = res.data.data.bankaccount_info;
                    }
                    if(res.data.data.store_info){
                        _this.store_info = res.data.data.store_info;
                    }
                    if(res.data.data.contract_info){
                        _this.contract_info = res.data.data.contract_info;
                    }
                    console.log(_this.management_info)
                } else {
                    _this.$message({
                        message: data.msg,
                        type: 'error'
                    });
                }
            })
        },
        // 跳转银行账号
        viewDetail: function (item,type) {
            console.log(item)
            if(type == 'accountInfo'){
                var obj = {}
                obj.accountBank = item.account_bank
                obj.accountType = item.account_type
                obj.bsbNo = item.bsb_no
                obj.companyCode = item.company_code
                obj.currencyCode = item.currency_code
                obj.id = item.id
                obj.openBank = item.open_bank
                obj.reason = item.reason
                obj.state = item.state
                obj.swiftCode = item.swift_code
                obj.updateTime = item.update_time
                obj.updateUser = item.update_user
    
                sessionStorage.setItem('backAccount', JSON.stringify(obj));
                var href = "/index.php?m=finance&a=accountDetail",
                    a = document.createElement("a");
                a.setAttribute("style", "display: none");
                a.setAttribute("onclick", "opennewtab(this,'" + this.$lang('账户详情') + "')" );
                a.setAttribute("_href", href);
                a.onclick();
            }else if(type == 'contractInfo'){
                var idd = item.ID
                var href = "/index.php?m=contract&a=show&ID="+idd,
                a = document.createElement("a");
                a.setAttribute("style", "display: none");
                a.setAttribute("onclick", "opennewtab(this,'" + this.$lang('合同详情') + "')" );
                a.setAttribute("_href", href);
                a.onclick();
            }
        }



    },
    filters:{
        // 千分位处理
        thousandsDeal: function (num) {
            if(num){
                return (num || 0).toString().replace(/\d+/, function (n) {
                    var len = n.length;
                    if (len % 3 === 0) {
                        return n.replace(/(\d{3})/g, ',$1').slice(1);
                    } else {
                        return n.slice(0, len % 3) + n.slice(len % 3).replace(/(\d{3})/g, ',$1');
                    }
                })
            }else{
                return ''
            }

        },
    }
})