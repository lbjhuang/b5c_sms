var Vm = new Vue({
    el: '#logistics',
    data:{
        tableData:'',
        detail:'',
        tableDataLength:'',
        companyData:'',
        channelData:'',
        DictionaryList:'',
        page:{
            pageCurrent:1,
            pageRows:20,
            pageTotal:10,
            displayData:0,
         },
        pop_logistics_name:'',
        modefiedData:'',
        modefiedDataId:'',
        modefiedDataChannel:'',
        latelyPagesNum:0,
        addStateShow:false,
        checkboxData:'',
        modefiedState:false,
        popB5CIdentificationVal:'',
        popChannelIdentificationVal:'',
        apiType:{},
        providerSystem:{},
    },
    //页面渲染之前，先取码表数据
    /*beforeCreate:function () {
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00068,N00083,N00082,N00070,N00041';   //从码表获取数据，各参数分别为：仓库,销售渠道,物流类别, 物流公司,产地;
        axios.get(getDictionaryListUrl)
            .then(function(res) {
                Vm.DictionaryList=res.data.data;
                //从码表中获取所有的快递公司数据
                var companyData=[],DictionaryListcompany=res.data.data['N00070'];
                //从码表中获取所有的渠道数据
                var channelData=[],DictionaryListChannel=res.data.data['N00083'];
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
                Vm.channelData =channelData;
                Vm.companyData =companyData;
            })
    },*/
    created :function(){
        setTimeout(function () {
            Vm.trackSearch();
        },10)
    },
    mounted:function () {
        // 日期右侧图标点击加载日历插件/
        $(".common_data .input-group-btn button").click(function () {
            $(this).parents(".common_data").find("input").focus()
        })
    },
    methods:{
        // 翻页跳转
        handleCurrentChange:function(val) {
            
            Vm.latelyPagesNum=val;
            Vm.page.pageCurrent = val;
            Vm.trackSearch();
        },
        //所有取消按钮
        closeWindow:function (event) {
            var event= event || window.event;
            var target= event.target || event.srcElement;
            var dom = target.parentNode.parentNode;
            dom.style.display='none';
        },
        showWindow:function () {
            var event= event || window.event;
            var target= event.target || event.srcElement;
            var domAll = document.querySelectorAll('.trackList_pop');
            for(key in domAll){
                if(typeof(domAll[key]) == 'object'){
                    domAll[key].style.display='none';
                }
            }
            var dom = target.parentNode.parentNode.querySelector('.trackList_pop');
            dom.style.display='block';
        },
        //物流轨迹搜索
        trackSearch:function () {
            var startTime=$("#startTime").val(),endTime=$("#endTime").val(),apiType=$("#apiType").val(),providerSystem=$("#providerSystem").val();
            var pageRows = Vm.page.pageRows;
            var pageCurrent = Vm.page.pageCurrent;
            var trackSearchurl = '/index.php?g=logistics&m=configs&a=tracking_api_list'; //列表数据
            var getInterfacesUrl = '/index.php?g=logistics&m=configs&a=getInterface';   //接口数据
            
            if(startTime){
                trackSearchurl +='&startTime='+startTime;
            }
            if(endTime){
                trackSearchurl +='&endTime='+endTime;
            }
            if(apiType){
                trackSearchurl +='&apiType='+apiType;
            }
            if(providerSystem){
                trackSearchurl +='&providerSystem='+providerSystem;
            }
            //总页数
            if (pageRows) {
                trackSearchurl +='&pageRows='+pageRows;
            }
            if (pageCurrent) {
                trackSearchurl +='&pageCurrent='+pageCurrent;
            }
            //获取列表数据
            axios.get(trackSearchurl)
            .then(function (res) {
                var tableData=[];
                if (res.data.data.trackResData) {
                    tableData = res.data.data.trackResData;    
                }
                
                var sort = 1;
                for (var i = 0; i < tableData.length; i++) {
                    tableData[i]['sort_id'] = sort;
                    sort++
                }
                
                count = Number(res.data.data.count);
                Vm.page.pageTotal = count;
                Vm.tableData = tableData;
            })
            //获取接口数据
            axios.get(getInterfacesUrl)
            .then(function(res){
                if (res.data.code===200) {
                    Vm.apiType = res.data.data.apiType;
                    Vm.providerSystem = res.data.data.providerSystem;
                }
            })
           
        },
        //excel导出功能
        logisticsXlExport:function () {

            var startTime=$("#startTime").val(),endTime=$("#endTime").val(),apiType=$("#apiType").val(),providerSystem=$("#providerSystem").val();
            var pageRows = Vm.page.pageRows;
            var pageCurrent = Vm.page.pageCurrent;
            var exportApiLogUrl = '/index.php?g=logistics&m=configs&a=exportApiLog'; //列表数据
            var getInterfacesUrl = '/index.php?g=logistics&m=configs&a=getInterface';   //接口数据
            
            if(startTime){
                exportApiLogUrl +='&startTime='+startTime;
            }
            if(endTime){
                exportApiLogUrl +='&endTime='+endTime;
            }
            if(apiType){
                exportApiLogUrl +='&apiType='+apiType;
            }
            if(providerSystem){
                exportApiLogUrl +='&providerSystem='+providerSystem;
            }
            //总页数
            if (pageRows) {
                exportApiLogUrl +='&pageRows='+pageRows;
            }
            if (pageCurrent) {
                exportApiLogUrl +='&pageCurrent='+pageCurrent;
            }


           // var startTime=$("#startTime").val(),endTime=$("#endTime").val(),apiType=$("#apiType").val(),providerSystem=$("#providerSystem").val();
            //var exportApiLogUrl = '/index.php?g=logistics&m=configs&a=exportApiLog';
           
            window.location.href=exportApiLogUrl
        },
        //track detail 轨迹详情展示
        showDetail:function (Id) {
            var trackDetailurl = '/index.php?g=logistics&m=Track&a=track_detail&Id='+Id;
            var route = document.createElement("a");
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", "opennewtab(this,'物流详情')");
            route.setAttribute("_href", trackDetailurl);
            route.click();
            /*axios.get(trackDetailurl)
            .then(function (res) {
                var trackDetailurl=[];
                tableData = res.data.data;
                Vm.tableData = tableData;
            })*/
        }
    }
});