webpackJsonp([6],{

/***/ 104:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(219)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(146),
  /* template */
  __webpack_require__(228),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

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

/***/ 134:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(104);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__app__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_jquery__ = __webpack_require__(0);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_jquery___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2_jquery__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__assets_reset_css__ = __webpack_require__(4);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__assets_reset_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_3__assets_reset_css__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_element_ui__ = __webpack_require__(5);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_element_ui___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_4_element_ui__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_vue_quill_editor__ = __webpack_require__(26);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_vue_quill_editor___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_5_vue_quill_editor__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6_element_ui_lib_theme_default_index_css__ = __webpack_require__(3);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6_element_ui_lib_theme_default_index_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_6_element_ui_lib_theme_default_index_css__);







__WEBPACK_IMPORTED_MODULE_0_vue__["default"].use(__WEBPACK_IMPORTED_MODULE_5_vue_quill_editor___default.a);
__WEBPACK_IMPORTED_MODULE_0_vue__["default"].use(__WEBPACK_IMPORTED_MODULE_4_element_ui___default.a);

new __WEBPACK_IMPORTED_MODULE_0_vue__["default"]({
  components: { App: __WEBPACK_IMPORTED_MODULE_1__app___default.a }
}).$mount('app');

/***/ }),

/***/ 14:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 146:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_typeof__ = __webpack_require__(40);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_typeof___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_typeof__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_values__ = __webpack_require__(51);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_values___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_values__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_assign__ = __webpack_require__(38);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_assign___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_assign__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify__ = __webpack_require__(10);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys__ = __webpack_require__(39);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty__ = __webpack_require__(27);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6__api_index_js__ = __webpack_require__(2);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__ = __webpack_require__(8);







var _methods;



