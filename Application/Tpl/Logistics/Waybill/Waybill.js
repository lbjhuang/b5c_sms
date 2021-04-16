if(getCookie('think_language') !== "zh-cn" ){
    ELEMENT.locale(ELEMENT.lang.en)
}
var Vm = new Vue({
    el: '#logistics',
    data:{
        companyData:'',
        sourceData:'',
        templateData:'',
        typeData:'',
        DictionaryList:'',
        basisData:'',
        gshopperEditState:'',
        gshopperEditStateid:'',
        logisticsCodeValue:'',
        PopOperationType:'',
        startTime: '',
        endTime: '',
        logisticsWay: '',
        disabledButton_is_enable:0,
        disabledButton_id:1,
        source: '',
        page:{
            sePage:1,
            pageSize:10,
            pageLength:0,
            pageShow:'none',
            displayLength:0,
        },
        latelyPagesNum:1,
        go: {
            data11:[{name:11,age:11},{name:12,age:12},{name:13,age:13}],
            data12:[{name:21,age:21},{name:22,age:22},{name:23,age:23},{name:23,age:23}],
            data13:[{name:31,age:31},{name:32,age:32},{name:33,age:33},{name:34,age:34},{name:35,age:35}]
        },
        goLength:[],
        currentPage1: 5,
        currentPage2: 5,
        currentPage3: 5,
        currentPage4: 4
    },
    //页面渲染之前，先取码表数据
    beforeCreate:function () {
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00070,N00178,N00179';//仓库,销售渠道,物流类别, 物流公司,产地;
        axios.get(getDictionaryListUrl)
            .then(function(res) {
                Vm.DictionaryList=res.data.data;
                var companyData=[],DictionaryListcompany=res.data.data['N00070'],
                    sourceData=[],DictionaryListsource=res.data.data['N00178'],
                    templateData=[],DictionaryListtemplate=res.data.data['N00179'];
                for(key in DictionaryListcompany){
                    var companyObj={};
                    companyObj.code = DictionaryListcompany[key].CD;
                    companyObj.val = DictionaryListcompany[key].CD_VAL;
                    companyData.push(companyObj)
                }
                for(key in DictionaryListsource){
                    var sourceObj={};
                    sourceObj.code = DictionaryListsource[key].CD;
                    sourceObj.val = DictionaryListsource[key].CD_VAL;
                    sourceData.push(sourceObj)
                }
                for(key in DictionaryListtemplate){
                    var templateObj={};
                    templateObj.code = DictionaryListtemplate[key].CD;
                    templateObj.val = DictionaryListtemplate[key].CD_VAL;
                    templateData.push(templateObj)
                }
                Vm.companyData =companyData;
                Vm.sourceData =sourceData;
                console.log(Vm.sourceData, 333)
                Vm.templateData =templateData;
            })
    },
    created :function(){
        setTimeout(function () {
            Vm.basisDataSearch();
        },10)
    },
    mounted:function () {
        // 日期右侧图标点击加载日历插件/
        $(".common_data .input-group-btn button").click(function () {
            $(this).parents(".common_data").find("input").focus()
        })
    },
    methods:{
        handleCurrentChange:function(val) {
            Vm.latelyPagesNum = val;
            Vm.basisDataSearch(val)
            var checkBox = document.querySelectorAll('.checkboxItems');
            $(".checkboxAllItems").prop('checked',false);
            for(key in checkBox){
                checkBox[key].checked=false
            }
        },
        handleSizeChange: function (val) {
            console.log(val)
            Vm.page.sePage = 1;
            Vm.page.pageSize = val;
            Vm.latelyPagesNum = val;
            Vm.basisDataSearch();
        },
        //所有取消按钮
        cancel:function () {
            $(".logistics_pop").fadeOut(200)
        },
        //根据快递公司选择 物流方式
        logisticsCodeChange:function (data) {
            var logisticsCode='';
            if(data){
                logisticsCode= data;
            }else{
                var event = event || window.event;
                var target = event.target || event.srcElement;
                logisticsCode= target.value;
            }
            var url = '/index.php?g=logistics&m=configs&a=getLogisticsModeList';
            if(logisticsCode){
                url +='&logisticsCode='+logisticsCode
            }
            axios.get(url)
                .then(function(res) {
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
        //增加基础信息
        add:function (data) {
            $(".logistics_pop").fadeIn(200)
            $('.logistics_pop_body_right_edit button').hide();
            var selectEmpty = '<option value="">请选择</option>';
            if(data == 'add'){
                Vm.PopOperationType=true;
                $('#pop_logisticsCode select').val('')
                // $('#pop_logisticsModeId select').html(selectEmpty)
                this.logisticsWay = selectEmpty;
                $('#pop_sourceCode select').val('')
                $('#pop_templateCode select').val('')
                $("#pop_logisticsCode select").prop("disabled",false)
                // $("#pop_logisticsModeId select").prop("disabled",false)
            }
            else{
                Vm.logisticsCodeValue = data.logisticsModeId;
                Vm.logisticsCodeChange(data.logisticsCode);
                $('#pop_logisticsCode select').val(data.logisticsCode);
                $('#pop_sourceCode select').val(data.sourceCode);
                $('#pop_templateCode select').val(data.templateCode);
                $("#pop_logisticsCode select").prop("disabled",true)
                // $("#pop_logisticsModeId select").prop("disabled",true)

                if(data.sourceCode == 'N001780300'){
                    Vm.gshopperEditState=true;
                    $('#pop_templateCode select').val('N001790200');
                    $('#pop_templateCode select').attr('disabled',true);
                }else{
                    Vm.gshopperEditState=false
                    $('#pop_templateCode select').val('');
                    $('#pop_templateCode select').attr('disabled',false);
                }
                Vm.gshopperEditStateid = data.id;
                Vm.PopOperationType=false;
            }
        },
        //增加基础信息后保存数据
        basisSubmitButton:function () {
            var _this = this;
            var logisticsCode=$("#pop_logisticsCode select").val(),logisticsModeId=$("#pop_logisticsModeId select").val(),
                sourceCode=$("#pop_sourceCode select").val(),templateCode=$("#pop_templateCode select").val();
            if(logisticsCode && logisticsModeId && sourceCode && templateCode){
                var  basisUrl='',params='';
                if(Vm.PopOperationType){
                    basisUrl='/index.php?g=logistics&m=waybill&a=createWaybill';
                    params={
                        'logisticsCode':logisticsCode,
                        'logisticsModeId':logisticsModeId,
                        'sourceCode':sourceCode,
                        'templateCode':templateCode,
                    };
                }else {
                    basisUrl='/index.php?g=logistics&m=waybill&a=updateWaybill';
                    params={
                        'id':Vm.gshopperEditStateid,
                        'sourceCode':sourceCode,
                        'templateCode':templateCode,
                    };
                }
                axios.post(basisUrl,params)
                    .then(function (res) {
                        // console.info(res)
                        // console.info(params);
                        if(res.data.code == '200'){
                            if(Vm.PopOperationType){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+_this.$lang('添加成功')+"</span>");
                            }else {
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+_this.$lang('修改成功')+"</span>");
                            }
                            Vm.basisDataSearch()
                            $(".logistics_pop").fadeOut(200);
                        }else{
                            if(Vm.PopOperationType){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red;'>X</i>"+_this.$lang(res.data.msg)+"</span>");
                            }else {
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red;'>X</i>"+_this.$lang(res.data.msg)+"</span>");
                            }
                        }
                    })
            }else{
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>" + this.$lang('请将数据填写完整') + "</span>");
            }
        },
        //gshoppper面单来源判断
        sourceCodeChose:function () {
            var event = event || window.event;
            var target = event.target || event.srcElement;
            var logisticsCode= target.value;
            if(logisticsCode == 'N001780300'){
                Vm.gshopperEditState=true;
                $('#pop_templateCode select').val('N001790200')
                $('#pop_templateCode select').attr('disabled',true)
            }else{
                Vm.gshopperEditState=false;
                $('#pop_templateCode select').val('');
                $('#pop_templateCode select').attr('disabled',false);
            }
        },
        //关闭弹框
        logisticsPopCancel:function(){
            $(".logistics_pop_operation").fadeOut(200);
        },
        //打开弹框
        disabledButton:function(a){
            Vm.disabledButton_is_enable=a.is_enable;
            Vm.disabledButton_id = a.id;
            $(".logistics_pop_operation").fadeIn(200);
        },
        logisticsPopSure:function () {
            var url='/index.php?g=logistics&m=waybill&a=updateWaybill',parms={};
            parms={
                'id':Vm.disabledButton_id,
                'isEnable':Vm.disabledButton_is_enable == '0'?'1':'0'
            };
            axios.post(url,parms)
                .then(function(res){
                    if(res.data.code == '200'){
                        Vm.basisDataSearch(Vm.latelyPagesNum)
                        $(".logistics_pop_operation").fadeOut(200);
                    }
                })
        },
        //搜索功能
        basisDataSearch:function (num) {
            // var startTime=this.startTime,endTime=this.endTime,sourceCode=$("#sourceCode").val();
            var startTime=this.startTime,endTime=this.endTime,sourceCode=this.source;
            var url='/index.php?g=logistics&m=waybill&a=searchWaybill';
            var params={
                'startTime':startTime,
                'endTime':endTime,
                'sourceCode':sourceCode,
                'page':num?num:1
                // "pageSize": this.page.pageSize
            };
            axios.post(url,params)
                .then(function(res){
                    var data=res.data.data.list;
                    var basisDataArry=[]
                    // console.info(res)
                    for(key in data){
                        var basisDataObj={};
                        basisDataObj.create_time=data[key].create_time;
                        basisDataObj.creator=data[key].creator;
                        basisDataObj.id=data[key].id;
                        basisDataObj.is_enable=data[key].is_enable;
                        basisDataObj.logisticsCode=data[key].logisticsCode;
                        basisDataObj.logisticsCompany=data[key].logisticsCompany;
                        basisDataObj.logisticsModeId=data[key].logisticsModeId;
                        basisDataObj.logisticsModeName=data[key].logisticsModeName;
                        basisDataObj.sourceCode=data[key].sourceCode;
                        basisDataObj.sourceName=data[key].sourceName;
                        basisDataObj.templateCode=data[key].templateCode;
                        basisDataObj.templateName=data[key].templateName;
                        basisDataObj.update_time=data[key].update_time;
                        basisDataArry.push(basisDataObj)
                    }
                    Vm.basisData=basisDataArry;
                    Vm.page.sePage= Number(res.data.data.page);
                    Vm.page.pageLength=Number(res.data.data.total);
                    Vm.page.pageSize=Number(res.data.data.rows);
                    Vm.page.displayLength = res.data.data.total;
                })
        },
        reset: function () {
            this.startTime = '';
            this.endTime = '';
            // sourceCode=$("#sourceCode").val() = '';
            this.source = '';
            this.basisDataSearch();
        },
        endTimeChange: function (value) {
            this.endTime = value;
        },
        startTimeChange: function (value) {
            this.startTime = value;
        },
        //excel导出功能
        logisticsXlExport:function () {
            // var startTime=$("#start_time").val(),endTime=$("#end_time").val(),sourceCode=$("#sourceCode").val();
            var startTime=$("#start_time").val(),endTime=$("#end_time").val(),sourceCode=this.source;
            var url='/index.php?g=logistics&m=waybill&a=exportWaybillBind';
            if(startTime){
                url = url + '&startTime='+startTime;
            }
            if(endTime){
                url = url + '&endTime='+endTime;
            }
            if(sourceCode){
                url = url + '&sourceCode='+sourceCode;
            }
            window.location.href=url
        },
        toEdit: function (id, title) {
            var dom = document.createElement('a');
            var _href = '/index.php?g=logistics&m=waybill&a=faceFormat&id=' + id;
            dom.setAttribute("onclick", "opennewtab(this,'"+this.$lang(title) + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        }
    }
});