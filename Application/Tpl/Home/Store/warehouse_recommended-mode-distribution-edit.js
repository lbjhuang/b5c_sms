const warehouseRecommendedModeDistributionEdit = Vue.component("warehouseRecommendedModeDistributionEdit", {
    template: `
<div class="mode-distribution">
  <div class="edit">
    <div class="edit__fields">
            <el-row :gutter="20" type="flex">
               <el-col :md="24">
                <div class="destination__selection-region">
                   <div class="selected__destination">
                      <div class="title">
                         <span>{{$lang('筛选配送方式(第一步)')}}</span>
                      </div>
                      <div class="selected__destination-content">
                          <div class="filters">
                                <el-form @submit.native.prevent ref="form">
                                   <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item  :label="$lang('用户指定配送方式：')" >
                                           <el-input
                                              v-model="filter.userDeliveryMode"
                                              clearable
                                              :placeholder="$lang('多个用英文逗号分开（单个支持模糊查询）')"
                                              @keyup.enter.native="onSearch"
                                            />
                                         </el-form-item>
                                      </el-col>
                                      </el-row>
                                    <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item  :label="$lang('物流方式服务代码：')" >
                                           <el-input
                                              v-model="filter.serviceCode"
                                              clearable
                                              :placeholder="$lang('多个用英文逗号分开（单个支持模糊查询）')"
                                              @keyup.enter.native="onSearch"
                                            />
                                         </el-form-item>
                                      </el-col>
                                      </el-row>
                                   <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item :label="$lang('erp对应的物流方式：')">
                                            <el-input
                                               v-model="filter.logisticsMode"
                                               clearable
                                               :placeholder="$lang('多个用英文逗号分开（单个支持模糊查询）')"
                                               @keyup.enter.native="onSearch"
                                               />
                                         </el-form-item>
                                      </el-col>
                                   </el-row>
                                  <el-row :gutter="20" type="flex">
                                     <el-col :md="24">
                                        <el-form-item>
                                        <div class="filter__actions">
                                           <el-button
                                           type="primary"
                                           class="button button--search"
                                           :loading="searching"
                                           @click="onSearch"
                                           >{{$lang('查询')}}
                                        </el-button
                                        >   
                                        <el-button
                                           type="primary"
                                           class="button button--add"
                                           :loading="searching"
                                           @click="onAdd"
                                           >{{$lang('添加勾选')}}
                                        </el-button
                                        >       
                                        </div>
                                        </el-form-item>
                                     </el-col>
                                   </el-row>
                                </el-form>
                          </div>
                          <div class="list__data">
                             <el-table
                                :data="pagedModeDistribution.data"
                                border
                                ref="table"
                                @selection-change="onSelectionChange"
                                >
                                 <el-table-column
                                    fixed
                                    type="selection"
                                    width="55">
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('用户指定配送方式')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.display_name }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('物流方式服务代码')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ $lang(scope.row.service_name) }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('erp对应的物流方式')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{$lang(scope.row.logistics_mode)}}</span>
                                  </template>
                                 </el-table-column>
                             </el-table>
                      </div>
                      </div>
                   </div>
                   <div class="to-be-selected__destination">
                      <div class="title title__action">
                         <span>{{$lang('已选配送方式(第二步)')}}</span>
                         <el-button type="primary" @click="onDelete">{{$lang('删除已选')}}</el-button>
                      </div>
                      <div class="to-be-selected__destination-content">
                           <div class="list__data">
                             <el-table
                                :data="allSelectedModeDistribution"
                                border
                                ref="toBeTable"
                                @selection-change="onToBeSelectedChange"
                                >
                                <el-table-column
                                    fixed
                                    type="selection"
                                    width="55">
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('用户指定配送方式')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ $lang(scope.row.display_name) }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('物流方式服务代码')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ $lang(scope.row.service_name) }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('erp对应的物流方式')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.logistics_mode }}</span>
                                  </template>
                                 </el-table-column>
                             </el-table>
                      </div>
                      </div>
                   </div>
                </div>
               </el-col>
            </el-row>
    </div>
    <div class="actions">
       <el-button type="primary" @click="onSave">{{$lang('确定')}}</el-button>
       <el-button type="primary" @click="onClose">{{$lang('取消')}}</el-button>
    </div>
  </div>
</div>
    `,
    props: {
        isAddOrEditAction: {
            type: String,
            default: null
        },
        storeID: {
            type: String,
            default: null
        }
    },
    data() {
        return {
            filter: {
                userDeliveryMode: null,
                logisticsMode: null
            },
            searching: false,
            pagedModeDistribution: {},
            pagedModeDistributionCopy: {},
            allSelectedModeDistributionIds: [],
            allSelectedModeDistribution: [],
            selectedTableRows: [],
            toBeSelectedModeDistribution: []
        }
    },
    created() {
        this.$bus.$on("get-mode-distribution-by-edit", (values) => {
            console.log("接收到的值", values);
            this.allSelectedModeDistribution = values;
            // this.$nextTick(() => {
            //     this.allSelectedModeDistribution.forEach((obj) => {
            //         this.$refs.toBeTable.toggleRowSelection(obj);
            //     })
            // })
        })
        this.search();
    },
    beforeDestroy() {
        this.$bus.$off("get-order-destination-by-edit")
    },
    methods: {//这里定义的组件的方法，利用$emit()进行父子组件通信，子组件通过点击事件告诉父组件触发一个自定义事件，$emit()方法第二个参数也可以用来传递数据
        onPageChange() {

        },
        onAdd() {
            let selectedTableRows = _.cloneDeep(this.selectedTableRows);
            let allSelectedModeDistribution = _.cloneDeep(this.allSelectedModeDistribution);
            if (allSelectedModeDistribution.length !== 0) {
                selectedTableRows.forEach((selectedRow) => {
                    console.log(JSON.stringify(allSelectedModeDistribution));
                    if (JSON.stringify(allSelectedModeDistribution).indexOf(selectedRow.service_name) === -1) { //匹配不上时
                        console.log("不匹配时", selectedRow);
                        allSelectedModeDistribution.push(selectedRow);
                    }
                });
                this.allSelectedModeDistribution = allSelectedModeDistribution;
            } else {
                this.allSelectedModeDistribution = selectedTableRows;
            }
        },
        onSave() {
            let allSelectedModeDistribution = _.cloneDeep(this.allSelectedModeDistribution);
            this.$bus.$emit("edit-all-mode-distribution", allSelectedModeDistribution);
            this.$emit('closed-mode-distribution-by-edit', false)
            console.log("执行了");
        },
        onClose() {
            this.$emit('closed-mode-distribution-by-edit', false)
        },
        onSelectionChange(rows) {
            this.selectedTableRows = rows;
        },
        onToBeSelectedChange(rows) {
            // console.log("被选中的数据", rows);
            this.toBeSelectedModeDistribution = rows;
        },
        onDelete() {
            let allSelectedModeDistribution = _.cloneDeep(this.allSelectedModeDistribution);
            if (this.toBeSelectedModeDistribution.length !== 0) {
                this.$confirm(this.$lang('确认删除已选的配送方式吗？'), this.$lang('提示')).then(() => {
                    for (let i = 0; i < allSelectedModeDistribution.length; i++) {
                        for (let j = 0; j < this.toBeSelectedModeDistribution.length; j++) {
                            if (allSelectedModeDistribution[i].service_name === this.toBeSelectedModeDistribution[j].service_name) {
                                allSelectedModeDistribution.splice(i, 1);
                                console.log("被删除的下标", i);
                            }
                        }
                    }
                    this.allSelectedModeDistribution = allSelectedModeDistribution;
                })
            } else {
                this.$message.error(this.$lang('请先勾选配送方式'))
            }
        },
        onSearch() {
            if (!this.filter.userDeliveryMode && !this.filter.logisticsMode && !this.filter.serviceCode) {
                let pagedModeDistributionCopy = _.cloneDeep(this.pagedModeDistributionCopy);

                this.pagedModeDistribution.data = pagedModeDistributionCopy.data;
            } else {
                let pagedModeDistributionCopy = _.cloneDeep(this.pagedModeDistributionCopy);
                console.log("全部数据", this.pagedModeDistributionCopy);
                let array = [];
                array = pagedModeDistributionCopy.data.filter((item) => {
                    if (this.filter.userDeliveryMode) {
                        for (let i = 0; i < this.filter.userDeliveryMode.split(",").length; i++) {
                            if (item.display_name.indexOf(this.filter.userDeliveryMode.split(",")[i]) >= 0) {
                                console.log("字符串", this.filter.userDeliveryMode.split(",")[i]);
                                return true;
                            }
                        }
                    }
                    if (this.filter.logisticsMode) {
                        for (let i = 0; i < this.filter.logisticsMode.split(",").length; i++) {
                            if (item.logistics_mode.indexOf(this.filter.logisticsMode.split(",")[i]) >= 0) {
                                return true;
                            }
                        }
                    }
                    if (this.filter.serviceCode) {
                        for (let i = 0; i < this.filter.serviceCode.split(",").length; i++) {
                            if (item.service_name.indexOf(this.filter.serviceCode.split(",")[i]) >= 0) {
                                return true;
                            }
                        }
                    }
                })
                this.pagedModeDistribution.data = array;
            }
        },
        search() {
            axios.post("/index.php?m=store&a=getShippingType", {
                    id: this.storeId
                }
            ).then(res => {
                this.pagedModeDistribution = res.data;
                this.pagedModeDistributionCopy = _.cloneDeep(res.data);
                console.log("获取的配送方式", this.pagedModeDistribution);
            });
        }
    }
});