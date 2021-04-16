const warehouseRecommendedGoodsSkuEdit = Vue.component("warehouseRecommendedGoodsSkuEdit", {


    template: `
<div class="goodsSkuEdit">
  <div class="list">
    <div class="goods__sku">
      <div class="goods__sku-selected">
      <div class="title">
         <span>{{$lang('已选条件（第一步）')}}</span>
      </div>
     <div class="list__filters">
      <el-form @submit.native.prevent ref="form">
        <el-row :gutter="20" type="flex">
          <el-col :md="24">
            <el-form-item>
              <el-input
                  v-model="filter.sku_id"
                  clearable
                  :placeholder="$lang('sku编码')"
                  @keyup.enter.native="onSearch"
              />
            </el-form-item>
          </el-col>
          </el-row>
          <el-row :gutter="20" type="flex">
          <el-col :md="24">
            <el-form-item>
              <el-input
                  v-model="filter.product_name"
                  clearable
                  :placeholder="$lang('商品名称')"
                  @keyup.enter.native="onSearch"
              />
            </el-form-item>
          </el-col>
          </el-row>
          <el-row :gutter="20" type="flex">
            <el-col :md="24">
              <el-form-item>
                <el-button
                   type="primary"
                   class="button button--search"
                   :loading="searching"
                   @click="onSearch"
                   >{{$lang('查询')}}
                </el-button
                >
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
    </div>
      <div class="goods__sku-selected-content">
        <div class="list__data">
          <el-table
            :data="pagedGoodsSku.list"
            ref="selectedTable"
            border
            @selection-change="onSelectedChange"
            @select="onSelected"
            @select-all="onAllSelected"
          >
          <el-table-column
             type="selection"
             width="55">
          </el-table-column>
          <el-table-column
             fixed
             prop="sku_id"
             :resizable="false"
             :label="$lang('商品SKU')"
          >
            <template slot-scope="scope">
              <span style="cursor: pointer;color:#409EFF" @click="openGoodsListBySku">{{ scope.row.sku_id }}</span>
            </template>
          </el-table-column>
          <el-table-column
              prop="sort"
             :resizable="false"
             :label="$lang('商品名称')"
          >
          <template slot-scope="scope">
            <span>{{ scope.row.spu_name }}</span>
          </template>
        </el-table-column>
        <el-table-column
            prop="status"
            :resizable="false"
            :label="$lang('审核状态')"
        >
          <template slot-scope="scope">
            <span>{{ scope.row.review_states }}</span>
          </template>
        </el-table-column>
        <el-table-column
            prop="status"
            :resizable="false"
            :label="$lang('商品状态')"
        >
          <template slot-scope="scope">
            <span>{{ scope.row.sku_states}}</span>
          </template>
        </el-table-column>
      </el-table>
    </div>
        <div data-test="pagination" class="list__pagination">
        <el-pagination
           @current-change="onPageChange"
           @size-change="onSizeChange"
           :current-page="pagination.page"
           :page-size="pagination.pageSize"
           :page-sizes="[10,20]"
           layout="sizes,prev, pager, next, jumper"
           :total="pagedGoodsSku.total"
        />
    </div>
      </div>
    </div> 
      <div class="goods__sku-to-be-selected">
         <div class="title title__action">
            <span>{{$lang('已选条件（第二步）')}}</span>
            <el-button type="primary" @click="onDelete">{{$lang('删除已选')}}</el-button>
         </div>
         <div class="goods__sku-to-be-selected-content">
            <div class="list__data">
             <el-table
              ref="toBeSelectedTable"
              :data="allSelectedSku"
              border
              @selection-change="onToBeSelectedChange"
              @select="onToBeSelected"
              @select-all="onAllToBeSelected"
              >
             <el-table-column
               type="selection"
               width="55">
             </el-table-column>
             <el-table-column
               fixed
            prop="sku_id"
            :resizable="false"
            :label="$lang('商品SKU')"
        >
          <template slot-scope="scope">
            <span>{{ scope.row.sku_id }}</span>
          </template>
        </el-table-column>
        <el-table-column
            prop="sort"
            :resizable="false"
            :label="$lang('商品名称')"
        >
          <template slot-scope="scope">
            <span>{{ scope.row.goods_name }}</span>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
  </div>
</div>

    <div class="actions">
      <el-button type="primary" @click="onSave">{{$lang('确定')}}</el-button>
      <el-button type="primary" @click="onClose">{{$lang('取消')}}</el-button>
    </div>
  </div>
</div>
    `,
    props: {
        pmsHost: {
            type: String,
            default: null
        },
        isAddOrEditAction: {
            type: String,
            default: null
        }
    },
    data() {
        return {
            filter: {
                language: null,
                type: 3
            },
            pagination: {
                page: 1,
                pageSize: 10,
            },
            searching: false,
            pagedGoodsSku: {},
            allLanguage: {},
            currentLanguage: null,
            allSelectedSku: [],
            toBeAllSelectedSkuStatus: []
        }
    },
    created() {
        let cookie = document.cookie.split("; ");
        let cookieArray = [];
        let currentLanguage = null;

        cookie.forEach((value) => {
            cookieArray.push({name: value.split("=")[0], value: value.split("=")[1]});

        })
        cookieArray.forEach((obj) => {
            if (obj.name === "think_lang_list") {
                this.allLanguage = JSON.parse(unescape(obj.value).split("think:")[1]);
            }
            if (obj.name === "think_language") {
                currentLanguage = obj.value;
            }
        })
        Object.keys(this.allLanguage).forEach((key) => {
            if (this.allLanguage[key] === currentLanguage) {
                this.currentLanguage = key;
                this.filter.language = this.currentLanguage
                this.search();
            }
        })

        this.$bus.$on("get-goods-sku-by-edit", (values) => {
            console.log("接收到的值", values);
            this.allSelectedSku = values;
            this.$nextTick(() => {
                this.allSelectedSku.forEach((obj) => {
                    this.$refs.toBeSelectedTable.toggleRowSelection(obj);
                })


            })
        })
    },
    beforeDestroy() {
        this.$bus.$off("get-goods-sku-by-edit")
    },
    methods: {
        onPageChange(page) {
            console.log("页码", page);
            this.pagination.page = page;
            this.search();
        },
        onSizeChange(size) {
            this.pagination.pageSize = size;
            this.search();
        },
        onSave() {
            if (this.toBeAllSelectedSkuStatus.length === 0) {
                this.$bus.$emit("edit-all-selected-sku", this.toBeAllSelectedSkuStatus)
                this.$emit('closed-goods-sku-by-edit', false)

            } else {
                this.$bus.$emit("edit-all-selected-sku", this.toBeAllSelectedSkuStatus)
                this.$emit('closed-goods-sku-by-edit', false)
            }
        },
        onClose() {
            this.$emit('closed-goods-sku-by-edit', false)
        },
        onSelectedChange(item) {
        },
        openGoodsListBySku() {
            let dom = document.createElement('a');
            let _href = "/index.php?g=pms&m=new_pms&a=productList";
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('商品列表') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        onSelected(selection, row) {
            if (this.allSelectedSku.length != 0) {
                if (selection.length != 0) {
                    selection.forEach((obj) => {
                        if (obj.sku_id === row.sku_id) {// 能匹配上表明是勾选上的数据
                            this.allSelectedSku.forEach((selectedSku) => {
                                if (row.sku_id != selectedSku.sku_id) { //添加进新的sku
                                    // push之前要判断是否已经存在
                                    if (JSON.stringify(this.allSelectedSku).indexOf(row.sku_id) === -1) { //当前勾选的数据不存在时
                                        this.allSelectedSku.push({sku_id: row.sku_id, goods_name: row.spu_name});
                                        this.allSelectedSku.forEach((skuObject) => {
                                            if (skuObject.sku_id === row.sku_id) {
                                                this.$refs.toBeSelectedTable.toggleRowSelection(skuObject, true);
                                            }

                                        })
                                        console.log("1");
                                    }
                                } else if (row.sku_id === selectedSku.sku_id) { //能匹配上对应数据
                                    this.$refs.toBeSelectedTable.toggleRowSelection(selectedSku, true);
                                    console.log("2");
                                }
                            });

                        } else { //取消勾选
                            this.allSelectedSku.forEach((selectedSku) => {
                                if (row.sku_id === selectedSku.sku_id) {
                                    this.$refs.toBeSelectedTable.toggleRowSelection(selectedSku, false);
                                    console.log("3");

                                }
                            });
                        }
                    })
                } else { //当前页取消最后一个勾选框时
                    this.allSelectedSku.forEach((obj) => {
                        if (obj.sku_id === row.sku_id) {
                            this.$refs.toBeSelectedTable.toggleRowSelection(obj, false);

                        }
                    })
                    console.log("5");
                }
            } else {
                if (selection.length != 0) { //当勾选第一个sku时
                    selection.forEach((obj) => {
                        this.allSelectedSku.push({sku_id: obj.sku_id, goods_name: obj.spu_name});
                        this.$refs.toBeSelectedTable.toggleRowSelection(this.allSelectedSku[0], true);

                    })
                    console.log("4");
                }

            }


        },
        onAllSelected(selection) {
            console.log("全选", selection);
            if (selection.length != 0) { //全部勾选
                if (this.allSelectedSku.length != 0) {
                    selection.forEach(obj => {
                        this.allSelectedSku.forEach(selectedObject => {
                            // 全部勾选的数据里有任意部分数据已经存在
                            if (obj.sku_id === selectedObject.sku_id) {
                                this.$refs.toBeSelectedTable.toggleRowSelection(selectedObject, true);
                                console.log("1");
                            }
                            // 全部勾选的数据里部分不存在
                            if (obj.sku_id != selectedObject.sku_id) {
                                if (JSON.stringify(this.allSelectedSku).indexOf(obj.sku_id) === -1) {
                                    this.allSelectedSku.push({sku_id: obj.sku_id, goods_name: obj.spu_name});

                                    this.allSelectedSku.forEach((skuObject) => {
                                        if (skuObject.sku_id === obj.sku_id) {
                                            this.$refs.toBeSelectedTable.toggleRowSelection(skuObject, true);
                                        }
                                    })
                                    console.log("2");
                                }

                            }
                        })
                    })
                } else { //如果第二步表格数据为空时
                    selection.forEach((obj) => {
                        this.allSelectedSku.push({sku_id: obj.sku_id, goods_name: obj.spu_name});
                    })
                    this.allSelectedSku.forEach((obj) => {
                        this.$refs.toBeSelectedTable.toggleRowSelection(obj, true);
                    })

                    console.log("3");
                }
            } else { //全部取消
                this.pagedGoodsSku.list.forEach((obj) => {
                    this.allSelectedSku.forEach((selectedSku) => {
                        if (obj.sku_id === selectedSku.sku_id) {
                            this.$refs.toBeSelectedTable.toggleRowSelection(selectedSku, false);
                        }
                    })
                })
            }
        },
        onToBeSelectedChange(item) {
            this.toBeAllSelectedSkuStatus = [];
            this.toBeAllSelectedSkuStatus = item;
            console.log("被选中的状态数据", this.toBeAllSelectedSkuStatus);
        },
        onToBeSelected(selection, row) {
            if (this.pagedGoodsSku.list.length != 0) {
                if (selection.length != 0) {
                    selection.forEach((obj) => {
                        if (obj.sku_id === row.sku_id) {// 能匹配上表明是勾选上的数据
                            this.pagedGoodsSku.list.forEach((selectedSku) => {
                                if (row.sku_id === selectedSku.sku_id) { //能匹配上对应数据
                                    this.$refs.selectedTable.toggleRowSelection(selectedSku, true);
                                    console.log("2");
                                }
                            });
                        } else { //取消勾选
                            this.pagedGoodsSku.list.forEach((selectedSku) => {
                                if (row.sku_id === selectedSku.sku_id) {
                                    this.$refs.selectedTable.toggleRowSelection(selectedSku, false);
                                    console.log("3");
                                }
                            });
                        }
                    })
                } else { //当前页取消最后一个勾选框时
                    this.pagedGoodsSku.list.forEach((obj) => {
                        if (obj.sku_id === row.sku_id) {
                            this.$refs.selectedTable.toggleRowSelection(obj, false);

                        }
                    })
                    console.log("5");
                }
            }
        },
        onAllToBeSelected(selection) {
            console.log("全选", selection);
            if (selection.length != 0) { //全部勾选
                if (this.pagedGoodsSku.list.length != 0) {
                    selection.forEach(obj => {
                        this.pagedGoodsSku.list.forEach(selectedObject => {
                            // 全部勾选的数据里有任意部分数据已经存在
                            if (obj.sku_id === selectedObject.sku_id) {
                                this.$refs.selectedTable.toggleRowSelection(selectedObject, true);
                                console.log("1");
                            }
                        })
                    })
                }
            } else { //全部取消
                this.allSelectedSku.forEach((obj) => {
                    this.pagedGoodsSku.list.forEach((selectedSku) => {
                        if (obj.sku_id === selectedSku.sku_id) {
                            this.$refs.selectedTable.toggleRowSelection(selectedSku, false);
                        }
                    })
                })
            }
        },
        onDelete() {
            let allSelectedSku = _.cloneDeep(this.allSelectedSku);
            if (this.toBeAllSelectedSkuStatus.length !== 0) {
                this.$confirm(this.$lang('确认删除已选的sku吗？'), this.$lang('提示')).then(() => {
                    for (let i = 0; i < allSelectedSku.length; i++) {
                        for (let j = 0; j < this.toBeAllSelectedSkuStatus.length; j++) {
                            console.log("遍历了", i);
                            if (allSelectedSku[i].sku_id === this.toBeAllSelectedSkuStatus[j].sku_id) {
                                allSelectedSku.splice(i, 1);
                                console.log("被删除的下标", i);
                            }
                        }
                    }
                    this.allSelectedSku = allSelectedSku;

                    this.toBeAllSelectedSkuStatus.forEach(selectedSku => {
                        this.pagedGoodsSku.list.forEach(obj => {
                            if (obj.sku_id === selectedSku.sku_id) {
                                this.$refs.selectedTable.toggleRowSelection(obj);
                                console.log("删除了选中的");
                            }

                        })
                    })

                })
            } else {
                this.$message.error(this.$lang('请先勾选sku'))
            }

        },
        onSearch() {
            this.search();
        },
        search() {
            console.log("this.filter", this.filter);
            axios.get(`${this.pmsHost}/product/index`, {
                params: Object.assign({}, this.filter, this.pagination)
            }).then(res => {
                this.pagedGoodsSku = res.data.data;
                this.$nextTick(() => {
                    // 区分情况
                    /*
                    * 新增第一次打开sku选择框时且无sku数据时
                    *
                    * 新增在打开sku选择框后存在sku数据时
                    * */

                    this.pagedGoodsSku.list.forEach(obj => {
                        this.toBeAllSelectedSkuStatus.forEach(selectedSku => {
                            if (obj.sku_id === selectedSku.sku_id) {
                                this.$refs.selectedTable.toggleRowSelection(obj);
                                console.log("选中了");
                            }

                        })
                    })
                })
            });
        }
    }
});