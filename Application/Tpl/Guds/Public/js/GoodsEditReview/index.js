webpackJsonp([4],{

/***/ 11:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });


__webpack_require__(15);
__webpack_require__(13);
__webpack_require__(14);
if (!window.Quill) {
  window.Quill = __webpack_require__(34);
}
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'quill-editor',
  data: function data() {
    return {
      _content: '',
      defaultModules: {
        toolbar: [['bold', 'italic', 'underline', 'strike'], ['blockquote', 'code-block'], [{ 'header': 1 }, { 'header': 2 }], [{ 'list': 'ordered' }, { 'list': 'bullet' }], [{ 'script': 'sub' }, { 'script': 'super' }], [{ 'indent': '-1' }, { 'indent': '+1' }], [{ 'direction': 'rtl' }], [{ 'size': ['small', false, 'large', 'huge'] }], [{ 'header': [1, 2, 3, 4, 5, 6, false] }], [{ 'color': [] }, { 'background': [] }], [{ 'font': [] }], [{ 'align': [] }], ['clean'], ['link', 'image', 'video']]
      }
    };
  },
  props: {
    content: String,
    value: String,
    disabled: Boolean,
    options: {
      type: Object,
      required: false,
      default: function _default() {
        return {};
      }
    }
  },
  mounted: function mounted() {
    this.initialize();
  },
  beforeDestroy: function beforeDestroy() {
    this.quill = null;
  },
  methods: {
    initialize: function initialize() {
      if (this.$el) {
        var self = this;
        self.options.theme = self.options.theme || 'snow';
        self.options.boundary = self.options.boundary || document.body;
        self.options.modules = self.options.modules || self.defaultModules;
        self.options.modules.toolbar = self.options.modules.toolbar !== undefined ? self.options.modules.toolbar : self.defaultModules.toolbar;
        self.options.placeholder = self.options.placeholder || 'Insert text here ...';
        self.options.readOnly = self.options.readOnly !== undefined ? self.options.readOnly : false;
        self.quill = new Quill(self.$refs.editor, self.options);

        if (self.value || self.content) {
          self.quill.pasteHTML(self.value || self.content);
        }

        self.quill.on('selection-change', function (range) {
          if (!range) {
            self.$emit('blur', self.quill);
          } else {
            self.$emit('focus', self.quill);
          }
        });

        self.quill.on('text-change', function (delta, oldDelta, source) {
          var html = self.$refs.editor.children[0].innerHTML;
          var text = self.quill.getText();
          if (html === '<p><br></p>') html = '';
          self._content = html;
          self.$emit('input', self._content);
          self.$emit('change', {
            editor: self.quill,
            html: html,
            text: text
          });
        });

        if (this.disabled) {
          this.quill.enable(false);
        }

        self.$emit('ready', self.quill);
      }
    }
  },
  watch: {
    content: function content(newVal, oldVal) {
      if (this.quill) {
        if (!!newVal && newVal !== this._content) {
          this._content = newVal;
          this.quill.pasteHTML(newVal);
        } else if (!newVal) {
          this.quill.setText('');
        }
      }
    },
    value: function value(newVal, oldVal) {
      if (this.quill) {
        if (!!newVal && newVal !== this._content) {
          this._content = newVal;
          this.quill.pasteHTML(newVal);
        } else if (!newVal) {
          this.quill.setText('');
        }
      }
    },
    disabled: function disabled(newVal, oldVal) {
      if (this.quill) {
        this.quill.enable(!newVal);
      }
    }
  }
});

/***/ }),

/***/ 13:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 141:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(20);
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

/***/ 15:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 16:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 17:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "quill-editor"
  }, [_vm._t("toolbar"), _vm._v(" "), _c('div', {
    ref: "editor"
  })], 2)
},staticRenderFns: []}

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

/***/ 20:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(42)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(37),
  /* template */
  __webpack_require__(44),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 3:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 37:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_object_values__ = __webpack_require__(51);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_object_values___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_object_values__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign__ = __webpack_require__(38);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys__ = __webpack_require__(39);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify__ = __webpack_require__(10);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_babel_runtime_helpers_typeof__ = __webpack_require__(40);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_babel_runtime_helpers_typeof___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_4_babel_runtime_helpers_typeof__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty__ = __webpack_require__(27);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6__api_index_js__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__ = __webpack_require__(8);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_8_vue_quill_editor__ = __webpack_require__(26);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_8_vue_quill_editor___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_8_vue_quill_editor__);







var _methods;




