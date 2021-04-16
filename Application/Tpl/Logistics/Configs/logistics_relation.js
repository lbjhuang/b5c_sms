var Vm = new Vue({
    el: '#logistics',
    data:{
        tableData:'',
        tableDataLength:'',
        companyData:'',
        channelData:'',
        DictionaryList:'',
        page:{
            pageCurrent:1,
            pageRows:0,
            pageTotal:0,
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
    },
    //页面渲染之前，先取码表数据
    beforeCreate:function () {
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
    },
    created :function(){
        setTimeout(function () {
            Vm.relationSearch();
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
            this.relationSearch(val);
            $(".checkboxAllItems").prop('checked',false);
            $(".logistics_table .table .use-body .logistics_table_checkbox").prop("checked",false)
        },
        //所有取消按钮
        cancel:function () {
            $(".logistics_relation_pop").hide()
        },
        //增加物流关系 弹出功能
        add:function () {
            Vm.addStateShow = true;
            Vm.modefiedState=false;
            $(".logistics_relation_pop").show();
            Vm.modefiedDataId='';
            $('#logistics_relation_pop_form input').val('');
            $('#logistics_relation_pop_form select').val('');
        },
        //增加物流关联
        relationSave:function () {
            var ownCode=$("#pop_b5c_logistics_cd select").val(),thirdCode=$("#pop_third_logistics_cd").val().trim(),
                platCode=$("#pop_plat_cd select").val(),logisticName=$("#remark").val().trim(),
                partnerId=$("#pop_partner_id").val().trim(),partnerKey=$("#pop_partner_key").val().trim();
            // console.info(ownCode,thirdCode,platCode,logisticName,partnerId,partnerKey);
            var url='',params='';
            if(Vm.modefiedState){
                url='/index.php?g=logistics&m=configs&a=updateRelation'
            }else{
                 url = 'index.php?g=logistics&m=configs&a=createRelations';
            }
            if(thirdCode && platCode && ownCode && logisticName){
                params +='ownCode='+ ownCode+'&thirdCode='+ thirdCode+'&platCode='+ platCode;
                if(logisticName){
                    params +='&logisticName='+ logisticName;
                }
                if(partnerId){
                    params +='&partnerId='+ partnerId
                }
                if(partnerKey){
                    params +='&partnerKey='+ partnerKey
                }
                if(Vm.modefiedDataId){
                    params +='&id='+ Vm.modefiedDataId
                }
                axios.post(url,params)
                    .then(function(res) {
                        // console.info(res.data.code,res)
                        if(res.data.code == '200'){
                            if(Vm.modefiedState){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>修改成功</span>");
                            }else{
                                layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>添加成功</span>");
                            }
                            $(".logistics_relation_pop").hide();
                            Vm.relationSearch();
                        }else {
                            if(Vm.modefiedState){
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>修改失败</span>");
                            }else{
                                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>添加失败</span>");
                            }

                        }
                    })
            }else{
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>数据不全</span>");
            }
        },
        //删除物流关系列表数据
        deleteRelation:function () {
            var idArry=[],idStr='';
            $(".logistics_table .table .use-body tr .logistics_table_checkbox").each(function () {
                if($(this).prop('checked')){
                    // console.info($(this).attr('data-id'));
                    var id = $(this).attr('data-id');
                    idArry.push(id);
                }
            })
            idStr = idArry.join(",");
            var url='/index.php?g=logistics&m=configs&a=deleteRelation'
            if(idStr){
                url +='&id='+idStr;
                this.$confirm('确认删除此条信息？', '提示', {type: 'warning'})
                    .then(function () {
                        axios.get(url)
                            .then(function (res) {
                                if(res.data.code == '200'){
                                    layer.msg("<span class='invoice_detail_bomb_tip'><i>√</i>删除成功</span>");
                                    $(".logistics_relation_pop").hide();
                                    $(".logistics_table .table .use-body .logistics_table_checkbox").prop("checked",false)
                                    Vm.relationSearch( Vm.latelyPagesNum);
                                }else{
                                    layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>请选择一个删除</span>");
                                }
                            })
                    })
            }
        },
        //修改操作
        modefiedButton:function () {
          // $('#pop_b5c_logistics_cd select').removeClass('selectNoTriangle')
          //   $('#pop_plat_cd select').removeClass('selectNoTriangle')
            // console.info(Vm.popChannelIdentificationVal,Vm.popB5CIdentificationVal)
            var addState = 0,pop_partner_key1='',pop_b5c_logistics_cd1='',pop_logistics_name1='',pop_plat_cd1='',pop_id='',
                pop_third_logistics_cd1='',logistics_mode1='',pop_partner_id1='',remark1 ='';
            $(".logistics_table .table .use-body .logistics_table_checkbox").each(function () {
                if($(this).prop("checked")){
                    addState++;
                    pop_id=$(this).attr('data-id')
                    pop_partner_key1=$(this).parents('tr').find(".list_partner_key").html();
                    pop_b5c_logistics_cd1=$(this).parents('tr').find(".list_b5c_logistics_cd").html();
                    pop_logistics_name1=$(this).parents('tr').find(".list_logistics_name").html();
                    pop_plat_cd1=$(this).parents('tr').find(".list_plat_cd").html();
                    pop_third_logistics_cd1=$(this).parents('tr').find(".list_third_logistics_cd").html();
                    pop_partner_id1=$(this).parents('tr').find(".list_partner_id").html();
                    // console.info(pop_id,pop_partner_key1,pop_b5c_logistics_cd1,pop_logistics_name1,pop_plat_cd1,pop_third_logistics_cd1,pop_partner_id1,logistics_mode1)
                }
            })
            console.info(pop_b5c_logistics_cd1,pop_plat_cd1)
            Vm.modefiedDataId='';
            if(addState ==0){
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>请选择要修改的数据</span>");
            }else if(addState > 1){
                layer.msg("<span class='invoice_detail_bomb_tip'><i style='color: red'>X</i>一次只能修改一条数据</span>");
                $(".logistics_table .table .use-body .logistics_table_checkbox").prop("checked",false);
            }else if(addState == 1){
                Vm.addStateShow = false;
                Vm.modefiedState=true;
                $(".logistics_relation_pop").show();
                $("#pop_partner_key").val(pop_partner_key1)
                $('#pop_b5c_logistics_cd select').val(pop_b5c_logistics_cd1);
                // $('#pop_logistics_name').html(pop_logistics_name1);
                $('#pop_plat_cd select').val(pop_plat_cd1);
                $('#pop_third_logistics_cd').val(pop_third_logistics_cd1);
                $('#pop_partner_id').val(pop_partner_id1);
                $('#logistics_mode').val(logistics_mode1);
                $('#remark').val(pop_logistics_name1);
                 // Vm.popChannelIdentificationVal=pop_plat_cd1;
                 // Vm.popB5CIdentificationVal=pop_b5c_logistics_cd1;
                Vm.modefiedDataId = pop_id;
            }
        },
        //关联表搜索
        relationSearch:function (num) {
            var startTime=$("#startTime").val(),endTime=$("#endTime").val(),thirdCode=$("#thirdCode").val(),ownCode=$("#ownCode").val();
            var relationSearchurl = '/index.php?g=logistics&m=configs&a=searchRelations';
            if(startTime){
                relationSearchurl +='&startTime='+startTime;
            }
            if(endTime){
                relationSearchurl +='&endTime='+endTime;
            }
            if(thirdCode){
                relationSearchurl +='&thirdCode='+thirdCode;
            }
            if(ownCode){
                relationSearchurl +='&ownCode='+ownCode;
            }
            if(num){
                relationSearchurl = relationSearchurl + '&page='+num;
            }
            axios.get(relationSearchurl)
                .then(function(res) {
                    if(res.data.code == '200'){
                        console.info(res)
                        var data=res.data.data.list;
                        var tableData=[];
                        for(key in data){
                            var tableObj={};
                            tableObj.id=data[key].id;
                            tableObj.b5c_logistics_cd=data[key].b5c_logistics_cd;
                            tableObj.create_time=data[key].create_time;
                            tableObj.create_user=data[key].create_user;
                            tableObj.is_delete=data[key].is_delete;
                            tableObj.logistics_company=data[key].logistics_company;
                            tableObj.logistics_name=data[key].logistics_name;
                            tableObj.partner_id=data[key].partner_id;
                            tableObj.partner_key=data[key].partner_key;
                            tableObj.plat_cd=data[key].plat_cd;
                            tableObj.platform_name=data[key].platform_name;
                            tableObj.third_logistics_cd=data[key].third_logistics_cd;
                            tableObj.update_time=data[key].update_time;
                            tableData.push(tableObj)
                        }
                        Vm.tableData=tableData;
                        // console.info(tableData)
                        Vm.page.pageCurrent =Number(res.data.data.page);
                        Vm.page.pageRows =Number(res.data.data.rows);
                        Vm.page.displayData=tableData.length;
                        Vm.page.pageTotal =Number(res.data.data.total);
                        // console.info(Vm.page.pageCurrent)
                    }
                })
        },
        //excel导出功能
        logisticsXlExport:function () {
            var startTime=$("#startTime").val(),endTime=$("#endTime").val(),thirdCode=$("#thirdCode").val().trim(),ownCode=$("#ownCode").val().trim();
            var relationSearchurl = '/index.php?g=logistics&m=configs&a=exportRelations';
            if(startTime){
                relationSearchurl +='&startTime='+startTime;
            }
            if(endTime){
                relationSearchurl +='&endTime='+endTime;
            }
            if(thirdCode){
                relationSearchurl +='&thirdCode='+thirdCode;
            }
            if(ownCode){
                relationSearchurl +='&ownCode='+ownCode;
            }
            window.location.href=relationSearchurl
        },
    }
});