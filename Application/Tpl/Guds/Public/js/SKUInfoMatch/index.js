webpackJsonp([8],{

/***/ 110:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(217)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(152),
  /* template */
  __webpack_require__(226),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 144:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(110);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__app__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_jquery__ = __webpack_require__(0);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_jquery___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2_jquery__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__assets_reset_css__ = __webpack_require__(4);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__assets_reset_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_3__assets_reset_css__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_element_ui__ = __webpack_require__(5);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_element_ui___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_4_element_ui__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_element_ui_lib_theme_default_index_css__ = __webpack_require__(3);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_element_ui_lib_theme_default_index_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_5_element_ui_lib_theme_default_index_css__);







__WEBPACK_IMPORTED_MODULE_0_vue__["default"].use(__WEBPACK_IMPORTED_MODULE_4_element_ui___default.a);

new __WEBPACK_IMPORTED_MODULE_0_vue__["default"]({
  components: { App: __WEBPACK_IMPORTED_MODULE_1__app___default.a }
}).$mount('app');

/***/ }),

/***/ 152:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify__ = __webpack_require__(10);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__api_index_js__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__ = __webpack_require__(8);





/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      addState: true,
      firstEdit: true,
      querySKUID: "",
      thirdSKU: "",

      addSkuBox: false,

      searchConditionList: [{
        value: "skuId",
        label: "SKU ID"
      }, {
        value: "gudsName",
        label: "商品名称"
      }, {
        value: "thirdSku",
        label: "第三方SKU ID"
      }, {
        value: "storeName",
        label: "店铺名称"
      }, {
        value: "platform",
        label: "平台名称"
      }],

      searchConditionValue: "SKU ID",

      searchConditionCode: "skuId",

      searchKeyword: "",

      tableData: [],

      skuInfoList: [],

      currentPage: 1,

      totalNum: 0,

      pageNum: 20,

      conditionTemp: "",

      PlatformValue: "",

      PlatformID: "",

      PlatformList: [],

      goodsName: "",

      storeValue: "",

      storeId: "",

      storeList: [],

      currentRow: ""
    };
  },
  created: function created() {
    this.getSkuBindList();
    this.getPlatformList();
  },

  methods: {
    resetData: function resetData() {
      this.querySKUID = "";
      this.goodsName = "";
      this.thirdSKU = "";
      this.storeValue = "";
      this.storeId = "";
      this.PlatformValue = "";
      this.PlatformID = "";
    },
    selectSearch: function selectSearch() {

      this.searchConditionCode = event.currentTarget.getAttribute("data-id");
    },
    doSearch: function doSearch() {
      var vm = this;
      var condition = vm.searchConditionCode;
      var keyword = $.trim(vm.searchKeyword);
      if (vm.currentPage == "1") {
        console.log("do 1");
        $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].serachSkuBind() + "&" + condition + "=" + keyword, function (json, textStatus) {
          if (json.code == 200) {
            vm.totalNum = +json.data.total;
            vm.tableData = json.data.list;
            if (condition && keyword != undefined) {
              vm.conditionTemp = "&" + condition + "=" + keyword;
            }
          } else {
            vm.$message({
              type: "error",
              message: json.msg
            });
          }
        });
      } else {
        if (condition && keyword != undefined) {
          vm.conditionTemp = "&" + condition + "=" + keyword;
          vm.currentPage = 1;
        }
      }
    },
    handleSizeChange: function handleSizeChange() {},
    handleCurrentChange: function handleCurrentChange(val) {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].serachSkuBind() + vm.conditionTemp + "&page=" + val, function (json, textStatus) {
        if (json.code == 200) {
          vm.tableData = json.data.list;
          vm.totalNum = +json.data.total;
        }
      });
    },
    getSkuBindList: function getSkuBindList() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].serachSkuBind(), function (json, textStatus) {
        if (json.code == 200) {
          vm.tableData = json.data.list;
          vm.totalNum = +json.data.total;
        }
      });
    },
    handleCurrentChange1: function handleCurrentChange1(val) {
      this.currentRow = val;
      console.log(this.currentRow);
    },
    doEdit: function doEdit() {
      var vm = this;
      vm.firstEdit = true;
      if (!vm.currentRow) {
        vm.$message({
          type: "error",
          message: "请先选择一行!"
        });
      } else {
        __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].showOverlay();
        vm.addSkuBox = true;
        vm.addState = false;
        vm.querySKUID = vm.currentRow.skuId;
        vm.goodsName = vm.currentRow.gudsName;
        vm.PlatformID = vm.currentRow.platformCode;
        vm.thirdSKU = vm.currentRow.thirdSkuId;
        vm.PlatformValue = vm.currentRow.platformCode;
        vm.storeValue = vm.currentRow.storeId;
      }
    },
    doDelete: function doDelete() {
      var vm = this;
      this.$confirm("此操作将永久删除该信息, 是否继续?", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }).then(function () {
        if (vm.currentRow.id) {
          $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].deleteThrSku(vm.currentRow.id), function (json, textStatus) {
            if (json.code == 200) {
              vm.$message({
                type: "success",
                message: "删除成功!"
              });
              vm.currentRow = "";
              vm.getSkuBindList();
            }
          });
        } else {
          vm.$message({
            type: "error",
            message: "请先选择一行!"
          });
        }
      }).catch(function () {
        vm.$message({
          type: "info",
          message: "已取消删除"
        });
      });
    },
    doDownload: function doDownload() {
      var vm = this;
      var url = __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].downloadTemp();
      window.open(url);
    },
    doImport: function doImport() {
      var vm = this;

      var postData = new FormData();

      postData.append("file", $(".import-btn").find("input")[0]["files"][0]);
      console.log($(".import-btn").find("input"));
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].importData(),
        type: "POST",
        dataType: "JSON",
        contentType: false,
        processData: false,
        data: postData,
        cache: false
      }).done(function (data) {
        if (data.code == 200 && data.data) {
          var d = data.data;
          console.log(d);

          var newDatas = [];
          var h = vm.$createElement;
          for (var i in d) {
            newDatas.push(h("p", { style: "line-height:2" }, d[i]));
          }
          vm.$msgbox({
            title: "消息",
            message: h("div", null, newDatas),
            showCancelButton: true,
            confirmButtonText: "确定",
            cancelButtonText: "取消"
          }).then(function (action) {
            vm.$message({
              type: "info",
              message: "action: " + action
            });
          });
        } else {
          vm.$message({
            type: "error",
            message: data.msg
          });
        }
      }).fail(function () {
        vm.$message({
          type: "error",
          message: "error"
        });
      }).always(function () {
        console.log("complete");
      });
    },
    doAdd: function doAdd() {
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].showOverlay();
      this.addState = true;
      this.resetData();
      this.addSkuBox = true;
    },
    cancelAdd: function cancelAdd() {
      this.addSkuBox = false;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
    },
    isSelect: function isSelect(val) {
      if (val) {
        this.firstEdit = false;
      }
    },
    getPlatformList: function getPlatformList() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getPlatformList(), function (json, textStatus) {
        if (json.code == 200) {
          vm.PlatformList = json.data;
        }
      });
    },
    selectPlatform: function selectPlatform() {
      var vm = this;
      var temp = vm.PlatformID;
      console.log(event);

      vm.PlatformID = event.currentTarget.getAttribute("data-id");
      if (!vm.PlatformID) {
        vm.PlatformID = temp;
      }
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getStoreList(vm.PlatformID), function (json, textStatus) {
        if (json.code == 200) {
          vm.storeList = json.data;
          console.log();
          if (!vm.firstEdit) {
            vm.storeValue = '';
          }
        }
      });
    },
    selectStore: function selectStore() {
      this.storeId = this.storeValue;
    },
    querySKU: function querySKU() {
      var vm = this;
      var querySKUID = vm.querySKUID;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getSkuData(querySKUID), function (json, textStatus) {
        if (json.code == 200) {
          vm.goodsName = json.data[0].gudsName;
          console.log(vm.goodsName);
        } else {
          vm.$message({
            type: "error",
            message: json.msg
          });
        }
      });
    },
    changeSKUID: function changeSKUID() {
      this.goodsName = "";
    },
    saveAdd: function saveAdd() {
      var vm = this;
      if (vm.addState) {
        if (vm.querySKUID && vm.thirdSKU && vm.PlatformID && vm.storeId && vm.goodsName) {
          $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].createBind(vm.querySKUID, vm.thirdSKU, vm.PlatformID, vm.storeId), function (json, textStatus) {
            if (json.code == 200) {
              vm.$message({
                type: "success",
                message: "添加成功"
              });
              vm.addSkuBox = false;
              __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
            } else {
              vm.$message({
                type: "error",
                message: json.msg
              });
            }
          });
        } else {
          vm.$message({
            type: "error",
            message: "请填写完整信息"
          });
        }
      } else {
        var postData = {
          thirdSkuId: vm.thirdSKU,
          storeId: vm.storeValue,
          platformCode: vm.PlatformValue
        };
        console.log(postData);
        $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].editThrSku(vm.currentRow.id),
          type: "POST",
          dataType: "JSON",
          contentType: false,
          processData: false,
          data: __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default()(postData),
          cache: false
        }).done(function (data) {
          if (data.code == 200) {
            vm.$message({
              type: "success",
              message: "修改成功"
            });
            vm.addSkuBox = false;
            __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
            vm.getSkuBindList();
          } else {
            vm.$message({
              type: "error",
              message: data.msg
            });
          }
        }).fail(function () {
          vm.$message({
            type: "error",
            message: "error"
          });
        }).always(function () {
          console.log("complete");
        });
      }
    }
  }
});
/* WEBPACK VAR INJECTION */}.call(__webpack_exports__, __webpack_require__(0)))

