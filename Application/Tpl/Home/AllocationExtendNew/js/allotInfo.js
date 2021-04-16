
Vue.component('AllotInfo', {
  props:['info','option','outstocks','instocks','photo','work'],
  data: function () {
    return {
      hv:'',
      planned_transportation_channel_cds:[],
      titlp: '【货值总额（CNY，不含税）】=每个 SKU 的（【平均不含税商品单价（CNY）】+【平均 PO 内费用单价（CNY）】）*调拨数量（正品+次品）之和',
      seleTitlp: '【销售总额（CNY，不含税）】=每个 SKU 的【不含税销售单价（CNY）】*调拨数量（正品+次品）之和',
      visible: false,
      quote_no: '',
    }
  },
  computed:{
    tariff:function(){
      var tariff = 0;
      for(var x = 0;x<this.$props.instocks.length;x++){
        if(this.$props.instocks[x].logistics_information.tariff_currency_cd_val && this.$props.instocks[x].logistics_information.tariff){
          tariff += this.currCny(this.$props.instocks[x].logistics_information.tariff_currency_cd_val,this.$props.instocks[x].logistics_information.tariff)
        }
      }
      return tariff
    }
  },
  created:function(){
    var _this = this
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
    axios.post('index.php?g=oms&m=CommonData&a=commonData', {
      data: {
        query: {
          "planned_transportation_channel_cds": true
        }
      }
    }).then(function (response) {
      if (response.data.code == 2000) {
        _this.planned_transportation_channel_cds = response.data.data.planned_transportation_channel_cds
      }
    })
  },
  methods:{
    // 绑定报价单
    queryOffterClick() {
      let param = {
        allo_id: getQueryString('id'),
        quote_no: this.quote_no
      }
      axios.post('/index.php?m=AllocationExtendNew&a=allo_bind_quote_no',param).then(res => {
        if(res.status == 200 && res.data.code === 2000) {
          this.visible = false;
          this.info.quote_no = this.quote_no;
        } else {
          this.$message.error(this.$lang(res.data.msg));
          this.quote_no = '';
        }
      }).catch(err => {
        console.log(err)
      })
    },
    cancelOffterClick() {
      this.visible = false;
      this.quote_no = '';
    },
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    },
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    },
    open:function(){
      var bx = 0;
      var sj = 0;
      var gs = this.currCny(this.$props.work.value_added_service_fee_currency_cd_val,this.$props.work.value_added_service_fee)
      for(var x = 0;x<this.$props.outstocks.length;x++){
        if(this.$props.outstocks[x].logistics_information.insurance_fee_currency_cd_val && this.$props.outstocks[x].logistics_information.insurance_fee){
          bx += this.currCny(this.$props.outstocks[x].logistics_information.insurance_fee_currency_cd_val,this.$props.outstocks[x].logistics_information.insurance_fee)
        }
      }
      for(var x = 0;x<this.$props.instocks.length;x++){
        sj += this.currCny(this.$props.instocks[x].logistics_information.shelf_cost_currency_cd_val,this.$props.instocks[x].logistics_information.shelf_cost)
        gs += this.currCny(this.$props.instocks[x].logistics_information.value_added_service_fee_currency_cd_val,this.$props.instocks[x].logistics_information.value_added_service_fee)
      }
      this.$alert(
        '<table class="table table-bg">' +
        '<thead>' +
        '<th style="width: 100px">费用类型</th>'+
        '<th  style="width: 100px">金额</th>'+
        '</thead>'+
        '<tbody>' +
        '<tr>' +
        '<td>作业费用</td>' +
        '<td>'+'CNY'+this.currCny(this.$props.work.operating_expenses_currency_cd_val,this.$props.work.operating_expenses)+'</td>' +
        '</tr>' +
        '<tr>' +
        '<td>保险</td>' +
        '<td>CNY'+' '+ Math.round(bx*100)/100+'</td>' +
        '</tr>' +
        '<tr>' +
        '<td>上架费用</td>' +
        '<td>CNY'+ ' '+ Math.round(sj*100)/100+'</td>' +
        '</tr>' +
        '<tr>' +
        '<td>增值服务费用</td>' +
        '<td>'+'CNY'+' '+gs+'</td>' +
        '</tr>' +
        '</tbody>'+
        '</table>',
        '已产生服务费（CNY,预估）', {
          dangerouslyUseHTMLString: true
        });
    },
    open2:function(){
      var ck = 0;
      var tc = 0;
      for(var x = 0;x<this.$props.outstocks.length;x++){
        ck += this.currCny(this.$props.outstocks[x].logistics_information.outbound_cost_currency_cd_val,this.$props.outstocks[x].logistics_information.outbound_cost)
        tc += this.currCny(this.$props.outstocks[x].logistics_information.head_logistics_fee_currency_cd_val,this.$props.outstocks[x].logistics_information.head_logistics_fee)
      }
      this.$alert(
        '<table class="table table-bg">' +
        '<thead>' +
        '<th style="width: 100px">费用类型</th>'+
        '<th  style="width: 100px">金额</th>'+
        '</thead>'+
        '<tbody>' +
        '<tr>' +
        '<td>出库费用</td>' +
        '<td>CNY'+ ' '+ ck+'</td>' +
        '</tr>' +
        '<tr>' +
        '<tr>' +
        '<td>头程运费</td>' +
        '<td>CNY'+' '+ tc+'</td>' +
        '</tr>' +
        '</tbody>'+
        '</table>',
        '已产生物流费（CNY,预估）', {
          dangerouslyUseHTMLString: true
        });
    }
  },
  template: '    <div class="table table-striped table-detail-bg">\n' +
  '        <div>\n' +
  '        <div>\n' +
  '            <div colspan="7" class="text-l table-detail-title">{{$lang("基础信息")}}</div>\n' +
  '        </div>\n' +
  '        </div>\n' +
  '        <div class="text-c" style="overflow: hidden;">\n' +
  '        <div v-if="!option.no" style="width: 50%;float:left;display: flex">\n' +
  '            <div class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("调拨单号")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.allo_no}}</div>\n' +
  '        </div>'+
  '        <div v-if="!option.no"  style="width: 50%;float:left;display: flex">\n' +
  '            <div class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("调拨单状态")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{$lang(info.state_val)}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("调出仓库")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{$lang(info.allo_out_warehouse_cd_val)}}</div>\n' +
  '        </div>'+
  '        <div  style="width: 50%;float:left;display: flex">'+
  '            <div class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("始发仓库位置")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px;overflow: hidden;    line-height: 24px;" :title="info.originating_warehouse_location">{{info.originating_warehouse_location}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("调入仓库")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{$lang(info.allo_in_warehouse_cd_val)}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("目的仓库位置")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.destination_warehouse_location}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("销售团队")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.allo_in_team_val}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("调拨直接用途")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{$lang(info.transfer_use_type_val)}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex" v-if="info.is_optimize_team === 1">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("销售小团队")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{$lang(info.small_team_cd_val)}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("应审核人")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.reviewer_by}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("作业确认责任人")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.task_launch_by}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("出库确认责任人")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.transfer_out_library_by}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("入库确认责任人")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.transfer_warehousing_by}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex"  v-if="info.allo_out_warehouse_cd === \'N000688800\' || info.allo_out_warehouse_cd === \'N000688700\'">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("是否对接发网仓")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{$lang(info.use_fawang_logistics_val)}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("货值总额（CNY，不含税）")}}<el-tooltip class="item" effect="dark" :content="$lang(titlp)" placement="right"><i class="el-icon-question"></i>' +
  '    </el-tooltip></div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.total_value_goods}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex" v-if="info.transfer_use_type_val === \'销售\'">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("销售总额（CNY，不含税）")}}<el-tooltip class="item" effect="dark" :content="$lang(seleTitlp)" placement="right"><i class="el-icon-question"></i>' +
  '    </el-tooltip></div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.total_sales}}</div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-width: 1px; border-style: solid; border-color: rgba(0, 0, 0, 0) rgb(202, 222, 231) rgb(236, 239, 241); height: 35px; padding: 10px 0px; color: rgb(84, 110, 122); background: rgb(247, 249, 251); line-height: 35px;">{{$lang("期望出库日期")}} <span v-if="option.edit" style="color: red">*</span></div>\n' +
  '            <div style="flex: 1 1 0%; color: rgb(84, 110, 122); border-bottom: 1px solid rgb(236, 239, 241); border-right: 1px solid rgb(202, 222, 231); border-top: 1px solid rgba(0, 0, 0, 0); height: 35px; padding: 10px; line-height: 35px;"><el-date-picker format="yyyy-MM-dd" value-format="yyyy-MM-dd"\n' +
  '                    v-model="info.expected_delivery_date"\n' +
  '                    type="date"\n' +
  '                    :placeholder="$lang(\'选择日期\')" v-if="option.edit">\n' +
  '                </el-date-picker>\n' +
  '                <span v-if="!option.edit">{{info.expected_delivery_date}}</span>'+
  '            </div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-width: 1px; border-style: solid; border-color: rgba(0, 0, 0, 0) rgb(202, 222, 231) rgb(236, 239, 241); height: 35px; padding: 10px 0px; color: rgb(84, 110, 122); background: rgb(247, 249, 251); line-height: 35px;">{{$lang("期望入库日期")}} <span v-if="option.edit" style="color: red">*</span></div>\n' +
  '            <div style="flex: 1 1 0%; color: rgb(84, 110, 122); border-bottom: 1px solid rgb(236, 239, 241); border-right: 1px solid rgb(202, 222, 231); border-top: 1px solid rgba(0, 0, 0, 0); height: 35px; padding: 10px; line-height: 35px;">\n' +
  '                <el-date-picker format="yyyy-MM-dd"  value-format="yyyy-MM-dd"\n' +
  '                    v-model="info.expected_warehousing_date"\n' +
  '                    type="date"\n' +
  '                    :placeholder="$lang(\'选择日期\')" v-if="option.edit">\n' +
  '                </el-date-picker>\n' +
  '                <span v-if="!option.edit">{{info.expected_warehousing_date}}</span>'+
  '            </div>\n' +
  '        </div>\n' +
  '        <div  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-width: 1px; border-style: solid; border-color: rgba(0, 0, 0, 0) rgb(202, 222, 231) rgb(236, 239, 241); height: 35px; padding: 10px 0px; color: rgb(84, 110, 122); background: rgb(247, 249, 251); line-height: 35px;">{{$lang("计划运输渠道")}}  <span v-if="option.edit" style="color: red">*</span></div>\n' +
  '            <div style="flex: 1 1 0%; color: rgb(84, 110, 122); border-bottom: 1px solid rgb(236, 239, 241); border-right: 1px solid rgb(202, 222, 231); border-top: 1px solid rgba(0, 0, 0, 0); height: 35px; padding: 10px; line-height: 35px;">\n' +
  '                <el-select  v-if="option.edit"  v-model="info.planned_transportation_channel_cd" :placeholder="$lang(\'请选择\')"  clearable filterable>\n' +
  '                    <el-option v-for="(value,key) in planned_transportation_channel_cds" :key="key" :label="$lang(value.cdVal)" :value="value.cd"></el-option>\n' +
  '                </el-select>\n' +
  '                <span v-if="!option.edit">{{$lang(info.planned_transportation_channel_cd_val_val)}}</span>'+
  '            </div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info || !option.no"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("发起人")}} </div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.create_user}}</div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info || !option.no"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("发起时间")}} </div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.create_time}}</div>\n' +
  '        </div>\n' +
  '        <div v-if="(option.info || !option.no) && (info.state == \'N001970602\' || info.state == \'N001970603\')"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("关联报价单号")}} </div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px"><div v-if="info.quote_no">{{info.quote_no}}</div><el-popover v-else placement="bottom" width="400"  v-model="visible" trigger="click"><div style="padding: 30px;"><el-input  v-model="quote_no" :placeholder="$lang(\'请输入对应报价单号\')"></el-input> <div style="text-align: center;margin-top:15px"><el-button size="small" @click="cancelOffterClick">{{$lang("取消")}}</el-button><el-button size="small" @click="queryOffterClick">{{$lang("确定")}}</el-button></div></div><el-button slot="reference" size="small">{{$lang("绑定报价单号")}}</el-button></el-popover></div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("需求-出库差异原因")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.out_reason_difference?info.out_reason_difference:""}}</div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("出库-入库差异原因")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{info.in_reason_difference?info.in_reason_difference:""}}</div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("已产生服务费（CNY,预估）")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px"><span v-if="info.state_val !== \'已完成\' && info.state_val !== \'运输中\'">{{info.service_fee}}</span> <span v-if="info.state_val === \'已完成\' || info.state_val === \'运输中\'" style="cursor: pointer;color:blue" @click="open">{{info.service_fee}}</span></div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("已产生物流费（CNY,预估）")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px"><span v-if="info.state_val !== \'已完成\' && info.state_val !== \'运输中\'">{{info.logistics_costs}}</span> <span v-if="info.state_val === \'已完成\' || info.state_val === \'运输中\'" style="cursor: pointer;color:blue" @click="open2">{{info.logistics_costs}}</span></div>\n' +
  '        </div>\n' +
  '        <div v-if="option.info"  style="width: 50%;float:left;display: flex">\n' +
  '            <div  class="w-200" style="border-left: 1px solid #CADEE7;border-right: 1px solid #CADEE7;border-bottom: 1px solid #ECEFF1;height: 35px;padding: 10px 0;border-top:1px solid rgba(0,0,0,0);color: #546E7A;background: #F7F9FB;line-height:35px">{{$lang("已产生关税（CNY,预估）")}}</div>\n' +
  '            <div style="flex:1;color: #546E7A;border-bottom: 1px solid #ECEFF1;border-right: 1px solid #CADEE7;border-top:1px solid rgba(0,0,0,0);height: 35px;padding: 10px;line-height:35px">{{tariff}}</div>\n' +
  '        </div>\n' +
  '        </div>\n' +
  '    </div>'
})



