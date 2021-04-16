const warehouseRecommendedGoodsSkuPrefixEdit = Vue.component("warehouseRecommendedGoodsSkuPrefixEdit", {
    template: `
<div class="goodsSkuPrefix">
  <div class="edit">
    <div class="edit__fields">
      <el-form @submit.native.prevent ref="form">
        <el-row :gutter="20" type="flex">
          <el-col :md="24">
            <el-form-item>
              <el-input
                  type="textarea"
                  :rows="8"
                  v-model="skuPrefix"
                  clearable
                  :placeholder="$lang('一行一个前缀，忽略前后空格')"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
    </div>
    <div class="actions">
       <el-button type="primary" @click="onSave">{{$lang('确定')}}</el-button>
       <el-button type="primary" @click="onClose">{{$lang('取消')}}</el-button>
    </div>
  </div>
</div>
    `,
    props: {//这里是组件可以接受的参数，也就是相当于面向原型写组件时的配置参数，用户可以传递不同参数，自己定义组件
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
            filter: {},
            skuPrefix: null
        }
    },
    created() {
        console.log("组件加载了", this.pmsHost);
        this.$bus.$on("get-goods-sku-prefix-by-edit", (values) => {
            console.log("接收到的值", values);
            if (values) {
                this.skuPrefix = values.toString().replace(/,/g, "\n");
            }
        })
    },
    beforeDestroy() {
        this.$bus.$off("get-goods-sku-prefix-by-edit")
    },
    methods: {
        onPageChange() {

        },
        onSave() {
            // console.log("内容", this.skuPrefix.split(/[\n]/));
            if (this.skuPrefix) {
                let skuPrefixArray = [];
                this.skuPrefix.split(/[\n]/).forEach((value) => {
                    skuPrefixArray.push(value.trim());
                })
                console.log("过滤后的内容", skuPrefixArray);
                this.$bus.$emit("edit-all-selected-sku-prefix", skuPrefixArray);
                this.$emit('closed-goods-sku-prefix-by-edit', false)

            } else {
                this.$bus.$emit("edit-all-selected-sku-prefix", this.skuPrefix);
                this.$emit('closed-goods-sku-prefix-by-edit', false)
            }

        },
        onClose() {
            this.$emit('closed-goods-sku-prefix-by-edit', false)
        },
    }
});