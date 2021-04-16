const warehouseRecommendedDeliveryWarehouse = Vue.component("warehouseRecommendedDeliveryWarehouse", {
    template: `
<div class="delivery-warehouse">
  <div class="edit">
    <div class="edit__fields">
            <el-row :gutter="20" type="flex">
               <el-col :md="24">
                <div class="destination__selection-region">
                   <div class="selected__destination">
                      <div class="title">
                         <span>{{$lang('筛选发货仓库(第一步)')}}</span>
                      </div>
                      <div class="selected__destination-content">
                          <div class="filters">
                                <el-form @submit.native.prevent ref="form">
                                   <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item  :label="$lang('用户指定发货仓库：')" >
                                           <el-input
                                              v-model="filter.deliveryWarehouse"
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
                                :data="pagedDeliveryWareHouse"
                                border
                                ref="table"
                                >
                                 <el-table-column
                                    fixed
                                    :label="$lang('单选')"
                                    width="55">
                                  <template slot-scope="scope">
                                       <el-checkbox v-model="scope.row.currentCheckbox" @change="onSelectionChange(scope.row)"></el-checkbox>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="CD_VAL"
                                   :resizable="false"
                                   :label="$lang('仓库名称')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.CD_VAL }}</span>
                                  </template>
                                 </el-table-column>
                             </el-table>
                      </div>
                      </div>
                   </div>
                   <div class="to-be-selected__destination">
                      <div class="title title__action">
                         <span>{{$lang('已选发货仓库(第二步)')}}</span>
                         <el-button type="primary" @click="onDelete">{{$lang('删除已选')}}</el-button>
                      </div>
                      <div class="to-be-selected__destination-content">
                           <div class="list__data">
                             <el-table
                                :data="allSelectedDeliveryWareHouse"
                                border
                                ref="toBeTable"
                                >
                                  <el-table-column
                                    fixed
                                    :label="$lang('单选')"
                                    width="55">
                                    <template slot-scope="scope">
                                       <el-checkbox v-model="scope.row.currentCheckbox" @change="onToBeSelectedChange(scope.row)"></el-checkbox>
                                    </template>
                                  </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('仓库名称')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ $lang(scope.row.CD_VAL) }}</span>
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
        storeId: {
            type: String,
            default: null
        }
    },
    data() {
        return {
            filter: {
                deliveryWarehouse: null
            },
            searching: false,
            pagedDeliveryWareHouse: [],
            pagedDeliveryWareHouseCopy: [],
            allSelectedDeliveryWareHouseIds: [],
            allSelectedDeliveryWareHouse: [],
            selectedTableRows: [],
            toBeSelectedDeliveryWareHouse: [],
            leftSelectedRadio: 1,
            rightSelectedRadio: false
        }
    },
    created() {
        this.$bus.$on("get-delivery-warehouse-by-add", (values) => {
            console.log("接收到的值", values);
            this.allSelectedDeliveryWareHouse = values;
        })
        this.search();
    },
    beforeDestroy() {
        this.$bus.$off("get-delivery-warehouse-by-add")
    },
    methods: {
        onPageChange() {

        },
        onAdd() {
            if (this.selectedTableRows.length !== 0) {
                this.allSelectedDeliveryWareHouse = _.cloneDeep(this.selectedTableRows);
                this.allSelectedDeliveryWareHouse[0].currentCheckbox = false;
            }
        },
        onSave() {
            let allSelectedDeliveryWareHouse = _.cloneDeep(this.allSelectedDeliveryWareHouse);
            this.$bus.$emit("add-all-delivery-warehouse", allSelectedDeliveryWareHouse);
            this.$emit('closed-delivery-warehouse', false)

        },
        onClose() {
            this.$emit('closed-delivery-warehouse', false)
        },
        onSelectionChange(rows) {
            console.log("当前行", rows);
            if (rows.currentCheckbox) {
                this.selectedTableRows = [rows];
                this.pagedDeliveryWareHouse.forEach((obj) => {
                    if (obj.CD !== rows.CD) {
                        this.$set(obj, "currentCheckbox", false)
                    }
                })
            } else {
                this.selectedTableRows = [];
            }
        },
        onToBeSelectedChange(rows) {
            console.log("被选中的数据", rows);
            this.toBeSelectedDeliveryWareHouse = [rows];
        },
        onDelete() {
            if (this.toBeSelectedDeliveryWareHouse.length !== 0 && this.toBeSelectedDeliveryWareHouse[0].currentCheckbox) {
                this.$confirm(this.$lang('确认删除已选的发货仓库吗？'), this.$lang('提示')).then(() => {
                    this.allSelectedDeliveryWareHouse.splice(0, 1);
                    this.toBeSelectedDeliveryWareHouse.splice(0, 1);
                })
            } else {
                this.$message.error(this.$lang('请先勾选仓库'))
            }
        },
        onSearch() {

            if (!this.filter.deliveryWarehouse) {
                let pagedDeliveryWareHouseCopy = _.cloneDeep(this.pagedDeliveryWareHouseCopy);
                console.log("全部数据", this.pagedDeliveryWareHouseCopy);

                this.pagedDeliveryWareHouse = pagedDeliveryWareHouseCopy;
            } else {
                let pagedDeliveryWareHouseCopy = _.cloneDeep(this.pagedDeliveryWareHouseCopy);
                let array = [];
                array = pagedDeliveryWareHouseCopy.filter((item) => {
                    if (this.filter.deliveryWarehouse) {

                        for (let i = 0; i < this.filter.deliveryWarehouse.split(",").length; i++) {
                            if (item.CD_VAL.indexOf(this.filter.deliveryWarehouse.split(",")[i]) >= 0) {
                                console.log("字符串", this.filter.deliveryWarehouse.split(",")[i]);
                                return true;
                            }
                        }
                    }

                })
                this.pagedDeliveryWareHouse = array;
            }
        },
        search() {
            axios.post("/index.php?m=store&a=logisticsCompanyByStore", {store_id: this.storeId}).then(res => {
                this.pagedDeliveryWareHouse = res.data.data.ware_house; //仓库数据
                this.pagedDeliveryWareHouseCopy = _.cloneDeep(res.data.data.ware_house);
                this.pagedDeliveryWareHouse.forEach((obj) => {
                    this.$set(obj, "currentCheckbox", false)
                })
                console.log("获取的仓库列表数据", this.pagedDeliveryWareHouse);
            });
        }
    }
});