Vue.component('ProfitInfo', {
  props:['profit','info','goods'],
  data: function () {
    return {
      count: 0
    }
  },
  computed:{
    m:function(){
      var goods =  this.$props.goods;
      var bool = false;
      for(var x = 0;x<goods.length;x++){
        if(Number(goods[x].tax_free_sales_unit_price) !==0 && Number(this.$props.info.total_value_goods) === 0){
          bool = true
        }
      }
      if(bool){
        return '∞'
      }else{
        return  this.$props.profit.gross_profit_margin + '%'
      }
    }
  },
  template:'<table class="table table-striped table-detail-bg">\n' +
  '        <thead>\n' +
  '        <tr>\n' +
  '            <th colspan="7" class="text-l table-detail-title">{{$lang("利润信息")}}</th>\n' +
  '        </tr>\n' +
  '        </thead>\n' +
  '        <tbody class="text-c">\n' +
  '        <tr>\n' +
  '            <td class="w-200">{{$lang("毛利")}}</td>\n' +
  '            <td>￥{{profit.gross_profit}}</td>\n' +
  '            <td class="w-200">{{$lang("毛利率")}}</td>\n' +
  '            <td>{{m}}</td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})

Vue.component('GoodList', {
  props:['goods','option','info','profit'],
  data: function () {
    return {
      curr:[],
      hv:'',
      thOption: [
        {title: this.$lang('序号')},
        {title: this.$lang('SKU条码')},
        {title: this.$lang('条形码')},
        {title: this.$lang('商品名称')},
        {title: this.$lang('商品属性')},
        {title: this.$lang('商品图片')},
        {title: this.$lang('平均不含税商品单价(CNY)')},
        {title: this.$lang('平均PO内费用单价(CNY)')},
        {title: this.$lang('PO外费用单价(CNY)')},
        {title: this.$lang('不含税销售单价'), red: true},
        {title: this.$lang('不含税销售单价(CNY)'),red: true},
        {title: this.$lang('调拨数量')}
      ],
      tax_free_sales_unit_price_currency_cd:null
    }
  },
  computed:{
  },
  created: function () {
    var _this = this;
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
  },
  methods: {
    ji:function(){
      var p = 0.00
      for(var x = 0;x<this.$props.goods.length;x++){
        if(this.$props.goods[x].tax_free_sales_unit_price && this.$props.goods[x].tax_free_sales_unit_price_currency_cd){
          p += this.currCny(this.$props.goods[x].tax_free_sales_unit_price_currency_cd_val,this.$props.goods[x].tax_free_sales_unit_price) * (Number(this.$props.goods[x].transfer_authentic_products) + Number(this.$props.goods[x].transfer_defective_products))
        }
      }
      if(this.$props.profit){
        var w = p - this.$props.info.total_value_goods.replace(/,/g, "")
        this.$props.profit.gross_profit_margin = Math.round(((w / p)*100)*100)/100
        if(isNaN(this.$props.profit.gross_profit_margin)){
          this.$props.profit.gross_profit_margin = 0.00
        }
        this.$props.profit.gross_profit_margin === -Infinity?this.$props.profit.gross_profit_margin = 0:null
        this.$props.profit.gross_profit = Math.round(w*100)/100
      }
      this.$props.info.total_sales = Math.round(p*100)/100
    },
    checkNum2:function(event,data,type){
      var _this = this;
      var r= /^[1-9][0-9]*$////^\d{1,9}(\.{0}|\.{1}\d{1,2})?$/
      var p = /^\d{1,9}(\.{0}|\.{1}\d{1,9})?$/
      var num = event.value
      if(!r.test(Number(event.value)) && !p.test(Number(event.value))){
        var n = Math.abs(parseInt(event.value))
        if(isNaN(n)){
          num = '0'
        }else{
          num = n
        }
      }
      if(event.value === ''){
        num = ''
      }
      if(String(num).split('').length>1){
        var ww = String(num).split('');
        if(ww[0] === '0' && ww[1] !=='.'){
          ww.splice(0,1)
        }
        num = ww.join('')
      }
      event.value = String(num)
      data[type] = String(num)
    },
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    },
    change:function(v){
      for(var x = 0;x<this.curr.length;x++){
        if(this.curr[x].CD === v.tax_free_sales_unit_price_currency_cd){
          v.tax_free_sales_unit_price_currency_cd_val = this.curr[x].CD_VAL
        }
      }
      this.ji()
    },
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    },
    isSHowTableHeader(v){
      // console.log("value",v)
      let  value =v;
      // (info.transfer_use_type_val === '非销售' && (v.title === '不含税销售单价（CNY)' || v.title === '不含税销售单价')) || (info.transfer_use_type_val === '销售' && (v.title === '不含税销售单价（CNY)' || v.title === '不含税销售单价'))

      // 非销售
      if(this.info.transfer_use_type_val === '非销售'){
        if(value.title === '不含税销售单价(CNY)'||value.title === '不含税销售单价'){
          console.log("执行了几次");
          return false;
        }else {
          return  true;
        }
      } else if(this.info.transfer_use_type_val === '销售'){
        if(value.title === '不含税销售单价(CNY)' || value.title === '不含税销售单价'){
          return true;
        }else {
          return  true;
        }
      }

    },
    // 批量编辑
    onBatchEdit(value){
      this.goods.forEach((obj,index) => {
        this.$set(obj,"tax_free_sales_unit_price_currency_cd",value);
        this.change(obj);
      })
    }
  },
  template:'<table class="table table-bg">\n' +
  '        <thead>\n' +
  '      <template v-for="(v,k) in thOption" >\n' +
  '        <th :key="k"  v-if="isSHowTableHeader(v)">\n' +
  '            {{v.title}}' +
  '           <span v-if="(v.red && option.edit)" style="color: red"> *</span>\n' +
  '                    <el-select v-if="(v.red && option.edit && k===9)"  @change="onBatchEdit" v-model="tax_free_sales_unit_price_currency_cd" :placeholder="$lang(\'请选择货币\')" >\n' +
  '                        <el-option :key="k" v-for="(v,k) in curr" :label="v.CD_VAL" :value="v.CD"></el-option>\n' +
  '                    </el-select>\n' +
  '        </th>\n' +
  '      </template>\n'+
  '        </thead>\n' +
  '        <tbody>\n' +
  '        <tr class="text-c" v-for="(v,k) in goods">\n' +
  '            <td>{{Number(k) + 1}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td style="white-space: pre-line">{{v.upc_id}}</td>\n' +
  '            <td style="text-overflow:ellipsis;overflow:hidden;max-width:200px"><el-tooltip class="item" effect="dark" :content="v.spu_name" placement="bottom">\n' +
  '      <div  style="text-overflow:ellipsis;overflow:hidden;max-width:200px">{{v.spu_name}}</div>\n' +
  '    </el-tooltip></td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td>CNY {{v.average_price_goods_without_tax_cny}}</td>\n' +
  '            <td>CNY {{v.average_po_internal_cost_cny}}</td>\n' +
  '            <td>CNY {{v.po_outside_cost_unit_price_cny}}</td>\n' +
  '             <td  v-if="info.transfer_use_type_val === \'销售\'">\n' +
  '                <el-input ref="inputWithSelect" v-if="option.edit"  :value="v.tax_free_sales_unit_price"  @keyup.native="checkNum2($event.target,v,\'tax_free_sales_unit_price\');ji()" class="input-with-select">\n' +
  '                    <el-select  @change="change(v)" v-model="v.tax_free_sales_unit_price_currency_cd" slot="prepend" :placeholder="$lang(\'请选择货币\')">\n' +
  '                        <el-option :key="k" v-for="(v,k) in curr" :label="$lang(v.CD_VAL)" :value="v.CD"></el-option>\n' +
  '                    </el-select>\n' +
  '                </el-input>\n' +
  '                <div  v-if="!option.edit" >{{ (v.tax_free_sales_unit_price_currency_cd_val?v.tax_free_sales_unit_price_currency_cd_val:\'\')+" "+ (v.tax_free_sales_unit_price?v.tax_free_sales_unit_price:\'\')}}</div>'+
  '            </td>\n' +
  '            <td  v-if="info.transfer_use_type_val === \'销售\'"> {{currCny(v.tax_free_sales_unit_price_currency_cd_val,v.tax_free_sales_unit_price)}}</td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.transfer_authentic_products?v.transfer_authentic_products:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.transfer_defective_products?v.transfer_defective_products:0}}</div>\n' +
  '            </td>\n' +
  '        </tr>\n' +
  '        <tr class="text-c">\n' +
  '            <td>{{$lang("合计")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>\n' +
  '            </td>\n' +
  '            <td></td>\n' +
  '            <td> </td>\n' +
  '            <td> </td>\n' +
  '            <td v-if="info.transfer_use_type_val === \'销售\'"></td>\n' +
  '            <td v-if="info.transfer_use_type_val === \'销售\'"></td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'transfer_authentic_products\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'transfer_defective_products\')}}</div>\n' +
  '            </td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})

/**
 * 仓库作业中，可编辑商品信息
 */
Vue.component('GoodListEdit', {
  props:['goods','option'],
  data: function () {
    return {
      value:'',
      wb:'',
      hv:'',
      rABS:false,
      curr:[],
      thOption: [
        {title: this.$lang('序号')},
        {title: this.$lang('SKU条码')},
        {title: this.$lang('条形码')},
        {title: this.$lang('商品名称')},
        {title: this.$lang('商品属性')},
        {title: this.$lang('商品图片')},
        {title: this.$lang('调拨数量')},
        {title: this.$lang('重量')+ '（kg/'+this.$lang('单个')+'）'},
        {title: this.$lang('预计总重')+'（kg）'},
        {title: this.$lang('箱数')+'（'+this.$lang('箱')+'）'},
        {title: this.$lang('每箱个数')},
        {title: this.$lang('箱号')},
        {title: this.$lang('箱子长宽高')+'（cm）'},
        {title: this.$lang('净重')+'（kg）'},
      ]
    }
  },
  created: function () {
    var _this = this;
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
  },
  methods: {
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    },
    one:function(){
      this.$refs.one.click();
    },
    downloadExl:function(){
      var json = [{
        'SKU编码':'和条形码填一个',
        '条形码':'和SKU编码填一个',
        '箱数（箱）*':'必填',
        '每箱个数*':'必填',
        '箱号*':'必填',
        '箱子长宽高*':'必填',
        '净重（kg）*':'必填'
      }]
      downloadExl(json,'','导入模板')
    },
    downloadExl2:function(){
      var json = JSON.parse(JSON.stringify(this.$props.goods));
      var arr = []
      for(var x =0;x <  json.length;x++){
        var obj = {
          'SKU条码':json[x].sku_id,
          '条形码':json[x].upc_id,
          '商品名称':json[x].spu_name,
          '商品属性':json[x].attributes,
          '数量（正品）':(json[x].transfer_authentic_products?json[x].transfer_authentic_products:0),
          '数量（残次品）':(json[x].transfer_defective_products?json[x].transfer_defective_products:0),
          '重量（kg/单个）':json[x].weight,
          '预计总重(kg)':json[x].estimated_total_weight,
          '箱数（箱）':json[x].number_boxes,
          '每箱个数':json[x].number_per_box,
          '箱号':json[x].case_number,
          '箱子长宽高':json[x].box_length_and_width_cm,
          '净重（kg）':json[x].net_weight_kg,
        }
        arr.push(obj)
      }
      downloadExl(arr,'','导出列表')
    },
    importf:function(obj) {//导入
      var _this = this;
      if(!obj.files) {
        return;
      }
      var f = obj.files[0];
      var reader = new FileReader();
      reader.onload = function(e) {
        var data = e.target.result;
        if(_this.rABS) {
          _this.wb = XLSX.read(btoa(fixdata(data)), {//手动转化
            type: 'base64'
          });
        } else {
          wb = XLSX.read(data, {
            type: 'binary'
          });
        }
        //wb.SheetNames[0]是获取Sheets中第一个Sheet的名字
        //wb.Sheets[Sheet名]获取第一个Sheet的数据
        console.log( XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]]));
        _this.exForData( XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]]))
      };
      if(_this.rABS) {
        reader.readAsArrayBuffer(f);
      } else {
        reader.readAsBinaryString(f);
      }
    },
    exForData:function(data){
      this.$refs.one.value = ''
      var r= /^[1-9][0-9]*$/
      var p = /^\d{1,9}(\.{0}|\.{1}\d{1,9})?$/
      var _this = this;
      var bool = false;
      var num = false;
      for(var y = 0;y<data.length;y++){
        if(!data[y]['箱数（箱）*'] ||  !data[y]['每箱个数*'] ||  !data[y]['每箱个数*'] || !data[y]['箱子长宽高*'] || !data[y]['净重（kg）*'] ||!data[y]['箱号*']){
          bool = true
        }
        if(!data[y]['SKU编码'] && !data[y]['条形码']){
          bool = true
        }
        if(!r.test(data[y]['箱数（箱）*'])){
          _this.$message({
            message: '箱数（箱）必须为整数',
            type: 'warning'
          });
          return
        }
        if(!r.test(Number(data[y]['净重（kg）*'])) && !p.test(data[y]['净重（kg）*'])){
          _this.$message({
            message: '净重（kg）输入格式不正确',
            type: 'warning'
          });
          return
        }
      }

      if(bool){
        _this.$message({
          message: '请检查表格必填数据',
          type: 'warning'
        });
        _this.$refs.one.value = ''
        return
      }
      var b = false;
      var goods = JSON.parse(JSON.stringify(this.$props.goods))
      var info = '';
      for(var x = 0;x<this.$props.goods.length;x++){
        var a = false
        for(var y = 0;y<data.length;y++){
          if(trim(this.$props.goods[x].sku_id) == trim(data[y]['SKU编码'])){
            this.$props.goods[x].number_boxes = data[y]['箱数（箱）*']
            this.$props.goods[x].number_per_box =  data[y]['每箱个数*']
            this.$props.goods[x].case_number = data[y]['箱号*']
            this.$props.goods[x].box_length_and_width_cm = data[y]['箱子长宽高*']
            this.$props.goods[x].net_weight_kg = data[y]['净重（kg）*']
            a = true
          }else if(trim(this.$props.goods[x].upc_id) == trim(data[y]['条形码'])){
            this.$props.goods[x].number_boxes = data[y]['箱数（箱）*']
            this.$props.goods[x].number_per_box =  data[y]['每箱个数*']
            this.$props.goods[x].case_number = data[y]['箱号*']
            this.$props.goods[x].box_length_and_width_cm = data[y]['箱子长宽高*']
            this.$props.goods[x].net_weight_kg = data[y]['净重（kg）*']
            a = true
          }
        }
        if(!a){
          b = true
          info = '请检查sku:'+this.$props.goods[x].sku_id+'的数据是否正确'
        }
      }
      if(data.length === 0){
        b = true
      }
      if(!b){
        _this.$message({
          message: '导入成功',
          type: 'success'
        });
      }else{
        this.$props.goods = goods
        _this.$message({
          message: '导入失败'+info,
          type: 'error'
        });
      }

    },
    checkNum:function(event,data,type){
      var _this = this;
      var r= /^[1-9][0-9]*$/
      var num = event.value
      if(!r.test(event.value)){
        var n = Math.abs(parseInt(event.value))
        if(isNaN(n)){
          num = '0'
        }else{
          num = n
        }
      }
      if(event.value === ''){
        num = ''
      }
      event.value = String(num)
      data[type] = String(num)
    },
    checkNum2:function(event,data,type){
      var _this = this;
      var r= /^[1-9][0-9]*$////^\d{1,9}(\.{0}|\.{1}\d{1,2})?$/
      var p = /^\d{1,9}(\.{0}|\.{1}\d{1,9})?$/
      var num = event.value
      if(!r.test(Number(event.value)) && !p.test(Number(event.value))){
        var n = Math.abs(parseInt(event.value))
        if(isNaN(n)){
          num = '0'
        }else{
          num = n
        }
      }
      if(event.value === ''){
        num = ''
      }
      if(String(num).split('').length>1){
        var ww = String(num).split('');
        if(ww[0] === '0' && ww[1] !=='.'){
          ww.splice(0,1)
        }
        num = ww.join('')
      }
      event.value = String(num)
      data[type] = String(num)
    },
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    }
  },
  template:'<div><input  type="file" @change="importf($event.target)"  style="display: none" ref="one"><div style="display: flex"><p class="ck-wrap-title" style="margin-right: auto">{{$lang("商品信息")}}</p><el-button type="primary" size="mini" @click="downloadExl">{{$lang("下载导入模板")}}</el-button><el-button type="primary" size="mini" @click="one">{{$lang("导入")}}</el-button> <el-button type="primary" size="mini" @click="downloadExl2">{{$lang("导出")}}</el-button></div><table id="goodsListEdit" class="table table-bg">\n' +
  '        <thead>\n' +
  '        <th v-for="(v,k) in thOption">\n' +
  '            {{v.title}}<span v-if="(v.red&&option.edit)" style="color: red"> *</span>\n' +
  '        </th>\n' +
  '        </thead>\n' +
  '        <tbody>\n' +
  '        <tr class="text-c" v-for="(v,k) in goods">\n' +
  '            <td>{{Number(k) + 1}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td style="white-space: pre-line">{{v.upc_id}}</td>\n' +
  '            <td style="text-overflow:ellipsis;overflow:hidden;max-width:200px"><el-tooltip class="item" effect="dark" :content="v.spu_name" placement="bottom">\n' +
  '      <div  style="text-overflow:ellipsis;overflow:hidden;max-width:200px">{{v.spu_name}}</div>\n' +
  '    </el-tooltip></td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.transfer_authentic_products?v.transfer_authentic_products:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.transfer_defective_products?v.transfer_defective_products:0}}</div>\n' +
  '            </td>\n' +
  '            <td> {{v.weight}}</td>\n' +
  '            <td> {{v.estimated_total_weight}}</td>\n' +
  '            <td> <div class="el-input" align="center"><input  :value="v.number_boxes" @input="checkNum($event.target,v,\'number_boxes\')" autocomplete="off" placeholder="" type="text" rows="2" validateevent="true" class="el-input__inner"></div></td>\n' +
  '            <td> <div class="el-input" align="center"><input v-model="v.number_per_box"  autocomplete="off" placeholder="" type="text" rows="2" validateevent="true" class="el-input__inner"></div> </td>\n' +
  '            <td>  <el-input v-model="v.case_number" placeholder=""></el-input></td>\n' +
  '            <td> <el-input v-model="v.box_length_and_width_cm" placeholder=""></el-input></td>\n' +
  '            <td> <div class="el-input" align="center"><input  :value="v.net_weight_kg" @input="checkNum2($event.target,v,\'net_weight_kg\')" autocomplete="off" placeholder="" type="text" rows="2" validateevent="true" class="el-input__inner"></div></td>\n' +
  '        </tr>\n' +
  '        <tr class="text-c">\n' +
  '            <td>{{$lang("合计")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,"transfer_authentic_products")}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,"transfer_defective_products")}}</div>\n' +
  '            </td>\n' +
  '            <td> </td>\n' +
  '            <td>{{all(goods,"estimated_total_weight")}} </td>\n' +
  '            <td>{{all(goods,"number_boxes")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>{{all(goods,"net_weight_kg")}}</td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table></div>'
})


