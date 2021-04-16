var Vm = new Vue({
    el: '#logistics',
    data:{
        tableData:[],
        tableDataLength:0,
         popData:[{}],
        warehouseData:'',
        placeData:'',
        companyData:'',
        typeData:'',
        typeDataTip:'',
        typeDataTipState:false,
        channelData:'',
        DictionaryList:'',
        WindowHrefCn:true,
        WindowHrefEn:false,
        page:{
            sePage:1,
            pageSize:20,
            pageLength:0,
            pageShow:'none',
            displayLength:0,
        },
        latelyPagesNum:1,
    },
    beforeCreate:function () {
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00068,N00083,N00082,N00070,N00041';//仓库,销售渠道,物流类别, 物流公司,产地
        axios.get(getDictionaryListUrl)
            .then(function(res) {
                Vm.DictionaryList=res.data.data;
            })
        var getOriginPlace='/index.php?g=universal&m=area&a=getCountries';
        axios.get(getOriginPlace)
            .then(function(res) {
                var data = res.data.data;
                var dataArr=[];
                for(key in data){
                    var dataObj={};
                    dataObj.id=data[key].id;
                    dataObj.en_name=data[key].en_name;
                    dataObj.zh_name=data[key].zh_name;
                    dataArr.push(dataObj);
                }
                Vm.placeData=dataArr;
               var WindowHref = $('#iframeLang').attr('data-msg');
               console.info(WindowHref)
                if(WindowHref=="en-us"){
                    Vm.WindowHrefCn=false;
                    Vm.WindowHrefEn =true;
                }
                else if(WindowHref=="zh-cn"){
                    Vm.WindowHrefCn=true;
                    Vm.WindowHrefEn =false;
                }
            })
    },
    created :function(){
        setTimeout(function () {
            Vm.logisticsDispatchSearch();
        },10)
    },
    mounted:function () {
        // 日期右侧图标点击加载日历插件/
        $(".common_data .input-group-btn button").click(function () {
            $(this).parents(".common_data").find("input").focus()
        })
    },
    methods:{
        //翻页跳转
        handleCurrentChange:function(val) {
            Vm.latelyPagesNum = val;
            this.logisticsDispatchSearch(val)
            var checkBox = document.querySelectorAll('.checkboxItems');
            $(".checkboxAllItems").prop('checked',false);
            for(key in checkBox){
                checkBox[key].checked=false
            }
        },
        //操作提示弹框
        confirmExport:function () {
            if(!this.exportCheckList.length){
                this.$message({
                    message: '请至少勾选一个',
                    type: 'warning'
                });
                return;
            }
            // ids
            var idsLen = this.data.length;
            var ids = [];
            for(var i=0;i<idsLen;i++){
                if (this.data[i].checked) {
                    ids.push(this.data[i].EMPL_ID);
                }
            }
            // make info
            var url = '/index.php?m=Api&a=export_emp';
            var form1 = document.createElement('form');
            form1.setAttribute('style','display:none');
            form1.setAttribute('target','');
            form1.setAttribute('method','post');
            form1.setAttribute('action',url);
            // 创建一个输入
            var input1 = document.createElement("input");
            input1.type = "text";
            input1.name = "need_info";
            input1.value = JSON.stringify(this.exportCheckList);
            form1.appendChild(input1);
            // 创建一个输入
            var input2 = document.createElement("input");
            input2.type = "text";
            input2.name = "EMPL_ID";
            input2.value = ids.join(',');
            form1.appendChild(input2);
            document.body.appendChild(form1);
            form1.submit();
            form1.remove();
        },
        // 删除
        deleteDispatch:function (){
            var idArry=[],ruleNameArry=[],idStr='',ruleNameStr='',index=0;
            $(".logistics_table .table .use-body .checkbox").each(function () {
                if($(this).prop("checked")){
                    index++;
                    var id = $(this).attr('data-value');
                    var ruleNamedata = $(this).parents("tr").find('.TDruleName').html();
                    idArry.push(id);ruleNameArry.push(ruleNamedata);
                }
            })
            if(index < 31){
                idStr = idArry.join(","),ruleNameStr=idArry.join(",");
                var url='/index.php?g=logistics&m=configs&a=deleteRules';
                if(idStr){
                    url +='&id='+idStr;
                    this.$confirm('确认删除此条信息？', '提示', {type: 'warning'})
                        .then(function () {
                            axios.get(url)
                                .then(function (res) {
                                    if(res.data.code == '200'){
                                        // console.info(res.data.code,res)
                                        layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>删除成功</span>");
                                        $(".logistics_pop").hide();
                                        $(".logistics_table .table .use-body .checkbox").prop("checked",false)
                                        Vm.logisticsDispatchSearch(Vm.latelyPagesNum);
                                    }
                                })
                        })
                }
            }else {
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>最多只能删除30个</span>");
            }
        },
        // 取消
        cancel:function () {
            $(".logistics_pop").hide()
        },
        // 新增 弹框
        add:function () {
            $(".logistics_pop").show();
            $(".logistics_pop_content_dispatch input,.logistics_pop_content_dispatch select").val('')
            $(".logistics_pop_body_channel span").attr('data-msg',0).removeClass('active')
            // console.info(Vm.DictionaryList['N00068'])
            var warehouseData=[],DictionaryListWarehouse=Vm.DictionaryList['N00068'];
            // var placeData=[],placeObj=[],DictionaryListPlace=Vm.DictionaryList['N00041'];
            var companyData=[],DictionaryListcompany=Vm.DictionaryList['N00070'];
            var channelData=[],DictionaryListChannel=Vm.DictionaryList['N00083'];
            for(key in DictionaryListWarehouse){
                var warehouseObj={};
                warehouseObj.code = DictionaryListWarehouse[key].CD;
                warehouseObj.val = DictionaryListWarehouse[key].CD_VAL;
                warehouseData.push(warehouseObj)
            }
            // for(key in DictionaryListPlace){
            //     var placeObj={};
            //     placeObj.code = DictionaryListPlace[key].CD;
            //     placeObj.val = DictionaryListPlace[key].CD_VAL;
            //     placeData.push(placeObj)
            // }
            for(key in DictionaryListcompany){
                var companyObj={};
                companyObj.code = DictionaryListcompany[key].CD;
                companyObj.val = DictionaryListcompany[key].CD_VAL;
                companyData.push(companyObj)
            }
            for(key in DictionaryListChannel){
                var channelObj={};
                channelObj.code = DictionaryListChannel[key].CD;
                channelObj.val = DictionaryListChannel[key].CD_VAL;
                channelData.push(channelObj)
            }

            Vm.warehouseData =warehouseData;
            // Vm.placeData =placeData;
            Vm.companyData =companyData;
            Vm.channelData =channelData;
        },
        // 渠道选择
        channelChose:function(){
            var target= event.target;
            if(hasClass(target,'active')){
                removeClass(target, 'active')
                target.setAttribute('data-msg','0')
            }else{
                addClass(target,'active');
                target.setAttribute('data-msg','1')
            }
        },
        //启用和禁用操作
        Isable:function (a) {
            var event=window.event||event;
            var url='/index.php?g=logistics&m=configs&a=updateRule';
            if(a.ruleName && a.saleChannel && a.warehouse && a.LOGISTICS_CODE && a.destnCountry && a.LOGISTICS_MODE && a.id){
                url +='&id='+a.id+ '&ruleName='+a.ruleName+'&platforms='+a.saleChannel+'&warehouse='+a.warehouse+'&logisticsCode='+a.LOGISTICS_CODE+'&destnCountry='+a.destnCountry+'&logisticsMode='+a.LOGISTICS_MODE;
                if(event.target.value){
                    url += '&isEnable=' + Number(event.target.value);
                    axios.get(url)
                        .then(function (res) {
                            if(res.data.code == '200'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>操作成功</span>");
                                Vm.logisticsDispatchSearch(Vm.latelyPagesNum);
                            }
                            else if(res.data.code == '40004'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>无效的参数，请检查参数正确性</span>");
                            }
                        })
                }
            }else{
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>数据有误</span>");
            }
        },
        //派单规则列表页搜索
        logisticsDispatchSearch:function (num) {
            var startTime=$("#startTime").val(),endTime=$("#endTime").val(),ruleName=$(".ruleName").val().trim();
            var url='/index.php?g=logistics&m=configs&a=searchRules';
             if(startTime){
                 url = url + '&startTime='+startTime;
             }
             if(endTime){
                 url = url + '&endTime='+endTime;
             }
            if(ruleName){
                url = url + '&ruleName='+ruleName;
            }
            if(num){
                url = url + '&page='+num;
            }else{
                url = url + '&page=1';
            }
            axios.get(url)
                .then(function(res) {
                    Vm.tableData=[];
                    var data=res.data.data.list;
                    console.info(res)
                    for(key in data){
                       var tableDataOj={};
                        tableDataOj.allCode={
                                id:data[key].id,
                                ruleName:data[key].ruleName,
                                LOGISTICS_CODE:data[key].logisticsCode,
                                LOGISTICS_MODE:data[key].logisticsMode,
                                warehouse:data[key].warehouse,
                                destnCountry:data[key].destnCountry,
                                isEnable:data[key].isEnable,
                                saleChannel:data[key].saleChannel
                            }
                            tableDataOj.id=data[key].id;
                            tableDataOj.channelName=data[key].channelName;
                            tableDataOj.createTime=data[key].createTime;
                            tableDataOj.creator=data[key].creator;
                            tableDataOj.destnCity=data[key].destnCity;
                            tableDataOj.destnCountry=data[key].destnCountry;
                            tableDataOj.destnCountryName=data[key].destnCountryName;
                            if(tableDataOj.destnCity){}
                            else{tableDataOj.destnCity=data[key].destnCountryName;}
                            tableDataOj.isDelete=data[key].isDelete;
                            tableDataOj.isEnable=data[key].isEnable;
                            tableDataOj.logisticsCode=data[key].logisticsCode;
                            tableDataOj.logisticsMode=data[key].logisticsMode;
                            tableDataOj.logisticsModeName=data[key].logisticsModeName;
                            tableDataOj.remark=data[key].remark;
                            tableDataOj.ruleName=data[key].ruleName;
                            tableDataOj.saleChannel=data[key].saleChannel;
                            tableDataOj.updateTime=data[key].updateTime;
                            tableDataOj.warehouse=data[key].warehouse;
                            tableDataOj.warehouseName=data[key].warehouseName;
                            tableDataOj.logisticsCompany = data[key].logisticsCompany;
                            Vm.tableData.push(tableDataOj);
                    }
                    Vm.page.sePage= Number(res.data.data.page);
                    Vm.page.pageLength=Number(res.data.data.total);
                    Vm.page.pageSize=Number(res.data.data.rows)
                    Vm.page.displayLength = Vm.tableData.length;
                })
        },
        //增加 派单规则
        SubmitButton:function () {
            var ruleName='',platforms='',warehouse='',logisticsCode='',destnCountry='',logisticsMode='';
            ruleName=$('#ruleName').val().trim();logisticsMode=$('#logisticsMode').val().trim();warehouse=$('#warehouse').val().trim();
            logisticsCode=$('#logisticsCode').val().trim();destnCountry=$('#destnCountry').val().trim();
            var platformsArry=[],platformsStr='';
            $("#platforms span").each(function () {
               var  data_msg = $(this).attr('data-msg');
               if(data_msg == '1'){
                   var data_val=$(this).attr('data-value')
                   platformsArry.push(data_val);
               }
            })
           if(platformsArry){
               platformsStr = platformsArry.toString()
           }
            if(logisticsCode && ruleName && logisticsMode && warehouse && destnCountry && platformsStr){
               // console.info(expressCode,ruleName,expressType,Warehouse,destnCountry,platformsStr)
                var url='/index.php?g=logistics&m=configs&a=createRules';
                url += "&ruleName="+ruleName+"&platforms="+platformsStr+"&warehouse="+warehouse+"&logisticsCode="+logisticsCode+"&destnCountry="+destnCountry+"&logisticsMode="+logisticsMode;
                axios.get(url)
                    .then(function (res) {
                        if(res.data.code == '200'){
                            layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>添加成功</span>");
                            $(".logistics_pop").hide();
                            Vm.logisticsDispatchSearch();
                        }else {
                            layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>添加失败<</span>");
                        }
                    })
            }else {
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>数据必填</span>");
            }
        },
        //根据快递公司选择 物流方式
        logisticsCodeChange:function () {
            var event = event || window.event;
            var target = event.target || event.srcElement;
            var logisticsCode= target.value;
            var url = '/index.php?g=logistics&m=configs&a=getLogisticsModeList'
            if(logisticsCode){
                url +='&logisticsCode='+logisticsCode
            }
            axios.get(url)
                .then(function(res) {
                    console.info(res);
                    var data = res.data.data,dataArr=[];
                    if(data){
                        Vm.typeDataTipState = false;
                        for(key in data){
                            var dataObj={};
                            // dataObj.logistics_code = data[key].logistics_code;
                            dataObj.id = data[key].id;
                            dataObj.logistics_mode = data[key].logistics_mode;
                            dataArr.push(dataObj)
                        }
                    }else {
                        Vm.typeDataTipState = true;
                    }
                    Vm.typeDataTip = res.data.tips;
                    Vm.typeData = dataArr;

                })
        },
        //excel导出功能
        logisticsXlExport:function () {
            var startTime=$("#startTime").val(),endTime=$("#endTime").val(),ruleName=$(".ruleName").val();
            var url='/index.php?g=logistics&m=configs&a=exportRules';
            if(startTime){
                url = url + '&startTime='+startTime;
            }
            if(endTime){
                url = url + '&endTime='+endTime;
            }
            if(ruleName){
                url = url + '&ruleName='+ruleName;
            }
            window.location.href=url;

        }
    },
});