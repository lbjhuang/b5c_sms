webpackJsonp([21],{1:function(a,g,l){"use strict";function n(a){for(var g=[],l=0;l<a.length;l++)a[l]&&g.push(a[l]);return g}function e(a){return a?n(i()(a).map(function(g){return void 0===a[g]?"":encodeURIComponent(g)+"="+encodeURIComponent(a[g])})).join("&"):""}function u(){return(1e7*Math.random()).toString(16).substr(0,4)+"-"+(new Date).getTime()+"-"+Math.random().toString().substr(2,5)}function _(a){var g=JSON.parse(localStorage.getItem("hashJSON")),l=JSON.parse(localStorage.getItem("dataJSON")),n=c.a.get("think_language");a.prototype.$lang=function(a){if(!a||"zh-cn"===n)return a;if(!localStorage.getItem("hashJSON"))return a;var e=g.hasOwnProperty(a)?g[a]:null;return e?l[e][n]?l[e][n]:l[e]["en-us"]?l[e]["en-us"]:a+"*":a+"*"}}l.d(g,"b",function(){return p}),l.d(g,"d",function(){return h}),g.c=e,g.e=u,g.a=_;var r=l(14),i=l.n(r),t=l(7),c=l.n(t),d={"zh-cn":{CD:"N000920100",CD_NM:"Language Type",CD_VAL:"中文",ETC:"Chinese"},"en-us":{CD:"N000920200",CD_NM:"Language Type",CD_VAL:"英文",ETC:"English"},"ja-jp":{CD:"N000920300",CD_NM:"Language Type",CD_VAL:"日文",ETC:"Japanese"},"ko-kr":{CD:"N000920400",CD_NM:"Language Type",CD_VAL:"韩文",ETC:"Korean"},"fr-fr":{CD:"N000920500",CD_NM:"Language Type",CD_VAL:"法文",ETC:"French"}},p=function(){var a=c.a.get("think_language")||"zh-cn";return d[a].CD},h=function(){return c.a.get("PHPSESSID")}},137:function(a,g,l){"use strict";Object.defineProperty(g,"__esModule",{value:!0});var n=l(0),e=l(93),u=l.n(e),_=l(4),r=(l.n(_),l(6)),i=l.n(r),t=l(3),c=(l.n(t),l(2)),d=l(1),p=l(8);l.i(d.a)(n.default),n.default.use(i.a,{size:"medium",i18n:function(a,g){return c.a.t(a,g)}}),n.default.use(p.a);var h=new p.a({routes:[{path:"/",name:"index",hidden:!0,redirect:function(a){return"category"}},{path:"/category",name:"category",component:function(a){return l.e(6).then(function(){var g=[l(225)];a.apply(null,g)}.bind(this)).catch(l.oe)}}]});new n.default({i18n:c.a,router:h,components:{App:u.a}}).$mount("app")},146:function(a,g,l){"use strict";Object.defineProperty(g,"__esModule",{value:!0}),g.default={data:function(){return{}}}},199:function(a,g){},2:function(a,g,l){"use strict";var n=l(15),e=l.n(n),u=l(10),_=l.n(u),r=l(0),i=l(18),t=l(7),c=l.n(t),d=l(16),p=l.n(d),h=l(11),o=l.n(h),k={code:2e3,msg:"success",data:[{language_id:1,language_ch:"品牌列表",language_en:"Brand List",language_jp:null,language_kr:null},{language_id:2,language_ch:"品牌ID",language_en:"Brand ID",language_jp:null,language_kr:null},{language_id:3,language_ch:"品牌名",language_en:"Brand Name",language_jp:null,language_kr:null},{language_id:4,language_ch:"创建日期",language_en:"Create Date",language_jp:null,language_kr:null},{language_id:5,language_ch:"品牌状态",language_en:"Brand Status",language_jp:null,language_kr:null},{language_id:6,language_ch:"全部",language_en:"All",language_jp:null,language_kr:null},{language_id:7,language_ch:"激活",language_en:"Activate",language_jp:null,language_kr:null},{language_id:8,language_ch:"关闭",language_en:"Close",language_jp:null,language_kr:null},{language_id:9,language_ch:"查询",language_en:"Search",language_jp:null,language_kr:null},{language_id:10,language_ch:"创建新品牌",language_en:"Create New Brand",language_jp:null,language_kr:null},{language_id:11,language_ch:"导出",language_en:"Export",language_jp:null,language_kr:null},{language_id:12,language_ch:"搜索记录",language_en:"Search Records",language_jp:null,language_kr:null},{language_id:13,language_ch:"共（总共）",language_en:"All (Total)",language_jp:null,language_kr:null},{language_id:14,language_ch:"记录",language_en:"Record",language_jp:null,language_kr:null},{language_id:15,language_ch:"品牌国家",language_en:"Country Of Brand ",language_jp:null,language_kr:null},{language_id:16,language_ch:"授权状态",language_en:"Authorization Status",language_jp:null,language_kr:null},{language_id:17,language_ch:"授权备注",language_en:"Authorization Note",language_jp:null,language_kr:null},{language_id:18,language_ch:"创建人",language_en:"Creator",language_jp:null,language_kr:null},{language_id:19,language_ch:"修改时间",language_en:"Modify Time",language_jp:null,language_kr:null},{language_id:20,language_ch:"创建时间",language_en:"Create Time",language_jp:null,language_kr:null},{language_id:21,language_ch:"操作",language_en:"Operation",language_jp:null,language_kr:null},{language_id:22,language_ch:"页（页码）",language_en:"Page (Page Number)",language_jp:null,language_kr:null},{language_id:23,language_ch:"前往",language_en:"Move To",language_jp:null,language_kr:null},{language_id:24,language_ch:"公司名称",language_en:"Company Name",language_jp:null,language_kr:null},{language_id:25,language_ch:"法人",language_en:"Legal Person",language_jp:null,language_kr:null},{language_id:26,language_ch:"公司业务",language_en:"Company Business",language_jp:null,language_kr:null},{language_id:27,language_ch:"商业登记号",language_en:"Business Registration Number(TFN)",language_jp:null,language_kr:null},{language_id:28,language_ch:"公司地址",language_en:"Company Address",language_jp:null,language_kr:null},{language_id:29,language_ch:"品牌详情",language_en:"Brand Details",language_jp:null,language_kr:null},{language_id:30,language_ch:"保存",language_en:"Save",language_jp:null,language_kr:null},{language_id:31,language_ch:"返回",language_en:"Return",language_jp:null,language_kr:null},{language_id:32,language_ch:"删除",language_en:"Delete",language_jp:null,language_kr:null},{language_id:33,language_ch:"请选择",language_en:"Please Select",language_jp:null,language_kr:null},{language_id:34,language_ch:"重置",language_en:"Reset",language_jp:null,language_kr:null},{language_id:35,language_ch:"开始日期",language_en:"Start Date",language_jp:null,language_kr:null},{language_id:36,language_ch:"结束日期",language_en:"End Date",language_jp:null,language_kr:null},{language_id:37,language_ch:"至",language_en:"To",language_jp:null,language_kr:null},{language_id:38,language_ch:"品牌公司",language_en:"Company Of Brand ",language_jp:null,language_kr:null},{language_id:39,language_ch:"品牌信息",language_en:"Brand Information",language_jp:null,language_kr:null},{language_id:40,language_ch:"品牌LOGO",language_en:"Brand LOGO",language_jp:null,language_kr:null},{language_id:41,language_ch:"点击上传",language_en:"Click Upload",language_jp:null,language_kr:null},{language_id:42,language_ch:"上传图片",language_en:"Upload Pictures",language_jp:null,language_kr:null},{language_id:43,language_ch:"类目列表",language_en:"Category List",language_jp:null,language_kr:null},{language_id:44,language_ch:"类目等级",language_en:"Category Grade",language_jp:null,language_kr:null},{language_id:45,language_ch:"一级类目",language_en:"Primary Category",language_jp:null,language_kr:null},{language_id:46,language_ch:"二级类目",language_en:"Secondary Category",language_jp:null,language_kr:null},{language_id:47,language_ch:"三级类目",language_en:"Tertiary Category",language_jp:null,language_kr:null},{language_id:48,language_ch:"四级类目",language_en:"Quartus Category",language_jp:null,language_kr:null},{language_id:49,language_ch:"创建类目",language_en:"Create Category",language_jp:null,language_kr:null},{language_id:50,language_ch:"导入",language_en:"Import",language_jp:null,language_kr:null},{language_id:51,language_ch:"类目code",language_en:"Category Code",language_jp:null,language_kr:null},{language_id:52,language_ch:"类目名称",language_en:"Category Name",language_jp:null,language_kr:null},{language_id:53,language_ch:"类目状态",language_en:"Category Status ",language_jp:null,language_kr:null},{language_id:54,language_ch:"选择类目等级",language_en:"Select Category Grade",language_jp:null,language_kr:null},{language_id:55,language_ch:"属性列表",language_en:"Attribute List",language_jp:null,language_kr:null},{language_id:56,language_ch:"属性",language_en:"Attribute",language_jp:null,language_kr:null},{language_id:57,language_ch:"属性值",language_en:"Attribute Value",language_jp:null,language_kr:null},{language_id:58,language_ch:"创建属性",language_en:"Create Attribute",language_jp:null,language_kr:null},{language_id:59,language_ch:"添加属性值",language_en:"Add Attribute Value",language_jp:null,language_kr:null},{language_id:60,language_ch:"导入属性",language_en:"Import Attribute",language_jp:null,language_kr:null},{language_id:61,language_ch:"导入属性值",language_en:"Import Attribute Value",language_jp:null,language_kr:null},{language_id:62,language_ch:"属性ID",language_en:"Attribute ID",language_jp:null,language_kr:null},{language_id:63,language_ch:"属性名称",language_en:"Attribute Name",language_jp:null,language_kr:null},{language_id:64,language_ch:"属性值ID",language_en:" Attribute Value ID",language_jp:null,language_kr:null},{language_id:65,language_ch:"属性值状态",language_en:"Attribute Value Status",language_jp:null,language_kr:null},{language_id:66,language_ch:"新增属性",language_en:"Add New Attribute",language_jp:null,language_kr:null},{language_id:67,language_ch:"下载模板",language_en:"Download Template",language_jp:null,language_kr:null},{language_id:68,language_ch:"商品列表",language_en:"Commodity List ",language_jp:null,language_kr:null},{language_id:69,language_ch:"商品名称",language_en:" Commodity Name",language_jp:null,language_kr:null},{language_id:70,language_ch:"条形码",language_en:"Bar Code",language_jp:null,language_kr:null},{language_id:71,language_ch:"第三方编码",language_en:"Third Party Code",language_jp:null,language_kr:null},{language_id:72,language_ch:"语言",language_en:"Language",language_jp:null,language_kr:null},{language_id:73,language_ch:"审核状态",language_en:"Audit Status",language_jp:null,language_kr:null},{language_id:74,language_ch:"待审核",language_en:"To Be Audited",language_jp:null,language_kr:null},{language_id:75,language_ch:"审核通过",language_en:"Approved",language_jp:null,language_kr:null},{language_id:76,language_ch:"驳回",language_en:"Rejection",language_jp:null,language_kr:null},{language_id:77,language_ch:"商品状态",language_en:"Commodity Status",language_jp:null,language_kr:null},{language_id:78,language_ch:"上架",language_en:"Putaway",language_jp:null,language_kr:null},{language_id:79,language_ch:"下架",language_en:"Drop off",language_jp:null,language_kr:null},{language_id:80,language_ch:"创建商品",language_en:"Create Commodity",language_jp:null,language_kr:null},{language_id:81,language_ch:"审核失败",language_en:"Audit Failure",language_jp:null,language_kr:null},{language_id:82,language_ch:"商品品牌",language_en:"Brand Of Commodity",language_jp:null,language_kr:null},{language_id:83,language_ch:"币种",language_en:"Currency",language_jp:null,language_kr:null},{language_id:84,language_ch:"采购价",language_en:"Purchasing Price",language_jp:null,language_kr:null},{language_id:85,language_ch:"库存",language_en:"Inventory",language_jp:null,language_kr:null},{language_id:86,language_ch:"中文",language_en:"Chinese",language_jp:null,language_kr:null},{language_id:87,language_ch:"英文",language_en:"English",language_jp:null,language_kr:null},{language_id:88,language_ch:"韩文",language_en:"Korean",language_jp:null,language_kr:null},{language_id:89,language_ch:"日文",language_en:"Japanese",language_jp:null,language_kr:null},{language_id:90,language_ch:"基础信息",language_en:"Basic Information",language_jp:null,language_kr:null},{language_id:91,language_ch:"语言信息",language_en:"Language Information",language_jp:null,language_kr:null},{language_id:92,language_ch:"基本信息",language_en:"Basic Information",language_jp:null,language_kr:null},{language_id:93,language_ch:"商品单位",language_en:"Commodity Unit",language_jp:null,language_kr:null},{language_id:94,language_ch:"产地",language_en:"Origin",language_jp:null,language_kr:null},{language_id:95,language_ch:"sku信息",language_en:"SKU Information",language_jp:null,language_kr:null},{language_id:96,language_ch:"spu信息",language_en:"SPU Information",language_jp:null,language_kr:null},{language_id:97,language_ch:"添加SKU属性",language_en:"Add SKU Attribute",language_jp:null,language_kr:null},{language_id:98,language_ch:"添加SKU",language_en:"Add SKU",language_jp:null,language_kr:null},{language_id:99,language_ch:"属性信息",language_en:"Attribute Information",language_jp:null,language_kr:null},{language_id:100,language_ch:"序列号",language_en:"Serial Number",language_jp:null,language_kr:null},{language_id:101,language_ch:"cr编码",language_en:"CR Code",language_jp:null,language_kr:null},{language_id:102,language_ch:"hs编码",language_en:"HS Code",language_jp:null,language_kr:null},{language_id:103,language_ch:"物流信息",language_en:"Logistics Information",language_jp:null,language_kr:null},{language_id:104,language_ch:"长",language_en:"Length",language_jp:null,language_kr:null},{language_id:105,language_ch:"宽",language_en:"Width",language_jp:null,language_kr:null},{language_id:106,language_ch:"高",language_en:"Height",language_jp:null,language_kr:null},{language_id:107,language_ch:"重量",language_en:"Weight",language_jp:null,language_kr:null},{language_id:108,language_ch:"包装规格",language_en:"Packing Specification",language_jp:null,language_kr:null},{language_id:109,language_ch:"商品箱规",language_en:"Commodity Box Specification",language_jp:null,language_kr:null},{language_id:110,language_ch:"发货类型",language_en:"Delivery Type",language_jp:null,language_kr:null},{language_id:111,language_ch:"海关申报信息",language_en:"Customs Declaration Information",language_jp:null,language_kr:null},{language_id:112,language_ch:"内置电池",language_en:"Built-in Battery",language_jp:null,language_kr:null},{language_id:113,language_ch:"纯电池",language_en:"Pure Battery",language_jp:null,language_kr:null},{language_id:114,language_ch:"配套电池",language_en:"Assorted Battery",language_jp:null,language_kr:null},{language_id:115,language_ch:"液体",language_en:"Liquid",language_jp:null,language_kr:null},{language_id:116,language_ch:"刀具",language_en:"Cutting Tool",language_jp:null,language_kr:null},{language_id:117,language_ch:"磁性",language_en:"Magnetic",language_jp:null,language_kr:null},{language_id:118,language_ch:"膏状体",language_en:"Paste",language_jp:null,language_kr:null},{language_id:119,language_ch:"粉末",language_en:"Powder",language_jp:null,language_kr:null},{language_id:120,language_ch:"药品",language_en:"Medicine",language_jp:null,language_kr:null},{language_id:121,language_ch:"烟酒",language_en:"Alcohol & Tobacco",language_jp:null,language_kr:null},{language_id:122,language_ch:"易燃易爆品",language_en:"The Inflammable & Explosive",language_jp:null,language_kr:null},{language_id:123,language_ch:"易碎",language_en:"Fragile",language_jp:null,language_kr:null},{language_id:124,language_ch:"申报价值（美元）",language_en:"Declared Value (USD)",language_jp:null,language_kr:null},{language_id:125,language_ch:"确定",language_en:"Confirm",language_jp:null,language_kr:null},{language_id:126,language_ch:"属性值搜索",language_en:"Attribute Value Search",language_jp:null,language_kr:null},{language_id:127,language_ch:"选择上架",language_en:"Select Putaway",language_jp:null,language_kr:null},{language_id:128,language_ch:"平台类型",language_en:"Platform Type",language_jp:null,language_kr:null},{language_id:129,language_ch:"全选",language_en:"Select All",language_jp:null,language_kr:null},{language_id:130,language_ch:"平台搜索",language_en:"Platform Search",language_jp:null,language_kr:null},{language_id:131,language_ch:"平台",language_en:"Platform",language_jp:null,language_kr:null},{language_id:132,language_ch:"价格信息",language_en:"Price Information",language_jp:null,language_kr:null},{language_id:133,language_ch:"货币",language_en:"Currency",language_jp:null,language_kr:null},{language_id:134,language_ch:"售价",language_en:"Price",language_jp:null,language_kr:null},{language_id:135,language_ch:"销售信息",language_en:"Sales Information",language_jp:null,language_kr:null},{language_id:136,language_ch:"起售数量",language_en:"Sales Quantity",language_jp:null,language_kr:null},{language_id:137,language_ch:"商品图片",language_en:"Commodity Image",language_jp:null,language_kr:null},{language_id:138,language_ch:"商品主图",language_en:"Product Main Image",language_jp:null,language_kr:null},{language_id:139,language_ch:"选择文件",language_en:"Select File",language_jp:null,language_kr:null},{language_id:140,language_ch:"默认为列表图",language_en:"Default List Diagram",language_jp:null,language_kr:null},{language_id:141,language_ch:"商品列表图",language_en:"Commodity List",language_jp:null,language_kr:null},{language_id:142,language_ch:"视频地址",language_en:"Video Address",language_jp:null,language_kr:null},{language_id:143,language_ch:"地址",language_en:"Address",language_jp:null,language_kr:null},{language_id:144,language_ch:"商品详情",language_en:"Commodity Details",language_jp:null,language_kr:null},{language_id:145,language_ch:"通过",language_en:"Approve",language_jp:null,language_kr:null},{language_id:146,language_ch:"选择语言",language_en:"Select Language",language_jp:null,language_kr:null},{language_id:147,language_ch:"立即保存",language_en:"Save Instantly ",language_jp:null,language_kr:null},{language_id:148,language_ch:"所选属性值",language_en:"Select Attribute Value",language_jp:null,language_kr:null}]},j=k.data,s={},m={},C=!0,f=!1,y=void 0;try{for(var S,A=e()(j);!(C=(S=A.next()).done);C=!0){var D=S.value;s[D.language_ch]=D.language_ch,m[D.language_ch]=D.language_en}}catch(a){f=!0,y=a}finally{try{!C&&A.return&&A.return()}finally{if(f)throw y}}r.default.use(i.a);var I={en:_()({},m,p.a),zh:_()({},s,o.a)},v=new i.a({locale:function(){return"zh-cn"==c.a.get("think_language")?"zh":"en-us"==c.a.get("think_language")?"en":""}()||"zh",messages:I});g.a=v},216:function(a,g){a.exports={render:function(){var a=this,g=a.$createElement,l=a._self._c||g;return l("div",{staticClass:"category-box"},[l("router-view")],1)},staticRenderFns:[]}},3:function(a,g){},4:function(a,g){},93:function(a,g,l){l(199);var n=l(13)(l(146),l(216),null,null);a.exports=n.exports}},[137]);