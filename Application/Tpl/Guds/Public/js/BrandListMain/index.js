webpackJsonp([3],{

/***/ 107:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(218)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(149),
  /* template */
  __webpack_require__(227),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 138:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app__ = __webpack_require__(107);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__app___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__app__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_jquery__ = __webpack_require__(0);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2_jquery___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_2_jquery__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__assets_reset_css__ = __webpack_require__(4);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__assets_reset_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_3__assets_reset_css__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_element_ui__ = __webpack_require__(5);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4_element_ui___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_4_element_ui__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_element_ui_lib_theme_default_index_css__ = __webpack_require__(3);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5_element_ui_lib_theme_default_index_css___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_5_element_ui_lib_theme_default_index_css__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6__router_brand_js__ = __webpack_require__(49);








__WEBPACK_IMPORTED_MODULE_0_vue__["default"].use(__WEBPACK_IMPORTED_MODULE_4_element_ui___default.a);

new __WEBPACK_IMPORTED_MODULE_0_vue__["default"]({
  router: __WEBPACK_IMPORTED_MODULE_6__router_brand_js__["a" /* default */],
  components: { App: __WEBPACK_IMPORTED_MODULE_1__app___default.a }
}).$mount('app');

/***/ }),

/***/ 149:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });


/* harmony default export */ __webpack_exports__["default"] = ({});

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

/***/ 218:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 227:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('router-view')
},staticRenderFns: []}

/***/ }),

