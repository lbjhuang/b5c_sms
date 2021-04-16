var child = Vue.extend({
    template: '<div><script id="editor" type="text/plain" style="width:100%;height:100px"></script></div>',
    name: 'UE',
    data: function() {
        return {
            editor: null
        }
    },
    props: {
        defaultMsg: {
            type: String
        },
        config: {
            type: Object
        }
    },
    mounted:function(){
        const _this = this;
        this.editor =  UE.getEditor('editor', this.config) // 初始化UE
        this.editor.addListener("ready", function () {
            _this.editor.setContent(_this.defaultMsg); // 确保UE加载完成后，放入内容。
        });
    },
    methods: {
        getUEContent:function() {
            return this.editor.getContent();
        },
    },
    destroyed:function(){
        this.editor.destroy();
    }
});
var Vm = new Vue({
    el: '#rule_add',
    components: {
        'ue': child
    },
    data:{
        stash_data:[],
        OUT_AREASTotal:[],  //所有仓库
        form:{
            logCompany:'',   //物流公司
            LOGISTICS_MODE:'',  //物流方式
            MODEL_NM:'',  //模版名称
           // CHANNEL_TYPE:'',  //线路类型;0-跨境运输;1-中国国内
            STATE_CODE:0,
            OUT_AREAS:[],   //出发地(区域信息code,多个以逗号隔开)
            DAY1:'',     //时效(天)-开始时间
            DAY2:'',    //时效(天)-结束时间
            DENOMINATED_TYPE:'',    //计价方式;0-仅计重;1-计泡
            COEFFICIENT:'',     //计泡系数
            MAX_WEIGHT:'',     //最大重量
            MAX_WEIGHT_TYPE:false,    //重量是否有限制;0-有限制;1-无限制
            REMARK :'',             //注意事项
            POSTTAGE_DISCOUNT: '',      //运费折扣
            POSTTAGE_DISCOUNT_DATE_START:'',    //运费折扣有效期-开始时间
            POSTTAGE_DISCOUNT_DATE_END:'',//运费折扣有效期-结束时间
            BAN_ITEM_CAT:[],    //不支持类型code值，多个以逗号隔开
            PROCESS_DISCOUNT:'',
            PROCESS_DISCOUNT_DATE_START:null,
            PROCESS_DISCOUNT_DATE_END:null,
            FIRST_HEAVY_TYPE:false,   //区间开始重量值
            //区间
            postVal: [{
                WEIGHT1: '',   //区间开始重量值
                WEIGHT2: '',   //区间开始重量值
                COST: '',  //费用
                PROCESS_WEIGHT: '', //计费方式;0-按重量;1-按包裹
                PROCESS_COST: '',  //处理费用
            }],
            LENGTH1_START:'',   //最长边-间隔开始值
            LENGTH1_END:'',     //最长边-间隔结束值
            LENGTH2_START:'',   //第二长边-间隔开始值
            LENGTH2_END:'',     //第二长变-间隔结束值  
            LENGTH3_MAX:'',     //长宽高之合-最大值 (<=)
            VOLUME_MAX:'',      //体积最大值(<=)
            AlreadyChose:[], //已选区域,
            textareaValue:'',
        },
        popArea_status:false,
        popArea_result:false,
        SEND_AREAS:'',  //目的地区域 逗号分割
        HOT_AREAS:[],   //热门区域
        All_AREAS:'',  //所有目的地区域
        All_AREASAll:'',  //所有目的地区域
        ProvinceState:[], //省地区临时信息存放地点
        ProvinceStateChose:[], //省地区临时信息存放地点
        resultSuccessStates:[], // 导入结果提醒    已成功的数据
        resultSuccessStatesCountry:[], // 导入结果提醒   已存在国家
        resultSuccessStatesCountryYet:[], // 导入结果提醒   已存在国家
        resultSuccessStatesProvince:[], // 导入结果提醒   省
        resultSuccessStatesProvinceYet:[], // 导入结果提醒   已存在省
        resultFalseStates:[], // 导入结果提醒    失败的数据
        PopEreaCountry:'',      //弹框国家名称
        PopEreaProvince:[],      //弹框省份
        unSupType:{},            //限制条件
        ProvinceStateAdd:[], // 省或地区临时存放处    编辑页面需要
        CountryStateAdd:[], //   国家数据存放       编辑页面需要
        defaultMsg: '',      //富文本框相关配置
        config: {
            initialFrameWidth: null,
            initialFrameHeight: 350,
        },
        TITLE_NAME:'',
        NoneStartHeight:false,
        longestSide:true,      //最长边
        secondLongestSide:true,   //第二场边
        ChoseProvinceYet:false,   //选择目的地，input框搜索框添加成功的省份
        ChoseProvinceNot:false,   //选择目的地，input框搜索框添未成功的省份
        interStr:function(item){
            if(!item) return '其他';
            var index = item.indexOf('（');
            return item.substring(0,index);
        },
    },

    created : function(){
        this.defaultMsg = this.$lang('这里是UE测试')
        this.loadUe();
        setTimeout(function () {
           Vm.search();
        },500)
    },
    beforeCreate:function () {
        //从码表中获取对应数据  仓库,销售渠道,物流类别, 商品特性;
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00068,N00070,N00192';//仓库,销售渠道,物流类别, 商品特性;
        axios.get(getDictionaryListUrl)
            .then(function(res) {
                var OUT_AREASTotal = res.data.data['N00068'];
                for(key in OUT_AREASTotal) {
                    var OUT_AREASTotalObj = {};
                    OUT_AREASTotalObj.value = OUT_AREASTotal[key].CD;
                    OUT_AREASTotalObj.CD_VAL = OUT_AREASTotal[key].CD_VAL;
                    Vm.OUT_AREASTotal.push(OUT_AREASTotalObj)
                }
                var unSupTypeData = res.data.data['N00192'];
                var unSupType = [];
                for (k in unSupTypeData) {
                    unSupType.push(unSupTypeData[k]);
                }
                Vm.unSupType = unSupType;
            })
        var getAreaListUrl = "index.php?g=universal&m=Area&a=getInterCounty";   //国家(含洲际)
        axios.get(getAreaListUrl)
            .then(function(res) {
                 Vm.All_AREASAll = res.data.data;
                 var data =  res.data.data;
                //亚洲 N001960100，欧洲 N001960200，北美洲 N001960300，大洋洲 N001960400，南美洲 N001960500，非洲 N001960600，南极洲 N001960700
                var North_America=[],Asia=[],Europe=[],South_America=[],Africa=[],Oceania=[],Antarctica=[],dataAreaArr =[],HOT_AREASArr=[];
                    for(key in data){
                        var dataObj = {};
                        dataObj.CD = data[key].CD;
                        dataObj.CD_VAL = data[key].CD_VAL;
                        dataObj.area_no = data[key].area_no;
                        dataObj.area_type = data[key].area_type;
                        dataObj.rank = data[key].rank;
                        dataObj.zh_name = data[key].zh_name;
                        var CD_VAL=data[key].CD_VAL
                        if(CD_VAL){
                            var index = CD_VAL.indexOf("（")
                            dataObj.data_CD_VAL = CD_VAL.substring(0,index);
                        }else{
                            dataObj.data_CD_VAL = "Null"
                        }
                        //热门品牌 rank值小于或等于30
                        //其他的则分门别类放到对应州里面
                        //亚洲 N001960100，欧洲 N001960200，北美洲 N001960300，大洋洲 N001960400，南美洲 N001960500，非洲 N001960600，南极洲 N001960700
                        if(dataObj.rank < 31 && dataObj.rank>=1){
                            HOT_AREASArr.push(dataObj)
                        }else{
                        switch (data[key].CD){
                            case 'N001960100':
                                Asia.push(dataObj)
                            break;
                            case 'N001960200':
                                Europe.push(dataObj)
                                break;
                            case 'N001960300':
                                North_America.push(dataObj)
                             break;
                            case 'N001960400':
                                Oceania.push(dataObj)
                             break;
                            case 'N001960500':
                                South_America.push(dataObj)
                             break;
                            case 'N001960600':
                                Africa.push(dataObj)
                            break;
                            case 'N001960700':
                                Antarctica.push(dataObj)
                            break;
                        }
                    }
                }
                
                Vm.stash_data = {send:[[...Asia],[...Europe],[...North_America],[...Oceania],[...South_America],[...Africa],[...Antarctica]], hot:[...HOT_AREASArr]}
                Vm.HOT_AREAS = HOT_AREASArr;
                dataAreaArr.push(Asia,Europe,North_America,Oceania,South_America,Africa,Antarctica)
                Vm.SEND_AREAS = dataAreaArr;
                Vm.All_AREAS = res.data.data;
            })
        },
    mounted:function () {
        //主要是 enter键切换，焦点落入到下一个输入框中的事件，由于select特殊，以及出发地处多选框是第三方插件，比较复杂，所以着来年各个单独拿出来基于相应事件。
        //状态处下拉框
        $("#edit_part #onkeypressSelect").keydown(function (e) {
            var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;
            if (keyCode == 13) {
                $("#edit_part #onkeypressSelect").keydown(function (e) {
                    var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;
                    if (keyCode == 13) {
                        $(".rule_add_startPlace #onKeyPressSelectAll .el-input__inner").focus()
                        $("#onkeypressSelect").css('border','1px solid rgb(200, 210, 215)');
                        return false;
                    }})
            }
        })
        //出发地处 下拉多选框
        $("#onKeyPressSelectAll").keydown(function (e) {
            var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;
            if (keyCode == 13) {
                $('#onkeypressDay').focus().css('border','1px solid #20a0ff')
                $(".el-select-dropdown").css('display','none');
                $(".rule_add_startPlace #onKeyPressSelectAll .el-icon-caret-top").css('transform','translateY(-50%) rotateZ(180deg)')
            }
        })
        //所有输入框
        $(".onkeypressInput").keydown(function(e) {
            var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode;
            if (keyCode == 13){
                $("#onkeypressSelect").focus().css('border','1px solid rgb(200, 210, 215)');
                keyCode == 9;
                var inputs = document.querySelectorAll('.onkeypressInput')
                var inputsData =[];
                for(key in inputs){
                    if(inputs[key].disabled){}
                    else{
                        if(typeof(inputs[key]) == 'object'){
                            inputsData.push(inputs[key])
                        }
                    }
                }
                var idx = inputsData.indexOf(this);
                if (idx == inputsData.length - 1) {
                    inputsData[0].focus();
                    return false;
                }
                else {
                    if (idx == 0) {
                        $("#onkeypressSelect").focus().css("border","1px solid #20a0ff");
                    }
                    else{
                        inputsData[idx + 1].focus();
                        $(inputsData[idx + 1]).css('border','1px solid #20a0ff')
                        $(inputsData[idx]).css('border','1px solid rgb(200, 210, 215)');
                    }
                }
                return false;
            }
        });
        document.querySelector('#ruleAddBody').addEventListener('scroll', this.menu)
    },
    methods:{
        //富文本框，顶部菜单栏自适应函数，当富文本框中的信息过多则编辑菜单栏会在最上面，根据滚动情况修改位置显示。
        menu:function(){
            this.scroll = document.documentElement.scrollTop || document.body.scrollTop;
            //富文本框高度
            var scrollHeight = document.getElementById("ueditorTop").scrollHeight ;
            //整个文档的高度
            var bodyHeight = document.body.scrollHeight;
            var needHieght = bodyHeight - scrollHeight - 40;
            if(needHieght >= this.scroll){
                $('#edui1_toolbarbox').css({'position':"relative"})
            }else{
                $('#edui1_toolbarbox').css({'position':"fixed",'top':'0px','z-index':'999999','width':'100%'})
            }
        },
        //富文本框编辑器配置相关
        loadUe: function(type = '',content='') {
            if(type=='show'){
                setTimeout(function() {
                    UE.getEditor('editor', {
                        toolbars: [
                            ['bold', 'italic', 'underline', 'fontborder','fontsize','strikethrough', 'superscript', 'subscript', 'removeformat',
                                'formatmatch', 'autotypeset',  'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist','simpleupload','insertimage',
                                'justifyleft', 'justifyright',  'justifycenter',  'justifyjustify','forecolor',
                                'rowspacingtop','rowspacingbottom',  'insertunorderedlist', 'selectall', 'cleardoc', 'fullscreen', 'source', 'undo', 'redo',
                                'backcolor','imagecenter', 'wordimage',
                            ]
                        ],

                    }).setContent(content)
                }, 200)
            }else{
                setTimeout(function(){
                    this.ue = UE.getEditor('editor', {
                        toolbars: [
                            ['bold', 'italic', 'underline', 'fontfamily','fontborder','fontsize', 'strikethrough', 'superscript', 'subscript', 'removeformat',
                                'formatmatch', 'autotypeset',  'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist','simpleupload','insertimage',
                                'justifyleft', 'justifyright',  'justifycenter',  'justifyjustify','forecolor',
                                'rowspacingtop','rowspacingbottom',  'insertunorderedlist', 'selectall', 'cleardoc', 'fullscreen', 'source', 'undo', 'redo',
                                'backcolor','imagecenter', 'wordimage',
                            ]
                        ],

                    });
                },100)
            }
            //}, 200)
        },
        //运费折扣 以及 处理费折扣 时间
        POSTTAGE_DISCOUNT_DATE_START1:function () {
            Vm.form.POSTTAGE_DISCOUNT_DATE_START = document.querySelector('#POSTTAGE_DISCOUNT_DATE_START').value;
        },
        POSTTAGE_DISCOUNT_DATE_END1:function () {
            Vm.form.POSTTAGE_DISCOUNT_DATE_END = document.querySelector('#POSTTAGE_DISCOUNT_DATE_END').value;
        },
        PROCESS_DISCOUNT_DATE_START1:function () {
            Vm.form.PROCESS_DISCOUNT_DATE_START = document.querySelector('#PROCESS_DISCOUNT_DATE_START').value;
        },
        PROCESS_DISCOUNT_DATE_END1:function () {
            Vm.form.PROCESS_DISCOUNT_DATE_END = document.querySelector('#PROCESS_DISCOUNT_DATE_END').value;
        },
        //纯数字校验  键盘事件函数
        onlyNum:function (event,name,type,index) {
            if(type == '1'){
                var amount = parseFloat(Vm.form.postVal[index][name]);
                if (event.key !== '.' && isNaN(event.key) && event.key !== 'Backspace' && event.key !== 'Enter') {
                    Vm.form.postVal[index][name] = isNaN(amount) ? '' : amount;
                    return false;
                }
            }else{
                var amount = parseFloat(Vm.form[name]);
                if (event.key !== '.' && isNaN(event.key) && event.key !== 'Backspace' && event.key !== 'Enter') {
                    Vm.form[name] = isNaN(amount) ? '' : amount;
                    return false;
                }
            }
        },
        //纯数字校验 失去焦点事件函数
        onlyNum1:function (event,name,type,index) {
            if(type == '1'){
                var amount = parseFloat(Vm.form.postVal[index][name]);
                if (event.key !== '.' && isNaN(event.key)) {
                    Vm.form.postVal[index][name] = isNaN(amount) ? '' : amount;
                }
            }else{
                var amount = parseFloat(Vm.form[name]);
                if (event.key !== '.' && isNaN(event.key)) {
                    Vm.form[name] = isNaN(amount) ? '' : amount;
                    if (name == 'MAX_WEIGHT') {
                        Vm.form.postVal[Vm.form.postVal.length - 1].WEIGHT2 =  !isNaN(amount)? amount:'';
                    }
                    if (name == 'LENGTH1_START') {
                        if (amount == 0) {
                            Vm.form[name] = '';
                        }
                        if (Vm.form.LENGTH1_END && amount >= Vm.form.LENGTH1_END && Vm.form.LENGTH1_END !== '+∞') {
                            Vm.longestSide = false
                            layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>" + this.$lang('最长边左边要比右边小！') + "</span>");
                        } else {
                            Vm.longestSide = true;
                        }
                    }
                    if (name == 'LENGTH1_END') {
                        if (amount == 0) {
                            Vm.form[name] = '';
                        }
                        if (Vm.form.LENGTH1_START && amount <= Vm.form.LENGTH1_START && Vm.form.LENGTH1_END !== '+∞') {
                            Vm.longestSide = false
                            layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>" + this.$lang('最长边右边要比左边小！') + "</span>");
                        } else {
                            Vm.longestSide = true;
                        }
                    }
                    if (name == 'LENGTH2_START') {
                        if (amount == 0) {
                            Vm.form[name] = '';
                        }
                        if (Vm.form.LENGTH2_END && amount >= Vm.form.LENGTH2_END && Vm.form.LENGTH2_END !== '+∞') {
                            layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>" + this.$lang('第二长边左边要比右边小！') + "</span>");
                            Vm.secondLongestSide = false;
                        } else {
                            Vm.secondLongestSide = true;
                        }
                    }
                    if (name == 'LENGTH2_END') {
                        if (amount == 0) {
                            Vm.form[name] = '';
                        }
                        if (Vm.form.LENGTH2_START && amount <= Vm.form.LENGTH2_START && Vm.form.LENGTH2_END !== '+∞') {
                            layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>>" + this.$lang('第二长边右边要比左边小！') + "</span>");
                            Vm.secondLongestSide = false
                        } else {
                            Vm.secondLongestSide = true;
                        }
                    }
                    if (name == 'LENGTH3_MAX') {
                        if (amount == 0) {
                            Vm.form[name] = '';
                        }
                    }
                    if (name == 'VOLUME_MAX') {
                        if (amount == 0) {
                            Vm.form[name] = '';
                        }
                    }
                }
                return false;
            }
        },
        posttageDiscount:function (event,name) {
            if(isNaN(this.form[name])){
                this.form[name] = ''
            }
        },
        posttageDiscountBlur:function (name) {
            var amount =Vm.form[name];
            if(Number(amount) > 100){
                Vm.form[name] = "";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+this.$lang('请输入100以内的数字')+"</span>");
            }else{
                Vm.form[name] = amount;
            }
        },
        //此函数是，修改编辑页面的函数，获取接口返回过来的数据，进而塞进各对应输入框中
        search:function(){
            var logModeId = $("#logModeId").val();
            var postageId = $("#postageId").val();
            var logModeUrl = "/index.php?g=logistics&m=FreightRules&a=getLogModeData&id="+logModeId;
            axios.get(logModeUrl)
                .then(function(res){
                    Vm.form.logCompany = res.data.logCompany;
                    Vm.form.LOGISTICS_MODE = res.data.LOGISTICS_MODE;
                });
            if(postageId){
                var logModeUrl = "/index.php?g=logistics&m=FreightRules&a=getdetailData&modelID="+postageId
                axios.get(logModeUrl)
                    .then(function(res){
                        Vm.form.DENOMINATED_TYPE = res.data.DENOMINATED_TYPE;    //计价方式
                        Vm.form.COEFFICIENT = res.data.COEFFICIENT;      //计泡系数
                        Vm.form.DAY1 = res.data.DAY1            //时效(天)
                        Vm.form.DAY2 = res.data.DAY2            //时效(天)
                        Vm.form.FIRST_HEAVY_TYPE =  Number(res.data.FIRST_HEAVY_TYPE)    //首重
                        Vm.form.LENGTH1_END = res.data.LENGTH1_END             //最长边
                        Vm.form.LENGTH1_START = res.data.LENGTH1_START        //最长边
                        Vm.form.VOLUME_MAX = res.data.VOLUME_MAX            //体积
                        Vm.form.LENGTH2_END = res.data.LENGTH2_END          //第二场边
                        Vm.form.LENGTH2_START = res.data.LENGTH2_START       //第二场边
                        Vm.form.LENGTH3_MAX = res.data.LENGTH3_MAX          //长宽高之和
                        Vm.form.MAX_WEIGHT = res.data.MAX_WEIGHT      //最大重量
                        Vm.form.MAX_WEIGHT_TYPE = res.data.MAX_WEIGHT_TYPE=="0"?false:true
                        Vm.form.MODEL_NM = res.data.MODEL_NM     //模板名称
                       if(res.data.OUT_AREAS){
                           Vm.form.OUT_AREAS=res.data.OUT_AREAS.split(",")
                       }
                        // Vm.form.OUT_AREAS = res.data.OUT_AREAS;  //出发地
                        Vm.form.OUT_AREAS_DATA = res.data.OUT_AREAS_DATA
                        Vm.form.POSTTAGE_DISCOUNT = res.data.POSTTAGE_DISCOUNT    //运费折扣
                        Vm.form.POSTTAGE_DISCOUNT_DATE_END = res.data.POSTTAGE_DISCOUNT_DATE_END      //运费折扣  有效期
                        Vm.form.POSTTAGE_DISCOUNT_DATE_START = res.data.POSTTAGE_DISCOUNT_DATE_START    //运费折扣  有效期
                        Vm.form.PROCESS_DISCOUNT = res.data.PROCESS_DISCOUNT   //处理费折扣
                        Vm.form.PROCESS_DISCOUNT_DATE_END = res.data.PROCESS_DISCOUNT_DATE_END       //处理费折扣 有效期
                        Vm.form.PROCESS_DISCOUNT_DATE_START = res.data.PROCESS_DISCOUNT_DATE_START   //处理费折扣 有效期
                        Vm.form.REMARK = res.data.REMARK;              //富文本编译器相关
                        Vm.loadUe('show',Vm.form.REMARK);
                        Vm.form.STATE_CODE = res.data.STATE_CODE        //状态  启用，禁用
                        Vm.form.postVal=[];
                        if(res.data.POSTTAGE_VAL){
                            for(keyInterval in res.data.POSTTAGE_VAL){
                                Vm.form.postVal.push(res.data.POSTTAGE_VAL[keyInterval])
                            }
                        }
                        //wait
                        //限制条件相关
                        Vm.form.BAN_ITEM_CAT = res.data.BAN_ITEM_CAT;
                        var domSpan = document.querySelectorAll(".limitedType_items")
                        for(key1 in Vm.form.BAN_ITEM_CAT){
                            for(key2 in domSpan){
                                if(domSpan[key2].innerHTML){
                                    if(Vm.form.BAN_ITEM_CAT[key1] == domSpan[key2].innerHTML ){
                                        addClass(domSpan[key2],'active')
                                    }
                                }
                            }
                        }
                        //已选区域  国家地区
                        // Vm.AlreadyChose;ProvinceStateAdd,CountryStateAdd;
                        var CountryState = res.data.SEND_AREAS
                        for(key in CountryState){
                            if(CountryState[key].area_type == '1'){
                                Vm.CountryStateAdd.push(CountryState[key])
                            }else if(CountryState[key].area_type == '2'){
                                Vm.ProvinceStateAdd.push(CountryState[key])
                            }
                        }
                        for(key in Vm.ProvinceStateAdd){ //循环遍历省份，塞入对应国家
                            var keyIndex = -1;
                            for(key1 in Vm.CountryStateAdd){
                                if(Vm.ProvinceStateAdd[key1]){
                                    if(Vm.ProvinceStateAdd[key].parent_no ==Vm.CountryStateAdd[key1].area_no){
                                        keyIndex = key1;
                                    }
                                }
                            }
                            if(keyIndex > -1){   //此时，有对应国家
                                if( Vm.CountryStateAdd[keyIndex].province){
                                    Vm.CountryStateAdd[keyIndex].province.push(Vm.ProvinceStateAdd[key])
                                }else{
                                    Vue.set(Vm.CountryStateAdd[keyIndex],'province',[])
                                    Vm.CountryStateAdd[keyIndex].province.push(Vm.ProvinceStateAdd[key])
                                }
                            }
                        }
                        for(key in Vm.CountryStateAdd){
                            for(key5 in Vm.All_AREASAll){
                                if(Vm.CountryStateAdd[key].area_no == Vm.All_AREASAll[key5].area_no ){
                                    Vue.set(Vm.All_AREASAll[key5],'province',Vm.CountryStateAdd[key].province);
                                    var CD_VAL =  Vm.All_AREASAll[key5].CD_VAl,data_CD_VAL;
                                    if(CD_VAL){
                                        var index = CD_VAL.indexOf("（")
                                        data_CD_VAL = CD_VAL.substring(0,index);
                                    }else{
                                        data_CD_VAL = "Null"
                                    }
                                    Vue.set(Vm.All_AREASAll[key5],'data_CD_VAL',data_CD_VAL)
                                    Vm.form.AlreadyChose.push(Vm.All_AREASAll[key5])
                                }
                            }
                        }
                        //判断 默认全境还是部分地区
                        if(Vm.form.AlreadyChose){
                            for(key in Vm.form.AlreadyChose){
                                if(typeof Vm.form.AlreadyChose[key].province == 'object'){
                                //判断部分还是默认全境
                                    if(Vm.form.AlreadyChose[key].province.length>0){
                                        var areaNo = Vm.form.AlreadyChose[key].province[0].parent_no;
                                        var areaNoLength = 0;
                                        var url = "/index.php?g=logistics&m=FreightRules&a=getAreaData&area_no="+areaNo;
                                        $.ajax({
                                            type: "POST",
                                            url: url,
                                            async: false,
                                            dataType: "json",
                                            success: function (res) {
                                                areaNoLength = res.length;
                                                if (areaNoLength > Vm.form.AlreadyChose[key].province.length) {
                                                    if (Vm.form.AlreadyChose[key].apart) {
                                                        Vm.form.AlreadyChose[key].apart = true;
                                                    }
                                                    else {
                                                        Vue.set(Vm.form.AlreadyChose[key], 'apart', true)
                                                    }
                                                } else if (areaNoLength == Vm.form.AlreadyChose[key].province.length) {
                                                    if (Vm.form.AlreadyChose[key].apart) {
                                                        Vm.form.AlreadyChose[key].apart = false
                                                    }
                                                }
                                            }
                                        })
                                    }else{
                                        if(Vm.form.AlreadyChose[key].apart){
                                            Vm.form.AlreadyChose[key].apart =false
                                        }
                                    }
                                }
                                 // 去除未选区域 洲的国家
                                 for(key1 in Vm.HOT_AREAS){
                                       if(Vm.HOT_AREAS[key1].area_no == Vm.form.AlreadyChose[key].area_no){
                                             Vm.HOT_AREAS.splice(key1,1)
                                           }
                                       }
                                  for(key2 in Vm.SEND_AREAS){
                                        for(key3 in Vm.SEND_AREAS[key2]){
                                            if(Vm.SEND_AREAS[key2][key3]){
                                                if(Vm.SEND_AREAS[key2][key3].area_no == Vm.form.AlreadyChose[key].area_no ){
                                                    Vm.SEND_AREAS[key2].splice(key3,1)
                                                }
                                            }
                                        }
                                  }
                            }
                        }
                    })
            }
        },
        //提交模板  新增&&编辑
        addPostageTemp:function(){
            var _this = this;
            var LOGISTICS_MODEL_ID = $("#logModeId").val();
            var postageId = $("#postageId").val(); 
            var MEETING_CONTENT = UE.getEditor('editor').getContent();
             Vm.form.REMARK = MEETING_CONTENT;
            var dataArr =[];
            var dataVal = document.querySelectorAll(".rule_add_limited .limitedType_items");
            for(var i=0;i<dataVal.length;i++){
                if(hasClass(dataVal[i],"active")){
                    dataArr.push(dataVal[i].getAttribute("value"))
                }
            }
            Vm.form.BAN_ITEM_CAT = dataArr;
            Vm.form.LOGISTICS_MODEL_ID = LOGISTICS_MODEL_ID;
            var param = Vm.form;
            var editTempUrl = "/index.php?g=logistics&m=FreightRules&a=editPostageTemp&postageId="+postageId;
            var addTempUrl = "/index.php?g=logistics&m=FreightRules&a=addPostageTemp";
           if(Vm.form.LENGTH1_START && Vm.form.LENGTH1_END){
                if(Number(Vm.form.LENGTH1_START) >= Number(Vm.form.LENGTH1_END)){
                    Vm.longestSide =false
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+this.$lang('最长边，左边要小于右边！')+"</span>");
                }else{
                    Vm.longestSide =true
                }
           }
            if(Vm.form.LENGTH2_START && Vm.form.LENGTH2_END){
                if(Number(Vm.form.LENGTH2_START) >= Number(Vm.form.LENGTH2_END)){
                    Vm.secondLongestSide =false
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+this.$lang('第二长边，左边要小于右边！')+"</span>");
                }else{
                    Vm.secondLongestSide =true
                }
            }
            if(Vm.secondLongestSide && Vm.longestSide){
                if (postageId) {
                    axios.post(editTempUrl,{param:param})
                        .then(function (res) {
                            if(res.data.code == '200'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+_this.$lang('操作成功')+"</span>");
                                setTimeout(function () {
                                    var trackurl = '/index.php?g=logistics&m=FreightRules&a=rule_detail&modelID='+postageId+'&logModeId='+LOGISTICS_MODEL_ID;
                                    var route = document.createElement("a");
                                    route.setAttribute("style", "display: none");
                                    route.setAttribute("onclick", "changenewtab(this,'"+_this.$lang('规则详情页')+"')");
                                    route.setAttribute("_href", trackurl);
                                    route.click();
                                },1000)
                            }else if(res.data.code == '501'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+_this.$lang(res.data.data)+_this.$lang('重复了！')+"</span>");
                            }
                            else if(res.data.code == '500'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+_this.$lang(res.data.data)+"</span>");
                            }
                        })
                }else{
                    axios.post(addTempUrl,{param:param})
                        .then(function(res){
                            if(res.data.code == '200'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+_this.$lang('操作成功')+"</span>");
                                var dataState = res.data.data;
                                if(dataState.length>0){
                                    dataState = dataState.split(",")
                                }
                                setTimeout(function () { 
                                    var trackurl = '/index.php?g=logistics&m=FreightRules&a=rule_detail&modelID='+dataState[0]+'&logModeId='+dataState[1];
                                    var route = document.createElement("a");
                                    route.setAttribute("style", "display: none");
                                    route.setAttribute("onclick", "changenewtab(this,'" +_this.$lang('规则详情页')+ "')");
                                    route.setAttribute("_href", trackurl);
                                    route.click();
                                },1000)
                            }else if(res.data.code == '501'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+_this.$lang(res.data.data)+_this.$lang('重复了！')+"</span>");
                            }
                            else if(res.data.code == '500'){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red'>X</i>"+_this.$lang(res.data.data)+"</span>");
                            }
                        })
                }
            }
        },
        //价格详情，新增续重
        addNewContinued:function () {
            Vm.form.postVal.push( { WEIGHT1: '', WEIGHT2: '', COST: '', PROCESS_WEIGHT: '', PROCESS_COST: '', } )
        },
        //价格详情，删除续重
        deleteNewContinued:function (){
            if(Vm.form.postVal.length >1){
                Vm.form.postVal.pop();
            }else{
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('请保留一个区间')+"</span>");
            }
        },
        //限制条件 单选交互
        limitedTypeItems:function () {
            var event = event || window.event;
            var target = event.target || event.srcElement;
            var allItems = document.querySelectorAll(".limitedType_items")
            var totalItems = document.querySelector(".limitedType_all")
            if(hasClass(target, "active")){
                removeClass (target, "active")
            }else{
                addClass(target, "active");
            }
            var index=0;
            for(key in allItems){
                if(hasClass(allItems[key], "active")){
                    index++
                }
                if(index == allItems.length){
                    addClass(totalItems, "active");
                }else{
                    removeClass(totalItems, "active");
                }
            }
        },
        //限制条件 全选交互
        limitedTypeAll:function () {
            var event = event || window.event;
            var target = event.target || event.srcElement;
            var allItems = document.querySelectorAll(".limitedType_items")
            if(hasClass(target, "active")){
                removeClass (target, "active")
                for(key in allItems){
                    removeClass(allItems[key],"active")
                }
            }else{
                addClass(target, "active");
                for(key in allItems){
                    addClass(allItems[key],"active")
                }
            }
        },
        //国家地区，加减号，显示隐藏部分
        AreaDisplay:function(data,type){
            if(type == 'show'){
                Vue.set(data,'show',true)
            }else{
                Vue.set(data,'show',false)
            }
        },
        //热门区域 全选
        hotAreaChoseAll:function (data) {
            for(key in data){
                Vm.form.AlreadyChose.push(data[key])
            }
            Vm.HOT_AREAS = [];
        },
        //热门区域 单选
        hotAreaChoseItems:function(data,index) {
            Vm.form.AlreadyChose.push(data);
            Vm.HOT_AREAS.splice(index, 1)
        },
        //洲  全选
        areaChoseAll:function (data,index,data1) {
            for(key in data){
                Vm.form.AlreadyChose.push(data[key])
            }
            var dataArr=[];
            data1.zh_name="";
            dataArr.push(data1)
            Vm.SEND_AREAS.splice(index,1,dataArr);
        },
        //洲  单选
        areaChoseItems:function (data,index1,index) {
            Vm.form.AlreadyChose.push(data);
            if(Vm.SEND_AREAS[index].length == 1){
                var dataArr=[];
                data.zh_name="";
                dataArr.push(data)
                Vm.SEND_AREAS[index].splice(index1, 1,dataArr)
            }else{
                Vm.SEND_AREAS[index].splice(index1, 1)
            }
        },
        //已选  取消
        deleteAreaChoseItems : function (k,index) {
            if(k.rank < 31){
                Vm.HOT_AREAS.push(k)
            }else{
                for(key in Vm.SEND_AREAS){
                    if(Vm.SEND_AREAS[key][0]){
                        if(Vm.SEND_AREAS[key][0].CD == k.CD){
                            Vm.SEND_AREAS[key].push(k);
                            break;
                        }else{
                            continue;
                        }
                    }else{
                        Vm.SEND_AREAS[key].push(k);
                        break;
                    }
                }
            }
            Vm.form.AlreadyChose.splice(index,1)
        },
        //已选区域，全不选操作按钮，将已选区域中的数据放回到待选区域
        AlreadyChoseAllBack:function () {
            Vm.HOT_AREAS = [...Vm.stash_data.hot]
            Vm.SEND_AREAS = Vm.stash_data.send.map(item => [...item]);
            Vm.form.AlreadyChose=[];
        },
        //关闭区域选择弹框弹框
        popAreaHide:function () {
            Vm.popArea_status = false;
        },
        //已选区域， 部分地区选择 展示弹窗
        AlreadyChoseProvince:function (k) {
            var event = event || window.event;
            var target = event.target || event.srcElement;
            event.stopPropagation();
            event.preventDefault();
            Vm.PopEreaProvince = k.area_no;
            Vm.PopEreaCountry= k.zh_name;
            var url = "/index.php?g=logistics&m=FreightRules&a=getAreaData&area_no="+k.area_no;
            axios.post(url)
              .then(function(res){
                  var dataIndex=0;
                  for(key in Vm.form.AlreadyChose){
                      if(Vm.form.AlreadyChose[key].province){}
                      else{
                          Vue.set(Vm.form.AlreadyChose[key],'province',[])
                      }
                  }
                  for(key in res.data){      //先判断，暂存的ProvinceStateChose是否有于接口返回来的数据相同的部分
                      if(Vm.ProvinceStateChose.indexOf(res.data[key]) >=0){
                          dataIndex++;
                      }
                  }
                  //此时， dataIndex有两种情况，一是ProvinceStateChose无数据，二是有数据，但是是另一个国家
                  if(dataIndex == 0 ){
                      Vm.ProvinceState=[];
                      for(key in res.data){
                          if(k.province.length > 0){      //有数据
                              Vm.ProvinceStateChose = k.province;
                              var ProvinceStateChoseIndex=0;
                                  for(provinceKey in k.province){
                                      if(k.province[provinceKey].area_no == res.data[key].area_no){
                                          ProvinceStateChoseIndex++;
                                      }
                                  }
                              if(ProvinceStateChoseIndex == 0){
                                  Vm.ProvinceState.push(res.data[key])
                              }
                          }else{  //无数据
                              Vm.ProvinceStateChose = res.data;
                          }
                      }
                   //此时，ProvinceStateChose有数据，而且还是之前打开窗口的哪个国家
                  }else{
                      Vm.ProvinceState = [];
                      for(key in res.data){
                          if(Vm.ProvinceStateChose.indexOf(res.data[key]) < 0){
                              Vm.ProvinceState.push(res.data[key])
                          }
                      }
                  }
                  Vm.popArea_status = true;
                  if(Vm.ProvinceState.length > 20){
                      Vm.ChoseProvinceNot =true
                  }else{
                      Vm.ChoseProvinceNot = false
                  }
                  if(Vm.ProvinceStateChose.length>20){
                      Vm.ChoseProvinceYet = true
                  }else{
                      Vm.ChoseProvinceYet = false
                  }
              })
        },
        // 已选国家选择地区省份 单选
        popChoseProvince:function (data,number){
            Vm.ProvinceState.splice(number,1)
            Vm.ProvinceStateChose.push(data)
            if(Vm.ProvinceState.length > 20){
                Vm.ChoseProvinceNot =true
            }else{
                Vm.ChoseProvinceNot = false
            }
            if(Vm.ProvinceStateChose.length > 20){
                Vm.ChoseProvinceYet = true
            }else{
                Vm.ChoseProvinceYet = false
            }
        },
        //  已选国家选择地区省份  全选
        popChoseProvinceAll:function (data) {
           for(key in data){
                if(Vm.ProvinceStateChose.indexOf(data[key])<0){
                    Vm.ProvinceStateChose.push(data[key])
                }
           }
            Vm.ProvinceState=[];
            if(Vm.ProvinceState.length > 20){
                Vm.ChoseProvinceNot =true
            }else{
                Vm.ChoseProvinceNot = false
            }
            if(Vm.ProvinceStateChose.length>20){
                Vm.ChoseProvinceYet = true
            }else{
                Vm.ChoseProvinceYet = false
            }
        },
        //  已选国家选择地区省份  取消选择
        popChoseProvinceBack:function (data) {
            var parent_no = data.parent_no;
            if( Vm.ProvinceStateChose.length>1){
                Vm.ProvinceStateChose.splice(Vm.ProvinceStateChose.indexOf(data),1)
                Vm.ProvinceState.push(data);
            }else{
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('请保留至少一个地区')+"</span>");
            }
            if(Vm.ProvinceState.length > 20){
                Vm.ChoseProvinceNot =true
            }else{
                Vm.ChoseProvinceNot = false
            }
            if(Vm.ProvinceStateChose.length > 20){
                Vm.ChoseProvinceYet = true
            }else{
                Vm.ChoseProvinceYet = false
            }
        },
        //弹框 已选国家选择地区省份，全不选
        popChoseProvinceAllBack:function (data) {
            for(key in data){
                if(Vm.ProvinceState.indexOf(data[key])<0){
                    Vm.ProvinceState.push(data[key])
                }
            }
            Vm.ProvinceStateChose=[];
            if(Vm.ProvinceState.length > 20){
                Vm.ChoseProvinceNot =true
            }else{
                Vm.ChoseProvinceNot = false
            }
            if(Vm.ProvinceStateChose.length > 20){
                Vm.ChoseProvinceYet = true
            }else{
                Vm.ChoseProvinceYet = false
            }
        },
        // 已选国家选择地区省份 保存 
        popAreaStatusSubmit:function(){
           var  PopEreaProvince=[]
            for(key in Vm.PopEreaProvince){
                PopEreaProvince.push(Vm.PopEreaProvince[key].zh_name)
            }
            if(Vm.ProvinceStateChose.length>0){
                var index = 0;
                for(key in Vm.form.AlreadyChose){
                    if(Vm.form.AlreadyChose[key].area_no == Vm.ProvinceStateChose[0].parent_no ){
                        index = key;
                    }
                }
                for(key1 in Vm.ProvinceStateChose){
                    if(Vm.form.AlreadyChose[index].province.indexOf(Vm.ProvinceStateChose[key1]) <0){
                        Vm.form.AlreadyChose[index].province.push(Vm.ProvinceStateChose[key1])
                    }
                }
                for(key1 in Vm.ProvinceState){
                    if(Vm.form.AlreadyChose[index].province.indexOf(Vm.ProvinceState[key1]) > -1){
                        var number = Vm.form.AlreadyChose[index].province.indexOf(Vm.ProvinceState[key1])
                        Vm.form.AlreadyChose[index].province.splice(number-1,1)
                    }
                }
            }else{
                var area_no = Vm.ProvinceState[0].parent_no;
                for(key in Vm.form.AlreadyChose){
                    if(Vm.form.AlreadyChose[key].area_no == area_no){
                        Vm.form.AlreadyChose[key].province=[];
                    }
                }
            }
            var url = "/index.php?g=logistics&m=FreightRules&a=getAreaData&area_no="+Vm.PopEreaProvince;
            axios.post(url)
                .then(function(res) {
                    for (key in Vm.form.AlreadyChose) {
                        if (Vm.form.AlreadyChose[key].area_no == res.data[0].parent_no) {
                            if (Vm.form.AlreadyChose[key].province.length ==  res.data.length) {
                                if (Vm.form.AlreadyChose[key].apart) {
                                    Vm.form.AlreadyChose[key].apart = false;
                                }
                                else {
                                    Vue.set(Vm.form.AlreadyChose[key], 'apart', false)
                                }
                            } else {
                                Vm.form.AlreadyChose[key].apart = Vm.form.AlreadyChose[key].apart ? true : Vue.set(Vm.form.AlreadyChose[key], 'apart', true)
                            }
                        }
                    }
                })
            Vm.popArea_status= false
        },
        //  已选国家选择地区省份 取消关闭弹框
        popAreaStatusCancel:function () {
            Vm.popArea_status= false
        },
        //选择目的地  输入框 确认搜索
        InputChoseCountry:function () {
            var value= document.querySelector(".destination_searchInput").value.replace(/\s+/g, "");  //拿到input中的数据
            // var value = ['中国','日本','韩国','美国','安徽','辽宁','爱媛','大阪','胡志明市','噜噜噜噜']
            var valueIndex =0;
            var  inputCountry = [];    //用来存放能够找到的国家
            var  inputCountryAlready = [];    // 已经存在的国家;
            var  inputCountryToProvince = [];    // 已经存在的国家;
            var  inputProvince = [];    //用来存放能够找到的省份
            var  inputProvinceAlready = [];    //已经存在的省份
            var  inputWrongPlace = [];    //找不到的国家或地区
            if(value.indexOf("，") > -1){
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>'"+this.$lang('请用英文逗号')+",'</span>'");
                valueIndex++;
            }
            else if(value.indexOf(",") > -1){
                value = value.split(",")       //将value中的数据转为化数组
                value.join(",")
            }else if(value.length <=0 ){
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>"+this.$lang('请输入搜索目的地')+"</span>");
                valueIndex++;
            }
            var url ='/index.php?g=logistics&m=FreightRules&a=search_Destination&searchStr='+value;
            axios.post(url)
                .then(function(res){
                    Vm.resultSuccessStatesCountry =[];
                    Vm.resultSuccessStatesProvince=[];
                    for(key in res.data.country){
                        var CD_VAL =  res.data.country[key].CD_VAl,data_CD_VAL;
                        var inputCountryItems = res.data.country[key]
                        if(CD_VAL){
                            var index = CD_VAL.indexOf("（")
                            data_CD_VAL = CD_VAL.substring(0,index);
                        }else{
                            data_CD_VAL = "Null"
                        }
                        Vue.set(inputCountryItems,'data_CD_VAL',data_CD_VAL)
                        Vue.set(inputCountryItems,'province',[])
                        inputCountry.push(inputCountryItems)    //获取搜索到的国家的数据
                    }
                    for(key in res.data.area){
                        if(res.data.area[key].area_type == '2'){
                            inputProvince.push(res.data.area[key])    //获取搜索到的地区的数据
                        }else{
                            inputWrongPlace.push(res.data.area[key].zh_name)
                        }
                    }
                    for(key in res.data.error){
                        inputWrongPlace.push(res.data.error[key]);
                    }
                    // 获取错误 地方 的数据
                    if(Vm.form.AlreadyChose.length) {
                        for (key3 in inputCountry) {
                            var index = 0;
                            for (key4 in Vm.form.AlreadyChose) {     //判断已选区域是否包含 该国家
                                if (inputCountry[key3].area_no == Vm.form.AlreadyChose[key4].area_no) {
                                    index++;
                                }
                            }
                            if (index == 0) {
                                Vm.form.AlreadyChose.push(inputCountry[key3])
                                Vm.resultSuccessStatesCountry.push(inputCountry[key3])
                            } else {
                                inputCountryAlready.push(inputCountry[key3])
                            }
                        }
                    }else{
                        for(key3 in inputCountry){
                            Vm.form.AlreadyChose.push(inputCountry[key3])
                            Vm.resultSuccessStatesCountry.push(inputCountry[key3])
                        }
                    }
                    // 搜索国家信息已完成。
                    // 搜索地区信息开始。
                    setTimeout(function () {
                        if(Vm.form.AlreadyChose){   //先判断已选区域是否有国家数据
                            for(key5 in inputProvince){
                                var index = -1
                                for(key6 in Vm.form.AlreadyChose){
                                    if(inputProvince[key5].parent_no == Vm.form.AlreadyChose[key6].area_no){
                                        index = key6;
                                    }
                                }
                                if(index < 0){    //此时说明，已选区域 没有省所在的国家
                                    var All_AREASAllIndex =-1;     //则需要从总数组中拿到国家的信息
                                    for(key2 in Vm.All_AREASAll){
                                        if(Vm.All_AREASAll[key2].area_no ==inputProvince[key5].parent_no ) {
                                            All_AREASAllIndex = key2;
                                        }
                                    }
                                    if(All_AREASAllIndex > -1){
                                        var  AlreadyChoseItems = Vm.All_AREASAll[All_AREASAllIndex];
                                        Vue.set(AlreadyChoseItems,'province',[inputProvince[key5]])
                                        Vue.set(AlreadyChoseItems,'apart',true)
                                        Vm.form.AlreadyChose.push(AlreadyChoseItems)
                                        Vm.resultSuccessStatesProvince.push(inputProvince[key5])
                                    }

                                }else{        //此时说明，已选区域 有省所在的国家
                                    if(Vm.form.AlreadyChose[index].province){
                                        var parent_noIndex = 0;
                                        var parent_Index = 0;
                                        for(key8 in Vm.form.AlreadyChose[index].province) {  //先判断是否已选区域国家省里面包含该省
                                            if (Vm.form.AlreadyChose[index].province[key8].area_no == inputProvince[key5].area_no) {
                                                parent_noIndex++;
                                            }
                                        }
                                        if(parent_noIndex == 0){
                                            Vm.resultSuccessStatesProvince.push(inputProvince[key5])
                                            if(Vm.form.AlreadyChose[index].apart){
                                                Vm.form.AlreadyChose[index].province.push(inputProvince[key5])
                                            }else{
                                                Vue.set( Vm.form.AlreadyChose[index],'apart',true)
                                                Vm.form.AlreadyChose[index].province.push(inputProvince[key5])
                                            }
                                        }else{
                                            inputProvinceAlready.push(inputProvince[key5])
                                        }
                                    }else{
                                        Vm.resultSuccessStatesProvince.push(inputProvince[key5])
                                        Vue.set(Vm.form.AlreadyChose[index],'apart',true)
                                        Vue.set(Vm.form.AlreadyChose[index],'province',[inputProvince[key5]])
                                    }
                                }
                            }
                        }else{
                            for(key7 in inputProvince){
                                var All_AREASAllIndex =-1;     //则需要从总数组中拿到国家的信息
                                for(key2 in Vm.All_AREASAll){
                                    if(Vm.All_AREASAll[key2].area_no == Vm.inputProvince[key5].parent_no ) {
                                        All_AREASAllIndex = key2;
                                    }
                                }
                                if(All_AREASAllIndex > -1){
                                    var  AlreadyChoseItems = Vm.All_AREASAll[All_AREASAllIndex];
                                    Vue.set(AlreadyChoseItems,'province',[inputProvince[key5]])
                                    Vue.set(AlreadyChoseItems,'apart',true)
                                    Vm.form.AlreadyChose.push(AlreadyChoseItems)
                                    Vm.resultSuccessStatesProvince.push(inputProvince[key7])
                                }
                            }
                        }
                    },100)
                    Vm.resultSuccessStatesCountryYet=inputCountryAlready, // 导入结果提醒   已存在国家
                    Vm.resultSuccessStatesProvinceYet　= inputProvinceAlready, // 导入结果提醒   已存在省
                    Vm.resultFalseStates = inputWrongPlace
                    if(valueIndex == 0){
                        Vm.popArea_result =true
                    }
                    for(key11 in Vm.form.AlreadyChose){
                        var key12Index= -1,key13Index=-1,key15Index=-1;
                        for(key12 in Vm.SEND_AREAS){
                            for(key13 in  Vm.SEND_AREAS[key12]){
                                 if(Vm.SEND_AREAS[key12][key13].area_no ==Vm.form.AlreadyChose[key11].area_no ){
                                     key12Index = key12;key13Index =key13;
                                 }
                            }
                        }
                        for(key15 in Vm.HOT_AREAS){
                            if(Vm.HOT_AREAS[key15].area_no ==Vm.form.AlreadyChose[key11].area_no ){
                                key15Index =key15;
                            }
                        }
                        if(key15Index > -1){
                            Vm.HOT_AREAS.splice(key15Index,1)
                        }
                        if(key12Index>-1 && key13Index > -1){
                            Vm.SEND_AREAS[key12Index].splice(key13Index,1)
                        }
                    }
                })
        },
        popAreaHidestatus:function () {
            Vm.popArea_result =false
        },
    },
});
//判断是否有某个class
function hasClass(obj, cls) {
    if(typeof obj == "object"){
        return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
    }
};
//添加class
function addClass(obj, cls) {
    if (!this.hasClass(obj, cls)) obj.className += " " + cls;
};
//删除class
function removeClass (obj, cls) {
    if (hasClass(obj, cls)) {
        var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
        obj.className = obj.className.replace(reg, ' ');
    }
}