/**
 * 作业信息
 */
Vue.component('TaskInfo', {
  props:['photo','work','option'],
  data: function () {
    return {
      fileList:[],
      curr:[],
      input:'',
      // planned_transportation_channel_cds:[]  ---没用上
    }
  },
  created:function(){
    // var _this = this
    this.getCurr()
    // 修改  ---数据没用上
    // axios.post('index.php?g=oms&m=CommonData&a=commonData', {
    //     data: {
    //         query: {
    //             "planned_transportation_channel_cds": true
    //         }
    //     }
    // }).then(function (response) {
    //     if (response.data.code == 2000) {
    //         _this.planned_transportation_channel_cds = response.data.data.planned_transportation_channel_cds
    //     }
    // })


  },
  computed:{
    phos:function(){
      var p = this.$props.work.job_photos;
      if(p && p.length>0){
        return p
      }else{
        return []
      }
    }
  },
  methods:{
    checkNum2:function(event,data,type){
      console.log(event,data,type)
      var _this = this;
      var r= /^[1-9][0-9]*$////^\d{1,9}(\.{0}|\.{1}\d{1,2})?$/
      var p = /^\d{1,9}(\.{0}|\.{1}\d{1,9})?$/
      var num = event.value
      if(!r.test(Number(event.value)) && !p.test(Number(event.value))){
        var n = Math.abs(parseInt(event.value))
        if(isNaN(n)){
          num = '0'
        }else{
          num = n
        }
      }
      if(event.value === ''){
        num = ''
      }
      if(String(num).split('').length>1){
        var ww = String(num).split('');
        if(ww[0] === '0' && ww[1] !=='.'){
          ww.splice(0,1)
        }
        num = ww.join('')
      }
      event.value = String(num)
      data[type] = String(num)
    },
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    handleRemove:function(file, fileList) {
      for(var x = 0;x<this.$props.photo.length;x++){
        if(file === this.$props.photo[x].photoObj){
          this.$props.photo.splice(x,1)
        }
      }
    },
    handlePreview:function(file) {

    },
    success:function(response, file, fileList){
      if(response.status === 1){
        var obj = {
          photoObj:file,
          data:{
            name: response.info[0].name,
            savepath: response.info[0].savepath,
            savename: response.info[0].savename
          }
        }
        this.$props.photo.push(obj)
      }
    },
    handleExceed:function(files, fileList) {
      this.$message.warning(`${this.$lang('当前限制选择 3 个文件，本次选择了 ')} ${files.length} ${this.$lang('个文件，共选择了')}  ${files.length + fileList.length} ${this.$lang('个文件')}`);
    },
    beforeRemove:function(file, fileList) {
      return this.$confirm(`${this.$lang('确定移除')} ${ file.name }？`);
    }
  },
  template: '    <table class="table table-striped table-detail-bg" style="width: 100%">\n' +
  '        <thead>\n' +
  '        <tr>\n' +
  '            <th colspan="7" class="text-l table-detail-title">{{$lang("作业信息")}}</th>\n' +
  '        </tr>\n' +
  '        </thead>\n' +
  '        <tbody class="text-c">\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("打托信息")}}<span v-if="option.edit" style="color: red">*</span></td>\n' +
  '            <td style="flex: 1"><el-input  v-if="option.edit"  v-model="work.beat_information" placeholder=""></el-input><div  v-if="!option.edit"  style="text-align: left">{{work.beat_information}}</div></td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("作业照片")}}<span v-if="option.edit" style="color: red">*</span></td>\n' +
  '            <td style="flex: 1"><el-upload v-if="option.edit" style="width: 400px;"\n' +
  '  class="upload-demo"\n' +
  '  action="/index.php?g=common&m=file&a=file_upload"\n' +
  '  :on-preview="handlePreview"\n' +
  '  accept="image/gif, image/jpeg, image/png,image/jpg,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.csv"'+
  '  :on-remove="handleRemove"\n' +
  '  :before-remove="beforeRemove"\n' +
  '   :on-success="success"'+
  '  multiple\n' +
  '  :limit="3"\n' +
  '  :on-exceed="handleExceed"\n' +
  '  :file-list="fileList">\n' +
  '  <el-button size="small" type="primary">{{$lang("点击上传")}}</el-button>\n' +
  '  <div slot="tip" class="el-upload__tip">{{$lang("只能上传jpg/png,.xls,.xlsx,.csv文件，且不超过500kb")}}</div>\n' +
  '</el-upload>' +
  '<a v-for="(v,k) in phos" :href="\'/index.php?m=order_detail&a=download&file=\'+ v.savename" style="display: block;text-align: left;color:blue">{{v.name}}</a>'+
  '</td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("作业备注")}}<span v-if="option.edit" style="color: red">*</span></td>\n' +
  '            <td style="flex: 1"><el-input v-if="option.edit" v-model="work.job_note" placeholder=""></el-input><div  v-if="!option.edit"  style="text-align: left">{{work.job_note}}</div></td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("作业费用 (预估) ")}}<span v-if="option.edit" style="color: red">*</span></td>\n' +
  '            <td style="flex: 1">' +
  '                <el-input ref="input1" v-if="option.edit"  placeholder="" :value="work.operating_expenses" @input.native="checkNum2($event.target,work,\'operating_expenses\')" class="input-with-select" type="number">\n' +
  '                    <el-select v-model="work.operating_expenses_currency_cd" slot="prepend">\n' +
  '                        <el-option :key="k" v-for="(v,k) in curr" :label="$lang(v.CD_VAL)" :value="v.CD"></el-option>\n' +
  '                    </el-select>\n' +
  '                </el-input>\n' +
  '                <div v-if="!option.edit"  style="text-align: left">{{work.operating_expenses_currency_cd_val + \' \'+work.operating_expenses}}</div>'+
  '</td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("增值服务费 (预估)")}} <span style="color: red" v-if="option.edit">*</span></td>\n' +
  '            <td style="flex: 1">' +
  '                <el-input v-if="option.edit"  placeholder="" :value="work.value_added_service_fee" @input.native="checkNum2($event.target,work,\'value_added_service_fee\')" class="input-with-select">\n' +
  '                    <el-select v-model="work.value_added_service_fee_currency_cd" slot="prepend">\n' +
  '                        <el-option :key="k" v-for="(v,k) in curr" :label="$lang(v.CD_VAL)" :value="v.CD"></el-option>\n' +
  '                    </el-select>\n' +
  '                </el-input>\n' +
  '                <div v-if="!option.edit" style="text-align: left">{{work.value_added_service_fee_currency_cd_val + \' \'+work.value_added_service_fee}}</div>'+
  '</td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("总重量 (KG)")}} <span style="color: red" v-if="option.edit">*</span></td>\n' +
  '            <td style="flex: 1">' +
  '                <el-input v-if="option.edit"  placeholder="" :value="work.total_weight" @input.native="checkNum2($event.target,work,\'total_weight\')" type="number" class="input-with-select">\n' +
  '                </el-input>\n' +
  '                <div v-if="!option.edit" style="text-align: left">{{work.total_weight}}</div>'+
  '</td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("总体积 (立方米)")}} <span style="color: red" v-if="option.edit">*</span></td>\n' +
  '            <td style="flex: 1">' +
  '                <el-input v-if="option.edit" placeholder="" :value="work.total_volume" @input.native="checkNum2($event.target,work,\'total_volume\')" type="number" class="input-with-select">\n' +
  '                </el-input>\n' +
  '                <div v-if="!option.edit" style="text-align: left">{{work.total_volume}}</div>'+
  '</td>\n' +
  '        </tr>\n' +
  '        <tr  style="    display: flex;width: 100%;">\n' +
  '            <td  class="w-200" style="max-width: 250px;width:250px;overflow: hidden">{{$lang("总箱数 (箱)")}} <span style="color: red" v-if="option.edit">*</span></td>\n' +
  '            <td style="flex: 1">' +
  '                <el-input v-if="option.edit"  placeholder="" :value="work.total_box_num" @input.native="checkNum2($event.target,work,\'total_box_num\')" type="number" class="input-with-select">\n' +
  '                </el-input>\n' +
  '                <div v-if="!option.edit" style="text-align: left">{{work.total_box_num}}</div>'+
  '</td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})

Vue.component('OutRecord1', {
  props:['outstocks','option','goods','index','length','alloid','info'],
  data: function () {
    return {
      list:[],
      insurance_types:['','为我司自缴','为服务商代缴','为未购买保险'],
      gj:{
        '1':'订舱安排中',
        '2':'已出库,待离港',
        '3':'航行中',
        '4':'清关中',
        '5':'清关完成，待送仓',
        '6':'已送仓，待上架',
        '7':'上架中 ',
        '8':'上架完成',
      },
      cube_feet_types:['','立方米', 'KGS'],
      bs:[ '暂无', '散装', '打板'],
      indexN:(function(){
        var x = 1
        return function(){
          return x++
        }
      })()
      // planned_transportation_channel_cds:[] ---数据没用上
    }
  },
  computed:{
    statistical:function (){
      var arr = [0,0,0];
      this.$props.outstocks.goods.forEach(function(v){
        arr[0] += Number(v.number_authentic_outbound_this?v.number_authentic_outbound_this:0) + Number(v.number_defective_outbound_this?v.number_defective_outbound_this:0)
        arr[1] += Number(v.in_sum)
        arr[2] += Number(v.differ_num)
      })
      return arr
    },
    out:function(){
      var n = this.outstocks.logistics_information.insurance_fee;
      if(this.outstocks.logistics_information.insurance_fee_currency_cd_val && this.outstocks.logistics_information.insurance_fee === '0.00'){
        n = 0
      }
      if(!this.outstocks.logistics_information.insurance_fee_currency_cd_val && this.outstocks.logistics_information.insurance_fee === '0.00'){
        n = ''
      }
      return n
    }
  },
  created:function(){
    // 修改--数据没用上
    // var _this = this
    // axios.post('index.php?g=oms&m=CommonData&a=commonData', {
    //     data: {
    //         query: {
    //             "planned_transportation_channel_cds": true
    //         }
    //     }
    // }).then(function (response) {
    //     if (response.data.code == 2000) {
    //         _this.planned_transportation_channel_cds = response.data.data.planned_transportation_channel_cds
    //     }
    // })
  },
  methods:{
    cyc:function(id,out_stock_id){
      var _this = this;
      if(this.outstocks.logistics_information.is_auth_push_message === 0){
        this.$message({
          message: _this.$lang('您当前没有操作权限'),
          type: 'warning'
        })
        return
      }
      axios.post('/index.php?m=AllocationExtendNew&a=remind&id='+id+'&out_stock_id='+out_stock_id).then(function (response) {
        if(response.data.code === 200){
          _this.$message({
            message: _this.$lang('催办成功'),
            type: 'success'
          });
        }else{
          _this.$message({
            message: response.data.msg,
            type: 'error'
          });
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    },
    show:function(id){
      console.log(id)
      var _this = this
      axios.get('index.php?m=AllocationExtendNew&a=getOutStockLogisticsRemark&out_stock_id='+id).then(function (response) {
        if(response.data.code === 200){
          if(response.data.data !== null){
            _this.list = response.data.data
          }
        } else {
          _this.$message({
            message: response.data.msg,
            type: 'error'
          });
        }
      })
    },
    orderDetal: function (_href, title) {
      // if(this.outstocks.logistics_information.is_auth_edit === 0){
      //   this.$message({
      //     message: this.$lang('您当前没有操作权限'),
      //     type: 'warning'
      //   })
      //   return
      // }
      var dom = document.createElement('a');

      dom.setAttribute("onclick", "opennewtab(this,'"+this.$lang(title) + "')");
      dom.setAttribute("_href", _href);
      dom.click();
    },
    handleInStock(allo_id, out_stock_id) {
      axios.get(`/index.php?m=AllocationExtendNew&a=verifyOutStockLogistics&allo_id=${allo_id}&out_stock_id=${out_stock_id}`).then(res => {
        if (res.data.code === 2000) {
          this.orderDetal(`/index.php?m=allocation_extend_new&a=in_storage&id=${allo_id}&_index=${this.index}`, this.$lang('入库详情页'))
        } else {
          this.$message.error(res.data.msg);
        }
      })
    },
    open:function(){
      var text = `<div style="width: 100%"><span style="width: 50%;display: inline-block">${this.$lang('具体原因内容')}: ${this.$props.outstocks.logistics_information.reason_difference?this.$props.outstocks.logistics_information.reason_difference:''}</span><span style="width: 50%;display: inline-block">${this.$lang('时间')}：${this.$props.outstocks.logistics_information.in_stock_complete_at?this.$props.outstocks.logistics_information.in_stock_complete_at:''}</span></div><div style="width: 100%;"><span style="width: 50%;display: inline-block"></span><span style="width: 50%;display: inline-block">${this.$lang('操作人')}：${this.$props.outstocks.logistics_information.in_stock_complete_by?this.$props.outstocks.logistics_information.in_stock_complete_by:''}</span></div>`
      this.$alert(text, this.$lang('差异原因'), {
        confirmButtonText: this.$lang('确定'),
        dangerouslyUseHTMLString: true
      });
    },
    bz:function(id){
      var _this = this;
      this.$prompt(this.$lang('轨迹情况细则说明'), '', {
        confirmButtonText: _this.$lang('确定'),
        cancelButtonText: _this.$lang('取消'),
        inputPattern: '',
        inputErrorMessage: ''
      }).then(({ value }) => {
        if(!value){
          return
        }
        axios.post('index.php?m=AllocationExtendNew&a=submitOutStockRemark&out_stock_id='+id,{remark:value}).then(function (response) {
          if(response.data.code === 200){
            _this.$message({
              message: _this.$lang('保存成功'),
              type: 'success'
            });
          }else{
            _this.$message({
              message: response.data.msg,
              type: 'error'
            });
          }
        })
      }).catch(() => {
        this.$message({
          type: 'info',
          message: this.$lang('取消输入')
        });
      });
    },
    getTemplate() {
      
    }
  },
  template: 
  '<div v-if="info.allo_out_team !== \'N001282800\'"> ' +
  '<table class="table table-bg">\n' +
  '        <thead>\n' +
  '        <tr>\n' +
  '            <th colspan="9" class="text-l table-detail-title">{{$lang("出库记录")}}({{index}}/{{length}})</th>\n' +
  '        </tr>\n' +
  '        </thead>\n' +
  '        <thead>\n' +
  '        <th>{{$lang("序号")}}</th>\n' +
  '        <th>{{$lang("SKU条码")}}</th>\n' +
  '        <th>{{$lang("条形码")}}</th>\n' +
  '        <th>{{$lang("商品名称")}}</th>\n' +
  '        <th>{{$lang("商品属性")}}</th>\n' +
  '        <th>{{$lang("商品图片")}}</th>\n' +
  '        <th>{{$lang("出库数量")}}</th>\n' +
  '        <th>{{$lang("已入库数量")}}</th>\n' +
  '        <th>{{$lang("差异数量")}}</th>\n' +
  '        </thead>\n' +
  '        <tbody>\n' +
  '<template v-for="(v,k) in outstocks.goods">'+
  '        <tr class="text-c" v-if="(Number(v.number_authentic_outbound_this)+Number(v.number_defective_outbound_this))>0">\n' +
  '            <td>{{indexN()}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td>{{v.upc_id}}</td>\n' +
  '            <td>{{v.spu_name}}</td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td> <div>{{$lang("正品")+\':\'+(Number(v.number_authentic_outbound_this?v.number_authentic_outbound_this:0))}}</div>' +
  '<div>{{$lang("残次品")+\':\'+(Number(v.number_defective_outbound_this?v.number_defective_outbound_this:0))}}</div>'+
  '</td>\n' +
  '            <td> <div>{{$lang("正品")+\':\'+(Number(v.number_authentic_warehousing?v.number_authentic_warehousing:0))}}</div>' +
  '<div>{{$lang("残次品")+\':\'+(Number(v.number_defective_warehousing?v.number_defective_warehousing:0))}}</div>'+
  '</td>\n' +
  '            <td> {{v.differ_num}}</td>\n' +
  '        </tr>\n' +
  '</template>'+
  '         <tr class="text-c"><td>{{$lang(\'合计\')}}</td><td colspan="5"></td><td>{{statistical[0]}}</td><td>{{statistical[1]}}</td><td><el-button type="text" @click="open" v-if="statistical[2] !==0">{{statistical[2]}}</el-button><span v-else>0</span></td></tr>'+
  '        </tbody>\n' +
  '    </table>'+
  '<table class="table table-striped table-detail-bg">\n' +
  '        <thead>\n' +
  '        <tr>\n' +
  '            <th colspan="7" class="text-l table-detail-title">{{$lang("物流信息")}}</th>\n' +
  '        </tr>\n' +
  '        </thead>\n' +
  '        <tbody class="text-c">\n' +
  '        <tr>\n' +
  '            <td class="w-200">{{$lang("调拨单号")}}</td>\n' +
  '            <td class="">{{info.allo_no}}</td>\n' +
  '            <td  class="w-200">{{$lang("保险缴纳方")}}</td>\n' +
  '            <td>{{$lang(insurance_types[outstocks.logistics_information.insurance_type])}}</td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td class="w-200">{{$lang("运输公司")}}</td>\n' +
  '            <td class="">{{$lang(outstocks.logistics_information.transport_company_id_val)}}</td>\n' +
  '            <td  class="w-200">{{$lang("保险范围")}}</td>\n' +
  '            <td style="white-space: initial;">{{$lang(outstocks.logistics_information.insurance_coverage_cd_val)}}</td>\n' +
  '        <tr>\n' +
  '            <td class="w-200">{{$lang("运输渠道")}}</td>\n' +
  '            <td>{{$lang(outstocks.logistics_information.planned_transportation_channel_cd_val_val)}}</td>\n' +
  '            <td  class="w-200">{{$lang("保险费率")}}</td>\n' +
  '            <td style="white-space: initial;">{{outstocks.logistics_information.insurance_claims_cd_val}}</td>\n' +
  '        </tr>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("入仓单号/So号")}}</td>\n' +
  '            <td style="white-space: initial;">{{outstocks.logistics_information.third_party_warehouse_entry_number}}</td>\n' +
  '            <td  class="w-200">{{$lang("保险费用（预估）")}}</td>\n' +
  '            <td style="white-space: initial;">{{(outstocks.logistics_information.insurance_fee_currency_cd_val?outstocks.logistics_information.insurance_fee_currency_cd_val:\'\')+\' \'+ out}}</td>\n' +
  /*  '            <td  class="w-200">{{$lang("有无保险")}}</td>\n' +
    '            <td>{{(outstocks.logistics_information.have_insurance === \'1\'?$lang(\'有\'):\'\')}}{{(outstocks.logistics_information.have_insurance === \'0\'?$lang(\'无\'):\'\')}}</td>\n' +*/
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("出库板数")}}</td>\n' +
  '            <td>{{(outstocks.logistics_information.out_plate_number_type?$lang(bs[Number(outstocks.logistics_information.out_plate_number_type)-1]):\'\')+\' \'+ (outstocks.logistics_information.out_plate_number_val?outstocks.logistics_information.out_plate_number_val+$lang(\'箱\'):\'\')}}</td>\n' +
  '            <td  class="w-200">{{$lang("计费重/材积")}}</td>\n' +
  '            <td>{{(outstocks.logistics_information.cube_feet_val?outstocks.logistics_information.cube_feet_val:\'\')+\' \'+$lang((cube_feet_types[Number(outstocks.logistics_information.cube_feet_type)]?cube_feet_types[Number(outstocks.logistics_information.cube_feet_type)]:\'\')) }}</td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("柜型")}}</td>\n' +
  '            <td>{{outstocks.logistics_information.cabinet_type_val}}</td>\n' +
  '            <td  class="w-200">{{$lang("头程物流费（预估）")}}</td>\n' +
  '            <td>{{(outstocks.logistics_information.head_logistics_fee_currency_cd_val?outstocks.logistics_information.head_logistics_fee_currency_cd_val:\'\')+\' \'+ (outstocks.logistics_information.head_logistics_fee===\'0.00\'?\'\':outstocks.logistics_information.head_logistics_fee)}}</td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200"><span v-if="outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820001\'|| outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820003\'">{{$lang("柜号")}}</span></td>\n' +
  '            <td><span v-if="outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820001\'|| outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820003\'">{{outstocks.logistics_information.cabinet_number}}</span></td>\n' +
  '            <td  class="w-200">{{$lang("出库费用")}}</td>\n' +
  '            <td>{{(outstocks.logistics_information.outbound_cost_currency_cd_val?outstocks.logistics_information.outbound_cost_currency_cd_val:\'\')+\' \'+ outstocks.logistics_information.outbound_cost}}</td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200"><span v-if="outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820001\'|| outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820003\'">{{$lang("封条")}}</span></td>\n' +
  '            <td><span v-if="outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820001\'|| outstocks.logistics_information.planned_transportation_channel_cd ===\'N002820003\'">{{outstocks.logistics_information.strip_p_seal}}</span></td>\n' +
  '            <td  class="w-200"></td>\n' +
  '            <td></td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("清关方式")}}</td>\n' +
  '            <td>{{$lang(outstocks.logistics_information.customs_clear_val)}}</td>\n' +
  '            <td  class="w-200"></td>\n' +
  '            <td></td>\n' +
  '        </tr>\n' +
  '        <tr>' +
  '            <td  class="w-200">{{$lang("送仓方式")}}</td>\n' +
  '            <td>{{$lang(outstocks.logistics_information.send_warehouse_way_val)}}</td>\n' +
  '            <td  class="w-200"></td>\n' +
  '            <td></td>\n' +
  '        </tr>'+
  '        <tr>' +
  '            <td class="w-200"><span  v-show="$lang(outstocks.logistics_information.send_warehouse_way_val) !==$lang(\'卡派\')">{{$lang("快递单号")}}</span></td>\n' +
  '            <td><span  v-show="$lang(outstocks.logistics_information.send_warehouse_way_val) !==$lang(\'卡派\')">{{outstocks.logistics_information.tracking_number}}</span></td>\n' +
  '            <td  class="w-200"></td>\n' +
  '            <td></td>\n' +
  '        </tr>'+
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("货物运输轨迹")}}</td>\n' +
  '            <td>{{$lang(outstocks.logistics_information.logistics_state)}}</td>\n' +
  '            <td  class="w-200">{{$lang("系统预估上架完成时间")}}</td>\n' +
  '            <td><el-popover\n' +
  '  placement="bottom"\n' +
  '  width="400"\n' +
  '  trigger="click" @show="show(outstocks.logistics_information.out_stock_id)" v-if="outstocks.logistics_information.node_system_plan_time">\n' +
  '  <el-table class="table1" :data="list" :border="true" style="margin: 0;text-align: center">\n' +
  '    <el-table-column  type="index" :label="$lang(\'序号\')"></el-table-column>\n' +
  '    <el-table-column  property="remark" :label="$lang(\'具体内容\')"></el-table-column>\n' +
  '    <el-table-column  property="created_by" :label="$lang(\'备注人\')"></el-table-column>\n' +
  '    <el-table-column  property="created_at" :label="$lang(\'备注时间\')"></el-table-column>\n' +
  '  </el-table>\n' +
  '  <el-button slot="reference">{{outstocks.logistics_information.node_system_plan_time}}</el-button>\n' +
  '</el-popover><el-button v-if="outstocks.logistics_information.node_system_plan_time" @click="bz(outstocks.logistics_information.out_stock_id)">{{$lang(\'备注\')}}</el-button></td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("操作")}}</td>\n' +
  '            <td><el-button :disabled="outstocks.logistics_information.is_edit===1?false:true" @click="orderDetal(\'/index.php?m=allocation_extend_new&a=logistics_nodes&id=\'+alloid+\'&_index=\'+index,$lang(\'物流信息详情\'))" >{{$lang("编辑")}}</el-button><el-button @click="orderDetal(\'/index.php?m=allocation_extend_new&a=shipping_details&id=\'+alloid+\'&out_stock_id=\'+outstocks.logistics_information.out_stock_id,$lang(\'运输节点详情页\'))">{{$lang("更新节点")}}</el-button><el-button @click="orderDetal(\'/index.php?m=allocation_extend_new&a=in_storage&id=\'+alloid+\'&_index=\'+index,$lang(\'入库详情页\'))" :disabled="outstocks.logistics_information.in_stock===1?false:true">{{$lang("入库")}}</el-button><el-button @click="cyc(alloid,outstocks.logistics_information.out_stock_id)" :disabled="outstocks.logistics_information.send_wechat_message===1?false:true">{{$lang("催办")}}</el-button></td>\n' +
  '            <td  class="w-200"></td>\n' +
  '            <td></td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'+
  '</div>'
  +
  `
    <div v-else>
      <table class="table table-bg">
        <thead>
          <tr>
            <th colspan="9" class="text-l table-detail-title">{{$lang("出库记录")}}({{index}}/{{length}})</th>
              </tr>
        </thead>
        <thead>
          <th>{{$lang("序号")}}</th>
          <th>{{$lang("SKU条码")}}</th>
          <th>{{$lang("条形码")}}</th>
          <th>{{$lang("商品名称")}}</th>
          <th>{{$lang("商品属性")}}</th>
          <th>{{$lang("商品图片")}}</th>
          <th>{{$lang("出库数量")}}</th>
          <th>{{$lang("已入库数量")}}</th>
          <th>{{$lang("差异数量")}}</th>
        </thead>
        <tbody>
          <template v-for="(v,k) in outstocks.goods">
            <tr class="text-c" v-if="(Number(v.number_authentic_outbound_this)+Number(v.number_defective_outbound_this))>0">
              <td>{{indexN()}}</td>
              <td>{{v.sku_id}}</td>
              <td>{{v.upc_id}}</td>
              <td>{{v.spu_name}}</td>
              <td>{{v.attributes}}</td>
              <td>
                  <el-popover
                    v-if="v.image_url"
                    placement="right"
                    title=""
                    trigger="hover">
                      <img :src="v.image_url" style="height: 300px;"/>
                      <img slot="reference" :src="v.image_url"  style="height: 50px;">
                  </el-popover>
              </td>
              <td>
                <div>{{$lang("正品")}}:{{Number(v.number_authentic_outbound_this?v.number_authentic_outbound_this:0)}}</div>
                <div>{{$lang("残次品")}}:{{Number(v.number_defective_outbound_this?v.number_defective_outbound_this:0)}}</div>
              </td>
              <td>
                <div>{{$lang("正品")}}:{{Number(v.number_authentic_warehousing?v.number_authentic_warehousing:0)}}</div>
                <div>{{$lang("残次品")}}:{{Number(v.number_defective_warehousing?v.number_defective_warehousing:0)}}</div>
              </td>
              <td>{{v.differ_num}}</td>
            </tr>
          </template>
            <tr class="text-c">
              <td>{{$lang("合计")}}</td>
              <td colspan="5"></td><td>{{statistical[0]}}</td>
              <td>{{statistical[1]}}</td>
              <td><el-button type="text" @click="open" v-if="statistical[2] !==0">{{statistical[2]}}</el-button><span v-else>0</span></td>
            </tr>
        </tbody>
      </table>
      <table class="table table-striped table-detail-bg">
        <thead>
          <tr>
            <th colspan="7" class="text-l table-detail-title">{{$lang("物流信息")}}</th>
          </tr>
          </thead>
          <tbody class="text-c">
          <tr>
            <td class="w-200">{{$lang("调拨单号")}}</td>
            <td class="">{{info.allo_no}}</td>
            <template v-if="outstocks.logistics_information.is_optimize_team === 1 && outstocks.logistics_information.planned_transportation_channel_cd === 'N002820001'">
              <td class="w-200">{{$lang("船公司")}}</td>
              <td>{{$lang(outstocks.logistics_information.shipping_company_name)}}</td>
            </template>
            <template v-else>
              <td class="w-200"></td>
              <td></td>
            </template>
          </tr>
          <tr>
            <td class="w-200">{{$lang("运输公司")}}</td>
            <td>{{$lang(outstocks.logistics_information.transport_company_id_val)}}</td>
            <td class="w-200">{{$lang("保险缴纳方")}}</td>
            <td>{{$lang(insurance_types[outstocks.logistics_information.insurance_type])}}</td>
          </tr>
          <tr>
            <td class="w-200">{{$lang("运输渠道")}}</td>
            <td>{{$lang(outstocks.logistics_information.planned_transportation_channel_cd_val_val)}}</td>
            <td  class="w-200">{{$lang("保险范围")}}</td>
            <td style="white-space: initial;">{{$lang(outstocks.logistics_information.insurance_coverage_cd_val)}}</td>
          </tr>
          <tr>
            <td class="w-200">{{$lang("入仓单号/So号")}}</td>
            <td style="white-space: initial;">{{outstocks.logistics_information.third_party_warehouse_entry_number}}</td>
            <td  class="w-200">{{$lang("保险费率")}}</td>
            <td style="white-space: initial;">{{outstocks.logistics_information.insurance_claims_cd_val}}</td>
          </tr>
          <tr>
             <template v-if="outstocks.logistics_information.is_optimize_team === 1">
              <td class="w-200">{{$lang("海外仓入库单号")}}</td>
              <td style="white-space: initial;">{{outstocks.logistics_information.oversea_in_storage_no}}</td>
             </template>
             <template v-else>
               <td class="w-200"></td>
               <td></td>
             </template>
             <td class="w-200">{{$lang("保险费用（预估）")}}</td>
             <td style="white-space: initial;">{{(outstocks.logistics_information.insurance_fee_currency_cd_val||"") + " " + out}}</td>
          </tr>
          <tr>
            <td class="w-200">{{$lang("柜型")}}</td>
            <td>{{outstocks.logistics_information.cabinet_type_val}}</td>
            <td  class="w-200">{{$lang("计费重/材积")}}</td>
            <td>{{(outstocks.logistics_information.cube_feet_val || "")+ " " + $lang((cube_feet_types[Number(outstocks.logistics_information.cube_feet_type)]?cube_feet_types[Number(outstocks.logistics_information.cube_feet_type)]:"")) }}</td>
          </tr>
          <tr>
            <td  class="w-200">{{$lang("出库板数")}}</td>
            <td>{{(outstocks.logistics_information.out_plate_number_type?$lang(bs[Number(outstocks.logistics_information.out_plate_number_type)-1]):"")+" "+ (outstocks.logistics_information.out_plate_number_val?outstocks.logistics_information.out_plate_number_val+$lang("箱"):"")}}</td>
            <td  class="w-200">{{$lang("头程物流费（预估）")}}</td>
            <td>{{(outstocks.logistics_information.head_logistics_fee_currency_cd_val || '')+' '+ outstocks.logistics_information.head_logistics_fee}}</td>
          </tr>
          <tr>
            <td class="w-200">
              <span v-if="outstocks.logistics_information.planned_transportation_channel_cd === 'N002820001' || outstocks.logistics_information.planned_transportation_channel_cd === 'N002820003'">{{$lang("柜号")}}</span>
            </td>
            <td>
              <span v-if="outstocks.logistics_information.planned_transportation_channel_cd === 'N002820001' || outstocks.logistics_information.planned_transportation_channel_cd === 'N002820003'">{{outstocks.logistics_information.cabinet_number}}</span>
            </td>
            <td  class="w-200">{{$lang("出库费用")}}</td>
            <td>{{(outstocks.logistics_information.outbound_cost_currency_cd_val || '')+ ' ' + outstocks.logistics_information.outbound_cost}}</td>
          </tr>
          <tr>
            <td  class="w-200">{{$lang("清关方式")}}</td>
            <td>{{$lang(outstocks.logistics_information.customs_clear_val)}}</td>
            <td class="w-200">
              <span v-if="outstocks.logistics_information.planned_transportation_channel_cd === 'N002820001' || outstocks.logistics_information.planned_transportation_channel_cd === 'N002820003'">{{$lang("封条")}}</span>
            </td>
            <td>
              <span v-if="outstocks.logistics_information.planned_transportation_channel_cd === 'N002820001' || outstocks.logistics_information.planned_transportation_channel_cd === 'N002820003'">{{outstocks.logistics_information.strip_p_seal}}</span>
            </td>
          </tr>
          <tr>
            <td class="w-200">{{$lang("送仓方式")}}</td>
            <td>{{$lang(outstocks.logistics_information.send_warehouse_way_val)}}</td>
            <td class="w-200"></td>
            <td></td>
          </tr>
          <tr>
            <td class="w-200">
              <span v-show="$lang(outstocks.logistics_information.send_warehouse_way_val) !== $lang('卡派')">{{$lang("快递单号")}}</span></td>
            <td>
              <span v-show="$lang(outstocks.logistics_information.send_warehouse_way_val) !==$lang('卡派')">{{outstocks.logistics_information.tracking_number}}</span>
            </td>
            <td class="w-200"></td>
            <td></td>
          </tr>
          <tr>
            <td class="w-200">{{$lang("货物运输轨迹")}}</td>
            <td>{{$lang(outstocks.logistics_information.logistics_state)}}</td>
            <td class="w-200">{{$lang("系统预估上架完成时间")}}</td>
            <td>
            <el-popover
              placement="bottom"
              width="400"
              trigger="click"
              @show="show(outstocks.logistics_information.out_stock_id)"
              v-if="outstocks.logistics_information.node_system_plan_time">
                <el-table class="table1" :data="list" :border="true" style="margin: 0;text-align: center">
                  <el-table-column  type="index" :label="$lang('序号')"></el-table-column>
                  <el-table-column  property="remark" :label="$lang('具体内容')"></el-table-column>
                  <el-table-column  property="created_by" :label="$lang('备注人')"></el-table-column>
                  <el-table-column  property="created_at" :label="$lang('备注时间')"></el-table-column>
                </el-table>
                <el-button slot="reference">{{outstocks.logistics_information.node_system_plan_time}}</el-button>
            </el-popover>
            <el-button v-if="outstocks.logistics_information.node_system_plan_time" @click="bz(outstocks.logistics_information.out_stock_id)">{{$lang('备注')}}</el-button></td>
          </tr>
          <tr>
            <td class="w-200">{{$lang("操作")}}</td>
            <td>
              <el-button :disabled="outstocks.logistics_information.ori_logistics_state === '8'" @click="orderDetal('/index.php?m=allocation_extend_new&a=logistics_nodes&id=' + alloid + '&_index=' + index,$lang('物流信息详情'))">
                {{$lang("编辑")}}
              </el-button>
              <el-button @click="orderDetal('/index.php?m=allocation_extend_new&a=shipping_details&id=' + alloid + '&out_stock_id=' + outstocks.logistics_information.out_stock_id,$lang('运输节点详情页'))" >{{$lang("更新节点")}}</el-button>
              <el-button @click="handleInStock(alloid,outstocks.logistics_information.out_stock_id)" :disabled="outstocks.logistics_information.in_stock===1?false:true">{{$lang("入库")}}</el-button>
              <el-button @click="cyc(alloid,outstocks.logistics_information.out_stock_id)" :disabled="outstocks.logistics_information.send_wechat_message===1?false:true">{{$lang("催办")}}</el-button>
            </td>
            <td class="w-200"></td>
            <td></td>
          </tr>
        </tbody>
      </table>
  </div>
  `,
})


/**
 *  信息
 */

Vue.component('InRecord1', {
  props:['instocks','option','goods','index','length'],
  data: function () {
    return {
      // planned_transportation_channel_cds:[] ---数据没用上
    }
  },
  created:function(){
    // 修改---数据没用上
    // var _this = this
    // axios.post('index.php?g=oms&m=CommonData&a=commonData', {
    //     data: {
    //         query: {
    //             "planned_transportation_channel_cds": true
    //         }
    //     }
    // }).then(function (response) {
    //     if (response.data.code == 2000) {
    //         _this.planned_transportation_channel_cds = response.data.data.planned_transportation_channel_cds
    //     }
    // })
  },
  methods:{
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    }
  },
  template: '<div> <table class="table table-striped table-detail-bg">\n' +
  '        <thead>\n' +
  '        <tr>\n' +
  '            <th colspan="7" class="text-l table-detail-title">{{$lang("入库记录")}} ({{index}}/{{length}})</th>\n' +
  '        </tr>\n' +
  '        </thead>\n' +
  '        <tbody class="text-c">\n' +
  '        <tr>\n' +
  '            <td class="w-200">{{$lang("关税（预估）")}}</td>\n' +
  '            <td class="">{{instocks.logistics_information.tariff_currency_cd_val+ " "+ instocks.logistics_information.tariff }}</td>\n' +
  '            <td class="w-200">{{$lang("上架费用（预估）")}}</td>\n' +
  '            <td>{{instocks.logistics_information.shelf_cost_currency_cd_val+ " "+ instocks.logistics_information.shelf_cost }}</td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("增值服务费（预估）")}}</td>\n' +
  '            <td>{{instocks.logistics_information.value_added_service_fee_currency_cd_val+ " "+ instocks.logistics_information.value_added_service_fee }}</td>\n' +
  '            <td  class="w-200">{{$lang("入库单号")}}</td>\n' +
  '            <td  style="white-space: initial;">{{instocks.logistics_information.bill_id }}</td>\n' +
  '        </tr>\n' +
  '        <tr>\n' +
  '            <td  class="w-200">{{$lang("操作人")}}</td>\n' +
  '            <td>{{instocks.logistics_information.created_by}}</td>\n' +
  '            <td  class="w-200">{{$lang("操作时间")}}</td>\n' +
  '            <td>{{instocks.logistics_information.created_at}}</td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'+
  '<table class="table table-bg">\n' +
  '        <thead>\n' +
  '        <th>{{$lang("SKU条码")}}</th>\n' +
  '        <th>{{$lang("条形码")}}</th>\n' +
  '        <th>{{$lang("商品名称")}}</th>\n' +
  '        <th>{{$lang("商品属性")}}</th>\n' +
  '        <th>{{$lang("商品图片")}}</th>\n' +
  '        <th>{{$lang("商品类型")}}</th>\n' +
  '        <th>{{$lang("入库数量")}}</th>\n' +
  '        </thead>\n' +
  '        <tbody>\n' +
  ' <template  v-for="(v,k) in instocks.goods">'+
  '        <tr class="text-c" v-if="Number(v.this_in_authentic_products)>0">\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td>{{v.upc_id}}</td>\n' +
  '            <td>{{v.spu_name}}</td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td>{{$lang("正品")}} </td>\n' +
  '            <td> {{v.this_in_authentic_products}}</td>\n' +
  '        </tr>\n' +
  '        <tr class="text-c" v-if="Number(v.this_in_defective_products)>0">\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td>{{v.upc_id}}</td>\n' +
  '            <td>{{v.spu_name}}</td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td>{{$lang("残次品")}}</td>\n' +
  '            <td> {{v.this_in_defective_products}}</td>\n' +
  '        </tr>\n' +
  '</template>'+
  '        </tbody>\n' +
  '    </table>'+
  '</div>'
})

