const warehouseRecommendedDeliveryCountryEdit = Vue.component("warehouseRecommendedDeliveryCountryEdit", {
    template: `
<div class="delivery-country">
  <div class="edit">
    <div class="edit__fields">
            <el-row :gutter="20" type="flex">
               <el-col :md="24">
                <div class="destination__selection-region">
                   <div class="selected__destination">
                      <div class="title">
                         <span>{{$lang('已选条件（第一步）')}}</span>
                      </div>
                      <div class="selected__destination-content">
                          <div class="filters">
                                <el-form @submit.native.prevent ref="form">
                                   <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item>
                                           <el-input
                                              v-model="filter.en_name"
                                              clearable
                                              :placeholder="$lang('英文名称')"
                                              @keyup.enter.native="onSearch"
                                            />
                                         </el-form-item>
                                      </el-col>
                                      </el-row>
                                   <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item>
                                            <el-input
                                               v-model="filter.zh_name"
                                               clearable
                                               :placeholder="$lang('中文名称')"
                                               @keyup.enter.native="onSearch"
                                               />
                                         </el-form-item>
                                      </el-col>
                                   </el-row>
                                   <el-row :gutter="20" type="flex">
                                      <el-col :md="24">
                                         <el-form-item>
                                            <el-input
                                               v-model="filter.two_char"
                                               clearable
                                               :placeholder="$lang('国家二字码')"
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
                                :data="pagedDeliveryCountry.data"
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
                                   :label="$lang('英文名称')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.en_name }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('中文名称')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{$lang(scope.row.zh_name)}}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                  :label="$lang('国家二字码')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.two_char }}</span>
                                  </template>
                                 </el-table-column>
                             </el-table>
                      </div>
                      </div>
                   </div>
                   <div class="to-be-selected__destination">
                      <div class="title title__action">
                         <span>{{$lang('已选条件（第二步）')}}</span>
                         <el-button type="primary" @click="onDelete">{{$lang('删除已选')}}</el-button>
                      </div>
                      <div class="to-be-selected__destination-content">
                           <div class="list__data">
                             <el-table
                                :data="allSelectedDeliveryCountry"
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
                                   :label="$lang('英文名称')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.en_name }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('中文名称')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ $lang(scope.row.zh_name) }}</span>
                                  </template>
                                 </el-table-column>
                                 <el-table-column
                                   fixed
                                   prop="sku_id"
                                   :resizable="false"
                                   :label="$lang('国家二字码')"
                                 >
                                  <template slot-scope="scope">
                                      <span>{{ scope.row.two_char }}</span>
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
        }
    },
    data() {
        return {
            filter: {},
            searching: false,
            pagedDeliveryCountry: {},
            allSelectedDeliveryCountryIds: [],
            allSelectedDeliveryCountry: [],
            selectedTableRows: [],
            toBeSelectedDeliveryCountry: []
        }
    },
    created() {
        this.$bus.$on("get-delivery-country-by-edit", (values) => {
            console.log("接收到的值", values);
            this.allSelectedDeliveryCountry = values;
        })
        this.search();
    },
    beforeDestroy() {
        this.$bus.$off("get-delivery-country-by-edit")
    },
    methods: {
        onAdd() {
            let selectedTableRows = _.cloneDeep(this.selectedTableRows);
            let allSelectedDeliveryCountry = _.cloneDeep(this.allSelectedDeliveryCountry);
            if (allSelectedDeliveryCountry.length !== 0) {
                selectedTableRows.forEach((selectedRow) => {
                    console.log(JSON.stringify(allSelectedDeliveryCountry));
                    if (JSON.stringify(allSelectedDeliveryCountry).indexOf(selectedRow.id) === -1) { //匹配不上时
                        console.log("不匹配时", selectedRow);
                        allSelectedDeliveryCountry.push(selectedRow);
                    }
                });
                this.allSelectedDeliveryCountry = allSelectedDeliveryCountry;
            } else {
                this.allSelectedDeliveryCountry = selectedTableRows;
            }
        },
        onSave() {
            let allSelectedDeliveryCountry = _.cloneDeep(this.allSelectedDeliveryCountry);
            this.$bus.$emit("edit-all-delivery-country", allSelectedDeliveryCountry);
            this.$emit('closed-delivery-country-by-edit', false)

        },
        onClose() {
            this.$emit('closed-delivery-country-by-edit', false)
        },
        onSelectionChange(rows) {
            this.selectedTableRows = rows;
        },
        onToBeSelectedChange(rows) {
            // console.log("被选中的数据", rows);
            this.toBeSelectedDeliveryCountry = rows;
        },
        onDelete() {
            let allSelectedDeliveryCountry = _.cloneDeep(this.allSelectedDeliveryCountry);
            if (this.toBeSelectedDeliveryCountry.length !== 0) {
                this.$confirm(this.$lang('确认删除已选的发货国家吗？'), this.$lang('提示')).then(() => {
                    for (let i = 0; i < allSelectedDeliveryCountry.length; i++) {
                        for (let j = 0; j < this.toBeSelectedDeliveryCountry.length; j++) {
                            if (allSelectedDeliveryCountry[i].id === this.toBeSelectedDeliveryCountry[j].id) {
                                allSelectedDeliveryCountry.splice(i, 1);
                                console.log("被删除的下标", i);
                            }
                        }
                    }
                    this.allSelectedDeliveryCountry = allSelectedDeliveryCountry;
                })
            } else {
                this.$message.error(this.$lang('请先勾选发货国家'))
            }
        },
        onSearch() {
            this.search();
        },
        search() {
            axios.post("/index.php?m=store&a=country", this.filter
            ).then(res => {
                this.pagedDeliveryCountry = res.data;
                // console.log("获取的国家列表数据", this.pagedDeliveryCountry);
            });
        }
    }
});