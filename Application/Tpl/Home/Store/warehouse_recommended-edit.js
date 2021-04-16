const warehouseRecommendedEdit = Vue.component("warehouseRecommendedEdit", {
    template: `
            <div class="warehouse-recommended-edit">
                 <div class="edit">
                    <div class="edit__fields">
                       <el-form ref="form"      
                                :model="warehouseRecommended"      
                                :rules="rules"
                                label-width="120px"
                                label-position="left">
                          <el-row :gutter="20" type="flex">
                             <el-col :md="24">
                                <div class="conditional__selection-region">
                                   <div class="conditions__being-selected">
                                       <div class="title">
                                        <span>{{$lang('已选条件（第二步）')}}</span>
                                       </div>
                                       <div class="conditions__being-selected-content">
                                         <template v-for="(item,index) in selectedConditionals">
                                           <div :key="index" class="selected__conditional" @click="onSelectSku(item)">
                                             <span class="conditional__name">{{$lang(item.name)}}</span>
                                             <span class="conditional__value">{{item.value}}</span>
                                           </div>
                                         </template>
                                       </div>
                                   </div>
                                   <div class="conditional__to-be-selected">
                                       <div class="title">
                                        <span>{{$lang('已选条件（第一步）')}}</span>
                                       </div>
                                       <div class="conditional__to-be-selected-content">
                                               <el-checkbox  v-if="storePlatform.is_cdiscount===1 || storePlatform.is_aliexpress===1"   v-model="modeDistribution" :label="$lang('用户指定配送方式为：指定运输方式')"></el-checkbox>
<!--        需求不明确，暂时取消                                        <el-checkbox v-if="storePlatform.is_gp===1"   v-model="deliveryWarehouse" :label="$lang('用户指定的发货仓库：指定的发货仓库')"></el-checkbox>-->
                                               <el-checkbox v-if="storePlatform.is_aliexpress===1 || storePlatform.is_ebay===1"   v-model="deliveryCountry" :label="$lang('发货国家：国家')"></el-checkbox>
                                               <el-checkbox v-model="skuCode"  :label="$lang('订单商品sku包含：指定的sku商品')"></el-checkbox>
                                               <el-checkbox v-model="skuPrefix" :label="$lang('订单sku包含指定的前缀：前缀')"></el-checkbox>
                                               <el-checkbox v-model="skuSuffix"  :label="$lang('订单sku包含指定的后缀：后缀')"></el-checkbox>
                                               <el-checkbox v-model="country"  :label="$lang('订单的目的地是：国家')"></el-checkbox>
                                       </div>
                                   </div>
                                 </div>
                             </el-col>
                          </el-row>
                           <el-row :gutter="20" type="flex">
                             <el-col :md="24">
                               <div class="content__input">
                                 <div class="warehouse__logistics">
                                       <div class="title">
                                        <span>{{$lang('指定仓库物流（第三步）')}}</span>
                                       </div>
                                       <div class="warehouse__logistics-content">
                                           <el-form-item :label="$lang('下发仓库为：')"  label-width="100px" prop="warehouse_code">
                                              <el-select
                                                  filterable
                                                  v-model="warehouseRecommended.warehouse_code"
                                                  clearable
                                                  :placeholder="$lang('请选择仓库')"
                                               >
                                               <el-option
                                                  v-for="item in allWarehouses"
                                                  :key="item.CD"
                                                  :loading="loading"
                                                  :label="$lang(item.CD_VAL)"
                                                  :value="item.CD"
                                                />
                                              </el-select>
                                           </el-form-item>
                                           <el-form-item :label="$lang('物流公司为：')" label-width="100px" prop="logistics_company_code">
                                              <el-select
                                                  filterable
                                                  v-model="warehouseRecommended.logistics_company_code"
                                                  clearable
                                                  :placeholder="$lang('请选择物流公司')"
                                               >
                                               <el-option
                                                 v-for="item in allLogisticsCompany"
                                                 :key="item.code"
                                                 :loading="loading"
                                                 :label="$lang(item.CD_VAL)"
                                                 :value="item.LOGISTICS_CODE"
                                                />
                                              </el-select>
                                           </el-form-item>
                                           <el-form-item :label="$lang('物流方式为：')" label-width="100px" prop="logistics_mode_id">
                                              <el-select
                                                  filterable
                                                  v-model="warehouseRecommended.logistics_mode_id"
                                                  clearable
                                                  :placeholder="$lang('请选择物流方式')"
                                               >
                                               <el-option
                                                 v-for="item in allLogisticsWay"
                                                 :key="item.code"
                                                 :loading="loading"
                                                 :label="$lang(item.LOGISTICS_MODE)"
                                                 :value="item.ID"
                                                />
                                              </el-select>
                                           </el-form-item>
                                           <el-form-item :label="$lang('面单方式为：')" label-width="100px" prop="face_order_code">
                                              <el-select
                                                  filterable
                                                  v-model="warehouseRecommended.face_order_code"
                                                  clearable
                                                  :placeholder="$lang('请选择面单方式')"
                                               >
                                               <el-option
                                                  v-for="item in allSurfaceWay"
                                                  :key="item.code"
                                                  :loading="loading"
                                                  :label="$lang(item.CD_VAL)"
                                                  :value="item.CD"
                                                />
                                              </el-select>
                                           </el-form-item>
                                       </div>
                                       <div class="rule">
                                         <div class="title">
                                           <span>{{$lang('规则名称（第四步）')}}</span>
                                         </div>
                                         <div class="rule__content">
                                            <el-form-item :label="$lang('规则名称为：')" label-width="100px" prop="rule_name">
                                               <el-input
                                                 :placeholder="$lang('请输入内容')"
                                                 v-model="warehouseRecommended.rule_name">
                                               </el-input>
                                            </el-form-item>
                                           </div>
                                       </div>
                                 </div>
                                 <div class="remark">
                                         <div class="title">
                                           <span>{{$lang("备注")}}</span>
                                         </div>
                                       <div class="remark-content">
                                         <el-input
                                            type="textarea"
                                            :rows="14"
                                            :placeholder="$lang('请输入内容')"
                                            v-model="warehouseRecommended.remark">
                                         </el-input>
                                       </div>
                                 </div>
                               </div>
                            </el-col>
                           </el-row>
                           <el-row :gutter="20" type="flex">
                             <el-col :md="24">
                                 <div class="actions">
                                    <el-button type="primary" :loading="loading" @click="onSave">{{$lang("确定")}}</el-button>
                                    <el-button type="primary" @click="onClose">{{$lang("取消")}}</el-button>
                                 </div>
                             </el-col>
                           </el-row>
                       </el-form>
                    </div>
                 </div>
</div>
    `,
    props: {
        id: {
            type: String,
            default: null
        },
        storeId: {
            type: String,
            default: null
        }
    },
    components: {
        // "warehouse-recommended-goods-sku":warehouseRecommendedGoodsSku
    },
    data() {
        return {
            warehouseRecommended: {
                warehouse_code: null,
                logistics_company_code: null,
                logistics_mode_id: null,
                face_order_code: null,
                rule_name: null,
                remark: null
            },
            allWarehouses: [],
            allLogisticsCompany: [],
            allLogisticsCompanyByWarehouseCode: [],
            allLogisticsWay: [],
            allSurfaceWay: [],
            allCountry: [],
            checkedList: [],
            initConditionals: [
                {name: this.$lang("订单商品sku包含："), value: this.$lang("指定的sku商品"), identification: "sku"},
                {name: this.$lang("订单sku包含指定的前缀："), value: this.$lang("前缀"), identification: "skuPrefix"},
                {name: this.$lang("订单sku包含指定的后缀："), value: this.$lang("后缀"), identification: "skuSuffix"},
                {name: this.$lang("订单的目的地是："), value: this.$lang("国家"), identification: "country"},
                {name: this.$lang('用户指定的发货仓库：'), value: this.$lang('指定的发货仓库'), identification: "deliveryWarehouse"},
                {name: this.$lang('用户指定配送方式为：'), value: this.$lang('指定运输方式'), identification: "modeDistribution"},
                {name: this.$lang('发货国家：'), value: this.$lang('国家'), identification: "deliveryCountry"}
            ],
            selectedConditionals: [],
            loading: false,
            searching: false,
            selectedSkuDialogVisible: false,
            skuCode: null,
            skuPrefix: null,
            skuSuffix: null,
            country: null,
            deliveryWarehouse: null,
            modeDistribution: null,
            deliveryCountry: null,
            allSku: [],
            allSkuPrefix: null,
            allSkuSuffix: null,
            allOrderDestination: [],
            allDeliveryWarehouse: [],
            allModeDistribution: [],
            allDeliveryCountry: [],
            storePlatform: {},
            rules: {
                warehouse_code: [{required: true, message: this.$lang("请选择仓库名称")}],
                logistics_company_code: [{required: true, message: this.$lang("请选择物流公司名称")}],
                logistics_mode_id: [{required: true, message: this.$lang("请选择物流方式名称")}],
                face_order_code: [{required: true, message: this.$lang("请选择面单方式名称")}],
                rule_name: [{required: true, message: this.$lang("请输入规则名称")}]
            }
        }
    },
    watch: {
        skuCode: function (val, oldVal) {
            console.log("新值变化了", val);
            console.log("旧值变化了", oldVal);
            if (val && !oldVal) { //将内容勾选至第二步
                if (oldVal != null) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[0].name,
                        value: this.initConditionals[0].value,
                        identification: this.initConditionals[0].identification
                    })
                }
            }
            if (!val && oldVal) { //取消勾选
                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "sku") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allSku = [];
            }

        },
        skuPrefix: function (val, oldVal) {
            if (val && !oldVal) { //将内容勾选至第二步

                if (oldVal != null) {
                    console.log("前缀1");
                    this.selectedConditionals.push({
                        name: this.initConditionals[1].name,
                        value: this.initConditionals[1].value,
                        identification: this.initConditionals[1].identification
                    })
                }
            }
            console.log("当前内容值", val, oldVal);

            if (!val && oldVal) { //取消勾选
                console.log("前缀取消");

                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "skuPrefix") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allSkuPrefix = null;
            }
        },
        skuSuffix: function (val, oldVal) {
            if (val && !oldVal) { //将内容勾选至第二步

                if (oldVal != null) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[2].name,
                        value: this.initConditionals[2].value,
                        identification: this.initConditionals[2].identification
                    })
                }
            }
            if (!val && oldVal) { //取消勾选
                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "skuSuffix") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allSkuSuffix = null;
            }
        },
        country: function (val, oldVal) {
            if (val && !oldVal) { //将内容勾选至第二步

                if (oldVal != null) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[3].name,
                        value: this.initConditionals[3].value,
                        identification: this.initConditionals[3].identification
                    })
                }
            }
            if (!val && oldVal) { //取消勾选
                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "country") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allOrderDestination = [];
            }
        },
        deliveryWarehouse: function (val, oldVal) {
            if (val && !oldVal) { //将内容勾选至第二步
                if (oldVal != null) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[4].name,
                        value: this.initConditionals[4].value,
                        identification: this.initConditionals[4].identification
                    })
                }
            }
            if (!val && oldVal) { //取消勾选
                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "deliveryWarehouse") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allDeliveryWarehouse = [];
            }
        },
        modeDistribution: function (val, oldVal) {
            if (val && !oldVal) { //将内容勾选至第二步
                if (oldVal != null) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[5].name,
                        value: this.initConditionals[5].value,
                        identification: this.initConditionals[5].identification
                    })
                }

            }
            if (!val && oldVal) { //取消勾选
                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "modeDistribution") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allModeDistribution = [];
            }
        },
        deliveryCountry: function (val, oldVal) {
            if (val && !oldVal) { //将内容勾选至第二步
                if (oldVal != null) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[6].name,
                        value: this.initConditionals[6].value,
                        identification: this.initConditionals[6].identification
                    })
                }
            }
            if (!val && oldVal) { //取消勾选
                this.selectedConditionals.forEach((obj, index) => {
                    if (obj.identification === "deliveryCountry") {
                        this.selectedConditionals.splice(index, 1);
                    }
                })
                this.allDeliveryCountry = [];
            }
        },
        "warehouseRecommended.warehouse_code": function (val, oldval) {
            console.log("监听仓库变化了", val);

            if (val) {
                if (oldval === null) {
                    this.allLogisticsCompanyByWarehouseCode.forEach((companyObj) => {
                        // console.log("遍历次数");
                        companyObj.WARE_HOUSE.forEach((value) => {
                            // console.log("值是否相等", companyObj.WARE_HOUSE);
                            this.allWarehouses.forEach(warehouseObj => {
                                if (value === warehouseObj.CD && value === val) {
                                    this.allLogisticsCompany.push(companyObj);
                                }
                            })
                        })
                    })
                    // 去除重复数据
                    for (let i = 0; i < this.allLogisticsCompany.length; i++) {
                        for (let j = i + 1; j < this.allLogisticsCompany.length; j++) {
                            if (this.allLogisticsCompany[i].LOGISTICS_CODE == this.allLogisticsCompany[j].LOGISTICS_CODE) {
                                this.allLogisticsCompany.splice(j, 1);
                                j--;
                            }
                        }
                    }

                } else {

                    console.log("旧值", oldval);
                    this.allLogisticsCompany = [];
                    this.warehouseRecommended.logistics_company_code = null;

                    this.allLogisticsCompanyByWarehouseCode.forEach((companyObj) => {
                        // console.log("遍历次数");
                        companyObj.WARE_HOUSE.forEach((value) => {
                            // console.log("值是否相等", companyObj.WARE_HOUSE);
                            this.allWarehouses.forEach(warehouseObj => {
                                if (value === warehouseObj.CD && value === val) {
                                    this.allLogisticsCompany.push(companyObj);
                                }
                            })
                        })
                    })
                    // 去除重复数据
                    for (let i = 0; i < this.allLogisticsCompany.length; i++) {
                        for (let j = i + 1; j < this.allLogisticsCompany.length; j++) {
                            if (this.allLogisticsCompany[i].LOGISTICS_CODE == this.allLogisticsCompany[j].LOGISTICS_CODE) {
                                this.allLogisticsCompany.splice(j, 1);
                                j--;
                            }
                        }
                    }
                }
                console.log("数据去重", this.allLogisticsCompany);
            } else {
                this.allLogisticsCompany = [];
                this.warehouseRecommended.logistics_company_code = null;
            }


        },
        "warehouseRecommended.logistics_company_code": function (val, oldval) {
            console.log("监听物流公司变化了", val);

            if (val) {
                if (oldval === null) {
                    axios.post("/index.php?m=store&a=logisticsModeByCompanyAndStore", {
                        company_code: val,
                        store_id: this.storeId
                    }).then((res) => {
                        console.log("获取的物流方式", res.data.data);
                        this.allLogisticsWay = res.data.data;
                    })
                } else {
                    this.allLogisticsWay = [];
                    this.warehouseRecommended.logistics_mode_id = null;
                    axios.post("/index.php?m=store&a=logisticsModeByCompanyAndStore", {
                        company_code: val,
                        store_id: this.storeId
                    }).then((res) => {
                        console.log("获取的物流方式", res.data.data);
                        this.allLogisticsWay = res.data.data;
                    })
                }
            } else {
                this.allLogisticsWay = [];
                this.warehouseRecommended.logistics_mode_id = null;
            }

        },
        "warehouseRecommended.logistics_mode_id": function (val, oldval) {
            console.log("监听物流方式变化了", val);

            if (val) {

                if (oldval === null) {
                    axios.post("/index.php?g=OMS&m=Patch&a=faceOrderGet", {logistic_model_id: val}).then((res) => {
                        console.log("获取的面单方式", res.data.data);
                        this.allSurfaceWay = res.data.data;
                    })
                } else {
                    this.allSurfaceWay = [];
                    this.warehouseRecommended.face_order_code = null;
                    axios.post("/index.php?g=OMS&m=Patch&a=faceOrderGet", {logistic_model_id: val}).then((res) => {
                        console.log("获取的面单方式", res.data.data);
                        this.allSurfaceWay = res.data.data;
                    })
                }
            } else {
                this.allSurfaceWay = [];
                this.warehouseRecommended.face_order_code = null;
            }
        }
    },
    created() {
        // 商品SKU
        this.$bus.$on("edit-all-selected-sku", (value) => {
            console.log("监听到了", value);
            if (value.length === 0) {
                this.allSku = [];
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "sku") {
                        obj.value = this.$lang("指定的sku商品")
                    }
                })
            } else {
                this.allSku = value;

                let skuIds = [];
                value.forEach((obj) => {
                    skuIds.push(obj.sku_id)
                })

                let allSelectedSku = skuIds.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allSelectedSku);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "sku") {
                        obj.value = allSelectedSku
                    }
                })
            }

        })
        // 商品包含的SKU前缀
        this.$bus.$on("edit-all-selected-sku-prefix", (value) => {
            console.log("监听到了", value);
            if (!value) { //如果值为空了
                this.allSkuPrefix = null;
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "skuPrefix") {
                        obj.value = this.$lang('前缀');
                    }
                })
            } else {
                this.allSkuPrefix = value;
                let allSkuPrefix = value.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allSkuPrefix);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "skuPrefix") {
                        obj.value = allSkuPrefix
                    }
                })
            }

        })

        // 商品包含的SKU后缀
        this.$bus.$on("edit-all-selected-sku-suffix", (value) => {
            console.log("监听到了", value);
            if (!value) {
                this.allSkuSuffix = null;
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "skuSuffix") {
                        obj.value = this.$lang('后缀');
                    }
                })
            } else {
                this.allSkuSuffix = value;
                let allSkuSuffix = value.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allSkuSuffix);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "skuSuffix") {
                        obj.value = allSkuSuffix
                    }
                })
            }
        })

        // 选择国家
        this.$bus.$on("edit-all-order-destination", (value) => {
            console.log("监听到了", value);
            if (value.length === 0) {
                this.allOrderDestination = [];
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "country") {
                        obj.value = this.$lang("国家")
                    }
                })
            } else {
                this.allOrderDestination = value;
                let names = [];
                value.forEach((obj) => {
                    names.push(this.$lang(obj.zh_name));
                })
                console.log("名称", names);
                let allOrderDestination = names.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allOrderDestination);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "country") {
                        obj.value = allOrderDestination
                    }
                })
            }

        })
        // 用户指定的发货仓库
        this.$bus.$on("edit-all-delivery-warehouse", (value) => {
            console.log("监听到了", value);
            if (value.length === 0) {
                this.allDeliveryWarehouse = [];
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "deliveryWarehouse") {
                        obj.value = this.$lang("指定的发货仓库")
                    }
                })
            } else {
                this.allDeliveryWarehouse = value;
                let names = [];
                value.forEach((obj) => {
                    names.push(this.$lang(obj.CD_VAL));
                })
                console.log("名称", names);
                let allDeliveryWarehouse = names.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allDeliveryWarehouse);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "deliveryWarehouse") {
                        obj.value = allDeliveryWarehouse
                    }
                })
            }

        })

        // 用户指定的运输方式
        this.$bus.$on("edit-all-mode-distribution", (value) => {
            console.log("监听到了", value);
            if (value.length === 0) {
                this.allModeDistribution = [];
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "modeDistribution") {
                        obj.value = this.$lang("运输方式")
                    }
                })
            } else {
                this.allModeDistribution = value;
                let names = [];
                value.forEach((obj) => {
                    names.push(this.$lang(obj.display_name));
                })
                console.log("名称", names);
                let allModeDistribution = names.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allModeDistribution);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "modeDistribution") {
                        obj.value = allModeDistribution
                    }
                })
            }

        })

        // 发货国家
        this.$bus.$on("edit-all-delivery-country", (value) => {
            console.log("监听到了", value);
            if (value.length === 0) {
                this.allOrderDestination = [];
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "deliveryCountry") {
                        obj.value = this.$lang("国家")
                    }
                })
            } else {
                this.allDeliveryCountry = value;
                let names = [];
                value.forEach((obj) => {
                    names.push(this.$lang(obj.zh_name));
                })
                console.log("名称", names);
                let allDeliveryCountry = names.toString().replace(/,/g, this.$lang(" 或 "))
                console.log("结果", allDeliveryCountry);
                this.selectedConditionals.forEach((obj) => {
                    if (obj.identification === "deliveryCountry") {
                        obj.value = allDeliveryCountry
                    }
                })
            }

        })
        this.getWarehousesByStoreId();
        this.getPlatformByStore();

    },
    methods: {
        getPlatformByStore() {
            axios.post("/index.php?m=store&a=checkstorePlatCd", {id: this.storeId}).then((res) => {
                console.log("获取的平台", res.data.data);
                this.storePlatform = res.data.data;
            });
        },
        getWarehouseRecommendedDetailByStoreID() {
            axios.post("/index.php?m=store&a=CustomWarehouseConfigDetail", {id: this.id}).then((res) => {
                console.log("获取编辑的数据", res.data);
                // sku
                if (res.data.data.sku) {
                    let allSku = JSON.parse(res.data.data.sku);
                    this.allSku = allSku;

                    if (allSku.length != 0) {
                        this.skuCode = true;

                    }

                    let skuIds = [];
                    allSku.forEach((obj) => {
                        skuIds.push(obj.sku_id);
                    })
                    this.selectedConditionals.push({
                        name: this.initConditionals[0].name,
                        value: skuIds.toString().replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[0].identification
                    });

                } else {
                    this.skuCode = false;
                }

                // 前缀
                if (res.data.data.prefix) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[1].name,
                        value: res.data.data.prefix.replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[1].identification
                    });
                    if (res.data.data.prefix) {
                        this.skuPrefix = true;
                        console.log("前缀", res.data.data.prefix);

                    }
                    this.allSkuPrefix = res.data.data.prefix;

                } else {
                    console.log("前缀初始化");
                    this.skuPrefix = false;

                }

                // 后缀
                if (res.data.data.suffix) {
                    this.selectedConditionals.push({
                        name: this.initConditionals[2].name,
                        value: res.data.data.suffix.replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[2].identification
                    })
                    if (res.data.data.suffix) {
                        this.skuSuffix = true;
                        console.log("后缀", res.data.data.suffix);
                    }
                    this.allSkuSuffix = res.data.data.suffix;

                } else {
                    this.skuSuffix = false;

                }

                // 国家
                if (res.data.data.country) {
                    let AllCountry = JSON.parse(res.data.data.country);
                    this.allOrderDestination = AllCountry;

                    if (AllCountry.length != 0) {
                        this.country = true;

                    }
                    let names = [];
                    AllCountry.forEach((obj) => {
                        names.push(this.$lang(obj.zh_name));
                    })

                    this.selectedConditionals.push({
                        name: this.initConditionals[3].name,
                        value: names.toString().replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[3].identification
                    })
                } else {
                    this.country = false;

                }

                // 发货仓库
                if (res.data.data.assign_warehouse) {
                    let allDeliveryWarehouse = JSON.parse(res.data.data.assign_warehouse);
                    console.log("JSON序列化了", allDeliveryWarehouse);
                    this.allDeliveryWarehouse = allDeliveryWarehouse;

                    if (allDeliveryWarehouse.length != 0) {
                        this.deliveryWarehouse = true;

                    }
                    let names = [];
                    allDeliveryWarehouse.forEach((obj) => {
                        names.push(this.$lang(obj.CD_VAL));
                    })

                    this.selectedConditionals.push({
                        name: this.initConditionals[4].name,
                        value: names.toString().replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[4].identification
                    })
                } else {
                    this.deliveryWarehouse = false;

                }
                // 用户指定配送方式
                if (res.data.data.assign_shipping_method) {
                    let allModeDistribution = JSON.parse(res.data.data.assign_shipping_method);
                    console.log("JSON序列化了", allModeDistribution);

                    this.allModeDistribution = allModeDistribution;

                    if (allModeDistribution.length != 0) {
                        this.modeDistribution = true;

                    }
                    let names = [];
                    allModeDistribution.forEach((obj) => {
                        names.push(this.$lang(obj.display_name));
                    })

                    this.selectedConditionals.push({
                        name: this.initConditionals[5].name,
                        value: names.toString().replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[5].identification
                    })
                } else {
                    this.modeDistribution = false;

                }

                // 发货国家
                if (res.data.data.assign_country) {
                    let allDeliveryCountry = JSON.parse(res.data.data.assign_country);
                    this.allDeliveryCountry = allDeliveryCountry;
                    console.log("JSON序列化了", allDeliveryCountry);

                    if (allDeliveryCountry.length != 0) {
                        this.deliveryCountry = true;

                    }
                    let names = [];
                    allDeliveryCountry.forEach((obj) => {
                        names.push(this.$lang(obj.zh_name));
                    })

                    this.selectedConditionals.push({
                        name: this.initConditionals[6].name,
                        value: names.toString().replace(/,/g, this.$lang(" 或 ")),
                        identification: this.initConditionals[6].identification
                    })
                } else {
                    this.deliveryCountry = false;

                }


                this.warehouseRecommended.warehouse_code = res.data.data.warehouse_code;
                this.warehouseRecommended.logistics_company_code = res.data.data.logistics_company_code;
                this.warehouseRecommended.logistics_mode_id = res.data.data.logistics_mode_id;
                this.warehouseRecommended.face_order_code = res.data.data.face_order_code;

                this.warehouseRecommended.rule_name = res.data.data.rule_name;
                this.warehouseRecommended.remark = res.data.data.remark;
            });

        },
        getWarehousesByStoreId() {
            axios.post("/index.php?m=store&a=logisticsCompanyByStore", {store_id: this.storeId}).then((res) => {
                console.log("获取的仓库数据", res.data);
                this.allWarehouses = res.data.data.ware_house; //仓库数据
                this.allLogisticsCompanyByWarehouseCode = res.data.data.company; //物流公司数据
                let wareHouseArray = [];
                if (this.allLogisticsCompanyByWarehouseCode) {
                    this.allLogisticsCompanyByWarehouseCode.forEach((obj) => {
                        wareHouseArray = obj.WARE_HOUSE.split(',');
                        obj.WARE_HOUSE = wareHouseArray;
                    })
                }
                console.log("编辑---------仓库数据", this.allWarehouses);
                console.log("编辑---------物流公司", this.allLogisticsCompanyByWarehouseCode);
                this.getWarehouseRecommendedDetailByStoreID();

            })
        },
        onSave() {
            this.loading = true;
            if (this.allSku.length === 0 && this.skuCode) {
                this.$message.error(this.$lang("请选择商品sku"));
                this.loading = false;
                return;
            }
            if (this.allSkuPrefix === null && this.skuPrefix) {
                this.$message.error(this.$lang("请输入商品sku前缀"));
                this.loading = false;
                return;
            }
            if (this.allSkuSuffix === null && this.skuSuffix) {
                this.$message.error(this.$lang("请输入商品sku后缀"));
                this.loading = false;
                return;
            }
            if (this.allOrderDestination.length === 0 && this.country) {
                this.$message.error(this.$lang("请选择国家"));
                this.loading = false;
                return;
            }
            if (this.allDeliveryWarehouse.length === 0 && this.deliveryWarehouse) {
                this.$message.error(this.$lang('请选择发货仓库'));
                this.loading = false;
                return;
            }

            if (this.allModeDistribution.length === 0 && this.modeDistribution) {
                this.$message.error(this.$lang('请选择配送方式'));
                this.loading = false;
                return;
            }
            if (this.allDeliveryCountry.length === 0 && this.deliveryCountry) {
                this.$message.error(this.$lang('请选择发货国家'));
                this.loading = false;
                return;
            }
            if (!this.skuCode && !this.skuPrefix && !this.skuSuffix && !this.country && !this.modeDistribution && !this.deliveryCountry) {
                this.$message.error(this.$lang('请勾选已选条件（第一步）'));
                this.loading = false;
                return;
            }

            this.$refs["form"].validate((valid) => {
                if (valid) {
                    axios.post("/index.php?m=store&a=customWarehouseEdit", {
                        id: this.id,
                        store_id: this.storeId,
                        rule_name: this.warehouseRecommended.rule_name,
                        sku: this.allSku,
                        country: this.allOrderDestination,
                        prefix: this.allSkuPrefix ? this.allSkuPrefix.toString() : null,
                        suffix: this.allSkuSuffix ? this.allSkuSuffix.toString() : null,
                        assign_warehouse: this.allDeliveryWarehouse,
                        assign_shipping_method: this.allModeDistribution,
                        assign_country: this.allDeliveryCountry,
                        warehouse_code: this.warehouseRecommended.warehouse_code,
                        logistics_company_code: this.warehouseRecommended.logistics_company_code,
                        logistics_mode_id: this.warehouseRecommended.logistics_mode_id,
                        face_order_code: this.warehouseRecommended.face_order_code,
                        remark: this.warehouseRecommended.remark
                    }).then((res) => {
                        console.log("规则编辑成功", res.data);
                        this.$message.success(this.$lang("编辑成功"));
                        this.$emit("edit-saved", false);
                        this.loading = false;

                    }).catch(() => {
                        this.loading = false;
                    })
                } else {
                    this.loading = false;
                }
            })

        },
        onSelectSku(item) {
            console.log("内容",);
            if (item.identification === this.initConditionals[0].identification) {
                this.$emit('open-goods-sku-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-goods-sku-by-edit', this.allSku);
                    console.log("触发了事件");
                })
            } else if (item.identification === this.initConditionals[1].identification) {
                this.$emit('open-goods-sku-prefix-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-goods-sku-prefix-by-edit', this.allSkuPrefix);
                    console.log("触发了事件");
                })
            } else if (item.identification === this.initConditionals[2].identification) {
                this.$emit('open-goods-sku-suffix-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-goods-sku-suffix-by-edit', this.allSkuSuffix);
                    console.log("触发了事件");
                })
            } else if (item.identification === this.initConditionals[3].identification) {
                this.$emit('open-order-destination-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-order-destination-by-edit', this.allOrderDestination);
                    console.log("触发了事件");
                })
            } else if (item.identification === this.initConditionals[4].identification) {
                this.$emit('open-delivery-warehouse-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-delivery-warehouse-by-edit', this.allDeliveryWarehouse);
                    console.log("触发了事件");
                })
            } else if (item.identification === this.initConditionals[5].identification) {
                this.$emit('open-mode-distribution-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-mode-distribution-by-edit', this.allModeDistribution);
                    console.log("触发了事件");
                })
            } else if (item.identification === this.initConditionals[6].identification) {
                this.$emit('open-delivery-country-dialog-box-by-edit', true);
                this.$nextTick(() => {
                    this.$bus.$emit('get-delivery-country-by-edit', this.allDeliveryCountry);
                    console.log("触发了事件");
                })
            }
        },
        onClose() {
            this.$emit("edit-closed");
        }
    }
});