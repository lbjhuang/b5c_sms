webpackJsonp([9],{

/***/ 108:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(222)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(150),
  /* template */
  __webpack_require__(231),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 140:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(108);
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

/***/ 150:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_defineProperty__ = __webpack_require__(27);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_defineProperty___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_defineProperty__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__api_index_js__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__ = __webpack_require__(8);





/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      radioLevel: 1,

      editStatus: false,
      addStatus: false,

      checkedAll: true,

      categoryLevelValue: [],

      categoryLevelCode: [],

      categoryLevelList: [{
        id: "1",
        level: 1,
        checked: false,
        value: "Level 1"
      }, {
        id: "2",
        level: 2,
        checked: false,
        value: "Level 2"
      }, {
        id: "3",
        level: 3,
        checked: false,
        value: "Level 3"
      }],
      Type01: [],
      Type02: [],
      Type03: [],
      Type01Value: '',
      Type02Value: '',
      Type03Value: '',
      Type01ID: "",
      Type02ID: "",
      Type03ID: "",
      LevelType01Value: "",
      LevelType02Value: "",
      LevelType01: [],
      LevelType02: [],
      LevelType01ID: "",
      LevelType02ID: "",

      tableData: "",

      currentPage: 1,

      totalNum: 0,

      pageNum: 20,

      catCode: "",

      catNamePingyin: "",

      catNameCN: "",

      levelStatu03: false,
      levelStatu02: false,

      editRowInfo: {},

      queryCondition: {
        catCode: "",
        level: ""
      },

      currentRow: null
    };
  },
  created: function created() {
    this.getLevel01();
    this.getCatList();
  },

  methods: __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_defineProperty___default()({
    getLevel01: function getLevel01() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getType("", 1), function (json, textStatus) {
        if (json.code == 2000) {
          vm.Type01 = json.data;
          vm.LevelType01 = json.data;
        }
      });
    },
    resetValue: function resetValue() {
      this.editRowInfo = {};
      this.catCode = "";
      this.catNameCN = "";
      this.catNamePingyin = "";
      this.radioLevel = 1;
      this.LevelType01Value = "";
      this.LevelType02Value = "";
    },
    getCatList: function getCatList() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getCatList(), function (json, textStatus) {
        if (json.code == 200) {
          vm.tableData = json.data.list;
          vm.totalNum = +json.data.totalCount;
        }
      });
    },
    handleCheck: function handleCheck() {
      var vm = this;
      vm.categoryLevelCode = [];
      console.log(vm.checkedAll);
      if (vm.checkedAll) {
        vm.checkedAll = !vm.checkedAll;
        vm.categoryLevelValue = [];
        vm.categoryLevelValue.push(event.srcElement.defaultValue);
      } else if (vm.categoryLevelValue.length == 0) {
        vm.checkedAll = !vm.checkedAll;
        vm.categoryLevelValue = ["全部"];
      }
      setTimeout(function () {
        $('.langGroup input').each(function (index, el) {
          if (el.checked) {
            vm.categoryLevelCode.push(el.parentNode.parentNode.getAttribute("data-code"));
          }
        });
      }, 50);
      console.log(vm.categoryLevelCode);
    },
    handleCheckAll: function handleCheckAll() {
      this.checkedAll = !this.checkedAll;
      console.log(this.checkedAll);
      if (this.checkedAll) {
        this.categoryLevelValue = ["全部"];
        this.categoryLevelCode = [];
      } else if (this.categoryLevelValue.length == 0) {
        this.checkedAll = !this.checkedAll;
        this.categoryLevelValue = ["全部"];
        this.categoryLevelCode = [];
      }
    },
    handleSizeChange: function handleSizeChange(val) {
      console.log('\u6BCF\u9875 ' + val + ' \u6761');
    },
    handleCurrentChange: function handleCurrentChange(val) {
      console.log('\u5F53\u524D\u9875: ' + val);
    },
    selectType01: function selectType01() {
      console.log("hello");
      var vm = this;
      vm.Type02Value = "";
      vm.Type03Value = "";
      vm.Type01ID = event.currentTarget.getAttribute("data-id");
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getType(vm.Type01ID, 2), function (data, textStatus) {
        vm.Type02 = data.data;
      });
    },
    selectType02: function selectType02() {
      var vm = this;
      vm.Type03Value = "";
      vm.Type02ID = event.currentTarget.getAttribute("data-id");
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getType(vm.Type02ID, 3), function (data, textStatus) {
        vm.Type03 = data.data;
      });
    },
    selectType03: function selectType03() {
      var vm = this;
      vm.Type03ID = event.currentTarget.getAttribute("data-id");
    },
    queryCat: function queryCat() {
      var vm = this;
      vm.currentPage = 1;
      var catCode = "";
      var page = 1;
      var level = vm.categoryLevelCode.join(",");
      level = level ? level : "1,2,3";
      if (vm.Type03ID) {
        catCode = vm.Type03ID;
      } else if (vm.Type02ID) {
        catCode = vm.Type02ID;
      } else if (vm.Type01ID) {
        catCode = vm.Type01ID;
      } else {
        catCode = "";
      }
      vm.queryCondition = {
        catCode: catCode,
        level: level
      };
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].searchCat(catCode, level, page), function (json, textStatus) {
        vm.tableData = json.data.list;
        vm.totalNum = +json.data.totalCount;
      });
    },
    resetQuery: function resetQuery() {
      this.handleCheckAll();
      this.Type01Value = '';
      this.Type02Value = '';
      this.Type03Value = '';
      this.Type01ID = "";
      this.Type02ID = "";
      this.Type03ID = "";
    },
    changeVisible: function changeVisible() {
      var vm = this;
      var isVisible = "";

      if (!vm.currentRow) {
        vm.$message({
          type: 'error',
          message: '请先选择一行'
        });
      } else {
        vm.currentRow.disable == "Y" ? isVisible = "N" : isVisible = "Y";
        vm.$confirm('是否切换此类目的可见状态', '提示', {
          confirmButtonText: '确定',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(function () {
          $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].updataCat(vm.currentRow.code, vm.currentRow.id, vm.currentRow.name, vm.currentRow.cnName, isVisible), function (json, textStatus) {
            if (json.code == 200) {
              vm.currentRow.disable = isVisible;
              vm.getLevel01();
              vm.$message({
                type: 'success',
                message: '切换成功!'
              });
            } else {
              vm.$message({
                type: 'error',
                message: json.msg
              });
            }
          });
        }).catch(function () {
          vm.$message({
            type: 'error',
            message: '已取消切换'
          });
        });
      }
    },
    addFEcat: function addFEcat() {
      var vm = this;
      vm.resetValue();
      vm.addStatus = true;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].showOverlay();
    },
    cancelAdd: function cancelAdd() {
      this.addStatus = false;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
    },
    saveAdd: function saveAdd() {
      var vm = this;
      var catLevel = "";
      var catName = "";
      var cnName = "";
      var levelFirst = "";
      var levelSecond = "";
      vm.LevelType01ID ? levelFirst = vm.LevelType01ID : levelFirst = "";
      vm.LevelType02ID ? levelSecond = vm.LevelType02ID : levelSecond = "";
      catLevel = vm.radioLevel;
      catName = vm.catNamePingyin;
      cnName = vm.catNameCN;

      if (vm.radioLevel == 3 && levelFirst && levelSecond && catName && cnName || vm.radioLevel == 2 && levelFirst && catName && cnName || vm.radioLevel == 1 && catName && cnName) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].creatCat(catLevel, catName, cnName, levelFirst, levelSecond), function (json, textStatus) {
          if (json.code == 200) {
            vm.$message({
              type: 'success',
              message: '添加成功!'
            });
            vm.addStatus = false;
            __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
            vm.getLevel01();
          } else {
            vm.$message({
              type: 'info',
              message: json.msg
            });
          }
        });
      } else {
        vm.$message({
          type: 'error',
          message: '请填写完整数据'
        });
      }
    },
    levelSelectType01: function levelSelectType01() {
      var vm = this;
      vm.LevelType02Value = "";
      vm.LevelType01ID = event.currentTarget.getAttribute("data-id");
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getType(vm.LevelType01ID, 2), function (data, textStatus) {
        vm.LevelType02 = data.data;
        console.log(vm.LevelType02);
      });
    },
    levelSelectType02: function levelSelectType02() {
      var vm = this;
      vm.LevelType02ID = event.currentTarget.getAttribute("data-id");
    },
    doEdit: function doEdit(index, rows) {
      var vm = this;
      vm.resetValue();
      vm.editStatus = true;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].showOverlay();
      vm.editRowInfo = rows[index];
      var id = rows[index].id;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getCatById(id), function (json, textStatus) {
        if (json.code == 200) {
          vm.catCode = json.data.code;
          vm.catNamePingyin = json.data.name;
          vm.catNameCN = json.data.cnName;
        } else {
          vm.$message({
            type: 'error',
            message: json.msg
          });
        }
      });
    },
    saveEdit: function saveEdit() {
      var vm = this;
      var catCode = vm.editRowInfo.code;
      var id = vm.editRowInfo.id;
      var catName = vm.catNamePingyin;
      var catCnName = vm.catNameCN;
      var isVisible = vm.editRowInfo.disable;

      if (catCnName && catName) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].updataCat(catCode, id, catName, catCnName, isVisible), function (json, textStatus) {
          if (json.code == 200) {
            vm.editRowInfo.name = catName;
            vm.editRowInfo.cnName = catCnName;
            vm.$message({
              type: 'success',
              message: '编辑成功!'
            });
            vm.editStatus = false;
            __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
          } else {
            vm.$message({
              type: 'error',
              message: json.msg
            });
          }
        });
      } else {
        vm.$message({
          type: 'error',
          message: '请填写Name'
        });
      }
    },
    cancelEdit: function cancelEdit() {
      this.editStatus = false;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
    },
    closeEditBox: function closeEditBox() {
      this.editStatus = false;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
    },
    closeAddBox: function closeAddBox() {
      this.addStatus = false;
      __WEBPACK_IMPORTED_MODULE_2__utils_utils_js__["a" /* default */].hideOverlay();
    },
    handleCurrentChangePage: function handleCurrentChangePage(val) {
      var vm = this;

      var catCode = vm.queryCondition.catCode;
      var level = vm.queryCondition.level;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].searchCat(catCode, level, val), function (json, textStatus) {
        if (json.code == 200) {
          vm.tableData = json.data.list;
          vm.totalNum = +json.data.totalCount;
        } else {
          vm.$message({
            type: 'error',
            message: json.msg
          });
        }
      });
    },
    checkLevel: function checkLevel() {
      this.catNameCN = "";
      this.catNamePingyin = "";
      this.LevelType01Value = "";
      this.LevelType02Value = "";
      this.levelStatu02 = false;
      this.levelStatu03 = false;
      if (this.radioLevel == 2) {
        this.levelStatu02 = true;
      } else if (this.radioLevel == 3) {
        this.levelStatu03 = true;
      }
    }
  }, 'handleCurrentChange', function handleCurrentChange(val) {
    this.currentRow = val;
    console.log(this.currentRow);
  })
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

