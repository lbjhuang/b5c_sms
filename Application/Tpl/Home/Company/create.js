'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}

var VM = new Vue({
    el: '#createCompany',
    data: {
        title:'',
        urlIdd:'',
        form: {
            companyName:'',
            registrationStatus_cd:'',
            registrationStatus:[],
            OaNum:'',
            selectCountry: '',
            selectProvince: '',
            selectCounty: '',
            country:[],
            province:[],
            county:[],
            capitalInput:'',
            address:'',
            capital_cd:'',
            capital:[],
            remark:'',
            representative:'',
            supervisor:'',
            legal_alias_name:'',
            supervisor_alias_name:'',
            shareholderName_cd:'',
            shareholderName:[],
            shareholder_cd:'',
            // shareholder:[],
            shareholderList:[],
            company_shareholder_type:[],
            company_no: '',
            areaStatus:false,
            register_time: '',
            secretary_company_sp_id: [{supplier_id: '', company_telephone: []}],
            agency_company_sp_id: [{supplier_id: '', company_telephone: []}],
            agent_contact: []

        },
        secretaryCompany: [],
        agentCompany: [],
        required:false,
    },
    created: function () {
        this.pageType();
        this.ourCompany();
        this.areaData();
        this.baseData();
        this.getSecretaryCompany()
        this.getAgentCompany();
        // this.form.shareholderList = []
    },
    methods: {
        // 基本信息获取
        ourCompany:function(){
            var _this = this;
            axios.post('/index.php?g=common&m=index&a=get_cd', {
                cd_type:{
                    our_company:'true',
                    company_business_status:'true',
                    currency:'true',
                    company_shareholder_type:'true'
                }
            }).then(function (res) {
                // console.log(res)
                if(res.data.code === 2000){
                    _this.form.shareholderName = res.data.data.our_company
                    _this.form.registrationStatus = res.data.data.company_business_status
                    _this.form.capital = res.data.data.currency
                    _this.form.company_shareholder_type = res.data.data.company_shareholder_type
                }
            })
        },
        // 判断页面类型
        pageType: function () {
            var _url = window.location.href
            // console.log(_url)
            if(_url.indexOf("add") != -1 ){
                this.title = '新建我方公司'
            }else if(_url.indexOf("edit") != -1 ){
                this.title = '编辑我方公司'
            }
        },
        // 获取编辑页面数据
        baseData: function () {
            var _this = this;
            var _url = window.location.href
            // console.log(_url)
            if(_url.indexOf("add") != -1 ){
                _this.form.shareholderList = [
                    {
                        "id":"",
                        "type_cd":"N002960002",
                        "shareholder_name":"",
                        "shareholder_name_alias":""
                    }
                ]
            }else if(_url.indexOf("edit") != -1 ){
                var query = window.location.search.substring(1);
                var vars = query.split("&");
                for (var i=0;i<vars.length;i++) {
                        var pair = vars[i].split("=");
                        if(pair[0] == 'idd'){
                            var urlIdd = pair[1]
                            this.urlIdd = urlIdd
                        }
                }
                axios.post('/index.php?m=company&a=detail', {
                    "id": urlIdd,
                    "only_show_edit":"Y"
                }).then(function (res) {
                    console.log(res)
                    if (res.data.code == 200) {
                        var management_info = res.data.data.management_info;
                        var secretary_company_sp_id = management_info.secretary_company_sp_id && management_info.secretary_company_sp_id.map(item => {
                            return {
                                supplier_id: item.id,
                                company_telephone: item.company_telephone.split(',')
                            }
                        })
                        var agency_company_sp_id = management_info.agency_company_sp_id && management_info.agency_company_sp_id.map(item => {
                            return {
                                supplier_id: item.id,
                                company_telephone: item.company_telephone.split(',')
                            }
                        })
                        _this.form.companyName = management_info.our_company_cd_val;
                        _this.form.companyNameEn = management_info.our_company_en;
                        _this.form.registrationStatus_cd =management_info.company_business_status_cd;
                        _this.form.OaNum = management_info.oa_no;
                        _this.form.selectCountry = management_info.reg_country_id;
                        _this.form.selectProvince =management_info.reg_province_id;
                        _this.form.selectCounty = management_info.reg_city_id;
                        _this.form.address = management_info.reg_address;
                        _this.form.addressEn = management_info.reg_address_en;
                        _this.form.capital_cd = management_info.reg_amount_cd;
                        _this.form.capitalInput = _this.thousandsDeal(management_info.reg_amount);
                        _this.form.remark = management_info.remark;
                        _this.form.representative = management_info.legal_name;
                        _this.form.supervisor = management_info.supervisor_name;
                        _this.form.legal_alias_name = management_info.legal_alias_name;
                        _this.form.supervisor_alias_name = management_info.supervisor_alias_name;
                        _this.form.shareholderList = management_info.shareholder_info;
                        _this.form.company_no = management_info.company_no;
                        _this.form.register_time = management_info.register_time;
                        _this.form.secretary_company_sp_id = secretary_company_sp_id || [{supplier_id: '', company_telephone: []}];
                        _this.form.agency_company_sp_id = agency_company_sp_id || [{supplier_id: '', company_telephone: []}];
                        if(!_this.form.shareholderList){
                            _this.form.shareholderList = [
                                {
                                    "id":"",
                                    "type_cd":"N002960002",
                                    "shareholder_name":"",
                                    "shareholder_name_alias":""
                                }
                            ] 
                        }
                    } else {
                        _this.$message({
                            message: data.msg,
                            type: 'error'
                        });
                    }
                })
            }

        },
        // 区域获取
        areaData: function () {
            var _this = this;
            axios.post('/index.php?g=common&m=index&a=get_area', {
                "parent_no": 0,
                "is_id":''
            }).then(function (res) {
            console.log(res)
              if (res.data.code == 2000) {
                _this.form.country = res.data.data;
              } else {
                _this.$message({
                  message: data.msg,
                  type: 'error'
                });
              }
            })
        },
        // 工商登记状态下拉change
        registrationChangeForm: function (value) {
            // console.log(value)
            value.shareholder_name = ''
            value.shareholder_name_alias = ''
        },
        shareholderAdd: function () {
            var _this = this;
            var obj ={}
            obj.id = ''
            obj.type_cd = 'N002960002'
            obj.shareholder_name = ''
            obj.shareholder_name_alias = ''
            _this.form.shareholderList.push(obj)
        },
        shareholderDel: function (data,index) {
            var _this = this;
            _this.form.shareholderList.splice(index,1);
        },
        // 保存
        submitButton: function () {
            var _this = this
            var capitalInputDeal = this.form.capitalInput.replace(/,/g, "")

            // 传参id
            if(_this.title == '编辑我方公司'){
                var idd = _this.urlIdd
                var shareholderIdd = 'edit'
                var successMessage = '编辑成功'
            }else if(_this.title == '新建我方公司'){ 
                var idd = ''
                var shareholderIdd = ''
                var successMessage = '新建成功'
            }
            // 传参股东
            if(_this.form.shareholderList.length == 1 && _this.form.shareholderList[0].shareholder_name.trim() == ''){
                _this.form.shareholderList = []
            }

            if (!_this.form.company_no) {
                return _this.$message({
                    message: _this.$lang('请输入Company No.'),
                    type: 'warning'
                })
            }

            if (_this.form.secretary_company_sp_id.length > 1) {
                var ary = []
                var aryNull = []
                for(var i = 0; i < _this.form.secretary_company_sp_id.length; i++){
                    ary.push(_this.form.secretary_company_sp_id[i].supplier_id)
                    if(_this.form.secretary_company_sp_id[i].supplier_id == ''){
                        aryNull.push(_this.form.secretary_company_sp_id[i].supplier_id)
                    }
                }
                if((new Set(ary)).size != ary.length && aryNull.length != ary.length){
                    _this.$message({
                        message: _this.$lang('秘书公司不能重复'),
                        type: 'warning'
                    });
                    return;
                }
            }
            if (_this.form.agency_company_sp_id.length > 1) {
                var ary = []
                var aryNull = []
                for(var i = 0; i < _this.form.agency_company_sp_id.length; i++){
                    ary.push(_this.form.agency_company_sp_id[i].supplier_id)
                    if(_this.form.agency_company_sp_id[i].supplier_id == ''){
                        aryNull.push(_this.form.agency_company_sp_id[i].supplier_id)
                    }
                }
                if((new Set(ary)).size != ary.length && aryNull.length != ary.length){
                    _this.$message({
                        message: _this.$lang('代理记账公司不能重复'),
                        type: 'warning'
                    });
                    return;
                }
            }
            // 校验
            if(_this.form.companyName == ''){
                _this.$message({
                    message: _this.$lang('请输入公司名称'),
                    type: 'warning'
                });
            }else if(_this.form.registrationStatus_cd == ''){
                _this.$message({
                    message: _this.$lang('请选择工商登记状态'),
                    type: 'warning'
                }); 
            }else{
                if(_this.form.registrationStatus_cd == 'N002950001'){
                    if(_this.form.OaNum == ''){
                        _this.$message({
                            message: _this.$lang('请输入OA编号'),
                            type: 'warning'
                        }); 
                    }else if(_this.form.selectCountry == ''){
                        _this.$message({
                            message: _this.$lang('请选择注册区域'),
                            type: 'warning'
                        }); 
                    }else if(_this.form.address == ''){
                        _this.$message({
                            message: _this.$lang('请输入注册地址'),
                            type: 'warning'
                        }); 
                    }else if(_this.form.representative == ''){
                        _this.$message({
                            message: _this.$lang('请输入法定代表人/董事/负责人'),
                            type: 'warning'
                        }); 
                    }else {
                        var shareholderList = _this.form.shareholderList
                        var ary = []
                        var aryNull = []
                        for(var i = 0; i < shareholderList.length; i++){
                            ary.push(shareholderList[i].shareholder_name)
                            if(shareholderList[i].shareholder_name == ''){
                                aryNull.push(shareholderList[i].shareholder_name)
                            }
                        }
                        // console.log(aryNull.length)
                        // console.log(ary.length)
                        if((new Set(ary)).size != ary.length && aryNull.length != ary.length){
                            _this.$message({
                                message: _this.$lang('股东名字重复'),
                                type: 'warning'
                            }); 
                        }else{
                            if(aryNull.length == ary.length){
                                _this.form.shareholderList = []
                            }
                            var secretary_company_sp_id = _this.form.secretary_company_sp_id.filter(item => item.supplier_id);
                            var agency_company_sp_id = _this.form.agency_company_sp_id.filter(item => item.supplier_id);
                            axios.post('/index.php?m=company&a=create', {
                                "id":idd,
                                "our_company_name":_this.form.companyName,
                                "our_company_en":_this.form.companyNameEn,
                                "oa_no":_this.form.OaNum,
                                "company_business_status_cd":_this.form.registrationStatus_cd,
                                "reg_country":_this.form.selectCountry,
                                "reg_province":_this.form.selectProvince,
                                "reg_city":_this.form.selectCounty,
                                "legal_name":_this.form.representative,
                                "legal_alias_name":_this.form.legal_alias_name,
                                "supervisor_alias_name":_this.form.supervisor_alias_name,
                                "supervisor_name":_this.form.supervisor,
                                "reg_address":_this.form.address,
                                "reg_address_en":_this.form.addressEn,
                                "remark":_this.form.remark,
                                "reg_amount_cd":_this.form.capital_cd,
                                "reg_amount":capitalInputDeal,
                                "shareholder_info": _this.form.shareholderList,
                                "company_no": _this.form.company_no,
                                "register_time": _this.form.register_time,
                                "secretary_company_sp_id": secretary_company_sp_id.map(item => item.supplier_id).join(','),
                                "agency_company_sp_id": agency_company_sp_id.map(item => item.supplier_id).join(',')
                            }).then(function (res) {
                                // console.log(res.data)
                                if (res.data.code == 200) {
                                    _this.$message({
                                        message: successMessage,
                                        type: 'success'
                                    }); 
                                    var titleTxt = window.parent.document.querySelector('#min_title_list .active').textContent
                                    sessionStorage.setItem("ourCompany", titleTxt);
                                    console.log("新增后传的参数",res.data);

                                    var route = document.createElement("a");
                                    var title = "opennewtab(this,'我方公司详情')";
                                    route.setAttribute("style", "display: none");
                                    route.setAttribute("onclick", title);
                                    route.setAttribute("_href", '/index.php?m=company&a=detail&idd='+res.data.data);
                                    route.onclick();

                                    // closeTab();
                                    // newTab('/index.php?m=company&a=detail&idd='+res.data.data, "我方公司详情");

                                    // backTab('/index.php?m=company&a=detail&idd='+res.data.data, this.$lang("我方公司详情"));

                                }else{
                                    _this.$message({
                                        message: _this.$lang(res.data.msg),
                                        type: 'warning'
                                    }); 
                                }
                            })
                    }
                    }
                }else{
                    var shareholderList = _this.form.shareholderList
                    var ary = []
                    var aryNull = []
                    for(var i = 0; i < shareholderList.length; i++){
                        ary.push(shareholderList[i].shareholder_name)
                        if(shareholderList[i].shareholder_name == ''){
                            aryNull.push(shareholderList[i].shareholder_name)
                        }
                    }
                    console.log(aryNull.length)
                    console.log(ary.length)
                    if((new Set(ary)).size != ary.length && aryNull.length != ary.length){
                        _this.$message({
                            message: '股东名字重复',
                            type: 'warning'
                        }); 
                    }else{
                        if(aryNull.length == ary.length){
                            _this.form.shareholderList = []
                        }
                        var secretary_company_sp_id = _this.form.secretary_company_sp_id.filter(item => item.supplier_id);
                        var agency_company_sp_id = _this.form.agency_company_sp_id.filter(item => item.supplier_id);
                        axios.post('/index.php?m=company&a=create', {
                            "id":idd,
                            "our_company_name":_this.form.companyName,
                            "our_company_en":_this.form.companyNameEn,
                            "oa_no":_this.form.OaNum,
                            "company_business_status_cd":_this.form.registrationStatus_cd,
                            "reg_country":_this.form.selectCountry,
                            "reg_province":_this.form.selectProvince,
                            "reg_city":_this.form.selectCounty,
                            "legal_name":_this.form.representative,
                            "legal_alias_name":_this.form.legal_alias_name,
                            "supervisor_alias_name":_this.form.supervisor_alias_name,
                            "supervisor_name":_this.form.supervisor,
                            "reg_address":_this.form.address,
                            "reg_address_en":_this.form.addressEn,
                            "remark":_this.form.remark,
                            "reg_amount_cd":_this.form.capital_cd,
                            "reg_amount":capitalInputDeal,
                            "shareholder_info": _this.form.shareholderList,
                            "company_no": _this.form.company_no,
                            "register_time": _this.form.register_time,
                            "secretary_company_sp_id": secretary_company_sp_id.map(item => item.supplier_id).join(','),
                            "agency_company_sp_id": agency_company_sp_id.map(item => item.supplier_id).join(',')
                        }).then(function (res) {
                            if (res.data.code == 200) {
                                _this.$message({
                                    message: successMessage,
                                    type: 'success'
                                }); 
                                var titleTxt = window.parent.document.querySelector('#min_title_list .active').textContent
                                sessionStorage.setItem("ourCompany", titleTxt);
                                console.log("编辑传的参数",res.data.data);

                                var route = document.createElement("a");
                                var title = "opennewtab(this,'我方公司详情')";
                                route.setAttribute("style", "display: none");
                                route.setAttribute("onclick", title);
                                route.setAttribute("_href", '/index.php?m=company&a=detail&idd='+res.data.data);
                                route.onclick();

                                // closeTab();

                                // newTab('/index.php?m=company&a=detail&idd='+res.data.data, this.$lang("我方公司详情"));

                            }else{
                                _this.$message({
                                    message: _this.$lang(res.data.msg),
                                    type: 'warning'
                                }); 
            }
                        })
                    }
            


                }
            }
            
            
        },
        // 监听资本输入
        watchcapital: function () {
            this.form.capitalInput=this.form.capitalInput.replace(/[^\.\d]/g,'');
        },
        capitalBlur: function () {
            this.form.capitalInput = this.thousandsDeal(this.form.capitalInput)
            // console.log(this.form.capitalInput)
        },
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
        // 法定代表人blur
        representativeBlur: function () {
            var _this = this
            axios.post('/index.php?m=company&a=getAliasNameByTrueName', {
                "EMP_NM":_this.form.representative
            }).then(function (res) {
                if (res.data.code == 200 && res.data.data != null) {
                    _this.form.legal_alias_name = res.data.data
                }else{
                    _this.form.legal_alias_name = ''
                }
            })
        },
        // 监事blur
        supervisorBlur: function () {
            var _this = this
            axios.post('/index.php?m=company&a=getAliasNameByTrueName', {
                "EMP_NM":_this.form.supervisor
            }).then(function (res) {
                if (res.data.code == 200 && res.data.data != null) {
                    _this.form.supervisor_alias_name = res.data.data
                }else {
                    _this.form.supervisor_alias_name = ''
                }
            })
        },
        // 股东blur
        shareholder_nameBlur: function (item) {
            var _this = this
            axios.post('/index.php?m=company&a=getAliasNameByTrueName', {
                "EMP_NM":item.shareholder_name
            }).then(function (res) {
                if (res.data.code == 200 && res.data.data != null) {
                    item.shareholder_name_alias = res.data.data
                }else {
                    item.shareholder_name_alias = ''
                }
            })
            
        },
        areaChangeCountry:function(){
            // this.areaStatus = true
            this.form.province = []
            this.form.selectProvince = ''
            this.form.county = []
            this.form.selectCounty = ''
    },
        areaChangeProvince:function(){
            // this.areaStatus = true
            this.form.county = []
            this.form.selectCounty = ''
        },
        areaChangeCounty:function(){
            // this.areaStatus = true
        },
        addSecretaryCompany() {
            this.form.secretary_company_sp_id.push({id: '', contact: []})
        },
        delSecretaryCompany() {
            this.form.secretary_company_sp_id.pop();
        },
        addAgentCompany() {
            this.form.agency_company_sp_id.push({id: '', contact: []})
        },
        delAgentCompany() {
            this.form.agency_company_sp_id.pop();
        },
        handleSecretarySelect(supplier_id) {
            this.getContact('secretary');
            console.log(this.form, 'form')
        },
        handleAgentSelect() {
            this.getContact('agent');
        },
        getSecretaryCompany() {
            var params = {
                COPANY_TYPE_CD: 'N001190902'
            }
            var _this = this;
            axios.post('/index.php?m=company&a=getSupplier', params).then(function (res) {
                res.data.data = res.data.data.map(item => {
                    item.company_telephone = item.company_telephone.split(',')
                    return item;
                })
                _this.secretaryCompany = res.data.data;
            })
        },
        getAgentCompany() {
            var params = {
                COPANY_TYPE_CD: 'N001190903'
            }
            var _this = this;
            axios.post('/index.php?m=company&a=getSupplier', params).then(function (res) {
                res.data.data = res.data.data.map(item => {
                    item.company_telephone = item.company_telephone.split(',')
                    return item;
                })
                _this.agentCompany = res.data.data;
            })
        },
        // 根据类型以及选择的公司获取联系方式
        getContact(type) {
            var _this = this;
            // 获取秘书类型公司联系方式
            if (type === 'secretary') {
                _this.secretaryCompany.forEach(function (item) {
                    _this.form.secretary_company_sp_id.forEach(function (secretary) {
                        if (item.supplier_id === secretary.supplier_id) {
                            secretary.company_telephone = item.company_telephone;
                        }
                    })
                })
            }
            if (type === 'agent') {
                _this.agentCompany.forEach(function (item) {
                    _this.form.agency_company_sp_id.forEach(function (agent) {
                        if (item.supplier_id === agent.supplier_id) {
                            agent.company_telephone = item.company_telephone;
                        }
                    })
                })
            }
        },
    },
    computed: {
        selectCountry() {
            return this.form.selectCountry
        },
        selectProvince() {
            return this.form.selectProvince
        },
        shareholderList() {
            return this.form.shareholderList
        },
        registrationStatus_cd() {
            return this.form.registrationStatus_cd
        }
    },
    watch: {
        selectCountry: {
            handler(newValue, oldValue) {
                var _this = this
                // console.log(newValue)
                    // _this.form.province = []
                    // _this.form.selectProvince = ''
                    // _this.form.county = []
                    // _this.form.selectCounty = ''
                    // for(var i=0;i<_this.form.country.length;i++){
                    //     if(_this.form.country[i].id == newValue){
                    //         var area_no = _this.form.country[i].area_no
                    //     }
                    // }
                    // console.log(area_no)
                axios.post('/index.php?g=common&m=index&a=get_area', {
                        parent_no: newValue,
                        is_id:'Y'
                }).then(function (res) {
                //   console.log(res)
                  if (res.data.code == 2000) {
                    _this.form.province = res.data.data;
                        // console.log('11')
                        // console.log(_this.form.province)
                  } else {
                    _this.$message({
                      message: data.msg,
                      type: 'error'
                    });
                  }
                })
                    // console.log(_this.form.province)
                    _this.areaStatus = true  
            },
            deep: true
        },
        selectProvince: {
            handler(newValue, oldValue) {
                var _this = this
                // console.log(newValue)
                // console.log(_this.form.province)
                    // _this.form.county = []
                    // _this.form.selectCounty = ''
                    // for(var i=0;i<_this.form.province.length;i++){
                    //     if(_this.form.province[i].id == newValue){
                    //         var area_no = _this.form.province[i].area_no
                    //     }
                    // }
                    // console.log(area_no)
                axios.post('/index.php?g=common&m=index&a=get_area', {
                        parent_no: newValue,
                        is_id:'Y'
                }).then(function (res) {
                //   console.log(res)
                  if (res.data.code == 2000) {
                    _this.form.county = res.data.data;
                  } else {
                    _this.$message({
                      message: data.msg,
                      type: 'error'
                    });
                  }
                })
                    _this.areaStatus = true  

            },
            deep: true
        },

        shareholderList: {
            handler(newValue, oldValue) {
                if(newValue == null || newValue.length == 0){
                    this.form.shareholderList = [
                        {
                            "id":"",
                            "type_cd":"N002960002",
                            "shareholder_name":""
                        }
                    ]
                }
            },
            deep: true
        },
        registrationStatus_cd: {
            handler(newValue, oldValue) {
                if(newValue == 'N002950001'){
                   this.required = true 
                }else{
                    this.required = false
                }
            },
            deep: true
        },
    },
})