/***/ }),

/***/ 2:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var _baseUrl = "";
if (window.location.host == "localhost:8801") {
  _baseUrl = "http://erp.stage.com/index.php";
} else {
  _baseUrl = "//" + window.location.host + '/index.php';
}

/* harmony default export */ __webpack_exports__["a"] = ({
  getBrand: function getBrand() {
    return _baseUrl + '?g=guds&m=guds&a=addPage';
  },
  getCateLev01: function getCateLev01() {
    return _baseUrl + '?g=guds&m=category&a=getCategoryByLevel&level=1';
  },
  getCateLev: function getCateLev(lev, page, rows) {
    return _baseUrl + '?g=guds&m=category&a=getCategoryByLevel&level=' + lev + '&page=' + page + '&rows' + rows;
  },
  getSubCate: function getSubCate(cateCode, lev) {
    return _baseUrl + '?g=guds&m=category&a=getSubcategory&catCode=' + cateCode + '&catLevel=' + lev;
  },
  getOptionList: function getOptionList(gudsId, sellerId) {
    return _baseUrl + '?g=guds&m=gudsOptions&a=getOptionList&gudsId=' + gudsId + '&sellerId=' + sellerId;
  },
  getBasicOptions: function getBasicOptions() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=getBasicOptions';
  },
  getOptionValues: function getOptionValues(selectedOptId) {
    return _baseUrl + '?g=guds&m=guds_options&a=getOptionValues&selectedOptId=' + selectedOptId;
  },
  getOptionGroup: function getOptionGroup() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=getOptionGroup';
  },
  searchOptionValue: function searchOptionValue(optNameCode, keyword) {
    return _baseUrl + '?g=guds&m=gudsOptions&a=searchOptionValue&optNameCode=' + optNameCode + '&keyword=' + keyword;
  },
  createSku: function createSku() {
    return _baseUrl + '?g=guds&m=guds_options&a=create';
  },
  updatePic: function updatePic() {
    return _baseUrl + '?g=guds&m=guds&a=uploadGudsImage';
  },
  addNewOptionValue: function addNewOptionValue() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=addNewOptionValue';
  },
  createGoodsBasic: function createGoodsBasic() {
    return _baseUrl + '?g=guds&m=guds&a=doAdd';
  },
  createGoods: function createGoods() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=create';
  },
  showBrandList: function showBrandList() {
    return _baseUrl + '?g=guds&m=brand&a=showBrandList&isAjax=1';
  },
  showGoodsList: function showGoodsList() {
    return _baseUrl + '?g=guds&m=guds&a=showList';
  },
  getType: function getType(id, level) {
    return _baseUrl + '?g=guds&m=B5cai&a=getB5caiListByLevel&pId=' + id + '&levId=' + level;
  },
  showGudsBasic: function showGudsBasic(gudsId) {
    return _baseUrl + '?g=guds&m=guds&a=showGuds&gudsId=' + gudsId;
  },
  showGudsSKU: function showGudsSKU(mainId, brandId, gudsId) {
    return _baseUrl + '?g=guds&m=gudsOptions&a=getOptionList&mainGudsId=' + mainId + '&sellerId=' + brandId + '&gudsId=' + gudsId;
  },
  updateGudsDataOld: function updateGudsDataOld() {
    return _baseUrl + '?g=guds&m=guds&a=updateGudsData';
  },
  updateGudsData: function updateGudsData() {
    return _baseUrl + '?g=guds&m=guds&a=updateGudsMultiple';
  },
  modifySKU: function modifySKU() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=modify';
  },
  doCheckGuds: function doCheckGuds() {
    return _baseUrl + '?g=guds&m=guds&a=doChkGuds';
  },
  checkExist: function checkExist() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=checkExist';
  },
  changeSKUState: function changeSKUState(mainGudsId, optionId, enable) {
    return _baseUrl + '?g=guds&m=gudsOptions&a=setEnable&mainGudsId=' + mainGudsId + '&optionId=' + optionId + '&enable=' + enable;
  },
  getBrands: function getBrands() {
    return _baseUrl + '?g=guds&m=brand&a=getBrandList';
  },
  getBrandList: function getBrandList() {
    return _baseUrl + '?g=guds&m=brand&a=showList';
  },
  isCompanyNameExit: function isCompanyNameExit(companyName) {
    return _baseUrl + '?g=guds&m=brandSllr&a=isCompanyNameExit&companyName=' + companyName;
  },
  isSllrIdExist: function isSllrIdExist(brandId) {
    return _baseUrl + '?g=guds&m=brandSllr&a=isSllrIdExist&sllrId=' + brandId;
  },
  showBrandDetail: function showBrandDetail(brandId) {
    return _baseUrl + '?g=guds&m=brand&a=showBrandData&brandId=' + brandId;
  },
  getAddBrandInfo: function getAddBrandInfo() {
    return _baseUrl + '?g=guds&m=brand&a=addPage';
  },
  doAddBrand: function doAddBrand() {
    return _baseUrl + '?g=guds&m=brand&a=doAdd';
  },
  downloadData: function downloadData() {
    return _baseUrl + '?g=guds&m=brand&a=downLoadBrandData';
  },
  modifyData: function modifyData() {
    return _baseUrl + '?g=guds&m=brand&a=updateBrandData';
  },
  getCatList: function getCatList() {
    return _baseUrl + '?g=guds&m=category&a=getList';
  },
  searchCat: function searchCat(catCode, level, page) {
    return _baseUrl + '?g=guds&m=category&a=search&catCode=' + catCode + '&levels=' + level + '&page=' + page;
  },
  creatCat: function creatCat(catLevel, catName, cnName, levelFirst, levelSecond) {
    return _baseUrl + '?g=guds&m=category&a=create&levelFirst=' + levelFirst + '&levelSecond=' + levelSecond + '&catLevel=' + catLevel + '&catName=' + catName + '&cnName=' + cnName;
  },
  getCatById: function getCatById(id) {
    return _baseUrl + '?g=guds&m=category&a=getCategoryById&id=' + id;
  },
  updataCat: function updataCat(catCode, id, catName, catCnName, isVisible) {
    return _baseUrl + '?g=guds&m=category&a=update&catCode=' + catCode + '&id=' + id + '&catName=' + catName + '&catCnName=' + catCnName + '&isVisible=' + isVisible;
  },
  getCategoryList: function getCategoryList(sellerId) {
    return _baseUrl + '?g=guds&m=brandCategory&a=getCategoryList&sellerId=' + sellerId;
  },
  getBindRelation: function getBindRelation(sellerId, parCode) {
    return _baseUrl + '?g=guds&m=brandCategory&a=getBindRelation&sellerId=' + sellerId + '&parCode=' + parCode;
  },
  createBECat: function createBECat() {
    return _baseUrl + '?g=guds&m=brandCategory&a=create';
  },
  delBECate: function delBECate(sellerId, selected) {
    return _baseUrl + '?g=guds&m=brandCategory&a=delete&sellerId=' + sellerId + '&selected=' + selected;
  },
  updateBECat: function updateBECat() {
    return _baseUrl + '?g=guds&m=brandCategory&a=update';
  },
  serachSkuBind: function serachSkuBind() {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=search';
  },
  batchCreate: function batchCreate() {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=batchCreate';
  },
  getPlatformList: function getPlatformList() {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=getPlatformList';
  },
  getStoreList: function getStoreList(platformCode) {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=getStoreList&platformCode=' + platformCode;
  },
  getSkuData: function getSkuData(skuId) {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=getSkuData&skuId=' + skuId;
  },
  createBind: function createBind(skuId, thirdSkuId, platformCode, storeId) {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=create&skuId=' + skuId + '&thirdSkuId=' + thirdSkuId + '&platformCode=' + platformCode + '&storeId=' + storeId;
  },
  importData: function importData() {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=import';
  },
  downloadTemp: function downloadTemp() {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=download&name=sku-bind.xlsx';
  },
  getOptionPrice: function getOptionPrice(mainGudsId) {
    return _baseUrl + '?g=guds&m=gudsOptions&a=getOptionPrice&mainGudsId=' + mainGudsId;
  },
  addPrice: function addPrice() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=addPrice';
  },
  updatePrice: function updatePrice() {
    return _baseUrl + '?g=guds&m=gudsOptions&a=updatePrice';
  },
  delPrice: function delPrice(id, mainGudsId, optionId) {
    return _baseUrl + '?g=guds&m=gudsOptions&a=deletePrice&id=' + id + '&mainGudsId=' + mainGudsId + '&optionId=' + optionId;
  },
  deleteThrSku: function deleteThrSku(id) {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=deleteBind&id=' + id;
  },
  editThrSku: function editThrSku(id) {
    return _baseUrl + '?g=guds&m=gudsSkuBind&a=editRelation&id=' + id;
  }
});

