if (getCookie("think_language") !== "zh-cn") {
  ELEMENT.locale(ELEMENT.lang.en);
}
const vm = new Vue({
  name: "demandClaim",
  el: "#app",
  data() {
    return {
      searchParams: {
        paymentDate: "",
        dataSyncDate: "",
        company_code: "",
        open_bank: "",
        account_bank: "",
        opp_company_name: "",
        page: 0,
        pageSize: 10,
      },
      totalCount: 0,
      data: [],
      companyList: [],
      bankList: [], // 搜索的银行列表
      bankAccountList: [], // 搜索的银行账户列表
      accountTypes: [], // 账户类型
      loading: false,
    };
  },
  created() {
    this.getData();
    this.getOurCompany();
    this.getFinanceCommon();
  },
  methods: {
    getData() {
      const search = this.searchParams;
      const params = {
        transfer_time_start: search.paymentDate ? search.paymentDate[0] : "",
        transfer_time_end: search.paymentDate ? search.paymentDate[1] : "",
        create_time_start: search.dataSyncDate ? search.dataSyncDate[0] : "",
        create_time_end: search.dataSyncDate ? search.dataSyncDate[1] : "",
        company_code: search.company_code,
        open_bank: search.open_bank,
        account_bank: search.account_bank,
        opp_company_name: search.opp_company_name,
        page: search.page,
        page_size: search.pageSize,
      };
      this.loading = true;
      axios
        .post("/index.php?m=finance&a=waitingReceiveListData", params)
        .then((res) => {
          const response = res.data;
          this.loading = false;
          if (response.code === 2000) {
            this.totalCount = response.data.total;
            this.data = response.data.list || [];
          } else {
            this.$message.error(this.$lang(response.msg));
          }
        });
    },
    getFinanceCommon() {
      axios
        .post("/index.php?m=finance&a=commonData", {
          data: { query: { receiptType: "true" } },
        })
        .then((res) => {
          if (res.data.code === 2000) {
            this.accountTypes = res.data.data.receiptType;
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        });
    },
    getOurCompany() {
      axios
        .post("/index.php?g=common&m=index&a=get_cd", {
          cd_type: { our_company: "false" },
        })
        .then((res) => {
          const response = res.data;
          if (response.code === 2000) {
            this.companyList = response.data.our_company;
          } else {
            this.$message.error(this.$lang(response.msg));
          }
        });
    },
    handleSubmit(item) {
      if (!item.collection_type) {
        return this.$message.error(this.$lang("预分方向不能为空"));
      }
      const params = {
        turnover_id: item.id,
        collection_type: item.collection_type,
        our_remark: item.our_remark,
      };
      console.log('params',params);
      axios
        .post("/index.php?m=finance&a=referenceTurnoverDirection", params)
        .then((res) => {
          if (res.data.code === 2000) {
            this.$message.success(this.$lang(res.data.msg));
            setTimeout(() => {
              this.getData();
            }, 1500);
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        });
    },
    reset() {
      this.searchParams = {
        paymentDate: "",
        dataSyncDate: "",
        company_code: "",
        ourBankName: "",
        ourBankAccount: "",
        opp_company_name: ""
      };
      this.getData();
    },
    handleSearch() {
      this.getData();
    },
    handleSizeChange(size) {
      this.searchParams.pageSize = size;
      this.getData();
    },
    handleCurrentChange(current) {
      this.searchParams.page = current;
      this.getData();
    },
    RouteToDetail(row) {
      newTab(
        "index.php?m=finance&a=billing_detail&turnover_id=" + row.id,
        this.$lang("日记账详情")
      );
    },
  },
});