/***/ 3:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 35:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(43)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(36),
  /* template */
  __webpack_require__(45),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 36:
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

      mobileSrc: "",
      pcSrc: "",
      detailSrc: "",
      logoSrc: "",

      brandDetailInfo: '',

      brandStatusValue: "",
      brandStatusList: [],
      brandStatusCode: "",
      brandStatusInfo: "",

      ischange: false
    };
  },
  beforeRouteEnter: function beforeRouteEnter(to, from, next) {
    next(function (vm) {
      if (to.params.brandId) {
        vm.brandID = vm.$route.params.brandId;
      }

      vm.getAddBrandInfo();
      vm.getBrandDetailInfo();
    });
  },
  beforeRouteLeave: function beforeRouteLeave(to, from, next) {
    next();
  },
  created: function created() {},

  methods: {
    getBrandDetailInfo: function getBrandDetailInfo() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].showBrandDetail(vm.brandID), function (json, textStatus) {
        if (json.code == 2000) {
          var info = json.data.basicData;
          var tel = info.sllrData.bzopTelNo.split("-");
          var num = info.sllrData.cRegNo.split("-");
          vm.companyName = info.sllrData.companyName;
          vm.companyBusiness = info.sllrData.itNm;
          vm.regNumber = info.sllrData.commRtlNo;
          vm.businessType = info.sllrData.bztNm;
          vm.tel01 = tel[0];
          vm.tel02 = tel[1];
          vm.tel03 = tel[2];
          vm.organizationCode01 = num[0];
          vm.organizationCode02 = num[1];
          vm.artificialPerson = info.sllrData.bzNm;
          vm.address = info.SllrAddrData.sllrAddr;
          vm.detailAddress = info.SllrAddrData.sllrDtlAddr;
          vm.cnBrandName = info.brandData.BRND_STR_NM;
          vm.krBrandName = info.brandData.BRND_STR_KR_NM;
          vm.jpBrandName = info.brandData.BRND_STR_JPA_NM;
          vm.enBrandName = info.brandData.BRND_STR_ENG_NM;
          vm.accreditStatusId = info.brandData.VEST_WAY;
          vm.accreditStatusValue = vm.accreditStatusList[vm.accreditStatusId];
          vm.brandDetailInfo = info.brandData.BRND_INTD_CONT;
          vm.brandImgData = info.brandImgData;
          vm.brandCountryId = info.brandData.BRND_ORGP_CD;
          vm.brandCountryValue = vm.brandCountryList[vm.brandCountryId];
          vm.brandID = info.SllrAddrData.sllrId;
          if (vm.brandImgData != null) {
            if (vm.brandImgData["N000350300"]) {
              vm.logoSrc = vm.brandImgData["N000350300"].orgName;
            }
            if (vm.brandImgData["N000350200"]) {
              vm.mobileSrc = vm.brandImgData["N000350200"].orgName;
            }
            if (vm.brandImgData["N000350600"]) {
              vm.pcSrc = vm.brandImgData["N000350600"].orgName;
            }
            if (vm.brandImgData["N000350400"]) {
              vm.detailSrc = vm.brandImgData["N000350400"].orgName;
            }
          }
          if (info.brandStrRepCateData) {
            info.brandStrRepCateData.forEach(function (element, index) {
              setTimeout(function () {
                vm.cateListContent.push(vm.cateDataList[element.cateId]["catNamePath"]);
                vm.cateListCodeContent.push(vm.cateDataList[element.cateId]["catId"]);
              }, 50);
            });
          }
          vm.brandStatusCode = info.brandData.BRND_STR_STAT_CD;
          vm.brandStatusValue = vm.brandStatusList[vm.brandStatusCode];
          vm.brandStatusInfo = info.brandData.ENST_APRV_DNY_RSN_CONT;
          if (info.brandData.SALE_CHANNEL) {
            var arr = info.brandData.SALE_CHANNEL.split(",");
            vm.checkedChannelCode = arr;
            vm.checkedChannelCode.forEach(function (element, index) {
              vm.checkedChannel.push(vm.distributionChannel[element]);
            });
          }
        }
      });
    },
    getAddBrandInfo: function getAddBrandInfo() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getAddBrandInfo(), function (json, textStatus) {
        if (json.code == 2000) {
          console.log(json);
          vm.brandCountryList = json.data.brandCountryData;
          vm.distributionChannel = json.data.saleChannelData;
          vm.accreditStatusList = json.data.authTypeData;
          vm.cateDataList = json.data.cateData;
          vm.brandStatusList = json.data.brandStatusData;
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
            message: '不能使用的品牌'
          });
        }
      });
    },
    checkCompanyRepeat: function checkCompanyRepeat() {
      var vm = this;
      if (this.ischange) {
        $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].isCompanyNameExit(vm.companyName), function (json, textStatus) {
          if (json.code == 2000) {
            vm.$message({
              type: 'success',
              message: '可以使用'
            });
          } else {
            vm.$message({
              type: 'error',
              message: '不能使用的公司名字'
            });
          }
        });
      } else {
        vm.$message({
          type: 'success',
          message: '公司名字可用'
        });
      }
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
          vm.$message({
            type: 'success',
            message: '图片上传成功'
          });
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
      if (event.type == "click") {
        this.accreditStatusId = event.currentTarget.getAttribute("data-code");
      }
    },
    selectBrandCountry: function selectBrandCountry() {
      if (event.type == "click") {
        this.brandCountryId = event.currentTarget.getAttribute("data-code");
      }
    },
    selcetCateData: function selcetCateData() {
      if (event.type == "click") {
        this.cateDataId = event.currentTarget.getAttribute("data-code");
      }
    },
    selcetBrandStatus: function selcetBrandStatus() {
      if (event.type == "click") {
        this.brandStatusId = event.currentTarget.getAttribute("data-code");
      }
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
      console.log(this.cateListCodeContent);
      console.log(this.cateListContent);
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
      console.log(this.cateListCodeContent);
      console.log(this.cateListContent);
    },
    ischanged: function ischanged() {
      this.ischange = true;
    },
    changeBrand: function changeBrand() {
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
        brandStatus: vm.brandStatusId,
        remark: vm.brandStatusInfo,
        ischange: vm.ischange,
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
          url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].modifyData(),
          type: 'POST',
          dataType: 'json',
          data: __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default()(postData)
        }).done(function (data) {
          if (data.code == 2000) {
            vm.$alert("保存成功", {
              confirmButtonText: '确定',
              callback: function callback(action) {
                vm.$router.push({
                  name: 'brandlist'
                });
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
            message: '接口出错了'
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

/***/ 4:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 43:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 45:
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
      "span": 10
    }
  }, [_c('span', [_vm._v(_vm._s(_vm.brandID))])]), _vm._v(" "), _c('el-col', {
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
    on: {
      "change": _vm.ischanged
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
  }, [_vm._v(_vm._s(_vm.mobileSrc))])])]), _vm._v(" "), _c('el-col', {
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
  }, [_vm._v(_vm._s(_vm.pcSrc))])])])], 1), _vm._v(" "), _c('el-row', {
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
  }, [_vm._v(_vm._s(_vm.logoSrc))])])]), _vm._v(" "), _c('el-col', {
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
  }, [_vm._v(_vm._s(_vm.detailSrc))])])])], 1), _vm._v(" "), _c('el-row', {
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
      "placeholder": ""
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
  })], 2)], 1), _vm._v(" "), _c('header', [_vm._v("品牌状态")]), _vm._v(" "), _c('div', {
    staticClass: "brandStatus"
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
    staticClass: "first-title"
  }, [_vm._v("品牌状态")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 4
    }
  }, [_c('el-select', {
    attrs: {
      "placeholder": ""
    },
    on: {
      "change": function($event) {
        _vm.selcetBrandStatus($event)
      }
    },
    model: {
      value: (_vm.brandStatusValue),
      callback: function($$v) {
        _vm.brandStatusValue = $$v
      },
      expression: "brandStatusValue"
    }
  }, _vm._l((_vm.brandStatusList), function(item, key) {
    return _c('el-option', {
      key: key,
      attrs: {
        "label": item,
        "value": item,
        "data-code": key
      }
    })
  }))], 1)], 1), _vm._v(" "), _c('el-row', {
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
  }, [_vm._v("备注")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 22
    }
  }, [_c('el-input', {
    staticClass: "brand-status-content",
    staticStyle: {
      "height": "160px"
    },
    attrs: {
      "type": "textarea",
      "placeholder": "请输入内容",
      "resize": "none"
    },
    model: {
      value: (_vm.brandStatusInfo),
      callback: function($$v) {
        _vm.brandStatusInfo = $$v
      },
      expression: "brandStatusInfo"
    }
  })], 1)], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "erp-addbrand-btns"
  }, [_c('el-button', {
    staticClass: "addbrand-save-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.changeBrand
    }
  }, [_vm._v("保存")])], 1)])
},staticRenderFns: []}

