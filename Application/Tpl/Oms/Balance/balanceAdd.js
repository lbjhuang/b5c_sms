/**
 * @file 对应"新增收支明细页面"：OMS模块-收支明细-选择特定平台后-点击新增 
 */
'use strict';
if (getCookie('think_language') !== "zh-cn") {
  ELEMENT.locale(ELEMENT.lang.en)
}

var VM = new Vue({
  el: '#balance-add',
  data: {
    platCd: '',
    siteCd: '',
    storeId: '',
    tableId: null, // 导入表格成功后，数据库对应的id
    indicator: {
      order: {
        order_no: {
          show: true,
          label: '买家购物订单号',
          disabled: true
        }, // 订单号
        order_created_date: {
          show: true,
          label: '订单创建日',
          disabled: false
        }, // 订单创建日期
        paid_on_date: {
          show: true,
          label: '订单支付日',
          disabled: false
        }, // 付款日期
        close_month: {
          show: true,
          label: '结算月',
          disabled: true
        }, // 结算月
        deposit_date: {
          show: true,
          label: '入账月',
          disabled: false
        }, // 入账月
        amount: {
          show: true,
          label: '净入账款',
          disabled: true
        }, // 净入账款
        currency_cd: {
          show: true,
          label: '币种',
          disabled: true
        }, // 币种
        payment_method: {
          show: true,
          label: '支付渠道',
          disabled: false
        }, // 支付渠道
        goods_name: {
          show: true,
          label: '商品名',
          disabled: false
        }, // 商品名
        sku_id: {
          show: true,
          label: 'SKU',
          disabled: false
        }, // sku
        plat_goods_id: {
          show: true,
          label: '平台商品号',
          disabled: false
        }, // 平台商品号
        our_goods_id: {
          show: true,
          label: '我方商品号',
          disabled: false
        }, // 我方商品号
      },
      sell: {
        goods_number: {
          show: true,
          label: '购货量',
          disabled: false
        }, // 商品数量
        sale_amount: {
          show: true,
          label: '原含税货价',
          disabled: false
        }, // 含税货价
        our_discount_amount: {
          show: true,
          label: '原含税货价中：我方以折扣让利的部分',
          disabled: false
        }, // 我方折扣让利金额
        our_coupon_amount: {
          show: true,
          label: '原含税货价中：我方以优惠券让利的部分',
          disabled: false
        }, // 我方优惠券让利金额
        our_integral_amount: {
          show: true,
          label: '原含税货价中：我方以积分让利的部分',
          disabled: false
        }, // 我方积分让利金额
        our_bind_sale_amount: {
          show: true,
          label: '原含税货价中：我方以捆绑销售让利的部分',
          disabled: false
        }, // 我方捆绑销售承担金额
        shared_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分',
          disabled: false
        }, // 买家、 平台、 信用卡商共同承担的金额
        plat_discount_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以折扣承担的部分',
          disabled: false
        }, // 平台折扣承担金额
        plat_coupon_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以优惠券承担的部分',
          disabled: false
        }, // 平台优惠券承担金额
        plat_integral_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以积分承担的部分',
          disabled: false
        }, // 平台积分承担金额
        plat_bind_sale_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以捆绑销售承担的部分',
          disabled: false
        }, // 平台捆绑销售承担金额
        credit_card_dealer_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：信用卡商承担的部分',
          disabled: false
        }, // 信用卡商承担的金额
        buyer_amount: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分',
          disabled: false
        }, // 买家承担的金额
        buyer_amount_tax: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中：买家承担的原交易税',
          disabled: false
        }, // 买家承担的交易税
        our_cost_of_buyer_amount_tax: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：我方主动承担的部分',
          disabled: false
        }, // 我方主承担的买家交易税
        palat_collection_buyer_amount_tax: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收',
          disabled: false
        }, // 买家承担的买家交易税商家代收
        palat_payment_buyer_amount_tax: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收后代付',
          disabled: false
        }, // 买家承担的买家交易税商家代收后代付
        plat_collection_drawback: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收后退税',
          disabled: false
        }, // 商家代收代付后退税
        plat_collection_net_tax: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收代付后的退税后的净税额',
          disabled: false
        }, // 商家代收代付后的退税后的净税额
        sale_amount_excluding_tax: {
          show: true,
          label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的部分中：买家承担的不含税货价',
          disabled: false
        }, // 买家承担的不含税货价
        commission: {
          show: true,
          label: '佣金及销货服务费',
          disabled: true
        }, // 佣金及服务费
        our_collection_of_cost: {
          show: true,
          label: '买家承担精美包装款/保险费我方代收',
          disabled: false
        }, // 买家承担精美包装款 / 保险费我方代收
        our_payment_of_cost: {
          show: true,
          label: '买家承担精美包装款/保险费我方代收后代付',
          disabled: false
        }, // 买家承担精美包装款 / 保险费我方代付
      },
      ship: {
        shipped_date: {
          show: true,
          label: '发货日',
          disabled: false
        }, // 发货日
        confirmed_date: {
          show: true,
          label: '收货日',
          disabled: false
        }, // 收货日
        buyer_freight: {
          show: true,
          label: '不含税销货运费中：买家承担的部分',
          disabled: false
        }, // 买家承担运费
        our_payment_plat_freight: {
          show: true,
          label: '不含税销货运费中：平台承担的部分我方垫付',
          disabled: false
        }, // 我方垫付平台承担运费
        our_collection_plat_freight: {
          show: true,
          label: '不含税销货运费中：平台承担的部分我方垫付后收回',
          disabled: false
        }, // 我方收回平台承担运费
        our_collection_buyer_freight_tax: {
          show: true,
          label: '销货运费的税中：买家承担的部分我方代收',
          disabled: false
        }, // 买家承担运费的税中我方代收
        our_payment_buyer_freight_tax: {
          show: true,
          label: '销货运费的税中：买家承担的部分我方代收后代付',
          disabled: false
        }, // 买家承担运费的税中我方代收后代付
        our_collection_buyer_cost_charge: {
          show: true,
          label: '不含税货到付款服务费中：买家承担的部分我方代收',
          disabled: false
        }, // 买家承担服务费我方代收
        our_collection_buyer_service_cost_tax: {
          show: true,
          label: '货到付款服务费的税中：买家承担的部分我方代收',
          disabled: false
        }, // 买家承担服务费税费我方代收
        our_payment_buyer_service_cost_and_tax: {
          show: true,
          label: '货到付款服务费价税合计中：买家承担的部分我方代收后代付',
          disabled: false
        }, // 买家承担服务费及税费我方代收后代付
        plat_service_cost: {
          show: true,
          label: '平台配送服务费',
          disabled: false
        }, // 平台配送服务费
        plat_pack_cost: {
          show: true,
          label: '平台打包费',
          disabled: false
        }, // 平台打包费用
        plat_weight_cost: {
          show: true,
          label: '平台称重费',
          disabled: false
        }, // 平台称重费
        plat_warheouse_cost: {
          show: true,
          label: '平台入仓费',
          disabled: false
        }, // 平台入仓费
        plat_stock_transfer_cost: {
          show: true,
          label: '平台存货转移费',
          disabled: false
        }, // 平台存货转移费
        plat_inventory_destruction_cost: {
          show: true,
          label: '平台存货销毁费',
          disabled: false
        }, // 平台存货销毁费
        distribution_cost: {
          show: true,
          label: '第三方配送服务费',
          disabled: false
        }, // 第三方配送服务费
      },
      return: {
        return_date: {
          show: true,
          label: '退货日',
          disabled: false
        }, // 退货日
        refund_date: {
          show: true,
          label: '退货退款日',
          disabled: false
        }, // 退货退款日
        return_rate: {
          show: true,
          label: '退货率',
          disabled: false
        }, // 退货率
        return_number: {
          show: true,
          label: '退货量',
          disabled: false
        }, // 退货数量
        refund: {
          show: true,
          label: '原含税货价退货退款',
          disabled: false
        }, // 退货退款
        our_discount_amount_return: {
          show: true,
          label: '原含税货价中：我方以折扣让利的部分退货收回',
          disabled: false
        }, // 我方折扣让利金额收回
        our_coupon_amount_return: {
          show: true,
          label: '原含税货价中：我方以优惠券让利的部分退货收回',
          disabled: false
        }, // 我方优惠券让利金额收回
        our_integral_amount_return: {
          show: true,
          label: '原含税货价中：我方以积分让利的部分退货收回',
          disabled: false
        }, // 我方积分让利金额收回
        our_bind_sale_amount_return: {
          show: true,
          label: '原含税货价中：我方以捆绑销售让利的部分退货收回',
          disabled: false
        }, // 我方捆绑销售承担金额收回
        plat_discount_amount_return: {
          show: true,
          label: '原含税货价中的平台、信用卡商、买家共同承担的部分中：平台以折扣承担的部分退货退款',
          disabled: false
        }, // 平台以折扣承担的部分退回
        our_cost_of_buyer_amount_tax_return: {
          show: true,
          label: '原含税货价中的平台、信用卡商、买家共同承担的部分中的买家承担的部分中的买家承担的原交易税中：我方主动承担的部分退货收回',
          disabled: false
        }, // 我方主承担的买家交易税
        buyer_amount_return: {
          show: true,
          label: '原含税货价中的平台、信用卡商、买家共同承担的部分中：买家承担的部分退货退款',
          disabled: false
        }, // 买家承担的金额退回
        commission_return: {
          show: true,
          label: '佣金退货收回',
          disabled: false
        }, // 佣金退货收回
        service_cost_return: {
          show: true,
          label: '销货服务费退货收回',
          disabled: false
        }, // 销货服务费退货收回
        retrun_service_cost: {
          show: true,
          label: '退货手续费',
          disabled: false
        }, // 退货手续费
        return_service_amount: {
          show: true,
          label: '退货服务价',
          disabled: false
        }, // 退货服务价
        buyer_freight_return: {
          show: true,
          label: '不含税运费中：买家承担的部分货退款',
          disabled: false
        }, // 买家承担运费退回
        amount_return: {
          show: true,
          label: '入账金额退货退款',
          disabled: false
        }, // 入账金额退货退款
        our_payment_plat_freight: {
          show: true,
          label: '退货运费中：平台承担的部分我方垫付',
          disabled: false
        }, // 我方垫付平台承担运费
        our_collection_plat_freight: {
          show: true,
          label: '退货运费中：平台承担的部分我方垫付后收回',
          disabled: false
        }, // 我方收回平台承担运费
        our_collection_buyer_service_cost_and_tax: {
          show: true,
          label: '货到付款服务费价税合计中：买家承担的部分退货我方代收',
          disabled: false
        }, // 买家承担服务费及税费我方代收
        our_payment_buyer_cost_charge: {
          show: true,
          label: '不含税货到付款服务费中：买家承担的部分退货我方代收后代付',
          disabled: false
        }, // 买家承担服务费我方代收后代付
        our_payment_buyer_service_cost_tax: {
          show: true,
          label: '货到付款服务费的税中：买家承担的部分退货我方代收后代付',
          disabled: false
        }, // 买家承担服务费税费我方代收后代付
        our_collection_of_cost: {
          show: true,
          label: '买家承担精美包装款退货我方代收',
          disabled: false
        }, // 买家承担精美包装退货方代收
      },
      other: {
        plat_indemnity: {
          show: true,
          label: '收平台赔款',
          disabled: false
        }, // 平台赔款
        buyer_indemnity: {
          show: true,
          label: '收买家赔款',
          disabled: false
        }, // 买家赔款
        promotion_cost: {
          show: true,
          label: '付推广费',
          disabled: false
        }, // 推广费
      }
    },
    wordWidth: {
      six: 180,
      seven: 210,
      eight: 240,
      nine: 270,
      ten: 300,
      eleven: 330,
      twelve: 360,
      thirteen: 390,
      fourteen: 420,
      fifteen: 450,
      sixteen: 480
    },
    rangeMonth: [],
    total_amount_min: '', // 净入账款小计（ 最小）
    total_amount_max: '', // 净入账款小计（ 最大）
    total_cost_min: '', // 佣金及销货服务费（ 最小）
    total_cost_max: '', // 佣金及销货服务费（ 最大）
    introduction: '', // 说明
    words: 0,
    explainOptions: [{
      'value': '店铺无订单',
    }, {
      'value': '分销商控制店铺，我方无权导出',
    }, {
      'value': '店铺不支持导出原始表',
    }, {
      'value': '导出的原始表乱码，无法导入生成标准表',
    }],
    updated_by: '', // 修改人
    updated_at: '', // 最近修改时间
    tableData: {
      detail: [],
      page: {
        total_rows: 0
      }
    },
    tableLoading: false,
    page: 1,
    pageSize: 10,
    datePickerDisabled: false,
  },
  watch: {
    introduction: function (newVal, oldVal) {
      if (this.introduction.length > 50) {
        this.$message.error(this.$lang('说明不得超过50个字符'));
        this.introduction = this.introduction.slice(0, 50);
      }
      this.words = this.introduction.length;
    }
  },
  created: function () {
    this.platCd = this.getQueryString('platcd');
    this.siteCd = this.getQueryString('sitecd');
    this.storeId = this.getQueryString('storeid');
    
    var itemId = this.getQueryString('itemid');
    if (itemId) {
      this.tableId = itemId;
      this.getTableData();
    }
  },
  methods: {
    /**
     * 获取URL中查询字符串中相应参数的值
     *
     * @param {string} name 参数名
     * @return {(string | null)} 参数的值
     */
    getQueryString: function(name) {
      var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
      var r = window.location.search.substr(1).match(reg);
      if (r != null) return decodeURIComponent(r[2]);
      return null;
    },

    /**
     * axios.post的封装，提交 form表单数据
     */
    queryPost: function (url, param) {
      var headers = {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }
      return axios.post(url, Qs.stringify(param), headers);
    },

    /**
     * 根据输入，过滤“说明”选项
     * 详见https://element.eleme.cn/#/zh-CN/component/input ,带输入建议的输入框
     */
    querySearch: function (queryString, cb) {
      var explainOptions = this.explainOptions;
      var results = queryString ? explainOptions.filter(this.createFilter(queryString)) : explainOptions;
      // 调用 callback 返回建议列表的数据
      cb(results);
    },
    createFilter: function (queryString) {
      return (explain) => {
        return (explain.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0);
      };
    },

    // 查看导入模板（下载导入模板的标准表）
    seeTemplate: function () {
      window.location.href = `index.php?g=home&m=finance&a=downloadTemplate&plat_cd=${this.platCd}&site_cd=${this.siteCd}&store_id=${this.storeId}`
    },

    // 导入表格
    importTable: function (params) {
      this.tableLoading = true;
      const form = new FormData();
      form.append('file', params.file);
      form.append('plat_cd', this.platCd);
      form.append('site_cd', this.siteCd);
      form.append('store_id', this.storeId);
      
      axios.post('/index.php?g=home&m=finance&a=transform', form).then((res) => {
        if (res.data.code == 2000) {
          this.tableId = res.data.data;
          this.getTableData();
          this.$message.success(this.$lang('导入成功'));
        } else {
          this.$message.error(this.$lang(res.data.msg));
          this.tableLoading = false;
        }
      }).catch(function (err) {
        console.log(err);
      });
    },

    // 获取表格数据
    getTableData: function () {
      this.tableLoading = true;
      var param = {
        id: this.tableId,
        p: this.page,
        rows: this.pageSize
      };
      this.queryPost("/index.php?&m=finance&a=settlement_detail", param).then((res) => {
        this.tableLoading = false;
        var data = res.data;
        if (data.code === 2000) {
          data.data.page.total_rows = parseInt(data.data.page.total_rows);
          this.tableData = data.data;

          this.rangeMonth = [this.tableData.detail[0].settlement.start_date, this.tableData.detail[0].settlement.end_date];
          this.datePickerDisabled = this.tableData.detail[0].settlement.excel_has_date === '1' ? true : false;
          this.updated_by = this.tableData.detail[0].settlement.updated_by;
          this.updated_at = this.tableData.detail[0].settlement.updated_at;
        } else {
          this.$message.error(this.$lang(data.msg));
          this.tableLoading = false;
        }
      }).catch(function (err) {
        console.log(err);
      });
    },

    // 保存
    save: function () {
      // console.log(JSON.stringify(this.introduction));
      if (this.tableId) {
        if (this.rangeMonth.length === 0) {
          this.$message.error(this.$lang('请选择结算月'));
          return;
        }
      } else {
        if (this.rangeMonth.length === 0) {
          this.$message.error(this.$lang('请选择结算月'));
          return;
        }
        if (this.introduction.length === 0) {
          this.$message.error(this.$lang('说明不能为空'));
          return;
        }
        this.$message.error(this.$lang('保存失败，请先导入原始表'));
        return;
      }
      
      var param = {
        id: this.tableId,
        start_date: this.rangeMonth[0] || null,
        end_date: this.rangeMonth[1] || null,
        introduction: this.introduction
      };
      this.queryPost("/index.php?&m=finance&a=update_settlement", param).then((res) => {
        var data = res.data;
        if (data.code === 2000) {
          var url = window.location.pathname + window.location.search;
          sessionStorage.setItem('closeWindow', url);
        } else {
          this.$message.error(this.$lang(data.msg));
        }
      }).catch( (err) => {
        console.log(err);
      });
      
    },
    handleSelectionChange: function (val) {
      this.listSelected = val;
    },
    pageSizeChange: function (val) {
      this.pageSize = val;
      this.getTableData();
    },
    currentPageChange: function (val) {
      this.page = val;
      this.getTableData();
    },
    
  },
});
