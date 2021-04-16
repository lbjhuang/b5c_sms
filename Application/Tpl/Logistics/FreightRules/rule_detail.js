var Vm = new Vue({
    el: '#rule_add',
    data:{
        wareData:'',
        baseModelData:{
        },  //基础数据
        logCompany:'',
        LOGISTICS_MODE:'',
        interval:'',    //区间
        BAN_ITEM_CAT:'',  //限制条件
        ProvinceState:[], // 省或地区临时存放处
        CountryState:[], //   国家数据存放
        TITLE_NAME:'', //标题
        startPlace:[], //出发地，展示在表格的数据
        startPlaceToMany:[],    //出发地，信息太多的话
        startPlaceAlldisplay:false, //出发地  查看全部展示相关
        startPlaceshow:false,       //出发地  多余的部分展示
        startPlaceShowActive:false,   //出发地多余三个，则其为true
        ContinuedHeavyLength:0,       //价格详情，续重数目

    },
    created :function(){
        this.search();
    },
    //页面渲染之前，先取码表数据
    beforeCreate:function () {
        var getDictionaryListUrl = '/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00192';//不支持方式;
        axios.get(getDictionaryListUrl).then(function (res) {
            Vm.DictionaryList = res.data.data;
            var wareData = [], DictionaryListWare = res.data.data['N00192'];
            for (key in DictionaryListWare) {
                var wareObj = {};
                wareObj.code = DictionaryListWare[key].CD;
                wareObj.val = DictionaryListWare[key].CD_VAL;
                wareData.push(wareObj)
            }
            Vm.wareData = wareData;
        })

        var logModeId = $("#logModeId").val();
        var logModeUrl = "/index.php?g=logistics&m=FreightRules&a=getLogModeData&id=" + logModeId
        axios.get(logModeUrl).then(function (res) {
            Vm.logCompany = res.data.logCompany;
            Vm.LOGISTICS_MODE = res.data.LOGISTICS_MODE;
        })
    },
    methods:{
       //页面数据预加载。
        search:function(){
            var id = $("#modelID").val();
            var logModeUrl = "/index.php?g=logistics&m=FreightRules&a=getdetailData&modelID="+id
            axios.get(logModeUrl)
                .then(function(res){
                    Vm.TITLE_NAME =res.data.MODEL_NM
                    Vm.baseModelData = res.data;
                    for (k in res.data) {
                        if (!isNaN(res.data[k]) && res.data[k]!==null) {
                            Vm.baseModelData[k] = parseFloat(res.data[k]);
                        }
                    }
                    for (k in res.data.first_heavy) {
                        if (!isNaN(res.data.first_heavy[k])) {
                            Vm.baseModelData.first_heavy[k] = parseFloat(res.data.first_heavy[k]);
                        }
                    }
                    var Klength=0;
                    for (k in res.data.POSTTAGE_VAL) {
                        for (k1 in res.data.POSTTAGE_VAL[k]) {
                            if (!isNaN(res.data.POSTTAGE_VAL[k][k1])) {
                                Vm.baseModelData.POSTTAGE_VAL[k][k1] = parseFloat(res.data.POSTTAGE_VAL[k][k1]);
                            }
                        }
                        Klength = k;
                    }
                    Vm.ContinuedHeavyLength = Klength
                    var CountryState = res.data.SEND_AREAS;
                    for(key in CountryState){
                        if(CountryState[key].area_type == '1'){
                            Vue.set(CountryState[key],'display',false)
                            if(CountryState[key].province){}
                            else{
                                Vue.set(CountryState[key],'province',[])
                            }
                            Vm.CountryState.push(CountryState[key])
                        }else if(CountryState[key].area_type == '2'){
                            Vm.ProvinceState.push(CountryState[key])
                        }
                    }
                    for(key in Vm.ProvinceState){ //循环遍历省份，塞入对应国家
                        var keyIndex = -1;
                        for(key1 in Vm.CountryState){
                            if(Vm.ProvinceState[key1]){
                                if(Vm.ProvinceState[key].parent_no ==Vm.CountryState[key1].area_no){
                                    keyIndex = key1;
                                }
                            }
                        }
                        if(keyIndex > -1){   //此时，有对应国家
                            if( Vm.CountryState[keyIndex].province){
                                Vm.CountryState[keyIndex].province.push(Vm.ProvinceState[key])
                            }else{
                                Vue.set(Vm.CountryState[keyIndex],'province',[])
                                Vm.CountryState[keyIndex].province.push(Vm.ProvinceState[key])
                            }
                        }
                    }
                    for(key in Vm.CountryState){
                        if(Vm.CountryState[key].province.length>0){}
                        else{
                            Vue.set(Vm.CountryState[key],'all',true)
                        }
                    }
                    // 已选区域  国家身份是否全部判断
                    if(Vm.CountryState){
                        for(key in Vm.CountryState){
                            if(typeof Vm.CountryState[key].province){
                                //判断部分还是默认全境
                                if(Vm.CountryState[key].province.length>0){
                                    var areaNo = Vm.CountryState[key].province[0].parent_no;
                                    var areaNoLength = 0;
                                    var url = "/index.php?g=logistics&m=FreightRules&a=getAreaData&area_no="+areaNo;
                                    $.ajax({
                                        type: "POST",
                                        url: url,
                                        async: false,
                                        dataType: "json",
                                        success: function (res) {
                                            areaNoLength = res.length;
                                            if (areaNoLength > Vm.CountryState[key].province.length) {
                                                if (Vm.CountryState[key].apart) {
                                                    Vm.CountryState[key].apart = true;
                                                }
                                                else {
                                                    Vue.set(Vm.CountryState[key], 'apart', true)
                                                }
                                            } else if (areaNoLength ==Vm.CountryState[key].province.length) {
                                                if (Vm.CountryState[key].apart) {
                                                    Vm.CountryState[key].apart = false
                                                }
                                            }
                                        }
                                    })
                                }else{
                                    if(Vm.CountryState[key].apart){
                                        Vm.CountryState[key].apart =false
                                    }
                                }
                            }
                        }
                    }
                   $(".relue_detail_textarea .table_span ").html(res.data.REMARK)
                    if(Vm.baseModelData.OUT_AREAS_DATA.length>0){
                        Vm.baseModelData.OUT_AREAS_DATA=Vm.baseModelData.OUT_AREAS_DATA.split(',')
                        if(Vm.baseModelData.OUT_AREAS_DATA.length > 0 && Vm.baseModelData.OUT_AREAS_DATA.length <= 2 ){
                            Vm.startPlace = Vm.baseModelData.OUT_AREAS_DATA;
                            Vm.startPlaceAlldisplay = false;
                        }else if(Vm.baseModelData.OUT_AREAS_DATA.length >2){
                            for(var i=0;i<2;i++){
                                Vm.startPlace.push(Vm.baseModelData.OUT_AREAS_DATA[i])
                            }
                            Vm.startPlaceToMany = Vm.baseModelData.OUT_AREAS_DATA;
                            Vm.startPlaceAlldisplay = true;
                        }
                        if(Vm.baseModelData.OUT_AREAS_DATA.length >10){
                            Vm.startPlaceShowActive =true
                        }
                    }
                })

        },
        //跳转到编辑页面
        edit: function(postageId) {
            var logModeId = $("#logModeId").val();
            var route = document.createElement("a");
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", "changenewtab(this,'"+this.$lang('修改运费规则')+"')");
            route.setAttribute("_href", '/index.php?g=logistics&m=FreightRules&a=rule_add&postageId='+postageId+"&logModeId="+logModeId);
            console.log(route)
            route.click();
        },
        //已选区域，点击展示其下的州或者省份
        AreaDisplay:function (index) {
            for(key in Vm.CountryState){
                Vm.CountryState[key].display=false
            }
            Vm.CountryState[index].display=true
        },
        //关闭已选区域国家其下的州或者省份
        AreaDisplayNone:function (index) {
            Vm.CountryState[index].display=false
        },
        //展示多余的出发地仓库
        startPlacedisplay:function () {
            Vm.startPlaceshow =true;
        },
        //隐藏多余的出发地仓库
        startPlacedisplayNone:function () {
            Vm.startPlaceshow =false;
        },
    },
    
});