/***/ }),

/***/ 47:
/***/ (function(module, exports, __webpack_require__) {


/* styles */
__webpack_require__(80)

var Component = __webpack_require__(6)(
  /* script */
  __webpack_require__(59),
  /* template */
  __webpack_require__(82),
  /* scopeId */
  null,
  /* cssModules */
  null
)

module.exports = Component.exports


/***/ }),

/***/ 49:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__(1);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1_vue_router__ = __webpack_require__(57);



__WEBPACK_IMPORTED_MODULE_0_vue__["default"].use(__WEBPACK_IMPORTED_MODULE_1_vue_router__["a" /* default */]);

/* harmony default export */ __webpack_exports__["a"] = (new __WEBPACK_IMPORTED_MODULE_1_vue_router__["a" /* default */]({

  routes: [{
    path: '/brandlist',
    name: 'brandlist',
    component: __webpack_require__(47)
  }, {
    path: '/brandlist/brandedit/:brandId',
    name: 'brandEdit',
    component: __webpack_require__(35)
  }, {
    path: '*',
    redirect: '/brandlist'
  }]
}));

/***/ }),

/***/ 59:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* WEBPACK VAR INJECTION */(function($, jQuery) {/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify__ = __webpack_require__(10);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__api_index_js__ = __webpack_require__(2);




/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      brandAccredit: [],
      brandAccreditId: "",
      brandAccreditValue: "",

      brandLists: [],
      brandSearch: [],
      brandSearchId: "",
      brandSearchValue: "",

      brandStatusValue: [],

      brandStatusCode: [],

      brandStatusList: [{
        id: "1",
        checked: false,
        value: "待审核",
        code: "N000040100"
      }, {
        id: "2",
        checked: false,
        value: "审核中",
        code: "N000040200"
      }, {
        id: "3",
        checked: false,
        value: "审核成功",
        code: "N000040300"
      }, {
        id: "4",
        checked: false,
        value: "审核失败",
        code: "N000040400"

      }, {
        id: "5",
        checked: false,
        value: "锁定",
        code: "N000040500"
      }],

      checkedAll: true,
      checkedStatusCode: [],
      brandInfoList: [],

      actionDateValue: "",
      actionDate: [{
        value: '创建时间',
        label: '创建时间',
        code: "cd"
      }, {
        value: '更新时间',
        label: '更新时间',
        code: 'ud'
      }],

      dateDuring: "",
      dateType: '',
      pickerOptions2: {
        shortcuts: [{
          text: '今天',
          onClick: function onClick(picker) {
            var end = new Date();
            var start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24);
            picker.$emit('pick', [start, end]);
          }
        }, {
          text: '最近一周',
          onClick: function onClick(picker) {
            var end = new Date();
            var start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
            picker.$emit('pick', [start, end]);
          }
        }, {
          text: '最近一个月',
          onClick: function onClick(picker) {
            var end = new Date();
            var start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
            picker.$emit('pick', [start, end]);
          }
        }, {
          text: '最近一年',
          onClick: function onClick(picker) {
            var end = new Date();
            var start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 365);
            picker.$emit('pick', [start, end]);
          }
        }]
      },

      currentPage: 1,

      totalNum: 0,

      pageNum: 10,

      downloadCondition: {
        authType: "",
        brandId: "",
        brandStatus: "",
        datetype: "",
        dateVal: ""
      }
    };
  },
  created: function created() {
    this.getBrands();
    this.getBrandList();
  },

  methods: {
    getBrands: function getBrands() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getBrands(), function (json, textStatus) {
        if (json.code == 2000) {
          vm.brandLists = json.data;
        }
      });
    },
    getBrandList: function getBrandList() {
      var vm = this;
      $.getJSON(__WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getBrandList(), function (json, textStatus) {
        if (json.code == 2000) {
          vm.brandInfoList = json.data;
          vm.brandAccredit = json.data.authType;
          vm.brandSearch = json.data.list;
          vm.totalNum = +json.data.totalNum;
        }
      });
    },
    selectAccredit: function selectAccredit() {
      this.brandAccreditId = event.currentTarget.getAttribute("data-id");
    },
    selectBrand: function selectBrand() {
      this.brandSearchId = event.currentTarget.getAttribute("data-id");
    },
    handleCheck: function handleCheck() {
      var vm = this;
      vm.checkedStatusCode = [];
      console.log(vm.checkedAll);
      if (vm.checkedAll) {
        vm.checkedAll = !vm.checkedAll;
        vm.brandStatusValue = [];
        vm.brandStatusValue.push(event.srcElement.defaultValue);
      } else if (vm.brandStatusValue.length == 0) {
        vm.checkedAll = !vm.checkedAll;
        vm.brandStatusValue = ["全部"];
        vm.checkedStatusCode = [];
      }
      setTimeout(function () {
        $('.brandStatusGroup input').each(function (index, el) {
          if (el.checked) {
            vm.checkedStatusCode.push(el.parentNode.parentNode.getAttribute("data-code"));
          }
        });
      }, 50);
      console.log(vm.checkedStatusCode);
    },
    handleCheckAll: function handleCheckAll() {
      this.checkedAll = !this.checkedAll;
      console.log(this.checkedAll);
      if (this.checkedAll) {
        this.brandStatusValue = ["全部"];
        this.brandStatusCode = [];
        this.checkedStatusCode = [];
      } else if (this.brandStatusValue.length == 0) {
        this.checkedAll = !this.checkedAll;
        this.brandStatusValue = ["全部"];
        this.brandStatusCode = [];
        this.checkedStatusCode = [];
      }
      console.log(this.brandStatusCode);
    },
    getDateType: function getDateType() {
      this.dateType = event.currentTarget.getAttribute("data-code");
    },
    queryBrands: function queryBrands() {
      var vm = this;
      var postData = {
        authType: vm.brandAccreditId,
        brandId: vm.brandSearchId,
        brandStatus: vm.checkedStatusCode[0] == null ? [] : vm.checkedStatusCode,
        datetype: vm.dateType,
        dateVal: $('.el-date-editor--daterange input').val()
      };
      console.log(postData);
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getBrandList(),
        type: 'POST',
        dataType: 'json',
        data: __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default()(postData)
      }).done(function (json) {
        if (json.code == 2000) {
          vm.brandInfoList = json.data;
          vm.brandAccredit = json.data.authType;
          vm.totalNum = +json.data.totalNum;
          vm.downloadCondition = postData;
          vm.currentPage = 1;
        } else {
          vm.$message({
            type: 'error',
            message: json.msg
          });
        }
      }).fail(function () {
        vm.$message({
          type: 'error',
          message: '接口错误'
        });
      }).always(function () {
        console.log("complete");
      });
    },
    resetQuery: function resetQuery() {
      this.brandAccreditValue = "";
      this.brandSearchValue = "";
      this.brandAccreditId = "";
      this.brandSearchId = "";
      this.checkedStatusCode = "";
      this.dateType = "";
      this.dateDuring = "";
      this.handleCheckAll();
      this.actionDateValue = "";
    },
    handleSizeChange: function handleSizeChange(val) {
      console.log("\u6BCF\u9875 " + val + " \u6761");
    },
    handleCurrentChange: function handleCurrentChange(val) {
      var vm = this;
      var postData = vm.downloadCondition;
      postData.page = +val;
      $.ajax({
        url: __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].getBrandList(),
        type: 'POST',
        dataType: 'json',
        data: __WEBPACK_IMPORTED_MODULE_0_babel_runtime_core_js_json_stringify___default()(postData)
      }).done(function (json) {
        vm.brandInfoList = json.data;
        vm.brandAccredit = json.data.authType;
      }).fail(function () {
        console.log("error");
      }).always(function () {
        console.log("complete");
      });
    },
    seeDetail: function seeDetail(index, rows) {
      var vm = this;
      var brandId = rows[index].brandId;
      console.log(brandId);
      vm.$router.push({
        name: 'brandEdit',
        params: {
          brandId: encodeURIComponent(brandId)
        }
      });
    },
    downloadData: function downloadData() {
      var vm = this;
      var url = __WEBPACK_IMPORTED_MODULE_1__api_index_js__["a" /* default */].downloadData() + "&" + jQuery.param(vm.downloadCondition);
      window.open(url);
    }
  }
});
/* WEBPACK VAR INJECTION */}.call(__webpack_exports__, __webpack_require__(0), __webpack_require__(0)))