/**
 * 出库信息
 */
Vue.component('GoodListOut', {
  props:['goods','option','info','goods2'],
  data: function () {
    return {
      curr:[],
      hv:'',
      thOption: [
        {title: this.$lang('序号')},
        {title: this.$lang('SKU条码')},
        {title: this.$lang('条形码')},
        {title: this.$lang('商品名称')},
        {title: this.$lang('商品属性')},
        {title: this.$lang('商品图片')},
        {title: this.$lang('重量（kg/单个）')},
        {title: this.$lang('预计总重（kg）')},
        {title: this.$lang('箱数（箱）')},
        {title: this.$lang('每箱个数')},
        {title: this.$lang('箱号')},
        {title: this.$lang('箱子长宽高（cm）')},
        {title: this.$lang('净重（kg）')},
        {title: this.$lang('调拨数量')},
        {title: this.$lang('出库数量')},
        {title: this.$lang('本次出库（正品）')},
        {title: this.$lang('本次出库（残次品）')},
        {title: this.$lang('差异数')},
      ]
    }
  },
  computed:{
    // totalOutStorageAllAuthenticProducts:function () {
    //     let total=0;
    //     this.goods.forEach((v,k)=>{
    //         total +=  (v.number_authentic_outbound - v.number_authentic_warehousing - v.this_in_authentic_products);
    //     })
    //     return total;
    // },
    // totalOutStorageAllDefectiveProducts:function () {
    //     let total=0;
    //     this.goods.forEach((v,k)=>{
    //         total +=  (v.number_defective_outbound - v.number_defective_warehousing - v.this_in_defective_products);
    //     })
    //     return total;
    // },
  },
  created: function () {
    var _this = this;
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
  },
  methods: {
    openOut:function(){
      this.orderDetal(id,'出库单打印')
    },
    orderDetal: function (orderId, title) {
      var dom = document.createElement('a');
      var _href;
      if (title === '出库单打印') {
        _href = '/index.php?m=allocation_extend_new&a=out_print&id='+orderId
      }
      dom.setAttribute("onclick", "opennewtab(this,'"+this.$lang(title) + "')");
      dom.setAttribute("_href", _href);
      dom.click();
    },
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    },
    ExportBoxInformation:function(){
      var json = JSON.parse(JSON.stringify(this.$props.goods));
      var arr = []
      for(var x =0;x <  json.length;x++){
        var obj = {
          'SKU条码':json[x].sku_id,
          '条形码':json[x].upc_id,
          '商品名称':json[x].spu_name,
          '商品属性':json[x].attributes,
          '重量（kg/单个）':json[x].weight,
          '预计总重(kg)':json[x].estimated_total_weight,
          '箱数（箱）':json[x].number_boxes,
          '每箱个数':json[x].number_per_box,
          '箱号':json[x].case_number,
          '箱子长宽高':json[x].box_length_and_width_cm,
          '净重（kg）':json[x].net_weight_kg,
        }
        arr.push(obj)
      }
      downloadExl(arr,'','导出列表')
    },
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    }
  },
  template:'<table class="table table-bg">\n' +

  '<thead>\n' +
  '<tr>'+
  '<th colspan="16" style="text-align: left;font-size: 15px;text-indent: 20px;padding: 0;">{{$lang("商品名称")}}</th>'+
  '<th colspan="2"><el-button type="primary" size="mini" @click="openOut">{{$lang("打印出库单")}}</el-button><el-button type="primary" size="mini" @click="ExportBoxInformation">{{$lang("导出装箱信息")}}</el-button></th>'+
  '<th v-if="info.transfer_use_type_val ===\'非销售\'"></th>\n' +
  '</tr>'+
  '<tr>'+
  '        <th v-for="(v,k) in thOption"  v-if="v.title!==\'本次出库（残次品）\'||(v.title ===\'本次出库（残次品）\'&&info.transfer_use_type_val ===\'非销售\')">\n' +
  '            {{v.title}}<span v-if="(v.red&&option.edit)" style="color: red"> *</span>\n' +
  '        </th>\n' +
  '</tr>'+
  '        </thead>\n' +
  '        <tbody>\n' +
  '        <tr class="text-c" v-for="(v,k) in goods">\n' +
  '            <td>{{Number(k) + 1}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td style="white-space: pre-line">{{v.upc_id}}</td>\n' +
  '            <td style="text-overflow:ellipsis;overflow:hidden;max-width:200px"><el-tooltip class="item" effect="dark" :content="v.spu_name" placement="bottom">\n' +
  '      <div  style="text-overflow:ellipsis;overflow:hidden;max-width:200px">{{v.spu_name}}</div>\n' +
  '    </el-tooltip></td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td> {{v.weight}}</td>\n' +
  '            <td> {{v.estimated_total_weight}}</td>\n' +
  '            <td> {{v.number_boxes}}</td>\n' +
  '            <td> {{v.number_per_box}}</td>\n' +
  '            <td> {{v.case_number}}</td>\n' +
  '            <td> {{v.box_length_and_width_cm}}</td>\n' +
  '            <td> {{v.net_weight_kg}}</td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.transfer_authentic_products?v.transfer_authentic_products:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.transfer_defective_products?v.transfer_defective_products:0}}</div>\n' +
  '            </td>\n' +
  '            <td>{{Number(v.number_authentic_outbound?v.number_authentic_outbound:0) + Number(v.number_defective_outbound?v.number_defective_outbound:0)}}</td>\n' +
  '            <td> <el-input :disabled="goods2[k].this_out_authentic_products === 0?true:false" v-model="v.this_out_authentic_products"   placeholder=""></el-input></td>\n' +
  '            <td v-if="info.transfer_use_type_val ===\'非销售\'"> <el-input :disabled="goods2[k].this_out_defective_products === 0?true:false" v-model="v.this_out_defective_products"  placeholder=""></el-input></td>\n' +
  '            <td>' +
  '                 <div><span v-if="info.transfer_use_type_val===\'非销售\'">正品：</span>{{v.transfer_authentic_products - v.number_authentic_outbound - v.this_out_authentic_products}}</div>' +
  '                 <div v-if="info.transfer_use_type_val===\'非销售\'"><span >残次品：</span>{{v.transfer_defective_products - v.number_defective_outbound - v.this_out_defective_products}}</div>' +
  '             </td>\n' +
  '        </tr>\n' +
  '        <tr class="text-c">\n' +
  '            <td>{{$lang("合计")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>\n' +
  '            </td>\n' +
  '            <td> </td>\n' +
  '            <td> {{all(goods,"estimated_total_weight")}}</td>\n' +
  '            <td> {{all(goods,"number_boxes")}}</td>\n' +
  '            <td>{{all(goods,"number_per_box")}}</td>\n' +
  '            <td> </td>\n' +
  '            <td> </td>\n' +
  '            <td> {{all(goods,"net_weight_kg")}}</td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,"transfer_authentic_products")}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,"transfer_defective_products")}}</div>\n' +
  '            </td>\n' +
  '            <td>{{all(goods,"number_authentic_outbound") + all(goods,"number_defective_outbound")}}</td>\n' +
  '            <td> {{all(goods,"this_out_authentic_products")}}</td>\n' +
  '            <td v-if="info.transfer_use_type_val ===\'非销售\'">{{all(goods,"this_out_defective_products")}} </td>\n' +
  '            <td></td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})


