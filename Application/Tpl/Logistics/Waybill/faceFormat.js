var Vm = new Vue({
    el: '#faceFormat',
    data:{
       basisData:'',
       basisDataBarCode:'',
       basisDataOrder:'',
       basisDataOrderGudsOptList:'',
       basisDataOrderGudstbMsOrdGudsOptDtos:'',
       basisDataTablemount:0,
       basisDataTableweight:0,
       basisDataTabletotalCost:0,
    },
    created :function(){
        setTimeout(function () {
            Vm.basisDataSearch();
        },10)
    },
    methods:{
        //所有取消按钮
        cancel:function () {
            $(".logistics_pop").hide()
        },
        //搜索功能
        basisDataSearch:function () {
            var faceFormatDetailInformation=$(".faceFormatDetailInformation input").val();
            // console.info(faceFormatDetailInformation)
            if(faceFormatDetailInformation){
                faceFormatDetailInformation=JSON.parse(faceFormatDetailInformation);
                var data=faceFormatDetailInformation.shipper;
                var basisDataArry=[],basisDataObj={};
                basisDataObj.addr=data.addr;
                basisDataObj.area=data.area;
                basisDataObj.city=data.city;
                basisDataObj.company=data.company;
                basisDataObj.id=data.id;
                basisDataObj.logo=data.logo;
                basisDataObj.mobile=data.mobile;
                basisDataObj.name=data.name;
                basisDataObj.province=data.province;
                basisDataObj.tel=data.tel;
                var website=data.website;
                var websiteIndex= website.indexOf('http')
                if(websiteIndex == '0'){
                    website = website.substring(7,website.length);
                }
                basisDataObj.website=website
                basisDataObj.zip_code=data.zip_code;
                basisDataArry.push(basisDataObj);
                Vm.basisData=basisDataArry;
                Vm.basisDataBarCode = faceFormatDetailInformation.barCode;
                // console.info(faceFormatDetailInformation.orderApi)
                if(faceFormatDetailInformation.orderApi) {
                    //备用地址   http://172.16.13.62/b5capi/shipping/getOrderDetail.json?ids
                    var url = faceFormatDetailInformation.orderApi;
                    $.ajax({
                        url: url,
                        type: 'get',
                        async: true,
                        dataType: "JSONP",
                        jsonp: "callbackparam",
                        success: function callbackparam(data) {
                            console.info(data)
                            var dataThings = data[0], dataoptList = dataThings.optList,
                                datatbMsOrdGudsOptDtos = dataThings.tbMsOrdGudsOptDtos;
                            // 主信息获取
                            var basisDataOrderArr = [], basisDataOrderObj = {};
                            basisDataOrderObj.adprAddr = dataThings.adprAddr;
                            basisDataOrderObj.custId = dataThings.custId;
                            basisDataOrderObj.ordCustCpNo = dataThings.ordCustCpNo;
                            basisDataOrderObj.ordCustNm = dataThings.ordCustNm,
                                basisDataOrderObj.dlvAmt = dataThings.dlvAmt;
                            basisDataOrderArr.push(basisDataOrderObj)
                            Vm.basisDataOrder = basisDataOrderArr[0]
                            basisDataOrderObj.ordId = dataThings.ordId;
                            //商品信息获取.
                            var dataoptListArr = [];
                            for (key in dataoptList) {
                                dataoptListObj = {};
                                dataoptListObj.gudsOptId = dataoptList[key].gudsOptId;
                                dataoptListObj.optValName = dataoptList[key].optValName;
                                dataoptListObj.ordGudsOrgRmbp = datatbMsOrdGudsOptDtos[key].ordGudsOrgRmbp;
                                dataoptListObj.rmbPrice = datatbMsOrdGudsOptDtos[key].rmbPrice;
                                dataoptListObj.ordGudsQty = datatbMsOrdGudsOptDtos[key].ordGudsQty;
                                dataoptListObj.gudsCnsNm = datatbMsOrdGudsOptDtos[key].tbMsGuds.gudsCnsNm;
                                dataoptListObj.gudsDlvcDesnVal4 = datatbMsOrdGudsOptDtos[key].tbMsGuds.gudsDlvcDesnVal4;
                                dataoptListArr.push(dataoptListObj)
                            }
                            Vm.basisDataOrderGudsOptList = dataoptListArr;
                            var basisDataTableweight = 0, totalFee = 0;
                            for (key in dataoptListArr) {
                                basisDataTableweight += Number(dataoptListArr[key].gudsDlvcDesnVal4);
                                var ordGudsQty = dataoptListArr[key].ordGudsQty;
                                var rmbPrice = dataoptListArr[key].rmbPrice
                                totalFee += Number(ordGudsQty) * Number(rmbPrice)
                            }
                            Vm.basisDataTablemount = dataoptListArr.length;
                            Vm.basisDataTableweight = basisDataTableweight+'g';
                            Vm.basisDataTabletotalCost = totalFee + basisDataOrderObj.dlvAmt;
                        },
                    })
                }
                // else{
                //     Vm.basisDataBarCode=faceFormatDetailInformation.barCode;
                // }
            }
        },

    }
});