/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    var _ref;

    return _ref = {
      isMutipleUpc: "",
      hsUpcCode: "",

      UPCMain: {},
      firstUPC: "",
      UPCcontainer: [],
      UPCVisible: false,
      contentUPC: "",

      editorOption01: {
        modules: {
          toolbar: "#toolbar01"
        }
      },
      editorOption02: {
        modules: {
          toolbar: "#toolbar02"
        }
      },
      editorOption03: {
        modules: {
          toolbar: "#toolbar03"
        }
      },
      editorOption04: {
        modules: {
          toolbar: "#toolbar04"
        }
      },
      editor: {},
      thisEditor: "",
      length: "",

      showSKUinfo: false,

      dialogSaleVisible: false,
      dialogUnitVisible: false,
      dialogCustomInfoVisible: false,
      dialogCustomItemVisible: false,
      dialogPriceVisible: false,

      activeNameMain: "firstMain",

      checkedAllPlatform: true,
      checkListPlatform: ["全部"],
      PlatformList: {},
      checkedPlatformCode: [],

      goodsType: "",
      goodsTypeCode: "",
      goosdTypeList: [],

      FETag: "0",

      goodsDesValue: "产品介绍",

      goodsDesList: [],
      goodsDesCode: "N000760100",
      goodsDetailContent: {
        desc: {}
      },

      cnEditor: "",
      enEditor: "",
      krEditor: "",
      jaEditor: "",

      drawback: "Y",
      drawbackAccess: false,

      drawbackPercent: "",
      drawbackList: [],
      drawbackPercentCode: "",

      bondedWarehouse: "",
      bondedWarehouseList: [],
      bondedWarehouseCode: "",

      packingNum: "",

      minNum: "",

      maxNum: "",

      logisticsCategory: "",
      logisticsCategoryList: [],
      logisticsCategoryCode: "",

      logisticsTypes: "",
      logisticsTypesList: [],
      logisticsTypesCode: "",

      warehouseList: [],
      warehouseValue: {},
      warehouseCode: {},

      saleStatu: { freight: {}, sale: {} },
      saleStatusValue: "",
      saleStatusValueTemp: "",
      saleStatusList: {},
      saleStatusCode: "",

      declarationValue: "",

      skuWidthTemp: "",
      skuLengthTemp: "",
      skuWeightTemp: "",
      skuHeightTemp: "",

      declarationValueTemp: "",

      currentBtn: "",

      declarationValueItem: "",

      optionPrice: {},

      warehousePriceLink: {},

      purchasePrice: "",
      purchasePriceTemp: "",

      marketPrice: "",
      marketPriceTemp: "",

      grossInterestRate: "",
      grossInterestRateTemp: "",

      salePrice: "",
      salePriceTemp: "",

      textTools: "Chinese",

      loading01: false,
      loading02: false,
      loading03: false,
      loading04: false,

      activeName: "first",

      cnContent: {},
      enContent: {},
      krContent: {},
      jaContent: {},

      brandName: {},
      brandNameValue: "",

      brandCategory: {},
      brandCat: "",
      brandCategoryValue: "",

      categoryCode: "",

      categoryLevel: "",

      codeArr: [],

      catLv1: [],
      catLv2: [],
      catLv3: [],
      catLv4: [],

      brandId: "",

      goodsUnit: "",
      goodsUnitValue: "个",
      goodsUnitID: "N000690101",

      cnPic: ""
    }, __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "cnContent", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "enPic", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "enContent", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "krPic", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "krContent", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "jaPic", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "jaContent", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "mainGudsId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "gudsId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currency", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currencyId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currencyValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "originPlace", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "originPlaceId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "originPlaceValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "goodsShelfLife", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel01", "1"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel02", "2"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel03", "3"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "categoryLevel04", "4"), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "chineseTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "englishTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "koreaTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "japanTitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "chineseSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "englishSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "koreaSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "japanSubtitle", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "checkList", ["中文", "英文"]), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "cnLanguage", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "enLanguage", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "krLanguage", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "jaLanguage", true), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "KoreanOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "ChineseOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "EnglishOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "JapaneseOptionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionNameId", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "AddOptionPostData", {
      optNameCode: "",
      optValues: []
    }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "KoreanOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "ChineseOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "EnglishOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "JapaneseOptionValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionInfoItem", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionValueAll", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "selectedOptionInfoItem", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "addSkuOptionLine", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "nameCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "valueCode", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWidth", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuHeight", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuPrice", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuWeight", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuLength", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "searchBarValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionNameValue", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionNameObj", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionValueObj", []), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "optionName", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "showSearchBox", false), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "maxLength", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeature", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeatureAll", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeatureContent", {}), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "currentRow", ""), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_ref, "skuFeatureTemp", {}), _ref;
  },
  created: function created() {
    this.getBrand();

    this.getCate01();
    this.getBasicOptions();
  },

  methods: (_methods = {
    setCustomsInfo: function setCustomsInfo() {
      var vm = this;
      vm.skuFeatureAll = vm.cloneObj(vm.skuFeature);
      console.log(vm.skuFeature);
      vm.dialogCustomInfoVisible = true;
    },
    addUPCitem: function addUPCitem() {
      this.UPCcontainer.push("");
    },
    delUPCitem: function delUPCitem(event, index) {
      this.$delete(this.UPCcontainer, index);
    },
    setUPC: function setUPC(event, index) {
      this.UPCVisible = true;
      $('.doSetting-UPC-btn').attr("data-index", index);
      if (Object.prototype.hasOwnProperty.call(this.UPCMain, index)) {
        this.firstUPC = this.UPCMain[index].split(",")[0];
        this.UPCcontainer = this.UPCMain[index].split(',').slice(1);
      }
      this.contentUPC = $(event.currentTarget).prev("span");
    },
    doSettingUPC: function doSettingUPC(event) {
      var UPCarr = [];
      var index = $('.doSetting-UPC-btn').attr("data-index");
      $('.UPC-box').find('input').each(function (index, el) {
        if ($.trim($(this).val()) && $.inArray($(this).val(), UPCarr) == -1) {
          UPCarr.push($(this).val());
        }
      });
      this.UPCMain[index] = UPCarr.join(',');
      this.contentUPC.text(UPCarr.join(','));
      this.UPCcontainer = [];
      this.firstUPC = "";
      this.UPCVisible = false;
      console.log(UPCarr);
      if (UPCarr.length > 1) {
        $('.erp-sku-info.erp-sku-FE tbody').find("tr").eq(index).find('.edit-custom-btn').click();
        this.$message({
          type: 'info',
          message: '您已设置了多个UPC,请在海关申报信息中填写海关条形码!'
        });
      } else if (UPCarr.length == 1 && UPCarr.join(',')) {
        $('.erp-sku-info.erp-sku-FE tbody').find("tr").eq(index).find('.upc-content').html(UPCarr.join(','));
      }
    },
    handleClick: function handleClick(tab, event) {
      console.log(tab, event);
    },
    handleClick01: function handleClick01(tab, event) {
      var t = tab.name;
      console.log(t);
      $("." + t).find(".ql-editor").focus();
    },
    getBrand: function getBrand() {
      var vm = this;
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getBrand(),
        type: "GET",
        dataType: "json"
      }).success(function (data) {
        vm.brandName = data.data.brandList;
        vm.goodsUnit = data.data.unit;
        vm.goosdTypeList = data.data.productType;
        vm.goodsDesList = data.data.productDesc;
      }).error(function () {
        vm.$message({ showClose: true, message: "error", type: "error" });
      }).complete(function () {});
    },
    getCate01: function getCate01() {
      var vm = this;
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
        }
      }).error(function () {
        vm.$message({ showClose: true, message: "error", type: "error" });
      }).complete(function () {});
    },
    changeBrand: function changeBrand(val) {
      this.brandId = val;
      $(".brand-name input").css("borderColor", "#bfcbd9");
    },
    selcetGoodsUnit: function selcetGoodsUnit(val) {
      this.goodsUnitID = val;
      $(".selectUnit input").css("borderColor", "#bfcbd9");
    },
    filterDisplay: function filterDisplay(item) {
      for (var key in item) {
        if (item[key]['disable'] == "N") {
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
    toggleCheck: function toggleCheck(event) {
      event.indexOf("中文") != -1 ? this.cnLanguage = false : this.cnLanguage = true;
      event.indexOf("英文") != -1 ? this.enLanguage = false : this.enLanguage = true;
      event.indexOf("韩文") != -1 ? this.krLanguage = false : this.krLanguage = true;
      event.indexOf("日文") != -1 ? this.jaLanguage = false : this.jaLanguage = true;
    },
    updatePic: function updatePic() {
      var vm = this;
      var lang = event.currentTarget.className;
      var langContent = event.currentTarget.getAttribute("id");
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
            vm[langContent] = data.data;
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
    createGoodBasic: function createGoodBasic() {
      var vm = this;
      var postData = "";
      console.log(vm.goodsDetailContent.desc);
      var flag = true;
      var langData = {};

      if (!vm.cnLanguage) {
        langData["N000920100"] = {
          gudsName: vm.chineseTitle,
          gudsSubName: vm.chineseSubtitle,
          imgData: vm.cnContent,
          detail: vm.cnEditor
        };
      }
      if (!vm.krLanguage) {
        langData["N000920400"] = {
          gudsName: vm.koreaTitle,
          gudsSubName: vm.koreaSubtitle,
          imgData: vm.krContent,
          detail: vm.krEditor
        };
      }
      if (!vm.enLanguage) {
        langData["N000920200"] = {
          gudsName: vm.englishTitle,
          gudsSubName: vm.englishSubtitle,
          imgData: vm.enContent,
          detail: vm.enEditor
        };
      }
      if (!vm.jaLanguage) {
        langData["N000920300"] = {
          gudsName: vm.japanTitle,
          gudsSubName: vm.japanSubtitle,
          imgData: vm.jaContent,
          detail: vm.jaEditor
        };
      }
      postData = {
        publishType: "1",
        cateId: vm.categoryCode,
        brandId: vm.brandId,
        brandName: $('.brand-name').find('input').val(),
        unit: vm.goodsUnitID,
        originCountry: vm.originPlaceId,
        currency: vm.currencyId,
        isRefundTax: vm.drawback,
        refundTaxRate: vm.drawbackPercentCode,
        overseasTax: vm.bondedWarehouseCode,
        PCS: vm.packingNum,
        minBuyNum: vm.minNum,
        maxBuyNum: vm.maxNum,
        expressCat: vm.logisticsCategoryCode,
        expressType: vm.logisticsTypesCode,
        isLifetime: vm.goodsShelfLife,
        saleChannel: vm.checkedPlatformCode.length > 0 ? vm.checkedPlatformCode : __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default()(vm.PlatformList),

        productFlag: vm.FETag,
        desc: vm.goodsDetailContent.desc,
        langData: langData
      };
      if (postData.overseasTax == "" || postData.PCS == "" || postData.minBuyNum == "" || postData.maxBuyNum == "" || postData.expressCat == "" || postData.expressType == "" || postData.isLifetime == "") {
        flag = false;
        if (vm.drawback == "Y") {
          if (postData.refundTaxRate == "") {
            $(".drawback-per input").css("borderColor", "red");
            vm.$alert("请选择返税比率", {
              confirmButtonText: "确定"
            });
          }
        }
        if (postData.isLifetime == "") {
          vm.$alert("请选择 有无有效期", {
            confirmButtonText: "确定"
          });
        } else if (postData.overseasTax == "") {
          $(".bonded-warehouse input").css("borderColor", "red");
          vm.$alert("请选择跨境电商综合税", {
            confirmButtonText: "确定"
          });
        } else if (postData.PCS == "") {
          $(".pack-num input").css("borderColor", "red");
          vm.$alert("请填写装箱数", {
            confirmButtonText: "确定"
          });
        } else if (postData.minBuyNum == "") {
          $(".min-num input").css("borderColor", "red");
          vm.$alert("请填写最小起订数", {
            confirmButtonText: "确定"
          });
        } else if (postData.maxBuyNum == "") {
          $(".max-num input").css("borderColor", "red");
          vm.$alert("请填写最大起订量", {
            confirmButtonText: "确定"
          });
        } else if (postData.expressCat == "") {
          $(".logistic-cat input").css("borderColor", "red");
          vm.$alert("请填写物流类目", {
            confirmButtonText: "确定"
          });
        } else if (postData.expressType == "") {
          $(".logistic-type input").css("borderColor", "red");
          vm.$alert("请填写物流类型", {
            confirmButtonText: "确定"
          });
        }
      }

      vm.checkList.forEach(function (element, index) {
        if (element == "中文" && (vm.chineseTitle == "" || vm.cnPic == "" || vm.cnEditor == "" || vm.cnEditor == "<p></p>" || $.trim(vm.cnEditor.slice(3, -4)) == "")) {
          flag = false;
          vm.$alert("勾选的语言标题、主图、详细信息必填", { confirmButtonText: "确定" });
        } else if (element == "韩文" && (vm.koreaTitle == "" || vm.krPic == "" || vm.krEditor == "" || vm.krEditor == "<p></p>" || $.trim(vm.krEditor.slice(3, -4)) == "")) {
          flag = false;
          vm.$alert("勾选的语言标题、主图、详细信息必填", { confirmButtonText: "确定" });
        } else if (element == "英文" && (vm.englishTitle == "" || vm.enPic == "" || vm.enEditor == "" || vm.enEditor == "<p></p>" || $.trim(vm.enEditor.slice(3, -4)) == "")) {
          flag = false;
          vm.$alert("勾选的语言标题、主图、详细信息必填", { confirmButtonText: "确定" });
        } else if (element == "日文" && (vm.japanTitle == "" || vm.jaPic == "" || vm.jaEditor == "" || vm.jaEditor == "<p></p>" || $.trim(vm.jaEditor.slice(3, -4)) == "")) {
          flag = false;
          vm.$alert("勾选的语言标题、主图、详细信息必填", { confirmButtonText: "确定" });
        }
      });
      if (postData.cateId == "" || postData.brandId == "" || postData.brandName == "" || postData.unit == "" || vm.checkList.length == 0 || postData.isLifetime == "" || postData.originCountry == "" || postData.currency == "") {
        flag = false;
        if (postData.cateId == "") {
          vm.$alert("请选择类目", {
            confirmButtonText: "确定"
          });
        } else if (postData.unit == "") {
          $(".selectUnit input").css("borderColor", "red");
          vm.$alert("请选择单位", {
            confirmButtonText: "确定"
          });
        } else if (postData.brandName == "") {
          $(".brand-name input").css("borderColor", "red");
          vm.$alert("请选择品牌", {
            confirmButtonText: "确定"
          });
        } else if (postData.isLifetime == "") {
          vm.$alert("请选择 有无有效期", {
            confirmButtonText: "确定"
          });
        } else if (vm.checkList.length == 0) {
          vm.$alert("请选择 语言", {
            confirmButtonText: "确定"
          });
        } else if (postData.currency == "") {
          vm.$alert("请选择 币种", {
            confirmButtonText: "确定"
          });
          $("#currency_choice input").css("borderColor", "red");
        } else if (postData.originCountry == "") {
          vm.$alert("请选择 产地", {
            confirmButtonText: "确定"
          });
          $("#origin_choice input").css("borderColor", "red");
        }
      } else if ($.inArray("中文", vm.checkList) == -1 || $.inArray("英文", vm.checkList) == -1) {
        flag = false;
        vm.$alert("中文英文信息必填", {
          confirmButtonText: "确定"
        });
      }
      if (flag) {
        $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].createGoodsBasic(),
          type: "POST",
          dataType: "json",
          data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(postData)
        }).success(function (data) {
          if (data.code == 2000) {
            vm.mainGudsId = data.data.mainId;
            vm.gudsId = data.data.langData[__WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default()(data.data.langData)[0]].gudsId;
            $(".erp-addbasic-btns").hide();
            vm.$alert("保存成功", {
              confirmButtonText: "确定",
              callback: function callback(action) {
                vm.activeName = "second";
              }
            });
          } else if (data.code == 40000101) {
            vm.$alert("中文 英文信息必填", { confirmButtonText: "确定" });
          } else {
            vm.$alert(data.msg, { confirmButtonText: "确定" });
          }
        }).error(function () {
          console.log("error");
        }).complete(function () {});
      }
    },
    getBasicOptions: function getBasicOptions() {
      var vm = this;
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getBasicOptions(),
        type: "GET",
        dataType: "json"
      }).success(function (data) {
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
          vm.checkListPlatform = ["全部"];
          vm.skuFeature = data.data.skuFeature;
          for (var key in vm.skuFeature) {
            vm.$set(vm.skuFeature[key], "radioVal", "");
          }
          vm.skuFeatureAll = vm.cloneObj(vm.skuFeature);
        }
      }).error(function () {
        vm.$message({ showClose: true, message: "error", type: "error" });
      }).complete(function () {});
    },
    selectCurrency: function selectCurrency(val) {
      this.currencyId = val;
      $("#currency_choice input").css("borderColor", "#bfcbd9");
    },
    selectOriginPlace: function selectOriginPlace(val) {
      this.originPlaceId = val;
      $("#origin_choice input").css("borderColor", "#bfcbd9");
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
          $('.option-info-item').each(function (ind, el) {
            codeItems.split(',').forEach(function (element, index) {
              console.log(el.getAttribute('data-code'));
              console.log(element);
              if (el.getAttribute('data-code') == element) {
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
    delSkuOption: function delSkuOption(index) {
      this.addSkuOptionLine.splice(index, 1);
    },
    handleClose: function handleClose(done) {
      done();
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
              id = __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default()(data.data)[0];
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

      for (var key in vm.optionValueAll) {
        if (vm.optionValueAll[key].CODE == id) {
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
    saveOptionInfo: function saveOptionInfo() {
      var vm = this;
      var index = document.querySelector(".option-search-box").getAttribute("data-index");
      var thisCode = document.querySelector(".option-search-box").getAttribute("data-code");

      if (index != null) {
        var flag = false;
        if (this.optionNameValue && this.selectedOptionInfoItem.length > 0) {
          flag = true;

          var optionName = [];
          var valueCode = "";
          var optionValue = [];

          this.selectedOptionInfoItem.forEach(function (el, index) {
            optionValue.push(el.value);
            valueCode += el.id + ",";
          });

          if (flag) {
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
        var _flag = false;
        if (this.optionNameValue && this.selectedOptionInfoItem.length > 0) {
          _flag = true;
          var _optionValue = [];
          var _valueCode = "";
          this.selectedOptionInfoItem.forEach(function (el, index) {
            _optionValue.push(el.value);
            _valueCode += el.id + ",";
          });

          var _vm = this;

          _vm.addSkuOptionLine.forEach(function (element, index) {
            if (element["nameCode"] == _vm.optionNameId) {
              _flag = false;
              _vm.$alert("已经存在相同的optionName", {
                confirmButtonText: "确定"
              });
            }
          });
          if (_flag) {
            _vm.addSkuOptionLine.push({
              optionName: _vm.optionNameValue,
              optionValue: _optionValue,
              nameCode: _vm.optionNameId,
              valueCode: _valueCode.substring(0, _valueCode.length - 1)
            });
            _vm.resetOption();
            _vm.showSearchBox = false;
            __WEBPACK_IMPORTED_MODULE_7__utils_utils_js__["a" /* default */].hideOverlay();
          }
        } else {
          this.$alert("optionValue不能为空", {
            confirmButtonText: "确定"
          });
        }
        console.log(this.addSkuOptionLine);
      }
    },
    resetOption: function resetOption() {
      this.optionNameValue = "";
      this.selectedOptionInfoItem = [];
      this.optionInfoItem = [];
      $(".option-add").find("input").val("");
      $(".search-optioninfo").find("input").val("");
    },
    selectWhatToAdd: function selectWhatToAdd() {
      var vm = this;

      var index = event.currentTarget.getAttribute("data-index");
      var flag = true;
      var select = event.currentTarget;
      if ($(select).parent().hasClass('is_select_option')) {
        $(select).parent().removeClass('is_select_option');
      } else {
        $(select).parent().siblings().each(function (index, el) {
          if ($(this).hasClass('is_select_option')) {
            $(this).removeClass('is_select_option');
          }
        });
        $(select).parent().addClass('is_select_option');
      }
    },
    delRowOption: function delRowOption() {
      var vm = this;
      console.log(vm.addSkuOptionLine);
      var current = $(event.currentTarget);
      vm.addSkuOptionLine.forEach(function (element, index) {
        if (element.nameCode == current.prev('span').attr('data-parcode')) {
          var ind = vm.addSkuOptionLine[index].valueCode.split(',').indexOf(current.prev('span').attr('data-code'));
          if (ind != -1) {
            console.log("要删除的index:" + ind);
            var arr = vm.addSkuOptionLine[index].valueCode.split(',');
            var valueArr = vm.addSkuOptionLine[index].optionValue;
            console.log("删除之前：" + arr.join(','));
            arr.splice(ind, 1);
            valueArr.splice(ind, 1);
            vm.addSkuOptionLine[index].valueCode = arr.join(',');
            vm.addSkuOptionLine[index].optionValue = valueArr;
            console.log("删除之后：" + arr.join(','));
            console.log(vm.addSkuOptionLine);
          }
        }
      });
    },
    checkSKU: function checkSKU(str) {
      var vm = this;
      var mainArr = [];
      console.log(vm.optionValueObj);
      vm.optionValueObj.forEach(function (element, index) {
        var arr = [];
        for (var key in element) {
          arr.push(element[key]['CODE']);
        }

        mainArr.push(arr.sort().join(','));
      });

      if (mainArr.indexOf(str.split(',').sort().join(',')) == -1) {
        return true;
      } else {
        return false;
      }
    },
    singleToBuild: function singleToBuild() {
      var vm = this;
      vm.showSKUinfo = true;
      var currentSKU = {};
      var inObj = {};
      var isSelectedNone = true;
      var checkSKU = [];
      var row = vm.optionValueObj.length;
      $('.erp-add-sku .added-sku-option').each(function (index, element) {
        $(element).find('.item-option-value').each(function (ind, el) {
          if ($(el).parent().hasClass('is_select_option')) {
            checkSKU.push($(el).attr('data-code'));
            isSelectedNone = false;
            inObj[$(el).attr('data-parcode')] = {
              "ALL_VAL": $(el).text(),
              "CODE": $(el).attr('data-code'),
              "PAR_CODE": $(el).attr('data-parcode')
            };
            setTimeout(function () {
              $('#option-name-content tr').eq(row).find('.option-value-lists').eq(index).text($(el).text()).attr('title', $(el).text()).attr('data-code', $(el).attr('date-code')).attr('data-parcode', $(el).attr('data-parcode'));
              $(element).find('.item-option-content').removeClass('is_select_option');
            }, 50);
          }
        });
      });
      if (!isSelectedNone && vm.checkSKU(checkSKU.join(','))) {
        vm.optionValueObj.push(inObj);
        var length = __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default()(vm.skuFeatureContent).length;
        vm.skuFeatureContent[length] = vm.cloneObj(vm.skuFeature);
      } else if (!vm.checkSKU(checkSKU.join(','))) {

        vm.$message({
          showClose: true,
          message: "该Option组合已经存在",
          type: "error"
        });
      } else {
        vm.$message({
          showClose: true,
          message: "请至少选择一个Option",
          type: "error"
        });
      }
      vm.$set(vm.saleStatu.sale, row, {
        code: "",
        value: ""
      });
    },
    applyToBuild: function applyToBuild() {
      var vm = this;
      var flag = true;
      var postData = {};

      vm.addSkuOptionLine.forEach(function (element, index) {
        if (element.valueCode == "") {
          flag = false;
        }
        postData[element.nameCode] = element.valueCode;
      });

      if (!$.isEmptyObject(postData)) {
        if (!flag) {
          vm.$message({ showClose: true, message: "option name 不能为空", type: "error" });
        } else {
          $.ajax({
            url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getOptionGroup(),
            type: "POST",
            dataType: "JSON",
            data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(postData),
            cache: false
          }).success(function (data) {
            vm.optionNameObj = data.data.optionNames;
            vm.optionValueObj = data.data.optionGroup;
            for (var i = vm.optionValueObj.length - 1; i >= 0; i--) {
              vm.$set(vm.saleStatu.sale, i, {
                code: "",
                value: ""
              });
            }
            setTimeout(function () {
              for (var i = vm.optionValueObj.length - 1; i >= 0; i--) {
                vm.skuFeatureContent[i] = vm.cloneObj(vm.skuFeature);
                $('#option-name-content tr').eq(i).find('.option-value-lists').each(function (index, element) {
                  __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default()(vm.optionValueObj[i]).forEach(function (ele, ind) {
                    if ($('.option-name-lists').eq(index).attr('data-namecode') == vm.optionValueObj[i][ele]['PAR_CODE']) {
                      console.log(ele);
                      var $this = vm.optionValueObj[i][ele];
                      $(element).text($this.ALL_VAL).attr('data-code', $this.CODE).attr("data-parcode", $this.PAR_CODE).attr("title", $this.ALL_VAL);
                    }
                  });
                });
              }
            }, 50);
            vm.showSKUinfo = true;
          }).error(function () {
            console.log("error");
          }).complete(function () {});
        }
      } else {
        vm.$alert("请先添加SKU", {
          confirmButtonText: "确定"
        });
      }
    },
    createGood: function createGood() {
      var vm = this;
      var optionGroup = [];
      var postData = void 0;
      var optionValueObjTmp = JSON.parse(__WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(vm.optionValueObj));
      var flag = true;
      optionValueObjTmp.forEach(function (element, index) {
        var $tr = $(".erp-sku-info.erp-sku-FE>tbody>tr:eq(" + index + ")");

        if ($tr.find(".sale-status-lists").find(".el-input__inner").val() == "") {
          vm.$alert("请选择销售状态", {
            confirmButtonText: "确定"
          });
          flag = false;
        } else if (!$tr.find('.upc-content').html() && $tr.find(".upc-value").attr('title') && $tr.find(".upc-value").attr('title').split(",").length > 1) {
          vm.$alert("拥有多个UPC的SKU需要在海关申报信息中填写海关条形码", {
            confirmButtonText: "确定"
          });
          flag = false;
        }
        element["attributes"] = {
          PRICE: "",
          UPC: $tr.find(".upc-value").attr("title") ? $tr.find(".upc-value").attr("title") : "",
          CR: $tr.find(".cr-code-value").find("input").val(),
          HS: $tr.find(".hs-code-value").find("input").val(),
          LENGTH: $tr.find(".length-value").find("input").val(),
          WIDTH: $tr.find(".width-value").find("input").val(),
          HEIGHT: $tr.find(".height-value").find("input").val(),
          WEIGHT: $tr.find(".weight-value").find("input").val(),
          saleState: vm.saleStatu.sale[index].code,
          hsUpcCode: $tr.find('.upc-content').html().replace(/\s+/g, ""),
          customsPrice: $tr.find(".declarationValue").text()
        };
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

        element["extension"] = extension;
        optionGroup.push(element);
      });

      postData = {
        publishType: 1,
        sellerId: vm.brandId,
        gudsId: vm.gudsId,
        mainGudsId: vm.mainGudsId,
        platform: vm.checkedPlatformCode.length > 0 ? vm.checkedPlatformCode : __WEBPACK_IMPORTED_MODULE_4_babel_runtime_core_js_object_keys___default()(vm.PlatformList),
        optionGroup: optionGroup
      };

      if (postData.sellerId == "" || postData.gudsId == "" || postData.mainGudsId == "" || postData.optionGroup.length < 1) {
        vm.$alert("数据填写不全", {
          confirmButtonText: "确定"
        });

        flag = false;
        if (postData.optionGroup.length < 1) {
          vm.$alert("请添加SKU", {
            confirmButtonText: "确定"
          });
        }
      }

      if (flag) {
        $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].createGoods(),
          type: "POST",
          dataType: "json",
          data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(postData)
        }).success(function (data) {
          if (data.code == 200) {
            $(".erp-addsku-btns").hide();
            vm.$alert("sku创建成功", {
              confirmButtonText: "确定",
              callback: function callback(action) {
                vm.activeName = "third";

                vm.getOptionPrice();
              }
            });
          } else {
            vm.$alert(data.msg, {
              confirmButtonText: "确定"
            });
          }
        }).error(function () {
          console.log("error");
        }).complete(function () {
          console.log("complete");
        });
      }
    },
    selectDes: function selectDes(val) {
      this.goodsDesCode = $(event.currentTarget).getAttribute('data-code');
    },
    getGoodsType: function getGoodsType() {
      this.goodsTypeCode = event.currentTarget.getAttribute("data-code");
      $(".goods-type input").css("borderColor", "#bfcbd9");
    },
    selectDrawback: function selectDrawback(val) {
      console.log(val);
      this.drawbackPercentCode = val;
      $(".drawback-per input").css("borderColor", "#bfcbd9");
    },
    selectbondedWarehouse: function selectbondedWarehouse(val) {
      this.bondedWarehouseCode = val;
      $(".bonded-warehouse input").css("borderColor", "#bfcbd9");
    },
    selectLogisticsCategory: function selectLogisticsCategory(val) {
      this.logisticsCategoryCode = val;
      $(".logistic-cat input").css("borderColor", "#bfcbd9");
    },
    selectLogisticsTypes: function selectLogisticsTypes(val) {
      this.logisticsTypesCode = val;
      $(".logistic-type input").css("borderColor", "#bfcbd9");
    },
    selectSaleStatus: function selectSaleStatus() {
      if (event.type == 'click') {
        this.saleStatusCode = event.currentTarget.getAttribute("data-code");
        console.log(this.saleStatusCode);
      }
    },
    addGoodsDes: function addGoodsDes() {
      var vm = this;
      var code = vm.goodsDesCode;
      var name = vm.goodsDesValue;

      if (vm.goodsDetailContent.desc.hasOwnProperty(code)) {
        vm.$message({ showClose: true, message: "已经存在的描述", type: "error" });
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
    checkDrawBack: function checkDrawBack(value) {

      if (value == "Y") {
        this.drawbackAccess = false;
      } else {
        this.drawbackPercent = "";
        this.drawbackPercentCode = "";
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
      type: 'success',
      message: '海关申报信息批量设置成功'
    });
    this.dialogCustomInfoVisible = false;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "editCustomInfo", function editCustomInfo(event, index) {
    var upc = "";
    console.log(this.skuFeatureContent);
    console.log(index);
    this.currentRow = index;
    this.skuFeatureTemp = __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_assign___default()({}, this.skuFeatureContent[index]);

    this.currentBtn = $(event.currentTarget);
    this.dialogCustomItemVisible = true;


    this.hsUpcCode = this.currentBtn.next(".custom-content").find(".upc-content").html();
    this.declarationValueItem = this.currentBtn.next(".custom-content").find(".declarationValue").html();
    upc = this.currentBtn.parents('tr').find('.upc-value').attr('title');
    if (upc.split(',').length > 1) {
      this.isMutipleUpc = false;
    } else {
      this.isMutipleUpc = true;
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "selectItemSaleStatus", function selectItemSaleStatus(value, index) {
    if (event.type == "click") {
      var code = event.currentTarget.getAttribute("data-code");
      if (code) {
        this.saleStatu.sale[index].code = code;
      }
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "doSettingCustomItem", function doSettingCustomItem() {
    this.currentBtn.next(".custom-content").find(".declarationValue").text(this.declarationValueItem).end().find(".upc-content").text(this.hsUpcCode);

    this.skuFeatureContent[this.currentRow] = __WEBPACK_IMPORTED_MODULE_2_babel_runtime_core_js_object_assign___default()({}, this.skuFeatureTemp);
    this.declarationValueItem = "";
    this.hsUpcCode = "";
    this.skuFeatureTemp = this.skuFeature;
    this.dialogCustomItemVisible = false;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "getOptionPrice", function getOptionPrice() {
    var vm = this;
    $.getJSON(__WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].getOptionPrice(vm.mainGudsId), function (json, textStatus) {
      if (json.code == 200) {
        vm.optionPrice = json.data.skuInfo;
        vm.maxLength = 1;
        __WEBPACK_IMPORTED_MODULE_1_babel_runtime_core_js_object_values___default()(vm.optionPrice).forEach(function (element, index) {
          if (vm.maxLength < element.length) {
            vm.maxLength = element;
          }
        });
      } else {
        vm.$message({
          showClose: true,
          type: 'error',
          message: json.msg
        });
      }
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
    var skuid = event.currentTarget.getAttribute("data-index");
    var flag = true;

    if (!vm.warehousePriceLink.hasOwnProperty(skuid)) {
      vm.$set(vm.warehousePriceLink, skuid, []);
    }
    if (vm.warehousePriceLink[skuid]) {
      if (!Object.prototype.hasOwnProperty.call(vm.warehouseCode, skuid)) {
        flag = false;
        vm.$message({
          showClose: true,
          message: "请先选择仓库",
          type: "error"
        });
      } else {
        vm.warehousePriceLink[skuid].forEach(function (element, index) {
          if (vm.warehouseCode[skuid] == element.warehouse) {
            flag = false;
            vm.$message({
              showClose: true,
              message: "已经存在的仓库",
              type: "error"
            });
          }
        });
      }
    }
    if (flag) {
      vm.warehousePriceLink[skuid].push({
        warehouse: vm.warehouseCode[index],
        name: vm.warehouseList[vm.warehouseCode[index]].CD_VAL
      });
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "delWareHouse", function delWareHouse() {
    var vm = this;
    var index = event.currentTarget.getAttribute("data-index");
    var id = event.currentTarget.getAttribute("data-id");
    var delCode = $(event.currentTarget).prev("span").attr("data-code");
    vm.warehousePriceLink[index].forEach(function (element, i) {
      if (element.warehouse == delCode) {
        vm.$delete(vm.warehousePriceLink[index], i);
      }
    });
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "addPriceInfo", function addPriceInfo() {
    var vm = this;
    var priceGroup = [];
    var flag = true;
    $(".erp-sku-price-info tbody>tr").each(function (index, el) {
      var $this = $(this);


      if ($this.find(".Warehouse-id").length == 0) {
        flag = false;
        vm.$alert("每一个sku至少需要添加一条价格信息", {
          confirmButtonText: "确定"
        });
      }
      $this.find(".warehouse-id").each(function (i, e) {
        var optionId = $this.find(".sku-id").text();
        var warehouse = $(this).data("code");
        var purchasePrice = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".purchasing-price").find("input").val();
        var marketPrice = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".market-price").find("input").val();
        var realPrice = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".sale-price").find("input").val();
        var grossProfitMargin = $this.find(".price-info.warehouse-set:eq(" + i + ")").find(".gross-interest-rate").find("input").val();
        if (purchasePrice == "" || marketPrice == "" || realPrice == "" || grossProfitMargin == "" || warehouse == "") {
          flag = false;
          vm.$alert("请填写完整数据", {
            confirmButtonText: "确定"
          });
        }
        priceGroup.push({
          optionId: optionId,
          warehouse: warehouse,
          purchasePrice: purchasePrice,
          marketPrice: marketPrice,
          realPrice: realPrice,
          grossProfitMargin: grossProfitMargin
        });
      });
    });


    var postData = {
      publishType: 1,
      mainGudsId: vm.mainGudsId,
      gudsId: vm.gudsId,
      currency: vm.currencyId,
      priceGroup: priceGroup
    };

    if (flag) {
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_6__api_index_js__["a" /* default */].addPrice(),
        type: "POST",
        dataType: "json",
        data: __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(postData)
      }).done(function (data) {
        if (data.code == 200) {
          vm.$alert("商品创建成功", {
            confirmButtonText: "确定",
            callback: function callback(action) {
              window.location.reload();
            }
          });
        } else {
          vm.$alert(data.msg, { confirmButtonText: "确定" });
        }
        console.log(data);
      }).fail(function () {
        console.log("error");
      }).always(function () {
        console.log("complete");
      });
    }
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "upload", function upload(event) {
    var vm = this;
    var data = new FormData();
    var _thisFile = "<input type='file' class='custom-input' @change='upload' style='display: none !important;'>";
    var tag = $(event.currentTarget).prev();

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

        vm.editor.insertEmbed(vm.length, "image", vm.contentImg);
        vm.contentImg = "";
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
    var vm = this;


    if ($(event.currentTarget).hasClass("cnBtn")) {
      vm.editor = vm.$refs.cnBtn.quill;
    } else if ($(event.currentTarget).hasClass("enBtn")) {
      vm.editor = vm.$refs.enBtn.quill;
    } else if ($(event.currentTarget).hasClass("jaBtn")) {
      vm.editor = vm.$refs.jaBtn.quill;
    } else if ($(event.currentTarget).hasClass("krBtn")) {
      vm.editor = vm.$refs.krBtn.quill;
    }

    var range = vm.editor.getSelection(true);

    var length = range.index;
    vm.length = length;
    $(event.currentTarget).next(".custom-input").click();
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorBlur", function onEditorBlur(editor) {
    this.editor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorFocus", function onEditorFocus(editor) {
    this.editor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "onEditorReady", function onEditorReady(editor) {
    this.editor = editor;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "clearPic", function clearPic() {
    event.currentTarget.value = null;
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "checkInputEmpty", function checkInputEmpty() {
    if (event.currentTarget.value) {
      $(event.currentTarget).css("borderColor", "#bfcbd9");
    }
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
  }), __WEBPACK_IMPORTED_MODULE_5_babel_runtime_helpers_defineProperty___default()(_methods, "cloneObj", function (_cloneObj) {
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
    if ((typeof obj === "undefined" ? "undefined" : __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_typeof___default()(obj)) !== 'object') {
      return;
    } else if (window.JSON) {
      str = __WEBPACK_IMPORTED_MODULE_3_babel_runtime_core_js_json_stringify___default()(obj), newobj = JSON.parse(str);
    } else {
      for (var i in obj) {
        newobj[i] = __WEBPACK_IMPORTED_MODULE_0_babel_runtime_helpers_typeof___default()(obj[i]) === 'object' ? cloneObj(obj[i]) : obj[i];
      }
    }
    return newobj;
  })), _methods),

  computed: {
    editor: function editor() {
      var temp = vm.thisEditor;
      return this.$refs.temp.quill;
    }
  },
  watch: {
    skuPrice: function skuPrice() {
      var vm = this;
      $(".price-value").each(function () {
        $(this).find("input").val(vm.skuPrice);
      });
    },
    skuLength: function skuLength() {
      var vm = this;
      $(".length-value").each(function () {
        $(this).find("input").val(vm.skuLength);
      });
    },
    skuHeight: function skuHeight() {
      var vm = this;
      $(".height-value").each(function () {
        $(this).find("input").val(vm.skuHeight);
      });
    },
    skuWeight: function skuWeight() {
      var vm = this;
      $(".weight-value").each(function () {
        $(this).find("input").val(vm.skuWeight);
      });
    },
    skuWidth: function skuWidth() {
      var vm = this;
      $(".width-value").each(function () {
        $(this).find("input").val(vm.skuWidth);
      });
    },

    saleStatusValue: function saleStatusValue() {
      var vm = this;
      for (var key in vm.saleStatu.sale) {
        vm.saleStatu.sale[key].value = vm.saleStatusValue;
        vm.saleStatu.sale[key]['code'] = vm.saleStatusCode;
      }
    },
    declarationValue: function declarationValue() {
      var vm = this;
      $(".erp-sku-info.erp-sku-FE").find(".custom-content").each(function (index, el) {
        $(this).find(".declarationValue").text(vm.declarationValue);
      });
    }
  }
});
/* WEBPACK VAR INJECTION */}.call(__webpack_exports__, __webpack_require__(0)))

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

/***/ 219:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 228:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "erp-mian-content"
  }, [_c('div', {
    attrs: {
      "id": "mask"
    }
  }), _vm._v(" "), _c('div', {
    staticClass: "erp-addgoods"
  }, [_c('header', [_vm._v("平台信息")]), _vm._v(" "), _c('div', {
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
      "checked": _vm.checkedAllPlatform
    },
    on: {
      "change": _vm.CheckAllPlatform
    }
  }), _vm._v(" "), _vm._l((_vm.PlatformList), function(value, key) {
    return _c('el-checkbox', {
      key: key,
      staticStyle: {
        "width": "110px"
      },
      attrs: {
        "checked": false,
        "value": value.CD_VAL,
        "label": value.CD_VAL,
        "data-code": key
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
      "label": "中文"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "英文"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "韩文"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "日文"
    }
  })], 1)], 1)], 1)], 1), _vm._v(" "), _c('header', [_vm._v("商品信息")]), _vm._v(" "), _c('table', {
    staticClass: "erp-brand-info",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tbody', [_c('tr', {
    staticClass: "tr-1"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("品牌名")]), _vm._v(" "), _c('td', [_c('el-select', {
    staticClass: "brand-name",
    attrs: {
      "placeholder": "请选择",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.changeBrand($event, _vm.brandNameValue)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.changeBrand($event, _vm.brandNameValue)
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
        "value": value.brandId
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("品牌ID")]), _vm._v(" "), _c('td', [_c('p', [_vm._v(_vm._s(_vm.brandId))])]), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("商品单位")]), _vm._v(" "), _c('td', [_c('el-select', {
    staticClass: "selectUnit",
    attrs: {
      "placeholder": "请选择",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selcetGoodsUnit($event, _vm.goodsUnitValue)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selcetGoodsUnit($event, _vm.goodsUnitValue)
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
        "value": key,
        "data-code": key
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("有无有效期")]), _vm._v(" "), _c('td', [_c('el-radio-group', {
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
    staticClass: "tr-goods"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("中文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "必填",
      "disabled": _vm.cnLanguage
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.chineseTitle),
      callback: function($$v) {
        _vm.chineseTitle = $$v
      },
      expression: "chineseTitle"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("英文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "必填",
      "disabled": _vm.enLanguage
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.englishTitle),
      callback: function($$v) {
        _vm.englishTitle = $$v
      },
      expression: "englishTitle"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("韩文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "必填",
      "disabled": _vm.krLanguage
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.koreaTitle),
      callback: function($$v) {
        _vm.koreaTitle = $$v
      },
      expression: "koreaTitle"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("日文标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "必填",
      "disabled": _vm.jaLanguage
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.japanTitle),
      callback: function($$v) {
        _vm.japanTitle = $$v
      },
      expression: "japanTitle"
    }
  })], 1)]), _vm._v(" "), _c('tr', {
    staticClass: "tr-goods"
  }, [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("中文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "选填",
      "disabled": _vm.cnLanguage
    },
    model: {
      value: (_vm.chineseSubtitle),
      callback: function($$v) {
        _vm.chineseSubtitle = $$v
      },
      expression: "chineseSubtitle"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("英文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "选填",
      "disabled": _vm.enLanguage
    },
    model: {
      value: (_vm.englishSubtitle),
      callback: function($$v) {
        _vm.englishSubtitle = $$v
      },
      expression: "englishSubtitle"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("韩文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "选填",
      "disabled": _vm.krLanguage
    },
    model: {
      value: (_vm.koreaSubtitle),
      callback: function($$v) {
        _vm.koreaSubtitle = $$v
      },
      expression: "koreaSubtitle"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("日文副标题")]), _vm._v(" "), _c('td', [_c('el-input', {
    attrs: {
      "placeholder": "选填",
      "disabled": _vm.jaLanguage
    },
    model: {
      value: (_vm.japanSubtitle),
      callback: function($$v) {
        _vm.japanSubtitle = $$v
      },
      expression: "japanSubtitle"
    }
  })], 1)]), _vm._v(" "), _c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("币种")]), _vm._v(" "), _c('td', [_c('el-select', {
    attrs: {
      "placeholder": "请选择",
      "id": "currency_choice",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selectCurrency($event, _vm.currencyValue)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selectCurrency($event, _vm.currencyValue)
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
        "value": item.CD,
        "data-code": item.CD
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("产地")]), _vm._v(" "), _c('td', [_c('el-select', {
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selectOriginPlace($event, _vm.originPlaceValue)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selectOriginPlace($event, _vm.originPlaceValue)
      }
    },
    model: {
      value: (_vm.originPlaceValue),
      callback: function($$v) {
        _vm.originPlaceValue = $$v
      },
      expression: "originPlaceValue"
    }
  }, _vm._l((_vm.originPlace), function(item) {
    return _c('el-option', {
      key: item.zh_name,
      attrs: {
        "label": item.zh_name,
        "value": item.id,
        "data-code": item.id
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("前台标签")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "N000370200"
    },
    model: {
      value: (_vm.FETag),
      callback: function($$v) {
        _vm.FETag = $$v
      },
      expression: "FETag"
    }
  }, [_vm._v("Hot")]), _vm._v(" "), _c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "N000370100"
    },
    model: {
      value: (_vm.FETag),
      callback: function($$v) {
        _vm.FETag = $$v
      },
      expression: "FETag"
    }
  }, [_vm._v("New")]), _vm._v(" "), _c('el-radio', {
    staticClass: "radio",
    attrs: {
      "label": "0"
    },
    model: {
      value: (_vm.FETag),
      callback: function($$v) {
        _vm.FETag = $$v
      },
      expression: "FETag"
    }
  }, [_vm._v("None")])], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }), _vm._v(" "), _c('td')])])]), _vm._v(" "), _c('header', [_vm._v("税率信息")]), _vm._v(" "), _c('table', {
    staticClass: "erp-tax-info",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("退税")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-radio-group', {
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
  }, [_vm._v("返税比率")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-select', {
    staticClass: "drawback-per",
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice",
      "disabled": _vm.drawbackAccess
    },
    on: {
      "change": function($event) {
        _vm.selectDrawback($event, _vm.drawbackPercent)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selectDrawback($event, _vm.drawbackPercent)
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
      key: value.CD,
      attrs: {
        "label": value.CD_VAL + '%',
        "value": value.CD,
        "data-code": value.CD
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("跨境电商综合税 保税仓专用")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-select', {
    staticClass: "bonded-warehouse",
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice"
    },
    on: {
      "change": function($event) {
        _vm.selectbondedWarehouse($event, _vm.bondedWarehouse)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selectbondedWarehouse($event, _vm.bondedWarehouse)
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
        "value": value.CD,
        "data-code": value.CD
      }
    })
  }))], 1)])]), _vm._v(" "), _c('header', [_vm._v("运输信息")]), _vm._v(" "), _c('table', {
    staticClass: "erp-tax-info",
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("装箱数")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-input', {
    staticClass: "pack-num",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.packingNum),
      callback: function($$v) {
        _vm.packingNum = $$v
      },
      expression: "packingNum"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("最小起订数")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-input', {
    staticClass: "min-num",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.minNum),
      callback: function($$v) {
        _vm.minNum = $$v
      },
      expression: "minNum"
    }
  })], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("最大起订量")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-input', {
    staticClass: "max-num",
    attrs: {
      "placeholder": ""
    },
    on: {
      "blur": _vm.checkInputEmpty
    },
    model: {
      value: (_vm.maxNum),
      callback: function($$v) {
        _vm.maxNum = $$v
      },
      expression: "maxNum"
    }
  })], 1)]), _vm._v(" "), _c('tr', [_c('td', {
    staticClass: "info-title"
  }, [_vm._v("物流类目")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-select', {
    staticClass: "logistic-cat",
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice"
    },
    on: {
      "change": function($event) {
        _vm.selectLogisticsCategory($event, _vm.logisticsCategory)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selectLogisticsCategory($event, _vm.logisticsCategory)
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
        "value": key,
        "data-code": key
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
    staticClass: "info-title"
  }, [_vm._v("物流类型")]), _vm._v(" "), _c('td', {
    staticStyle: {
      "text-align": "left"
    }
  }, [_c('el-select', {
    staticClass: "logistic-type",
    attrs: {
      "placeholder": "请选择",
      "id": "origin_choice"
    },
    on: {
      "change": function($event) {
        _vm.selectLogisticsTypes($event, _vm.logisticsTypes)
      }
    },
    nativeOn: {
      "key": function($event) {
        if (!('button' in $event) && _vm._k($event.keyCode, "enter", 13)) { return null; }
        _vm.selectLogisticsTypes($event, _vm.logisticsTypes)
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
        "value": key,
        "data-code": key
      }
    })
  }))], 1), _vm._v(" "), _c('td', {
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
    staticClass: "img-content"
  }, [_c('i', {
    staticClass: "cn-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.cnPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.cnLanguage),
      expression: "!cnLanguage"
    }],
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
    staticClass: "img-content"
  }, [_c('i', {
    staticClass: "en-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.enPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.enLanguage),
      expression: "!enLanguage"
    }],
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
    staticClass: "img-content"
  }, [_c('i', {
    staticClass: "kr-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.krPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.krLanguage),
      expression: "!krLanguage"
    }],
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
      expression: "loading04"
    }],
    staticClass: "img-content"
  }, [_c('i', {
    staticClass: "ja-icon"
  }), _c('img', {
    attrs: {
      "src": _vm.jaPic,
      "alt": ""
    }
  })]), _vm._v(" "), _c('span'), _vm._v(" "), _c('form', {
    staticClass: "updatePicForm",
    attrs: {
      "action": ""
    }
  }, [_c('a', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (!_vm.jaLanguage),
      expression: "!jaLanguage"
    }],
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
  })])])])])])]), _vm._v(" "), _c('header', [_vm._v("商品描述")]), _vm._v(" "), _c('div', {
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
    }, [_vm._v(_vm._s(value['name']))])]), _vm._v(" "), _c('el-input', {
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
  })], 2), _vm._v(" "), _c('header', [_vm._v("详细信息")]), _vm._v(" "), _c('div', {
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
    ref: "cnBtn",
    staticClass: "Chinese",
    attrs: {
      "disabled": _vm.cnLanguage,
      "options": _vm.editorOption01
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlur($event)
      },
      "focus": function($event) {
        _vm.onEditorFocus($event)
      },
      "ready": function($event) {
        _vm.onEditorReady($event)
      }
    },
    model: {
      value: (_vm.cnEditor),
      callback: function($$v) {
        _vm.cnEditor = $$v
      },
      expression: "cnEditor"
    }
  }, [_c('div', {
    attrs: {
      "id": "toolbar01"
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
    staticClass: "el-icon-picture cnBtn",
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
      "change": _vm.upload,
      "click": _vm.clearPic
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
    ref: "enBtn",
    staticClass: "English",
    attrs: {
      "disabled": _vm.enLanguage,
      "options": _vm.editorOption02
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlur($event)
      },
      "focus": function($event) {
        _vm.onEditorFocus($event)
      },
      "ready": function($event) {
        _vm.onEditorReady($event)
      }
    },
    model: {
      value: (_vm.enEditor),
      callback: function($$v) {
        _vm.enEditor = $$v
      },
      expression: "enEditor"
    }
  }, [_c('div', {
    attrs: {
      "id": "toolbar02"
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
    staticClass: "el-icon-picture enBtn",
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
      "change": _vm.upload,
      "click": _vm.clearPic
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
    ref: "krBtn",
    staticClass: "Korean",
    attrs: {
      "disabled": _vm.krLanguage,
      "options": _vm.editorOption03
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlur($event)
      },
      "focus": function($event) {
        _vm.onEditorFocus($event)
      },
      "ready": function($event) {
        _vm.onEditorReady($event)
      }
    },
    model: {
      value: (_vm.krEditor),
      callback: function($$v) {
        _vm.krEditor = $$v
      },
      expression: "krEditor"
    }
  }, [_c('div', {
    attrs: {
      "id": "toolbar03"
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
    staticClass: "el-icon-picture krBtn",
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
      "change": _vm.upload,
      "click": _vm.clearPic
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
    ref: "jaBtn",
    staticClass: "Japanese",
    attrs: {
      "disabled": _vm.jaLanguage,
      "options": _vm.editorOption04
    },
    on: {
      "blur": function($event) {
        _vm.onEditorBlur($event)
      },
      "focus": function($event) {
        _vm.onEditorFocus($event)
      },
      "ready": function($event) {
        _vm.onEditorReady($event)
      }
    },
    model: {
      value: (_vm.jaEditor),
      callback: function($$v) {
        _vm.jaEditor = $$v
      },
      expression: "jaEditor"
    }
  }, [_c('div', {
    attrs: {
      "id": "toolbar04"
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
    staticClass: "el-icon-picture jaBtn",
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
      "change": _vm.upload,
      "click": _vm.clearPic
    }
  }), _vm._v(" "), _c('button', {
    staticClass: "ql-video"
  })])])], 1)], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "erp-addbasic-btns"
  }, [_c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.createGoodBasic
    }
  }, [_vm._v("保存")])], 1)]), _vm._v(" "), _c('el-tab-pane', {
    attrs: {
      "label": "SKU信息",
      "name": "second"
    }
  }, [_c('header', [_vm._v("添加SKU\n          "), _c('div', {
    staticClass: "btns-content"
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
    staticClass: "apply_btn",
    attrs: {
      "type": "button"
    },
    on: {
      "click": _vm.applyToBuild
    }
  }, [_vm._v("自动生成SKU")]), _vm._v(" "), _c('el-button', {
    staticClass: "apply_btn single_apply_btn",
    attrs: {
      "type": "button"
    },
    on: {
      "click": _vm.singleToBuild
    }
  }, [_vm._v("手动添加SKU")])], 1)]), _vm._v(" "), _c('table', {
    staticClass: "erp-add-sku",
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
  }, [_vm._v("Option Value")])])]), _vm._v(" "), _c('tbody', _vm._l((_vm.addSkuOptionLine), function(item, index) {
    return _c('tr', {
      staticClass: "added-sku-option"
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
    })])], 1)], 1)])
  }))]), _vm._v(" "), _c('header', [_vm._v("SKU信息")]), _vm._v(" "), _c('table', {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: (_vm.showSKUinfo),
      expression: "showSKUinfo"
    }],
    staticClass: "erp-sku-info erp-sku-FE",
    staticStyle: {
      "table-layout": "fixed",
      "word-break": "break-all"
    },
    attrs: {
      "border": "0",
      "cellspacing": "0",
      "cellpadding": "0"
    }
  }, [_c('thead', [_c('tr', [_vm._l((_vm.addSkuOptionLine), function(item, index) {
    return _c('th', {
      staticClass: "option-name-lists",
      staticStyle: {
        "text-align": "center"
      },
      attrs: {
        "data-index": index,
        "data-namecode": item.nameCode
      }
    }, [_vm._v(" " + _vm._s(item.optionName) + " ")])
  }), _vm._v(" "), _c('th', [_vm._v("UPC")]), _vm._v(" "), _c('th', [_vm._v("CR code")]), _vm._v(" "), _c('th', [_vm._v("HS code")]), _vm._v(" "), _c('th', {
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
      "width": "120px"
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
      "title": "海关申报信息"
    }
  }, [_vm._v("海关申报信息")])]), _vm._v(" "), _c('el-col', {
    staticClass: "set-btn",
    attrs: {
      "span": 6
    }
  }, [_c('span', {
    on: {
      "click": _vm.setCustomsInfo
    }
  }, [_vm._v("设置")])]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })], 1)], 1)], 2)]), _vm._v(" "), _c('tbody', {
    attrs: {
      "id": "option-name-content"
    }
  }, _vm._l((_vm.optionValueObj), function(item, index) {
    return _c('tr', {
      attrs: {
        "data-row": index
      }
    }, [_vm._l((_vm.addSkuOptionLine), function(it) {
      return _c('td', {
        staticClass: "option-value-lists"
      })
    }), _vm._v(" "), _c('td', [_c('span', {
      staticClass: "upc-value",
      attrs: {
        "title": _vm.UPCMain[index]
      },
      domProps: {
        "innerHTML": _vm._s(_vm.UPCMain[index] ? _vm.UPCMain[index].replace(/\,/g, '<br>') : '')
      }
    }, [_vm._v(_vm._s(_vm.UPCMain[index]))]), _vm._v(" "), _c('el-button', {
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
    })], 1), _vm._v(" "), _c('td', [_c('el-input', {
      staticClass: "cr-code-value",
      attrs: {
        "placeholder": "选填"
      }
    })], 1), _vm._v(" "), _c('td', [_c('el-input', {
      staticClass: "hs-code-value",
      attrs: {
        "placeholder": "选填"
      }
    })], 1), _vm._v(" "), _c('td', {
      staticClass: "input-select unit-content"
    }, [_c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("长(cm)")]), _vm._v(" "), _c('el-input', {
      staticClass: "length-value",
      attrs: {
        "placeholder": "选填"
      }
    })], 1), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("宽(cm)")]), _vm._v(" "), _c('el-input', {
      staticClass: "width-value",
      attrs: {
        "placeholder": "选填"
      }
    })], 1), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("高(cm)")]), _vm._v(" "), _c('el-input', {
      staticClass: "height-value",
      attrs: {
        "placeholder": "选填"
      }
    })], 1), _vm._v(" "), _c('div', {
      staticClass: "item-content"
    }, [_c('span', {
      staticClass: "item-unit"
    }, [_vm._v("重量(g)")]), _vm._v(" "), _c('el-input', {
      staticClass: "weight-value",
      attrs: {
        "placeholder": "必填"
      },
      on: {
        "blur": _vm.checkInputEmpty
      }
    })], 1)]), _vm._v(" "), _c('td', [_c('el-select', {
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
        value: (_vm.saleStatu.sale[index].value),
        callback: function($$v) {
          _vm.saleStatu.sale[index].value = $$v
        },
        expression: "saleStatu.sale[index].value"
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
    }))], 1), _vm._v(" "), _c('td', {
      staticStyle: {
        "padding": "0px"
      }
    }, [_c('el-button', {
      staticClass: "edit-custom-btn",
      attrs: {
        "type": "primary"
      },
      on: {
        "click": function($event) {
          _vm.editCustomInfo($event, index)
        }
      },
      slot: "reference"
    }, [_vm._v("编辑")]), _vm._v(" "), _c('div', {
      staticClass: "custom-content",
      staticStyle: {
        "visibility": "hidden",
        "width": "0px",
        "height": "0px"
      }
    }, [_vm._l((_vm.skuFeatureContent[index]), function(item) {
      return _c('span')
    }), _vm._v(" "), _c('span', {
      staticClass: "upc-content"
    }), _vm._v(" "), _c('span', {
      staticClass: "declarationValue"
    })], 2)], 1)], 2)
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
    staticClass: "item-content"
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
    staticClass: "item-content"
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
    staticClass: "item-content"
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
    staticClass: "item-content"
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
    staticClass: "custom-set",
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
  }, [_vm._v("申报价值(美元)")]), _vm._v(" $\n              "), _c('el-input', {
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
    staticClass: "item-content"
  }, [_c('span', {
    staticClass: "item-title",
    staticStyle: {
      "width": "170px"
    }
  }, [_vm._v("申报价值(美元)")]), _vm._v(" $\n              "), _c('el-input', {
    staticClass: "declaration-value",
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
  }, [_vm._v("确 定")])], 1)]), _vm._v(" "), _c('div', {
    staticClass: "erp-addsku-btns"
  }, [_c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.createGood
    }
  }, [_vm._v("保存")])], 1)], 1), _vm._v(" "), _c('el-tab-pane', {
    staticClass: "erp-sku-BE erp-sku-price-info",
    attrs: {
      "label": "价格信息",
      "name": "third"
    }
  }, [_c('div', {
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
  }, [_c('thead', [_c('tr', [_c('th', [_vm._v("Main ID")]), _vm._v(" "), _c('th', [_vm._v("SKU ID")]), _vm._v(" "), _c('th', [_vm._v("SKU 属性名")]), _vm._v(" "), _c('th', [_vm._v("SKU 属性值")]), _vm._v(" "), _c('th', {
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
    on: {
      "click": _vm.setPriceInfo
    }
  }, [_vm._v("设置")])]), _vm._v(" "), _c('div', {
    staticClass: "batch-operation"
  })], 1)], 1), _vm._v(" "), _c('th', [_vm._v("仓库")])])]), _vm._v(" "), _c('tbody', {
    staticClass: "price-tbody-content"
  }, _vm._l((_vm.optionPrice), function(item, key, index) {
    return _c('tr', [_c('td', [_c('span', {
      staticClass: "main-guds-id"
    }, [_vm._v(_vm._s(_vm.mainGudsId))])]), _vm._v(" "), _c('td', [_c('span', {
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
        staticClass: "warehouse-set price-info hasBorderTop"
      }, [_c('div', {
        staticClass: "item-content market-price-content"
      }, [_c('span', {
        staticClass: "item-price"
      }, [_vm._v("市场价")]), _vm._v(" "), _c('el-input', {
        staticClass: "market-price",
        attrs: {
          "placeholder": "",
          "value": value.marketPrice,
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
      staticClass: "item-content warehouse-content"
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
        "click": _vm.addWareHouse
      }
    }, [_vm._v("添加\n                    ")])], 1), _vm._v(" "), _vm._l((_vm.warehousePriceLink[key]), function(value, k) {
      return _c('div', {
        staticClass: "item-content warehouse-content",
        class: {
          'isNotEditClass': false
        }
      }, [_c('span', {
        staticClass: "warehouse-id",
        attrs: {
          "data-code": value.warehouse,
          "data-id": value.id
        }
      }, [_vm._v(_vm._s(value.name))]), _vm._v(" "), _c('el-button', {
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
      }, [_vm._v("删除\n                    ")])], 1)
    })], 2)])
  }))]), _vm._v(" "), _c('div', {
    staticClass: "erp-addPrice-btns"
  }, [_c('el-button', {
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.addPriceInfo
    }
  }, [_vm._v("保存")])], 1), _vm._v(" "), _c('el-dialog', {
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
  }, [_vm._v("重置")])], 1)], 1)], 1)])
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

},[134]);