/**
 * 入库信息
 */
Vue.component('GoodListIn', {
  props:['goods','option','info','goods2'],
  data: function () {
    return {
      curr:[],
      hv:'',
      thOption: [
        {title: this.$lang('序号')},
        {title: this.$lang('SKU条码')},
        {title: this.$lang('条形码')},
        {title: this.$lang('商品名称')},
        {title: this.$lang('商品属性')},
        {title: this.$lang('商品图片')},
        {title: this.$lang('调拨数量')},
        {title: this.$lang('已出库')},
        {title: this.$lang('已入库')},
        {title: this.$lang('剩余入库')},
        {title: this.$lang('本次入库（正品）')},
        {title: this.$lang('本次入库（残次品）')},
        {title: this.$lang('差异数')}
      ]
    }
  },
  computed:{
    // totalAllAuthenticProducts:function () {
    //     let total=0;
    //     this.goods.forEach((v,k)=>{
    //      total +=  (v.number_authentic_outbound - v.number_authentic_warehousing - v.this_in_authentic_products);
    //     })
    //     return total;
    // },
    // totalAllDefectiveProducts:function () {
    //     let total=0;
    //     this.goods.forEach((v,k)=>{
    //         total +=  (v.number_defective_outbound - v.number_defective_warehousing - v.this_in_defective_products);
    //     })
    //     return total;
    // },
  },
  created: function () {
    var _this = this;
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
  },
  methods: {
    orderDetal: function (orderId, title) {
      var dom = document.createElement('a');
      var _href;
      if (title === '入库单打印') {
        _href = '/index.php?m=allocation_extend_new&a=in_print&id='+orderId
      }
      dom.setAttribute("onclick", "opennewtab(this,'"+this.$lang(title) + "')");
      dom.setAttribute("_href", _href);
      dom.click();
    },
    openIn:function(){
      this.orderDetal(id,'入库单打印')
    },
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    },
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    }
  },
  template:'<table class="table table-bg">\n' +
  '        <thead>\n' +
  '<tr>'+
  '<th colspan="11" style="text-align: left;font-size: 15px;text-indent: 20px;padding: 0;">{{$lang("商品名称")}}</th>'+
  /*        '<th colspan="2"><el-button type="primary" size="mini" @click="openIn">{{$lang("打印入库单")}}</el-button></th>'+*/
  '</tr><tr>'+
  '        <th>{{$lang("序号")}}</th>\n' +
  '        <th>{{$lang("SKU条码")}}</th>\n' +
  '        <th>{{$lang("条形码")}}</th>\n' +
  '        <th>{{$lang("商品名称")}}</th>\n' +
  '        <th>{{$lang("商品属性")}}</th>\n' +
  '        <th>{{$lang("商品图片")}}</th>\n' +
  '        <th>{{$lang("调拨数量")}}</th>\n' +
  '        <th>{{$lang("出库数量")}}</th>\n' +
  '        <th>{{$lang("本次入库（正品）")}}</th>\n' +
  '        <th>{{$lang("本次入库（残次品）")}}</th>\n' +
  '        <th>{{$lang("差异数")}}</th></tr>\n' +
  '        </thead>\n' +
  '        <tbody>' +
  '<template  v-for="(v,k) in goods">' +
  '        <tr class="text-c">\n' +
  '            <td>{{Number(k)+1}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td>{{v.upc_id}}</td>\n' +
  '            <td style="text-overflow:ellipsis;overflow:hidden;max-width:200px"><el-tooltip class="item" effect="dark" :content="v.spu_name" placement="bottom">\n' +
  '      <div  style="text-overflow:ellipsis;overflow:hidden;max-width:200px">{{v.spu_name}}</div>\n' +
  '    </el-tooltip></td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '                <td><div>{{$lang("正品")}}:{{v.transfer_authentic_products?v.transfer_authentic_products:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.transfer_defective_products?v.transfer_defective_products:0}}</div></td>\n' +
  '            <td>{{Number(v.number_authentic_outbound_this?v.number_authentic_outbound_this:0) + Number(v.number_defective_outbound_this?v.number_defective_outbound_this:0)}}</td>\n' +
  '            <td> <el-input :disabled="goods2[k].this_in_authentic_products === 0?true:false" v-model="v.this_in_authentic_products"   placeholder=""></el-input></td>\n' +
  '            <td   > <el-input  v-model="v.this_in_defective_products"  placeholder=""></el-input></td>\n' +
  '            <td>' +
  '              <div><span >{{$lang("正品")}}：</span>{{v.number_authentic_outbound_this - v.number_authentic_warehousing - v.this_in_authentic_products}}</div>' +
  '              <div ><span >{{$lang("残次品：")}}</span>{{v.number_defective_outbound_this - v.number_defective_warehousing - v.this_in_defective_products}}</div>' +
  '            </td>\n' +
  '        </tr>\n' +
  '</template>'+
  '        <tr class="text-c">\n' +
  '            <td>{{$lang("合计")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'transfer_authentic_products\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'transfer_defective_products\')}}</div>\n' +
  '            </td>\n' +
  '               <td>{{all(goods,"number_authentic_outbound") + all(goods,"number_defective_outbound")}}</td>'+
  '            <td> {{all(goods,"this_in_authentic_products")}}</td>\n' +
  '            <td >{{all(goods,"this_in_defective_products")}} </td>\n' +
  '            <td></td>\n' +
  '          </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})



