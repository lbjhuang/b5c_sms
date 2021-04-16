webpackJsonp([10],{

/***/ 106:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(221)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(148),
  /* template */
  __webpack_require__(230),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 136:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(106);
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

/***/ 148:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__api_index_js__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__utils_utils_js__ = __webpack_require__(8);




/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      addCatStatus: false,

      brandNameValue: "",

      brandNameList: [],

      brandNameID: "",

      brandInfo: "",
      catLv1: [],
      catLv2: [],
      catLv3: [],

      catLv1ID: "",
      catLv2ID: "",
      catLv3ID: "",

      parCode: "",

      Type01: [],
      Type02: [],
      Type03: [],
      Type01Value: '',
      Type02Value: '',
      Type03Value: '',
      Type01ID: "",
      Type02ID: "",
      Type03ID: "",
      checked: true,

      cnCatName: "",
      enCatName: "",
      krCatName: "",
      jpCatName: "",

      addType01: [],
      addType02: [],
      addType03: [],
      addType01Value: '',
      addType02Value: '',
      addType03Value: '',
      addType01ID: "",
      addType02ID: "",
      addType03ID: "",

      cnCatNameAdd: "",
      enCatNameAdd: "",
      krCatNameAdd: "",
      jpCatNameAdd: "",

      checkedLevel01: "",
      checkedCode01: "",
      checkedLevel02: "",
      checkedCode02: "",
      checkedLevel03: "",
      checkedCode03: ""
    };
  },
  created: function created() {
    this.getBrandList();
    this.getLevel01();
  },

  methods: {
    doDelCat: function doDelCat(levelCode01, levelCode02, levelCode03) {
      var vm = this;
      if (levelCode03) {
        delete vm.brandInfo.thirdCate[vm.checkedCode01][vm.checkedCode02][vm.checkedCode03];
        vm.$delete(vm.catLv3, vm.checkedCode03);
      } else if (levelCode02) {
        delete vm.brandInfo.secondCate[vm.checkedCode01][vm.checkedCode02];
        vm.$delete(vm.catLv2, vm.checkedCode02);
        vm.Type03 = [];
      } else if (levelCode01) {
        delete vm.brandInfo.firstCate[vm.checkedCode01];
        vm.$delete(vm.catLv1, vm.checkedCode01);
        vm.Type02 = [];
        vm.Type03 = [];
      }
      var brand = vm.brandNameValue;
      vm.brandNameValue = "";
      vm.brandNameValue = brand;
    },
    getBindRelation: function getBindRelation(sellerId, parCode) {
      var vm = this;
      vm.Type01ID = "";
      vm.Type02ID = "";
      vm.Type03ID = "";
      vm.Type01Value = "";
      vm.Type02Value = "";
      vm.Type03Value = "";

      $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].getBindRelation(sellerId, parCode), function (json, textStatus) {
        if (json.code == 200) {
          var data = json.data;
          if (data != null && data.boundFirstCD != -1) {
            vm.Type01ID = data.boundFirstCD;
            vm.Type01Value = data.firstLevel[vm.Type01ID]["namePath"].split(">")[0];
          }
          if (data != null && data.boundSecondCD != -1) {
            vm.Type02ID = data.boundSecondCD;
            vm.Type02Value = data.secondLevel[vm.Type02ID]["namePath"].split(">")[1];
          }
          if (data != null && data.boundThirdCD != -1) {
            vm.Type03ID = data.boundThirdCD;
            vm.Type03Value = data.thirdLevel[vm.Type03ID]["namePath"].split(">")[2];
          }
        } else {
          vm.$message({
            type: 'error',
            message: data.msg
          });
        }
      });
    },
    getCatName: function getCatName(str) {
      var arr = str.split("-");
      this.krCatName = arr[0];
      this.cnCatName = arr[1];
      this.enCatName = arr[2];
      this.jpCatName = arr[3];
    },
    getBrandList: function getBrandList() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].showBrandList(), function (json, textStatus) {
        if (json.code == 2000) {
          vm.brandNameList = json.data;
        } else {
          vm.$message({
            type: 'error',
            message: data.msg
          });
        }
      });
    },
    selectBrand: function selectBrand() {
      var vm = this;

      vm.catLv1 = [];
      vm.catLv2 = [];
      vm.catLv3 = [];
      vm.checkedCode01 = '';
      vm.checkedCode02 = '';
      vm.checkedCode03 = '';
      vm.Type01Value = '';
      vm.Type02Value = '';
      vm.Type03Value = '';
      vm.Type01ID = '';
      vm.Type02ID = '';
      vm.Type03ID = '';
      vm.cnCatName = '';
      vm.enCatName = '';
      vm.krCatName = '';
      vm.jpCatName = '';
      this.parCode = "";
      this.checkedLevel01 = "";
      this.checkedLevel02 = "";

      vm.brandNameID = event.currentTarget.getAttribute("data-id");
      $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].getCategoryList(vm.brandNameID), function (json, textStatus) {
        if (json.code == 200) {
          vm.catLv1 = json.data.firstCate;
          vm.brandInfo = json.data;
        } else {
          vm.$alert(json.msg, {
            confirmButtonText: '确定'
          });
        }
      });
    },
    catLv1Click: function catLv1Click() {
      var vm = this;
      var text = event.currentTarget.innerText;

      vm.checkedCode02 = "";
      vm.checkedCode03 = "";
      vm.catLv2 = [];
      vm.catLv3 = [];
      vm.checkedLevel02 = "";
      vm.checkedCode02 = "";

      vm.catLv1ID = event.currentTarget.getAttribute("data-id");
      vm.parCode = vm.catLv1ID;

      vm.checkedLevel01 = text;
      vm.checkedCode01 = vm.catLv1ID;

      vm.getBindRelation(vm.brandNameID, vm.parCode);

      vm.getCatName(text);
      for (var key in vm.brandInfo.secondCate) {
        if (key == vm.catLv1ID) {
          vm.catLv2 = vm.brandInfo.secondCate[key];
        }
      }
    },
    catLv2Click: function catLv2Click() {
      var vm = this;
      var text = event.currentTarget.innerText;

      vm.checkedCode03 = "";
      vm.catLv3 = [];

      vm.catLv2ID = event.currentTarget.getAttribute("data-id");
      vm.parCode = vm.catLv2ID;

      vm.checkedLevel02 = text;
      vm.checkedCode02 = vm.catLv2ID;

      vm.getCatName(text);

      vm.getBindRelation(vm.brandNameID, vm.parCode);
      for (var key in vm.brandInfo.thirdCate) {
        if (key == vm.catLv1ID) {
          for (var k in vm.brandInfo.thirdCate[key]) {
            if (k == vm.catLv2ID) {
              vm.catLv3 = vm.brandInfo.thirdCate[key][k];
            }
          }
        }
      }
    },
    catLv3Click: function catLv3Click() {
      var vm = this;
      var text = event.currentTarget.innerText;
      vm.catLv3ID = event.currentTarget.getAttribute("data-id");
      vm.parCode = vm.catLv3ID;

      vm.checkedLevel03 = text;
      vm.checkedCode03 = vm.catLv3ID;

      vm.getBindRelation(vm.brandNameID, vm.parCode);

      vm.getCatName(text);
    },
    getLevel01: function getLevel01() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].showGoodsList(), function (json, textStatus) {
        if (json.code == 2000) {
          vm.Type01 = json.data.b5caiCateList;
          vm.addType01 = json.data.b5caiCateList;
        } else {
          vm.$message({
            type: 'error',
            message: data.msg
          });
        }
      });
    },
    selectType01: function selectType01() {
      var vm = this;
      if (event.type == "click" && (event.target.nodeName == "LI" || event.target.nodeName == "SPAN")) {
        vm.Type02Value = "";
        vm.Type03Value = "";
        vm.Type01ID = event.currentTarget.getAttribute("data-id");

        $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].getType(vm.Type01ID, 2), function (data, textStatus) {
          vm.Type02 = data.data;
        });
      }
    },
    selectType02: function selectType02() {
      var vm = this;
      if (event.type == "click" && (event.target.nodeName == "LI" || event.target.nodeName == "SPAN")) {
        vm.Type03Value = "";
        vm.Type02ID = event.currentTarget.getAttribute("data-id");
        $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].getType(vm.Type02ID, 3), function (data, textStatus) {
          vm.Type03 = data.data;
        });
      }
    },
    selectType03: function selectType03() {
      var vm = this;
      if (event.type == "click" && (event.target.nodeName == "LI" || event.target.nodeName == "SPAN")) {
        vm.Type03ID = event.currentTarget.getAttribute("data-id");
      }
    },
    delCat: function delCat() {
      var vm = this;
      vm.$confirm('是否确认删除此类目', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }).then(function () {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].delBECate(vm.brandNameID, vm.parCode), function (json, textStatus) {
          if (json.code == 200) {
            vm.doDelCat(vm.checkedCode01, vm.checkedCode02, vm.checkedCode03);
            vm.$message({
              type: 'success',
              message: '删除成功!'
            });
          } else {
            vm.$message({
              type: 'info',
              message: '删除失败!'
            });
          }
        });
      }).catch(function () {
        vm.$message({
          type: 'info',
          message: '已取消删除'
        });
      });
    },
    addCat: function addCat() {
      this.addCatStatus = true;
      __WEBPACK_IMPORTED_MODULE_1__utils_utils_js__["a" /* default */].showOverlay();

      this.cnCatNameAdd = "";
      this.krCatNameAdd = "";
      this.enCatNameAdd = "";
      this.jpCatNameAdd = "";
      this.addType01Value = "";
      this.addType02Value = "";
      this.addType03Value = "";
      this.addType01ID = "";
      this.addType02ID = "";
      this.addType03ID = "";
    },
    saveCat: function saveCat() {
      var vm = this;
      var postData = {
        sellerId: vm.brandNameID,
        selected: vm.parCode,
        cnName: vm.cnCatName,
        krName: vm.krCatName,
        enName: vm.enCatName,
        jpName: vm.jpCatName,
        bindL1: vm.Type01ID ? vm.Type01ID : "-1",
        bindL2: vm.Type02ID ? vm.Type02ID : "-1",
        bindL3: vm.Type03ID ? vm.Type03ID : "-1"
      };
      if (postData.bindL1 == -1) {
        vm.$message({
          type: 'info',
          message: "一级前端类目必须绑定！"
        });
      } else {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].updateBECat() + '&' + jQuery.param(postData), function (json, textStatus) {
          if (json.code == 200) {
            vm.$message({
              type: 'success',
              message: '修改成功!'
            });
          } else {
            vm.$message({
              type: 'info',
              message: '修改失败!'
            });
          }
        });
      }
    },
    saveAdd: function saveAdd() {
      var vm = this;
      var flag = true;
      var postData = {
        isCreateSub: vm.checked ? "Y" : "N",
        sellerId: vm.brandNameID,
        selected: vm.parCode,
        cnName: vm.cnCatNameAdd,
        krName: vm.krCatNameAdd,
        enName: vm.enCatNameAdd,
        jpName: vm.jpCatNameAdd,
        bindL1: vm.addType01ID ? vm.addType01ID : "-1",
        bindL2: vm.addType02ID ? vm.addType02ID : "-1",
        bindL3: vm.addType03ID ? vm.addType03ID : "-1"
      };
      if (postData.bindL1 == -1) {
        vm.$message({
          type: 'info',
          message: "一级前端类目必须绑定！"
        });
      } else {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].createBECat() + "&" + jQuery.param(postData), function (json, textStatus) {
          if (json.code == 200) {
            vm.$message({
              type: 'success',
              message: '添加成功!'
            });
            vm.addCatStatus = false;
            __WEBPACK_IMPORTED_MODULE_1__utils_utils_js__["a" /* default */].hideOverlay();
          } else {
            vm.$message({
              type: 'info',
              message: json.msg
            });
          }
        });
      }
    },
    cancelAdd: function cancelAdd() {
      __WEBPACK_IMPORTED_MODULE_1__utils_utils_js__["a" /* default */].hideOverlay();
      this.addCatStatus = false;
    },
    selectType01add: function selectType01add() {
      var vm = this;
      vm.addType02Value = "";
      vm.addType03Value = "";
      vm.addType01ID = event.currentTarget.getAttribute("data-id");
      $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].getType(vm.addType01ID, 2), function (data, textStatus) {
        vm.addType02 = data.data;
      });
    },
    selectType02add: function selectType02add() {
      var vm = this;
      vm.addType03Value = "";
      vm.addType02ID = event.currentTarget.getAttribute("data-id");
      $.getJSON(__WEBPACK_IMPORTED_MODULE_0__api_index_js__["a" /* default */].getType(vm.addType02ID, 3), function (data, textStatus) {
        vm.addType03 = data.data;
      });
    },
    selectType03add: function selectType03add() {
      var vm = this;
      vm.addType03ID = event.currentTarget.getAttribute("data-id");
    }
  }
});
/* WEBPACK VAR INJECTION */}.call(__webpack_exports__, __webpack_require__(0), __webpack_require__(0)))

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