/***/ }),

/***/ 80:
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 82:
/***/ (function(module, exports) {

module.exports={render:function (){var _vm=this;var _h=_vm.$createElement;var _c=_vm._self._c||_h;
  return _c('div', {
    staticClass: "brand-list-content list-common"
  }, [_c('el-row', {
    staticClass: "bl-row01"
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("授权状态")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-select', {
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.selectAccredit($event)
      }
    },
    model: {
      value: (_vm.brandAccreditValue),
      callback: function($$v) {
        _vm.brandAccreditValue = $$v
      },
      expression: "brandAccreditValue"
    }
  }, _vm._l((_vm.brandAccredit), function(item, key) {
    return _c('el-option', {
      key: item,
      attrs: {
        "label": item,
        "value": item,
        "data-id": key
      }
    })
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("品牌名搜索")])]), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('el-select', {
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
      value: (_vm.brandSearchValue),
      callback: function($$v) {
        _vm.brandSearchValue = $$v
      },
      expression: "brandSearchValue"
    }
  }, _vm._l((_vm.brandLists), function(item) {
    return _c('el-option', {
      key: item.brandId,
      attrs: {
        "label": item.brandId,
        "value": item.brandId,
        "data-id": item.brandId
      }
    })
  }))], 1)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "bl-row02"
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("品牌状态")])]), _vm._v(" "), _c('el-checkbox-group', {
    staticClass: "brandStatusGroup",
    model: {
      value: (_vm.brandStatusValue),
      callback: function($$v) {
        _vm.brandStatusValue = $$v
      },
      expression: "brandStatusValue"
    }
  }, [_c('el-checkbox', {
    attrs: {
      "label": "全部",
      "checked": _vm.checkedAll
    },
    on: {
      "change": _vm.handleCheckAll
    }
  }), _vm._v(" "), _vm._l((_vm.brandStatusList), function(item) {
    return _c('el-checkbox', {
      attrs: {
        "value": item.id,
        "label": item.value,
        "checked": item.checked,
        "data-code": item.code
      },
      on: {
        "change": function($event) {
          _vm.handleCheck($event)
        }
      }
    })
  })], 2)], 1), _vm._v(" "), _c('el-row', {
    staticClass: "bl-row03"
  }, [_c('el-col', {
    attrs: {
      "span": 2
    }
  }, [_c('div', {
    staticClass: "first-title"
  }, [_vm._v("操作日期")])]), _vm._v(" "), _c('el-col', {
    staticStyle: {
      "width": "180px"
    },
    attrs: {
      "span": 2
    }
  }, [_c('el-select', {
    staticStyle: {
      "width": "160px",
      "float": "left"
    },
    attrs: {
      "placeholder": "请选择"
    },
    on: {
      "change": function($event) {
        _vm.getDateType($event)
      }
    },
    model: {
      value: (_vm.actionDateValue),
      callback: function($$v) {
        _vm.actionDateValue = $$v
      },
      expression: "actionDateValue"
    }
  }, _vm._l((_vm.actionDate), function(item) {
    return _c('el-option', {
      key: item.value,
      attrs: {
        "data-code": item.code,
        "label": item.label,
        "value": item.value
      }
    })
  }))], 1), _vm._v(" "), _c('el-col', {
    attrs: {
      "span": 6
    }
  }, [_c('div', {
    staticClass: "block"
  }, [_c('el-date-picker', {
    attrs: {
      "type": "daterange",
      "align": "right",
      "placeholder": "选择日期范围",
      "picker-options": _vm.pickerOptions2
    },
    model: {
      value: (_vm.dateDuring),
      callback: function($$v) {
        _vm.dateDuring = $$v
      },
      expression: "dateDuring"
    }
  })], 1)])], 1), _vm._v(" "), _c('div', {
    staticClass: "query-btns"
  }, [_c('el-button', {
    staticClass: "query-btn",
    attrs: {
      "type": "primary"
    },
    on: {
      "click": _vm.queryBrands
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
  }, [_c('h3', [_vm._v("搜索结果:共"), _c('span', [_vm._v(_vm._s(_vm.brandInfoList.totalNum))]), _vm._v("条记录 "), _c('el-button', {
    attrs: {
      "id": "export-btn"
    },
    on: {
      "click": _vm.downloadData
    }
  }, [_vm._v("导出")])], 1), _vm._v(" "), _c('el-table', {
    staticStyle: {
      "width": "100%",
      "min-width": "1000px"
    },
    attrs: {
      "data": _vm.brandInfoList.list,
      "stripe": ""
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
          staticClass: "see-detail",
          attrs: {
            "type": "primary"
          },
          nativeOn: {
            "click": function($event) {
              $event.preventDefault();
              _vm.seeDetail(scope.$index, _vm.brandInfoList.list)
            }
          }
        }, [_vm._v("编辑")])]
      }
    }])
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "brandId",
      "label": "品牌ID"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "brandCnName",
      "label": "品牌名"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "brandStatusName",
      "label": "品牌状态"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "createTime",
      "label": "创建时间"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "updatedTime",
      "label": "更新时间"
    }
  }), _vm._v(" "), _c('el-table-column', {
    attrs: {
      "prop": "authTypeName",
      "label": "授权方式"
    }
  })], 1)], 1), _vm._v(" "), _c('div', {
    staticClass: "pagination-block"
  }, [_c('el-pagination', {
    attrs: {
      "current-page": _vm.currentPage,
      "page-size": _vm.brandInfoList.pageNum,
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
  })], 1)], 1)
},staticRenderFns: []}

/***/ })

},[138]);