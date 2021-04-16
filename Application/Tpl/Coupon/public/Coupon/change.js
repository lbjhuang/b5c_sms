/**
 * Created by b5m on 2017/12/7.
 */
var vm = new Vue({
    el:"#couponAdd",
    data:{
        // 适用店铺
        suitableShopData:'',
        // 发放对象
        sendObjectItems:{
            allUser:false,
            manualSelection:false,
            excelUser:false,
        },
        useLimitItems:false,
        // 优惠券类型
        useCategoriesItems:{
            money:false,
            vouche:false,
        },
        // 使用时间
        useTimesItems:{
            absolute:false,
            relative:false,
        },
        // 发放方式
        useSendWayItems:{
            empty:true,
            once:false,
            continue:false,
        },
        sendContinuteObjectVip:false,
        useSuitableRangeItems:false,
        couponAddPopManual:false,
        partsGoodsItems:false,
        suitableShopNone:true,
        useSendWayItemsNone:true,
        ObjectmanualData:{
            email:'',
            mobile:'',
            nickName:'',
            userId:'',
        },
        ObjectGudsData:{
            gudsName:'',
            skuId:'',
            spuId:'',
        },
        pageManul:{
            sePage:0,
            displayLength:0,
            pageLength:0,
            pageSize:0,
        },
        pageGuds:{
            sePage:0,
            displayLength:0,
            pageLength:0,
            pageSize:0,
        },
        couponAddPopManualSaveData:[],
        couponAddPopManualSaveDataAll:'',
        couponAddPopGudsSaveData:[],
        couponAddPopGudsSaveDataDetail:'',
        couponAddPopGudsSaveDataAll:'',
        excelUploadNum:0,
        excelUploadData:'',
        form:{
            id:'',
            title:'',
            shop:'',
            sendWay:'',
            sendObject:'',
            orderNum:'',
            threshold:'',
            thresholdCondition:'',
            couponType:'',
            maxAmount:'',
            proportion:'',
            timeType:'',
            start_time:'',
            end_time:'',
            timeValue:'',
            superpositionRule:'',
            useRange:'',
            users:'',
            products:'',
            timeHourValue:'',
            timeMinuteValue:'',

        },
        objectchosePlatState1:true,
        objectchoseWayState2:true,
        objectchoseState:false,
        seSuitableRangeItemsAll:false,
        objectchoseMenulStateparts:0,
        useSuitableRangeItemsparts:0,
        useSuitableRangeItemsAll:false,
        manualPage:true,
        goodesPage:true,
        manualPopstate1:"",
        manualPopstate2:"",
        goodesPopstate1:"",
        goodesPopstate2:"",
    },
    created:function () {
        localStorage.setItem('page',"优惠券修改页")
        setTimeout(function () {
           vm.updataData();
        },100)
    },
    methods: {
        updataData:function () {
            var data=document.querySelector('#jsonData').getAttribute('data-value');
            data = JSON.parse(data);
            vm.form.id=data.id;
            vm.form.title=data.title;
            vm.form.couponType=data.couponType;
            vm.form.sendWay=data.sendWay;
            vm.form.sendObject=data.sendObject;
            vm.form.orderNum=data.orderNum;
            vm.suitableShopData=data.shop;
            vm.form.threshold=data.threshold;
            vm.form.thresholdCondition=data.threshold_condition;
            vm.form.proportion=data.proportion;
            vm.form.maxAmount=data.maxAmount;
            vm.form.timeType=data.usedTimeType;
            vm.form.superpositionRule=data.uperpositionRule;
            vm.form.useRange=data.useRange;
            vm.form.users=document.querySelector('input[name="users"]').value
            vm.form.products=document.querySelector('input[name="products"]').value;
            vm.couponAddPopManualSaveData = vm.form.users.split(',');
            // console.log( vm.form.users,vm.form.products)
            vm.couponAddPopGudsSaveData = vm.form.products.split(',');
            //使用时间 展示相关
            if(data.usedTimeType == '1'){
                var usedTimeValue = data.usedTimeValue;
                var usedTimeValueIndex=data.usedTimeValue.indexOf('_');
                var start_time = usedTimeValue.substring(0,usedTimeValueIndex);
                var end_time = usedTimeValue.substring(usedTimeValueIndex+1,usedTimeValue.length);
                vm.form.start_time = start_time;
                vm.form.end_time = end_time;
                vm.useTimesItems.absolute=true;
            }else {
                var timeVal = data.usedTimeValue.split("-");
                vm.form.timeValue=timeVal[0];
                vm.form.timeHourValue=timeVal[1];
                vm.form.timeMinuteValue=timeVal[2];
                console.log(timeVal[0],timeVal[1],timeVal[3])
                vm.useTimesItems.relative=true;
            }
            //适用店铺 展示相关
            var formDataArr = data.shop.split(",");
            var formData=[];
            for(key in formDataArr){
                if(formDataArr[key]){
                    formData.push(formDataArr[key])
                }
            }
            vm.form.shop=formData.join(',')
            var formDataInput = document.querySelectorAll('#shop li')
            for(key in formDataInput){
                if(typeof (formDataInput[key]) =='object'){
                    var index= formData.indexOf(formDataInput[key].querySelector('input').value);
                    if(index > -1){
                        formDataInput[key].querySelector('input').checked= true;
                        addClass(formDataInput[key].querySelector('span'),'active');
                        document.querySelector('select[name="sendWay"]').disabled=false;
                        vm.useSendWayItemsNone = false;
                    }
                }
            }
            //发放对象 展示相关
            var sendWayData=data.sendWay;
            switch (data.sendWay){
                case '':
                    vm.useSendWayItems.once = false;
                    vm.useSendWayItems.continue = false;
                    vm.useSendWayItems.empty = true;
                    break;
                case '1':
                    vm.useSendWayItems.once = true;
                    vm.useSendWayItems.continue = false;
                    vm.useSendWayItems.empty = false;
                    setTimeout(function () {
                      document.querySelector('select[name="sendObject"]').value =data.sendObject;
                        vm.sendObjectItemsChange()
                    },100)
                    break;
                case '2':
                    vm.useSendWayItems.once = false;
                    vm.useSendWayItems.empty = false;
                    vm.useSendWayItems.continue = true;
                    setTimeout(function () {
                        document.querySelector('select[name="sendObject"]').value =data.sendObject;
                        vm.sendObjectItemsChange()
                    },100)
                    break;
                }
                //使用门槛 展示相关
            vm.useLimitChange(data.threshold);
            //优惠券类型	 展示相关
            vm.useCategoriesChange(data.couponType);
            //适用范围 展示相关
            vm.useSuitableRangeChange(data.useRange)
            if(data.shop){
                vm.objectchosePlatState1=false
            }
            if(data.sendWay){
                vm.objectchoseWayState2=false;
            }
        },
        //用户弹框翻页
        handleCurrentChange:function(val) {
            if(vm.manualPage){
                vm.ObjectmanualSelection(vm.manualPopstate1,vm.manualPopstate2,val)
            }
        },
        handleCurrentChangeGuds:function (val) {
            vm.pageGuds.sePage=val;
            if(vm.goodesPage){
                vm.partsGoodsSelection(val,vm.goodesPopstate1,vm.goodesPopstate2)
            }
        },
        partsGoodsSelectionClose:function () {
            vm.partsGoodsItems=false;
        },
        ObjectmanualSelectionClose:function () {
            vm.couponAddPopManual=false;
        },
        // 适用店铺
        suitableShop: function () {
            var event = event || window.event;
            var target = event.target;
            if (hasClass(target.nextSibling, 'active')) {
                removeClass(target.nextSibling, 'active')
            } else {
                addClass(target.nextSibling, 'active')
            }
            var parentTarget =  event.target.parentNode.parentNode.querySelectorAll('input');
            var parentTargetIndex = 0,parentTargetArry = [];
            for(key in parentTarget){
                if(parentTarget[key].checked){
                    parentTargetIndex ++;
                    parentTargetArry.push(parentTarget[key].value);
                }
            }
            if(parentTargetIndex > 0){
                parentTargetStr = parentTargetArry.join(",")
                vm.suitableShopData = parentTargetStr;
                vm.objectchosePlatState1 = false;
                if(vm.objectchoseWayState2){
                    vm.objectchoseState=true;
                }
                else{
                    vm.objectchoseState=false;
                }
            }else{
                vm.suitableShopData = '';
                vm.objectchosePlatState1 = true;
                vm.objectchoseState=true;
            }
            uploader = WebUploader.create({
                swf: '/Application/Tpl/Home/Public/lib/webuploader/0.1.5/Uploader.swf',
                server: '/index.php?g=coupon&m=coupon&a=getExcelData&shop='+vm.suitableShopData,
                pick: {id:'#import-goods'},
                auto : true,
                duplicate:true,
            });
            uploader.on('uploadSuccess',function (file,res) {
                utils.lazy_loading();
                if(vm.suitableShopData){
                    // console.log(res)
                    if(res.code == '2000') {
                        layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>导入成功</span>");
                        vm.excelUploadNum=res.data.total;
                        var data=res.data.list;
                        var excelUploadDataArry=[]
                        for(key in data){
                            excelUploadDataArry.push(data[key].email)
                        }
                        vm.excelUploadData=excelUploadDataArry.join(',');
                        // console.log(vm.excelUploadData)
                    }else {
                        // console.log(res)
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>"+res.data+"</span>");
                    }
                }else{
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请选择店铺</span>");
                }
            })
            uploader.on('uploadStart',function (file) {
                utils.lazy_loading('show');
            })
        },
        //一次性  发放对象
        sendObjectItemsChange:function () {
            var targetVal=document.querySelector('select[name="sendObject"]').value
            switch (targetVal){
                case '':
                    vm.sendObjectItems.allUser = false;
                    vm.sendObjectItems.manualSelection=false;
                    vm.sendObjectItems.excelUser=false;
                    break;
                case 'N001850100':
                    vm.sendObjectItems.allUser = true;
                    vm.sendObjectItems.manualSelection=false;
                    vm.sendObjectItems.excelUser=false;
                    var sendObjectItemsAllUrl = '/index.php?g=coupon&m=coupon&a=getUserData'
                    var parms={
                        shop:vm.suitableShopData,
                    }
                    parms = JSON.stringify(parms);
                    axios.post(sendObjectItemsAllUrl,parms)
                        .then(function(res) {
                            var dataTotal = res.data.data.total
                            document.querySelector('input[name="users"]').value = dataTotal;
                            document.querySelector('.use-object-items-num i').innerHTML = dataTotal;
                            vm.couponAddPopManualSaveDataAll=dataTotal;
                        })
                    break;
                case 'N001850200':
                    vm.sendObjectItems.allUser = false;
                    vm.sendObjectItems.manualSelection= true;
                    vm.sendObjectItems.excelUser=false;
                    var arrMenual = [];
                    for(key in vm.couponAddPopManualSaveData){
                        if(vm.couponAddPopManualSaveData[key]){
                            arrMenual.push(vm.couponAddPopManualSaveData[key])
                        }
                    }
                    vm.objectchoseMenulStateparts = arrMenual.length;
                    break;
                case 'N001850300':
                    vm.sendObjectItems.allUser = false;
                    vm.sendObjectItems.manualSelection=false;
                    if(vm.suitableShopData){
                        vm.sendObjectItems.excelUser=true;
                        setTimeout(function () {
                            uploader = WebUploader.create({
                                swf: '/Application/Tpl/Home/Public/lib/webuploader/0.1.5/Uploader.swf',
                                server: '/index.php?g=coupon&m=coupon&a=getExcelData&shop='+vm.suitableShopData,
                                pick: {id:'#import-goods'},
                                auto : true,
                                duplicate:true,
                            });
                            uploader.on('uploadSuccess',function (file,res) {
                                utils.lazy_loading();
                                // console.log(res)
                                if(res.code == '2000') {
                                    layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>导入成功</span>");
                                    vm.excelUploadNum=res.data.total;
                                    var data=res.data.list;
                                    var excelUploadDataArry=[]
                                    for(key in data){
                                        excelUploadDataArry.push(data[key].email)
                                    }
                                    vm.excelUploadData=excelUploadDataArry.join(',');
                                }else {
                                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>"+res.data+"</span>");
                                }
                            })
                            uploader.on('uploadStart',function (file) {
                                utils.lazy_loading('show');
                                if(vm.suitableShopData){}
                                else{
                                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red;'>x</i>请选择店铺</span>");
                                    return false;
                                }
                            })
                        },50)
                    }else{
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color:red;'>x</i>请选择店铺</span>");
                    }
                    break;
            }
        },
        // 使用门槛
        useLimitChange:function (targetVal) {
            if(targetVal){}
            else{
                targetVal = document.querySelector('select[name="threshold"]').value;
            }
            switch (targetVal){
                case '':
                    vm.useLimitItems = false;
                    break;
                case '0':
                    vm.useLimitItems = false;
                    break;
                case '1':
                    vm.useLimitItems = true;
                    break;
            }
        },
        // 优惠券类型
        useCategoriesChange:function (targetVal) {
            if(targetVal){}
            else{
                targetVal = document.querySelector('select[name="couponType"]').value;
            }
            switch (targetVal){
                case '':
                    vm.useCategoriesItems.money = false;
                    vm.useCategoriesItems.vouche = false;
                    break;
                case 'N001870100':
                    vm.useCategoriesItems.money = true;
                    vm.useCategoriesItems.vouche = false;
                    break;
                case 'N001870200':
                    vm.useCategoriesItems.money = false;
                    vm.useCategoriesItems.vouche = true;
                    break;
            }
        },
        // 使用时间
        useTimesChange:function () {
            var event = event || window.event;
            var target = event.target;
            switch (target.value){
                case '':
                    vm.useTimesItems.absolute = false;
                    vm.useTimesItems.relative = false;
                    break;
                case '1':
                    vm.useTimesItems.absolute = true;
                    vm.useTimesItems.relative = false;
                    break;
                case '2':
                    vm.useTimesItems.absolute = false;
                    vm.useTimesItems.relative = true;
                    break;
            }
        },
        // 适用范围
        useSuitableRangeChange:function (targetVal) {
            if(targetVal){}
            else{
                targetVal = document.querySelector('select[name="useRange"]').value;
            }
            switch (targetVal){
                case '1':
                    vm.useSuitableRangeItems = false;
                    vm.useSuitableRangeItemsAll = true;
                    var useSuitableUrl = '/index.php?g=coupon&m=coupon&a=getGudsData'
                    axios.post(useSuitableUrl)
                        .then(function(res) {
                            // console.log(res)
                            var dataTotal = res.data.data.total;
                            document.querySelector('input[name="products"]').value = dataTotal;
                            vm.couponAddPopGudsSaveDataAll = dataTotal;
                        })
                    break;
                case '2':
                    vm.useSuitableRangeItems = true;
                    vm.useSuitableRangeItemsAll = false;
                    var arrParts = [];
                    for(key in vm.couponAddPopGudsSaveData){
                        if(vm.couponAddPopGudsSaveData[key]){
                            arrParts.push(vm.couponAddPopGudsSaveData[key])
                        }
                    }
                    vm.useSuitableRangeItemsparts = arrParts.length;

                    break;
                case '':
                    vm.useSuitableRangeItems = false;
                    vm.useSuitableRangeItemsAll = false;
                    break;
            }
        },
        // 发放方式
        useSendWayItemsChange:function () {
            var targetVal = document.querySelector('select[name="sendWay"]').value
            switch (targetVal){
                case '':
                    vm.useSendWayItems.once = false;
                    vm.useSendWayItems.continue = false;
                    vm.useSendWayItems.empty = true;
                    vm.objectchoseWayState2 = true;
                    vm.objectchoseState=true;
                    break;
                case '1':
                    vm.useSendWayItems.once = true;
                    vm.useSendWayItems.continue = false;
                    vm.useSendWayItems.empty = false;
                    vm.objectchoseWayState2 = false;
                    if(vm.objectchosePlatState1){
                        vm.objectchoseState=true;
                    }
                    else{
                        vm.objectchoseState=false;
                    }
                    break;
                case '2':
                    vm.useSendWayItems.once = false;
                    vm.useSendWayItems.empty = false;
                    vm.useSendWayItems.continue = true;
                    vm.objectchoseWayState2 = false;
                    if(vm.objectchosePlatState1){
                        vm.objectchoseState=true;
                    }
                    else{
                        vm.objectchoseState=false;
                    }
                    break;
            }
        },
        // 持续性发放对象
        sendContinuteObjectItemsChange:function () {
            var event = event || window.event;
            var target = event.target;
            // console.log(target.value)
            if(target.value == 'N001860300'){
                vm.sendContinuteObjectVip=true;
            }else{
                vm.sendContinuteObjectVip=false;
            }
        },
        //一次性发放对象，手工选择用户 选择按操作
        ObjectmanualSelection:function (data1,data2,page) {
            if(vm.suitableShopData){
                vm.couponAddPopManual=true;
                var ObjectmanualUrl = '/index.php?g=coupon&m=coupon&a=getUserData';
                var params={
                    shop:vm.suitableShopData,
                }
                if(page){
                    params={
                        shop:vm.suitableShopData,
                        page:page,
                    }
                    if(data2){
                        if(data1 =="mobile"){
                            params={
                                shop:vm.suitableShopData,
                                page:page,
                                mobile:data2,
                            }
                        }else if(data1 =="nickname"){
                            params={
                                shop:vm.suitableShopData,
                                page:page,
                                nickname:data2,
                            }
                        }else if(data1 =="email"){
                            params={
                                shop:vm.suitableShopData,
                                page:page,
                                email:data2,
                            }
                        }
                    }
                }
                if(data2){
                    if(data1 =="mobile"){
                        params={
                            shop:vm.suitableShopData,
                            page:page,
                            mobile:data2,
                        }
                    }else if(data1 =="nickname"){
                        params={
                            shop:vm.suitableShopData,
                            page:page,
                            nickname:data2,
                        }
                    }else if(data1 =="email"){
                        params={
                            shop:vm.suitableShopData,
                            page:page,
                            email:data2,
                        }
                    }
                }
                params = JSON.stringify(params);
                // console.log(params)
                utils.lazy_loading("show");
                axios.post(ObjectmanualUrl,params)
                    .then(function(res) {
                        utils.lazy_loading();
                        // console.log(res)
                        var data=res.data.data.list;
                        var ObjectmanualDataArry=[]
                        for(key in data){
                            var ObjectmanualDataObj={};
                            ObjectmanualDataObj.email = data[key].email;
                            ObjectmanualDataObj.mobile = data[key].mobile;
                            ObjectmanualDataObj.nickName = data[key].nickName;
                            ObjectmanualDataObj.userId = data[key].userId;
                            ObjectmanualDataObj.plat = data[key].plat;
                            ObjectmanualDataArry.push(ObjectmanualDataObj)
                        }
                        vm.ObjectmanualData = ObjectmanualDataArry;
                        vm.pageManul.sePage= Number(res.data.data.page);
                        vm.pageManul.pageLength=Number(res.data.data.total);
                        vm.pageManul.pageSize=Number(res.data.data.pageNum);
                        setTimeout(function () {
                            var ManuaCheckboxAll=document.querySelectorAll('.ManuaCheckbox');
                            for(key in ManuaCheckboxAll){
                                if(vm.couponAddPopManualSaveData.indexOf(ManuaCheckboxAll[key].value) >= 0){
                                    ManuaCheckboxAll[key].checked =true;
                                }else{
                                    ManuaCheckboxAll[key].checked = false
                                }
                            }
                        },10)
                    }).catch(function () {
                         utils.lazy_loading();
                })
            }else{
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>适用店铺不能为空</span>");
            }
        },
        couponAddPopManualSearch:function () {
            var dataSelect=document.getElementById("couponAddPopManualSelect").value;
            if(dataSelect){
                var dataInput=document.getElementById("couponAddPopManualInput").value;
                if(dataInput){
                    vm.ObjectmanualSelection(dataSelect,dataInput)
                    vm.manualPage = false;
                    vm.manualPopstate1=dataSelect;
                    vm.manualPopstate2=dataInput;
                }else{
                    vm.ObjectmanualSelection();
                    vm.manualPage=true;
                    vm.manualPopstate1="";
                    vm.manualPopstate2="";
                }
            }else{
                vm.ObjectmanualSelection()
                vm.manualPage=true;
                vm.manualPopstate1="";
                vm.manualPopstate2="";
            }
        },
        partsGoodsSelection:function (page,data1,data2) {
            vm.partsGoodsItems=true;
            var params={};
            if(page){
                params={
                    page:page,
                }
                if(data2){
                    if(data1 =="skuId"){
                        params={
                            page:page,
                            skuId:data2,
                        }
                    }else if(data1 =="gudsId"){
                        params={
                            page:page,
                            gudsId:data2,
                        }
                    }else if(data1 =="gudsName"){
                        params={
                            page:page,
                            gudsName:data2,
                        }
                    }
                }
            }
            if(data2){
                if(data1 =="skuId"){
                    params={
                        page:page,
                        skuId:data2,
                    }
                }else if(data1 =="gudsId"){
                    params={
                        page:page,
                        gudsId:data2,
                    }
                }else if(data1 =="gudsName"){
                    params={
                        page:page,
                        gudsName:data2,
                    }
                }
            }
            params = JSON.stringify(params);
            var partsGoodsSelectionUrl='/index.php?g=coupon&m=coupon&a=getGudsData'
            utils.lazy_loading("show");
            axios.post(partsGoodsSelectionUrl,params)
                .then(function(res) {
                    utils.lazy_loading()
                    var data=res.data.data.list;
                    var ObjectGudsDataArry=[];
                    for(key in data){
                        var ObjectGudsDataObj={};
                        ObjectGudsDataObj.gudsName = data[key].gudsName;
                        ObjectGudsDataObj.skuId = data[key].skuId;
                        ObjectGudsDataObj.spuId = data[key].spuId;
                        ObjectGudsDataArry.push(ObjectGudsDataObj)
                    }
                    vm.ObjectGudsData = ObjectGudsDataArry;
                    vm.pageGuds.sePage= Number(res.data.data.page);
                    vm.pageGuds.pageLength=Number(res.data.data.total);
                    vm.pageGuds.pageSize=Number(res.data.data.pageNum);
                    setTimeout(function () {
                        var GudsCheckboxAll=document.querySelectorAll('.GudsCheckbox');
                        for(key in GudsCheckboxAll){
                            if(vm.couponAddPopGudsSaveData.indexOf(GudsCheckboxAll[key].value) >= 0){
                                GudsCheckboxAll[key].checked =true;
                            }else{
                                GudsCheckboxAll[key].checked = false
                            }
                        }
                    },10)
                }).catch(function () {
                    utils.lazy_loading();
            })
        },
        _submit:function () {
            var _submitIndex=0;
            if(document.querySelector('input[name="title"]').value){
                document.querySelector('input[name="title"]').style.borderColor="#D7DADD";
            }else{
                _submitIndex=1;
                document.querySelector('input[name="title"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>优惠券名称	不能为空</span>");
            }
            if(vm.suitableShopData){
                document.querySelector('#shopVal').value = vm.suitableShopData
            }
            else{
                _submitIndex=1;
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>适用店铺不能为空</span>");
            }
            if(document.querySelector('select[name="sendWay"]').value){
                document.querySelector('select[name="sendWay"]').style.borderColor="#D7DADD";
            }else{
                _submitIndex=1;
                document.querySelector('select[name="sendWay"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>发放方式不能为空</span>");
            }
            if(document.querySelector('select[name="sendObject"]').value){
                document.querySelector('select[name="sendObject"]').style.borderColor="#D7DADD";
                if(document.querySelector('select[name="sendObject"]').value =='N001860300' ){
                    if(document.querySelector('input[name="orderNum"]').value){
                        document.querySelector('input[name="orderNum"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="orderNum"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入需求成功支付订单的次数</span>");
                    }
                }
            }else{
                _submitIndex=1;
                document.querySelector('select[name="sendObject"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>发放对象不能为空</span>");
            }

            if(document.querySelector('select[name="threshold"]').value){
                document.querySelector('select[name="threshold"]').style.borderColor="#D7DADD";
                if(document.querySelector('select[name="threshold"]').value =='1'){
                    if(document.querySelector('input[name="thresholdCondition"]').value){
                        document.querySelector('input[name="thresholdCondition"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="thresholdCondition"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入门槛金额</span>");
                    }
                }
            }else{
                _submitIndex=1;
                document.querySelector('select[name="threshold"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>使用门槛不能为空</span>");
            }
            if(document.querySelector('select[name="couponType"]').value){
                document.querySelector('select[name="couponType"]').style.borderColor="#D7DADD";
                if(document.querySelector('select[name="couponType"]').value =='N001870100'){
                    if(document.querySelector('input[name="maxAmount"]').value){
                        document.querySelector('input[name="maxAmount"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="maxAmount"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入抵扣金额</span>");
                    }
                }
                else if(document.querySelector('select[name="couponType"]').value =='N001870200'){
                    if(document.querySelector('input[name="proportion"]').value){
                        document.querySelector('input[name="proportion"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="proportion"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入抵扣比例</span>");
                    }
                    if(document.querySelector('input[name="maxAmount"]').value){
                        document.querySelector('input[name="maxAmount"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="maxAmount"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入最高优惠金额</span>");
                    }
                }
            }else{
                _submitIndex=1;
                document.querySelector('select[name="couponType"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>优惠劵类型不能为空</span>");
            }
            if(document.querySelector('select[name="timeType"]').value){
                document.querySelector('select[name="timeType"]').style.borderColor="#D7DADD";
                if(document.querySelector('select[name="timeType"]').value =='1'){
                    if(document.querySelector('input[name="start_time"]').value){
                        document.querySelector('input[name="start_time"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="start_time"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入起始时间</span>");
                    }
                    if(document.querySelector('input[name="end_time"]').value){
                        document.querySelector('input[name="end_time"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="end_time"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入结束日期</span>");
                    }
                }
                else if(document.querySelector('select[name="timeType"]').value =='2'){
                    if(document.querySelector('input[name="timeValue"]').value){
                        document.querySelector('input[name="timeValue"]').style.borderColor="#D7DADD";
                    }else{
                        _submitIndex=1;
                        document.querySelector('input[name="timeValue"]').style.borderColor="red";
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>请输入相对时间</span>");
                    }
                }
            }else{
                _submitIndex=1;
                document.querySelector('select[name="timeType"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>使用时间不能为空</span>");
            }

            if(document.querySelector('select[name="superpositionRule"]').value){
                document.querySelector('select[name="superpositionRule"]').style.borderColor="#D7DADD";
            }else{
                _submitIndex=1;
                document.querySelector('select[name="superpositionRule"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>叠加规则不能为空</span>");
            }
            if(document.querySelector('select[name="useRange"]').value){
                document.querySelector('select[name="useRange"]').style.borderColor="#D7DADD";
            }else{
                _submitIndex=1;
                document.querySelector('select[name="useRange"]').style.borderColor="red";
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>适用范围不能为空</span>");
            }
            if(_submitIndex==0){
                var _submitIndexNex=0;
                var useRangeVal=document.querySelector('select[name="useRange"]').value;
                var sendObjectVal=document.querySelector('select[name="sendObject"]').value;
                var data_product = useRangeVal == '1'?vm.couponAddPopGudsSaveDataAll:vm.couponAddPopGudsSaveData.join(",")
                var data_user = '';
                if(sendObjectVal =='N001850100' ){
                    data_user = vm.couponAddPopManualSaveDataAll
                }else if(sendObjectVal =='N001850200'){
                    data_user = vm.couponAddPopManualSaveData.join(",");
                }else if(sendObjectVal =='N001850300'){
                    data_user = vm.excelUploadData;
                }else if(sendObjectVal =='N001860300'){
                    data_user = document.querySelector('input[name="orderNum"]').value;
                }else{
                    data_user = 'noneVal'
                }
                // console.info(data_product,"data_product",data_user)
                if(data_product){}
                else{
                    _submitIndexNex=1;
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>适用范围不能为空</span>");
                }
                if(data_user || data_user == 'noneVal'){}
                else{
                    _submitIndexNex=1;
                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>发放对象不能为空</span>");
                }
                // console.info(_submitIndexNex)
                if(_submitIndexNex==0){
                    document.querySelector('input[name="products"]').value = data_product;
                    // console.log(data_product);
                    if(data_user =='noneVal' ){
                        document.querySelector('input[name="users"]').disabled = true;
                    }else{
                        document.querySelector('input[name="users"]').value = data_user;
                    }
                    setTimeout(function () {
                        // document.getElementById("_submit").submit();
                        var data= $("#_submit").serialize();
                        axios.post('/index.php?g=coupon&m=coupon&a=updateCoupon',data)
                            .then(function(res) {
                                // console.info(res)
                                if(res.data.code =='2000'){
                                    layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+res.data.msg+"</span>");
                                    setTimeout(function () {
                                        var couponUrl="/index.php?g=coupon&m=coupon&a=detail&id="+vm.form.id;
                                        var route = document.createElement("a");
                                        route.setAttribute("style", "display: none");
                                        route.setAttribute("onclick", "changenewtab(this,'优惠券详情')");
                                        route.setAttribute("_href", couponUrl);
                                        route.click();
                                    },1000)
                                }
                            })
                    },100);
                }
            }
        },
        //手工添加用户，全选框
        couponAddPopManuaCheckboxAll:function () {
            var event = event || window.event;
            var target = event.target;
            var ManuaCheckbox= document.querySelectorAll('.ManuaCheckbox');
            if(target.checked){
                for(key in ManuaCheckbox){
                    ManuaCheckbox[key].checked=true
                }
            }else{
                for(key in ManuaCheckbox){
                    ManuaCheckbox[key].checked=false
                }
            }
        },
        //手工添加用户，单选
        couponAddPopManuaCheckbox:function () {
            var event = event || window.event;
            var target = event.target;
            var ManuaCheckbox= document.querySelectorAll('.ManuaCheckbox');
            var ManuaCheckboxIndex=0;
            for(key in ManuaCheckbox){
                if(ManuaCheckbox[key].checked){
                    ManuaCheckboxIndex++;
                }
            }
            if(ManuaCheckboxIndex == ManuaCheckbox.length){
                document.querySelectorAll('#ManuaCheckboxAll')[0].checked=true
            }else{
                document.querySelectorAll('#ManuaCheckboxAll')[0].checked=false
            }
        },
        //手工添加用户,确认保存按钮
        couponAddPopManualSave:function () {
            var ManuaCheckboxAll=document.querySelectorAll('.ManuaCheckbox');
            for(key in ManuaCheckboxAll){
                if(ManuaCheckboxAll[key].checked){
                    var emailData = ManuaCheckboxAll[key].value;
                    var emailDataIndex = 0;
                    for(key1 in vm.couponAddPopManualSaveData){
                        if(vm.couponAddPopManualSaveData[key1] == emailData){
                            emailDataIndex ++;
                        }
                    }
                    if(emailDataIndex == 0){
                        vm.couponAddPopManualSaveData.push(emailData);
                        layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>保存成功</span>");
                    }
                }else{
                    for(key2 in vm.couponAddPopManualSaveData){
                        if(vm.couponAddPopManualSaveData[key2] == ManuaCheckboxAll[key].value){
                            vm.couponAddPopManualSaveData.splice(key2, 1)
                        }
                    }
                }
            }
            var data_user = vm.couponAddPopManualSaveData.join(",");
            document.querySelector('input[name="users"]').value = data_user;
            var arrMenual = [];
            for(key in vm.couponAddPopManualSaveData){
                if(vm.couponAddPopManualSaveData[key]){
                    arrMenual.push(vm.couponAddPopManualSaveData[key])
                }
            }
            vm.objectchoseMenulStateparts = arrMenual.length;
        },
        //商品搜索
        couponAddPopGudsSearch:function () {
            var dataSelect,dataInput;
            dataSelect=document.querySelector("#couponAddPopGudsSelect").value;
            if(dataSelect){
                dataInput=document.querySelector("#couponAddPopGudsInput").value;
                if(dataInput){
                    vm.partsGoodsSelection("",dataSelect,dataInput)
                    vm.goodesPage=false;
                    vm.goodesPopstate1=dataSelect;
                    vm.goodesPopstate2=dataInput;
                }else{
                    vm.partsGoodsSelection();
                    vm.goodesPage = true;
                    vm.goodesPopstate1="";
                    vm.goodesPopstate2="";
                }
            }else{
                vm.partsGoodsSelection()
                vm.goodesPage = true;
                vm.goodesPopstate1="";
                vm.goodesPopstate2="";
            }
        },
        //商品弹框   全选
        couponAddPopGudsCheckboxAll:function () {
            var event = event || window.event;
            var target = event.target;
            var ManuaCheckbox= document.querySelectorAll('.GudsCheckbox');
            if(target.checked){
                for(key in ManuaCheckbox){
                    ManuaCheckbox[key].checked=true
                }
            }else{
                for(key in ManuaCheckbox){
                    ManuaCheckbox[key].checked=false
                }
            }
        },
        //商品弹框   单选
        couponAddPopGudsCheckbox:function () {
            var event = event || window.event;
            var target = event.target;
            var ManuaCheckbox= document.querySelectorAll('.GudsCheckbox');
            var ManuaCheckboxIndex=0;
            for(key in ManuaCheckbox){
                if(ManuaCheckbox[key].checked){
                    ManuaCheckboxIndex++;
                }
            }
            if(ManuaCheckboxIndex == ManuaCheckbox.length){
                document.querySelectorAll('.GudsCheckboxAll')[0].checked=true
            }else{
                document.querySelectorAll('.GudsCheckboxAll')[0].checked=false
            }
        },
        //部分商品,确认保存按钮
        couponAddPopGudsSave:function () {
            var GudsCheckboxAll=document.querySelectorAll('.GudsCheckbox');
            for(key in GudsCheckboxAll){
                if(GudsCheckboxAll[key].checked){
                    var spuData = GudsCheckboxAll[key].value;
                    var spuDataIndex = 0;
                    for(key1 in vm.couponAddPopGudsSaveData){
                        if(vm.couponAddPopGudsSaveData[key1] == spuData){
                            spuDataIndex ++;
                        }
                    }
                    if(spuDataIndex == 0){
                        vm.couponAddPopGudsSaveData.push(spuData);
                        layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>保存成功</span>");
                    }
                }else{
                    for(key2 in vm.couponAddPopGudsSaveData){
                        if(vm.couponAddPopGudsSaveData[key2] == GudsCheckboxAll[key].value){
                            vm.couponAddPopGudsSaveData.splice(key2, 1)
                        }
                    }
                }
            }
            var data_product = vm.couponAddPopGudsSaveData.join(",");
            document.querySelector('input[name="products"]').value = data_product;
            var arrParts = [];
            for(key in vm.couponAddPopGudsSaveData){
                if(vm.couponAddPopGudsSaveData[key]){
                    arrParts.push(vm.couponAddPopGudsSaveData[key])
                }
            }
            vm.useSuitableRangeItemsparts = arrParts.length;
        },
        VoucherProtion:function () {
            var event = event || window.event;
            var target = event.target;
            var targetVal = Number(target.value);
            if(targetVal>100){
                document.querySelector(".use-categories-Voucher-Proportion").value = 0;
                document.querySelector(".use-categories-Voucher-Proportion").style.borderColor = "red"
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>折扣比例不能超过100</span>");
            }else{
                document.querySelector(".use-categories-Voucher-Proportion").style.borderColor="#D7DADD";
            }
        },
    }
})
function hasClass(obj, cls) {
    return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
};
function addClass(obj, cls) {
    if (!this.hasClass(obj, cls)) obj.className += " " + cls;
};
function removeClass (obj, cls) {
    if (hasClass(obj, cls)) {
        var reg = new RegExp('(\\s|^)' + cls + '(\\s|$)');
        obj.className = obj.className.replace(reg, ' ');
    }
}