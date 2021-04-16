if(getCookie('think_language') !== "zh-cn" ){
    ELEMENT.locale(ELEMENT.lang.en)
}
var Vm = new Vue({
    el: '#logistics',
    data:{
        DictionaryList:'',
        postageModelData:[],
        search:{
            STATE_CODE:'', //状态
            DENOMINATED_TYPE:'', //计价方式
            OUT_AREAS:'',  //仓库出发地
            SEND_AREAS:'', //国家目的地
        },
        OUT_AREAS_ALL:[],
        SEND_AREAS_ALL:[],
        page:{
            sePage:1,
            pageSize:20,
        },
        latelyPagesNum:1,
        goLength:[],
        Popstatus:"",
        PopModefiedId:"",
        rule_list_pop:false,
        rulePopData:[],   //弹框数据  主要是选中停用模板名称
        MODELID:[],      // 停用接口 所需数据
        LOGISTICS_MODE:'', //快递公司
       popActive:false,
        TotalPageSize:0,
        tableLoading: false
    },
    //页面渲染之前，先取码表数据
    beforeCreate:function () {
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00068,N00083,N00082,N00070,N00041';//仓库,销售渠道,物流类别, 物流公司,产地;
        axios.get(getDictionaryListUrl)
            .then(function(res) {
                var OUT_AREAS_ALL=[],DictionaryListOutarea=res.data.data['N00068'];
                for(key in DictionaryListOutarea){
                    var companyObj={};
                    companyObj.code = DictionaryListOutarea[key].CD;
                    companyObj.val = DictionaryListOutarea[key].CD_VAL;
                    OUT_AREAS_ALL.push(companyObj)
                }
                Vm.OUT_AREAS_ALL =OUT_AREAS_ALL;
            })
        var getAreaListUrl='/index.php?m=Api&a=choice';
        axios.get(getAreaListUrl)
            .then(function(res){
                Vm.SEND_AREAS_ALL = res.data[0].countryData;
            })
        var logModeId=$("#logModeId").val();
        var LogMdeUrl = 'index.php?g=logistics&m=FreightRules&a=getLogModeData&id='+logModeId
        axios.get(LogMdeUrl)
            .then(function(res){
                Vm.LOGISTICS_MODE = res.data.LOGISTICS_MODE
            })
    },
    created :function(){
        setTimeout(function () {
            Vm.postageDataSearch();
        },10)
    },
    mounted:function(){
     //翻页处校验，跳到输入页码的页面，当大于最大页数时，提示报错。
     $("#logistics .logistics_table_record").on("keyup",'.el-pagination__jump .el-pagination__editor',function () {
         var amount = parseFloat($(this).val());
         //上下左右，删除，enter键以及纯数字键盘校验。
         if (event.key !== 'Backspace' && isNaN(event.key) && event.key !== 'ArrowDown' && event.key !== 'ArrowUp' && event.key !== 'ArrowRight' && event.key !== 'ArrowLeft'&& event.key !== 'Enter') {
             $(this).val('')
             layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('输入页码有误，请检查后重新输入')+"</span>");
             return false;
         }
         if(amount ==0|| amount > Vm.TotalPageSize){
             layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('输入页码有误，请检查后重新输入')+"</span>");
             $(this).val('')
             return false;
         }
     })
    },
    methods:{
        //翻页跳转函数
        handleCurrentChange:function(pagenow) {   //翻页
            this.page.sePage = pagenow;
            this.postageDataSearch();
            var checkBox = document.querySelectorAll('.postageModelDataCheckbox');
            $(".checkBoxAll").prop('checked',false);
            for(key in checkBox){
                checkBox[key].checked=false
            }
        },
        //改变当前页数据展示的条数。
        handleSizeChange:function (pageSize) {
            this.page.pageSize = pageSize
            this.postageDataSearch();
        },
        //所有取消按钮
        cancel:function () {
            this.rule_list_pop = false;
        },
        //增加基础信息
        add:function (type) {
            var logModeId = $("#logModeId").val();
            if (type=='add') {
                var trackurl = "/index.php?g=logistics&m=FreightRules&a=rule_add&logModeId="+logModeId;
                var route = document.createElement("a");
                route.setAttribute("style", "display: none");
                // route.setAttribute("onclick", "opennewtab(this,'创建运费规则')");
                route.setAttribute("onclick", "opennewtab(this,'"+ this.$lang('创建运费规则') + "')");
                route.setAttribute("_href", trackurl);
                route.click();
            }
        },
        //增加基础信息后保存数据
        basisSubmitButton:function () {
            var _this = this;
            var logModeId = $("#logModeId").val();
            var getAreaListUrl='/index.php?g=logistics&m=FreightRules&a=disableModel&modelId='+ Vm.MODELID+"&logModeId="+logModeId;
            axios.get(getAreaListUrl)
                .then(function(res){
                    if(res.data.code == '200'){
                        layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+_this.$lang(res.data.data)+"</span>");
                        setTimeout(function () {
                            Vm.rule_list_pop = false;
                             Vm.postageDataSearch();
                        },100)
                    }else{
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+_this.$lang(res.data.data)+"</span>");
                    }
                })
        },
        //搜索功能
        postageDataSearch:function () {
            this.tableLoading = true;
            var logModeId=$("#logModeId").val();
            var LogMdeUrl = 'index.php?g=logistics&m=FreightRules&a=getLogModeData&id='+logModeId
            axios.get(LogMdeUrl)
                .then(function(res){
                    Vm.LOGISTICS_MODE = res.data.LOGISTICS_MODE

                })
            var sePage = this.page.sePage;
            var pageSize = this.page.pageSize;
            var url='/index.php?g=logistics&m=FreightRules&a=getRuleList'
            if (sePage) {
                 url=url+'&pagenow='+sePage;    
            }
            if (pageSize) {
                 url=url+'&pageSize='+pageSize; 
            }
            if(logModeId){
                url = url + '&logModeId='+logModeId;
            }
            var param = this.search;
            axios.post(url, {param: param})
                .then(function(res){
                    Vm.page.pageLength = parseInt(res.data.msg)
                    Vm.postageModelData = res.data.data;
                    var dataList = res.data.data.length;
                    var dataListTotal =  Number(res.data.msg)
                    var totalPage = Math.ceil(dataListTotal/dataList)
                    Vm.TotalPageSize = totalPage;
                })
                this.tableLoading = false;
        },
        //删除功能
        deleteData:function () {
            this.rule_list_pop = true;
        },
        jumpDetail:function (modelID) {
            var logModeId = $("#logModeId").val();
            var trackurl = "/index.php?g=logistics&m=FreightRules&a=rule_detail&modelID="+modelID+"&logModeId="+logModeId;
            var route = document.createElement("a");
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", "opennewtab(this,'"+this.$lang('规则详情页')+"')");
            route.setAttribute("_href", trackurl);
            route.click();
            var url='/index.php?g=logistics&m=FreightRules&a=getdetailData&modelID='+modelID;
        },
        //条件重置
        reset:function(){
            for (k in Vm.search) {
                Vm.search[k] = '';
            }
            Vm.postageDataSearch();
        },
        //查看日志跳转
        reviewLog:function () {
            var route = document.createElement("a");
            var logModeId = $("#logModeId").val();
            var trackurl = "/index.php?g=logistics&m=FreightRules&a=rule_log&logModeId="+logModeId;
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", "changenewtab(this,'运费规则表')");
            route.setAttribute("_href", trackurl);
            route.click();
        },
        //停用功能
        stopUseItems:function () {
            var logModeId = $("#logModeId").val();
            var postageModelDataCheckbox = document.querySelectorAll(".postageModelDataCheckbox");
            var modelId = [],popData=[];
            var stopYetData=[];
            for(key in postageModelDataCheckbox){
                if(typeof postageModelDataCheckbox[key]=='function' || typeof postageModelDataCheckbox[key]=='number'){}
                else{
                    if(postageModelDataCheckbox[key].checked && postageModelDataCheckbox[key].disabled==false){
                        modelId.push(postageModelDataCheckbox[key].getAttribute("name"));
                        popData.push(postageModelDataCheckbox[key].getAttribute("data-msg"))
                    }else if(postageModelDataCheckbox[key].disabled){
                        stopYetData.push($(postageModelDataCheckbox[key]).attr('data-msg'))
                    }
                }
            }
            if(modelId.length>0){
                Vm.rulePopData = popData;
                Vm.MODELID = modelId;
                Vm.rule_list_pop = true
                if(popData.length>12){
                    Vm.popActive =true
                }else{
                    Vm.popActive =false
                }
            }else{
                if(stopYetData.length == Number(Vm.page.pageSize)){
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('此页均为已停用模板，请到其他页选择！')+"</span>");
                }else{
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('请选择要停用的模板！')+"</span>");
                }
            }
        },
        //多选框相关
        choseAllItems:function () {
            var checkBoxAll =$(".checkBoxAll");
            var checkBox = document.querySelectorAll('.postageModelDataCheckbox');
            setTimeout(function () {
                if(checkBoxAll.prop('checked')){
                    for(key in checkBox){
                        if(typeof checkBox[key] == 'object'){
                            if(checkBox[key].disabled){
                                $(checkBox[key]).prop('checked',false);
                            }else{
                                $(checkBox[key]).prop('checked',true);
                                checkBox[key].checked=true;
                            }
                        }
                    }
                }
            },10)
        },
    }
});