/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    var _ref;

    return _ref = {
      expendState: true,
      isMutipleUpc: false,
      hsUpcCode: "",

      UPCMain: {},
      firstUPC: "",
      UPCcontainer: [],
      UPCVisible: false,
      contentUPC: "",

      fullscreenLoading: false,

      currentEditor: {},

      langListContent: {},

      langList: {},

      detailsContent: {},

      hasSubtitle: false,

      cneditorOption: {
        modules: {
          toolbar: "#cntoolbar"
        }
      },
      eneditorOption: {
        modules: {
          toolbar: "#entoolbar"
        }
      },
      kreditorOption: {
        modules: {
          toolbar: "#krtoolbar"
        }
      },
      jaeditorOption: {
        modules: {
          toolbar: "#jatoolbar"
        }
      },
      cneditor: {},
      eneditor: {},
      kreditor: {},
      jaeditor: {},

      length: "",

      toFEStatus: false,

      dialogSaleVisible: false,
      dialogUnitVisible: false,
      dialogCustomInfoVisible: false,
      dialogCustomItemVisible: false,
      dialogPriceVisible: false,

      cnDetails: "",
      enDetails: "",
      krDetails: "",
      jaDetails: "",

      textTools: "Chinese",
      tempData: {},

      checkName: "",

      isReviewing: true,

      showStatus: true,

      editStatus: false,

      reviewStatus: false,

      activeName: "first",
      loading01: "",
      loading02: "",
      loading03: "",
      loading04: "",

      cnPic: "",
      enPic: "",
      jaPic: "",
      krPic: "",
      skuPrice: "",
      skuWidth: ""
    }, __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWidth", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuPrice", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionNameObj", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "gudsId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "mainId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "brandId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "isFEStatus", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "brandName", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "brandNameValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "brandCategory", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "brandCategoryValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "brandCat", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "catLv1", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "catLv2", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "catLv3", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "catLv4", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsUnitValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsUnit", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsUnitID", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currency", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currencyId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currencyValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "originPlace", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "originPlaceId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "originPlaceValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsShelfLife", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "cateId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel01", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel02", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel03", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel04", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevelCode01", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevelCode02", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevelCode03", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "chineseTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "englishTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "koreaTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "japanTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "chineseSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "englishSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "koreaSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "japanSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "checkList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "cnLanguage", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "enLanguage", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "krLanguage", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "jaLanguage", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "KoreanOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "ChineseOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "EnglishOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "JapaneseOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "KoreanOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "ChineseOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "EnglishOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "JapaneseOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuLength", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWeight", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuHeight", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWide", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "searchBarValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "options", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionNameValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionName", [{
      value: "选项1",
      label: "颜色"
    }, {
      value: "选项2",
      label: "尺寸"
    }]), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "showSearchBox", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "showBackText", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "backText", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "dataContent", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "modifyTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "modifySubTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "langContent", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "langCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "updateGudsAJAX", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "updateSkuAJAX", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "getBasicOptionsAJAX", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "showGudsBasicAJAX", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "updataPriceAJAX", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "addNewSKUAjax", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "checkedAllPlatform", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "checkListPlatform", ["全部"]), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "PlatformList", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "checkedPlatformCode", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsType", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsTypeCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsTypeList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "FETag", "N000370200"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsDesValue", "产品介绍"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsDesList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsDesCode", "N000760100"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsDetailContent", {
      desc: {}
    }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "drawback", "Y"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "drawbackAccess", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "drawbackPercent", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "drawbackList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "drawbackPercentCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "bondedWarehouse", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "bondedWarehouseList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "bondedWarehouseCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "packingNum", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "minNum", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "maxNum", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "logisticsCategory", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "logisticsCategoryList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "logisticsCategoryCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "logisticsTypes", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "logisticsTypesList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "logisticsTypesCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "warehouseList", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "warehouseValue", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "warehouseCode", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "lithiumBattery", "N"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "nonLiquidCosmetic", "N"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "pureCell", "N"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "fragile", "N"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWidthTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuLengthTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWeightTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuHeightTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currentBtn", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "declarationValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "declarationValueTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "declarationValueItem", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionPrice", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionsShow", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "priceGroup", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "warehousePriceLink", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "purchasePrice", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "purchasePriceTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "marketPrice", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "marketPriceTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "grossInterestRate", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "grossInterestRateTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "salePrice", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "salePriceTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "saleStatusValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "saleStatusValueTemp", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "saleStatusList", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "saleStatusCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "saleStatu", { sale: {} }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "selectedOptionInfoItem", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionInfoItem", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "addSkuOptionLine", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeature", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeatureAll", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeatureContent", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currentRow", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeatureTemp", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "addSKUContent", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "finishLoad", true), _ref;
  },

  components: {
    quillEditor: __WEBPACK_IMPORTED_MODULE_8_vue_quill_editor__["quillEditor"] },
  beforeRouteEnter: function beforeRouteEnter(to, from, next) {
    next(function (vm) {
      if (to.path.match("goodsEdit")) {
        vm.editStatus = false;
        vm.reviewStatus = false;
        vm.showStatus = true;
      } else if (to.path.match("goodsReview")) {
        vm.editStatus = false;
        vm.reviewStatus = true;
        vm.showStatus = false;
      }

      if (to.params.gudsId) {
        vm.gudsId = vm.$route.params.gudsId;

        vm.checkName = vm.$route.params.checkName;
      }

      vm.getUnit();
      vm.getBasicOptions();

      vm.showGudsBasic();
    });
  },
  beforeRouteLeave: function beforeRouteLeave(to, from, next) {
    next();
  },
  created: function created() {},

  methods: (_methods = {
    expendOption: function expendOption() {
      if (this.expendState) {
        $(".erp-add-sku tbody").slideDown(300);
      } else {
        $(".erp-add-sku tbody").slideUp(300);
      }
      this.expendState = !this.expendState;
    },
    cloneObj: function (_cloneObj) {
      function cloneObj(_x) {
        return _cloneObj.apply(this, arguments);
      }

      cloneObj.toString = function () {
        return _cloneObj.toString();
      };

      return cloneObj;
    }(function (obj) {
      var str,
          newobj = obj.constructor === Array ? [] : {};
      if ((typeof obj === "undefined" ? "undefined" : __WEBPACK_IMPORTED_MODULE_4_babel_runtime_helpers_typeof___default()(obj)) !== "object") {
        return;
      } else if (window.JSON) {
        str = __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(obj), newobj = JSON.parse(str);
      } else {
        for (var i in obj) {
          newobj[i] = __WEBPACK_IMPORTED_MODULE_4_babel_runtime_helpers_typeof___default()(obj[i]) === "object" ? cloneObj(obj[i]) : obj[i];
        }
      }
      return newobj;
    }),
    setCustomsInfo: function setCustomsInfo() {
      var vm = this;
      vm.skuFeatureAll = vm.cloneObj(vm.skuFeature);
      console.log(vm.skuFeature);
      vm.dialogCustomInfoVisible = true;
    },
    changeBrand: function changeBrand() {
      if (event.type == "click") {
        this.brandId = event.currentTarget.getAttribute('data-code');
        $(".brand-name input").css("borderColor", "#bfcbd9");
      }
    },
    changeSKUState: function changeSKUState(event, enable) {
      var vm = this;
      var skuid = $(event.currentTarget).parents("tr").attr("data-sku");
      var code = "";
      if (enable == 0) {
        code = 1;
      } else {
        code = 0;
      }
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].changeSKUState(vm.mainId, skuid, code),
        type: "POST",
        dataType: "json"
      }).done(function (data) {
        if (data.code == 200) {
          vm.showGudsSKU();
        }
      }).fail(function () {
        console.log("error");
      }).always(function () {
        console.log("complete");
      });
    },
    doReview: function doReview(postData, msg, callback, isBack) {
      var vm = this;
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].doCheckGuds(),
        type: "POST",
        dataType: "json",
        data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(postData)
      }).done(function (data) {
        if (isBack) {
          vm.showBackText = false;
        }
        if (data.code == 2000) {
          if (callback) {
            vm.$alert(msg, {
              confirmButtonText: "确定",
              callback: function callback(action) {
                vm.$router.push({
                  name: "goodslist"
                });
              }
            });
          }
        } else {
          vm.$alert(data.msg, { confirmButtonText: "确定" });
        }
      }).fail(function () {
        console.log("error");
      }).always(function () {
        console.log("complete");
      });
    },
    addUPCitem: function addUPCitem() {
      this.UPCcontainer.push("");
    },
    delUPCitem: function delUPCitem(event, index) {
      this.$delete(this.UPCcontainer, index);
    },
    setUPC: function setUPC(event, index) {
      console.log(this.UPCMain);
      console.log(index);
      this.UPCVisible = true;
      setTimeout(function () {
        $(".doSetting-UPC-btn").attr("data-index", index);
      }, 50);
      if (Object.prototype.hasOwnProperty.call(this.UPCMain, index)) {
        this.firstUPC = this.UPCMain[index].split(",")[0];
        this.UPCcontainer = this.UPCMain[index].split(",").slice(1);
      }
      this.contentUPC = $(event.currentTarget).prev("span");
    },
    doSettingUPC: function doSettingUPC(event) {
      var UPCarr = [];
      var length = this.isFEStatus ? $(".erp-sku-info.erp-sku-FE tbody tr[data-sku]").length : $(".erp-sku-info.erp-sku-BE tbody tr[data-sku]").length;
      var index = $(".doSetting-UPC-btn").attr("data-index");
      console.log(index);
      $(".UPC-box").find("input").each(function (index, el) {
        if ($.trim($(this).val())) {
          UPCarr.push($(this).val());
        }
      });
      this.UPCMain[+index] = UPCarr.join(",");
      this.contentUPC.attr("title", UPCarr.join(","));
      this.UPCcontainer = [];
      this.firstUPC = "";
      this.UPCVisible = false;
      if (UPCarr.length > 1) {
        this.isFEStatus ? $(".erp-sku-info.erp-sku-FE tbody").find("tr").eq(index).find(".edit-custom-btn").click() : $(".erp-sku-info.erp-sku-BE tbody").find("tr").eq(index).find(".edit-custom-btn").click();

        this.$message({
          type: "info",
          message: "您已设置了多个UPC,请在海关申报信息中填写海关条形码!"
        });
      } else if (UPCarr.length == 1 && UPCarr.join(",")) {
        if (this.isFEStatus) {
          $(".erp-sku-info.erp-sku-FE").find("tbody").find("tr").eq(index).find(".upc-content").html(UPCarr.join(","));
        } else {
          $(".erp-sku-info.erp-sku-BE").find("tbody").find("tr").eq(index).find(".upc-content").html(UPCarr.join(","));
        }
      }
    },
    getBrandInfo: function getBrandInfo() {
      var vm = this;
      vm.catLv1 = [];
      if (vm.categoryLevelCode01) {
        $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getCateLev01(),
          type: "GET",
          dataType: "json"
        }).success(function (data) {
          if (data.code == 200) {
            vm.catLv1 = {};
            vm.categoryLevel = data.data.list;
            vm.filterDisplay(vm.categoryLevel);
            vm.catLv1 = vm.categoryLevel;
            setTimeout(function () {
              console.log(vm.categoryLevelCode01);
              $("#select_category_lv1").val(vm.categoryLevelCode01);
            }, 50);
          }
        }).error(function () {
          vm.$message({ showClose: true, message: "error", type: "error" });
        }).complete(function () {});
      }
      if (vm.categoryLevelCode02) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getSubCate(vm.categoryLevelCode01, 2), function (data, textStatus) {
          vm.catLv2 = data.data;
          vm.filterDisplay(vm.catLv2);
          setTimeout(function () {
            $("#select_category_lv2").val(vm.categoryLevelCode02);
          }, 50);
        });

        setTimeout(function () {
          $("#select_category_lv2").val(vm.categoryLevelCode02);
        }, 50);
      }
      if (vm.categoryLevelCode02) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getSubCate(vm.categoryLevelCode02, 3), function (data, textStatus) {
          vm.catLv3 = data.data;
          vm.filterDisplay(vm.catLv3);
          setTimeout(function () {
            $("#select_category_lv3").val(vm.categoryLevelCode03);
          }, 50);
        });
      }
    },
    filterDisplay: function filterDisplay(item) {
      for (var key in item) {
        if (item[key]["disable"] == "N") {
          this.$delete(item, key);
        }
      }
    },
    catLv1Click: function catLv1Click() {
      var vm = this;
      vm.catLv3 = {};
      var id = event.currentTarget.getAttribute("value");
      var name = event.currentTarget.innerText;
      if (Object.prototype.hasOwnProperty.call(vm.categoryLevel, id)) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getSubCate(id, 2), function (data, textStatus) {
          vm.catLv2 = data.data;
          vm.filterDisplay(vm.catLv2);
        });
      } else {
        vm.catLv2 = [];
      }
      vm.categoryCode = id;
    },
    catLv2Click: function catLv2Click() {
      var vm = this;
      var id = event.currentTarget.getAttribute("value");
      var name = event.currentTarget.innerText;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getSubCate(id, 3), function (data, textStatus) {
        vm.catLv3 = data.data;
        vm.filterDisplay(vm.catLv3);

        if (vm.catLv3 == null) {
          vm.catLv3 = [];
        }
      });
      vm.categoryCode = id;
    },
    catLv3Click: function catLv3Click() {
      var id = event.currentTarget.getAttribute("value");
      var name = event.currentTarget.innerText;
      this.categoryCode = id;
    },
    getValues: function getValues(ind, Arr) {
      var tempArr = [];
      Arr.forEach(function (element, index) {
        tempArr = tempArr.concat(element[ind]);
      });
      return tempArr.join("；");
    },
    toggleStatus: function toggleStatus() {
      this.showStatus = !this.showStatus;
      this.editStatus = !this.editStatus;
    },
    toggleStatusToFE: function toggleStatusToFE() {
      this.toFEStatus = true;
      this.isFEStatus = true;
      this.editStatus = true;
      this.showStatus = false;
    },
    toggleStatusSave: function toggleStatusSave() {
      var vm = this;
      var BasicData = {};
      var attributes = [];
      var optionGroup = [];
      var NewOptionGroup = [];
      var SKUData = {};
      var description = [];
      var priceData = {};
      var priceGroup = [];
      var flagBasic = true;
      var flagSKU = true;
      var flagPrice = true;
      var langData = {};
      var newSKUData = {};
      console.log(vm.langCode);
      console.log(vm.langListContent);
      console.log(vm.langContent);
      if (!vm.isFEStatus && vm.activeName == "first") {
        vm.langCode.forEach(function (ele, index) {
          if (ele == "N000920100") {
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920100") && !vm.langContent["cnPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.chineseTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.chineseTitle,
                gudsSubName: vm.chineseSubtitle,
                imgData: vm.langContent["cnPic"] ? vm.langContent["cnPic"] : { cdnAddr: vm.langListContent["N000920100"].img }
              };
            }
          } else if (ele == "N000920400") {
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920400") && !vm.langContent["krPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.koreaTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.koreaTitle,
                gudsSubName: vm.koreaSubtitle,
                imgData: vm.langContent["krPic"] ? vm.langContent["krPic"] : { cdnAddr: vm.langListContent["N000920400"].img }
              };
            }
          } else if (ele == "N000920200") {
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920200") && !vm.langContent["enPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.englishTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.englishTitle,
                gudsSubName: vm.englishSubtitle,
                imgData: vm.langContent["enPic"] ? vm.langContent["enPic"] : { cdnAddr: vm.langListContent["N000920200"].img }
              };
            }
          } else if (ele == "N000920300") {
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920300") && !vm.langContent["jaPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.japanTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.japanTitle,
                gudsSubName: vm.japanSubtitle,
                imgData: vm.langContent["jaPic"] ? vm.langContent["jaPic"] : { cdnAddr: vm.langListContent["N000920300"].img }
              };
            }
          }
        });
        BasicData = {
          publishType: "0",
          brandId: vm.brandId,
          brandName: vm.brandNameValue,
          cateId: vm.categoryCode,
          mainId: vm.mainId,
          priceType: vm.currencyId,
          originCountry: vm.originPlaceId,

          isLifetime: vm.goodsShelfLife,
          unit: vm.goodsUnitID,

          langData: langData
        };
      } else if ((vm.isFEStatus || vm.toFEStatus) && vm.activeName == "first") {
        vm.langCode.forEach(function (ele, index) {
          if (ele == "N000920100") {
            var desc = {};
            for (key in vm.goodsDetailContent.desc) {
              if (Object.prototype.hasOwnProperty.call(vm.goodsDetailContent.desc[key], "N000920100")) {
                desc[key] = vm.goodsDetailContent.desc[key]["N000920100"];
              }
            }

            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920100") && !vm.langContent["cnPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.chineseTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else if (vm.cnDetails == null || vm.cnDetails == "" || vm.cnDetails == "<p></p>" || $.trim(vm.cnDetails.slice(3, -4)) == "") {
              flagBasic = false;
              vm.$alert("商品详情不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.chineseTitle,
                gudsSubName: vm.chineseSubtitle,
                imgData: vm.langContent["cnPic"] ? vm.langContent["cnPic"] : { cdnAddr: vm.langListContent["N000920100"].img },
                desc: desc,
                detail: vm.cnDetails
              };
            }
          } else if (ele == "N000920400") {
            var _desc = {};
            for (key in vm.goodsDetailContent.desc) {
              if (Object.prototype.hasOwnProperty.call(vm.goodsDetailContent.desc[key], "N000920400")) {
                _desc[key] = vm.goodsDetailContent.desc[key]["N000920400"];
              }
            }
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920400") && !vm.langContent["krPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.koreaTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else if (vm.krDetails == null || vm.krDetails == "" || vm.krDetails == "<p></p>" || $.trim(vm.krDetails.slice(3, -4)) == "") {
              flagBasic = false;
              vm.$alert("商品详情不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.koreaTitle,
                gudsSubName: vm.koreaSubtitle,
                imgData: vm.langContent["krPic"] ? vm.langContent["krPic"] : { cdnAddr: vm.langListContent["N000920400"].img },
                desc: _desc,
                detail: vm.krDetails
              };
            }
          } else if (ele == "N000920200") {
            var _desc2 = {};
            for (key in vm.goodsDetailContent.desc) {
              if (Object.prototype.hasOwnProperty.call(vm.goodsDetailContent.desc[key], "N000920200")) {
                _desc2[key] = vm.goodsDetailContent.desc[key]["N000920200"];
              }
            }
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920200") && !vm.langContent["enPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.englishTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else if (vm.enDetails == null || vm.enDetails == "" || vm.enDetails == "<p></p>" || $.trim(vm.enDetails.slice(3, -4)) == "") {
              flagBasic = false;
              vm.$alert("商品详情不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.englishTitle,
                gudsSubName: vm.englishSubtitle,
                imgData: vm.langContent["enPic"] ? vm.langContent["enPic"] : { cdnAddr: vm.langListContent["N000920200"].img },
                desc: _desc2,
                detail: vm.enDetails
              };
            }
          } else if (ele == "N000920300") {
            var _desc3 = {};
            for (key in vm.goodsDetailContent.desc) {
              if (Object.prototype.hasOwnProperty.call(vm.goodsDetailContent.desc[key], "N000920300")) {
                _desc3[key] = vm.goodsDetailContent.desc[key]["N000920300"];
              }
            }
            if (!Object.prototype.hasOwnProperty.call(vm.langListContent, "N000920300") && !vm.langContent["jaPic"]) {
              flagBasic = false;
              vm.$alert("商品主图不能为空", { confirmButtonText: "确定" });
            } else if ($.trim(vm.japanTitle) == "") {
              flagBasic = false;
              vm.$alert("商品标题不能为空", { confirmButtonText: "确定" });
            } else if (vm.jaDetails == null || vm.jaDetails == "" || vm.jaDetails == "<p></p>" || $.trim(vm.jaDetails.slice(3, -4)) == "") {
              flagBasic = false;
              vm.$alert("商品详情不能为空", { confirmButtonText: "确定" });
            } else {
              langData[ele] = {
                gudsId: vm.langListContent[ele] ? vm.langListContent[ele].gudsId : "",
                gudsName: vm.japanTitle,
                gudsSubName: vm.japanSubtitle,
                imgData: vm.langContent["jaPic"] ? vm.langContent["jaPic"] : { cdnAddr: vm.langListContent["N000920300"].img },
                desc: _desc3,
                detail: vm.jaDetails
              };
            }
          }
        });
        BasicData = {
          publishType: "1",
          brandId: vm.brandId,
          brandName: vm.brandNameValue,
          cateId: vm.categoryCode,
          mainId: vm.mainId,
          priceType: vm.currencyId,
          originCountry: vm.originPlaceId,
          isRefundTax: vm.drawback,
          refundTaxRate: vm.drawbackPercentCode,
          overseasTax: vm.bondedWarehouseCode,
          PCS: vm.packingNum,
          minBuyNum: vm.minNum,
          maxBuyNum: vm.maxNum,
          expressCat: vm.logisticsCategoryCode,
          expressType: vm.logisticsTypesCode,
          detail: vm.details,
          saleChannel: vm.checkedPlatformCode.length > 0 ? vm.checkedPlatformCode : __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.PlatformList),
          productFlag: vm.FETag,
          isLifetime: vm.goodsShelfLife,
          unit: vm.goodsUnitID,

          langData: langData
        };

        if (vm[vm.modifyTitle] == "") {
          flagBasic = false;
          vm.$alert("标题不能为空", { confirmButtonText: "确定" });
        }
        if (BasicData.overseasTax == "" || BasicData.PCS == "" || BasicData.minBuyNum == "" || BasicData.maxBuyNum == "" || BasicData.expressCat == "" || BasicData.expressType == "") {
          flagBasic = false;
          if (vm.drawback == "Y") {
            if (BasicData.refundTaxRate == "" || BasicData.refundTaxRate == null) {
              $(".drawback-per input").css("borderColor", "red");
              vm.$alert("请选择返税比率", {
                confirmButtonText: "确定"
              });
            }
          }
          if (BasicData.overseasTax == "" || BasicData.overseasTax == null) {
            $(".bonded-warehouse input").css("borderColor", "red");
            vm.$alert("请选择跨境电商综合税", {
              confirmButtonText: "确定"
            });
          } else if (BasicData.PCS == "" || BasicData.PCS == null) {
            $(".pack-num input").css("borderColor", "red");
            vm.$alert("请填写装箱数", {
              confirmButtonText: "确定"
            });
          } else if (BasicData.minBuyNum == "" || BasicData.minBuyNum == null) {
            $(".min-num input").css("borderColor", "red");
            vm.$alert("请填写最小起订数", {
              confirmButtonText: "确定"
            });
          } else if (BasicData.maxBuyNum == "" || BasicData.maxBuyNum == null) {
            $(".max-num input").css("borderColor", "red");
            vm.$alert("请填写最大起订量", {
              confirmButtonText: "确定"
            });
          } else if (BasicData.expressCat == "" || BasicData.expressCat == null) {
            $(".logistic-cat input").css("borderColor", "red");
            vm.$alert("请填写物流类目", {
              confirmButtonText: "确定"
            });
          } else if (BasicData.expressType == "" || BasicData.expressType == null) {
            $(".logistic-type input").css("borderColor", "red");
            vm.$alert("请填写物流类型", {
              confirmButtonText: "确定"
            });
          }
        }
      }

      for (var key in vm.goodsDetailContent.desc) {
        description.push({
          gudsInfo: key,
          productDetail: vm.goodsDetailContent.desc[key][vm.langCode]
        });
      }

      if (!vm.isFEStatus && vm.activeName == "second") {
        var length = $(".erp-sku-info.erp-sku-BE tbody tr[data-sku]").length;
        $(".erp-sku-info.erp-sku-BE tbody tr:not([data-sku])").each(function (index, el) {
          var extension = {};
          for (var key in vm.skuFeatureContent[index + length]) {
            extension[key] = vm.skuFeatureContent[index + length][key].radioVal;
            if (extension[key] == "") {
              flagSKU = false;
              vm.$alert("海关信息都为必填(海关条形码不一定)", {
                confirmButtonText: "确定"
              });
            }
          }
          var temObj = {
            attributes: {
              PRICE: $(el).find(".price-value").find("input").val(),
              UPC: $(el).find(".upc-value").attr("title") ? $(el).find(".upc-value").attr("title") : "",
              CR: $(el).find(".cr-code-value").find("input").val(),
              HS: $(el).find(".hs-code-value").find("input").val(),
              LENGTH: $(el).find(".length-value").find("input").val(),
              WIDTH: $(el).find(".width-value").find("input").val(),
              HEIGHT: $(el).find(".height-value").find("input").val(),
              WEIGHT: $(el).find(".weight-value").find("input").val(),
              hsUpcCode: $(el).find(".upc-content").html().replace(/\s+/g, ""),
              customsPrice: $(el).find(".declarationValue").text()
            },
            extension: extension
          };
          __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign___default()(temObj, vm.addSKUContent[index]);
          vm.$delete(temObj, "codes");
          NewOptionGroup.push(temObj);
        });

        newSKUData = {
          publishType: 0,
          sellerId: vm.brandId,
          gudsId: vm.gudsId,
          mainGudsId: vm.mainId,
          optionGroup: NewOptionGroup
        };

        $(".erp-sku-info.erp-sku-BE tbody tr[data-sku]").each(function (index, el) {
          var extension = {};
          for (var key in vm.skuFeatureContent[index]) {
            extension[key] = vm.skuFeatureContent[index][key].radioVal;
            if (extension[key] == "") {
              flagSKU = false;
              vm.$alert("海关信息都为必填(海关条形码不一定)", {
                confirmButtonText: "确定"
              });
            }
          }

          optionGroup.push({
            optionId: el.getAttribute("data-sku"),
            attributes: {
              PRICE: $(el).find(".price-value").find("input").val(),
              UPC: $(el).find(".upc-value").attr("title") ? $(el).find(".upc-value").attr("title") : "",
              CR: $(el).find(".cr-code-value").find("input").val(),
              HS: $(el).find(".hs-code-value").find("input").val(),
              LENGTH: $(el).find(".length-value").find("input").val(),
              WIDTH: $(el).find(".width-value").find("input").val(),
              HEIGHT: $(el).find(".height-value").find("input").val(),
              WEIGHT: $(el).find(".weight-value").find("input").val(),
              hsUpcCode: $(el).find(".upc-content").html().replace(/\s+/g, ""),
              customsPrice: $(el).find(".declarationValue").text()
            },
            extension: extension
          });
        });
        SKUData = {
          publishType: 0,
          sellerId: vm.brandId,
          gudsId: vm.gudsId,
          mainGudsId: vm.mainId,
          optionGroup: optionGroup
        };
      } else if ((vm.isFEStatus || vm.toFEStatus) && vm.activeName == "second") {
        var _length = $(".erp-sku-info.erp-sku-FE tbody tr[data-sku]").length;
        $(".erp-sku-info.erp-sku-FE tbody tr:not([data-sku])").each(function (index, el) {
          var extension = {};
          for (var key in vm.skuFeatureContent[index + _length]) {
            extension[key] = vm.skuFeatureContent[index + _length][key].radioVal;
            if (extension[key] == "") {
              flagSKU = false;
              vm.$alert("海关信息都为必填(海关条形码不一定)", {
                confirmButtonText: "确定"
              });
            }
          }
          if ($(el).find(".weight-value").find("input").val() == "") {
            vm.$alert("请填写重量", {
              confirmButtonText: "确定"
            });
            flagSKU = false;
          } else if ($(el).find(".sale-status-lists").find(".el-input__inner").val() == "") {
            vm.$alert("请选择销售状态", {
              confirmButtonText: "确定"
            });
            flagSKU = false;
          } else if (!$(el).find(".upc-content").html() && $(el).find(".upc-value").attr("title") && $(el).find(".upc-value").attr("title").split(",").length > 1) {
            vm.$alert("拥有多个UPC的SKU需要在海关申报信息中填写海关条形码", {
              confirmButtonText: "确定"
            });
            flagSKU = false;
          }
          var temObj = {
            attributes: {
              PRICE: $(el).find(".price-value").find("input").val(),
              UPC: $(el).find(".upc-value").attr("title") ? $(el).find(".upc-value").attr("title") : "",
              CR: $(el).find(".cr-code-value").find("input").val(),
              HS: $(el).find(".hs-code-value").find("input").val(),
              LENGTH: $(el).find(".length-value").find("input").val(),
              WIDTH: $(el).find(".width-value").find("input").val(),
              HEIGHT: $(el).find(".height-value").find("input").val(),
              WEIGHT: $(el).find(".weight-value").find("input").val(),
              saleState: vm.saleStatu.sale[index + _length]["code"],
              hsUpcCode: $(el).find(".upc-content").html().replace(/\s+/g, ""),
              customsPrice: $(el).find(".declarationValue").text()
            },
            extension: extension
          };
          __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign___default()(temObj, vm.addSKUContent[index]);

          NewOptionGroup.push(temObj);
        });

        newSKUData = {
          publishType: 1,
          sellerId: vm.brandId,
          gudsId: vm.gudsId,
          mainGudsId: vm.mainId,
          optionGroup: NewOptionGroup
        };
        $(".erp-sku-info.erp-sku-FE tbody tr[data-sku]").each(function (index, el) {
          var extension = {};
          for (var key in vm.skuFeatureContent[index]) {
            extension[key] = vm.skuFeatureContent[index][key].radioVal;
            if (extension[key] == "") {
              flag = false;
              vm.$alert("海关信息都为必填(海关条形码不一定)", {
                confirmButtonText: "确定"
              });
            }
          }

          var $tr = $(this);
          if ($tr.find(".weight-value").find("input").val() == "") {
            vm.$alert("请填写重量", {
              confirmButtonText: "确定"
            });
            flagSKU = false;
          } else if ($tr.find(".sale-status-lists").find(".el-input__inner").val() == "") {
            vm.$alert("请选择销售状态", {
              confirmButtonText: "确定"
            });
            flagSKU = false;
          } else if (!$tr.find(".upc-content").html() && $tr.find(".upc").attr("title") && $tr.find(".upc-value").attr("title").split(",").length > 1) {
            vm.$alert("拥有多个UPC的SKU需要在海关申报信息中填写海关条形码", {
              confirmButtonText: "确定"
            });
            flagSKU = false;
          }
          optionGroup.push({
            optionId: el.getAttribute("data-sku"),
            attributes: {
              PRICE: $(el).find(".price-value").find("input").val(),
              UPC: $(el).find(".upc-value").attr("title") ? $(el).find(".upc-value").attr("title") : "",
              CR: $(el).find(".cr-code-value").find("input").val(),
              HS: $(el).find(".hs-code-value").find("input").val(),
              LENGTH: $(el).find(".length-value").find("input").val(),
              WIDTH: $(el).find(".width-value").find("input").val(),
              HEIGHT: $(el).find(".height-value").find("input").val(),
              WEIGHT: $(el).find(".weight-value").find("input").val(),
              hsUpcCode: $(el).find(".upc-content").html().replace(/\s+/g, ""),
              customsPrice: $(el).find(".declarationValue").text(),
              saleState: vm.saleStatu.sale[index]["code"] },
            extension: extension
          });
        });

        SKUData = {
          publishType: 1,
          sellerId: vm.brandId,
          gudsId: vm.gudsId,
          mainGudsId: vm.mainId,
          platform: vm.checkedPlatformCode.length > 0 ? vm.checkedPlatformCode : __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.PlatformList),
          optionGroup: optionGroup
        };
      }

      if (vm.activeName == "third") {
        $(".erp-sku-price-info tbody>tr").each(function (index, el) {
          var $this = $(this);
          $this.find(".warehouse-id").each(function (i, e) {
            var id = $(this).data("id");
            var optionId = $this.find(".sku-id").text();
            var warehouse = $(this).data("code");
            var purchasePrice = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".purchasing-price").find("input").val();
            var marketPrice = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".market-price").find("input").val();
            var realPrice = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".sale-price").find("input").val();
            var grossProfitMargin = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".gross-interest-rate").find("input").val();

            if (purchasePrice == "" || marketPrice == "" || realPrice == "" || grossProfitMargin == "" || warehouse == "") {
              flagPrice = false;
              vm.$alert("所有价格相关信息必填", { confirmButtonText: "确定" });
            }
            priceGroup.push({
              id: id,
              optionId: optionId,
              warehouse: warehouse,
              purchasePrice: purchasePrice,
              marketPrice: marketPrice,
              realPrice: realPrice,
              grossProfitMargin: grossProfitMargin
            });
          });
          priceData = {
            publishType: 1,
            mainGudsId: vm.mainId,
            gudsId: vm.gudsId,
            currency: vm.currencyId,
            priceGroup: priceGroup
          };
        });
        if (priceGroup.length == 0) {
          flagPrice = false;
          vm.$alert("价格信息不能为空", { confirmButtonText: "确定" });
        }
      }

      if (vm.activeName == "first" && flagBasic) {
        vm.fullscreenLoading = true;
        vm.updateGudsAJAX = $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].updateGudsData(),
          type: "POST",
          dataType: "json",
          data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(BasicData)
        }).done(function (data) {
          if (data.code == 2000) {
            vm.fullscreenLoading = false;
            vm.checkName = "N000420200";

            vm.$alert("保存成功", {
              confirmButtonText: "确定",
              callback: function callback(action) {
                vm.activeName = "second";
                vm.showGudsSKU();
              }
            });
          } else {
            vm.fullscreenLoading = false;
            vm.$alert(data.msg, {
              confirmButtonText: "确定"
            });
          }
        }).fail(function () {
          vm.fullscreenLoading = false;
          console.log("error");
        }).always(function () {
          vm.fullscreenLoading = false;
        });
      } else if (vm.activeName == "second" && flagSKU) {
        vm.fullscreenLoading = true;
        if (newSKUData.optionGroup.length < 1) {
          vm.updateSkuAJAX = $.ajax({
            url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].modifySKU(),
            type: "POST",
            dataType: "json",
            data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(SKUData)
          }).done(function (data) {
            if (data.code == 200) {
              vm.fullscreenLoading = false;
              vm.checkName = "N000420200";

              if (vm.isFEStatus || vm.toFEStatus) {
                vm.$alert("修改SKU成功！", {
                  confirmButtonText: "确定",
                  callback: function callback(action) {
                    vm.activeName = "third";
                    vm.getOptionPrice();
                  }
                });
              } else {
                vm.$alert("修改SKU成功！", {
                  confirmButtonText: "确定",
                  callback: function callback(action) {
                    vm.editStatus = false;
                    vm.showStatus = true;
                  }
                });
              }
            } else {
              vm.fullscreenLoading = false;
              vm.$alert(data.msg, {
                confirmButtonText: "确定"
              });
            }
          }).fail(function () {
            vm.fullscreenLoading = false;
          }).always(function () {
            vm.fullscreenLoading = false;
          });
        } else {
          $.when($.ajax({
            url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].createGoods(),
            type: "POST",
            dataType: "json",
            data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(newSKUData)
          }), $.ajax({
            url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].modifySKU(),
            type: "POST",
            dataType: "json",
            data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(SKUData)
          })).done(function (data1, data2) {
            vm.fullscreenLoading = false;
            if (data1[0].code == 200 && data2[0].code == 200) {
              vm.checkName = "N000420200";
              if (vm.isFEStatus || vm.toFEStatus) {
                vm.$alert("修改SKU成功！新增SKU成功！", {
                  confirmButtonText: "确定",
                  callback: function callback(action) {
                    vm.activeName = "third";
                    vm.getOptionPrice();
                  }
                });
              } else {
                vm.$alert("修改SKU成功！新增SKU成功！", {
                  confirmButtonText: "确定",
                  callback: function callback(action) {
                    vm.editStatus = false;
                    vm.showStatus = true;
                    vm.showGudsSKU();
                  }
                });
              }
            } else if (data1[0].code != 200 && data2[0].code == 200) {
              vm.$alert("修改SKU成功！新增SKU失败!  新增：" + data1[0].msg, {
                confirmButtonText: "确定",
                callback: function callback(action) {}
              });
            } else if (data1[0].code == 200 && data2[0].code != 200) {
              vm.$alert("修改SKU失败！新增SKU成功!  修改：" + data2[0].msg, {
                confirmButtonText: "确定",
                callback: function callback(action) {}
              });
            } else {
              vm.$alert("修改SKU失败！新增SKU失败! 新增：" + data1[0].msg + " 修改：" + data2[0].msg, {
                confirmButtonText: "确定",
                callback: function callback(action) {}
              });
            }
          });
        }
      } else if (vm.activeName == "third" && flagPrice) {
        vm.fullscreenLoading = true;
        if (!vm.toFEStatus) {
          vm.updataPriceAJAX = $.ajax({
            url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].updatePrice(),
            type: "POST",
            dataType: "json",
            data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(priceData)
          }).done(function (data) {
            vm.fullscreenLoading = false;
            if (data.code == 200) {
              vm.checkName = "N000420200";

              vm.$alert("保存成功", {
                confirmButtonText: "确定",
                callback: function callback(action) {
                  vm.editStatus = false;
                  vm.showStatus = true;
                  vm.getOptionPrice();
                }
              });
            } else {
              vm.fullscreenLoading = false;
              vm.$alert(data.msg, {
                confirmButtonText: "确定"
              });
            }
          }).fail(function () {
            vm.fullscreenLoading = false;
          }).always(function () {
            vm.fullscreenLoading = false;
          });
        } else if (vm.toFEStatus) {
          vm.fullscreenLoading = true;

          vm.updataPriceAJAX = $.ajax({
            url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].updatePrice(),
            type: "POST",
            dataType: "json",
            data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(priceData)
          }).done(function (data) {
            vm.fullscreenLoading = false;

            if (data.code == 200) {
              vm.checkName = "N000420200";

              vm.$alert("发布成功", {
                confirmButtonText: "确定",
                callback: function callback(action) {
                  vm.checkName = "N000420200";
                  vm.toFEStatus = false;
                  vm.showStatus = true;
                  vm.isFEStatus = true;
                  vm.editStatus = false;
                  vm.activeName = "first";
                  $(window.parent.document).find(".show_iframe").filter(function (index) {
                    return $(this).css("display") == "block";
                  }).find("iframe").attr("src", "/index.php?g=guds&m=guds&a=index#/goodslist/goodsedit/" + vm.mainId);
                }
              });
            } else {
              vm.fullscreenLoading = false;

              vm.$alert(data.msg, {
                confirmButtonText: "确定"
              });
            }
          }).fail(function () {
            vm.fullscreenLoading = false;
          }).always(function () {
            vm.fullscreenLoading = false;
          });
        }
      }
    },
    handleClick: function handleClick(tab, event) {
      if (tab.name == "second") {
        this.showGudsSKU();
      } else if (tab.name == "third") {
        this.getOptionPrice();
      }
    },
    handleClick01: function handleClick01(tab, event) {
      var vm = this;

      if (tab.name == "Chinese") {
        vm.details = vm.detailsContent["N000920100"];
      } else if (tab.name == "English") {
        vm.details = vm.detailsContent["N000920200"];
      } else if (tab.name == "Korean") {
        vm.details = vm.detailsContent["N000920400"];
      } else if (tab.name == "Japanese") {
        vm.details = vm.detailsContent["N000920300"];
      }
    },
    languageChange: function languageChange(event) {},
    toggleCheck: function toggleCheck(event) {
      this.langCode = [];
      if (event.indexOf("中文") != -1) {
        this.cnLanguage = false;
        this.langCode.push("N000920100");
      } else {
        this.cnLanguage = true;
        this.$delete(this.langCode, this.langCode.indexOf("N000920100"));
      }
      if (event.indexOf("英语") != -1) {
        this.enLanguage = false;
        this.langCode.push("N000920200");
      } else {
        this.enLanguage = true;
        this.$delete(this.langCode, this.langCode.indexOf("N000920200"));
      }
      if (event.indexOf("韩语") != -1) {
        this.krLanguage = false;
        this.langCode.push("N000920400");
      } else {
        this.krLanguage = true;
        this.$delete(this.langCode, this.langCode.indexOf("N000920400"));
      }
      if (event.indexOf("日语") != -1) {
        this.jaLanguage = false;
        this.langCode.push("N000920300");
      } else {
        this.jaLanguage = true;
        this.$delete(this.langCode, this.langCode.indexOf("N000920300"));
      }
    },
    selectCurrency: function selectCurrency() {
      if (event.type == "click") {
        this.currencyId = event.currentTarget.getAttribute("data-id");
      }
    },
    selectOriginPlace: function selectOriginPlace() {
      if (event.type == "click") {
        this.originPlaceId = event.currentTarget.getAttribute("data-id");
      }
    },
    selcetGoodsUnit: function selcetGoodsUnit() {
      if (event.type == "click") {
        this.goodsUnitID = event.currentTarget.getAttribute("data-id");
      }
    },
    selectOptionName: function selectOptionName() {
      var vm = this;
      vm.optionNameId = event.currentTarget.getAttribute("data-code");
      if (vm.optionNameId) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getOptionValues(vm.optionNameId), function (data, textStatus) {
          vm.optionInfoItem = data.data;
          vm.optionValueAll = data.data;

          vm.AddOptionPostData = {
            optNameCode: "",
            optValues: []
          };
          $(".option-info-item.active-option").removeClass("active-option");
          vm.selectedOptionInfoItem = [];
        });
      }
    },
    openSearchBox: function openSearchBox() {
      __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].showOverlay();
      document.querySelector(".option-search-box").removeAttribute("data-index");
      document.querySelector(".option-search-box").removeAttribute("data-code");
      this.showSearchBox = true;
      var index = event.currentTarget.getAttribute("data-index");
      var code = event.currentTarget.getAttribute("data-code");
      var codeItems = event.currentTarget.getAttribute("data-items");

      if (index != null) {
        document.querySelector(".option-search-box").setAttribute("data-index", index);
        document.querySelector(".option-search-box").setAttribute("data-code", code);
        document.querySelector(".option-search-box").setAttribute("data-items", codeItems);
        this.optionNameValue = this.optionName[code]["ALL_VAL"];
        setTimeout(function () {
          $(".option-info-item").each(function (ind, el) {
            codeItems.split(",").forEach(function (element, index) {
              console.log(el.getAttribute("data-code"));
              console.log(element);
              if (el.getAttribute("data-code") == element) {
                console.log(element);
                console.log(el);
                $(el).click();
              }
            });
          });
        }, 200);
      }
    },
    closeSearchBox: function closeSearchBox() {
      this.resetOption();
      this.showSearchBox = false;
      __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].hideOverlay();
    },
    delRowOption: function delRowOption() {
      var vm = this;
      var current = $(event.currentTarget);
      vm.addSkuOptionLine.forEach(function (element, index) {
        if (element.nameCode == current.prev("span").attr("data-parcode")) {
          var ind = vm.addSkuOptionLine[index].valueCode.split(",").indexOf(current.prev("span").attr("data-code"));
          if (ind != -1) {
            var arr = vm.addSkuOptionLine[index].valueCode.split(",");
            arr.splice(ind, 1);
            vm.addSkuOptionLine[index].optionValue.splice(ind, 1);
            vm.addSkuOptionLine[index].valueCode = arr.join(",");
          }
        }
      });
    },
    searchOptionName: function searchOptionName() {
      var vm = this;
      var searchKey = $(".search-optioninfo").find("input").val();
      $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].searchOptionValue(vm.optionNameId, searchKey), function (data, textStatus) {
        vm.optionInfoItem = data.data;

        setTimeout(function () {
          $(".option-info .option-info-item").each(function (ind, el) {
            $(el).removeClass("active-option");
          });
          $(".selected-options .selected-options-item").each(function (index, element) {
            var id = element.getAttribute("data-code");
            $(".option-info .option-info-item").each(function (ind, el) {
              if (el.getAttribute("data-code") == id) {
                $(el).addClass("active-option");
              }
            });
          });
        }, 50);
      });
    },
    FEcheckSKU: function FEcheckSKU(arr) {
      var vm = this;
      var result = true;
      vm.addSKUContent.forEach(function (element, index) {
        if (arr.sort().toString() == element.codes.sort().toString()) {
          result = false;
        }
      });
      return result;
    },
    checkSKU: function checkSKU(data) {
      var result = "";
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].checkExist(),
        type: "POST",
        async: false,
        dataType: "json",
        data: data
      }).done(function (data) {
        if (data.code == 200) {
          result = data.data;
        }
      }).fail(function () {
        console.log("error");
      }).always(function () {
        console.log("complete");
      });
      return result;
    },
    singleToBuild: function singleToBuild() {
      var vm = this;
      vm.showSKUinfo = true;
      var currentSKU = {};
      var inObj = {};
      var isSelectedNone = true;
      var optName = "";
      var optValues = "";
      var optCode = {};
      var optObj = {};
      optObj["codes"] = [];
      $(".erp-add-sku .added-sku-option").each(function (index, element) {
        $(element).find(".item-option-value").each(function (ind, el) {
          if ($(el).parent().hasClass("is_select_option")) {
            isSelectedNone = false;
            optName += $(el).parents("tr").find(".option_name").find(".item-option-name").text() + "<BR/>";
            optValues += $(el).text() + ";<BR/>";
            optCode[$(el).attr("data-parcode")] = $(el).attr("data-code");
            optObj[$(el).attr("data-parcode")] = {
              CODE: $(el).attr("data-code"),
              PAR_CODE: $(el).attr("data-parcode"),
              ALL_VAL: $(el).text()
            };
            optObj["codes"].push($(el).attr("data-code"));
            setTimeout(function () {
              $(element).find(".item-option-content").removeClass("is_select_option");
            }, 50);
          }
        });
      });
      var flag = vm.checkSKU(__WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()({
        mainGudsId: vm.mainId,
        optionGroup: optObj
      }));
      inObj["optText"] = {
        optNames: optName,
        optValues: optValues
      };

      inObj["optCode"] = optCode;

      if (!isSelectedNone && !flag && vm.FEcheckSKU(optObj.codes)) {
        vm.$set(vm.saleStatu.sale, __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.saleStatu.sale).length, {
          code: "",
          value: ""
        });

        vm.optionList.push(inObj);
        var length = __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.skuFeatureContent).length;
        vm.skuFeatureContent[length] = vm.cloneObj(vm.skuFeature);
        vm.addSKUContent.push(optObj);
        vm.UPCMain[__WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.UPCMain).length] = "";
      } else if (flag || !vm.FEcheckSKU(optObj.codes)) {
        vm.$message({
          showClose: true,
          message: "sku option信息重复",
          type: "error"
        });
      } else {
        vm.$message({
          showClose: true,
          message: "请至少选择一个Option",
          type: "error"
        });
      }
    },
    delSkuOption: function delSkuOption(index) {
      this.addSkuOptionLine.splice(index, 1);
    },
    resetOption: function resetOption() {
      this.optionNameValue = "";
      this.selectedOptionInfoItem = [];
      this.optionInfoItem = [];
      $(".option-add").find("input").val("");
      $(".search-optioninfo").find("input").val("");
    },
    saveOptionInfo: function saveOptionInfo() {
      var vm = this;
      var index = document.querySelector(".option-search-box").getAttribute("data-index");
      var thisCode = document.querySelector(".option-search-box").getAttribute("data-code");

      if (index != null) {
        var _flag = false;
        if (this.optionNameValue && this.selectedOptionInfoItem.length > 0) {
          _flag = true;

          var optionName = [];
          var optionValue = [];
          var valueCode = "";
          this.selectedOptionInfoItem.forEach(function (el, index) {
            optionValue.push(el.value);
            valueCode += el.id + ",";
          });
          if (_flag) {
            vm.addSkuOptionLine[index] = {
              optionName: vm.optionNameValue,
              optionValue: optionValue,
              nameCode: vm.optionNameId,
              valueCode: valueCode.substring(0, valueCode.length - 1)
            };
            vm.resetOption();
            vm.showSearchBox = false;
            __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].hideOverlay();
          }
        } else {
          this.$alert("optionValue不能为空", {
            confirmButtonText: "确定"
          });
        }
      } else {
        var _flag2 = false;
        if (this.optionNameValue && this.selectedOptionInfoItem.length > 0) {
          _flag2 = true;
          var _optionValue = [];
          var _valueCode = "";
          this.selectedOptionInfoItem.forEach(function (el, index) {
            _optionValue.push(el.value);
            _valueCode += el.id + ",";
          });

          vm.addSkuOptionLine.forEach(function (element, index) {
            if (element["nameCode"] == vm.optionNameId) {
              _flag2 = false;
              vm.$alert("已经存在相同的optionName", {
                confirmButtonText: "确定"
              });
            }
          });
          if (_flag2) {
            vm.addSkuOptionLine.push({
              optionName: vm.optionNameValue,
              optionValue: _optionValue,
              nameCode: vm.optionNameId,
              valueCode: _valueCode.substring(0, _valueCode.length - 1)
            });
            vm.resetOption();
            vm.showSearchBox = false;
            __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].hideOverlay();
          }
        } else {
          this.$alert("optionValue不能为空", {
            confirmButtonText: "确定"
          });
        }
      }
    },
    selectWhatToAdd: function selectWhatToAdd() {
      var vm = this;

      var index = event.currentTarget.getAttribute("data-index");
      var flag = true;
      var select = event.currentTarget;
      if ($(select).parent().hasClass("is_select_option")) {
        $(select).parent().removeClass("is_select_option");
      } else {
        $(select).parent().siblings().each(function (index, el) {
          if ($(this).hasClass("is_select_option")) {
            $(this).removeClass("is_select_option");
          }
        });
        $(select).parent().addClass("is_select_option");
      }
    },
    addOptionToLsits: function addOptionToLsits() {
      var defaultOption = ["Kr", "Cn", "En", "Ja"];
      var addOption = [];
      var addStr = "";
      var html = "";
      var id = "";
      var vm = this;
      if (this.optionInfoItem == null) {
        this.optionInfoItem = {};
      }
      for (var key in this.optionInfoItem) {
        id = key;
      }
      $(".option-add").find("input").each(function (index, el) {
        $(this).data("default", defaultOption[index]);
        $(this).val() ? addOption.push($(this).val()) : addOption.push($(this).data("default"));
      });
      addStr = addOption.join("/");

      if (addStr != "Kr/Cn/En/Ja") {
        var dataID = 0;
        this.AddOptionPostData.optNameCode = this.optionNameId;
        this.AddOptionPostData.optValues.push({
          KR: addOption[0],
          CN: addOption[1],
          EN: addOption[2],
          JP: addOption[3]
        });

        var postData = __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(this.AddOptionPostData);
        console.log(this.AddOptionPostData);
        if (this.AddOptionPostData.optNameCode) {
          $.post(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].addNewOptionValue(), postData, function (d) {
            var data = eval("(" + d + ")");
            if (data.code == 200) {
              id = __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(data.data)[0];
              console.log(id);
              vm.$set(vm.optionInfoItem, parseInt(id), {
                ALL_VAL: addStr,
                CODE: parseInt(id)
              });
              console.log(vm.optionInfoItem);
              dataID = parseInt(id);
              console.log(vm.AddOptionPostData);
              console.log(vm.selectedOptionInfoItem);
              vm.selectedOptionInfoItem.push({
                id: dataID,
                value: addStr
              });

              setTimeout(function () {
                $(".option-info-item").each(function (index, el) {
                  if (el.getAttribute("data-code") == id) {
                    $(el).addClass("active-option");
                  }
                });
              }, 50);
            } else {
              vm.$message({
                showClose: true,
                message: data.msg,
                type: "error"
              });
            }
          });
        }
      } else {
        this.$alert("添加的optionValue不能全为空", {
          confirmButtonText: "确定"
        });
      }
      $('.option-add').find('.el-input .el-input__inner').each(function () {
        $(this).val('');
      });
      vm.$set(vm.AddOptionPostData, "optValues", []);
    },
    chooseOptionItem: function chooseOptionItem(event) {
      var vm = this;
      var id = event.currentTarget.getAttribute("data-code");
      $(event.currentTarget).toggleClass("active-option");
      if ($(event.currentTarget).hasClass("active-option")) {
        vm.selectedOptionInfoItem.push({
          value: $(event.currentTarget).text(),
          id: event.currentTarget.getAttribute("data-code")
        });
      } else {
        vm.selectedOptionInfoItem.forEach(function (el, index) {
          if (el.id == id) {
            vm.selectedOptionInfoItem.splice(index, 1);
          }
        });
      }
    },
    delOptionValue: function delOptionValue() {
      var vm = this;
      var id = event.currentTarget.parentNode.getAttribute("data-code");

      for (var _key in vm.optionValueAll) {
        if (vm.optionValueAll[_key].CODE == id) {
          vm.selectedOptionInfoItem.forEach(function (el, ind) {
            if (el.id == id) {
              vm.selectedOptionInfoItem.splice(ind, 1);
            }
          });
        }
      }
      $(".option-info .option-info-item").each(function (index, el) {
        if (el.getAttribute("data-code") == id) {
          $(el).removeClass("active-option");
        }
      });
    },
    openBackText: function openBackText() {
      this.showBackText = true;
    },
    cancelBackText: function cancelBackText() {
      this.showBackText = false;
    },
    showGudsBasic: function showGudsBasic() {
      var vm = this;
      vm.showGudsBasicAJAX = $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].showGudsBasic(vm.gudsId), function (data, textStatus) {});
      $.when(vm.getBasicOptionsAJAX, vm.showGudsBasicAJAX).then(function (data1, data2) {
        if (data1[0].code == 200 && data2[0].code == 2000) {
          vm.currency = data1[0].data.currency;
          vm.originPlace = data1[0].data.origin;
          var gudsInfo = data2[0].data;
          var langCode = __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(gudsInfo.guds.common.lang);
          vm.langListContent = gudsInfo.guds.common.lang;
          var time01 = 0;
          var time02 = 0;
          vm.langCode = langCode;
          vm.dataContent = data2[0].data;
          vm.checkName = gudsInfo.guds.common.chkStatus;
          console.log(vm.checkName);
          if (gudsInfo.guds.common.chkStatus == "N000420100") {
            vm.isReviewing = false;
          } else if (gudsInfo.guds.common.chkStatus == "N000420300" || gudsInfo.guds.common.chkStatus == "N000420400") {
            vm.isReviewing = true;
            vm.reviewStatus = false;
            vm.editStatus = false;
            vm.showStatus = true;
          }
          vm.isFEStatus = gudsInfo.guds.common.isFrontProduct;
          vm.mainId = gudsInfo.guds.common.mainId;
          vm.brandId = gudsInfo.guds.common.brandId;

          if (gudsInfo.guds.common.saleChannel == null) {
            vm.checkListPlatform = ["全部"];
          } else if (gudsInfo.guds.common.saleChannel.split(",").length != __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.PlatformList).length && gudsInfo.guds.common.saleChannel != "") {
            var arr = gudsInfo.guds.common.saleChannel.split(",");
            vm.checkedPlatformCode = arr;
            vm.checkListPlatform = [];
            vm.checkedAllPlatform = false;
            vm.checkedPlatformCode.forEach(function (element, index) {
              vm.checkListPlatform.push(gudsInfo.saleChannel[element]);
            });
          } else {
            vm.checkListPlatform = ["全部"];
          }

          vm.drawbackPercentCode = gudsInfo.guds.common.refundTaxRate;
          vm.drawbackPercent = gudsInfo.guds.common.refundTaxRate ? vm.drawbackList[gudsInfo.guds.common.refundTaxRate]["CD_VAL"] : "";

          vm.goodsTypeCode = gudsInfo.guds.common.productType;
          vm.goodsType = gudsInfo.guds.common.productType ? gudsInfo.productType[gudsInfo.guds.common.productType] : "";

          vm.productFlag = gudsInfo.guds.common.productFlag;
          vm.drawback = gudsInfo.guds.common.isRefundTax;

          vm.bondedWarehouseCode = gudsInfo.guds.common.overseasTax;
          vm.bondedWarehouse = gudsInfo.guds.common.overseasTax ? vm.bondedWarehouseList[gudsInfo.guds.common.overseasTax]["CD_VAL"] : "";
          vm.packingNum = parseInt(gudsInfo.guds.common.PCS);

          vm.minNum = gudsInfo.guds.common.minBuyNum || "";
          vm.maxNum = gudsInfo.guds.common.maxBuyNum || "";

          vm.logisticsCategoryCode = gudsInfo.guds.common.expressCat;
          vm.logisticsCategory = gudsInfo.guds.common.expressCat ? vm.logisticsCategoryList[gudsInfo.guds.common.expressCat]["CD_VAL"] : "";

          vm.logisticsTypesCode = gudsInfo.guds.common.expressType;
          vm.logisticsTypes = gudsInfo.guds.common.expressType ? vm.logisticsTypesList[gudsInfo.guds.common.expressType]["CD_VAL"] : "";

          if (gudsInfo.gudsDescData && gudsInfo.gudsDescData != null || gudsInfo.gudsDescData.length > 0) {
            vm.goodsDetailContent.desc = gudsInfo.gudsDescData;
          }

          vm.goodsShelfLife = gudsInfo.guds.common.isShelflife;

          vm.brandNameValue = gudsInfo.guds.common.brandName;

          if (vm.dataContent.guds.common.cateId && vm.dataContent.brandList.list.hasOwnProperty(vm.dataContent.guds.common.cateId)) {
            vm.brandCategoryValue = vm.dataContent.brandList.list[vm.dataContent.guds.common.cateId].allVal;
            vm.cateId = vm.dataContent.guds.common.cateId;
          }
          vm.categoryCode = vm.dataContent.guds.common.gudsCat;
          vm.brandCat = vm.brandCategoryValue;
          if (vm.dataContent.guds.common.catLev1 && vm.dataContent.brandList.list.hasOwnProperty(vm.dataContent.guds.common.catLev1)) {
            vm.categoryLevelCode01 = vm.dataContent.guds.common.catLev1;
            vm.categoryLevel01 = vm.dataContent.brandList.list[vm.dataContent.guds.common.catLev1].allVal;
          }
          if (vm.dataContent.guds.common.catLev2 && vm.dataContent.brandList.list.hasOwnProperty(vm.dataContent.guds.common.catLev2)) {
            vm.categoryLevelCode02 = vm.dataContent.guds.common.catLev2;
            vm.categoryLevel02 = vm.dataContent.brandList.list[vm.dataContent.guds.common.catLev2].allVal;
          }
          if (vm.dataContent.guds.common.catLev3 && vm.dataContent.brandList.list.hasOwnProperty(vm.dataContent.guds.common.catLev3)) {
            vm.categoryLevelCode03 = vm.dataContent.guds.common.catLev3;
            vm.categoryLevel03 = vm.dataContent.brandList.list[vm.dataContent.guds.common.catLev3].allVal;
          }

          vm.goodsUnitValue = vm.dataContent.guds.common.unit ? vm.goodsUnit[vm.dataContent.guds.common.unit] : "";
          vm.goodsUnitID = vm.dataContent.guds.common.unit;
          vm.checkList = [];

          vm.currencyId = vm.dataContent.guds.common.priceType;
          if (vm.dataContent.guds.common.productFlag != "" && vm.dataContent.guds.common.productFlag != null) {
            vm.FETag = vm.dataContent.guds.common.productFlag;
          }
          vm.currencyValue = vm.currencyId ? vm.currency[vm.currencyId]["CD_VAL"] : "";
          vm.originPlaceId = vm.dataContent.guds.common.gudsOrgp;

          vm.originPlace.forEach(function (ele, index) {
            if (ele.id == vm.originPlaceId) {
              vm.originPlaceValue = vm.originPlace[index]["zh_name"];
            }
          });
          vm.categoryLevel = vm.dataContent.brandList.cateStru;
          vm.brandCategory = vm.dataContent.brandList.list;

          vm.backText = vm.dataContent.guds.common.remark;
          setTimeout(function () {
            langCode.forEach(function (ele, index) {
              if (vm.isFEStatus) {}
              vm.checkList.push(vm.langList[ele]["cn"]);

              if (ele == "N000920100") {
                vm.modifyTitle = "chineseTitle";
                vm.cnLanguage = false;
                vm.modifySubTitle = "chineseSubtitle";

                vm.cnDetails = gudsInfo.guds.common.lang[ele]["detail"];
                vm.cnPic = gudsInfo.guds.common.lang[ele]["img"];
                vm.chineseTitle = vm.dataContent.guds.common.lang[ele].gudsName;
                if (vm.dataContent.guds.common.lang[ele].gudsSubName) {
                  vm.chineseSubtitle = vm.dataContent.guds.common.lang[ele].gudsSubName;
                  vm.hasSubtitle = true;
                }
              } else if (ele == "N000920400") {
                vm.modifyTitle = "koreaTitle";
                vm.modifySubTitle = "koreaSubtitle";
                vm.krLanguage = false;

                vm.krDetails = gudsInfo.guds.common.lang[ele]["detail"];
                vm.krPic = gudsInfo.guds.common.lang[ele]["img"];

                vm.koreaTitle = vm.dataContent.guds.common.lang[ele].gudsName;
                if (vm.dataContent.guds.common.lang[ele].gudsSubName) {
                  vm.koreaSubtitle = vm.dataContent.guds.common.lang[ele].gudsSubName;
                  vm.hasSubtitle = true;
                }
              } else if (ele == "N000920200") {
                vm.modifyTitle = "englishTitle";
                vm.modifySubTitle = "englishSubtitle";
                vm.enLanguage = false;

                vm.enPic = gudsInfo.guds.common.lang[ele]["img"];
                vm.enDetails = gudsInfo.guds.common.lang[ele]["detail"];

                vm.englishTitle = vm.dataContent.guds.common.lang[ele].gudsName;
                if (vm.dataContent.guds.common.lang[ele].gudsSubName) {
                  vm.englishSubtitle = vm.dataContent.guds.common.lang[ele].gudsSubName;
                  vm.hasSubtitle = true;
                }
              } else if (ele == "N000920300") {
                vm.modifyTitle = "japanTitle";
                vm.modifySubTitle = "japanSubtitle";
                vm.jaLanguage = false;

                vm.jaDetails = gudsInfo.guds.common.lang[ele]["detail"];
                vm.jaPic = gudsInfo.guds.common.lang[ele]["img"];
                vm.japanTitle = vm.dataContent.guds.common.lang[ele].gudsName;
                if (vm.dataContent.guds.common.lang[ele].gudsSubName) {
                  vm.japanSubtitle = vm.dataContent.guds.common.lang[ele].gudsSubName;
                  vm.hasSubtitle = true;
                }
              }
            });
          }, 50);

          $(".img-content").each(function (index, el) {
            if (el.getAttribute("data-code") == langCode) {
              $(el).children("img").attr("src", vm.dataContent.guds.common.lang[langCode].img);
              $(el).next().next().find("a").show();
            }
          });

          vm.getBrandInfo();
        }
      }).fail(function (a, b) {});
    },
    showGudsSKU: function showGudsSKU() {
      var vm = this;
      vm.finishLoad = false;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].showGudsSKU(vm.mainId, vm.brandId, vm.gudsId), function (data, textStatus) {
        if (data.code == 200) {
          var optionName = [];
          __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_object_values___default()(data.data.selector).forEach(function (element, index) {
            optionName.push(element.optName[element.optNameCode]);
          });
          vm.optionNameObj = optionName.join(",");
          vm.optionList = data.data.optionList ? data.data.optionList : [];
          vm.optionList.forEach(function (element, index) {
            vm.skuFeatureContent[index] = vm.cloneObj(vm.skuFeature);
            for (var key in vm.skuFeatureContent[index]) {
              vm.skuFeatureContent[index][key]["radioVal"] = element["CUSTOMS_LOGISTICS"][key];
            }
          });
          vm.options = data.data.selector;
          __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_object_values___default()(vm.options).forEach(function (element, index) {
            var arr = [];
            element.optValueValue.forEach(function (ele, ind) {
              arr.push(ele.join("/"));
            });
            vm.$set(vm.addSkuOptionLine, index, {
              nameCode: element.optNameCode,
              optionName: element.optName[element.optNameCode],
              optionValue: arr,
              valueCode: element.optValueCode.join(",")
            });
          });

          vm.optionList.forEach(function (element, index) {
            var v = "";
            if (Object.prototype.hasOwnProperty.call(vm.saleStatusList, element.GUDS_OPT_SALE_STAT_CD)) {
              v = vm.saleStatusList[element.GUDS_OPT_SALE_STAT_CD]["CD_VAL"];
            } else {
              v = "";
            }
            vm.$set(vm.saleStatu.sale, index, {
              code: element.GUDS_OPT_SALE_STAT_CD,
              value: v
            });

            vm.UPCMain[index] = element["GUDS_OPT_UPC_ID"];
          });
        } else {
          vm.$alert(data.msg, { confirmButtonText: "确定" });
        }
        vm.finishLoad = true;
      });
    },
    getUnit: function getUnit() {
      var vm = this;
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getBrand(),
        type: "GET",
        dataType: "json"
      }).done(function (data) {
        vm.brandName = data.data.brandList;
        vm.goodsUnit = data.data.unit;
        vm.goodsTypeList = data.data.productType;
        vm.goodsDesList = data.data.productDesc;
        vm.langList = data.data.lang;
      }).fail(function () {
        vm.$message({ showClose: true, message: "error", type: "error" });
      }).always(function () {});
    },
    getBasicOptions: function getBasicOptions() {
      var vm = this;
      vm.getBasicOptionsAJAX = $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getBasicOptions(),
        type: "GET",
        dataType: "json"
      }).done(function (data) {
        if (data.code == 200) {
          vm.currency = data.data.currency;
          vm.originPlace = data.data.origin;
          vm.optionName = data.data.options;

          vm.PlatformList = data.data.platform;
          vm.drawbackList = data.data.refundRate;
          vm.bondedWarehouseList = data.data.crossBoardRate;
          vm.logisticsCategoryList = data.data.expressCat;
          vm.logisticsTypesList = data.data.expressType;
          vm.saleStatusList = data.data.saleState;
          vm.warehouseList = data.data.warehouse;
          vm.skuFeature = data.data.skuFeature;
          for (var key in vm.skuFeature) {
            vm.$set(vm.skuFeature[key], "radioVal", "");
          }
          vm.skuFeatureAll = vm.cloneObj(vm.skuFeature);
        } else {
          vm.$alert(data.msg, { confirmButtonText: "确定" });
        }
      }).fail(function () {
        vm.$message({ showClose: true, message: "error", type: "error" });
      }).always(function () {});
    },
    updatePic: function updatePic() {
      var vm = this;
      var lang = event.currentTarget.className;
      var langContent = event.currentTarget.className;
      var loading = event.currentTarget.getAttribute("data-loading");

      var data = new FormData();

      data.append("file", $(event.currentTarget)[0]["files"][0]);

      if ($(event.currentTarget).val() && /\.(gif|jpg|jpeg|png|GIF|JPG|PNG|bmp|BMP)$/.test($(event.currentTarget).val())) {
        vm[loading] = true;
        $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].updatePic(),
          type: "POST",
          dataType: "JSON",
          contentType: false,
          processData: false,
          data: data,
          cache: false
        }).success(function (data) {
          if (data.code == 2000) {
            vm.langContent[langContent] = data.data;
            vm[lang] = data.data.cdnAddr;
            vm[loading] = false;
          } else {
            vm.$alert("data.msg", { confirmButtonText: "确定" });
          }
        }).error(function () {
          console.log("error");
        }).complete(function () {});
      } else {
        vm.$alert("图片类型必须是gif,jpeg,jpg,png,bmp中的一种", { confirmButtonText: "确定" });
      }
      $(event.currentTarget).val('');
    },
    submitReview: function submitReview() {
      var vm = this;
      var postData = {
        gudsId: vm.gudsId,
        mainId: vm.mainId,
        status: "N000420100",
        content: ""
      };
      vm.doReview(postData, "已经成功提交审核", true, false);
    },
    passReview: function passReview() {
      var vm = this;
      var postData = {
        gudsId: vm.gudsId,
        mainId: vm.mainId,
        status: "N000420400",
        content: ""
      };
      vm.doReview(postData, "完成审核", true, false);
    },
    backToReview: function backToReview() {
      var vm = this;
      var postData = {
        gudsId: vm.gudsId,
        mainId: vm.mainId,
        status: "N000420300",
        content: $(".open-bac-text").find("textarea").val()
      };
      vm.doReview(postData, "完成审核", true, true);
    },
    cancelSave: function cancelSave() {
      this.skuPrice = "";
      this.skuLength = "";
      this.skuHeight = "";
      this.skuWeight = "";
      this.skuWidth = "";

      this.showGudsBasic();

      this.showGudsSKU();

      this.showStatus = true;
      this.editStatus = false;
    },
    checkPlatform: function checkPlatform() {
      var vm = this;
      var value = event.srcElement.defaultValue;
      var result = __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].checkBox(vm.checkedPlatformCode, vm.checkedAllPlatform, vm.checkListPlatform, value, $(".erp-platform-info input"));
      vm.checkedPlatformCode = result[0];
      vm.checkedAllPlatform = result[1];
      vm.checkListPlatform = result[2];
    },
    CheckAllPlatform: function CheckAllPlatform() {
      var vm = this;
      var result = __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].checkBoxAll(vm.checkedPlatformCode, vm.checkedAllPlatform, vm.checkListPlatform);
      vm.checkedPlatformCode = result[0];
      vm.checkedAllPlatform = result[1];
      vm.checkListPlatform = result[2];
    },
    isTrue: function isTrue(str) {
      return str == "Y" ? 1 : 0;
    },
    selectDes: function selectDes() {
      if (event.type == "click") {
        this.goodsDesCode = event.currentTarget.getAttribute("data-code");
      }
    },
    getGoodsType: function getGoodsType() {
      if (event.type == "click") {
        this.goodsTypeCode = event.currentTarget.getAttribute("data-code");
        $(".goods-type input").css("borderColor", "#bfcbd9");
      }
    },
    selectDrawback: function selectDrawback() {
      if (event.type == "click") {
        this.drawbackPercentCode = event.currentTarget.getAttribute("data-code");
        $(".drawback-per input").css("borderColor", "#bfcbd9");
      }
    },
    selectbondedWarehouse: function selectbondedWarehouse() {
      if (event.type == "click") {
        this.bondedWarehouseCode = event.currentTarget.getAttribute("data-code");
      }
    },
    selectLogisticsCategory: function selectLogisticsCategory() {
      if (event.type == "click") {
        this.logisticsCategoryCode = event.currentTarget.getAttribute("data-code");
      }
    },
    selectLogisticsTypes: function selectLogisticsTypes() {
      if (event.type == "click") {
        this.logisticsTypesCode = event.currentTarget.getAttribute("data-code");
      }
    },
    selectSaleStatus: function selectSaleStatus() {
      if (event.type == "click") {
        this.saleStatusCode = event.currentTarget.getAttribute("data-code");
      }
    },
    addGoodsDes: function addGoodsDes() {
      var vm = this;
      var code = vm.goodsDesCode;
      var name = vm.goodsDesValue;
      console.log(vm.goodsDetailContent);
      if (vm.goodsDetailContent.desc && vm.goodsDetailContent.desc.hasOwnProperty(code)) {
        vm.$message({
          showClose: true,
          message: "已经存在的描述",
          type: "error"
        });
      } else {
        vm.$set(vm.goodsDetailContent.desc, code, {
          name: vm.goodsDesValue,
          N000920100: "",
          N000920400: "",
          N000920200: "",
          N000920300: ""
        });
      }
    },
    delDes: function delDes() {
      var vm = this;
      var code = event.currentTarget.getAttribute("data-code");
      vm.$delete(vm.goodsDetailContent.desc, code);
    },
    checkDrawBack: function checkDrawBack(value) {

      if (value == "Y") {
        this.drawbackAccess = false;
      } else {
        this.drawbackPercent = "0";
        this.drawbackAccess = true;
      }
    },
    selectWareHouse: function selectWareHouse() {
      var index = event.currentTarget.getAttribute("data-index");
      this.$set(this.warehouseCode, index, event.currentTarget.getAttribute("data-code"));
    },
    setInfo: function setInfo() {
      this.dialogUnitVisible = true;
    },
    doSettingUnit: function doSettingUnit() {
      this.skuWidth = this.skuWidthTemp;
      this.skuLength = this.skuLengthTemp;
      this.skuWeight = this.skuWeightTemp;
      this.skuHeight = this.skuHeightTemp;
      this.dialogUnitVisible = false;
    },
    setSaleStatus: function setSaleStatus() {
      this.dialogSaleVisible = true;
    }
  }, __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "setCustomsInfo", function setCustomsInfo() {
    this.dialogCustomInfoVisible = true;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "doSettingSale", function doSettingSale() {
    this.saleStatusValue = this.saleStatusValueTemp;
    this.dialogSaleVisible = false;
    this.saleStatusValueTemp = "";
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "doSettingCustom", function doSettingCustom() {
    this.declarationValue = this.declarationValueTemp;
    for (var key in this.skuFeatureContent) {
      this.skuFeatureContent[key] = this.cloneObj(this.skuFeatureAll);
    }

    this.declarationValueTemp = "";
    this.$message({
      showClose: true,
      type: "success",
      message: "海关申报信息批量设置成功"
    });
    this.dialogCustomInfoVisible = false;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "editCustomInfo", function editCustomInfo(event, index) {
    var upc = "";
    this.currentRow = index;
    this.skuFeatureTemp = __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign___default()({}, this.skuFeatureContent[index]);

    this.currentBtn = $(event.currentTarget);


    this.hsUpcCode = this.currentBtn.nextAll(".custom-content").find(".upc-content").html();
    this.declarationValueItem = this.currentBtn.nextAll(".custom-content").find(".declarationValue").html();
    upc = this.currentBtn.parents("tr").find(".upc-value").attr("title");
    if (upc.split(",").length > 1) {
      this.isMutipleUpc = false;
    } else {
      this.isMutipleUpc = true;
    }
    this.dialogCustomItemVisible = true;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "doSettingCustomItem", function doSettingCustomItem() {
    this.currentBtn.nextAll(".custom-content").find(".declarationValue").text(this.declarationValueItem).end().find(".upc-content").text(this.hsUpcCode);

    this.skuFeatureContent[this.currentRow] = __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_assign___default()({}, this.skuFeatureTemp);
    this.declarationValueItem = "";
    this.hsUpcCode = "";
    this.skuFeatureTemp = this.skuFeature;
    this.dialogCustomItemVisible = false;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "selectItemSaleStatus", function selectItemSaleStatus(value, index) {
    if (event.type == "click") {
      var code = event.currentTarget.getAttribute("data-code");
      if (code) {
        this.saleStatu.sale[index]["code"] = code;
      }
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "getOptionPrice", function getOptionPrice() {
    var vm = this;
    vm.finishLoad = false;
    $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getOptionPrice(vm.mainId), function (json, textStatus) {
      vm.optionPrice = json.data.skuInfo;
      vm.optionsShow = vm.optionPrice[__WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_keys___default()(vm.optionPrice)[0]];
      vm.priceGroup = json.data.priceGroup;
      for (var key in vm.priceGroup) {
        vm.$set(vm.warehousePriceLink, key, []);
        vm.priceGroup[key].forEach(function (element, index) {
          if (element.warehouse) {
            var k = element.warehouse;
            var name = vm.warehouseList[k]["CD_VAL"];
            vm.warehousePriceLink[key].push({
              id: element.id,
              warehouse: k,
              name: name,
              marketPrice: element.marketPrice,
              grossProfitMargin: element.grossProfitMargin,
              purchasePrice: element.purchasePrice,
              realPrice: element.realPrice
            });
          }
        });
      }
      vm.finishLoad = true;
    });
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "setPriceInfo", function setPriceInfo() {
    this.dialogPriceVisible = true;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "doSettingPrice", function doSettingPrice() {
    var vm = this;
    vm.purchasePriceTemp = vm.purchasePrice;
    vm.marketPriceTemp = vm.marketPrice;
    vm.grossInterestRateTemp = vm.grossInterestRate;
    vm.salePriceTemp = vm.salePrice;
    vm.dialogPriceVisible = false;
    $(".erp-sku-info").find(".purchasing-price").each(function () {
      $(this).find("input").val(vm.purchasePriceTemp);
    });
    $(".erp-sku-info").find(".gross-interest-rate").each(function () {
      $(this).find("input").val(vm.grossInterestRateTemp);
    });
    $(".erp-sku-info").find(".sale-price").each(function () {
      $(this).find("input").val(vm.salePriceTemp);
    });
    $(".erp-sku-info").find(".market-price").each(function () {
      $(this).find("input").val(vm.marketPriceTemp);
    });
    vm.purchasePrice = "";
    vm.marketPrice = "";
    vm.grossInterestRate = "";
    vm.salePrice = "";
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "addWareHouse", function addWareHouse() {
    var vm = this;
    var index = $(event.currentTarget).prev(".el-select").attr("data-index");

    if (vm.toFEStatus) {
      var _flag3 = true;

      var skuid = event.currentTarget.getAttribute("data-index");


      if (!vm.warehousePriceLink.hasOwnProperty(skuid)) {
        vm.$set(vm.warehousePriceLink, skuid, []);
      }
      if (!Object.prototype.hasOwnProperty.call(vm.warehouseCode, skuid)) {
        _flag3 = false;
        vm.$message({
          showClose: true,
          message: "请先选择仓库",
          type: "error"
        });
      } else {
        vm.warehousePriceLink[skuid].forEach(function (element, index) {
          if (vm.warehouseCode[skuid] == element.warehouse) {
            _flag3 = false;
            vm.$message({
              showClose: true,
              message: "已经存在的仓库",
              type: "error"
            });
          }
        });
      }
      if (_flag3) {
        vm.warehousePriceLink[skuid].push({
          warehouse: vm.warehouseCode[index],
          name: vm.warehouseList[vm.warehouseCode[index]].CD_VAL
        });
      }
    } else if (!vm.toFEStatus && vm.isFEStatus) {
      var _skuid = event.currentTarget.getAttribute("data-index");
      var _flag4 = true;

      if (!vm.warehousePriceLink.hasOwnProperty(_skuid)) {
        vm.$set(vm.warehousePriceLink, _skuid, []);
      }
      if (vm.warehousePriceLink[_skuid]) {
        if (!Object.prototype.hasOwnProperty.call(vm.warehouseCode, _skuid)) {
          _flag4 = false;
          vm.$message({
            showClose: true,
            message: "请先选择仓库",
            type: "error"
          });
        } else {
          vm.warehousePriceLink[_skuid].forEach(function (element, index) {
            if (vm.warehouseCode[_skuid] == element.warehouse) {
              _flag4 = false;
              vm.$message({
                showClose: true,
                message: "已经存在的仓库",
                type: "error"
              });
            }
          });
        }
      }
      if (_flag4) {
        vm.warehousePriceLink[_skuid].push({
          warehouse: vm.warehouseCode[index],
          name: vm.warehouseList[vm.warehouseCode[index]].CD_VAL
        });
      }
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "delWareHouse", function delWareHouse() {
    var vm = this;
    var index = event.currentTarget.getAttribute("data-index");
    var id = event.currentTarget.getAttribute("data-id");
    var delCode = $(event.currentTarget).prev("span").attr("data-code");

    if (id) {
      if (vm.warehousePriceLink[index].length == 1) {
        vm.$confirm("该SKU只有一条数据价格数据，删除价格等同于下架SKU，确定删除么？", "提示", {
          confirmButtonText: "确定",
          cancelButtonText: "取消",
          type: "warning"
        }).then(function () {
          $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].delPrice(id, vm.mainId, index), function (json, textStatus) {
            if (json.code == 200) {
              vm.warehousePriceLink[index].forEach(function (element, i) {
                if (element.warehouse == delCode) {
                  vm.$delete(vm.warehousePriceLink[index], i);
                }
              });
              vm.$message({
                type: "success",
                message: "删除成功!"
              });
            } else {
              vm.$message({
                showClose: true,
                message: json.msg,
                type: "error"
              });
            }
          });
        }).catch(function () {
          vm.$message({
            type: "info",
            message: "已取消删除"
          });
        });
      } else {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].delPrice(id, vm.mainId, index), function (json, textStatus) {
          if (json.code == 200) {
            vm.warehousePriceLink[index].forEach(function (element, i) {
              if (element.warehouse == delCode) {
                vm.$delete(vm.warehousePriceLink[index], i);
                vm.$message({
                  type: "success",
                  message: "删除成功!"
                });
              }
            });
          } else {
            vm.$message({
              showClose: true,
              message: json.msg,
              type: "error"
            });
          }
        });
      }
    } else {
      if (vm.warehousePriceLink[index].length == 1) {
        vm.$confirm("该SKU只有一条数据价格数据，删除价格等同于下架SKU，确定删除么？", "提示", {
          confirmButtonText: "确定",
          cancelButtonText: "取消",
          type: "warning"
        }).then(function () {
          vm.warehousePriceLink[index].forEach(function (element, i) {
            if (element.warehouse == delCode) {
              vm.$delete(vm.warehousePriceLink[index], i);
            }
          });
          vm.$message({
            type: "success",
            message: "删除成功!"
          });
        }).catch(function () {
          vm.$message({
            type: "info",
            message: "已取消删除"
          });
        });
      } else {
        vm.warehousePriceLink[index].forEach(function (element, i) {
          if (element.warehouse == delCode) {
            vm.$delete(vm.warehousePriceLink[index], i);
          }
        });
      }
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "BEtoFE", function BEtoFE() {
    this.toggleStatusSave();
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorBlurCN", function onEditorBlurCN(editor) {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorFocusCN", function onEditorFocusCN(editor) {
    this.cneditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorReadyCN", function onEditorReadyCN(editor) {
    this.cneditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorBlurEN", function onEditorBlurEN(editor) {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorFocusEN", function onEditorFocusEN(editor) {
    this.eneditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorReadyEN", function onEditorReadyEN(editor) {
    this.eneditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorBlurKR", function onEditorBlurKR(editor) {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorFocusKR", function onEditorFocusKR(editor) {
    this.kreditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorReadyKR", function onEditorReadyKR(editor) {
    this.kreditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorBlurJA", function onEditorBlurJA(editor) {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorFocusJA", function onEditorFocusJA(editor) {
    this.jaeditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorReadyJA", function onEditorReadyJA(editor) {
    this.jaeditor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "upload", function upload(event) {
    var vm = this;
    var data = new FormData();

    data.append("file", $(event.currentTarget)[0]["files"][0]);

    $.ajax({
      url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].updatePic(),
      type: "POST",
      dataType: "JSON",
      contentType: false,
      processData: false,
      data: data,
      cache: false
    }).done(function (data) {
      if (data.code == 2000) {
        vm.contentImg = data.data.cdnAddr;

        vm.currentEditor.insertEmbed(vm.length, "image", vm.contentImg);
        data = "";
      } else {
        vm.$message({
          type: "error",
          message: data.data
        });
      }
    }).fail(function () {
      console.log("error");
    }).always(function () {});
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "customButtonClick", function customButtonClick() {
    var id = event.currentTarget.getAttribute("data-id");
    var range = this[id].getSelection(true);
    var length = range.index;
    this.currentEditor = this[id];
    this.length = length;

    $(event.currentTarget).next(".custom-input").click();
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "rateBlur", function rateBlur() {
    var vm = this;
    if (vm.grossInterestRate != "") {
      vm.grossInterestRate = parseFloat(vm.grossInterestRate).toFixed(2);
    }
    if (vm.purchasePrice != "" && vm.grossInterestRate != "") {
      var sale = parseFloat(vm.purchasePrice) * (1 + parseFloat(vm.grossInterestRate) / 100);
      vm.salePrice = sale.toFixed(2);
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "saleBlur", function saleBlur() {
    var vm = this;
    if (vm.salePrice != "") {
      vm.salePrice = parseFloat(vm.salePrice).toFixed(2);
    }
    if (vm.purchasePrice != "" && vm.salePrice != "") {
      var rate = (parseFloat(vm.salePrice) / parseFloat(vm.purchasePrice) - 1) * 100;
      vm.grossInterestRate = rate.toFixed(2);
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "purchaseBlur", function purchaseBlur() {
    var vm = this;
    if (vm.purchasePrice != "") {
      vm.purchasePrice = parseFloat(vm.purchasePrice).toFixed(2);
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "marketBlur", function marketBlur() {
    var vm = this;
    if (vm.marketPrice != "") {
      vm.marketPrice = parseFloat(vm.marketPrice).toFixed(2);
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "checkNum", function checkNum() {
    var vm = this;
    var v = parseFloat($(event.currentTarget).val()).toFixed(2);
    if ($(event.currentTarget).val()) {
      $(event.currentTarget).val(v);
      $(event.currentTarget)[0].dispatchEvent(new Event("input"));
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "countSale", function countSale() {
    var vm = this;
    var rate = parseFloat($(event.currentTarget).val()).toFixed(2);
    var pur = $(event.currentTarget).parents(".item-content").prev(".item-content").find("input").val();
    var $saleDom = $(event.currentTarget).parents(".item-content").next(".item-content").find("input");
    var salePrice = "";
    if ($(event.currentTarget).val()) {
      $(event.currentTarget).val(rate);
      $(event.currentTarget)[0].dispatchEvent(new Event("input"));
      salePrice = parseFloat(pur) * (1 + rate / 100);
      $saleDom.val(salePrice.toFixed(2));
      $saleDom[0].dispatchEvent(new Event("input"));
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "countRate", function countRate() {
    var vm = this;
    var $rateDom = $(event.currentTarget).parents(".item-content").prev(".item-content").find("input");
    var sale = parseFloat($(event.currentTarget).val()).toFixed(2);
    var pur = $(event.currentTarget).parents(".warehouse-set").find(".purchasing-price-content").find("input").val();

    var rate = "";
    if ($(event.currentTarget).val()) {
      $(event.currentTarget).val(sale);
      $(event.currentTarget)[0].dispatchEvent(new Event("input"));
      rate = (sale / parseFloat(pur) - 1) * 100;

      $rateDom.val(rate.toFixed(2));
      $rateDom[0].dispatchEvent(new Event("input"));
    }
  }), _methods),
  computed: {
    cneditor: function cneditor() {
      return this.$refs.cneditor.quill;
    },
    eneditor: function eneditor() {
      return this.$refs.eneditor.quill;
    },
    kreditor: function kreditor() {
      return this.$refs.kreditor.quill;
    },
    jaeditor: function jaeditor() {
      return this.$refs.jaeditor.quill;
    }
  },
  watch: {
    skuPrice: function skuPrice() {
      var vm = this;
      if (vm.skuPrice) {
        vm.optionList.forEach(function (element, index) {
          element.GUDS_OPT_ORG_PRC = vm.skuPrice;
        });
      }
    },
    skuLength: function skuLength() {
      var vm = this;
      if (vm.skuLength) {
        vm.optionList.forEach(function (element, index) {
          element.GUDS_OPT_LENGTH = vm.skuLength;
        });
      }
    },
    skuHeight: function skuHeight() {
      var vm = this;
      if (vm.skuHeight) {
        vm.optionList.forEach(function (element, index) {
          element.GUDS_OPT_HEIGHT = vm.skuHeight;
        });
      }
    },
    skuWeight: function skuWeight() {
      var vm = this;
      if (vm.skuWeight) {
        vm.optionList.forEach(function (element, index) {
          element.GUDS_OPT_WEIGHT = vm.skuWeight;
        });
      }
    },
    skuWidth: function skuWidth() {
      var vm = this;
      if (vm.skuWidth) {
        vm.optionList.forEach(function (element, index) {
          element.GUDS_OPT_WIDTH = vm.skuWidth;
        });
      }
    },

    lithiumBattery: function lithiumBattery() {
      var vm = this;
      $(".erp-sku-info.erp-sku-FE ").find(".custom-content").each(function (index, el) {
        $(this).find(".lithiumBattery").text(vm.lithiumBattery);
      });
    },
    nonLiquidCosmetic: function nonLiquidCosmetic() {
      var vm = this;
      $(".erp-sku-info.erp-sku-FE").find(".custom-content").each(function (index, el) {
        $(this).find(".nonLiquidCosmetic").text(vm.nonLiquidCosmetic);
      });
    },
    pureCell: function pureCell() {
      var vm = this;
      $(".erp-sku-info.erp-sku-FE").find(".custom-content").each(function (index, el) {
        $(this).find(".pureCell").text(vm.pureCell);
      });
    },
    fragile: function fragile() {
      var vm = this;
      $(".erp-sku-info.erp-sku-FE").find(".custom-content").each(function (index, el) {
        $(this).find(".fragile").text(vm.fragile);
      });
    },

    saleStatusValue: function saleStatusValue() {
      var vm = this;
      for (var key in vm.saleStatu.sale) {
        vm.saleStatu.sale[key].value = vm.saleStatusValue;
        vm.saleStatu.sale[key]["code"] = vm.saleStatusCode;
      }
    }

  }
});
/* WEBPACK VAR INJECTION */}.call(__webpack_exports__, __webpack_require__(0)))

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 42:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 44:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "goodsEdit"
  }, [_c('div', {
    attrs: {
      "id": "mask"
    }
  }), _vm._v(" "), _c('div', {
    staticClass: "status-btn"
  }, [_c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus && _vm.isReviewing && !_vm.isFEStatus),
      expression: "showStatus && isReviewing && !isFEStatus"
    }, {
      name: "loading",
      rawName: "v-loading.fullscreen.lock",
      value: (_vm.fullscreenLoading),
      expression: "fullscreenLoading",
      modifiers: {
        "fullscreen": true,
        "lock": true
      }
    }],
    staticClass: "to-FE-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.toggleStatusToFE
    }
  }, [_vm._v("发布为前端商品\n    ")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.toFEStatus && _vm.activeName == 'third'),
      expression: "toFEStatus && activeName =='third'"
    }, {
      name: "loading",
      rawName: "v-loading.fullscreen.lock",
      value: (_vm.fullscreenLoading),
      expression: "fullscreenLoading",
      modifiers: {
        "fullscreen": true,
        "lock": true
      }
    }],
    staticClass: "to-FE-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.BEtoFE
    }
  }, [_vm._v("\n      确认发布为前端商品\n    ")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus && _vm.isReviewing && !_vm.toFEStatus),
      expression: "showStatus && isReviewing && !toFEStatus"
    }],
    staticClass: "edit-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.toggleStatus
    }
  }, [_vm._v("编辑\n    ")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "loading",
      rawName: "v-loading.fullscreen.lock",
      value: (_vm.fullscreenLoading),
      expression: "fullscreenLoading",
      modifiers: {
        "fullscreen": true,
        "lock": true
      }
    }, {
      name: "show",
      rawName: "v-show",
      value: ((_vm.finishLoad && _vm.editStatus && _vm.isReviewing && !_vm.toFEStatus) || (_vm.activeName != 'third' && _vm.toFEStatus && _vm.editStatus)),
      expression: "( finishLoad && editStatus && isReviewing && !toFEStatus)|| (activeName !='third' && toFEStatus && editStatus)"
    }],
    staticClass: "save-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.toggleStatusSave
    }
  }, [_vm._v("保存\n    ")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.finishLoad && _vm.editStatus && !_vm.toFEStatus),
      expression: " finishLoad && editStatus && !toFEStatus"
    }],
    staticClass: "cancel-save",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.cancelSave
    }
  }, [_vm._v("取消编辑\n    ")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.reviewStatus),
      expression: "reviewStatus"
    }],
    staticClass: "back-to-edit",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.openBackText
    }
  }, [_vm._v("退回编辑")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: ((_vm.showStatus && _vm.isReviewing && _vm.activeName == 'second' && !_vm.isFEStatus && (_vm.checkName == 'N000420200')) || (_vm.showStatus && _vm.isReviewing && _vm.activeName == 'third' && _vm.isFEStatus && (_vm.checkName == 'N000420200'))),
      expression: "(showStatus && isReviewing && activeName=='second' && !isFEStatus && (checkName == 'N000420200' )) ||(showStatus && isReviewing && activeName=='third' && isFEStatus && (checkName == 'N000420200'))"
    }],
    staticClass: "submit-review-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.submitReview
    }
  }, [_vm._v("提交审核申请\n    ")]), _vm._v(" "), _c('el-button', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.reviewStatus),
      expression: "reviewStatus"
    }],
    staticClass: "review-pass",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.passReview
    }
  }, [_vm._v("审核通过")])], 1), _vm._v(" "), _c('header', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticStyle: {
      "margin-top": "60px"
    }
  }, [_vm._v("平台信息")]), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "erp-platform-info erp-language-info"
  }, [_c('el-row', {
    staticClass: "row-line row02",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    staticStyle: {
      "width": "100px",
      "background": "#f7f9fb",
      "border-right": "1px solid #cadee7",
      "text-align": "center",
      "height": "50px",
      "line-height": "50px"
    },
    attrs: {
      "span": 1
    }
  }, [_c('div', {
    staticClass: "first-title ",
    staticStyle: {
      "font-size": "13px",
      "color": "#546e7a"
    }
  }, [_vm._v("平台")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 23
    }
  }, [_c('el-checkbox-group', {
    staticClass: "clearfix platform-items",
    staticStyle: {
      "width": "95%"
    },
    model: {
      value: (_vm.checkListPlatform),
      callback: function($$v) {
        _vm.checkListPlatform = $$v
      },
      expression: "checkListPlatform"
    }
  }, [_c('el-checkbox', {
    staticStyle: {
      "margin-left": "15px",
      "width": "105px"
    },
    attrs: {
      "label": "全部",
      "checked": _vm.checkedAllPlatform,
      "disabled": _vm.showStatus || _vm.reviewStatus || _vm.activeName != 'first'
    },
    on: {
      "change": _vm.CheckAllPlatform
    }
  }), _vm._v(" "), _vm._l((_vm.PlatformList), function(value, key) {
    return _c('el-checkbox', {
      staticStyle: {
        "width": "110px"
      },
      attrs: {
        "value": value.CD_VAL,
        "label": value.CD_VAL,
        "data-code": key,
        "disabled": _vm.showStatus || _vm.reviewStatus || _vm.activeName != 'first'
      },
      on: {
        "change": _vm.checkPlatform
      }
    })
  })], 2)], 1)], 1)], 1), _vm._v(" "), _c('el-tabs', {
    on: {
      "tab-click": _vm.handleClick
    },
    model: {
      value: (_vm.activeName),
      callback: function($$v) {
        _vm.activeName = $$v
      },
      expression: "activeName"
    }
  }, [_c('el-tab-pane', {
    attrs: {
      "label": "基础信息",
      "name": "first"
    }
  }, [_c('header', [_vm._v("语言信息")]), _vm._v(" "), _c('div', {
    staticClass: "erp-language-info"
  }, [_c('div', {
    staticClass: "info-title"
  }, [_vm._v("语言")]), _vm._v(" "), _c('el-checkbox-group', {
    on: {
      "change": _vm.toggleCheck
    },
    model: {
      value: (_vm.checkList),
      callback: function($$v) {
        _vm.checkList = $$v
      },
      expression: "checkList"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "中文",
      "disabled": _vm.showStatus || _vm.reviewStatus
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "英语",
      "disabled": _vm.showStatus || _vm.reviewStatus
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "韩语",
      "disabled": _vm.showStatus || _vm.reviewStatus
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "日语",
      "disabled": _vm.showStatus || _vm.reviewStatus
    }
  })], 1)], 1)], 1)], 1), _vm._v(" "), _c('header', [_vm._v("商品信息")]), _vm._v(" "), _c('table', {
    staticClass: "erp-goods-info",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tbody', [_c('tr', {
    staticClass: "tr-goods"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("品牌名")]), _vm._v(" "), _c('td', [_c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus|| reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.brandNameValue))]), _vm._v(" "), _c('el-select', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticClass: "brand-name",
    attrs: {
      "placeholder": "请选择",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.changeBrand($event)
      }
    },
    model: {
      value: (_vm.brandNameValue),
      callback: function($$v) {
        _vm.brandNameValue = $$v
      },
      expression: "brandNameValue"
    }
  }, _vm._l((_vm.brandName), function(value, key, index) {
    return _c('el-option', {
      key: value.brandId,
      staticClass: "brandlist",
      attrs: {
        "data-code": value.brandId,
        "label": value.brandEnName,
        "value": value.brandEnName
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("品牌ID")]), _vm._v(" "), _c('td', [_c('p', [_vm._v(_vm._s(_vm.brandId))])]), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("商品单位")]), _vm._v(" "), _c('td', [_c('el-select', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "请选择",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selcetGoodsUnit($event)
      }
    },
    model: {
      value: (_vm.goodsUnitValue),
      callback: function($$v) {
        _vm.goodsUnitValue = $$v
      },
      expression: "goodsUnitValue"
    }
  }, _vm._l((_vm.goodsUnit), function(value, key) {
    return _c('el-option', {
      key: value,
      attrs: {
        "label": value,
        "value": value,
        "data-id": key
      }
    })
  })), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.goodsUnitValue))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("有无有效期")]), _vm._v(" "), _c('td', [_c('el-radio-group', {
    attrs: {
      "disabled": _vm.showStatus || _vm.reviewStatus
    },
    model: {
      value: (_vm.goodsShelfLife),
      callback: function($$v) {
        _vm.goodsShelfLife = $$v
      },
      expression: "goodsShelfLife"
    }
  }, [_c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "Y"
    }
  }, [_vm._v("Yes")]), _vm._v(" "), _c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "N"
    }
  }, [_vm._v("No")])], 1)], 1)]), _vm._v(" "), _c('tr', {
    staticClass: "tr-2"
  }, [_c('td', {
    staticClass: "info-title category"
  }, [_vm._v("一级类目")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus "
    }]
  }, [_c('span', [_vm._v(_vm._s(_vm.categoryLevel01))])]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticStyle: {
      "padding": "0px"
    }
  }, [_c('select', {
    attrs: {
      "id": "select_category_lv1",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv1), function(item) {
    return _c('option', {
      attrs: {
        "title": item.cnName
      },
      domProps: {
        "value": item.code
      },
      on: {
        "click": function($event) {
          _vm.catLv1Click($event)
        }
      }
    }, [_vm._v(_vm._s(item.cnName))])
  }))]), _vm._v(" "), _c('td', {
    staticClass: "info-title category"
  }, [_vm._v("二级类目")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus "
    }]
  }, [_c('span', [_vm._v(_vm._s(_vm.categoryLevel02))])]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticStyle: {
      "padding": "0px"
    }
  }, [_c('select', {
    attrs: {
      "id": "select_category_lv2",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv2), function(item) {
    return _c('option', {
      attrs: {
        "title": item.cnName
      },
      domProps: {
        "value": item.code
      },
      on: {
        "click": function($event) {
          _vm.catLv2Click($event)
        }
      }
    }, [_vm._v(_vm._s(item.cnName))])
  }))]), _vm._v(" "), _c('td', {
    staticClass: "info-title category"
  }, [_vm._v("三级类目")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus "
    }]
  }, [_c('span', [_vm._v(_vm._s(_vm.categoryLevel03))])]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticStyle: {
      "padding": "0px"
    }
  }, [_c('select', {
    attrs: {
      "id": "select_category_lv3",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv3), function(item) {
    return _c('option', {
      attrs: {
        "title": item.cnName
      },
      domProps: {
        "value": item.code
      },
      on: {
        "click": function($event) {
          _vm.catLv3Click($event)
        }
      }
    }, [_vm._v(_vm._s(item.cnName))])
  }))]), _vm._v(" "), _c('td', {
    staticClass: "info-title category"
  }, [_vm._v("四级类目")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus "
    }]
  }, [_c('span', [_vm._v(_vm._s(_vm.categoryLevel04))])]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticStyle: {
      "padding": "0px"
    }
  }, [_c('select', {
    attrs: {
      "id": "select_category_lv4",
      "name": "select_category",
      "aria-invalid": "false",
      "size": "10"
    }
  }, _vm._l((_vm.catLv4), function(item) {
    return _c('option', {
      attrs: {
        "value": ""
      }
    })
  }))])]), _vm._v(" "), _c('tr', {
    staticClass: "tr-goods langList"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("中文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.cnLanguage,
      "data-code": "N000920100"
    },
    model: {
      value: (_vm.chineseTitle),
      callback: function($$v) {
        _vm.chineseTitle = $$v
      },
      expression: "chineseTitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920100"
    }
  }, [_vm._v(_vm._s(_vm.chineseTitle))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("英文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.enLanguage,
      "data-code": "N000920200"
    },
    model: {
      value: (_vm.englishTitle),
      callback: function($$v) {
        _vm.englishTitle = $$v
      },
      expression: "englishTitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920200"
    }
  }, [_vm._v(_vm._s(_vm.englishTitle))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("韩文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.krLanguage,
      "data-code": "N000920400"
    },
    model: {
      value: (_vm.koreaTitle),
      callback: function($$v) {
        _vm.koreaTitle = $$v
      },
      expression: "koreaTitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920400"
    }
  }, [_vm._v(_vm._s(_vm.koreaTitle))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("日文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.jaLanguage,
      "data-code": "N000920300"
    },
    model: {
      value: (_vm.japanTitle),
      callback: function($$v) {
        _vm.japanTitle = $$v
      },
      expression: "japanTitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920300"
    }
  }, [_vm._v(_vm._s(_vm.japanTitle))])], 1)]), _vm._v(" "), _c('tr', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.hasSubtitle || _vm.editStatus),
      expression: "hasSubtitle || editStatus"
    }],
    staticClass: "tr-goods langList"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("中文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.cnLanguage,
      "data-code": "N000920100"
    },
    model: {
      value: (_vm.chineseSubtitle),
      callback: function($$v) {
        _vm.chineseSubtitle = $$v
      },
      expression: "chineseSubtitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920100"
    }
  }, [_vm._v(_vm._s(_vm.chineseSubtitle))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("英文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.enLanguage,
      "data-code": "N000920200"
    },
    model: {
      value: (_vm.englishSubtitle),
      callback: function($$v) {
        _vm.englishSubtitle = $$v
      },
      expression: "englishSubtitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920200"
    }
  }, [_vm._v(_vm._s(_vm.englishSubtitle))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("韩文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.krLanguage,
      "data-code": "N000920400"
    },
    model: {
      value: (_vm.koreaSubtitle),
      callback: function($$v) {
        _vm.koreaSubtitle = $$v
      },
      expression: "koreaSubtitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920400"
    }
  }, [_vm._v(_vm._s(_vm.koreaSubtitle))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("日文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "",
      "disabled": _vm.jaLanguage,
      "data-code": "N000920300"
    },
    model: {
      value: (_vm.japanSubtitle),
      callback: function($$v) {
        _vm.japanSubtitle = $$v
      },
      expression: "japanSubtitle"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }],
    attrs: {
      "data-code": "N000920300"
    }
  }, [_vm._v(_vm._s(_vm.japanSubtitle))])], 1)]), _vm._v(" "), _c('tr', {
    staticClass: "tr-goods"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("币种")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }]
  }, [_c('el-select', {
    attrs: {
      "placeholder": "请选择",
      "id": "currency_choice",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selectCurrency($event)
      }
    },
    model: {
      value: (_vm.currencyValue),
      callback: function($$v) {
        _vm.currencyValue = $$v
      },
      expression: "currencyValue"
    }
  }, _vm._l((_vm.currency), function(item) {
    return _c('el-option', {
      key: item.CD_VAL,
      attrs: {
        "label": item.CD_VAL,
        "value": item.CD_VAL,
        "data-id": item.CD
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.currencyValue))]), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("产地")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }]
  }, [_c('el-select', {
    attrs: {
      "placeholder": "请选择 ",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selectOriginPlace($event)
      }
    },
    model: {
      value: (_vm.originPlaceValue),
      callback: function($$v) {
        _vm.originPlaceValue = $$v
      },
      expression: "originPlaceValue "
    }
  }, _vm._l((_vm.originPlace), function(item) {
    return _c('el-option', {
      key: item.zh_name,
      attrs: {
        "label": item.zh_name,
        "value": item.zh_name,
        "data-id": item.id
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus||reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.originPlaceValue))]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "info-title"
  }, [_vm._v("前台标签")]), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }]
  }, [_c('el-radio-group', {
    attrs: {
      "disabled": _vm.showStatus || _vm.reviewStatus
    },
    model: {
      value: (_vm.FETag),
      callback: function($$v) {
        _vm.FETag = $$v
      },
      expression: "FETag"
    }
  }, [_c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "N000370200"
    }
  }, [_vm._v("Hot")]), _vm._v(" "), _c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "N000370100"
    }
  }, [_vm._v("New")]), _vm._v(" "), _c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "0"
    }
  }, [_vm._v("None")])], 1)], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }), _vm._v(" "), _c('td'), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.isFEStatus),
      expression: "!isFEStatus"
    }],
    staticClass: "info-title"
  }), _vm._v(" "), _c('td', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.isFEStatus),
      expression: "!isFEStatus"
    }]
  })])])]), _vm._v(" "), _c('header', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }]
  }, [_vm._v("税率信息")]), _vm._v(" "), _c('table', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "erp-tax-info",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("退税")]), _vm._v(" "), _c('td', [_c('el-radio-group', {
    attrs: {
      "disabled": _vm.showStatus || _vm.reviewStatus
    },
    on: {
      "change": function($event) {
        _vm.checkDrawBack($event)
      }
    },
    model: {
      value: (_vm.drawback),
      callback: function($$v) {
        _vm.drawback = $$v
      },
      expression: "drawback"
    }
  }, [_c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "Y"
    }
  }, [_vm._v("Yes")]), _vm._v(" "), _c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "N"
    }
  }, [_vm._v("No")])], 1)], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("返税比率")]), _vm._v(" "), _c('td', [_c('el-select', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticClass: "drawback-per",
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice",
      "disabled": _vm.drawbackAccess
    },
    on: {
      "change": function($event) {
        _vm.selectDrawback($event)
      }
    },
    model: {
      value: (_vm.drawbackPercent),
      callback: function($$v) {
        _vm.drawbackPercent = $$v
      },
      expression: "drawbackPercent"
    }
  }, _vm._l((_vm.drawbackList), function(value, key) {
    return _c('el-option', {
      key: value.CD_VAL,
      attrs: {
        "label": value.CD_VAL + '%',
        "value": value.CD_VAL + '%',
        "data-code": value.CD
      }
    })
  })), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.drawbackPercent || 0) + " %")])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("跨境电商综合税 保税仓专用")]), _vm._v(" "), _c('td', [_c('el-select', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice"
    },
    on: {
      "change": function($event) {
        _vm.selectbondedWarehouse($event)
      }
    },
    model: {
      value: (_vm.bondedWarehouse),
      callback: function($$v) {
        _vm.bondedWarehouse = $$v
      },
      expression: "bondedWarehouse"
    }
  }, _vm._l((_vm.bondedWarehouseList), function(value, key) {
    return _c('el-option', {
      key: value.CD_VAL,
      attrs: {
        "label": value.CD_VAL + '%',
        "value": value.CD_VAL + '%',
        "data-code": value.CD
      }
    })
  })), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.bondedWarehouse) + " %")])], 1)])]), _vm._v(" "), _c('header', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }]
  }, [_vm._v("运输信息")]), _vm._v(" "), _c('table', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "erp-tax-info",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("装箱数")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.packingNum),
      callback: function($$v) {
        _vm.packingNum = $$v
      },
      expression: "packingNum"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.packingNum))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("最小起订数")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.minNum),
      callback: function($$v) {
        _vm.minNum = $$v
      },
      expression: "minNum"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.minNum))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("最大起订量")]), _vm._v(" "), _c('td', [_c('el-input', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.maxNum),
      callback: function($$v) {
        _vm.maxNum = $$v
      },
      expression: "maxNum"
    }
  }), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.maxNum))])], 1)]), _vm._v(" "), _c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("物流类目")]), _vm._v(" "), _c('td', [_c('el-select', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice"
    },
    on: {
      "change": function($event) {
        _vm.selectLogisticsCategory($event)
      }
    },
    model: {
      value: (_vm.logisticsCategory),
      callback: function($$v) {
        _vm.logisticsCategory = $$v
      },
      expression: "logisticsCategory"
    }
  }, _vm._l((_vm.logisticsCategoryList), function(value, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": value.CD_VAL,
        "value": value.CD_VAL,
        "data-code": key
      }
    })
  })), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.logisticsCategory))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("物流类型")]), _vm._v(" "), _c('td', [_c('el-select', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice"
    },
    on: {
      "change": function($event) {
        _vm.selectLogisticsTypes($event)
      }
    },
    model: {
      value: (_vm.logisticsTypes),
      callback: function($$v) {
        _vm.logisticsTypes = $$v
      },
      expression: "logisticsTypes"
    }
  }, _vm._l((_vm.logisticsTypesList), function(value, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": value.CD_VAL,
        "value": value.CD_VAL,
        "data-code": key
      }
    })
  })), _vm._v(" "), _c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showStatus || _vm.reviewStatus),
      expression: "showStatus || reviewStatus"
    }]
  }, [_vm._v(_vm._s(_vm.logisticsTypes))])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  })])]), _vm._v(" "), _c('header', [_vm._v("商品主图")]), _vm._v(" "), _c('table', {
    staticClass: "erp-goods-pic",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tbody', [_c('tr', [_c('td', [_c('div', {
    directives: [{
      name: "loading",
      rawName: "v-loading",
      value: (_vm.loading01),
      expression: "loading01"
    }],
    staticClass: "img-content",
    attrs: {
      "data-code": "N000920100"
    }
  }, [_c('i', {
    staticClass: "cn-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.cnPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus && !_vm.cnLanguage),
      expression: "editStatus && !cnLanguage"
    }],
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    staticClass: "file",
    attrs: {
      "href": "javascript:;"
    }
  }, [_vm._v("选择文件"), _c('input', {
    staticClass: "cnPic",
    attrs: {
      "type": "file",
      "data-loading": "loading01",
      "id": "cnContent",
      "data-content": "cnContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })])])]), _vm._v(" "), _c('td', [_c('div', {
    directives: [{
      name: "loading",
      rawName: "v-loading",
      value: (_vm.loading02),
      expression: "loading02"
    }],
    staticClass: "img-content",
    attrs: {
      "data-code": "N000920200"
    }
  }, [_c('i', {
    staticClass: "en-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.enPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus && !_vm.enLanguage),
      expression: "editStatus && !enLanguage"
    }],
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    staticClass: "file",
    attrs: {
      "href": "javascript:;"
    }
  }, [_vm._v("选择文件"), _c('input', {
    staticClass: "enPic",
    attrs: {
      "type": "file",
      "data-loading": "loading02",
      "id": "enContent",
      "data-content": "enContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })])])]), _vm._v(" "), _c('td', [_c('div', {
    directives: [{
      name: "loading",
      rawName: "v-loading",
      value: (_vm.loading03),
      expression: "loading03"
    }],
    staticClass: "img-content",
    attrs: {
      "data-code": "N000920400"
    }
  }, [_c('i', {
    staticClass: "kr-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.krPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus && !_vm.krLanguage),
      expression: "editStatus && !krLanguage"
    }],
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    staticClass: "file",
    attrs: {
      "href": "javascript:;"
    }
  }, [_vm._v("选择文件"), _c('input', {
    staticClass: "krPic",
    attrs: {
      "type": "file",
      "data-loading": "loading03",
      "id": "krContent",
      "data-content": "krContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })])])]), _vm._v(" "), _c('td', [_c('div', {
    directives: [{
      name: "loading",
      rawName: "v-loading",
      value: (_vm.loading04),
      expression: "loading04 "
    }],
    staticClass: "img-content",
    attrs: {
      "data-code": "N000920300"
    }
  }, [_c('i', {
    staticClass: "ja-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.jaPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus && !_vm.jaLanguage),
      expression: "editStatus && !jaLanguage"
    }],
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    staticClass: "file",
    attrs: {
      "href": "javascript:;"
    }
  }, [_vm._v("选择文件"), _c('input', {
    staticClass: "jaPic",
    attrs: {
      "type": "file",
      "data-loading": "loading04",
      "id": "jaContent",
      "data-content": "jaContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })])])])])])]), _vm._v(" "), _c('header', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }]
  }, [_vm._v("商品描述")]), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus && _vm.editStatus),
      expression: "isFEStatus && editStatus"
    }],
    staticClass: "goods-description"
  }, [_c('div', {
    staticClass: "add-bar"
  }, [_c('el-select', {
    attrs: {
      "placeholder": "请选择",
      "id": "selectGoodsDes"
    },
    on: {
      "change": function($event) {
        _vm.selectDes($event)
      }
    },
    model: {
      value: (_vm.goodsDesValue),
      callback: function($$v) {
        _vm.goodsDesValue = $$v
      },
      expression: "goodsDesValue"
    }
  }, _vm._l((_vm.goodsDesList), function(value, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": value,
        "value": value,
        "data-code": key
      }
    })
  })), _vm._v(" "), _c('i', {
    staticClass: "el-icon-plus",
    on: {
      "click": _vm.addGoodsDes
    }
  })], 1), _vm._v(" "), _vm._l((_vm.goodsDetailContent.desc), function(value, key) {
    return _c('div', {
      staticClass: "added-content"
    }, [_c('div', [_c('i', {
      staticClass: "el-icon-close",
      attrs: {
        "data-code": key
      },
      on: {
        "click": function($event) {
          _vm.delDes($event)
        }
      }
    }), _vm._v(" "), _c('div', {
      staticClass: "goods-des-info"
    }, [_vm._v(_vm._s(_vm.goodsDesList[key]))])]), _vm._v(" "), _c('el-input', {
      staticClass: "added-item cn-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "Chinese",
        "resize": "none",
        "disabled": _vm.cnLanguage
      },
      model: {
        value: (value['N000920100']),
        callback: function($$v) {
          _vm.$set(value, 'N000920100', $$v)
        },
        expression: "value['N000920100']"
      }
    }), _vm._v(" "), _c('el-input', {
      staticClass: "added-item en-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "English",
        "resize": "none",
        "disabled": _vm.enLanguage
      },
      model: {
        value: (value['N000920200']),
        callback: function($$v) {
          _vm.$set(value, 'N000920200', $$v)
        },
        expression: "value['N000920200']"
      }
    }), _vm._v(" "), _c('el-input', {
      staticClass: "added-item kr-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "Korean",
        "resize": "none",
        "disabled": _vm.krLanguage
      },
      model: {
        value: (value['N000920400']),
        callback: function($$v) {
          _vm.$set(value, 'N000920400', $$v)
        },
        expression: "value['N000920400']"
      }
    }), _vm._v(" "), _c('el-input', {
      staticClass: "added-item jp-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "Japanese",
        "resize": "none",
        "disabled": _vm.jaLanguage
      },
      model: {
        value: (value['N000920300']),
        callback: function($$v) {
          _vm.$set(value, 'N000920300', $$v)
        },
        expression: "value['N000920300']"
      }
    })], 1)
  })], 2), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus && (_vm.showStatus || _vm.reviewStatus)),
      expression: "isFEStatus && (showStatus || reviewStatus)"
    }],
    staticClass: "goods-description"
  }, _vm._l((_vm.goodsDetailContent.desc), function(value, key) {
    return _c('div', {
      staticClass: "added-content"
    }, [_c('div', [_c('div', {
      staticClass: "goods-des-info"
    }, [_vm._v(_vm._s(_vm.goodsDesList[key]))])]), _vm._v(" "), _c('el-input', {
      staticClass: "added-item cn-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "Chinese",
        "resize": "none",
        "disabled": true
      },
      model: {
        value: (value['N000920100']),
        callback: function($$v) {
          _vm.$set(value, 'N000920100', $$v)
        },
        expression: "value['N000920100']"
      }
    }), _vm._v(" "), _c('el-input', {
      staticClass: "added-item en-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "English",
        "resize": "none",
        "disabled": true
      },
      model: {
        value: (value['N000920200']),
        callback: function($$v) {
          _vm.$set(value, 'N000920200', $$v)
        },
        expression: "value['N000920200']"
      }
    }), _vm._v(" "), _c('el-input', {
      staticClass: "added-item kr-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "Korean",
        "resize": "none",
        "disabled": true
      },
      model: {
        value: (value['N000920400']),
        callback: function($$v) {
          _vm.$set(value, 'N000920400', $$v)
        },
        expression: "value['N000920400']"
      }
    }), _vm._v(" "), _c('el-input', {
      staticClass: "added-item jp-content",
      attrs: {
        "type": "textarea",
        "rows": 4,
        "placeholder": "Japanese",
        "resize": "none",
        "disabled": true
      },
      model: {
        value: (value['N000920300']),
        callback: function($$v) {
          _vm.$set(value, 'N000920300', $$v)
        },
        expression: "value['N000920300']"
      }
    })], 1)
  })), _vm._v(" "), _c('header', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }]
  }, [_vm._v("详细信息")]), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "detail-info"
  }, [_c('el-tabs', {
    on: {
      "tab-click": _vm.handleClick01
    },
    model: {
      value: (_vm.textTools),
      callback: function($$v) {
        _vm.textTools = $$v
      },
      expression: "textTools"
    }
  }, [_c('el-tab-pane', {
    attrs: {
      "label": "Chinese",
      "name": "Chinese",
      "disabled": _vm.cnLanguage
    }
  }, [_c('quill-editor', {
    ref: "cneditor",
    staticClass: "texttool",
    attrs: {
      "disabled": _vm.showStatus,
      "options": _vm.cneditorOption
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlurCN($event)
      },
      "focus": function($event) {
        _vm.onEditorFocusCN($event)
      },
      "ready": function($event) {
        _vm.onEditorReadyCN($event)
      }
    },
    model: {
      value: (_vm.cnDetails),
      callback: function($$v) {
        _vm.cnDetails = $$v
      },
      expression: "cnDetails"
    }
  }, [_c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "id": "cntoolbar"
    },
    slot: "toolbar"
  }, [_c('button', {
    staticClass: "ql-bold"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-italic"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-underline"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-strike"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-blockquote"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-code-block"
  }), _vm._v(" "), _c('select', {
    staticClass: "ql-size"
  }, [_c('option', {
    attrs: {
      "value": "small"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "selected": ""
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "large"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "huge"
    }
  })]), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "ordered"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "bullet"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "sub"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "super"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "el-icon-picture",
    attrs: {
      "data-id": "cneditor"
    },
    on: {
      "click": _vm.customButtonClick
    }
  }), _vm._v(" "), _c('input', {
    staticClass: "custom-input",
    staticStyle: {
      "display": "none !important"
    },
    attrs: {
      "type": "file"
    },
    on: {
      "change": _vm.upload
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-video"
  })])])], 1), _vm._v(" "), _c('el-tab-pane', {
    attrs: {
      "label": "English",
      "name": "English",
      "disabled": _vm.enLanguage
    }
  }, [_c('quill-editor', {
    ref: "eneditor",
    staticClass: "texttool",
    attrs: {
      "disabled": _vm.showStatus,
      "options": _vm.eneditorOption
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlurEN($event)
      },
      "focus": function($event) {
        _vm.onEditorFocusEN($event)
      },
      "ready": function($event) {
        _vm.onEditorReadyEN($event)
      }
    },
    model: {
      value: (_vm.enDetails),
      callback: function($$v) {
        _vm.enDetails = $$v
      },
      expression: "enDetails"
    }
  }, [_c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "id": "entoolbar"
    },
    slot: "toolbar"
  }, [_c('button', {
    staticClass: "ql-bold"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-italic"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-underline"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-strike"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-blockquote"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-code-block"
  }), _vm._v(" "), _c('select', {
    staticClass: "ql-size"
  }, [_c('option', {
    attrs: {
      "value": "small"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "selected": ""
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "large"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "huge"
    }
  })]), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "ordered"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "bullet"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "sub"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "super"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "el-icon-picture",
    attrs: {
      "data-id": "eneditor"
    },
    on: {
      "click": _vm.customButtonClick
    }
  }), _vm._v(" "), _c('input', {
    staticClass: "custom-input",
    staticStyle: {
      "display": "none !important"
    },
    attrs: {
      "type": "file"
    },
    on: {
      "change": _vm.upload
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-video"
  })])])], 1), _vm._v(" "), _c('el-tab-pane', {
    attrs: {
      "label": "Korean",
      "name": "Korean",
      "disabled": _vm.krLanguage
    }
  }, [_c('quill-editor', {
    ref: "kreditor",
    staticClass: "texttool",
    attrs: {
      "disabled": _vm.showStatus,
      "options": _vm.kreditorOption
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlurKR($event)
      },
      "focus": function($event) {
        _vm.onEditorFocusKR($event)
      },
      "ready": function($event) {
        _vm.onEditorReadyKR($event)
      }
    },
    model: {
      value: (_vm.krDetails),
      callback: function($$v) {
        _vm.krDetails = $$v
      },
      expression: "krDetails"
    }
  }, [_c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "id": "krtoolbar"
    },
    slot: "toolbar"
  }, [_c('button', {
    staticClass: "ql-bold"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-italic"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-underline"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-strike"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-blockquote"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-code-block"
  }), _vm._v(" "), _c('select', {
    staticClass: "ql-size"
  }, [_c('option', {
    attrs: {
      "value": "small"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "selected": ""
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "large"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "huge"
    }
  })]), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "ordered"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "bullet"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "sub"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "super"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "el-icon-picture",
    attrs: {
      "data-id": "kreditor"
    },
    on: {
      "click": _vm.customButtonClick
    }
  }), _vm._v(" "), _c('input', {
    staticClass: "custom-input",
    staticStyle: {
      "display": "none !important"
    },
    attrs: {
      "type": "file"
    },
    on: {
      "change": _vm.upload
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-video"
  })])])], 1), _vm._v(" "), _c('el-tab-pane', {
    attrs: {
      "label": "Japanese",
      "name": "Japanese",
      "disabled": _vm.jaLanguage
    }
  }, [_c('quill-editor', {
    ref: "jaeditor",
    staticClass: "texttool",
    attrs: {
      "disabled": _vm.showStatus,
      "options": _vm.jaeditorOption
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlurJA($event)
      },
      "focus": function($event) {
        _vm.onEditorFocusJA($event)
      },
      "ready": function($event) {
        _vm.onEditorReadyJA($event)
      }
    },
    model: {
      value: (_vm.jaDetails),
      callback: function($$v) {
        _vm.jaDetails = $$v
      },
      expression: "jaDetails"
    }
  }, [_c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    attrs: {
      "id": "jatoolbar"
    },
    slot: "toolbar"
  }, [_c('button', {
    staticClass: "ql-bold"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-italic"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-underline"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-strike"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-blockquote"
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-code-block"
  }), _vm._v(" "), _c('select', {
    staticClass: "ql-size"
  }, [_c('option', {
    attrs: {
      "value": "small"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "selected": ""
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "large"
    }
  }), _vm._v(" "), _c('option', {
    attrs: {
      "value": "huge"
    }
  })]), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "ordered"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-list",
    attrs: {
      "value": "bullet"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "sub"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-script",
    attrs: {
      "value": "super"
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "el-icon-picture",
    attrs: {
      "data-id": "jaeditor"
    },
    on: {
      "click": _vm.customButtonClick
    }
  }), _vm._v(" "), _c('input', {
    staticClass: "custom-input",
    staticStyle: {
      "display": "none !important"
    },
    attrs: {
      "type": "file"
    },
    on: {
      "change": _vm.upload
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-video"
  })])])], 1)], 1)], 1)]), _vm._v(" "), _c('el-tab-pane', {
    attrs: {
      "label": "SKU信息",
      "name": "second"
    }
  }, [_c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showSearchBox),
      expression: "showSearchBox"
    }],
    staticClass: "option-search-box"
  }, [_c('h3', [_c('span', [_vm._v("option 搜索")]), _vm._v(" "), _c('i', {
    staticClass: "el-icon-close",
    on: {
      "click": _vm.closeSearchBox
    }
  })]), _vm._v(" "), _c('h4', [_vm._v("option 名字")]), _vm._v(" "), _c('el-row', {
    staticClass: "option-search",
    attrs: {
      "gutter": 20
    }
  }, [_c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('el-select', {
    staticClass: "selectOptionName",
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectOptionName($event)
      }
    },
    model: {
      value: (_vm.optionNameValue),
      callback: function($$v) {
        _vm.optionNameValue = $$v
      },
      expression: "optionNameValue"
    }
  }, _vm._l((_vm.optionName), function(item) {
    return _c('el-option', {
      key: item.ALL_VAL,
      staticClass: "OptionNameList",
      attrs: {
        "label": item.ALL_VAL,
        "value": item.ALL_VAL,
        "data-code": item.CODE
      }
    })
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('el-input', {
    staticClass: "search-optioninfo",
    attrs: {
      "placeholder": "",
      "icon": "search",
      "on-icon-click": _vm.searchOptionName
    },
    model: {
      value: (_vm.searchBarValue),
      callback: function($$v) {
        _vm.searchBarValue = $$v
      },
      expression: "searchBarValue"
    }
  })], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "selected-options",
    attrs: {
      "gutter": 10
    }
  }, _vm._l((_vm.selectedOptionInfoItem), function(item) {
    return _c('el-col', {
      staticStyle: {
        "padding-left": "12px",
        "padding-right": "30px"
      },
      attrs: {
        "span": 6
      }
    }, [_c('div', {
      staticClass: "selected-options-item",
      attrs: {
        "title": item.value,
        "data-code": item.id
      }
    }, [_c('span', [_vm._v(_vm._s(item.value))]), _vm._v(" "), _c('i', {
      staticClass: "el-icon-circle-close",
      on: {
        "click": function($event) {
          _vm.delOptionValue($event)
        }
      }
    })])])
  })), _vm._v(" "), _c('h4', [_vm._v("option 信息")]), _vm._v(" "), _c('el-row', {
    staticClass: "option-info",
    attrs: {
      "gutter": 10
    }
  }, _vm._l((_vm.optionInfoItem), function(item) {
    return _c('el-col', {
      attrs: {
        "span": 6
      }
    }, [_c('div', {
      staticClass: "option-info-item",
      attrs: {
        "title": item.ALL_VAL,
        "data-code": item.CODE
      },
      on: {
        "click": function($event) {
          _vm.chooseOptionItem($event)
        }
      }
    }, [_vm._v(_vm._s(item.ALL_VAL))])])
  })), _vm._v(" "), _c('h4', [_vm._v("添加 option")]), _vm._v(" "), _c('el-row', {
    staticClass: "option-add",
    attrs: {
      "gutter": 10
    }
  }, [_c('el-col', {
    attrs: {
      "span": 1
    }
  }, [_c('div', [_vm._v("Kr")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 1
    }
  }, [_c('span', [_vm._v("Cn")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 1
    }
  }, [_c('span', [_vm._v("En")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 1
    }
  }, [_c('span', [_vm._v("Ja")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-button', {
    staticClass: "add-sku-btn",
    attrs: {
      "type": "button",
      "icon": "plus"
    },
    on: {
      "click": _vm.addOptionToLsits
    }
  }, [_vm._v("添加")])], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "erp-addoption-btns"
  }, [_c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.saveOptionInfo
    }
  }, [_vm._v("保存")]), _vm._v(" "), _c('el-button', {
    on: {
      "click": _vm.resetOption
    }
  }, [_vm._v("重置")])], 1)], 1), _vm._v(" "), _c('header', [_vm._v("SKU信息\n        "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    staticClass: "btns-content",
    staticStyle: {
      "top": "70px"
    }
  }, [_c('el-button', {
    staticClass: "add-sku-btn",
    attrs: {
      "type": "button",
      "id": "add-sku-btn",
      "icon": "plus"
    },
    on: {
      "click": _vm.openSearchBox
    }
  }, [_vm._v("添加option信息")]), _vm._v(" "), _c('el-button', {
    staticClass: "apply_btn single_apply_btn",
    attrs: {
      "type": "button"
    },
    on: {
      "click": _vm.singleToBuild
    }
  }, [_vm._v("手动添加SKU")])], 1)]), _vm._v(" "), _c('table', {
    staticClass: "erp-add-sku",
    staticStyle: {
      "margin-bottom": "20px"
    },
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('thead', [_c('tr', {
    staticClass: "add-sku-title"
  }, [_c('td', {
    staticStyle: {
      "width": "20%"
    }
  }, [_vm._v("Option Name")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "width": "80%"
    }
  }, [_vm._v("Option Value\n              "), _c('i', {
    staticClass: "toggleOption",
    on: {
      "click": _vm.expendOption
    }
  }, [_vm._v(_vm._s(_vm.expendState ? "展开option信息" : "折叠option信息"))])])])]), _vm._v(" "), _c('tbody', _vm._l((_vm.addSkuOptionLine), function(item, index) {
    return _c('tr', {
      staticClass: "add-sku-option added-sku-option"
    }, [_c('td', {
      staticClass: "option_name"
    }, [_c('span', {
      staticClass: "item-option-name",
      attrs: {
        "title": item.optionName
      }
    }, [_vm._v(_vm._s(item.optionName))])]), _vm._v(" "), _c('td', {
      staticClass: "option_value"
    }, [_c('el-row', {
      attrs: {
        "gutter": 10
      }
    }, [_c('el-col', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticStyle: {
        "padding": "8px 0"
      },
      attrs: {
        "span": 20
      }
    }, _vm._l((item.optionValue), function(it, i) {
      return _c('div', {
        staticClass: "item-option-content",
        attrs: {
          "title": it
        }
      }, [_c('span', {
        staticClass: "item-option-value",
        attrs: {
          "data-index": index,
          "data-parcode": item.nameCode,
          "data-code": item.valueCode.split(',')[i]
        },
        on: {
          "click": _vm.selectWhatToAdd
        }
      }, [_vm._v(_vm._s(it))]), _vm._v(" "), _c('i', {
        staticClass: "el-icon-circle-close",
        on: {
          "click": function($event) {
            _vm.delRowOption($event)
          }
        }
      })])
    })), _vm._v(" "), _c('el-col', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticStyle: {
        "padding": "8px 0"
      },
      attrs: {
        "span": 4
      }
    }, [_c('i', {
      staticClass: "el-icon-search",
      attrs: {
        "data-index": index,
        "data-code": item.nameCode,
        "data-items": item.valueCode
      },
      on: {
        "click": function($event) {
          _vm.openSearchBox($event)
        }
      }
    }), _vm._v(" "), _c('i', {
      staticClass: "el-icon-delete2",
      on: {
        "click": function($event) {
          _vm.delSkuOption(index)
        }
      }
    })]), _vm._v(" "), _c('el-col', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus || reviewStatus"
      }],
      staticStyle: {
        "padding": "8px 0"
      },
      attrs: {
        "span": 20
      }
    }, _vm._l((item.optionValue), function(it, i) {
      return _c('div', {
        staticClass: "item-option-content",
        attrs: {
          "title": it
        }
      }, [_c('span', {
        staticClass: "item-option-value",
        attrs: {
          "data-index": index,
          "data-parcode": item.nameCode,
          "data-code": item.valueCode.split(',')[i]
        }
      }, [_vm._v(_vm._s(it))])])
    })), _vm._v(" "), _c('el-col', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus || reviewStatus"
      }],
      staticStyle: {
        "padding": "8px 0"
      },
      attrs: {
        "span": 4
      }
    })], 1)], 1)])
  }))]), _vm._v(" "), _c('table', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.isFEStatus),
      expression: "!isFEStatus"
    }],
    staticClass: "erp-sku-info erp-sku-BE",
    staticStyle: {
      "table-layout": "fixed",
      "word-break": "break-all",
      "text-align": "center"
    },
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('thead', [_c('tr', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }]
  }, [_c('th', {
    staticStyle: {
      "width": "67px"
    }
  }, [_vm._v("SKU ID")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性名")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性值")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "135px"
    }
  }, [_vm._v("UPC")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("CR code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("HS code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    attrs: {
      "title": "采购价"
    }
  }, [_vm._v("采购价")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "批量"
    },
    model: {
      value: (_vm.skuPrice),
      callback: function($$v) {
        _vm.skuPrice = $$v
      },
      expression: "skuPrice"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    attrs: {
      "title": "重量(g)"
    }
  }, [_vm._v("重量(g)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "批量"
    },
    model: {
      value: (_vm.skuWeight),
      callback: function($$v) {
        _vm.skuWeight = $$v
      },
      expression: "skuWeight"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    attrs: {
      "title": "长(cm)"
    }
  }, [_vm._v("长"), _c('br'), _vm._v("(cm)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "批量"
    },
    model: {
      value: (_vm.skuLength),
      callback: function($$v) {
        _vm.skuLength = $$v
      },
      expression: "skuLength"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    attrs: {
      "title": "宽(cm)"
    }
  }, [_vm._v("宽"), _c('br'), _vm._v("(cm)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "批量"
    },
    model: {
      value: (_vm.skuWidth),
      callback: function($$v) {
        _vm.skuWidth = $$v
      },
      expression: "skuWidth"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    attrs: {
      "title": "高(cm)"
    }
  }, [_vm._v("高"), _c('br'), _vm._v("(cm)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "批量"
    },
    model: {
      value: (_vm.skuHeight),
      callback: function($$v) {
        _vm.skuHeight = $$v
      },
      expression: "skuHeight"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "78px"
    }
  }, [_c('span', {
    staticStyle: {
      "display": "inline-block",
      "margin-bottom": "6px"
    },
    attrs: {
      "title": "海关申报信息"
    }
  }, [_vm._v("海关申报信息")]), _vm._v(" "), _c('br'), _vm._v(" "), _c('span', {
    on: {
      "click": _vm.setCustomsInfo
    }
  }, [_vm._v("设置")]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "85px"
    }
  }, [_vm._v("sku状态")])]), _vm._v(" "), _c('tr', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.reviewStatus || _vm.showStatus),
      expression: "reviewStatus||showStatus"
    }]
  }, [_c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("SKU ID")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性名")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性值")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "90px"
    }
  }, [_vm._v("UPC")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("CR code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("HS code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "80px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', [_vm._v("采购价")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "50px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', [_vm._v("重量(g)")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "50px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', [_vm._v("长(cm)")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "50px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', [_vm._v("宽(cm)")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "50px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', [_vm._v("高(cm)")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "78px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', {
    attrs: {
      "title": "海关申报信息"
    }
  }, [_vm._v("海关申报信息")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "110px"
    }
  }, [_vm._v("sku状态")])])]), _vm._v(" "), _c('tbody', _vm._l((_vm.optionList), function(value, key) {
    return _c('tr', {
      attrs: {
        "data-sku": value.GUDS_OPT_ID
      }
    }, [_c('td', [_c('span', {
      attrs: {
        "title": value.GUDS_OPT_ID
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_ID))])]), _vm._v(" "), _c('td', {
      staticClass: "option-name-value",
      attrs: {
        "title": value.optText.optNames
      },
      domProps: {
        "innerHTML": _vm._s(value.optText.optNames)
      }
    }), _vm._v(" "), _c('td', {
      staticClass: "option-name-value",
      attrs: {
        "title": value.optText.optValues
      },
      domProps: {
        "innerHTML": _vm._s(value.optText.optValues)
      }
    }), _vm._v(" "), _c('td', [_c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "upc-value option-upc",
      attrs: {
        "title": _vm.UPCMain[key]
      },
      domProps: {
        "innerHTML": _vm._s(_vm.UPCMain[key] ? _vm.UPCMain[key].replace(/\,/g, '<br>') : '')
      }
    }, [_vm._v(_vm._s(_vm.UPCMain[key]))]), _vm._v(" "), _c('el-button', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "add-sku-btn",
      staticStyle: {
        "width": "26px"
      },
      attrs: {
        "type": "button",
        "icon": "edit"
      },
      on: {
        "click": function($event) {
          _vm.setUPC($event, key)
        }
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      staticClass: "option-upc",
      attrs: {
        "title": _vm.UPCMain[key]
      },
      domProps: {
        "innerHTML": _vm._s(_vm.UPCMain[key] ? _vm.UPCMain[key].replace(/\,/g, '<br>') : '')
      }
    }, [_vm._v(_vm._s(_vm.UPCMain[key]))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "cr-code-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_HS_CODE),
        callback: function($$v) {
          value.GUDS_HS_CODE = $$v
        },
        expression: "value.GUDS_HS_CODE"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_HS_CODE
      }
    }, [_vm._v(_vm._s(value.GUDS_HS_CODE))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "hs-code-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_HS_CODE2),
        callback: function($$v) {
          value.GUDS_HS_CODE2 = $$v
        },
        expression: "value.GUDS_HS_CODE2"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_HS_CODE2
      }
    }, [_vm._v(_vm._s(value.GUDS_HS_CODE2))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "price-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_OPT_ORG_PRC),
        callback: function($$v) {
          value.GUDS_OPT_ORG_PRC = $$v
        },
        expression: "value.GUDS_OPT_ORG_PRC"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_OPT_ORG_PRC
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_ORG_PRC))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "weight-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_OPT_WEIGHT),
        callback: function($$v) {
          value.GUDS_OPT_WEIGHT = $$v
        },
        expression: "value.GUDS_OPT_WEIGHT"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_OPT_WEIGHT
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_WEIGHT))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "length-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_OPT_LENGTH),
        callback: function($$v) {
          value.GUDS_OPT_LENGTH = $$v
        },
        expression: "value.GUDS_OPT_LENGTH"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_OPT_LENGTH
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_LENGTH))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "width-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_OPT_WIDTH),
        callback: function($$v) {
          value.GUDS_OPT_WIDTH = $$v
        },
        expression: "value.GUDS_OPT_WIDTH"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_OPT_WIDTH
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_WIDTH))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "height-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_OPT_HEIGHT),
        callback: function($$v) {
          value.GUDS_OPT_HEIGHT = $$v
        },
        expression: "value.GUDS_OPT_HEIGHT"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_OPT_HEIGHT
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_HEIGHT))])], 1), _vm._v(" "), _c('td', {
      staticStyle: {
        "padding": "0px"
      }
    }, [_c('el-button', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "edit-custom-btn",
      attrs: {
        "type": "primary",
        "data-index": key
      },
      on: {
        "click": function($event) {
          _vm.editCustomInfo($event, key)
        }
      },
      slot: "reference"
    }, [_vm._v("编辑\n              ")]), _vm._v(" "), _c('el-button', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus || reviewStatus"
      }],
      staticClass: "edit-custom-btn",
      attrs: {
        "type": "primary",
        "data-index": key
      },
      on: {
        "click": function($event) {
          _vm.editCustomInfo($event, key)
        }
      },
      slot: "reference"
    }, [_vm._v("查看\n              ")]), _vm._v(" "), _c('div', {
      staticClass: "custom-content",
      staticStyle: {
        "visibility": "hidden",
        "width": "0px",
        "height": "0px"
      }
    }, [_vm._l((_vm.skuFeatureContent[key]), function(item) {
      return _c('span')
    }), _vm._v(" "), _c('span', {
      staticClass: "upc-content"
    }, [_vm._v(_vm._s(value.GUDS_HS_UPC))]), _vm._v(" "), _c('span', {
      staticClass: "declarationValue"
    }, [_vm._v(_vm._s(value.CUSTOMS_PRICE))])], 2)], 1), _vm._v(" "), _c('td', [_c('span', [_vm._v(_vm._s((value.IS_ENABLE == 1) ? "启用" : "停用"))]), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (value.IS_ENABLE == 1),
        expression: "value.IS_ENABLE == 1"
      }],
      staticClass: "end-state",
      on: {
        "click": function($event) {
          _vm.changeSKUState($event, value.IS_ENABLE)
        }
      }
    }, [_vm._v(" 停用")]), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (value.IS_ENABLE == 0),
        expression: "value.IS_ENABLE == 0"
      }],
      staticClass: "start-state",
      on: {
        "click": function($event) {
          _vm.changeSKUState($event, value.IS_ENABLE)
        }
      }
    }, [_vm._v(" 启用")])])])
  }))]), _vm._v(" "), _c('table', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "erp-sku-info erp-sku-FE",
    staticStyle: {
      "table-layout": "fixed",
      "word-break": "break-all",
      "text-align": "center"
    },
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('thead', [_c('tr', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }]
  }, [_c('th', {
    staticStyle: {
      "width": "67px"
    }
  }, [_vm._v("SKU ID")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性名")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性值")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "135px"
    }
  }, [_vm._v("UPC")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("CR code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("HS code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "240px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('span', {
    attrs: {
      "title": "单位"
    }
  }, [_vm._v("单位")])]), _vm._v(" "), _c('el-col', {
    staticClass: "set-btn",
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    on: {
      "click": _vm.setInfo
    }
  }, [_vm._v("设置")])]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "100px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 14
    }
  }, [_c('span', {
    attrs: {
      "title": "销售状态"
    }
  }, [_vm._v("销售状态")])]), _vm._v(" "), _c('el-col', {
    staticClass: "set-btn",
    attrs: {
      "span": 10
    }
  }, [_c('span', {
    on: {
      "click": _vm.setSaleStatus
    }
  }, [_vm._v("设置")])]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "78px"
    }
  }, [_c('span', {
    staticStyle: {
      "display": "inline-block",
      "margin-bottom": "6px"
    },
    attrs: {
      "title": "海关申报信息"
    }
  }, [_vm._v("海关申报信息")]), _vm._v(" "), _c('br'), _vm._v(" "), _c('span', {
    on: {
      "click": _vm.setCustomsInfo
    }
  }, [_vm._v("设置")]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "85px"
    }
  }, [_vm._v("sku状态")])]), _vm._v(" "), _c('tr', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.reviewStatus || _vm.showStatus),
      expression: "reviewStatus||showStatus"
    }]
  }, [_c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("SKU ID")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性名")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "text-align": "center"
    }
  }, [_vm._v("商品属性值")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "90px"
    }
  }, [_vm._v("UPC")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("CR code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "75px"
    }
  }, [_vm._v("HS code")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "240px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', {
    attrs: {
      "title": "单位"
    }
  }, [_vm._v("单位")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "100px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', {
    attrs: {
      "title": "销售状态"
    }
  }, [_vm._v("销售状态")])])], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "78px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 24
    }
  }, [_c('span', {
    attrs: {
      "title": "海关申报信息"
    }
  }, [_vm._v("海关申报信息")])]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "110px"
    }
  }, [_vm._v("sku状态")])])]), _vm._v(" "), _c('tbody', _vm._l((_vm.optionList), function(value, index) {
    return _c('tr', {
      attrs: {
        "data-sku": value.GUDS_OPT_ID
      }
    }, [_c('td', [_c('span', {
      attrs: {
        "title": value.GUDS_OPT_ID
      }
    }, [_vm._v(_vm._s(value.GUDS_OPT_ID))])]), _vm._v(" "), _c('td', {
      staticClass: "option-name-value",
      attrs: {
        "title": value.optText.optNames
      },
      domProps: {
        "innerHTML": _vm._s(value.optText.optNames)
      }
    }), _vm._v(" "), _c('td', {
      staticClass: "option-name-value",
      attrs: {
        "title": value.optText.optValues
      },
      domProps: {
        "innerHTML": _vm._s(value.optText.optValues)
      }
    }), _vm._v(" "), _c('td', [_c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "upc-value option-upc",
      attrs: {
        "title": _vm.UPCMain[index]
      },
      domProps: {
        "innerHTML": _vm._s(_vm.UPCMain[index] ? _vm.UPCMain[index].replace(/\,/g, '<br>') : '')
      }
    }, [_vm._v(_vm._s(_vm.UPCMain[index]))]), _vm._v(" "), _c('el-button', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "add-sku-btn",
      staticStyle: {
        "width": "26px"
      },
      attrs: {
        "type": "button",
        "icon": "edit"
      },
      on: {
        "click": function($event) {
          _vm.setUPC($event, index)
        }
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      staticClass: "option-upc",
      attrs: {
        "title": _vm.UPCMain[index]
      },
      domProps: {
        "innerHTML": _vm._s(_vm.UPCMain[index] ? _vm.UPCMain[index].replace(/\,/g, '<br>') : '')
      }
    }, [_vm._v(_vm._s(_vm.UPCMain[index]))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "cr-code-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_HS_CODE),
        callback: function($$v) {
          value.GUDS_HS_CODE = $$v
        },
        expression: "value.GUDS_HS_CODE"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_HS_CODE
      }
    }, [_vm._v(_vm._s(value.GUDS_HS_CODE))])], 1), _vm._v(" "), _c('td', [_c('el-input', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "hs-code-value",
      attrs: {
        "placeholder": ""
      },
      model: {
        value: (value.GUDS_HS_CODE2),
        callback: function($$v) {
          value.GUDS_HS_CODE2 = $$v
        },
        expression: "value.GUDS_HS_CODE2"
      }
    }), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus||reviewStatus"
      }],
      attrs: {
        "title": value.GUDS_HS_CODE2
      }
    }, [_vm._v(_vm._s(value.GUDS_HS_CODE2))])], 1), _vm._v(" "), _c('td', {
      staticClass: "input-select unit-content"
    }, [_c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("长(cm)")]), _vm._v(" "), _c('el-input', {
      staticClass: "length-value",
      attrs: {
        "placeholder": "选填",
        "disabled": _vm.showStatus || _vm.reviewStatus
      },
      model: {
        value: (value.GUDS_OPT_LENGTH),
        callback: function($$v) {
          value.GUDS_OPT_LENGTH = $$v
        },
        expression: "value.GUDS_OPT_LENGTH"
      }
    })], 1), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("宽(cm)")]), _vm._v(" "), _c('el-input', {
      staticClass: "width-value",
      attrs: {
        "placeholder": "选填",
        "disabled": _vm.showStatus || _vm.reviewStatus
      },
      model: {
        value: (value.GUDS_OPT_WIDTH),
        callback: function($$v) {
          value.GUDS_OPT_WIDTH = $$v
        },
        expression: "value.GUDS_OPT_WIDTH"
      }
    })], 1), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("高(cm)")]), _vm._v(" "), _c('el-input', {
      staticClass: "height-value",
      attrs: {
        "placeholder": "选填",
        "disabled": _vm.showStatus || _vm.reviewStatus
      },
      model: {
        value: (value.GUDS_OPT_HEIGHT),
        callback: function($$v) {
          value.GUDS_OPT_HEIGHT = $$v
        },
        expression: "value.GUDS_OPT_HEIGHT"
      }
    })], 1), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("重量(g)")]), _vm._v(" "), _c('el-input', {
      staticClass: "weight-value",
      attrs: {
        "placeholder": "选填",
        "disabled": _vm.showStatus || _vm.reviewStatus
      },
      model: {
        value: (value.GUDS_OPT_WEIGHT),
        callback: function($$v) {
          value.GUDS_OPT_WEIGHT = $$v
        },
        expression: "value.GUDS_OPT_WEIGHT"
      }
    })], 1)]), _vm._v(" "), _c('td', [_c('el-select', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "sale-status-lists",
      attrs: {
        "placeholder": "请选择"
      },
      on: {
        "change": function($event) {
          _vm.selectItemSaleStatus($event, index)
        }
      },
      model: {
        value: (_vm.saleStatu.sale[index]['value']),
        callback: function($$v) {
          _vm.$set(_vm.saleStatu.sale[index], 'value', $$v)
        },
        expression: "saleStatu.sale[index]['value']"
      }
    }, _vm._l((_vm.saleStatusList), function(value, key) {
      return _c('el-option', {
        key: key,
        attrs: {
          "label": value.CD_VAL,
          "value": value.CD_VAL,
          "data-code": key
        }
      })
    })), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: ((_vm.showStatus || _vm.reviewStatus) && _vm.isFEStatus),
        expression: "(showStatus||reviewStatus) && isFEStatus"
      }]
    }, [_vm._v(_vm._s((_vm.saleStatusList[value.GUDS_OPT_SALE_STAT_CD]) ? _vm.saleStatusList[value.GUDS_OPT_SALE_STAT_CD]["CD_VAL"] : "") + "\n              ")])], 1), _vm._v(" "), _c('td', {
      staticStyle: {
        "padding": "0px"
      }
    }, [_c('el-button', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "edit-custom-btn",
      attrs: {
        "type": "primary",
        "data-index": index
      },
      on: {
        "click": function($event) {
          _vm.editCustomInfo($event, index)
        }
      },
      slot: "reference"
    }, [_vm._v("编辑\n              ")]), _vm._v(" "), _c('el-button', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.showStatus || _vm.reviewStatus),
        expression: "showStatus || reviewStatus"
      }],
      staticClass: "edit-custom-btn",
      attrs: {
        "type": "primary",
        "data-index": index
      },
      on: {
        "click": function($event) {
          _vm.editCustomInfo($event, index)
        }
      },
      slot: "reference"
    }, [_vm._v("查看\n              ")]), _vm._v(" "), _c('div', {
      staticClass: "custom-content",
      staticStyle: {
        "visibility": "hidden",
        "width": "0px",
        "height": "0px"
      }
    }, [_c('span', {
      staticClass: "upc-content"
    }, [_vm._v(_vm._s(value.GUDS_HS_UPC))]), _vm._v(" "), _c('span', {
      staticClass: "declarationValue"
    }, [_vm._v(_vm._s(value.CUSTOMS_PRICE))])])], 1), _vm._v(" "), _c('td', [_c('span', [_vm._v(_vm._s((value.IS_ENABLE == 1) ? "启用" : "停用"))]), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (value.IS_ENABLE == 1),
        expression: "value.IS_ENABLE == 1"
      }],
      staticClass: "end-state",
      on: {
        "click": function($event) {
          _vm.changeSKUState($event, value.IS_ENABLE)
        }
      }
    }, [_vm._v(" 停用")]), _vm._v(" "), _c('span', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (value.IS_ENABLE == 0),
        expression: "value.IS_ENABLE == 0"
      }],
      staticClass: "start-state",
      on: {
        "click": function($event) {
          _vm.changeSKUState($event, value.IS_ENABLE)
        }
      }
    }, [_vm._v(" 启用")])])])
  }))]), _vm._v(" "), _c('el-dialog', {
    staticClass: "unit-set",
    attrs: {
      "title": "UPC",
      "visible": _vm.UPCVisible,
      "close-on-click-modal": false
    },
    on: {
      "update:visible": function($event) {
        _vm.UPCVisible = $event
      }
    }
  }, [_c('div', {
    staticClass: "UPC-box"
  }, [_c('div', {
    staticClass: "add-UPC"
  }, [_c('el-input', {
    staticClass: "upc-item",
    model: {
      value: (_vm.firstUPC),
      callback: function($$v) {
        _vm.firstUPC = $$v
      },
      expression: "firstUPC"
    }
  }), _vm._v(" "), _c('el-button', {
    staticClass: "add-sku-btn",
    staticStyle: {
      "width": "26px"
    },
    attrs: {
      "type": "button",
      "icon": "plus"
    },
    on: {
      "click": _vm.addUPCitem
    }
  })], 1), _vm._v(" "), _vm._l((_vm.UPCcontainer), function(item, index) {
    return _c('div', {
      staticClass: "upc-items"
    }, [_c('el-input', {
      staticClass: "upc-item",
      model: {
        value: (_vm.UPCcontainer[index]),
        callback: function($$v) {
          _vm.$set(_vm.UPCcontainer, index, $$v)
        },
        expression: "UPCcontainer[index]"
      }
    }), _vm._v(" "), _c('el-button', {
      staticClass: "add-sku-btn",
      staticStyle: {
        "width": "26px"
      },
      attrs: {
        "type": "button",
        "icon": "minus"
      },
      on: {
        "click": function($event) {
          _vm.delUPCitem($event, index)
        }
      }
    })], 1)
  }), _vm._v(" "), _c('div', {
    staticClass: "dialog-footer",
    slot: "footer"
  }, [_c('el-button', {
    on: {
      "click": function($event) {
        _vm.UPCVisible = false;
        _vm.firstUPC = '';
        _vm.UPCcontainer = []
      }
    }
  }, [_vm._v("取 消")]), _vm._v(" "), _c('el-button', {
    staticClass: "doSetting-UPC-btn",
    attrs: {
      "type": "primary",
      "data-index": 0
    },
    on: {
      "click": function($event) {
        _vm.doSettingUPC($event)
      }
    }
  }, [_vm._v("确 定")])], 1)], 2)]), _vm._v(" "), _c('el-dialog', {
    staticClass: "unit-set",
    attrs: {
      "title": "批量设置",
      "visible": _vm.dialogUnitVisible,
      "close-on-click-modal": false
    },
    on: {
      "update:visible": function($event) {
        _vm.dialogUnitVisible = $event
      }
    }
  }, [_c('div', {
    staticClass: "dialog-content"
  }, [_c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-bottom": "20px"
    }
  }, [_c('span', {
    staticClass: "item-unit",
    staticStyle: {
      "display": "inline-block",
      "width": "100px"
    }
  }, [_vm._v("长(cm)")]), _vm._v(" "), _c('el-input', {
    staticClass: "length-value",
    staticStyle: {
      "width": "400px"
    },
    attrs: {
      "placeholder": "选填"
    },
    model: {
      value: (_vm.skuLengthTemp),
      callback: function($$v) {
        _vm.skuLengthTemp = $$v
      },
      expression: "skuLengthTemp"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-bottom": "20px"
    }
  }, [_c('span', {
    staticClass: "item-unit",
    staticStyle: {
      "display": "inline-block",
      "width": "100px"
    }
  }, [_vm._v("宽(cm)")]), _vm._v(" "), _c('el-input', {
    staticClass: "width-value",
    staticStyle: {
      "width": "400px"
    },
    attrs: {
      "placeholder": "选填"
    },
    model: {
      value: (_vm.skuWidthTemp),
      callback: function($$v) {
        _vm.skuWidthTemp = $$v
      },
      expression: "skuWidthTemp"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-bottom": "20px"
    }
  }, [_c('span', {
    staticClass: "item-unit",
    staticStyle: {
      "display": "inline-block",
      "width": "100px"
    }
  }, [_vm._v("高(cm)")]), _vm._v(" "), _c('el-input', {
    staticClass: "height-value",
    staticStyle: {
      "width": "400px"
    },
    attrs: {
      "placeholder": "选填"
    },
    model: {
      value: (_vm.skuHeightTemp),
      callback: function($$v) {
        _vm.skuHeightTemp = $$v
      },
      expression: "skuHeightTemp"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-bottom": "20px"
    }
  }, [_c('span', {
    staticClass: "item-unit",
    staticStyle: {
      "display": "inline-block",
      "width": "100px"
    }
  }, [_vm._v("重量(g)")]), _vm._v(" "), _c('el-input', {
    staticClass: "weight-value",
    staticStyle: {
      "width": "400px"
    },
    attrs: {
      "placeholder": "必填"
    },
    model: {
      value: (_vm.skuWeightTemp),
      callback: function($$v) {
        _vm.skuWeightTemp = $$v
      },
      expression: "skuWeightTemp"
    }
  })], 1)]), _vm._v(" "), _c('div', {
    staticClass: "dialog-footer",
    slot: "footer"
  }, [_c('el-button', {
    on: {
      "click": function($event) {
        _vm.dialogUnitVisible = false
      }
    }
  }, [_vm._v("取 消")]), _vm._v(" "), _c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doSettingUnit
    }
  }, [_vm._v("确 定")])], 1)]), _vm._v(" "), _c('el-dialog', {
    staticClass: "sale-status-set",
    attrs: {
      "title": "批量设置",
      "visible": _vm.dialogSaleVisible,
      "close-on-click-modal": false
    },
    on: {
      "update:visible": function($event) {
        _vm.dialogSaleVisible = $event
      }
    }
  }, [_c('div', {
    staticClass: "dialog-content"
  }, [_c('div', {
    staticClass: "item-content"
  }, [_c('span', {
    staticClass: "item-price"
  }, [_vm._v("销售状态")]), _vm._v(" "), _c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectSaleStatus($event)
      }
    },
    model: {
      value: (_vm.saleStatusValueTemp),
      callback: function($$v) {
        _vm.saleStatusValueTemp = $$v
      },
      expression: "saleStatusValueTemp"
    }
  }, _vm._l((_vm.saleStatusList), function(value, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": value.CD_VAL,
        "value": value.CD_VAL,
        "data-code": key
      }
    })
  }))], 1)]), _vm._v(" "), _c('div', {
    staticClass: "dialog-footer",
    slot: "footer"
  }, [_c('el-button', {
    on: {
      "click": function($event) {
        _vm.dialogSaleVisible = false
      }
    }
  }, [_vm._v("取 消")]), _vm._v(" "), _c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doSettingSale
    }
  }, [_vm._v("确 定")])], 1)]), _vm._v(" "), _c('el-dialog', {
    staticClass: "custom-set mutiple",
    attrs: {
      "title": "批量设置",
      "visible": _vm.dialogCustomInfoVisible,
      "close-on-click-modal": false
    },
    on: {
      "update:visible": function($event) {
        _vm.dialogCustomInfoVisible = $event
      }
    }
  }, [_c('div', {
    staticClass: "dialog-content"
  }, [_vm._l((_vm.skuFeatureAll), function(item) {
    return _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-title"
    }, [_vm._v(_vm._s(item.CD_VAL))]), _vm._v(" "), _c('el-radio-group', {
      staticClass: "sku-feature",
      attrs: {
        "data-code": item.CD
      },
      model: {
        value: (item.radioVal),
        callback: function($$v) {
          item.radioVal = $$v
        },
        expression: "item.radioVal"
      }
    }, [_c('el-radio', {
      staticClass: "radio",
      attrs: {
        "label": "0"
      }
    }, [_vm._v("否")]), _vm._v(" "), _c('el-radio', {
      staticClass: "radio",
      attrs: {
        "label": "1"
      }
    }, [_vm._v("是")]), _vm._v(" "), _c('el-radio', {
      staticClass: "radio",
      attrs: {
        "label": "2"
      }
    }, [_vm._v("未知")])], 1)], 1)
  }), _vm._v(" "), _c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-bottom": "0px"
    }
  }, [_c('span', {
    staticClass: "item-title",
    staticStyle: {
      "width": "170px"
    }
  }, [_vm._v("申报价值(美元)")]), _vm._v(" $\n            "), _c('el-input', {
    staticClass: "declaration-value",
    model: {
      value: (_vm.declarationValueTemp),
      callback: function($$v) {
        _vm.declarationValueTemp = $$v
      },
      expression: "declarationValueTemp"
    }
  })], 1)], 2), _vm._v(" "), _c('div', {
    staticClass: "dialog-footer",
    slot: "footer"
  }, [_c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doSettingCustom
    }
  }, [_vm._v("确 定")])], 1)]), _vm._v(" "), _c('el-dialog', {
    staticClass: "custom-set",
    attrs: {
      "title": "海关申报信息设置",
      "visible": _vm.dialogCustomItemVisible,
      "close-on-click-modal": false
    },
    on: {
      "update:visible": function($event) {
        _vm.dialogCustomItemVisible = $event
      }
    }
  }, [_c('div', {
    staticClass: "dialog-content"
  }, [_vm._l((_vm.skuFeatureTemp), function(item) {
    return _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-title"
    }, [_vm._v(_vm._s(item.CD_VAL))]), _vm._v(" "), _c('el-radio-group', {
      staticClass: "sku-feature",
      attrs: {
        "data-code": item.CD,
        "disabled": _vm.showStatus || _vm.reviewStatus
      },
      model: {
        value: (item.radioVal),
        callback: function($$v) {
          item.radioVal = $$v
        },
        expression: "item.radioVal"
      }
    }, [_c('el-radio', {
      staticClass: "radio",
      attrs: {
        "label": "0"
      }
    }, [_vm._v("否")]), _vm._v(" "), _c('el-radio', {
      staticClass: "radio",
      attrs: {
        "label": "1"
      }
    }, [_vm._v("是")]), _vm._v(" "), _c('el-radio', {
      staticClass: "radio",
      attrs: {
        "label": "2"
      }
    }, [_vm._v("未知")])], 1)], 1)
  }), _vm._v(" "), _c('div', {
    staticClass: "item-content"
  }, [_c('span', {
    staticClass: "item-title",
    staticStyle: {
      "width": "170px"
    }
  }, [_vm._v("申报价值(美元)")]), _vm._v(" $\n            "), _c('el-input', {
    staticClass: "declaration-value",
    attrs: {
      "disabled": _vm.showStatus || _vm.reviewStatus
    },
    model: {
      value: (_vm.declarationValueItem),
      callback: function($$v) {
        _vm.declarationValueItem = $$v
      },
      expression: "declarationValueItem"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-bottom": "0px"
    }
  }, [_c('span', {
    staticClass: "item-title",
    staticStyle: {
      "width": "126px"
    }
  }, [_vm._v("海关条形码")]), _vm._v(" "), _c('el-input', {
    staticClass: "hs-upc-code",
    staticStyle: {
      "width": "150px",
      "display": "inline-block"
    },
    attrs: {
      "disabled": _vm.isMutipleUpc
    },
    model: {
      value: (_vm.hsUpcCode),
      callback: function($$v) {
        _vm.hsUpcCode = $$v
      },
      expression: "hsUpcCode"
    }
  })], 1)], 2), _vm._v(" "), _c('div', {
    staticClass: "dialog-footer",
    slot: "footer"
  }, [_c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doSettingCustomItem
    }
  }, [_vm._v("确 定")])], 1)])], 1), _vm._v(" "), _c('el-tab-pane', {
    staticClass: "erp-sku-FE erp-sku-price-info",
    attrs: {
      "label": "价格信息",
      "name": "third",
      "disabled": !_vm.isFEStatus
    }
  }, [_c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.isFEStatus),
      expression: "isFEStatus"
    }],
    staticClass: "price-info"
  }, [_c('header', [_vm._v("价格信息")]), _vm._v(" "), _c('table', {
    staticClass: "erp-sku-info",
    staticStyle: {
      "table-layout": "fixed",
      "word-break": "break-all"
    },
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('thead', [_c('tr', [_c('th', [_vm._v("SKU ID")]), _vm._v(" "), _c('th', [_vm._v("商品属性名")]), _vm._v(" "), _c('th', [_vm._v("商品属性值")]), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "480px"
    }
  }, [_c('el-row', {
    attrs: {
      "gutter": 0
    }
  }, [_c('el-col', {
    attrs: {
      "span": 18
    }
  }, [_c('span', {
    attrs: {
      "title": "价格"
    }
  }, [_vm._v("价格")])]), _vm._v(" "), _c('el-col', {
    staticClass: "set-btn",
    attrs: {
      "span": 6
    }
  }, [_c('span', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.editStatus),
      expression: "editStatus"
    }],
    on: {
      "click": _vm.setPriceInfo
    }
  }, [_vm._v("设置")])]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })], 1)], 1), _vm._v(" "), _c('th', {
    staticStyle: {
      "width": "220px"
    }
  }, [_vm._v("仓库")])])]), _vm._v(" "), _c('tbody', _vm._l((_vm.optionPrice), function(item, key, index) {
    return _c('tr', [_c('td', [_c('span', {
      staticClass: "sku-id"
    }, [_vm._v(_vm._s(key))])]), _vm._v(" "), _c('td', {
      staticClass: "option-items option-name-value",
      domProps: {
        "innerHTML": _vm._s(item.optNames)
      }
    }), _vm._v(" "), _c('td', {
      staticClass: "option-items option-name-value",
      domProps: {
        "innerHTML": _vm._s(item.optValues)
      }
    }), _vm._v(" "), _c('td', {
      staticClass: "price-td"
    }, [_c('div', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "warehouse-set"
    }, [_c('div', {
      staticClass: "item-content"
    }), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    })]), _vm._v(" "), _vm._l((_vm.warehousePriceLink[key]), function(value) {
      return _c('div', {
        staticClass: "Warehouse-set price-info",
        class: {
          'hasBorderTop': _vm.editStatus
        }
      }, [_c('div', {
        staticClass: "item-content market-price-content"
      }, [_c('span', {
        staticClass: "item-price"
      }, [_vm._v("市场价")]), _vm._v(" "), _c('el-input', {
        staticClass: "market-price",
        attrs: {
          "placeholder": "",
          "value": value.marketPrice,
          "disabled": _vm.showStatus || _vm.reviewStatus,
          "data-id": value.id,
          "type": "text"
        },
        on: {
          "blur": _vm.checkNum
        }
      })], 1), _vm._v(" "), _c('div', {
        staticClass: "item-content purchasing-price-content"
      }, [_c('span', {
        staticClass: "item-price"
      }, [_vm._v("采购价")]), _vm._v(" "), _c('el-input', {
        staticClass: "purchasing-price",
        attrs: {
          "placeholder": "",
          "value": value.purchasePrice,
          "disabled": _vm.showStatus || _vm.reviewStatus,
          "data-id": value.id,
          "type": "text"
        },
        on: {
          "blur": _vm.checkNum
        }
      })], 1), _vm._v(" "), _c('div', {
        staticClass: "item-content"
      }, [_c('span', {
        staticClass: "item-price rate-content"
      }, [_vm._v("毛利率")]), _vm._v(" "), _c('el-input', {
        staticClass: "gross-interest-rate",
        attrs: {
          "placeholder": "",
          "value": value.grossProfitMargin,
          "disabled": _vm.showStatus || _vm.reviewStatus,
          "data-id": value.id,
          "type": "text"
        },
        on: {
          "blur": _vm.countSale
        }
      })], 1), _vm._v(" "), _c('div', {
        staticClass: "item-content"
      }, [_c('span', {
        staticClass: "item-price sale-content"
      }, [_vm._v("销售价")]), _vm._v(" "), _c('el-input', {
        staticClass: "sale-price",
        attrs: {
          "placeholder": "",
          "value": value.realPrice,
          "disabled": _vm.showStatus || _vm.reviewStatus,
          "data-id": value.id,
          "type": "text"
        },
        on: {
          "blur": _vm.countRate
        }
      })], 1)])
    })], 2), _vm._v(" "), _c('td', {
      staticClass: "price-td"
    }, [_c('div', {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: (_vm.editStatus),
        expression: "editStatus"
      }],
      staticClass: "item-content Warehouse-content"
    }, [_c('el-select', {
      attrs: {
        "placeholder": "请选择",
        "data-index": key
      },
      on: {
        "change": function($event) {
          _vm.selectWareHouse($event)
        }
      },
      model: {
        value: (_vm.warehouseValue[key]),
        callback: function($$v) {
          _vm.$set(_vm.warehouseValue, key, $$v)
        },
        expression: "warehouseValue[key]"
      }
    }, _vm._l((_vm.warehouseList), function(value, k) {
      return _c('el-option', {
        key: key,
        staticClass: "warehouse-list",
        attrs: {
          "data-index": key,
          "label": value.CD_VAL,
          "value": value.CD_VAL,
          "data-code": k
        }
      })
    })), _vm._v(" "), _c('el-button', {
      staticClass: "add-sku-btn",
      staticStyle: {
        "padding-left": "8px"
      },
      attrs: {
        "type": "button",
        "icon": "plus",
        "data-index": key
      },
      on: {
        "click": function($event) {
          _vm.addWareHouse($event)
        }
      }
    }, [_vm._v("添加\n                  ")])], 1), _vm._v(" "), _vm._l((_vm.warehousePriceLink[key]), function(value, k) {
      return _c('div', {
        staticClass: "item-content warehouse-content",
        class: {
          'isNotEditClass': !_vm.editStatus
        }
      }, [_c('span', {
        staticClass: "warehouse-id",
        attrs: {
          "data-code": value.warehouse,
          "data-id": value.id
        }
      }, [_vm._v(_vm._s(value.name))]), _vm._v(" "), _c('el-button', {
        directives: [{
          name: "show",
          rawName: "v-show",
          value: (_vm.editStatus),
          expression: "editStatus"
        }],
        staticClass: "add-sku-btn",
        staticStyle: {
          "padding-left": "8px"
        },
        attrs: {
          "type": "button",
          "icon": "close",
          "data-id": value.id,
          "data-index": key
        },
        on: {
          "click": _vm.delWareHouse
        }
      }, [_vm._v("删除\n                  ")])], 1)
    })], 2)])
  }))]), _vm._v(" "), _c('el-dialog', {
    staticClass: "price-set",
    attrs: {
      "title": "批量修改价格",
      "visible": _vm.dialogPriceVisible
    },
    on: {
      "update:visible": function($event) {
        _vm.dialogPriceVisible = $event
      }
    }
  }, [_c('div', {
    staticClass: "dialog-content"
  }, [_c('div', {
    staticClass: "row-content price-content"
  }, [_c('div', {
    staticClass: "item-content",
    staticStyle: {
      "margin-right": "8px"
    }
  }, [_c('span', {
    staticClass: "item-price"
  }, [_vm._v("市场价")]), _vm._v(" "), _c('el-input', {
    staticClass: "market-price",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.marketBlur
    },
    model: {
      value: (_vm.marketPrice),
      callback: function($$v) {
        _vm.marketPrice = $$v
      },
      expression: "marketPrice"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticStyle: {
      "flex": "3",
      "display": "flex",
      "border-left": "2px solid #1E7EB4"
    }
  }, [_c('div', {
    staticClass: "item-content"
  }, [_c('span', {
    staticClass: "item-price",
    staticStyle: {
      "padding-left": "6px"
    }
  }, [_vm._v("采购价")]), _vm._v(" "), _c('el-input', {
    staticClass: "purchasing-price",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.purchaseBlur
    },
    model: {
      value: (_vm.purchasePrice),
      callback: function($$v) {
        _vm.purchasePrice = $$v
      },
      expression: "purchasePrice"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "item-content"
  }, [_c('span', {
    staticClass: "item-price"
  }, [_vm._v("毛利率")]), _vm._v(" "), _c('el-input', {
    staticClass: "gross-interest-rate",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.rateBlur
    },
    model: {
      value: (_vm.grossInterestRate),
      callback: function($$v) {
        _vm.grossInterestRate = $$v
      },
      expression: "grossInterestRate"
    }
  })], 1), _vm._v(" "), _c('div', {
    staticClass: "item-content"
  }, [_c('span', {
    staticClass: "item-price"
  }, [_vm._v("销售价")]), _vm._v(" "), _c('el-input', {
    staticClass: "sale-price",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.saleBlur
    },
    model: {
      value: (_vm.salePrice),
      callback: function($$v) {
        _vm.salePrice = $$v
      },
      expression: "salePrice"
    }
  })], 1)])]), _vm._v(" "), _c('div', {
    staticClass: "dialog-footer",
    slot: "footer"
  }, [_c('el-button', {
    on: {
      "click": function($event) {
        _vm.dialogPriceVisible = false
      }
    }
  }, [_vm._v("取 消")]), _vm._v(" "), _c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.doSettingPrice
    }
  }, [_vm._v("确 定")])], 1)])])], 1)])], 1), _vm._v(" "), _c('div', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showBackText),
      expression: "showBackText"
    }],
    staticClass: "open-bac-text"
  }, [_c('el-input', {
    attrs: {
      "type": "textarea",
      "rows": 4,
      "placeholder": "请输入退回理由",
      "resize": "none"
    },
    model: {
      value: (_vm.backText),
      callback: function($$v) {
        _vm.backText = $$v
      },
      expression: "backText"
    }
  }), _vm._v(" "), _c('div', {
    staticClass: "confirm-btn"
  }, [_c('el-button', {
    staticClass: "confirm-to-back",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.backToReview
    }
  }, [_vm._v("确认退回")]), _vm._v(" "), _c('el-button', {
    staticClass: "cancle-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.cancelBackText
    }
  }, [_vm._v("取消")])], 1)], 1)], 1)
},staticRenderFns: []}

/***/ }),

/***/ 46:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(16)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(11),
  /* template */
  __webpack_require__(17),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


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

},[141]);