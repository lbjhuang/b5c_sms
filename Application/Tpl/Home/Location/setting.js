if (getCookie('think_language') !== "zh-cn") {
  ELEMENT.locale(ELEMENT.lang.en)
}
var vm = new Vue({
  el: "#warehouse-setting",
  data() {
    return {
      queryForm: {
        warehouse: "", // 仓库
        logistics_company: "", // 物流公司
        logistics_mode: "", // 物流方式
      },
      tableData: {
        data: [],
        pages: {
          total: 0,
        },
      },
      tableLoading: false,
      addForm: {
        warehouse_code: "", // 仓库code
        logistics_company_code: "", // 物流公司code
        logistics_mode_id: "", // 物流方式id
        is_own_logistics_warehouse: null, // 0 非自有1自有
      },
      editForm: {
        warehouse_code: "", // 仓库code
        logistics_company_code: "", // 物流公司code
        logistics_mode_id: "", // 物流方式id
        is_own_logistics_warehouse: null, // 0 非自有1自有
        id: null,
      },
      rules: {
        warehouse_code: [
          {
            required: true,
            message: this.$lang("请选择仓库"),
            trigger: "change",
          },
        ],
        logistics_company_code: [
          {
            required: true,
            message: this.$lang("请选择物流公司"),
            trigger: "change",
          },
        ],
        logistics_mode_id: [
          {
            required: true,
            message: this.$lang("请选择物流方式"),
            trigger: "change",
          },
        ],
        is_own_logistics_warehouse: [
          {
            required: true,
            message: this.$lang("请选择自有物流"),
            trigger: "change",
          },
        ],
      },
      addDialogVisible: false,
      editDialogVisible: false,
      warehouseOptions: {},
      logisticsCompanyOptions: [],
      logisticsWayOptions: [],
      page: 1,
      pageSize: 10,
      unwatchAdd: null,
      unwatchEdit: null,
    };
  },
  created: function () {
    this.getTableData(false);
  },
  methods: {
    /** * 获取URL中查询字符串中相应参数的值 * * @param {string} name 参数名 * @return {(string | null)} 参数的值 */
    queryPost: function (url, param) {
      var headers = {
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "X-Requested-With": "XMLHttpRequest",
        },
      };
      return axios.post(url, Qs.stringify(param), headers);
    },
    // 获取表格数据
    getTableData: function (updateOrNot) {
      if (updateOrNot) {
        this.pageSize = 10;
        this.page = 1;
      }
      this.tableLoading = true;
      var postData = {
        search: this.queryForm,
        pages: {
          per_page: this.pageSize,
          current_page: this.page,
        },
      };
      axios
        .post("/index.php?m=location&a=getOwnLogisticsList", postData)
        .then((res) => {
          if (res.data.code == 200) {
            this.tableLoading = false;
            res.data.data.pages.total = parseInt(res.data.data.pages.total);
            this.tableData = res.data.data;
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        })
        .catch(function (err) {
          console.log(err);
        });
    },

    resetQueryForm: function () {
      this.queryForm = {
        warehouse: "", // 仓库
        logistics_company: "", // 物流公司
        logistics_mode: "", // 物流方式
      };
      this.page = 1;
      this.pageSize = 10;
      this.getTableData(true);
    },
    // 导出表格
    exportTable: function () {
      var data = Qs.stringify(this.queryForm);
      window.location.href = `index.php?m=location&a=exportOwmLogistics&${data}`;
      // console.log(data);
    },

    // 新增
    handleAdd: function () {
      this.addDialogVisible = true;
      this.getWarehouseData();
      this.getLogisticsCompany();
      this.unwatchAdd = vm.$watch(
        "addForm.logistics_company_code",
        function (newCode, oldCode) {
          this.getLogisticsWay();
        }
      );
    },
    handleEdit: function (row) {
      this.editDialogVisible = true;
      this.editForm.id = row.id;
      this.editForm.is_own_logistics_warehouse = row.is_own_logistics_warehouse;
      this.getWarehouseData(row.warehouse_code);
      this.getLogisticsCompany(
        row.logistics_company_code,
        row.logistics_mode_id
      );
    },
    handleDelete: function (row) {
      axios
        .post("/index.php?m=location&a=deleteOwmLogistics", { id: row.id })
        .then((res) => {
          if (res.data.code == 200) {
            this.$message.success(this.$lang("删除成功"));
            this.getTableData(true);
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        })
        .catch(function (err) {
          console.log(err);
        });
    },

    // 获取仓库数据
    getWarehouseData: function (warehouseCode) {
      var postData = {
        data: {
          query: {
            warehouses: "true",
          },
          type: "sorting",
        },
      };
      axios
        .post("/index.php?g=oms&m=CommonData&a=commonData", postData)
        .then((res) => {
          if (res.data.code == 2000) {
            this.warehouseOptions = res.data.data.warehouses;
            if (warehouseCode) {
              this.editForm.warehouse_code = warehouseCode;
            }
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        })
        .catch(function (err) {
          console.log(err);
        });
    },
    // 获取物流公司数据
    getLogisticsCompany: function (logisticsCompanyCode, logistics_mode_id) {
      var postData = {
        data: {
          query: {
            logisticsCompany: "true",
          },
          type: "sorting",
        },
      };
      axios
        .post("/index.php?g=oms&m=CommonData&a=commonData", postData)
        .then((res) => {
          if (res.data.code == 2000) {
            this.logisticsCompanyOptions = res.data.data.logisticsCompany;
            if (logisticsCompanyCode && logistics_mode_id) {
              this.editForm.logistics_company_code = logisticsCompanyCode;
              this.getLogisticsWay(true, logistics_mode_id);
              this.unwatchEdit = vm.$watch(
                "editForm.logistics_company_code",
                (newCode, oldCode) => {
                  this.getLogisticsWay(true);
                }
              );
            }
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        })
        .catch(function (err) {
          console.log(err);
        });
    },

    // 根据物流公司获取相应的物流方式
    getLogisticsWay: function (isEdit, logisticsModeCode) {
      var postData = {
        logistics_company_code: isEdit
          ? this.editForm.logistics_company_code
          : this.addForm.logistics_company_code,
      };
      axios
        .post("/index.php?g=common&m=index&a=getLogisticsWay", postData)
        .then((res) => {
          if (res.data.code == 200) {
            this.logisticsWayOptions = res.data.data;

            if (isEdit && logisticsModeCode) {
              this.editForm.logistics_mode_id = logisticsModeCode;
            } else if (isEdit) {
              this.editForm.logistics_mode_id = res.data.data
                ? res.data.data[0].id
                : "";
            } else {
              this.addForm.logistics_mode_id = res.data.data
                ? res.data.data[0].id
                : "";
            }
          } else {
            this.$message.error(this.$lang(res.data.msg));
          }
        })
        .catch(function (err) {
          console.log(err);
        });
    },

    // 确认新增
    saveAdd: function () {
      this.$refs["addForm"].validate((valid) => {
        if (valid) {
          this.addDialogVisible = false;
          axios
            .post("/index.php?m=location&a=saveOwmLogistics", this.addForm)
            .then((res) => {
              if (res.data.code == 200) {
                this.$message.success(this.$lang("新增成功"));
                this.getTableData(true);
              } else {
                this.$message.error(this.$lang(res.data.msg));
              }
            })
            .catch(function (err) {
              console.log(err);
            });
        }
      });
    },

    // 确认修改
    saveEdit: function () {
      this.$refs["editForm"].validate((valid) => {
        if (valid) {
          this.editDialogVisible = false;
          axios
            .post("/index.php?m=location&a=saveOwmLogistics", this.editForm)
            .then((res) => {
              if (res.data.code == 200) {
                this.$message.success(this.$lang("修改成功"));
                this.getTableData(true);
              } else {
                this.$message.error(this.$lang(res.data.msg));
              }
            })
            .catch(function (err) {
              console.log(err);
            });
        }
      });
    },

    resetForm(formName) {
      if (formName === "addForm") {
        this.unwatchAdd();
      } else if (formName === "editForm") {
        this.unwatchEdit();
      }
      this.warehouseOptions = {};
      this.logisticsCompanyOptions = [];
      this.logisticsWayOptions = [];
      this.$refs[formName].resetFields();
    },

    pageSizeChange: function (val) {
      this.pageSize = val;
      this.page = 1;
      this.getTableData();
    },
    currentPageChange: function (val) {
      this.page = val;
      this.getTableData();
    },
  },
});