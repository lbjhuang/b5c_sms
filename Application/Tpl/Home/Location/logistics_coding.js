if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}
var Vm = new Vue({
    el: '#logisticsCoding',
    data() {
      return {
        // 公司列表获取
        rcd_id:'',
        lgt_track_platform_cd:'',
        lgt_track_platform_name:'',
        level:'',
        form: {
          logistics_name: '',
          com_en_name: '',
          service_code: ''
        },
        page:{
            count:0,
            this_page:1,
            page_count:10,
        },
        // 表单内容
        tableData: [],
        dialogForm: {
            rcd_id:'',
            optional:true,
            IStrajectory: true, // 是否查询物流轨迹
            // PartnerData: '', // 伙伴数据拼音代码
            companyCode: '', // 物流公司code
            aboutStore: [], // 关联店铺
            LogisticsCompanyName:'',
            LogisticsCompanyNameEn: '',
            com_sort_name: '', // 伙伴数据拼音代码
            ServiceCode:'',
            lgt_track_platform_cd:'',
            LogisticsTrackPlatform:[],
            level:'',
            lgt_track_platform_name:''
        },
        all: false,
        companyCode_list: [], // 物流公司code列表
        aboutStore_list: [], // 关联店铺列表
        Popstatus:"",
        dialogVisible: false,
        tableLoading: false,
        nameStatus:false,
        rules: {
          LogisticsCompanyName: [
            { required: true, message: this.$lang('请输入实际物流公司名称'), trigger: 'blur' }
          ],
          lgt_track_platform_cd: [
            { required: true, message: this.$lang('请选择物流轨迹平台'), trigger: 'change' }
          ],
          ServiceCode: [
            { required: true, message: this.$lang('请输入服务代码'), trigger: 'blur' }
          ],
          com_sort_name: [
            { required: true, message: this.$lang('请输入伙伴数据拼音代码'), trigger: 'blur' }
          ],
          level: [
            { required: true, message: this.$lang('请输入优先级'), trigger: 'blur' },
            { type: 'number', message: this.$lang('优先级必须为数字值')}
          ]
        },
        numberValidateForm: {
          age: ''
        }
      }
    },
    beforeCreate:function () {
        // 物流轨迹平台获取
        var _this = this
        var getDictionaryListUrl='/index.php?g=universal&m=dictionary&a=getDictionaryList&prefix=N00285';
        axios.get(getDictionaryListUrl)
            .then(function(res) {
              var companyData=[],
              DictionaryListcompany=res.data.data['N00285'];
              for(key in DictionaryListcompany){
                  var companyObj={};
                  companyObj.code = DictionaryListcompany[key].CD;
                  companyObj.val = DictionaryListcompany[key].CD_VAL;
                  companyData.push(companyObj)
              }
              _this.dialogForm.LogisticsTrackPlatform =companyData;
        })
    },
    created :function(){
      this.getTabledata();
      this.getCodeList();
      this.getStoreList();
    },
    methods:{
        // 获取物流公司code列表
        getCodeList() {
          var _this = this;
          let param = {
            cd_type: {
              logics_company: "false", //true查所有（一般列表查询条件时用）
            }
          }
          axios.post('/index.php?g=common&m=index&a=get_cd', param).then(function(res) {
            
            console.log(res);
            if (res.data.code == 2000) {
              _this.companyCode_list = res.data.data.logics_company;
            }
          })
        },
        // 获取关联店铺列表
        getStoreList() {
          var _this = this;
          let param = {
            cd_type: {
              plat: "true", //true查所有（一般列表查询条件时用）
          }
          }
          axios.post('/index.php?g=common&m=index&a=get_cd', param).then(function(res) {
            console.log(res);
            if (res.data.code == 2000) {
              _this.aboutStore_list = res.data.data.plat;
            }
          })
        },
        // 选择全部店铺
        allChange(type) {
          if (type) {
            this.dialogForm.aboutStore = ['all']
          } else {
            this.dialogForm.aboutStore = []
          }
        },
        // 获取表单内容
        getTabledata: function getTabledata (num) {

            this.tableLoading = true;
            var _this = this;
            var logistics_name = _this.form.logistics_name
            var com_en_name = _this.form.com_en_name
            var service_code = _this.form.service_code
            // if(num){
            //   var this_page = _this.page.this_page
            // }else{
            //   var this_page = 1
            // }
            var this_page = _this.page.this_page
            var page_count = _this.page.page_count
            // console.log(logistics_name)
            // console.log(com_en_name)
            // console.log(service_code)
            // console.log('传'+this_page)
            // console.log('传'+page_count)
            // console.log(page_count)
            axios.post("/index.php?g=logistics&m=realLgtCompany&a=getComInfoList",{
                "data": {
                  "logistics_name":logistics_name,
                  "com_en_name" : com_en_name,
                  "service_code" : service_code,
                },
                "page": {
                  "this_page": this_page,
                  "page_count": page_count
                }
            }).then(function (res) {
                console.log(res)
                if (res.data.code == 200) {
                  _this.page.this_page = Number(res.data.data.page.this_page)
                  _this.page.count = Number(res.data.data.page.count)
                  _this.page.page_count = Number(res.data.data.page.page_count)
                  // console.log('收'+res.data.data.page.this_page)
                  var data=res.data.data.data;
                  var basisDataArry=[];
                  for(key in data){
                    var basisDataObj={};
                    basisDataObj.rc_id = data[key].rc_id;
                      basisDataObj.optional = data[key].optional
                    basisDataObj.rcd_id = data[key].rcd_id;
                    basisDataObj.logistics_name = data[key].logistics_name;
                    basisDataObj.com_en_name = data[key].com_en_name;
                    basisDataObj.com_sort_name = data[key].com_sort_name;
                    basisDataObj.service_code = data[key].service_code;
                    basisDataObj.lgt_track_platform_cd = data[key].lgt_track_platform_cd;
                    basisDataObj.lgt_track_platform_name = data[key].lgt_track_platform_name;
                    basisDataObj.level = data[key].level;
                    basisDataObj.check_logistics = data[key].check_logistics;
                    basisDataObj.logistics_cd = data[key].logistics_cd;
                    basisDataObj.store_cd = data[key].store_cd;
                    basisDataArry.push(basisDataObj);
                  }
                  _this.tableData = basisDataArry;
                  _this.tableLoading = false;
                }
            })
        },
        // 操作按钮
        operating:function (state,data) {
          // debugger;
            console.log(data)
            this.dialogVisible = true;
            var _this = this;
            if(_this.$refs.ruleForm){
              _this.$refs.ruleForm.resetFields();
            }
            console.log('操作');
            if(state == "add"){
                _this.Popstatus = "add";
                _this.nameStatus=false
                _this.dialogForm.LogisticsCompanyName = ''
                _this.dialogForm.optional = false
                _this.dialogForm.LogisticsCompanyNameEn = ''
                _this.dialogForm.com_sort_name = ''
                _this.dialogForm.ServiceCode = ''
                _this.dialogForm.level = ''
                _this.dialogForm.lgt_track_platform_cd = 'N002850001'
                _this.dialogForm.lgt_track_platform_name = ''
                _this.dialogForm.LogisticsCompanyNameEn = ''
                _this.dialogForm.com_sort_name = ''
                _this.dialogForm.LogisticsCompanyName = ''
                _this.dialogForm.ServiceCode = ''
            }else if(state == "modefied"){
                _this.Popstatus = "modefied";
                _this.nameStatus=true
                _this.dialogForm.optional = (data.optional==='2'?true:false)
                _this.dialogForm.rc_id = data.rc_id
                _this.dialogForm.rcd_id = data.rcd_id
                _this.dialogForm.LogisticsCompanyName = data.logistics_name
                _this.dialogForm.LogisticsCompanyNameEn = data.com_en_name
                _this.dialogForm.com_sort_name = data.com_sort_name
                _this.dialogForm.ServiceCode = data.service_code
                // _this.dialogForm.level = data.level
                _this.dialogForm.level = Number(data.level)
                _this.dialogForm.lgt_track_platform_cd = data.lgt_track_platform_cd
                _this.dialogForm.lgt_track_platform_name = data.lgt_track_platform_name

                _this.dialogForm.IStrajectory = data.check_logistics === '1' ? true : false
                _this.dialogForm.companyCode = data.logistics_cd
                _this.dialogForm.aboutStore = data.store_cd ? data.store_cd.split(',') : []
                this.all = data.store_cd === 'all' ? true : false
            }
        },
        //取消按钮
        cancel:function (formName) {
            this.$refs[formName].resetFields();
            this.dialogVisible = false;
            // this.$refs[formName].clearValidate();
        },
        //切换每页展示的数目
        handleSizeChange:function (val) {
          console.log('num')
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
        // 物流轨迹平台下拉change
        logisticsChange: function (value) {
            console.log(value)
        },
        //搜索功能
        basisDataSearch:function () {
          //  console.log(_this.form.logistics_name)
          //  console.log(_this.form.com_en_name)
          //  console.log(_this.form.service_code)
          //  console.log(_this.page.this_page)
          //  console.log(_this.page.page_count)
          this.getTabledata()
        },
        // 重置
        reset: function () {
            this.form = {
                logistics_name: '',
                com_en_name: '',
                service_code: '',
            }
            this.page.this_page = 1
            this.getTabledata()
            
        },
        // 保存编辑/新增
        basisSubmitButton:function (formName) {
            // this.dialogVisible = false;
            var _this = this;



            console.log(this.dialogForm);
            if(_this.Popstatus == 'add'){
              // console.log('add')
              // console.log(_this.dialogForm.LogisticsCompanyName)
              // console.log(_this.dialogForm.LogisticsCompanyNameEn)
              // console.log(_this.dialogForm.ServiceCode)
              // console.log(_this.dialogForm.lgt_track_platform_cd)
              // console.log(_this.dialogForm.level)

              this.$refs[formName].validate( function(valid) {
                if (valid) {
                  axios.post("/index.php?g=logistics&m=realLgtCompany&a=saveComInfo",{
                      "rc_id" : '',
                      "logistics_name" : _this.dialogForm.LogisticsCompanyName,
                      "com_en_name" : _this.dialogForm.LogisticsCompanyNameEn,
                      "com_sort_name": _this.dialogForm.com_sort_name, // 伙伴数据拼音代码
                      "service_code" : _this.dialogForm.ServiceCode,
                      "lgt_track_platform_cd" : _this.dialogForm.lgt_track_platform_cd, 
                      "level" : _this.dialogForm.level,
                      "check_logistics": _this.dialogForm.IStrajectory ? 1: 0,
                      "optional":_this.dialogForm.optional ? 2: 1,
                      'logistics_cd': _this.dialogForm.companyCode,
                      'store_cd': _this.dialogForm.aboutStore.join(",")
                  }).then(function (res) {
                      console.log(res)
                      if (res.data.code == 200) {
                        _this.$message({
                          message: _this.$lang('添加成功'),
                          type: 'success'
                        })
                        _this.getTabledata()
                        this.dialogVisible = false;
                        location.reload()
                      }else{
                        _this.$message({
                            message: _this.$lang(res.data.msg),
                            type: 'error'
                        })
                      }
                  })

                } else {
                  console.log('error submit!!');
                  return false;
                }
              });





            }else if(_this.Popstatus == 'modefied'){
              // console.log('modefied')
              // console.log(_this.dialogForm.rc_id)
              // console.log(_this.dialogForm.LogisticsCompanyName)
              // console.log(_this.dialogForm.LogisticsCompanyNameEn)
              // console.log(_this.dialogForm.ServiceCode)
              // console.log(_this.dialogForm.lgt_track_platform_cd)
              _this.dialogForm.level = Number(_this.dialogForm.level)
              console.log(_this.dialogForm.com_sort_name);
              this.$refs[formName].validate( function(valid) {
                if (valid) {
                  
                axios.post("/index.php?g=logistics&m=realLgtCompany&a=saveComInfo",{
                  "rc_id" : _this.dialogForm.rc_id,
                  "rcd_id" : _this.dialogForm.rcd_id,
                  "logistics_name" : _this.dialogForm.LogisticsCompanyName,
                  "com_en_name" : _this.dialogForm.LogisticsCompanyNameEn,
                  "com_sort_name": _this.dialogForm.com_sort_name,
                  "service_code" : _this.dialogForm.ServiceCode,
                  "lgt_track_platform_cd" : _this.dialogForm.lgt_track_platform_cd, 
                  "level" : _this.dialogForm.level,
                  "check_logistics": _this.dialogForm.IStrajectory ? 1: 0,
                    "optional":_this.dialogForm.optional ? 2: 1,
                  'logistics_cd': _this.dialogForm.companyCode,
                  'store_cd': _this.dialogForm.aboutStore.join(',')
                }).then(function (res) {
                  console.log(res)
                  if (res.data.code == 200) {
                    _this.$message({
                        message: _this.$lang('修改成功'),
                        type: 'success'
                    })
                    _this.getTabledata()
                    this.dialogVisible = false;
                    location.reload()
                  }else{
                    _this.$message({
                        message: _this.$lang(res.data.msg),
                        type: 'error'
                    })
                  }
                })

                } else {
                  console.log('error submit!!');
                  return false;
                }
              });





            }


        },
        // 删除
        deleteData:function(data){
            var _this = this;
            console.log(typeof(data.rcd_id))
            _this.$confirm(_this.$lang('是否确认删除此类目'), _this.$lang('提示'), {
              confirmButtonText: _this.$lang('确定'),
              cancelButtonText: _this.$lang('取消'),
              type: 'warning'
            }).then(function () {
              axios.post("/index.php?g=logistics&m=realLgtCompany&a=deleteRealCompanyInfo",{
                  "rcd_id":data.rcd_id
              }).then(function (res) {
                  console.log(res)
                  if (res.data.code == 200) {
                    _this.$message({
                      message: _this.$lang('删除成功'),
                      type: 'success'
                    })
                    _this.getTabledata()
                  }else{
                    _this.$message({
                        message: _this.$lang(res.data.msg),
                        type: 'error'
                    })
                  }
              })
            }).catch(function () {
                _this.$message({
                  type: 'info',
                  message: _this.$lang('已取消删除')
                });
            });
        }
    }
})