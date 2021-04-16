webpackJsonp([12],{

/***/ 103:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(216)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(145),
  /* template */
  __webpack_require__(225),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 133:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(103);
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

/***/ 145:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify__ = __webpack_require__(10);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__api_index_js__ = __webpack_require__(2);




/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      brandID: '',

      companyName: "",

      address: "",

      detailAddress: "",

      artificialPerson: "",

      tel01: '',
      tel02: '',
      tel03: '',

      organizationCode01: "",
      organizationCode02: "",

      businessType: "",

      companyBusiness: "",

      regNumber: "",

      cnBrandName: "",
      enBrandName: "",
      krBrandName: "",
      jpBrandName: "",

      distributionChannel: [],
      checkedChannel: [],
      checkedChannelCode: [],

      accreditStatusValue: "",
      accreditStatusList: [],
      accreditStatusId: "",

      cateDataValue: '',
      cateDataList: [],
      cateDataId: "",

      cateListContent: [],
      cateListCodeContent: [],

      brandCountryValue: "",
      brandCountryList: [],
      brandCountryId: "",

      mobileContent: [],
      pcContent: [],
      logoContent: [],
      detailContent: [],

      brandDetailInfo: ""
    };
  },
  created: function created() {
    this.getAddBrandInfo();
  },

  methods: {
    getAddBrandInfo: function getAddBrandInfo() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getAddBrandInfo(), function (json, textStatus) {
        if (json.code == 2000) {
          vm.brandCountryList = json.data.brandCountryData;
          vm.distributionChannel = json.data.saleChannelData;
          vm.accreditStatusList = json.data.authTypeData;
          vm.cateDataList = json.data.cateData;
        } else {
          vm.$message({
            type: 'error',
            message: json.msg
          });
        }
      });
    },
    checkBrandRepeat: function checkBrandRepeat() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].isSllrIdExist(vm.brandID), function (json, textStatus) {
        if (json.code == 2000) {
          vm.$message({
            type: 'success',
            message: '可以使用'
          });
        } else {
          vm.$message({
            type: 'error',
            message: '已经存在的品牌'
          });
        }
      });
    },
    checkCompanyRepeat: function checkCompanyRepeat() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].isCompanyNameExit(vm.companyName), function (json, textStatus) {
        if (json.code == 2000) {
          vm.$message({
            type: 'success',
            message: '可以使用'
          });
        } else {
          vm.$message({
            type: 'error',
            message: '已经存在的公司名字'
          });
        }
      });
    },
    handleCheckedCitiesChange: function handleCheckedCitiesChange() {
      var vm = this;
      vm.checkedChannelCode = [];
      setTimeout(function () {
        $('.el-checkbox-group input').each(function (index, el) {
          if (el.checked) {
            vm.checkedChannelCode.push(el.parentNode.parentNode.getAttribute("data-code"));
          }
        });
      }, 50);
      console.log(vm.checkedChannelCode);
      console.log(vm.checkedChannel);
    },
    updatePic: function updatePic() {
      var vm = this;
      var type = event.currentTarget.className;
      var content = event.currentTarget.getAttribute("data-content");

      var data = new FormData();

      data.append('file', $(event.currentTarget)[0]["files"][0]);
      console.log($(event.currentTarget)[0]);
      $(event.currentTarget).parent().next("span").text($(event.currentTarget)[0]["files"][0]["name"]);

      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].updatePic(),
        type: 'POST',
        dataType: 'JSON',
        contentType: false,
        processData: false,
        data: data,
        cache: false
      }).success(function (data) {
        if (data.code == 2000) {
          vm[content] = data.data;
        } else {
          vm.$message({
            type: 'error',
            message: data.msg
          });
        }
      }).error(function () {
        vm.$message({
          type: 'error',
          message: '接口出错了'
        });
      }).complete(function () {});
    },
    selectAccredit: function selectAccredit() {
      this.accreditStatusId = event.currentTarget.getAttribute("data-code");
    },
    selectBrandCountry: function selectBrandCountry() {
      this.brandCountryId = event.currentTarget.getAttribute("data-code");
    },
    selcetCateData: function selcetCateData() {
      this.cateDataId = event.currentTarget.getAttribute("data-code");
    },
    addedCateDate: function addedCateDate() {
      if (this.cateListContent.indexOf(this.cateDataValue) == -1 && this.cateDataId) {
        this.cateListContent.push(this.cateDataValue);
      } else {
        this.$alert("无法添加", { confirmButtonText: '确定' });
      }
      if (this.cateListCodeContent.indexOf(this.cateDataId) == -1 && this.cateDataId) {
        this.cateListCodeContent.push(this.cateDataId);
      }
    },
    delCatItem: function delCatItem() {
      var target = event.currentTarget.parentNode.parentNode.parentNode;
      var del = event.currentTarget.parentNode.parentNode;
      var text = event.currentTarget.parentNode.innerText;
      for (var key in this.cateDataList) {
        if (this.cateDataList[key]["catNamePath"] == text) {
          var codeindex = this.cateListCodeContent.indexOf(key);
          var valueindex = this.cateListContent.indexOf(text);
          this.cateListCodeContent.splice(codeindex, 1);
          this.cateListContent.splice(valueindex, 1);
        }
      }
    },
    createBrand: function createBrand() {
      var vm = this;
      var postData = {
        companyName: vm.companyName,
        sllrId: vm.brandID,
        sllrAddr: vm.address,
        sllrDtlAddr: vm.detailAddress,
        bzNm: vm.artificialPerson,
        bzopTelNo: vm.tel01 + "-" + vm.tel02 + "-" + vm.tel03,
        cRegNo: vm.organizationCode01 + "-" + vm.organizationCode02,
        bztNm: vm.businessType,
        itNm: vm.companyBusiness,
        commRtlNo: vm.regNumber,
        brandName: vm.cnBrandName,
        brandKrName: vm.krBrandName,
        brandJpName: vm.jpBrandName,
        brandEnName: vm.enBrandName,
        saleChannel: vm.checkedChannelCode,
        brandContent: vm.brandDetailInfo,
        vestWay: vm.accreditStatusId,
        brandOrgCd: vm.brandCountryId,
        catList: vm.cateListCodeContent,
        imgList: [{
          brandImgCd: "N000350200",
          brandImgStatCd: "N000360100",
          imgContent: vm.mobileContent
        }, {
          brandImgCd: "N000350300",
          brandImgStatCd: "N000360100",
          imgContent: vm.logoContent
        }, {
          brandImgCd: "N000350600",
          brandImgStatCd: "N000360100",
          imgContent: vm.pcContent
        }, {
          brandImgCd: "N000350400",
          brandImgStatCd: "N000360100",
          imgContent: vm.detailContent
        }]
      };
      if (!$.trim(vm.companyName)) {
        vm.$message({
          type: 'error',
          message: '请填写公司名字!'
        });
      } else if (!$.trim(vm.brandID)) {
        vm.$message({
          type: 'error',
          message: '请填写品牌ID!'
        });
      } else if (/\s/.test(vm.brandID)) {
        vm.$message({
          type: 'error',
          message: '品牌ID不能带空格!'
        });
      } else if (!$.trim(vm.businessType)) {
        vm.$message({
          type: 'error',
          message: '请填写业务类型!'
        });
      } else if (!$.trim(vm.cnBrandName) || !$.trim(vm.enBrandName) || !$.trim(vm.krBrandName) || !$.trim(vm.jpBrandName)) {
        vm.$message({
          type: 'error',
          message: '请填写品牌名字!'
        });
      } else {
        $.ajax({
          url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].doAddBrand(),
          type: 'POST',
          dataType: 'json',
          data: __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default()(postData)
        }).done(function (data) {
          if (data.code == 2000) {
            vm.$alert("创建成功", {
              confirmButtonText: '确定',
              callback: function callback(action) {
                window.location.reload();
              }
            });
          } else {
            vm.$message({
              type: 'error',
              message: data.msg
            });
          }
        }).fail(function () {
          vm.$message({
            type: 'error',
            message: '接口错误!'
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

/***/ 216:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 225:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "add-brand-content"
  }, [_c('header', [_vm._v("公司信息")]), _vm._v(" "), _c('div', {
    staticClass: "company-info"
  }, [_c('el-row', {
    staticClass: "row-line row01",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("品牌ID")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 7
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.brandID),
      callback: function($$v) {
        _vm.brandID = $$v
      },
      expression: "brandID"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 3
    }
  }, [_c('el-button', {
    staticClass: "repeat-check",
    on: {
      "click": _vm.checkBrandRepeat
    }
  }, [_vm._v("重复查询")])], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("公司名字")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 7
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.companyName),
      callback: function($$v) {
        _vm.companyName = $$v
      },
      expression: "companyName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 3
    }
  }, [_c('el-button', {
    staticClass: "repeat-check",
    on: {
      "click": _vm.checkCompanyRepeat
    }
  }, [_vm._v("重复查询")])], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row02",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("公司地址")])]), _vm._v(" "), _c('el-col', {
    staticClass: "address",
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.address),
      callback: function($$v) {
        _vm.address = $$v
      },
      expression: "address"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-input', {
    staticClass: "detail-address",
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.detailAddress),
      callback: function($$v) {
        _vm.detailAddress = $$v
      },
      expression: "detailAddress"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("法人")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 3
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.artificialPerson),
      callback: function($$v) {
        _vm.artificialPerson = $$v
      },
      expression: "artificialPerson"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("公司电话")])]), _vm._v(" "), _c('el-col', {
    staticClass: "company-tel",
    attrs: {
      "span": 1
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "区号"
    },
    model: {
      value: (_vm.tel01),
      callback: function($$v) {
        _vm.tel01 = $$v
      },
      expression: "tel01"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    staticClass: "company-tel",
    attrs: {
      "span": 3
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "座机号"
    },
    model: {
      value: (_vm.tel02),
      callback: function($$v) {
        _vm.tel02 = $$v
      },
      expression: "tel02"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 1
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": "分机号"
    },
    model: {
      value: (_vm.tel03),
      callback: function($$v) {
        _vm.tel03 = $$v
      },
      expression: "tel03"
    }
  })], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row03",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("企业组织机构代码")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    staticClass: "organization-code",
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.organizationCode01),
      callback: function($$v) {
        _vm.organizationCode01 = $$v
      },
      expression: "organizationCode01"
    }
  }), _vm._v(" "), _c('el-input', {
    staticClass: "organization-code",
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.organizationCode02),
      callback: function($$v) {
        _vm.organizationCode02 = $$v
      },
      expression: "organizationCode02"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("业务类型")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.businessType),
      callback: function($$v) {
        _vm.businessType = $$v
      },
      expression: "businessType"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("公司业务")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 3
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.companyBusiness),
      callback: function($$v) {
        _vm.companyBusiness = $$v
      },
      expression: "companyBusiness"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("商业登记号码")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 5
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.regNumber),
      callback: function($$v) {
        _vm.regNumber = $$v
      },
      expression: "regNumber"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('header', [_vm._v("品牌信息")]), _vm._v(" "), _c('div', {
    staticClass: "brand-info"
  }, [_c('el-row', {
    staticClass: "row-line row01",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("品牌名字(CN)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.cnBrandName),
      callback: function($$v) {
        _vm.cnBrandName = $$v
      },
      expression: "cnBrandName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("品牌名字(EN)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.enBrandName),
      callback: function($$v) {
        _vm.enBrandName = $$v
      },
      expression: "enBrandName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("品牌名字(KR)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.krBrandName),
      callback: function($$v) {
        _vm.krBrandName = $$v
      },
      expression: "krBrandName"
    }
  })], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title necessary"
  }, [_vm._v("品牌名字(JPA)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-input', {
    attrs: {
      "placeholder": ""
    },
    model: {
      value: (_vm.jpBrandName),
      callback: function($$v) {
        _vm.jpBrandName = $$v
      },
      expression: "jpBrandName"
    }
  })], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row02",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title "
  }, [_vm._v("销售渠道")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 22
    }
  }, [_c('el-checkbox-group', {
    on: {
      "change": function($event) {
        _vm.handleCheckedCitiesChange($event)
      }
    },
    model: {
      value: (_vm.checkedChannel),
      callback: function($$v) {
        _vm.checkedChannel = $$v
      },
      expression: "checkedChannel"
    }
  }, _vm._l((_vm.distributionChannel), function(item, key) {
    return _c('el-checkbox', {
      key: key,
      attrs: {
        "label": item,
        "data-code": key
      }
    }, [_vm._v(_vm._s(item))])
  }))], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row03",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title "
  }, [_vm._v("Banner(MOBILE)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('form', {
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
    staticClass: "picMobile",
    attrs: {
      "type": "file",
      "data-content": "mobileContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })]), _vm._v(" "), _c('span', {
    staticClass: "picName"
  })])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title "
  }, [_vm._v("Banner(PC)")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('form', {
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
    staticClass: "picPC",
    attrs: {
      "type": "file",
      "data-content": "pcContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })]), _vm._v(" "), _c('span', {
    staticClass: "picName"
  })])])], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row04",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("LOGO")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('form', {
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
    staticClass: "picLogo",
    attrs: {
      "type": "file",
      "data-content": "logoContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })]), _vm._v(" "), _c('span', {
    staticClass: "picName"
  })])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title "
  }, [_vm._v("详情图")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 10
    }
  }, [_c('form', {
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
    staticClass: "picDetail",
    attrs: {
      "type": "file",
      "data-content": "detailContent"
    },
    on: {
      "change": function($event) {
        _vm.updatePic($event)
      }
    }
  })]), _vm._v(" "), _c('span', {
    staticClass: "picName"
  })])])], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row05",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("品牌信息")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 22
    }
  }, [_c('el-input', {
    staticClass: "brand-info-content",
    staticStyle: {
      "height": "160px"
    },
    attrs: {
      "type": "textarea",
      "placeholder": "请输入内容",
      "resize": "none"
    },
    model: {
      value: (_vm.brandDetailInfo),
      callback: function($$v) {
        _vm.brandDetailInfo = $$v
      },
      expression: "brandDetailInfo"
    }
  })], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row06",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("授权状态")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-select', {
    staticClass: "choose-country",
    attrs: {
      "placeholder": ""
    },
    on: {
      "change": function($event) {
        _vm.selectAccredit($event)
      }
    },
    model: {
      value: (_vm.accreditStatusValue),
      callback: function($$v) {
        _vm.accreditStatusValue = $$v
      },
      expression: "accreditStatusValue"
    }
  }, _vm._l((_vm.accreditStatusList), function(item, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": item,
        "value": item,
        "data-code": key
      }
    })
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("品牌国家")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-select', {
    staticClass: "choose-country",
    attrs: {
      "placeholder": "",
      "filterable": ""
    },
    on: {
      "change": function($event) {
        _vm.selectBrandCountry($event)
      }
    },
    model: {
      value: (_vm.brandCountryValue),
      callback: function($$v) {
        _vm.brandCountryValue = $$v
      },
      expression: "brandCountryValue"
    }
  }, _vm._l((_vm.brandCountryList), function(item, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": item,
        "value": item,
        "data-code": key
      }
    })
  }))], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "row-line row07",
    attrs: {
      "type": "flex"
    }
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("所属前端类目")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-select', {
    staticClass: "choose-country",
    attrs: {
      "placeholder": ""
    },
    on: {
      "change": function($event) {
        _vm.selcetCateData($event)
      }
    },
    model: {
      value: (_vm.cateDataValue),
      callback: function($$v) {
        _vm.cateDataValue = $$v
      },
      expression: "cateDataValue"
    }
  }, _vm._l((_vm.cateDataList), function(item) {
    return _c('el-option', {
      key: item.catId,
      attrs: {
        "label": item.catNamePath,
        "value": item.catNamePath,
        "data-code": item.catId
      }
    })
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('i', {
    staticClass: "el-icon-plus",
    on: {
      "click": _vm.addedCateDate
    }
  })]), _vm._v(" "), _vm._l((_vm.cateListContent), function(item) {
    return _c('el-col', {
      attrs: {
        "span": 2
      }
    }, [_c('span', {
      staticClass: "cate-list-item",
      staticStyle: {
        "text-align": "center",
        "font-size": "14px",
        "border": "1px solid #bfcbd9",
        "padding": "6px 10px"
      }
    }, [_vm._v(_vm._s(item) + " "), _c('i', {
      staticClass: "el-icon-circle-close",
      on: {
        "click": function($event) {
          _vm.delCatItem($event)
        }
      }
    })])])
  })], 2)], 1), _vm._v(" "), _c('div', {
    staticClass: "erp-addbrand-btns"
  }, [_c('el-button', {
    staticClass: "addbrand-save-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.createBrand
    }
  }, [_vm._v("保存")])], 1)])
},staticRenderFns: []}

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 4:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ })

},[133]);