/**
 * 日志信息
 */
Vue.component('AllotLog', {
  props:['alloid'],
  data: function () {
    return {
      mes:[]
    }
  },
  created: function () {
    this.get();
  },
  methods: {
    get:function(){
      var _this = this;
      axios.post('index.php?m=AllocationExtendNew&a=getLog&allo_id='+this.$props.alloid, {
        allo_id:this.$props.alloid
      }).then(function (response) {
        if (response.data.code == 200) {
          _this.mes = response.data.data
        }
      })
    }
  },
  template:'<table class="table table-bg">\n' +
  '        <thead>\n' +
  '        <th style="width: 33%;">{{$lang("时间")}}</th>\n' +
  '        <th>{{$lang("提交人")}}</th>\n' +
  '        <th>{{$lang("详细信息")}}</th>\n' +
  '        </thead>\n' +
  '        <tbody>' +
  '<template v-for="(v,k) in mes">' +
  '        <tr class="text-c">\n' +
  '            <td>{{v.created_at}}</td>\n' +
  '            <td>{{v.created_by}}</td>\n' +
  '            <td>{{v.operation_detail}}</td>\n' +
  '        </tr>\n' +
  '</template>'+
  '        </tbody>\n' +
  '    </table>'
})

/**
 *库存归属变更信息
 */
