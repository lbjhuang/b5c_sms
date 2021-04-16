'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}



var VM = new Vue({
    el: '#companyManagement',
    data: {
        form: {
            company_cd:[],
            company:[],
            BusinessRegistration_cd:[],
            BusinessRegistration:[],
            selectCountry: '',
            selectProvince: '',
            selectCounty: '',
            country:[],
            province:[],
            county:[],
            representative:'',
            shareholder_name:'',
        },
        click_disabled: false,
        page:{
            count:0,
            this_page:1,
            page_count:10,
        },
        // 表单的内容
        tableData: [],
        tableLoading: false,
    },
    created() {
        this.ourCompany();
        this.areaData();
        this.getTabledata()
        this.getOurCompany();
    },
    methods: {
        // 区域获取
        areaData: function () {
            var _this = this;
            axios.post('/index.php?&m=company&a=get_area', {
                "parent_no": 0,
                "is_id":''
            }).then(function (res) {
            // console.log(res)
              if (res.data.code == 2000) {
                _this.form.country = res.data.data;
              } else {
                _this.$message({
                  message: data.msg,
                  type: 'error'
                });
              }
            })
        },
        // 新版获取我方公司
        getOurCompany() {
            var _this = this;
            axios.post('/index.php?m=company&a=getOurCompany').then(function(res) {
                if (res.data.code === 2000) {
                    _this.form.company = res.data.data;
                } else {
                    _this.$message.error(this.$lang(res.data.msg));
                }
            })
        },
        // 我方公司获取
        ourCompany:function(){
            var _this = this;
            axios.post('/index.php?g=common&m=index&a=get_cd', {
                cd_type:{
                    company_business_status:'true'
                }
            }).then(function (res) {
                console.log(res)
                if(res.data.code === 2000){
                    _this.form.BusinessRegistration = res.data.data.company_business_status
                }
            })
        },
        // 获取表单内容
        getTabledata: function getTabledata () {
            var _this = this
            // _this.tableLoading = true;
            axios.post('/index.php?m=company&a=companyManagementList', {
                search: {
                    "our_company_cd":_this.form.company_cd,
                    "company_business_status_cd":_this.form.BusinessRegistration_cd,
                    "reg_country":_this.form.selectCountry,
                    "reg_province":_this.form.selectProvince,
                    "reg_city":_this.form.selectCounty,
                    "legal_name":_this.form.representative,
                    "shareholder_name":_this.form.shareholder_name // 替换成股东字段
                },
                pages: {
                    "per_page": _this.page.page_count,
                    "current_page": _this.page.this_page
                }
            }).then(function (res) {
                console.log(res)
                if (res.data.code == 200) {
                    _this.page.count = Number(res.data.data.pages.total)
                    _this.page.page_count = Number(res.data.data.pages.per_page)

                    var data=res.data.data.data;
                    var basisDataArry=[];
                    for(let key in data){
                        var basisDataObj={};
                        basisDataObj.our_company_cd_val = data[key].our_company_cd_val;
                        basisDataObj.company_business_status_cd_val = data[key].company_business_status_cd_val;
                        basisDataObj.reg_country = data[key].reg_country;
                        basisDataObj.reg_province = data[key].reg_province;
                        basisDataObj.reg_city = data[key].reg_city;
                        basisDataObj.two_char = data[key].two_char;
                        basisDataObj.reg_amount_cd_val = data[key].reg_amount_cd_val;
                        basisDataObj.reg_amount = data[key].reg_amount;
                        basisDataObj.legal_name = data[key].legal_name;
                        basisDataObj.shareholder_name = data[key].shareholder_name;
                        basisDataObj.company_no = data[key].company_no;
                        basisDataObj.supervisor_name = data[key].supervisor_name;
                        basisDataObj.legal_alias_name = data[key].legal_alias_name;
                        basisDataObj.supervisor_alias_name = data[key].supervisor_alias_name;
                        basisDataObj.remark = data[key].remark;
                        basisDataObj.idd = data[key].id;

                        basisDataArry.push(basisDataObj);

                    }
                    // console.log(basisDataArry)
                    _this.tableData = basisDataArry;
                    // _this.tableLoading = false;

                } else {
                    // _this.$message({
                    //     message: res.data.msg,
                    //     type: 'error'
                    // });
                }

            })
            
        },
        // 公司下拉change
        companyChangeForm: function (value) {
            console.log(value)
            // this.form.company_cd = this.form.company_cd[0]
        },
        BusinessRegistrationChangeForm:function(value) {
            console.log(value)
        },
        //搜索功能
        search:function () {
            this.getTabledata()
        },
        // 重置
        reset: function () {
            this.form.company_cd = []
            this.form.BusinessRegistration_cd = []
            this.form.selectCountry = ''
            this.form.selectProvince = ''
            this.form.selectCounty = ''
            this.form.province = []
            this.form.county = []
            this.form.representative = ''
            this.form.supervisor = ''
            this.form.shareholder_name = ''

            this.page.this_page = 1
            this.getTabledata()
        },
        // 操作按钮
        operating:function (state,data) {
            var _this = this;
            if(state == "add"){
                var route = document.createElement("a");
                // var title = "opennewtab(this,this.$lang('新建我方公司'))";
                var title = "opennewtab(this,'" + this.$lang('新建我方公司') + "')";

                route.setAttribute("style", "display: none");
                route.setAttribute("onclick", title);
                route.setAttribute("_href", '/index.php?m=company&a=create&type=add');
                route.onclick();
            }else if(state == "toDetail"){
                var route = document.createElement("a");
                // 翻译后的数据有单引号                
                var title = `opennewtab(this,"${this.$lang('我方公司详情')}")`;
                route.setAttribute("style", "display: none");
                route.setAttribute("onclick", title);
                route.setAttribute("_href", '/index.php?m=company&a=detail&idd='+data.idd);
                sessionStorage.setItem("ourCompany", '');
                route.onclick();
            }
        },
        // 删除
        deleteData:function(data){
            
        },
        //切换每页展示的数目
        handleSizeChange:function (val) {
            this.page.this_page = 1;
            this.page.page_count = val;
            this.getTabledata()
        },
        //翻页切换不同页面
        handleCurrentChange:function(val) {
            var _this = this
            // console.log('cho')
            _this.page.this_page = val;
            _this.getTabledata()
        },
        handleExport() {
            var _this = this;
            _this.click_disabled = true;
            var params = {
                search: {
                    "our_company_cd":_this.form.company_cd,
                    "company_business_status_cd":_this.form.BusinessRegistration_cd,
                    "reg_country":_this.form.selectCountry,
                    "reg_province":_this.form.selectProvince,
                    "reg_city":_this.form.selectCounty,
                    "legal_name":_this.form.representative,
                    "shareholder_name":_this.form.shareholder_name // 替换成股东字段
                },
                pages: {
                    "per_page": _this.page.page_count,
                    "current_page": _this.page.this_page
                }
            }
            var tmep = document.createElement('form');
            tmep.action = '/index.php?m=company&a=exportManagement';
            tmep.method = "post";
            tmep.style.display = "none";
            var opt = document.createElement("input");
            opt.name = 'export_params';
            opt.value = JSON.stringify(params);
            tmep.appendChild(opt);
            document.body.appendChild(tmep);
            tmep.submit();
            $(tmep).remove();
            _this.click_disabled = false;
            // axios.post('/index.php?m=company&a=exportManagement', params, {responseType: 'blob'}).then(res => {
            //     var blob = res.data;
            //     let reader = new FileReader()
            //     reader.readAsDataURL(blob)
            //     reader.onload = (e) => {
            //         let a = document.createElement('a')
            //         a.download = 'company_management_list' + new Date().Format('yyyyMMdd') + '.csv'
            //         a.href = e.target.result
            //         document.body.appendChild(a)
            //         a.click()
            //         document.body.removeChild(a)
            //     }
            // })
        },
        // 对于注销公司置灰
        handleCancellationCompany({row, rowIndex}) {
            if (row.company_business_status_cd_val === '注销') {
                return 'cancellation'
            }
        }
        
    },
    filters: {
        // 千分位处理
        thousandsDeal:function(value){
            if(value){
                return (value || 0).toString().replace(/\d+/, function (n) {
                    var len = n.length;
                    if (len % 3 === 0) {
                        return n.replace(/(\d{3})/g, ',$1').slice(1);
                    } else {
                        return n.slice(0, len % 3) + n.slice(len % 3).replace(/(\d{3})/g, ',$1');
                    }
                })
            }else{
                return ''
            }
        }
    },
    computed: {
        selectCountry() {
            return this.form.selectCountry
        },
        selectProvince() {
            return this.form.selectProvince
        },
    },
    watch: {
        selectCountry: {
            handler(newValue, oldValue) {
                console.log(newValue)
                var _this = this
                _this.form.province = []
                _this.form.selectProvince = ''
                _this.form.county = []
                _this.form.selectCounty = ''

                axios.post('/index.php?g=common&m=index&a=get_area', {
                    parent_no: newValue,
                    is_id:'Y'
                }).then(function (res) {
                  console.log(res)
                  if (res.data.code == 2000) {
                    _this.form.province = res.data.data;
                  } else {
                    _this.$message({
                      message: data.msg,
                      type: 'error'
                    });
                  }
                })
            },
            deep: true
        },
        selectProvince: {
            handler(newValue, oldValue) {
                console.log(newValue)
                var _this = this
                _this.form.county = []
                _this.form.selectCounty = ''

                axios.post('/index.php?g=common&m=index&a=get_area', {
                    parent_no: newValue,
                    is_id:'Y'
                }).then(function (res) {
                  console.log(res)
                  if (res.data.code == 2000) {
                    _this.form.county = res.data.data;
                  } else {
                    _this.$message({
                      message: data.msg,
                      type: 'error'
                    });
                  }
                })
            },
            deep: true
        },
    },  

})