/***/ 221:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 230:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "BE-category-content"
  }, [_c('div', {
    attrs: {
      "id": "mask"
    }
  }), _vm._v(" "), _c('header', [_vm._v("后端类目")]), _vm._v(" "), _c('div', {
    staticClass: "brand-name-content"
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("品牌名字")]), _vm._v(" "), _c('el-select', {
    attrs: {
      "placeholder": "请选择",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selectBrand($event)
      }
    },
    model: {
      value: (_vm.brandNameValue),
      callback: function($$v) {
        _vm.brandNameValue = $$v
      },
      expression: "brandNameValue"
    }
  }, _vm._l((_vm.brandNameList), function(item) {
    return _c('el-option', {
      key: item.brandEnName,
      attrs: {
        "label": item.brandEnName,
        "value": item.brandId,
        "data-id": item.brandId
      }
    })
  })), _vm._v(" "), _c('el-button', {
    staticClass: "add-cat-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.addCat
    }
  }, [_vm._v("添加类目")])], 1), _vm._v(" "), _c('div', {
    staticClass: "cat-level-content"
  }, [_c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 8
    }
  }, [_c('header', [_vm._v("类目等级一")]), _vm._v(" "), _c('select', {
    attrs: {
      "id": "select_category_lv1",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv1), function(item, key) {
    return _c('option', {
      attrs: {
        "data-id": key,
        "title": item.allName
      },
      domProps: {
        "value": item.allName
      },
      on: {
        "click": function($event) {
          _vm.catLv1Click($event)
        }
      }
    }, [_vm._v(_vm._s(item.allName))])
  }))]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 8
    }
  }, [_c('header', [_vm._v("类目等级二")]), _vm._v(" "), _c('select', {
    attrs: {
      "id": "select_category_lv2",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv2), function(item, key) {
    return _c('option', {
      attrs: {
        "data-id": key,
        "title": item.allName
      },
      domProps: {
        "value": item.allName
      },
      on: {
        "click": function($event) {
          _vm.catLv2Click($event)
        }
      }
    }, [_vm._v(_vm._s(item.allName))])
  }))]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 8
    }
  }, [_c('header', [_vm._v("类目等级三")]), _vm._v(" "), _c('select', {
    attrs: {
      "id": "select_category_lv3",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv3), function(item, key) {
    return _c('option', {
      attrs: {
        "data-id": key,
        "title": item.allName
      },
      domProps: {
        "value": item.allName
      },
      on: {
        "click": function($event) {
          _vm.catLv3Click($event)
        }
      }
    }, [_vm._v(_vm._s(item.allName))])
  }))])], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(KR)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.krCatName),
      callback: function($$v) {
        _vm.krCatName = $$v
      },
      expression: "krCatName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(CN)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.cnCatName),
      callback: function($$v) {
        _vm.cnCatName = $$v
      },
      expression: "cnCatName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(EN)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.enCatName),
      callback: function($$v) {
        _vm.enCatName = $$v
      },
      expression: "enCatName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(JPA)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.jpCatName),
      callback: function($$v) {
        _vm.jpCatName = $$v
      },
      expression: "jpCatName"
    }
  })], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line FE-cat-connect",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("前端类目关联")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
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
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-select', {
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
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-select', {
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
  }))], 1)], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "btns-content"
  }, [_c('el-button', {
    staticClass: "save-cat-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.saveCat
    }
  }, [_vm._v("保存")]), _vm._v(" "), _c('el-button', {
    staticClass: "del-cat--btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.delCat
    }
  }, [_vm._v("删除")])], 1), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.addCatStatus),
      expression: "addCatStatus"
    }],
    staticClass: "add-cat-content"
  }, [_c('h3', [_vm._v("添加类目")]), _vm._v(" "), _c('div', {
    staticClass: "main-content"
  }, [_c('div', {
    staticClass: "cat-connect"
  }, [_c('el-row', {
    staticClass: "row-line FE-cat-connect",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("一级类目")]), _vm._v(" "), _c('span', {
    staticClass: "info-content"
  }, [_vm._v(_vm._s(_vm.checkedLevel01))])]), _vm._v(" "), _c('el-row', {
    staticClass: "row-line FE-cat-connect",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("二级类目")]), _vm._v(" "), _c('span', {
    staticClass: "info-content"
  }, [_vm._v(_vm._s(_vm.checkedLevel02))])])], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line-title",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("添加类别名称")]), _vm._v(" "), _c('el-checkbox', {
    staticClass: "check-box",
    model: {
      value: (_vm.checked),
      callback: function($$v) {
        _vm.checked = $$v
      },
      expression: "checked"
    }
  }, [_vm._v("添加到子类别")])], 1), _vm._v(" "), _c('div', {
    staticClass: "cat-name"
  }, [_c('el-row', {
    staticClass: "row-line ",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(KR)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.krCatNameAdd),
      callback: function($$v) {
        _vm.krCatNameAdd = $$v
      },
      expression: "krCatNameAdd"
    }
  })], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(CN)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.cnCatNameAdd),
      callback: function($$v) {
        _vm.cnCatNameAdd = $$v
      },
      expression: "cnCatNameAdd"
    }
  })], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line ",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(EN)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.enCatNameAdd),
      callback: function($$v) {
        _vm.enCatNameAdd = $$v
      },
      expression: "enCatNameAdd"
    }
  })], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line ",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("类目名字(JPA)")]), _vm._v(" "), _c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.jpCatNameAdd),
      callback: function($$v) {
        _vm.jpCatNameAdd = $$v
      },
      expression: "jpCatNameAdd"
    }
  })], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line-title",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('span', {
    staticClass: "info-title"
  }, [_vm._v("与前端类目关联关系设置")])]), _vm._v(" "), _c('div', {
    staticClass: "FE-connect"
  }, [_c('el-row', {
    staticClass: "row-line",
    attrs: {
      "type": "flex",
      "gutter": 10
    }
  }, [_c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectType01add($event)
      }
    },
    model: {
      value: (_vm.addType01Value),
      callback: function($$v) {
        _vm.addType01Value = $$v
      },
      expression: "addType01Value"
    }
  }, _vm._l((_vm.addType01), function(item) {
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
        _vm.selectType02add($event)
      }
    },
    model: {
      value: (_vm.addType02Value),
      callback: function($$v) {
        _vm.addType02Value = $$v
      },
      expression: "addType02Value"
    }
  }, _vm._l((_vm.addType02), function(item) {
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
        _vm.selectType03add($event)
      }
    },
    model: {
      value: (_vm.addType03Value),
      callback: function($$v) {
        _vm.addType03Value = $$v
      },
      expression: "addType03Value"
    }
  }, _vm._l((_vm.addType03), function(item) {
    return _c('el-option', {
      key: item.catNamePath,
      attrs: {
        "label": item.catNamePath.split('>')[2],
        "value": item.catNamePath,
        "data-id": item.catId
      }
    })
  }))], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "btns"
  }, [_c('el-button', {
    staticClass: "save-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.saveAdd
    }
  }, [_vm._v("确认")]), _vm._v(" "), _c('el-button', {
    staticClass: "cancel-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.cancelAdd
    }
  }, [_vm._v("取消")])], 1)], 1)])])
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

},[136]);