Vue.component('InventoryInfo', {
  props:['attr'],
  data: function () {
    return {
      mes:[]
    }
  },
  created: function () {
  },
  methods: {
    get:function(){
    }
  },
  template:`<table class="table table-bg">
            <thead>
              <tr><th colspan="10" class="text-l table-detail-title">库存归属变更信息</th></tr>
              <tr>
                <th>{{$lang('序号')}}</th>
                <th>{{$lang('SKU条码')}}</th>
                <th>{{$lang('条形码')}}</th>
                <th>{{$lang('商品名称')}}</th>
                <th>{{$lang('商品图片')}}</th>
                <th>{{$lang('批次号')}}</th>
                <th>{{$lang('仓库')}}</th>
                <th>{{$lang('采购团队')}}</th>
                <th>{{$lang('销售小团队')}}</th>
                <th>{{$lang('数量')}}</th>
              </tr>
            </thead>
            <tbody>
              <tr class="text-c" v-for="(v,k) in attr.skus">
                <td>{{Number(k) + 1}}</td>
                <td>{{v.sku_id}}</td>
                <td>{{v.upc_id}}</td>
                <td>{{v.spu_name}}</td>
                <td><img height="70" :src="v.image_url"/></td>
                <td>{{v.batch_no}}</td>
                <td>{{v.warehouse_cd_val}}</td>
                <td>{{v.purchasing_team_val}}</td>
                <td></td>
                <td>{{v.transfer_number}}</td>
              </tr>
            </tbody>
          </table>`
})


