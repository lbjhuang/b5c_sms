if(getCookie('think_language') !== "zh-cn" ){
    ELEMENT.locale(ELEMENT.lang.en)
}
var Vm = new Vue({
    el: '#logistics',
    data:{
        companyData:'',
        real_logistics_companyData:'',
        surfaceWay:[],
        chooseData:[],
        DictionaryList:'',
        basisData: [],
        form: {
            startTime: '',
            endTime: '',
            companyType: 'mode',
            agentCompany: '',
            logistiosCompanyValue: []
        },
        page:{
            sePage:1,
            pageSize:10,
            pageLength:0,
            pageShow:'none',
            displayLength:0,
            currentPage: 1
        },
        latelyPagesNum:1,
        goLength:[],
        Popradio:"",
        logisticsState: "1",
        Popstatus:"",
        PopModefiedId:"",
        TotalPageSize:0,
        surfaceWay_chose:[],
        pop_logistics_company:'',
        real_logistics_company:'',
        real_logistics_company_id:'',
        addData: {},
        accountName: '',
        logisticsWayData: [],
        warehouseData: {},
        logisticsCompany: [],
        warehouses: {},
        forwarderCompany: [],
        buttItemData: [],
        totalCount: 0,
        accountName: '',
        tableLoading: false,
        pop_logistics_mode:'',
        pop_service_code:'',
        logisticsCompanyType: [{
            id: '0',
            val: '平台自有物流'
        },{
            id:'1',
            val: '物流公司物流'
        }],
        companyTypeData: [{
            id: 'mode',
            val: '物流方式'
        },{
            id:'serviceCode',
            val: '服务代码'
        }],
        isShow: false,
        dialogVisible: false
    },
    //页面渲染之前，先取码表数据
    beforeCreate:function () {
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00068,N00083,N00082,N00070,N00041,N00201';//仓库,销售渠道,物流类别, 物流公司,产地;
        axios.get(getDictionaryListUrl)
            .then(function(res) {
                Vm.DictionaryList=res.data.data;
                var companyData=[],DictionaryListcompany=res.data.data['N00070'];
                var surfaceWay =[],  DictionarysurfaceWay = res.data.data['N00201'];
                for(key in DictionaryListcompany){
                    var companyObj={};
                    companyObj.code = DictionaryListcompany[key].CD;
                    companyObj.val = DictionaryListcompany[key].CD_VAL;
                    companyData.push(companyObj)
                }
                for(key in DictionarysurfaceWay){
                    var surfaceWayObj={};
                    surfaceWayObj.value = DictionarysurfaceWay[key].CD;
                    surfaceWayObj.CD_VAL = DictionarysurfaceWay[key].CD_VAL;
                    surfaceWay.push(surfaceWayObj)
                }
                Vm.companyData =companyData;
                Vm.surfaceWay =surfaceWay;
                Vm.chooseData = companyData;
        })

        axios.get('/index.php?g=logistics&m=realLgtCompany&a=getComNameKeyValue').then(function(res){
            // console.log(res.data.data)
            var getComNameKeyValue = res.data.data
            var getComNameData =[]
            // for(key in getComNameKeyValue){
            //     var getComName={};
            //     getComName.value = getComNameKeyValue[key].key;
            //     getComNameData.push(getComName)
            // }

            for (var prop in getComNameKeyValue) {
                var getComName={};
                getComName.idd = prop;
                getComName.val = getComNameKeyValue[prop];
                getComNameData.push(getComName)
            }


            Vm.real_logistics_companyData =getComNameData;
            // console.log(getComNameData)
        })
    },
    created :function(){
        setTimeout(function () {
            Vm.basisDataSearch();
        },10)
        this.getWarehouseCompanyData();
    },
    
    mounted:function(){
        $("#logistics .logistics_table_record").on("keyup",'.el-pagination__jump .el-pagination__editor',function () {
            var amount = parseFloat($(this).val());
            if (event.key !== 'Backspace' && isNaN(event.key) && event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'ArrowRight' && event.key !== 'ArrowLeft'&& event.key !== 'Enter') {
                $(this).val('')
                _this.$message({
                    message: this.$lang('输入页码有误，请检查后重新输入'),
                    type: 'error'
                })
                return false;
            }
            if(amount ==0|| amount > Vm.TotalPageSize){
                 _this.$message({
                    message: this.$lang('输入页码有误，请检查后重新输'),
                    type: 'error'
                })
                $(this).val('')
                return false;
            }
        })
        // 日期右侧图标点击加载日历插件/
        $(".common_data .input-group-btn button").click(function () {
            $(this).parents(".common_data").find("input").focus()
        })
    },
    methods:{
        getWarehouseCompanyData: function getWarehouseCompanyData () {
            var _this = this;
            axios.post("/index.php?g=oms&m=CommonData&a=commonData",{
                "data": {
                    "query": {
                        "company": "true",
                        "logisticsCompany": "true",
                        "warehouses": "true",
                        "surfaceWayGet": "true"
                    }
                }
            }).then(function (res) {
                if (res.data.code == 2000) {
                    _this.logisticsCompany = res.data.data.logisticsCompany;
                    _this.warehouses = res.data.data.warehouses;
                    _this.forwarderCompany = res.data.data.company;
                    _this.buttItemData = res.data.data.surfaceWayGet;
                }
            })
        },
        handleSelectionChange: function handleSelectionChange () {

        },
        getLogisticeWayData: function getLogisticeWayData () {
            var _this = this;
            var url = 'index.php?g=logistics&m=configs&a=showLogisticsAccountInfo';
            axios.post(url, {'logistios_company_cd': this.warehouseData.logistics_code})
            .then(function (res) {
                if (res.data.code == 2000) {
                    _this.addData = res.data.data;
                } else {
                    _this.addData = res.data.data;
                }
            })
        },
        logisticsCompanyChange: function (value) {
            Vm.accountName = "";
            this.warehouseData.logistics_code = value;
            this.getLogisticeWayData();
        },
        real_logistics_company_change: function (value) {
            // console.log(value)
            this.real_logistics_company_id = value;
        },
        getWarehouseData: function getWarehouseData () {
            this.basisDataSearch();
        },
        //列表页，搜索条件处，物流公司、物流方式以及服务代码下拉框切换相关
        changeSelect:function(){
            // var moreSearch = $("#moreSearch").val();
            var moreSearch = this.form.logistiosCompanyValue.join(",");
            // var url='/index.php?g=logistics&m=configs&a=logisSelectData&moreSearch='+moreSearch;
            var url='/index.php?g=logistics&m=configs&a=logisSelectData&moreSearch='+moreSearch;
            axios.get(url)
                .then(function(res){
                    if (moreSearch=='company') {
                         var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00070';//面单获取方式
                         axios.get(getDictionaryListUrl)
                        .then(function(res) {
                            Vm.DictionaryList=res.data.data;
                            var companyData=[],DictionaryListcompany=res.data.data['N00070'];
                            for(key in DictionaryListcompany){
                                var companyObj={};
                                companyObj.code = DictionaryListcompany[key].CD;
                                companyObj.val = DictionaryListcompany[key].CD_VAL;
                                companyData.push(companyObj)
                            }
                            Vm.companyData =companyData;
                            Vm.chooseData = companyData;
                        })
                    }else{
                        //$("#logisticsMode").val('');
                        Vm.chooseData = res.data.data;    
                    }
                    
                })
        },
         //切换每页展示的数目
        handleSizeChange:function (val) {
            Vm.page.currentPage = 1;
            this.page.pageSize = val;
            this.basisDataSearch();
        },
        //翻页切换不同页面
        handleCurrentChange:function(val) {
            Vm.page.currentPage = val;
            Vm.basisDataSearch(val)
        },
        //所有取消按钮
        cancel:function () {
            this.dialogVisible = false;
            for (var val in this.addData) {
                if (val === this.warehouseData.logistics_account_info_id) {
                    Vm.accountName =  this.warehouseData.logistics_account_info_id;
                } else {
                    Vm.accountName = "";
                }
            }
        },
        //增加基础信息
        add:function (state,data) {
            Vm.accountName = '';
            this.dialogVisible = true;
            var _this = this;
            if (!data) {
                data = {};
                Vue.set(data, 'account_name', '');
            }
            $(".logistics_pop").fadeIn(200)
            if(state == "add"){
                Vm.Popstatus = "add";
                Vm.pop_logistics_company = '';
                Vm.real_logistics_company = '';
                Vm.pop_logistics_mode = '',
                Vm.pop_service_code = '',
                Vm.surfaceWay_chose =[];
                Vm.accountName = '';
                Vm.Popradio = true;
                Vm.logisticsState = '0';
                Vm.real_logistics_company_id = '';
                Vm.real_logistics_company = '';
            }else if(state == "modefied"){
                Vm.Popstatus = "modefied";
                this.warehouseData = data;
                Vm.pop_logistics_mode = data.logistics_mode;
                Vm.pop_service_code = data.service_code;
                Vm.PopModefiedId = data.id
                this.Popradio = data.is_enable;
                Vm.surfaceWay_chose = data.SURFACE_WAY_GET_CD;
                Vm.pop_logistics_company = data.logistics_code;
                Vm.real_logistics_company = data.real_logistics_company_name;
                if(data.real_logistics_company_id == 0){
                    Vm.real_logistics_company_id = '';
                }else{
                    Vm.real_logistics_company_id = data.real_logistics_company_id;
                }
                // Vm.real_logistics_company_id = data.real_logistics_company_id;
                Vm.Popradio = data.is_enable;
                console.log(data)
                if (data.is_enable == "0") {
                    this.Popradio = false;
                } else {
                    this.Popradio = true;
                }
                setTimeout(function () {
                   for (var val in _this.addData) {
                        if (val == +data.logistics_account_info_id) {
                            Vm.accountName = data.logistics_account_info_id;
                        }
                    }
                }, 700)
                Vm.logisticsState = data.need_gift;
            }
            Vm.getLogisticeWayData();
        },
        refresh:function (index,val) {
            var _this = this
            _this.$set(_this.basisData[index], 'isdisabled', true)
            console.log(val);
            axios.post("/index.php?g=logistics&m=configs&a=resetLogisticsSearchCount",{
                "logistics_mode_id": Number(val.id)
            }).then(function (res) {
                console.log(res);
                if (res.data.code == 200) {
                    if(res.data.data){
                        _this.$message({
                            message: _this.$lang('本次刷新成功条数：')+res.data.data.current_reset_count+_this.$lang('条，共')+res.data.data.total_reset_count+_this.$lang('条 还剩余')+res.data.data.over_reset_count+_this.$lang('条'),
                            type: 'success'
                        })
                    }else{
                        _this.$message({
                            message: _this.$lang('本次刷新成功条数：0条 共0条 还剩余0条'),
                            type: 'success'
                        })
                    }

                    setTimeout(() => {
                        _this.$set(_this.basisData[index], 'isdisabled', false)
                    }, 3000);
                }else{
                    _this.$message({
                        message: _this.$lang(res.data.msg),
                        type: 'error'
                    })
                    _this.$set(_this.basisData[index], 'isdisabled', false)
                }
            })


        },
        //增加基础信息后保存数据
        basisSubmitButton:function () {
            var _this = this;
            this.dialogVisible = false;
            var logistics_company=Vm.pop_logistics_company.trim(),
                real_logistics_company_id=Vm.real_logistics_company_id.trim(),
                logistics_mode=Vm.pop_logistics_mode.trim(),
                service_code=Vm.pop_service_code.trim(),
                isAble;
                if (Vm.Popradio == false) {
                    isAble = 0;
                } else {
                    isAble = 1;
                }
                isNeedGift = Vm.logisticsState;
                accountName =  Vm.accountName;

            
                if(logistics_company && real_logistics_company_id && logistics_mode && service_code && accountName !== '' &&　isNeedGift && Vm.surfaceWay_chose.length > 0){
                    var  params = "logisticsCode="+logistics_company+"&real_logistics_company_id="+real_logistics_company_id+"&logistics_account_info_id="+accountName+"&logisticsMode="+logistics_mode+"&need_gift="+isNeedGift+"&is_enable="+isAble+"&serviceCode="+service_code+"&surfaceWay_chose="+Vm.surfaceWay_chose;
                    var　basisUrl = ""; 
                    if(Vm.Popstatus == "add"){
                        basisUrl='/index.php?g=logistics&m=configs&a=createLogisticsMode';
                    }else if(Vm.Popstatus == "modefied"){
                        basisUrl='/index.php?g=logistics&m=configs&a=updateLogisticsMode';
                        params +="&id="+Vm.PopModefiedId;
                    }
                    // console.log(params)
                    axios.post(basisUrl,params)
                        .then(function (res) {
                            if(res.data.code == '200'){
                               _this.$message({
                                    message: _this.$lang('添加成功'),
                                    type: 'success'
                                })
                                Vm.basisDataSearch()
                            }else{
                                 _this.$message({
                                    message: _this.$lang(res.data.msg),
                                    type: 'error'
                                })
                            }
                        })
                }else{
                    _this.$message({
                        message: _this.$lang('请将数据填写完整'),
                        type: 'error'
                    })
                }
        },
        accountNameChange: function (value) {
            Vm.accountName = value;
        },
        //搜索功能
        basisDataSearch:function (num) {
            this.tableLoading = true;
            var startTime=this.form.startTime,endTime=this.form.endTime,companyType=this.form.companyType;
            var warehouses = this.form.logistiosCompanyValue.join(",")
            var agentCompany = this.form.agentCompany;
            var url='/index.php?g=logistics&m=configs&a=searchMode&pageSize=' + this.page.pageSize;
            if(startTime){
                url = url + '&startTime='+startTime;
            }
            if(endTime){
                url = url + '&endTime='+endTime;
            }
            if(warehouses){
                url = url + '&logisticsCompanyCode='+warehouses;
            }
            if (companyType) {
                url = url + '&moreSearch='+companyType;
            }
            if (agentCompany) {
                url = url + '&logisticsCode='+agentCompany;
            }
            url = url + '&page=' +  (num || 1);
            axios.get(url)
                .then(function(res){
                    console.log(res)
                    var data=res.data.data.list;
                    var basisDataArry=[];
                    Vm.totalCount = +res.data.data.total;
                    for(key in data){
                       var basisDataObj={};
                        basisDataObj.create_time = data[key].create_time;
                        basisDataObj.id = data[key].id;
                        basisDataObj.logistics_code = data[key].logistics_code;
                        basisDataObj.logistics_company = data[key].logistics_company;
                        basisDataObj.logistics_mode = data[key].logistics_mode;
                        basisDataObj.remark = data[key].remark;
                        basisDataObj.is_enable = data[key].is_enable;
                        basisDataObj.service_code = data[key].service_code;
                        basisDataObj.update_time = data[key].update_time;
                        basisDataObj.creator = data[key].creator;
                        basisDataObj.real_logistics_company_id = data[key].real_logistics_company_id;
                        // if(data[key].real_logistics_company_id == 0){
                        //     basisDataObj.real_logistics_company_id = '';
                        // }else{
                        //     basisDataObj.real_logistics_company_id = data[key].real_logistics_company_id;
                        // }
                        basisDataObj.real_logistics_company_name = data[key].real_logistics_company_name;
                        basisDataObj.need_gift = data[key].need_gift;
                        basisDataObj.account_name = data[key].account_name;
                        basisDataObj.logistics_account_info_id = data[key].logistics_account_info_id;
                        if (data[key].POSTAGE_ID) {
                            basisDataObj.POSTAGE_ID = data[key].POSTAGE_ID.split(",");
                        }
                        if(data[key].SURFACE_WAY_GET_NAME){
                            basisDataObj.SURFACE_WAY_GET_NAME =data[key].SURFACE_WAY_GET_NAME.split(',')
                        }else{
                            basisDataObj.SURFACE_WAY_GET_NAME =[];
                        }
                        if(data[key].SURFACE_WAY_GET_CD){
                            basisDataObj.SURFACE_WAY_GET_CD = data[key].SURFACE_WAY_GET_CD.split(',')
                        }else{
                            basisDataObj.SURFACE_WAY_GET_CD=[];
                        }
                        basisDataArry.push(basisDataObj);
                    }
                    Vm.basisData = basisDataArry;
                    // Vm.basisData.forEach(function (item) {
                    //     Vue.set(item, "accout", "")
                    // })
                    Vm.page.sePage= Number(res.data.data.page);
                    //Vm.page.pageSize = Number(res.data.data.pageSize)
                    Vm.page.pageLength=Number(res.data.data.total);
                   // Vm.page.pageSize=Number(res.data.data.rows);
                    Vm.page.displayLength = basisDataArry.length;
                    if (res.data.data.list !== null) {
                        var dataList = res.data.data.list.length;
                    }
                    var dataListTotal =  Number(res.data.data.total)
                    var totalPage = Math.ceil(dataListTotal/dataList)
                    Vm.TotalPageSize = totalPage;
                    Vm.tableLoading = false;
                })
        },
        //删除功能
        // deleteData:function () {
        //     var idArry=[],idStr='';
        //     $(".logistics_table .table .use-body tr .use_body_checkbox input").each(function () {
        //         if($(this).prop('checked')){
        //             var id = $(this).attr('data-id');
        //             idArry.push(id);
        //         }
        //     })
        //     var _this = this;
        //     idStr = idArry.join(",");
        //     var url='/index.php?g=logistics&m=configs&a=deleteLogisticsMode'
        //     if(idStr){
        //         var params = '&id='+idStr;
        //         this.$confirm('确认删除此条信息？', '提示', {type: 'warning'})
        //             .then(function () {
        //                 axios.post(url, params)
        //                 .then(function (res) {
        //                     if(res.data.code == '200'){
        //                         _this.$message({
        //                             message: "删除成功",
        //                             type: 'success'
        //                         })
        //                         Vm.basisDataSearch( Vm.latelyPagesNum);
        //                     }
        //                     location.reload()
        //                 })
        //             })
        //     }
        // },
        showTips: function (item) {
            Vue.set(item, "isShow", !item.isShow)
        },
        //excel导出功能
        logisticsXlExport:function () {
            var startTime=this.form.startTime,endTime=this.form.endTime,companyType=this.form.companyType;
            var warehouses = this.form.logistiosCompanyValue.join(",")
            var agentCompany = this.form.agentCompany;
            var url='/index.php?g=logistics&m=configs&a=exportMode', params='';
            if(startTime){
                url = url + '&startTime='+this.form.startTime;
            }
            if(endTime){
                url = url + '&endTime='+this.form.endTime;
            }
            if(warehouses){
                url = url + '&logisticsCompanyCode='+this.form.logistiosCompanyValue.join(",");
            }
            if (companyType) {
                url = url + '&moreSearch='+this.form.companyType;
            }
            if (agentCompany) {
                url = url + '&logisticsCode='+this.form.agentCompany;
            }
            window.location.href=url
        },
        // 运费模板导出
        freightTemplateExport:function(){
            var _this = this;

            var startTime=_this.form.startTime;
            var endTime=_this.form.endTime;
            var moreSearch=_this.form.companyType;
            var logisticsCompanyCode = _this.form.logistiosCompanyValue.join(",")
            var logisticsCode = _this.form.agentCompany;

            // console.log(startTime);
            // console.log(endTime);
            // console.log(moreSearch);
            // console.log(logisticsCompanyCode);
            // console.log(logisticsCode);

            var link = document.createElement('a');
            var url = "/index.php?g=logistics&m=configs&a=logisticsModeExport&logisticsCompanyCode=" + logisticsCompanyCode + "&moreSearch=" + moreSearch + "&logisticsCode=" + logisticsCode + "&startTime=" + startTime + "&endTime=" + endTime;

            // http://localhost/index.php?g=logistics&m=configs&a=logisticsModeExport&logisticsCompanyCode=N000707200&moreSearch=mode&logisticsCode=%E7%87%95%E6%96%87%E8%88%AA%E7%A9%BA%E7%BB%8F%E6%B5%8E%E5%B0%8F%E5%8C%85-%E6%99%AE%E8%B4%A7-%E4%B8%8A%E6%B5%B7%E6%8F%BD%E6%94%B6&startTime=2020-06-01&endTime=2020-06-30

            var body = document.querySelector('body')
            link.href = url
            link.style.display = 'none'
            body.appendChild(link)
            console.log(link);
            
            link.click()
            body.removeChild(link)

            

            // var param = {
            //   deduction_currency_cd: _this.form.search.deduction_currency_cd,
            //   our_company_cd: _this.form.search.our_company_cd,
            //   supplier_id: _this.form.search.supplier_id,
            //   type: type
            // }

            // var tmep = document.createElement('form');
            // tmep.action = '/index.php?m=order_detail&a=deduction_sup_export';
            // tmep.method = "post";
            // tmep.style.display = "none";
            // var opt = document.createElement("input");
            // opt.name = 'export_params';
            // opt.value = JSON.stringify(param);
            // tmep.appendChild(opt);
            // document.body.appendChild(tmep);
            // tmep.submit();
            // $(tmep).remove();
            // tmep = null
        },
        logisticsXlImport:function(){
            document.getElementById('activeImport').click();
        },
        /*前往运费模板*/
        jumpFre:function(id, title){
            var trackurl = "/index.php?g=logistics&m=FreightRules&a=rule_list&logModeId="+id;
            var route = document.createElement("a");
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", "opennewtab(this,'" + this.$lang(title) +  "')");
            route.setAttribute("_href", trackurl);
            route.click();
        },
        reset: function () {
            this.form = {
                startTime: '',
                endTime: '',
                companyType: 'mode',
                agentCompany: '',
                logistiosCompanyValue: []
            }
              this.companyType = 'mode',
            this.basisDataSearch();
        },
        dateChange: function dateChange (value) {
            this.form.startTime = value;
            this.basisDataSearch();
        },
        endTimeChange: function (value) {
            this.form.endTime = value;
            this.basisDataSearch();
        },
        agentCompanyBlur:function(){
            this.basisDataSearch();
        }
    }
});