/***/ }),

/***/ 217:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 226:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "sku-info-match list-common"
  }, [_c('div', {
    attrs: {
      "id": "mask"
    }
  }), _vm._v(" "), _c('div', {
    staticClass: "function-content"
  }, [_c('el-select', {
    staticClass: "search-select",
    attrs: {
      "placeholder": "搜索条件"
    },
    on: {
      "change": function($event) {
        _vm.selectSearch($event)
      }
    },
    model: {
      value: (_vm.searchConditionValue),
      callback: function($$v) {
        _vm.searchConditionValue = $$v
      },
      expression: "searchConditionValue"
    }
  }, _vm._l((_vm.searchConditionList), function(item) {
    return _c('el-option', {
      key: item.value,
      attrs: {
        "label": item.label,
        "value": item.value,
        "data-id": item.value
      }
    })
  })), _vm._v(" "), _c('el-input', {
    staticClass: "search-input",
    attrs: {
      "placeholder": "搜索关键字"
    },
    model: {
      value: (_vm.searchKeyword),
      callback: function($$v) {
        _vm.searchKeyword = $$v
      },
      expression: "searchKeyword"
    }
  }), _vm._v(" "), _c('el-button', {
    staticClass: "search-btn",
    attrs: {
      "type": "primary",
      "icon": "search"
    },
    on: {
      "click": _vm.doSearch
    }
  }, [_vm._v(" 搜索")]), _vm._v(" "), _c('span', {
    staticClass: "btns-content"
  }, [_c('el-button', {
    staticClass: " download-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doEdit
    }
  }, [_vm._v(" 编辑")]), _vm._v(" "), _c('el-button', {
    staticClass: "delete-btn download-btn",
    staticStyle: {
      "background-color": "#ff4949 !important"
    },
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doDelete
    }
  }, [_vm._v(" 删除")]), _vm._v(" "), _c('el-button', {
    staticClass: "download-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doDownload
    }
  }, [_vm._v(" 下载模板")]), _vm._v(" "), _c('form', {
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    staticClass: "import-btn",
    attrs: {
      "href": "javascript:;"
    }
  }, [_vm._v("导入"), _c('input', {
    attrs: {
      "type": "file"
    },
    on: {
      "change": function($event) {
        _vm.doImport($event)
      }
    }
  })])]), _vm._v(" "), _c('el-button', {
    staticClass: "add-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doAdd
    }
  }, [_vm._v(" 新增")])], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "parting-line"
  }), _vm._v(" "), _c('div', {
    staticClass: "query-result-list"
  }, [_c('h3', [_vm._v("搜索结果:共\n      "), _c('span', [_vm._v(_vm._s(_vm.totalNum))]), _vm._v("条记录")]), _vm._v(" "), _c('el-table', {
    staticStyle: {
      "width": "100%",
      "min-width": "1000px"
    },
    attrs: {
      "data": _vm.tableData,
      "stripe": "",
      "highlight-current-row": ""
    },
    on: {
      "current-change": _vm.handleCurrentChange1
    }
  }, [_c('el-table-column', {
    attrs: {
      "type": "index",
      "width": "80"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "skuId",
      "label": "SKU ID"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "gudsName",
      "label": "商品名称"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "thirdSkuId",
      "label": "第三方SKU ID"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "storeName",
      "label": "店铺名称"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "platformName",
      "label": "平台名称"
    }
  })], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "pagination-block"
  }, [_c('el-pagination', {
    attrs: {
      "current-page": _vm.currentPage,
      "page-size": _vm.pageNum,
      "layout": "prev, pager, next, jumper",
      "total": _vm.totalNum
    },
    on: {
      "size-change": _vm.handleSizeChange,
      "current-change": _vm.handleCurrentChange,
      "update:currentPage": function($event) {
        _vm.currentPage = $event
      }
    }
  })], 1), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.addSkuBox),
      expression: "addSkuBox"
    }],
    staticClass: "add-sku-connect"
  }, [_c('h3', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.addState),
      expression: "addState"
    }]
  }, [_vm._v("新增SKU关联")]), _vm._v(" "), _c('h3', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.addState),
      expression: "!addState"
    }]
  }, [_vm._v("修改SKU关联")]), _vm._v(" "), _c('div', {
    staticClass: "main-content"
  }, [_c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("SKU ID")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": "",
      "disabled": !_vm.addState
    },
    on: {
      "change": _vm.changeSKUID
    },
    model: {
      value: (_vm.querySKUID),
      callback: function($$v) {
        _vm.querySKUID = $$v
      },
      expression: "querySKUID"
    }
  }), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.addState),
      expression: "addState"
    }],
    staticClass: "query-sku-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.querySKU
    }
  }, [_vm._v(" 查询")])], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("商品名称")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": "",
      "disabled": ""
    },
    model: {
      value: (_vm.goodsName),
      callback: function($$v) {
        _vm.goodsName = $$v
      },
      expression: "goodsName"
    }
  })], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("第三方SKU ID")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.thirdSKU),
      callback: function($$v) {
        _vm.thirdSKU = $$v
      },
      expression: "thirdSKU"
    }
  })], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("平台名称")]), _vm._v(" "), _c('el-select', {
    staticClass: "search-select",
    attrs: {
      "placeholder": "选择平台"
    },
    on: {
      "change": function($event) {
        _vm.selectPlatform($event)
      },
      "visible-change": _vm.isSelect
    },
    model: {
      value: (_vm.PlatformValue),
      callback: function($$v) {
        _vm.PlatformValue = $$v
      },
      expression: "PlatformValue"
    }
  }, _vm._l((_vm.PlatformList), function(item) {
    return _c('el-option', {
      key: item.code,
      attrs: {
        "label": item.platformName,
        "value": item.code,
        "data-id": item.code
      }
    })
  }))], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("店铺名称")]), _vm._v(" "), _c('el-select', {
    staticClass: "search-select",
    attrs: {
      "placeholder": "选择店铺"
    },
    on: {
      "change": function($event) {
        _vm.selectStore($event)
      }
    },
    model: {
      value: (_vm.storeValue),
      callback: function($$v) {
        _vm.storeValue = $$v
      },
      expression: "storeValue"
    }
  }, _vm._l((_vm.storeList), function(item) {
    return _c('el-option', {
      key: item.id,
      attrs: {
        "label": item.name,
        "value": item.id,
        "data-id": item.id
      }
    })
  }))], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "btns"
  }, [_c('el-button', {
    staticClass: "do-add-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.saveAdd
    }
  }, [_vm._v("确认")]), _vm._v(" "), _c('el-button', {
    staticClass: "cancel-edit-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.cancelAdd
    }
  }, [_vm._v("取消")])], 1)])])
},staticRenderFns: []}

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 8:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__api_index_js__ = __webpack_require__(2);

