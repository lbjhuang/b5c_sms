if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var VM = new Vue({
    el: '#anagementDetail',
    data() {
        return {
            goodsSum: '',
            sku_id: '',
            id: '',
            detail: null,
            detailLoading: false,
            uploadLoading: false,
            activeName: 'first',
            templateVisible: false,
            secondTemplateVisible: false,
            submitSecondConfirmation: false,
            dialogProgramVisible: false,
            quotationSchemes: [],
            scheme_id: '',
            pickerOptions: {
                disabledDate(time) {
                    return time.getTime() < Date.now() - 86400000;
                },
            },
            expectedPickerOptions: {
                disabledDate(time) {
                    return time.getTime() < new Date(VM.detail.inquiry.expected_delivery_date).getTime() - 86400000;
                },
            },
            templateRadio: '',
            secondTemplateRadio: '',
            fileList: [],
            warehouses:[], // 仓库目录
            isElectric: [], // 是否带电
            declareType: [], // 报关方式
            logisticsSupplier: [], // 物流供应商
            planned_transportation_channel_cds: [], // 运输渠道
            currency: [], // 币种
            page: 1,
            size: 10,
            logTotal: 0,
            logTable: [],
            programOne: false,
            programTwo: false,
            programThree: false,
            bLoading: false,
        }
    },
    created() {
        console.log(window.location)
        let query = window.location.href.split('?')[1].split('&')[2];
        this.id = query.split('=')[1];
        this.getOptions();
        this.getDetailData();
        this.getLog();
    },
    methods: {
        handleUpdate() {
            const url = window.location.host === 'erp.gshopper.com' ?  window.location.protocol + '//data.gshopper.com' :
            window.location.protocol + '//data.gshopper.stage.com';
            if (this.id) {
                this.bLoading = true;
                axios.post(url + '/quote/quotationExecuteLogisticsPrice', {quotation_id: this.id}).then(res => {
                    this.bLoading = false;
                    if (res.data.code === 200) {
                        this.getDetailData();
                    }
                })
            }
        },
        templateRadioChange(newVal) {
            this.templateVisible = false;
            if(newVal == 1) {
                // 整柜
                this.detail.quotation_schemes = [{
                    "scheme_type": '1',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570001",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                }]
            } else if(newVal == 2) {
                // 散货
                this.detail.quotation_schemes = [{
                    "scheme_type": '2',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570002",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                }]
            } else if(newVal == 3) {
                // 整柜+散货
                this.detail.quotation_schemes = [{
                    "scheme_type": '3',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        },{
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                }]
            }
        },
        secondTemplateRadioChange(val) {
            this.secondTemplateVisible = false;
            if(val == 1) {
                // 整柜
                this.quotationSchemes = [{
                    "scheme_type": '1',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570001",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                }]
            } else if(val == 2) {
                // 散货
                this.quotationSchemes = [{
                    "scheme_type": '2',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570002",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                }]
            } else if(val == 3) {
                // 整柜+散货
                this.quotationSchemes = [{
                    "scheme_type": '3',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        },{
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                }]
            }
        },
        getOptions() {
            const param = {
                "data": {
                    "query": {
                        "warehouses":true, // 仓库目录
                        "isElectric": true, // 是否带电
                        "declareType": true, // 报关方式 
                        "logisticsSupplier": true, // 物流供应商 
                        "planned_transportation_channel_cds": true, // 运输渠道
                        "currency": true, // 币种
                        "stuffingType": true, // 装柜类型
                    }
                }
            }
            axios.post('/index.php?g=oms&m=CommonData&a=commonData',param).then(res => {
                console.log(res);
                if(res.status == 200 && res.data.code == 2000) {
                    this.warehouses = res.data.data.warehouses;
                    this.isElectric = res.data.data.isElectric;
                    this.declareType = res.data.data.declareType;
                    this.logisticsSupplier = res.data.data.logisticsSupplier;
                    this.planned_transportation_channel_cds = res.data.data.planned_transportation_channel_cds;
                    this.currency = res.data.data.currency;
                    this.stuffingType = res.data.data.stuffingType;
                }
            }).catch(err => {
                console.log(err);
            })
        },
        getDetailData() {
            const param = {
                id: this.id
            }
            this.detailLoading = true;
            axios.get('/index.php?&m=quote&a=quotation_detail',{params:param}).then(res => {
                console.log(res);
                this.detailLoading = false;
                if(res.status == 200 && res.data.code == 2000) {
                    let detail = res.data.data;
                    let packingInfo = [{
                        total_box_num: '',
                        total_volume: '',
                        total_weight: '',
                        allo_in_warehouse: '',
                        allo_in_warehouse_address: '',
                        allo_out_warehouse: '',
                        allo_out_warehouse_address: '',
                        declare_type_cd: '',
                        declare_type_remark: '',
                        is_electric_cd: '',
                    }]
                    this.detail = {
                        quotation: detail.quotation ? detail.quotation : {},
                        goods: detail.goods ? detail.goods : [],
                        inquiry: detail.inquiry ? detail.inquiry : {},
                        // 提前报价无装箱信息需自行添加
                        packing_info: detail.packing_info && detail.packing_info.length > 0 ? detail.packing_info : packingInfo,
                        quotation_schemes: detail.quotation_schemes ? detail.quotation_schemes :[],
                        schemes_options: detail.schemes_options ? detail.schemes_options : []
                    }
                    
                    if(this.detail.quotation.is_twice_quote == '0') { // 是否二次报价
                        if(detail.quotation_schemes && detail.quotation_schemes.length> 0) {
                            this.templateRadio = detail.quotation_schemes[0].scheme_type;
                        }
                    } else {
                        this.templateRadio = '';
                        let arr1  = this.detail.quotation_schemes.filter(item => {
                            return item.scheme_type == '1'
                        })
                        if(arr1.length>0) {
                            this.programOne = true;
                        }
                        let arr2  = this.detail.quotation_schemes.filter(item => {
                            return item.scheme_type == '2'
                        })
                        
                        if(arr2.length>0) {
                            this.programTwo = true;
                        }
                        let arr3  = this.detail.quotation_schemes.filter(item => {
                            return item.scheme_type == '3'
                        })
                        if(arr3.length>0) {
                            this.programThree = true;
                        }
                    }
                    
                    console.log(this.detail)
                } else {
                    this.$message.error(this.$lang(res.data.msg))
                }
            }).catch(err => {
                console.log(err)
            })
        },
        logSizeChange(size) {
            this.size = size;
        },
        logCurrentChange(page) {
            this.page = page;
        },
        getLog() {
            let param = {
                object_name: 'quotation',
                object_id: this.id,
                p: this.page,
                size: this.size
            }
            axios.get('/index.php?m=quote&a=quote_log_list',{params:param}).then(res => {
                if(res.status == 200 && res.data.code == 2000) {
                    this.logTable = res.data.data.data;
                    this.logTotal = Number(res.data.data.page.total);
                }
            })
        },
        // 导入商品文件
        upload() {
            $('#fileInput').click();
        },
        uploadFile(file,res) {
            console.log(file,res)
            let data = new FormData();
            let config = {
                headers:{'Content-Type':'multipart/form-data'}
            }
            data.append("excel", file.target.files[0], file.target.value);
            this.uploadLoading = true;
            axios.post('/index.php?&m=quote&a=excelGoodsImport',data,config).then(res => {
                console.log(res)
                this.uploadLoading = false;
                if(res.status == 200 && res.data.code == 2000) {
                    this.detail.goods = this.detail.goods.concat(res.data.data);
                    let sum = 0;
                    for (let index = 0; index < this.detail.goods.length; index++) {
                        console.log(Number(this.detail.goods[index].good_number))
                        sum += Number(this.detail.goods[index].good_number)
                        
                    }
                    this.goodsSum = sum;
                } else {
                    this.$message.error(res.data.msg)
                }
            })
        },
        // 搜索商品
        skuSearch() {
            let param = {
                keyword: this.sku_id,
                language: 'N000920100'
            }
            this.uploadLoading = true;
            axios.get('/index.php?m=quote&a=searchGoods',{params:param}).then(res => {
                console.log(res.data.data)
                this.uploadLoading = false;
                this.sku_id = '';
                if(res.status == 200 && res.data.code == 2000) {
                    for (let index = 0; index < res.data.data.length; index++) {
                        res.data.data[index].is_search = true;
                        res.data.data[index].good_number = '0';
                    }
                    this.detail.goods = this.detail.goods.concat(res.data.data);
                } else {
                    this.$message.error(res.data.msg)
                }
            })
        },
        // 修改商品数量
        goodNumberChange() {
            let sum = 0;
            for (let index = 0; index < this.detail.goods.length; index++) {
                sum += Number(this.detail.goods[index].good_number);
                
            }
            this.goodsSum = sum;
        },
        // 删除商品
        goodsDetail(index) {
            this.detail.goods.splice(index,1)
        },
        deliveryChange(val) {
            console.log(val)
            if(!val) {
                this.detail.inquiry.expected_warehousing_date = '';
            }
        },
        // 仓库地址联动
        alloChange(event,index) {
            for (const key in this.warehouses) {
                if (event == this.warehouses[key].CD) {
                    this.$set(this.detail.packing_info[index], 'allo_in_warehouse_address', this.warehouses[key].place_address)
                }
            }
        },
        // 仓库地址联动
        alloOutChange(event,index) {
            for (const key in this.warehouses) {
                if (event == this.warehouses[key].CD) {
                    this.$set(this.detail.packing_info[index], 'allo_out_warehouse_address', this.warehouses[key].place_address)
                }
            }
        },
        // 报关方式联动
        declareChange(event,index) {
            if(event == 'N003550001') {
                this.$set(this.detail.packing_info[index], 'declare_type_remark', '')
            }
        },
        // 发起询价
        inquiryClick() {
            console.log(this.detail.goods)
            if(this.detail.goods.length == 0) {
                this.$message.warning(this.$lang('请先导入商品'));
                return false;
            }
            if(!this.detail.inquiry.planned_transportation_channel_cd) {
                this.$message.warning(this.$lang('请选择计划运输渠道'));
                return false;
            }
            if(!this.detail.inquiry.expected_delivery_date) {
                this.$message.warning(this.$lang('期望出库日期'));
                return false;
            }
            if(!this.detail.inquiry.expected_warehousing_date) {
                this.$message.warning(this.$lang('期望入库日期'));
                return false;
            }
            let packing_info = this.detail.packing_info;
            for (let i = 0; i < packing_info.length; i++) {
                // 正式报价不校验箱数、体积、重量必填
                if(!packing_info[i].total_box_num && detail.quotation.quote_type == '2') {
                    this.$message.warning(this.$lang('总箱数必填'));
                    return false;
                }
                if(!packing_info[i].total_volume && detail.quotation.quote_type == '2') {
                    this.$message.warning(this.$lang('总体积必填'));
                    return false;
                }
                if(!packing_info[i].total_weight && detail.quotation.quote_type == '2') {
                    this.$message.warning(this.$lang('总重量必填'));
                    return false;
                }
                if(!packing_info[i].allo_in_warehouse) {
                    this.$message.warning(this.$lang('调入仓库必填'));
                    return false;
                }
                if(!packing_info[i].allo_in_warehouse_address) {
                    this.$message.warning(this.$lang('调入仓库地址必填'));
                    return false;
                }
                if(!packing_info[i].allo_out_warehouse) {
                    this.$message.warning(this.$lang('调出仓库必填'));
                    return false;
                }
                if(!packing_info[i].allo_out_warehouse_address) {
                    this.$message.warning(this.$lang('调出仓库地址必填'));
                    return false;
                }
                if(!packing_info[i].declare_type_cd) {
                    this.$message.warning(this.$lang('报关方式必填'));
                    return false;
                }
                if(!packing_info[i].is_electric_cd) {
                    this.$message.warning(this.$lang('是否带电必填'));
                    return false;
                }
                
            }
            for (let i = 0; i < this.detail.goods.length; i++) {
                if(!this.detail.goods[i].good_number) {
                    this.$message.warning(this.$lang('商品数量必填'));
                    return false;
                }
            }
            let packingInfo = this.detail.packing_info.map(item => {
                return {
                    id: item.id,
                    allo_no: item.allo_no,
                    total_box_num: item.total_box_num,
                    total_volume: item.total_volume,
                    total_weight: item.total_weight,
                    allo_in_warehouse: item.allo_in_warehouse,
                    allo_in_warehouse_address: item.allo_in_warehouse_address,
                    allo_out_warehouse: item.allo_out_warehouse,
                    allo_out_warehouse_address: item.allo_out_warehouse_address,
                    declare_type_cd: item.declare_type_cd,
                    declare_type_remark: item.declare_type_remark,
                    is_electric_cd: item.is_electric_cd,
                }
            })
            let goods_info = this.detail.goods.map(item => {
                return {
                    good_name: item.good_name,
                    good_number: item.good_number,
                    sku_id: item.sku_id
                }
            })
            let param = {
                "id": this.id,  
                "quotation": {
                    operate_remark: this.detail.quotation.operate_remark
                },
                "goods_info": goods_info,
                "inquiries": this.detail.inquiry,
                "packing_data": packingInfo
            };
            this.detailLoading = true;
            axios.post('/index.php?&m=quote&a=save_quotation_step2',param).then(res => {
                console.log(res);
                this.detailLoading = false;
                if(res.status == 200 && res.data.code == 2000) {
                    this.getDetailData();
                } else {
                    this.$message.error(res.data.msg)
                }
            }).catch(err => {
                console.log(err)
            })
        },
        // 提交报价
        offterClick() {
            if(this.detail.quotation.is_twice_quote == '0') { // 非二次报价
                if(this.detail.quotation_schemes.length === 0) {
                    this.$message.warning(this.$lang('请先选择报价模板'));
                    return false
                }
                for (let i = 0; i < this.detail.quotation_schemes.length; i++) {
                    let scheme_detail = this.detail.quotation_schemes[i].scheme_detail;
                    for (let j = 0; j < scheme_detail.length; j++) {
                        if(!scheme_detail[j].transport_supplier_id) {
                            this.$message.warning(this.$lang('运输公司必填'));
                            return false;
                        }
                        if(!scheme_detail[j].transportation_channel_cd) {
                            this.$message.warning(this.$lang('运输渠道必填'));
                            return false;
                        }
                        if(!scheme_detail[j].logistics_currency_cd) {
                            this.$message.warning(this.$lang('物流费用币种必填'));
                            return false;
                        }
                        if(!scheme_detail[j].logistics_cost) {
                            this.$message.warning(this.$lang('物流费用必填'));
                            return false;
                        }
                        if(scheme_detail[j].logistics_cost < 0) {
                            this.$message.warning(this.$lang('物流费用必须为正数'));
                            return false;
                        }
                        if(!scheme_detail[j].insurance_currency_cd) {
                            this.$message.warning(this.$lang('保险费用币种必填'));
                            return false;
                        }
                        if(!scheme_detail[j].insurance_cost) {
                            this.$message.warning(this.$lang('保险费用必填'));
                            return false;
                        }
                        if(scheme_detail[j].insurance_cost < 0) {
                            this.$message.warning(this.$lang('保险费用必须为正数'));
                            return false;
                        }
                        if(!scheme_detail[j].predict_currency_cd) {
                            this.$message.warning(this.$lang('预计费用币种必填'));
                            return false;
                        }
                        if(!scheme_detail[j].predict_cost) {
                            this.$message.warning(this.$lang('预计费用必填'));
                            return false;
                        }
                        if(scheme_detail[j].predict_cost < 0) {
                            this.$message.warning(this.$lang('预计费用必须为正数'));
                            return false;
                        }
                        if(!scheme_detail[j].delivery_date) {
                            this.$message.warning(this.$lang('出库时间必填'));
                            return false;
                        }
                        if(!scheme_detail[j].hours_underway_date) {
                            this.$message.warning(this.$lang('航行时间必填'));
                            return false;
                        }
                        if(!scheme_detail[j].stuffing_type_cd) {
                            this.$message.warning(this.$lang('装柜类型必填'));
                            return false;
                        }
                    }
                }
            } else { // 二次报价
                if(this.quotationSchemes.length === 0) {
                    this.$message.warning(this.$lang('请先选择报价模板'));
                    return false
                }
                for (let i = 0; i < this.quotationSchemes.length; i++) {
                    let scheme_detail = this.quotationSchemes[i].scheme_detail;
                    for (let j = 0; j < scheme_detail.length; j++) {
                        if(!scheme_detail[j].transport_supplier_id) {
                            this.$message.warning(this.$lang('运输公司必填'));
                            return false;
                        }
                        if(!scheme_detail[j].transportation_channel_cd) {
                            this.$message.warning(this.$lang('运输渠道必填'));
                            return false;
                        }
                        if(!scheme_detail[j].logistics_currency_cd) {
                            this.$message.warning(this.$lang('物流费用币种必填'));
                            return false;
                        }
                        if(!scheme_detail[j].logistics_cost) {
                            this.$message.warning(this.$lang('物流费用必填'));
                            return false;
                        }
                        if(scheme_detail[j].logistics_cost < 0) {
                            this.$message.warning(this.$lang('物流费用必须为正数'));
                            return false;
                        }
                        if(!scheme_detail[j].insurance_currency_cd) {
                            this.$message.warning(this.$lang('保险费用币种必填'));
                            return false;
                        }
                        if(!scheme_detail[j].insurance_cost) {
                            this.$message.warning(this.$lang('保险费用必填'));
                            return false;
                        }
                        if(scheme_detail[j].insurance_cost < 0) {
                            this.$message.warning(this.$lang('保险费用必须为正数'));
                            return false;
                        }
                        if(!scheme_detail[j].predict_currency_cd) {
                            this.$message.warning(this.$lang('预计费用币种必填'));
                            return false;
                        }
                        if(!scheme_detail[j].predict_cost) {
                            this.$message.warning(this.$lang('预计费用必填'));
                            return false;
                        }
                        if(scheme_detail[j].predict_cost < 0) {
                            this.$message.warning(this.$lang('预计费用必须为正数'));
                            return false;
                        }
                        if(!scheme_detail[j].delivery_date) {
                            this.$message.warning(this.$lang('出库时间必填'));
                            return false;
                        }
                        if(!scheme_detail[j].hours_underway_date) {
                            this.$message.warning(this.$lang('航行时间必填'));
                            return false;
                        }
                        if(!scheme_detail[j].stuffing_type_cd) {
                            this.$message.warning(this.$lang('装柜类型必填'));
                            return false;
                        }
                    }
                }
            }
            let quotationSchemes = [];
            for (let i = 0; i < this.detail.quotation_schemes.length; i++) {
                let schemesObj = {
                    "id": this.detail.quotation_schemes[i].id,
                    'scheme_type': this.detail.quotation_schemes[i].scheme_type,
                    'scheme_detail': []
                };
                let schemeDetail = this.detail.quotation_schemes[i].scheme_detail;
                for (let j = 0; j < schemeDetail.length; j++) {
                    let detailObj = {
                        "id": schemeDetail[j].id,
                        "transport_supplier_id": schemeDetail[j].transport_supplier_id,
                        "transportation_channel_cd": schemeDetail[j].transportation_channel_cd,
                        "logistics_currency_cd": schemeDetail[j].logistics_currency_cd,
                        "logistics_cost": schemeDetail[j].logistics_cost,
                        "insurance_currency_cd": schemeDetail[j].insurance_currency_cd,
                        "insurance_cost": schemeDetail[j].insurance_cost,
                        "predict_currency_cd": schemeDetail[j].predict_currency_cd,
                        "predict_cost": schemeDetail[j].predict_cost,
                        "delivery_date": schemeDetail[j].delivery_date,
                        "hours_underway_date": schemeDetail[j].hours_underway_date,
                        "stuffing_type_cd": schemeDetail[j].stuffing_type_cd,
                        "quotation_ids": schemeDetail[j].quotation_ids,
                        "remark": schemeDetail[j].remark,
                    }
                    schemesObj.scheme_detail.push(detailObj)
                }
                quotationSchemes.push(schemesObj);
            }
            
            quotationSchemes = quotationSchemes.concat(this.quotationSchemes);
            let param = {
                "object_name": "quotation", // # 数据类型 报价类型 传 quotation 、 
                "object_id": this.id, //# 报价记录ID 
                "quotation_scheme": quotationSchemes
                // "quotation_scheme": this.detail.quotation.is_twice_quote == '0' ? quotationSchemes : this.quotationSchemes, // 是否二次报价  0 否  1 是
            }
            
            this.submitSecondConfirmation = false;
            this.detailLoading = true;
            axios.post('/index.php?&m=quote&a=save_quotation_step3',param).then(res => {
                this.detailLoading = false;
                if(res.status == 200 && res.data.code == 2000) {
                    this.getDetailData();
                } else {
                    this.$message.warning(this.$lang(res.data.msg));
                }
            }).catch(err => {
                console.log(err)
            })
            // this.$confirm(this.$lang('请确认报价输入无误'), '', {
            //     confirmButtonText: this.$lang('确定'),
            //     cancelButtonText: this.$lang('取消'),
            //     center: true
            //     }).then(() => {
                
            // })
            
        },
        // 查看拼柜详情
        gocombineCabinetsDetail() {
            newTab("/index.php?m=quote&a=combine_cabinets_detail&id=" + this.detail.quotation.quote_lcl_id, this.$lang('拼柜详情'));
        },
        goBack(type) { // 退回上一步   type  是否二次报价
            let param = {};
            if(type) {
                param = {
                    quotation_id: this.id,
                    all_not_approved: type
                };
            } else {
                param = {
                    quotation_id: this.id,
                };
            }
            this.detailLoading = true;
            axios.post('/index.php?&m=quote&a=back_to_quote',param).then(res => {
                this.detailLoading = false;
                if(res.status == 200 && res.data.code == 2000) {
                    this.getDetailData();
                } else {
                    this.$message.warning(this.$lang(res.data.msg));
                }
            }).catch(err => {
                console.log(err)
            })
        },
        // 确认报价
        queryClick() {
            if(!this.scheme_id) {
                this.$message.warning(this.$lang('请先选择报价方案'));
                return false;
            }
            let param = {
                scheme_id: this.scheme_id
            };
            this.dialogProgramVisible = false;
            this.detailLoading = true;
            axios.post('/index.php?&m=quote&a=quotation_scheme_conform',param).then(res => {
                this.detailLoading = false;
                if(res.status == 200 && res.data.code == 2000) {
                    this.getDetailData();
                } else {
                    this.$message.warning(this.$lang(res.data.msg));
                }
            }).catch(err => {
                console.log(err)
            })
        },
        detailAdd(index) {
            this.detail.quotation_schemes[index].scheme_detail.push({
                "transport_supplier_id": "",
                "transportation_channel_cd": "",
                "logistics_currency_cd": "",
                "logistics_cost": "",
                "insurance_currency_cd": "",
                "insurance_cost": "",
                "predict_currency_cd": "",
                "predict_cost": "",
                "delivery_date": "",
                "hours_underway_date": "",
                "stuffing_type_cd": "",
                "quotation_ids": "",
                "remark": ""
            })
        },
        detailMinus(index,key){
            if(this.detail.quotation_schemes[index].scheme_detail.length > 1) {
                this.detail.quotation_schemes[index].scheme_detail.splice(key, 1);
            } else {
                this.$message.warning(this.$lang('最后一条不能删除'));
            }
        },
        // 添加方案
        add() {
            if(this.templateRadio == 1) {
                this.detail.quotation_schemes.push({
                    "scheme_type": '1',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570001",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                })
            } else if(this.templateRadio == 2) {
                this.detail.quotation_schemes.push({
                    "scheme_type": '2',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570002",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                })
            } else if(this.templateRadio == 3) {
                this.detail.quotation_schemes.push({
                    "scheme_type": '3',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        },{
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                })
            }
        },
        // 删除方案
        minus(index) {
            if(this.detail.quotation_schemes.length > 1) {
                this.detail.quotation_schemes.splice(index, 1)
            } else {
                this.$message.warning(this.$lang('最后一条不能删除'))
                
            }
            
        },
        // 二次报价添加方案
        secondAdd() {
            if(this.secondTemplateRadio == 1) {
                this.quotationSchemes.push({
                    "scheme_type": '1',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570001",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                })
            } else if(this.secondTemplateRadio == 2) {
                this.quotationSchemes.push({
                    "scheme_type": '2',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "N003570002",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                })
            } else if(this.secondTemplateRadio == 3) {
                this.quotationSchemes.push({
                    "scheme_type": '3',
                    "scheme_detail": [ 
                        {
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        },{
                            "transport_supplier_id": "",
                            "transportation_channel_cd": "",
                            "logistics_currency_cd": "",
                            "logistics_cost": "",
                            "insurance_currency_cd": "",
                            "insurance_cost": "",
                            "predict_currency_cd": "",
                            "predict_cost": "",
                            "delivery_date": "",
                            "hours_underway_date": "",
                            "stuffing_type_cd": "",
                            "quotation_ids": "",
                            "remark": ""
                        }
                    ]
                })
            }
        },
        // 二次报价删除方案
        secondMinus(index) {
            if(this.quotationSchemes.length > 1) {
                this.quotationSchemes.splice(index, 1)
            } else {
                this.$message.warning(this.$lang('最后一条不能删除'))
                
            }
            
        },
        secondDetailAdd(index) {
            this.quotationSchemes[index].scheme_detail.push({
                "transport_supplier_id": "",
                "transportation_channel_cd": "",
                "logistics_currency_cd": "",
                "logistics_cost": "",
                "insurance_currency_cd": "",
                "insurance_cost": "",
                "predict_currency_cd": "",
                "predict_cost": "",
                "delivery_date": "",
                "hours_underway_date": "",
                "stuffing_type_cd": "",
                "quotation_ids": "",
                "remark": ""
            })
        },
        secondDetailMinus(index,key){
            this.quotationSchemes[index].scheme_detail.splice(key, 1)
        },
    },
})