/***/ 222:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 231:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "list-common FE-category-content"
  }, [_c('div', {
    attrs: {
      "id": "mask"
    }
  }), _vm._v(" "), _c('el-row', {
    staticClass: "bl-row01"
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("类目等级")])]), _vm._v(" "), _c('el-checkbox-group', {
    staticClass: "langGroup",
    model: {
      value: (_vm.categoryLevelValue),
      callback: function($$v) {
        _vm.categoryLevelValue = $$v
      },
      expression: "categoryLevelValue"
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "全部",
      "checked": _vm.checkedAll
    },
    on: {
      "change": _vm.handleCheckAll
    }
  }), _vm._v(" "), _vm._l((_vm.categoryLevelList), function(item) {
    return _c('el-checkbox', {
      key: item.id,
      attrs: {
        "value": item.value,
        "label": item.value,
        "checked": item.checked,
        "data-code": item.level
      },
      on: {
        "change": function($event) {
          _vm.handleCheck($event)
        }
      }
    })
  })], 2)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "bl-row02"
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("类目")])]), _vm._v(" "), _c('el-col', {
    staticClass: "business-type",
    attrs: {
      "span": 22
    }
  }, [_c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectType01($event)
      }
    },
    model: {
      value: (_vm.Type01Value),
      callback: function($$v) {
        _vm.Type01Value = $$v
      },
      expression: "Type01Value"
    }
  }, _vm._l((_vm.Type01), function(item) {
    return _c('el-option', {
      key: item.catNamePath,
      attrs: {
        "label": item.catNamePath,
        "value": item.catNamePath,
        "data-id": item.catId
      }
    })
  })), _vm._v(" "), _c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectType02($event)
      }
    },
    model: {
      value: (_vm.Type02Value),
      callback: function($$v) {
        _vm.Type02Value = $$v
      },
      expression: "Type02Value"
    }
  }, _vm._l((_vm.Type02), function(item) {
    return _c('el-option', {
      key: item.catNamePath,
      attrs: {
        "label": item.catNamePath.split('>')[1],
        "value": item.catNamePath,
        "data-id": item.catId
      }
    })
  })), _vm._v(" "), _c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectType03($event)
      }
    },
    model: {
      value: (_vm.Type03Value),
      callback: function($$v) {
        _vm.Type03Value = $$v
      },
      expression: "Type03Value"
    }
  }, _vm._l((_vm.Type03), function(item) {
    return _c('el-option', {
      key: item.catNamePath,
      attrs: {
        "label": item.catNamePath.split('>')[2],
        "value": item.catNamePath,
        "data-id": item.catId
      }
    })
  }))], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "query-btns"
  }, [_c('el-button', {
    staticClass: "query-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.queryCat
    }
  }, [_vm._v("查询")]), _vm._v(" "), _c('el-button', {
    staticClass: "reset-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.resetQuery
    }
  }, [_vm._v("重置")])], 1), _vm._v(" "), _c('div', {
    staticClass: "parting-line"
  }), _vm._v(" "), _c('div', {
    staticClass: "query-result-list"
  }, [_c('h3', [_vm._v("搜索结果:共"), _c('span', [_vm._v(_vm._s(_vm.totalNum))]), _vm._v("条记录")]), _vm._v(" "), _c('div', {
    staticClass: "fun-btns"
  }, [_c('el-button', {
    staticClass: "click-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.changeVisible
    }
  }, [_vm._v("切换可见状态")]), _vm._v(" "), _c('el-button', {
    staticClass: "click-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.addFEcat
    }
  }, [_vm._v("添加前端类目")])], 1), _vm._v(" "), _c('el-table', {
    staticClass: "cat-list-table",
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
      "current-change": _vm.handleCurrentChange
    }
  }, [_c('el-table-column', {
    attrs: {
      "prop": "edit",
      "label": "操作"
    },
    scopedSlots: _vm._u([{
      key: "default",
      fn: function(scope) {
        return [_c('el-button', {
          staticClass: "click-btn",
          attrs: {
            "type": "primary"
          },
          nativeOn: {
            "click": function($event) {
              $event.preventDefault();
              _vm.doEdit(scope.$index, _vm.tableData)
            }
          }
        }, [_vm._v("编辑")])]
      }
    }])
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "code",
      "label": "类目Code"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "name",
      "label": "类目名称(拼音)"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "cnName",
      "label": "类目名称(中文)"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "level",
      "label": "类目等级"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "disable",
      "label": "前台可见"
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
      "current-change": _vm.handleCurrentChangePage,
      "update:currentPage": function($event) {
        _vm.currentPage = $event
      }
    }
  })], 1), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticClass: "edit-cat-content"
  }, [_c('h3', [_c('span', [_vm._v("编辑类目")]), _c('i', {
    staticClass: "el-icon-close",
    on: {
      "click": _vm.closeEditBox
    }
  })]), _vm._v(" "), _c('div', {
    staticClass: "main-content"
  }, [_c('div', {
    staticClass: "row01"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("类目Code")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": "",
      "disabled": true
    },
    model: {
      value: (_vm.catCode),
      callback: function($$v) {
        _vm.catCode = $$v
      },
      expression: "catCode"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "row02"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("Name(pinyin)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.catNamePingyin),
      callback: function($$v) {
        _vm.catNamePingyin = $$v
      },
      expression: "catNamePingyin"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "row03"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("Name(CN)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.catNameCN),
      callback: function($$v) {
        _vm.catNameCN = $$v
      },
      expression: "catNameCN"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "row04 btns"
  }, [_c('el-button', {
    staticClass: "save-edit-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.saveEdit
    }
  }, [_vm._v("保存")]), _vm._v(" "), _c('el-button', {
    staticClass: "cancel-edit-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.cancelEdit
    }
  }, [_vm._v("取消")])], 1)])]), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.addStatus),
      expression: "addStatus"
    }],
    staticClass: "add-cat-content"
  }, [_c('h3', [_c('span', [_vm._v("添加类目")]), _c('i', {
    staticClass: "el-icon-close",
    on: {
      "click": _vm.closeAddBox
    }
  })]), _vm._v(" "), _c('div', {
    staticClass: "main-content"
  }, [_c('div', {
    staticClass: "main-content"
  }, [_c('div', {
    staticClass: "row01"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("选择类目等级")]), _vm._v(" "), _c('el-radio-group', {
    on: {
      "change": function($event) {
        _vm.checkLevel(_vm.value)
      }
    },
    model: {
      value: (_vm.radioLevel),
      callback: function($$v) {
        _vm.radioLevel = $$v
      },
      expression: "radioLevel"
    }
  }, [_c('el-radio', {
    attrs: {
      "label": 1
    }
  }, [_vm._v("Level 1")]), _vm._v(" "), _c('el-radio', {
    attrs: {
      "label": 2
    }
  }, [_vm._v("Level 2")]), _vm._v(" "), _c('el-radio', {
    attrs: {
      "label": 3
    }
  }, [_vm._v("Level 3")])], 1)], 1), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.levelStatu02 || _vm.levelStatu03),
      expression: "levelStatu02||levelStatu03"
    }],
    staticClass: "row02"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("Level 1")]), _vm._v(" "), _c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.levelSelectType01($event)
      }
    },
    model: {
      value: (_vm.LevelType01Value),
      callback: function($$v) {
        _vm.LevelType01Value = $$v
      },
      expression: "LevelType01Value"
    }
  }, _vm._l((_vm.LevelType01), function(item) {
    return _c('el-option', {
      key: item.catNamePath,
      attrs: {
        "label": item.catNamePath.split('>')[2],
        "value": item.catNamePath,
        "data-id": item.catId
      }
    })
  }))], 1), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.levelStatu03),
      expression: "levelStatu03"
    }],
    staticClass: "row03"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("Level 2")]), _vm._v(" "), _c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.levelSelectType02($event)
      }
    },
    model: {
      value: (_vm.LevelType02Value),
      callback: function($$v) {
        _vm.LevelType02Value = $$v
      },
      expression: "LevelType02Value"
    }
  }, _vm._l((_vm.LevelType02), function(item) {
    return _c('el-option', {
      key: item.catNamePath,
      attrs: {
        "label": item.catNamePath.split('>')[1],
        "value": item.catNamePath,
        "data-id": item.catId
      }
    })
  }))], 1), _vm._v(" "), _c('div', {
    staticClass: "row04"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("Name(pinyin)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.catNamePingyin),
      callback: function($$v) {
        _vm.catNamePingyin = $$v
      },
      expression: "catNamePingyin"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "row05"
  }, [_c('span', {
    staticClass: "row-title"
  }, [_vm._v("Name(CN)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.catNameCN),
      callback: function($$v) {
        _vm.catNameCN = $$v
      },
      expression: "catNameCN"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "row06 btns"
  }, [_c('el-button', {
    staticClass: "save-edit-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.saveAdd
    }
  }, [_vm._v("保存")]), _vm._v(" "), _c('el-button', {
    staticClass: "cancel-edit-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.cancelAdd
    }
  }, [_vm._v("取消")])], 1)])])])], 1)
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

},[140]);