function fixdata(data) { //文件流转BinaryString
  var o = "",
    l = 0,
    w = 10240;
  for(; l < data.byteLength / w; ++l) o += String.fromCharCode.apply(null, new Uint8Array(data.slice(l * w, l * w + w)));
  o += String.fromCharCode.apply(null, new Uint8Array(data.slice(l * w)));
  return o;
}

function saveAs(obj, fileName) {
  var tmpa = document.createElement("a");
  tmpa.download = fileName || "下载";
  tmpa.href = URL.createObjectURL(obj);
  tmpa.click();
  setTimeout(function() {
    URL.revokeObjectURL(obj);
  }, 100);
}

const wopts = {
  bookType: 'xlsx',
  bookSST: false,
  type: 'binary'
};

function downloadExl(data, type,name) {
  const wb = {
    SheetNames: ['Sheet1'],
    Sheets: {},
    Props: {}
  };
  wb.Sheets['Sheet1'] = XLSX.utils.json_to_sheet(data); //通过json_to_sheet转成单页(Sheet)数据
  saveAs(new Blob([s2ab(XLSX.write(wb, wopts))], {
    type: "application/octet-stream"
  }), name+ '.' + (wopts.bookType == "biff2" ? "xls" : wopts.bookType));
}

function s2ab(s) {
  if (typeof ArrayBuffer !== 'undefined') {
    var buf = new ArrayBuffer(s.length);
    var view = new Uint8Array(buf);
    for (var i = 0; i != s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
    return buf;
  } else {
    var buf = new Array(s.length);
    for (var i = 0; i != s.length; ++i) buf[i] = s.charCodeAt(i) & 0xFF;
    return buf;
  }

}


/**
 * 已完成，商品列表
 */
Vue.component('GoodListOver', {
  props:['goods','option'],
  data: function () {
    return {
      curr:[],
      hv:'',
      thOption: [
        {title: this.$lang('序号')},
        {title: this.$lang('SKU条码')},
        {title: this.$lang('条形码')},
        {title: this.$lang('商品名称')},
        {title: this.$lang('商品属性')},
        {title: this.$lang('商品图片')},
        {title: this.$lang('调拨数量')},
        {title: this.$lang('已出库')},
        {title: this.$lang('已入库')},
      ]
    }
  },
  computed:{

  },
  created: function () {
    var _this = this;
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
  },
  methods: {
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    },
    change:function(v){
      for(var x = 0;x<this.curr.length;x++){
        if(this.curr[x].CD === v.tax_free_sales_unit_price_currency_cd){
          v.tax_free_sales_unit_price_currency_cd_val = this.curr[x].CD_VAL
          console.log(this.curr[x].CD_VAL)
        }
      }
    },
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    }
  },
  template:'<table class="table table-bg">\n' +
  '        <thead>\n' +
  '<tr>'+
  '<th colspan="17" style="text-align: left;font-size: 15px;text-indent: 20px;padding: 0;">{{$lang("商品信息")}}</th>'+
  '</tr><tr>'+
  '        <th v-for="(v,k) in thOption">\n' +
  '            {{v.title}}<span v-if="(v.red&&option.edit)" style="color: red"> *</span>\n' +
  '        </th></tr>\n' +
  '        </thead>\n' +
  '        <tbody>\n' +
  '        <tr class="text-c" v-for="(v,k) in goods">\n' +
  '            <td>{{Number(k) + 1}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td style="white-space: pre-line">{{v.upc_id}}</td>\n' +
  '            <td style="text-overflow:ellipsis;overflow:hidden;max-width:200px"><el-tooltip class="item" effect="dark" :content="v.spu_name" placement="bottom">\n' +
  '      <div  style="text-overflow:ellipsis;overflow:hidden;max-width:200px">{{v.spu_name}}</div>\n' +
  '    </el-tooltip></td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.transfer_authentic_products?v.transfer_authentic_products:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.transfer_defective_products?v.transfer_defective_products:0}}</div>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.number_authentic_outbound?v.number_authentic_outbound:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.number_defective_outbound?v.number_defective_outbound:0}}</div>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.number_authentic_warehousing?v.number_authentic_warehousing:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.number_defective_warehousing?v.number_defective_warehousing:0}}</div>\n' +
  '            </td>\n' +
  '        </tr>\n' +
  '        <tr class="text-c">\n' +
  '            <td>{{$lang("合计")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'transfer_authentic_products\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'transfer_defective_products\')}}</div>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'number_authentic_outbound\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'number_defective_outbound\')}}</div>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'number_authentic_warehousing\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'number_defective_warehousing\')}}</div>\n' +
  '            </td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})



Vue.component('GoodListOverTwo', {
  props:['goods','option','info'],
  data: function () {
    return {
      curr:[],
      hv:'',
      thOption: [
        {title: this.$lang('序号')},
        {title: this.$lang('SKU条码')},
        {title: this.$lang('条形码')},
        {title: this.$lang('商品名称')},
        {title: this.$lang('商品属性')},
        {title: this.$lang('商品图片')},
        {title: this.$lang('调拨数量')},
        {title: this.$lang('重量（kg/单个）')},
        {title: this.$lang('预计总重（kg）')},
        {title: this.$lang('箱数（箱）')},
        {title: this.$lang('每箱个数')},
        {title: this.$lang('箱号')},
        {title: this.$lang('箱子长宽高')},
        {title: this.$lang('净重（kg）')},
        {title: this.$lang('已出库')},
        {title: this.$lang('已入库')},
        {title: this.$lang('平均不含税商品单价（CNY）')},
        {title: this.$lang('平均PO内费用单价（CNY）')},
        {title: this.$lang('PO外费用单价（CNY）')},
        {title: this.$lang('不含税销售单价')},
        {title: this.$lang('不含税销售单价（CNY）')}
      ]
    }
  },
  computed:{

  },
  created: function () {
    var _this = this;
    this.getCurr()
    axios.get('/index.php?g=common&m=index&a=exchange_rate').then(function (response) {
      if(response.data.code === 2000){
        _this.hv = response.data.data.exchange_rate
      }
    })
  },
  methods: {
    /**
     * 获取币种
     */
    getCurr:function(){
      var _this = this;
      axios.post('/index.php?g=common&m=index&a=get_cd', {

        cd_type:{
          currency:true
        }

      }).then(function (response) {
        if(response.data.code === 2000){
          _this.curr = response.data.data.currency
        }
      })
    },
    all:function(arr,type){
      var n = 0
      if(arr){
        for(var x = 0;x<arr.length;x++){
          n += Number(arr[x][type])
        }
      }
      return n
    },
    change:function(v){
      for(var x = 0;x<this.curr.length;x++){
        if(this.curr[x].CD === v.tax_free_sales_unit_price_currency_cd){
          v.tax_free_sales_unit_price_currency_cd_val = this.curr[x].CD_VAL
          console.log(this.curr[x].CD_VAL)
        }
      }
    },
    currCny:function(curr,price){
      if(this.hv && curr){
        var curr = curr.toLowerCase()
        if(curr === 'cny'){
          return Math.round(price*100)/100
        }
        if(this.hv[curr+'XchrAmtCny']){
          var p =  Number(price)*this.hv[curr+'XchrAmtCny'];
          return Math.round(p*100)/100
        }
      }
    }
  },
  template:'<table class="table table-bg">\n' +
  '        <thead>\n' +
  '<tr>'+
  '<th colspan="21" style="text-align: left;font-size: 15px;text-indent: 20px;padding: 0;">{{$lang("商品信息")}}</th>'+
  '</tr><tr>'+
  '        <th v-for="(v,k) in thOption"  v-if="(info.transfer_use_type_val === \'销售\' && (v.title === \'不含税销售单价\' || v.title === \'不含税销售单价（CNY）\')) || (v.title !== \'不含税销售单价\' && v.title !== \'不含税销售单价（CNY）\')">\n' +
  '            {{v.title}}<span v-if="(v.red&&option.edit)" style="color: red"> *</span>\n' +
  '        </th></tr>\n' +
  '        </thead>\n' +
  '        <tbody>\n' +
  '        <tr class="text-c" v-for="(v,k) in goods">\n' +
  '            <td>{{Number(k) + 1}}</td>\n' +
  '            <td>{{v.sku_id}}</td>\n' +
  '            <td style="white-space: pre-line">{{v.upc_id}}</td>\n' +
  '            <td style="text-overflow:ellipsis;overflow:hidden;max-width:200px"><el-tooltip class="item" effect="dark" :content="v.spu_name" placement="bottom">\n' +
  '      <div  style="text-overflow:ellipsis;overflow:hidden;max-width:200px">{{v.spu_name}}</div>\n' +
  '    </el-tooltip></td>\n' +
  '            <td>{{v.attributes}}</td>\n' +
  '            <td>\n' +
  '                <el-popover\n' +
  '                        v-if="v.image_url"\n' +
  '                        placement="right"\n' +
  '                        title=""\n' +
  '                        trigger="hover">\n' +
  '                    <img :src="v.image_url" style="height: 300px;"/>\n' +
  '                    <img slot="reference" :src="v.image_url"  style="height: 50px;">\n' +
  '                </el-popover>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.transfer_authentic_products?v.transfer_authentic_products:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.transfer_defective_products?v.transfer_defective_products:0}}</div>\n' +
  '            </td>\n' +
  '            <td>{{v.weight}}</td>'+
  '            <td>{{v.estimated_total_weight}}</td>'+
  '            <td>{{v.number_boxes}}</td>'+
  '            <td>{{v.number_per_box}}</td>'+
  '            <td>{{v.case_number}}</td>'+
  '            <td>{{v.box_length_and_width_cm}}</td>'+
  '            <td>{{v.net_weight_kg}}</td>'+
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.number_authentic_outbound?v.number_authentic_outbound:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.number_defective_outbound?v.number_defective_outbound:0}}</div>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{v.number_authentic_warehousing?v.number_authentic_warehousing:0}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{v.number_defective_warehousing?v.number_defective_warehousing:0}}</div>\n' +
  '            </td>\n' +
  '            <td>CNY {{v.average_price_goods_without_tax_cny}}</td>'+
  '            <td>CNY {{v.average_po_internal_cost_cny}}</td>'+
  '            <td>CNY {{v.po_outside_cost_unit_price_cny}}</td>'+
  '            <td v-if="info.transfer_use_type_val === \'销售\'">{{ v.tax_free_sales_unit_price_currency_cd_val+" "+ v.tax_free_sales_unit_price}}</td>'+
  '            <td v-if="info.transfer_use_type_val === \'销售\'">{{currCny(v.tax_free_sales_unit_price_currency_cd_val,v.tax_free_sales_unit_price)}}</td>'+
  '        </tr>\n' +
  '        <tr class="text-c">\n' +
  '            <td>{{$lang("合计")}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'transfer_authentic_products\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'transfer_defective_products\')}}</div>\n' +
  '            </td>\n' +
  '            <td></td>\n' +
  '            <td>{{all(goods,\'estimated_total_weight\')}}</td>\n' +
  '            <td>{{all(goods,\'number_boxes\')}}</td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td>{{all(goods,\'net_weight_kg\')}}</td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'number_authentic_outbound\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'number_defective_outbound\')}}</div>\n' +
  '            </td>\n' +
  '            <td>\n' +
  '                <div>{{$lang("正品")}}:{{all(goods,\'number_authentic_warehousing\')}}</div>\n' +
  '                <div>{{$lang("残次品")}}:{{all(goods,\'number_defective_warehousing\')}}</div>\n' +
  '            </td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td></td>\n' +
  '            <td  v-if="info.transfer_use_type_val === \'销售\'"></td>\n' +
  '            <td  v-if="info.transfer_use_type_val === \'销售\'"></td>\n' +
  '        </tr>\n' +
  '        </tbody>\n' +
  '    </table>'
})

function trim(str){
  str = String(str).replace(/^\s+|\s+$/g,"");
  return str
}