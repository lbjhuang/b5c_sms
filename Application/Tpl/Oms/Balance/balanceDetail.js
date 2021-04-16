/**
 * @file 对应"收支明细页面"：OMS模块-收支明细 
 */
'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var VM = new Vue({
    el: '#balance-detail',
    data: {
        platData: [],
        platSelected: {
            CD: '',
            CD_VAL: ''
        },
        siteData: [],
        siteSelected: {
            CD: '',
            CD_VAL: ''
        },
        storeData: [],
        storeSelected: {
            CD: '',
            CD_VAL: ''
        },
        indicator: {
            order: {
                order_no: {
                    show: false,
                    label: '买家购物订单号',
                    disabled: true
                }, // 订单号
                order_created_date: {
                    show: false,
                    label: '订单创建日',
                    disabled: false
                }, // 订单创建日期
                paid_on_date: {
                    show: false,
                    label: '订单支付日',
                    disabled: false
                }, // 付款日期
                close_month: {
                    show: false,
                    label: '结算月',
                    disabled: true
                }, // 结算月
                deposit_date: {
                    show: false,
                    label: '入账月',
                    disabled: false
                }, // 入账月
                amount: {
                    show: false,
                    label: '净入账款',
                    disabled: true
                }, // 净入账款
                currency_cd: {
                    show: false,
                    label: '币种',
                    disabled: true
                }, // 币种
                payment_method: {
                    show: false,
                    label: '支付渠道',
                    disabled: false
                }, // 支付渠道
                goods_name: {
                    show: false,
                    label: '商品名',
                    disabled: false
                }, // 商品名
                sku_id: {
                    show: false,
                    label: 'SKU',
                    disabled: false
                }, // sku
                plat_goods_id: {
                    show: false,
                    label: '平台商品号',
                    disabled: false
                }, // 平台商品号
                our_goods_id: {
                    show: false,
                    label: '我方商品号',
                    disabled: false
                }, // 我方商品号
            },
            sell: {
                goods_number: {
                    show: false,
                    label: '购货量',
                    disabled: false
                }, // 商品数量
                sale_amount: {
                    show: false,
                    label: '原含税货价',
                    disabled: false
                }, // 含税货价
                our_discount_amount: {
                    show: false,
                    label: '原含税货价中：我方以折扣让利的部分',
                    disabled: false
                }, // 我方折扣让利金额
                our_coupon_amount: {
                    show: false,
                    label: '原含税货价中：我方以优惠券让利的部分',
                    disabled: false
                }, // 我方优惠券让利金额
                our_integral_amount: {
                    show: false,
                    label: '原含税货价中：我方以积分让利的部分',
                    disabled: false
                }, // 我方积分让利金额
                our_bind_sale_amount: {
                    show: false,
                    label: '原含税货价中：我方以捆绑销售让利的部分',
                    disabled: false
                }, // 我方捆绑销售承担金额
                shared_amount: {
                    show: true,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分',
                    disabled: false
                }, // 买家、 平台、 信用卡商共同承担的金额
                plat_discount_amount: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以折扣承担的部分',
                    disabled: false
                }, // 平台折扣承担金额
                plat_coupon_amount: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以优惠券承担的部分',
                    disabled: false
                }, // 平台优惠券承担金额
                plat_integral_amount: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以积分承担的部分',
                    disabled: false
                }, // 平台积分承担金额
                plat_bind_sale_amount: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：平台以捆绑销售承担的部分',
                    disabled: false
                }, // 平台捆绑销售承担金额
                credit_card_dealer_amount: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：信用卡商承担的部分',
                    disabled: false
                }, // 信用卡商承担的金额
                buyer_amount: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分',
                    disabled: false
                }, // 买家承担的金额
                buyer_amount_tax: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中：买家承担的原交易税',
                    disabled: false
                }, // 买家承担的交易税
                our_cost_of_buyer_amount_tax: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：我方主动承担的部分',
                    disabled: false
                }, // 我方主承担的买家交易税
                palat_collection_buyer_amount_tax: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收',
                    disabled: false
                }, // 买家承担的买家交易税商家代收
                palat_payment_buyer_amount_tax: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收后代付',
                    disabled: false
                }, // 买家承担的买家交易税商家代收后代付
                plat_collection_drawback: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收后退税',
                    disabled: false
                }, // 商家代收代付后退税
                plat_collection_net_tax: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的原交易税中：买家承担的剩余部分我方代收代付后的退税后的净税额',
                    disabled: false
                }, // 商家代收代付后的退税后的净税额
                sale_amount_excluding_tax: {
                    show: false,
                    label: '原含税货价中：平台、信用卡商、买家共同承担的部分中：买家承担的部分中的买家承担的部分中：买家承担的不含税货价',
                    disabled: false
                }, // 买家承担的不含税货价
                commission: {
                    show: false,
                    label: '佣金及销货服务费',
                    disabled: true
                }, // 佣金及服务费
                our_collection_of_cost: {
                    show: false,
                    label: '买家承担精美包装款/保险费我方代收',
                    disabled: false
                }, // 买家承担精美包装款 / 保险费我方代收
                our_payment_of_cost: {
                    show: false,
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
                    show: false,
                    label: '收货日',
                    disabled: false
                }, // 收货日
                buyer_freight: {
                    show: false,
                    label: '不含税销货运费中：买家承担的部分',
                    disabled: false
                }, // 买家承担运费
                our_payment_plat_freight: {
                    show: false,
                    label: '不含税销货运费中：平台承担的部分我方垫付',
                    disabled: false
                }, // 我方垫付平台承担运费
                our_collection_plat_freight: {
                    show: false,
                    label: '不含税销货运费中：平台承担的部分我方垫付后收回',
                    disabled: false
                }, // 我方收回平台承担运费
                our_collection_buyer_freight_tax: {
                    show: false,
                    label: '销货运费的税中：买家承担的部分我方代收',
                    disabled: false
                }, // 买家承担运费的税中我方代收
                our_payment_buyer_freight_tax: {
                    show: false,
                    label: '销货运费的税中：买家承担的部分我方代收后代付',
                    disabled: false
                }, // 买家承担运费的税中我方代收后代付
                our_collection_buyer_cost_charge: {
                    show: false,
                    label: '不含税货到付款服务费中：买家承担的部分我方代收',
                    disabled: false
                }, // 买家承担服务费我方代收
                our_collection_buyer_service_cost_tax: {
                    show: false,
                    label: '货到付款服务费的税中：买家承担的部分我方代收',
                    disabled: false
                }, // 买家承担服务费税费我方代收
                our_payment_buyer_service_cost_and_tax: {
                    show: false,
                    label: '货到付款服务费价税合计中：买家承担的部分我方代收后代付',
                    disabled: false
                }, // 买家承担服务费及税费我方代收后代付
                plat_service_cost: {
                    show: true,
                    label: '平台配送服务费',
                    disabled: false
                }, // 平台配送服务费
                plat_pack_cost: {
                    show: false,
                    label: '平台打包费',
                    disabled: false
                }, // 平台打包费用
                plat_weight_cost: {
                    show: false,
                    label: '平台称重费',
                    disabled: false
                }, // 平台称重费
                plat_warheouse_cost: {
                    show: false,
                    label: '平台入仓费',
                    disabled: false
                }, // 平台入仓费
                plat_stock_transfer_cost: {
                    show: false,
                    label: '平台存货转移费',
                    disabled: false
                }, // 平台存货转移费
                plat_inventory_destruction_cost: {
                    show: false,
                    label: '平台存货销毁费',
                    disabled: false
                }, // 平台存货销毁费
                distribution_cost: {
                    show: false,
                    label: '第三方配送服务费',
                    disabled: false
                }, // 第三方配送服务费
            },
            return: {
                return_date: {
                    show: false,
                    label: '退货日',
                    disabled: false
                }, // 退货日
                refund_date: {
                    show: false,
                    label: '退货退款日',
                    disabled: false
                }, // 退货退款日
                return_rate: {
                    show: false,
                    label: '退货率',
                    disabled: false
                }, // 退货率
                return_number: {
                    show: false,
                    label: '退货量',
                    disabled: false
                }, // 退货数量
                refund: {
                    show: false,
                    label: '原含税货价退货退款',
                    disabled: false
                }, // 退货退款
                our_discount_amount_return: {
                    show: false,
                    label: '原含税货价中：我方以折扣让利的部分退货收回',
                    disabled: false
                }, // 我方折扣让利金额收回
                our_coupon_amount_return: {
                    show: false,
                    label: '原含税货价中：我方以优惠券让利的部分退货收回',
                    disabled: false
                }, // 我方优惠券让利金额收回
                our_integral_amount_return: {
                    show: false,
                    label: '原含税货价中：我方以积分让利的部分退货收回',
                    disabled: false
                }, // 我方积分让利金额收回
                our_bind_sale_amount_return: {
                    show: false,
                    label: '原含税货价中：我方以捆绑销售让利的部分退货收回',
                    disabled: false
                }, // 我方捆绑销售承担金额收回
                plat_discount_amount_return: {
                    show: false,
                    label: '原含税货价中的平台、信用卡商、买家共同承担的部分中：平台以折扣承担的部分退货退款',
                    disabled: false
                }, // 平台以折扣承担的部分退回
                our_cost_of_buyer_amount_tax_return: {
                    show: false,
                    label: '原含税货价中的平台、信用卡商、买家共同承担的部分中的买家承担的部分中的买家承担的原交易税中：我方主动承担的部分退货收回',
                    disabled: false
                }, // 我方主承担的买家交易税
                buyer_amount_return: {
                    show: false,
                    label: '原含税货价中的平台、信用卡商、买家共同承担的部分中：买家承担的部分退货退款',
                    disabled: false
                }, // 买家承担的金额退回
                commission_return: {
                    show: false,
                    label: '佣金退货收回',
                    disabled: false
                }, // 佣金退货收回
                service_cost_return: {
                    show: false,
                    label: '销货服务费退货收回',
                    disabled: false
                }, // 销货服务费退货收回
                retrun_service_cost: {
                    show: false,
                    label: '退货手续费',
                    disabled: false
                }, // 退货手续费
                return_service_amount: {
                    show: false,
                    label: '退货服务价',
                    disabled: false
                }, // 退货服务价
                buyer_freight_return: {
                    show: false,
                    label: '不含税运费中：买家承担的部分货退款',
                    disabled: false
                }, // 买家承担运费退回
                amount_return: {
                    show: true,
                    label: '入账金额退货退款',
                    disabled: false
                }, // 入账金额退货退款
                our_payment_plat_freight: {
                    show: false,
                    label: '退货运费中：平台承担的部分我方垫付',
                    disabled: false
                }, // 我方垫付平台承担运费
                our_collection_plat_freight: {
                    show: false,
                    label: '退货运费中：平台承担的部分我方垫付后收回',
                    disabled: false
                }, // 我方收回平台承担运费
                our_collection_buyer_service_cost_and_tax: {
                    show: false,
                    label: '货到付款服务费价税合计中：买家承担的部分退货我方代收',
                    disabled: false
                }, // 买家承担服务费及税费我方代收
                our_payment_buyer_cost_charge: {
                    show: false,
                    label: '不含税货到付款服务费中：买家承担的部分退货我方代收后代付',
                    disabled: false
                }, // 买家承担服务费我方代收后代付
                our_payment_buyer_service_cost_tax: {
                    show: false,
                    label: '货到付款服务费的税中：买家承担的部分退货我方代收后代付',
                    disabled: false
                }, // 买家承担服务费税费我方代收后代付
                our_collection_of_cost: {
                    show: false,
                    label: '买家承担精美包装款退货我方代收',
                    disabled: false
                }, // 买家承担精美包装退货方代收
            },
            other: {
                plat_indemnity: {
                    show: false,
                    label: '收平台赔款',
                    disabled: false
                }, // 平台赔款
                buyer_indemnity: {
                    show: false,
                    label: '收买家赔款',
                    disabled: false
                }, // 买家赔款
                promotion_cost: {
                    show: false,
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
        indicatorSelected: [
            '原含税货价中：平台、信用卡商、买家共同承担的部分',
            '发货日',
            '平台配送服务费',
            '入账金额退货退款'
        ],
        indicatorSelectedCopy: [
            '原含税货价中：平台、信用卡商、买家共同承担的部分',
            '发货日',
            '平台配送服务费',
            '入账金额退货退款'
        ],
        rangeMonth: [],
        total_amount_min: '', // 净入账款小计（ 最小）
        total_amount_max: '', // 净入账款小计（ 最大）
        total_cost_min: '', // 佣金及销货服务费（ 最小）
        total_cost_max: '', // 佣金及销货服务费（ 最大）
        introduction: '', // 说明
        updated_by: '', // 修改人
        createInterface: 1,
        userData: [],
        userLoading: false,
        exportOption: null, // 导出下拉框选中的值
        exportOptions: [{ // 导出下拉框的选项
            value: 1,
            label: '导出原始表'
        }, {
            value: 2,
            label: '导出标准表',
            disabled: true
        }],
        tableData: {
            total_rows: 0
        },
        tableLoading: true,
        listSelected: [],
        page: 1,
        pageSize: 10,
        sort_key: 'start_date', // 排序字段 start_date结算月， total_amount进入账款小计， total_cost佣金及服务费小计， updated_at最近修改日
        sort_type: '1', // 排序类型0 升序， 1 降序
        editItemId: '',
    },
    watch: {
        storeSelected: function(newStore, oldStore) {
            // 将所有筛选条件，页码等置为初始值
            var date = new Date;
            var year = date.getFullYear();
            var month = date.getMonth() + 1;
            month = (month < 10 ? "0" + month : month);
            var formatDate = (year.toString() + '-' + month.toString());

            this.indicatorSelected = [
                    '原含税货价中：平台、信用卡商、买家共同承担的部分',
                    '发货日',
                    '平台配送服务费',
                    '入账金额退货退款'
                ],
                this.indicatorSelectedCopy = [
                    '原含税货价中：平台、信用卡商、买家共同承担的部分',
                    '发货日',
                    '平台配送服务费',
                    '入账金额退货退款'
                ],
                this.rangeMonth = [formatDate, formatDate],
                this.total_amount_min = '', // 净入账款小计（ 最小）
                this.total_amount_max = '', // 净入账款小计（ 最大）
                this.total_cost_min = '', // 佣金及销货服务费（ 最小）
                this.total_cost_max = '', // 佣金及销货服务费（ 最大）
                this.introduction = '', // 说明
                this.updated_by = '', // 修改人
                this.userData = [],
                this.listSelected = [],
                this.page = 1,
                this.pageSize = 10

            if (this.storeSelected.CD) {
                this.getTableData();
            } else {
                if (!this.platSelected.CD) {
                    this.getTableData();
                }
            }

        }
    },
    created: function() {
        var date = new Date;
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        month = (month < 10 ? "0" + month : month);
        var formatDate = (year.toString() + '-' + month.toString());
        this.rangeMonth = [formatDate, formatDate];

        this.getPlatData();
        this.getTableData();
    },
    methods: {
        /**
         * 获取URL中查询字符串中相应参数的值
         *
         * @param {string} name 参数名
         * @return {(string | null)} 参数的值
         */
        queryPost: function(url, param) {
            var headers = {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
            return axios.post(url, Qs.stringify(param), headers);
        },

        // 获取表格数据
        getPlatData: function() {
            var _this = this;
            var param = {
                cd_type: {
                    new_plat: true
                }
            };
            this.queryPost("/index.php?g=common&m=index&a=get_cd", param).then((res) => {
                var data = res.data;
                if (data.code === 2000) {
                    _this.platData = data.data.new_plat;
                } else {
                    _this.$message.error(_this.$lang(data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },

        // 选择平台
        selectPlat: function(item) {
            // console.log(JSON.stringify(item));
            if (!item) {
                if (this.platSelected.CD_VAL) {
                    this.platSelected = {
                        CD: '',
                        CD_VAL: ''
                    };
                    this.siteSelected = {
                        CD: '',
                        CD_VAL: ''
                    };
                    this.storeSelected = {
                        CD: '',
                        CD_VAL: ''
                    };
                }
            } else {
                this.platSelected = item;
                this.siteSelected = { CD: '', CD_VAL: '' };
                this.storeSelected = { CD: '', CD_VAL: '' };
                this.getSiteData();
            }
        },

        // 获取平台下站点信息
        getSiteData: function() {
            var _this = this;
            var param = {
                plat_cd: [_this.platSelected.CD]
            };
            axios.post('/index.php?g=oms&m=order&a=getSite', param).then(function(res) {
                var data = res.data;
                if (data.code === 2000) {
                    _this.siteData = data.data;
                    _this.siteSelected = data.data[0];
                    _this.getStoreData();
                } else {
                    _this.$message.error(_this.$lang(data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },

        // 选择站点
        selectSite: function(site) {
            if (site.CD !== this.siteSelected.CD) {
                this.siteSelected = site;
                this.storeSelected = { CD: '', CD_VAL: '' };
                this.getStoreData();
            }
        },

        // 获取站点下店铺信息
        getStoreData: function() {
            var _this = this;
            var param = {
                plat_form: [_this.siteSelected.CD]
            };
            axios.post('/index.php?g=Oms&m=Order&a=storesGet', param).then(function(res) {
                var data = res.data;
                if (data.status === 200000) {
                    _this.storeData = data.data;
                    _this.storeSelected = data.data[0];
                } else {
                    _this.$message.error(_this.$lang(data.info));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },

        // 选择店铺
        selectStore: function(store) {
            if (store.CD !== this.storeSelected.CD) {
                this.storeSelected = store;
            }
        },

        // 选择所需指标
        selectIndicator: function() {
            let resObj = this.compareArr(this.indicatorSelectedCopy, this.indicatorSelected);
            let indicatorChanged;
            if (resObj.add.length > 0) {
                indicatorChanged = resObj.add[0];
                for (let kind in this.indicator) {
                    for (let indicator in this.indicator[kind]) {
                        if (indicatorChanged === this.indicator[kind][indicator].label) {
                            this.indicator[kind][indicator].show = true;
                        }
                    }
                }
            } else if (resObj.del.length > 0) {
                indicatorChanged = resObj.del[0];
                for (let kind in this.indicator) {
                    for (let indicator in this.indicator[kind]) {
                        if (indicatorChanged === this.indicator[kind][indicator].label) {
                            this.indicator[kind][indicator].show = false;
                        }
                    }
                }
            }
            this.indicatorSelectedCopy = this.indicatorSelected;
        },

        // 获取数组改动后，新增、删除的项
        compareArr: function(beforeArr, afterArr) {
            let resObj = {
                    add: [],
                    del: []
                },
                cenObj = {};
            //把beforeArr数组去重放入cenObj 
            for (let i = 0; i < beforeArr.length; i++) {
                cenObj[beforeArr[i]] = beforeArr[i];
            }
            //遍历afterArr，查看其元素是否在cenObj中
            for (let j = 0; j < afterArr.length; j++) {
                if (!cenObj[afterArr[j]]) {
                    resObj.add.push(afterArr[j]);
                } else {
                    delete cenObj[afterArr[j]]
                }
            }
            for (let k in cenObj) {
                resObj.del.push(k);
            }
            return resObj;
        },

        // 获取表格数据
        getTableData: function(sortObj) {
            if (sortObj) {
                this.sort_key = sortObj.prop;
                this.sort_type = sortObj.order === 'descending' ? '1' : '0'
            }
            this.tableLoading = true;
            var _this = this;
            _this.tableData.list = []
            
            var param = {
                plat_cd: this.platSelected.CD,
                site_cd: this.siteSelected.CD,
                store_id: this.storeSelected.CD,
                start_date: this.rangeMonth ? this.rangeMonth[0] : '',
                end_date: this.rangeMonth ? this.rangeMonth[1] : '',
                total_amount_min: this.total_amount_min,
                total_amount_max: this.total_amount_max,
                total_cost_min: this.total_cost_min,
                total_cost_max: this.total_cost_max,
                introduction: this.introduction,
                updated_by: this.updated_by,
                p: this.page,
                rows: this.pageSize,
                sort_key: this.sort_key,
                sort_type: this.sort_type,
            };

            axios.post('/index.php?m=finance&a=settlement_list', param).then(function(res) {
                _this.tableLoading = false;
                var data = res.data;
                if (data.code === 2000) {
                    _this.tableData = data.data;
                    _this.tableData.total_rows = parseInt(data.data.total_rows || data.data.page.total_rows);
                } else {
                    _this.$message.error(_this.$lang(data.msg));
                }
            }).catch(function(err) {
                console.log(err);
            });
        },

        // 搜索修改人
        queryUser: function(query) {
            if (query !== '') {
                // var _this = this;
                this.userLoading = true;
                var param = {
                    name: query
                };
                this.queryPost("/index.php?g=common&m=user&a=search_user", param).then((res) => {
                    this.userLoading = false;
                    var data = res.data;
                    if (data.code === 2000) {
                        this.userData = data.data;
                    } else {
                        this.$message.error(this.$lang(data.msg));
                    }
                }).catch(function(err) {
                    console.log(err);
                });
            } else {
                this.uerData = [];
            }
        },
        resetTable: function() {
            window.location.reload();
        },
        toEdit: function(id) {
            this.editItemId = id;
            this.addBalance();
        },

        // 新增收支明细
        addBalance: function() {
            this.createInterface = sessionStorage.getItem('addBalanceInterface') || 1;
            this.route(this.$lang('新增收支明细'), "balance_add", "create", this.createInterface++);
            sessionStorage.setItem('addBalanceInterface', this.createInterface);
        },

        //跳转详情页
        route: function(title, _html, id, multiple) {
            var dom = document.createElement("a"),
                _href = "/index.php?g=oms&m=balance&a=" + _html + "&platcd=" + this.platSelected.CD + "&sitecd=" + this.siteSelected.CD + "&storeid=" + this.storeSelected.CD + "&itemid=" + this.editItemId;
            dom.setAttribute("onclick", "opennewtab(this,'" + title + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },

        // 删除表格数据
        deleteBalance: function() {
            var listSelectedId = this.listSelected.map((item) => {
                return item.id;
            });
            var param = {
                id: listSelectedId
            };
            var headers = {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
            axios.post("/index.php?g=home&m=finance&a=delete_settlement", Qs.stringify(param, {
                    arrayFormat: 'indices'
                }), headers)
                .then((res) => {
                    var data = res.data;
                    if (data.code === 2000) {
                        this.$message.success(this.$lang('删除成功'));
                        this.listSelected = [];
                        this.page = 1;
                        this.pageSize = 10;
                        this.getTableData();
                    } else {
                        this.$message.error(this.$lang(data.msg));
                    }
                }).catch(function(err) {
                    console.log(err);
                });
        },

        // 导出表格
        exportBalance: function(val) {
            if (val === 1) {
                var listSelectedId = this.listSelected.map((item) => {
                    return item.id;
                });

                window.location.href = `index.php?g=home&m=finance&a=export_settlement&id=${listSelectedId}`
            }
        },
        handleSelectionChange: function(val) {
            this.listSelected = val;
        },
        pageSizeChange: function(val) {
            this.pageSize = val;
            this.getTableData();
        },
        currentPageChange: function(val) {
            this.page = val;
            this.getTableData();
        }
    },
});