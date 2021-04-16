const warehouseRecommendedGoodsSkuSuffixEdit = Vue.component("warehouseRecommendedGoodsSkuSuffixEdit", {
    template: `
<div class="goodsSkuSuffix">
  <div class="edit">
    <div class="edit__fields">
      <el-form @submit.native.prevent ref="form">
        <el-row :gutter="20" type="flex">
          <el-col :md="24">
            <el-form-item>
              <el-input
                  type="textarea"
                  :rows="8"
                  v-model="skuSuffix"
                  clearable
                  :placeholder="$lang('一行一个后缀，忽略前后空格')"
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
            skuSuffix: null
        }
    },
    created() {
        console.log("组件加载了", this.pmsHost);
        this.$bus.$on("get-goods-sku-suffix-by-edit", (values) => {
            console.log("接收到的值", values);
            if (values) {
                this.skuSuffix = values.toString().replace(/,/g, "\n");
            }
        })
    },
    beforeDestroy() {
        this.$bus.$off("get-goods-sku-suffix-by-edit")
    },
    methods: {
        onPageChange() {

        },
        onSave() {
            // console.log("内容", this.skuSuffix.split(/[\n]/));
            if (this.skuSuffix) {
                let skuSuffixArray = [];
                this.skuSuffix.split(/[\n]/).forEach((value) => {
                    skuSuffixArray.push(value.trim());
                })
                console.log("过滤后的内容", skuSuffixArray);
                this.$bus.$emit("edit-all-selected-sku-suffix", skuSuffixArray);
                this.$emit('closed-goods-sku-suffix-by-edit', false)
            } else {
                this.$bus.$emit("edit-all-selected-sku-suffix", this.skuSuffix);
                this.$emit('closed-goods-sku-suffix-by-edit', false)
            }

        },
        onClose() {
            this.$emit('closed-goods-sku-suffix-by-edit', false)
        }
    }
});