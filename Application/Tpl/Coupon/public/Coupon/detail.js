
var vm = new Vue({
    el:"#couponDetail",
    data:{
        couponAddPopManual:false,
        partsGoodsItems:false,
        gudsList:[],
        usersList:[],
        pageGuds:{
            sePage:0,
            displayLength:0,
            pageLength:0,
            pageSize:0,
        },
        pageManul:{
            sePage:0,
            displayLength:0,
            pageLength:0,
            pageSize:0,
        },
    },
    created:function () {
        localStorage.setItem('page',"优惠券详情")
    },
    methods: {
        handleCurrentChangeGuds:function (val) {
            vm.viewGudsList('open',val)
        },
        handleCurrentChange:function (val) {
            vm.viewUsersList('open',val)
        },
        // 手工选择用户 显示列表
        viewUsersList:function (state,page,data1,data2) {
            if(state == 'open'){
                vm.couponAddPopManual=true;
                var viewUsersList= document.querySelector('#viewUsersListId').getAttribute('data-href');
                var viewUsersListUrlIndex= viewUsersList.indexOf('&id');
                var viewUsersListUrl = viewUsersList.substring(0,viewUsersListUrlIndex)
                var viewUsersListid=viewUsersList.substring(viewUsersListUrlIndex+4,viewUsersList.length)
                // console.log(viewUsersListUrl,viewUsersListid);
                var ObjectmanualUrl = '/index.php?g=coupon&m=coupon&a=showUserList';
                var params={
                }
                if(page){
                    params={
                        couponId:viewUsersListid,
                        page:page,
                    }
                    if(data2){
                        if(data1 =="mobile"){
                            params={
                                couponId:viewUsersListid,
                                page:page,
                                mobile:data2,
                            }
                        }else if(data1 =="nickname"){
                            params={
                                couponId:viewUsersListid,
                                page:page,
                                nickname:data2,
                            }
                        }else if(data1 =="email"){
                            params={
                                couponId:viewUsersListid,
                                page:page,
                                email:data2,
                            }
                        }
                    }
                }
                if(data2){
                    if(data1 =="mobile"){
                        params={
                            couponId:viewUsersListid,
                            page:page,
                            mobile:data2,
                        }
                    }else if(data1 =="nickname"){
                        params={
                            couponId:viewUsersListid,
                            page:page,
                            nickname:data2,
                        }
                    }else if(data1 =="email"){
                        params={
                            couponId:viewUsersListid,
                            page:page,
                            email:data2,
                        }
                    }
                }
                if( !data2 && !page){
                    params={
                        couponId:viewUsersListid,
                    }
                }
                params = JSON.stringify(params);
                $.ajax({
                    url:ObjectmanualUrl,
                    data:params,
                    dataType:"json",
                    type:'post',
                    success:function (res) {
                        console.info(res)
                        var data = res.data.list;
                                var ObjectmanualDataArry = []
                                for (key in data) {
                                    var ObjectmanualDataObj = {};
                                    ObjectmanualDataObj.email = data[key].email;
                                    ObjectmanualDataObj.mobile = data[key].mobile;
                                    ObjectmanualDataObj.nickName = data[key].nickName;
                                    ObjectmanualDataObj.userId = data[key].userId;
                                    ObjectmanualDataObj.plat = data[key].plat;
                                    ObjectmanualDataArry.push(ObjectmanualDataObj)
                                }
                                vm.usersList = ObjectmanualDataArry;
                                vm.pageManul.sePage = Number(res.data.page);
                                vm.pageManul.pageLength = Number(res.data.total);
                                vm.pageManul.pageSize = Number(res.data.pageNum);
                    }
                })
            }
            else{
                vm.couponAddPopManual=false;
            }
        },
        // 手工选择用户 搜索
        couponAddPopManualSearch:function () {
            var dataSelect=document.getElementById("couponAddPopManualSelect").value;
            if(dataSelect){
                var dataInput=document.getElementById("couponAddPopManualInput").value;
                if(dataInput){
                    vm.viewUsersList('open','1',dataSelect,dataInput)
                }
            }else{
                vm.viewUsersList('open','1')
            }
        },
        //部分商品 列表
        viewGudsList:function (state,page,data1,data2) {
            if(state == 'open'){
                vm.partsGoodsItems=true;
                var viewUsersList= document.querySelector('#viewGudsListId').getAttribute('data-href');
                var viewUsersListUrlIndex= viewUsersList.indexOf('&id');
                var viewUsersListUrl = viewUsersList.substring(0,viewUsersListUrlIndex)
                var viewUsersListid=viewUsersList.substring(viewUsersListUrlIndex+4,viewUsersList.length)
                // console.log(viewUsersListUrl,viewUsersListid);
                var params={};
                if(page){
                    params={
                        couponId:viewUsersListid,
                        page:page,
                    }
                    if(data2){
                        if(data1 =="skuId"){
                            params={
                                couponId:viewUsersListid,
                                page:page,
                                skuId:data2,
                            }
                        }else if(data1 =="gudsId"){
                            params={
                                couponId:viewUsersListid,
                                page:page,
                                gudsId:data2,
                            }
                        }else if(data1 =="gudsName"){
                            params={
                                couponId:viewUsersListid,
                                page:page,
                                gudsName:data2,
                            }
                        }
                    }
                }
                if(data2){
                    if(data1 =="skuId"){
                        params={
                            couponId:viewUsersListid,
                            page:page,
                            skuId:data2,
                        }
                    }else if(data1 =="gudsId"){
                        params={
                            couponId:viewUsersListid,
                            page:page,
                            gudsId:data2,
                        }
                    }else if(data1 =="gudsName"){
                        params={
                            couponId:viewUsersListid,
                            page:page,
                            gudsName:data2,
                        }
                    }
                }
                if( !data2 && !page){
                    params={
                        couponId:viewUsersListid,
                    }
                }
                params = JSON.stringify(params);
                axios.post(viewUsersListUrl,params)
                    .then(function(res) {
                        var data=res.data.data.list;
                        var ObjectGudsDataArry=[]
                        for(key in data){
                            var ObjectGudsDataObj={};
                            ObjectGudsDataObj.gudsName = data[key].gudsName;
                            ObjectGudsDataObj.skuId = data[key].skuId;
                            ObjectGudsDataObj.spuId = data[key].spuId;
                            ObjectGudsDataArry.push(ObjectGudsDataObj)
                        }
                        vm.gudsList = ObjectGudsDataArry;
                        vm.pageGuds.sePage= Number(res.data.data.page);
                        vm.pageGuds.pageLength=Number(res.data.data.total);
                        vm.pageGuds.pageSize=Number(res.data.data.pageNum);
                    })
            }else{
                vm.partsGoodsItems=false;
            }
        },
        //商品搜索
        couponAddPopGudsSearch:function () {
            var dataSelect,dataInput;
            dataSelect=document.querySelector("#couponAddPopGudsSelect").value;
            if(dataSelect){
                dataInput=document.querySelector("#couponAddPopGudsInput").value;
                if(dataInput){
                    vm.viewGudsList('open','1',dataSelect,dataInput);
                }else{
                    vm.viewGudsList('open','1')
                }
            }else{
                vm.viewGudsList('open','1')
            }
        },
        startCoupon:function () {
            var event = event || window.event;
            var target = event.target;
            var  startCoupon=target.getAttribute('data-href');
            axios.get(startCoupon)
                .then(function(res) {
                   if(res.data.code =='2000'){
                       layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+res.data.msg+"</span>");
                       setTimeout(function () {
                           window.location.href="/index.php?g=coupon&m=coupon&a=detail&id="+document.querySelector('#couponDetailId').innerHTML;
                       },500)
                   }else{
                       layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>"+res.data.msg+"</span>");
                   }
                })
        },
        stopCoupon:function () {
            var event = event || window.event;
            var target = event.target;
            var  startCoupon=target.getAttribute('data-href');
            axios.get(startCoupon)
                .then(function(res) {
                    if(res.data.code =='2000'){
                        layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>"+res.data.msg+"</span>");
                        setTimeout(function () {
                            window.location.href="/index.php?g=coupon&m=coupon&a=detail&id="+document.querySelector('#couponDetailId').innerHTML;
                        },500)
                    }else{
                        layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>x</i>"+res.data.msg+"</span>");
                    }
                })

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
//去除空格
function trim(s){
    return s.replace(/(^s*)|(s*$)/g, "");
}