/* harmony default export */ __webpack_exports__["a"] = ({
  isTrue: function isTrue(str) {
    return str == "Y" ? 1 : 0;
  },
  showOverlay: function showOverlay() {
    $("#mask").height(document.body.scrollHeight);
    $("#mask").width(document.body.scrollWidth);

    $("#mask").fadeTo(200, 0.5);

    $(window).resize(function () {
      $("#mask").height(document.body.scrollHeight);
      $("#mask").width(document.body.scrollWidth);
    });
  },
  hideOverlay: function hideOverlay() {
    $("#mask").fadeOut(200);
  },
  checkBox: function checkBox(checkedCode, checkedAll, checkList, value, doms) {
    checkedCode = [];
    if (checkedAll) {
      checkedAll = !checkedAll;
      checkList = [];
      checkList.push(value);
    } else if (checkList.length == 0) {
      checkedAll = !checkedAll;
      checkList = ["全部"];
      checkedCode = [];
    }
    setTimeout(function () {
      doms.each(function (index, el) {
        if (el.checked) {
          checkedCode.push(el.parentNode.parentNode.getAttribute("data-code"));
        }
      });
    }, 50);
    return [checkedCode, checkedAll, checkList, value, doms];
  },
  checkBoxAll: function checkBoxAll(checkedCode, checkedAll, checkList) {
    checkedAll = !checkedAll;
    if (checkedAll) {
      checkList = [];
      checkList.push("全部");
      checkedCode = [];
    } else if (checkList.length == 0) {
      checkedAll = !checkedAll;
      checkList = ["全部"];
      checkedCode = [];
    }
    return [checkedCode, checkedAll, checkList];
  }
});
/* WEBPACK VAR INJECTION */}.call(__webpack_exports__, __webpack_require__(0)))

/***/ })

},[144]);