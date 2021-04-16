'use strict';
if (getCookie('think_language') !== "zh-cn") {
    ELEMENT.locale(ELEMENT.lang.en)
}

var VM = new Vue({
    el: '#company-license-management',
    data: {
        form: {
            qualification_num: '',
            ourCompany_cd:'',
            ourCompany:[],
            qualification_name: ''
        },
        page:{
            count:0,
            this_page:1,
            page_count:10,
        },
        popstatus:'',
        dialogVisible: false,
        expiryDatePrompt:false,
        // attachmentPrompt:false,
        tableLoading: false,
        paginationStatus:true,
        // 表单内容
        tableData: [],
        // dialogForm: {
        //     idd:'',
        //     qualificationName:'',
        //     ourCompany_cd:'',
        //     ourCompany:[],
        //     qualificationAttachment:[],
        //     qualificationAttachment2:[],
        //     issuingDay:'',
        //     expiryDate:'',
        //     expiryDateStatus:false,
        //     expiryChecked:false,
        //     renewalDate:'',
        //     issuingAuthority:'',
        //     renewalLocation:'',
        //     renewalMaterial:'',
        //     correspondingDepartment:'',
        //     precautions:''
        // },
        // rules: {
        //     qualificationName: [
        //         { required: true, message: '请输入资质名称', trigger: 'blur' }
        //     ],
        //     ourCompany_cd: [
        //         { required: true, message: '请选择所属我方公司', trigger: 'change' }
        //     ],
        //     qualificationAttachment: [
        //         {required: true, message: '请上传资质附件', trigger: 'change'}
        //     ],
        // },
        // pickerOptions:{
        //     disabledDate(time) {
        //         return time.getTime() > Date.now() - 8.64e6
        //     }
        // }
    },
    created :function(){
        this.getOurCompany();
        this.getTabledata()
      },
    methods:{
        // 新版获取我方公司
        getOurCompany() {
            var _this = this;
            axios.post('/index.php?g=common&m=index&a=get_our_company').then(function(res) {
                if (res.data.code === 2000) {
                    _this.form.ourCompany = res.data.data;
                } else {
                    _this.$message.error(this.$lang(res.data.msg));
                }
            })
        },
        // 获取表单内容
        getTabledata: function (num) {
            this.tableLoading = true;
            var _this = this;
            var number = _this.form.qualification_num
            var our_company_code = _this.form.ourCompany_cd
            var name = _this.form.qualification_name
       
            var this_page = _this.page.this_page
            var page_count = _this.page.page_count
            axios.post("index.php?m=company&a=qualificationList",{
                "search": {
                    "number":number,
                    "our_company_code" : our_company_code,
                    "name" : name,
                },
                "pages": {
                    "current_page": this_page,
                    "per_page": page_count
                }
            }).then(function (res) {
                // console.log(res)
                if (res.data.code == 200) {
                    // _this.page.this_page = Number(res.data.data.pages.current_page)
                    _this.page.count = Number(res.data.data.pages.total)
                    _this.page.page_count = Number(res.data.data.pages.per_page)
                   
                    var data=res.data.data.data;
                    var basisDataArry=[];
                    for(let key in data){
                        var basisDataObj={};
                        basisDataObj.number = data[key].number;
                        basisDataObj.our_company_code = data[key].our_company_code;
                        basisDataObj.our_company_code_val = data[key].our_company_code_val;
                        basisDataObj.name = data[key].name;
                        basisDataObj.attachment = [];
                        basisDataObj.issue_date = data[key].issue_date;
                        basisDataObj.expire_date = data[key].expire_date;
                        basisDataObj.renew_date = data[key].renew_date;
                        basisDataObj.issue_office = data[key].issue_office;
                        basisDataObj.renew_address = data[key].renew_address;
                        basisDataObj.renew_material = data[key].renew_material;
                        basisDataObj.department = data[key].department;
                        basisDataObj.precautions = data[key].precautions;
                        basisDataObj.id = data[key].id;
                        basisDataObj.is_long_time = data[key].is_long_time;
                        basisDataObj.content = data[key].content;
                        basisDataObj.query_path = data[key].query_path;
                        

                        if(data[key].attachment.length != 0){
                            var attachment = JSON.parse(data[key].attachment)
                            for(let item in attachment){
                                var attachmentObj = {}
                                attachmentObj.original_name = attachment[item].original_name
                                attachmentObj.save_name = attachment[item].save_name
                                basisDataObj.attachment.push(attachmentObj)
                            }
                        }

                        // if(basisDataObj == null){
                        //     console.log('null')
                        // }
                        basisDataArry.push(basisDataObj);
                        
                    }
                    if(basisDataArry.length == 0){
                        _this.paginationStatus = false
                    }else{
                        _this.paginationStatus = true
                    }
                    _this.tableData = basisDataArry;
                    _this.tableLoading = false;
                }

            })
        },
        //搜索
        search:function () {
            this.getTabledata()
        },
        // 重置
        reset: function () {
            this.form = {
                qualification_num: '',
                ourCompany_cd:'',
                ourCompany:[],
                qualification_name: ''
            }
            this.page={
                this_page:1,
                page_count:10
            }
            this.getOurCompany();
            this.getTabledata()

        },
        // 预览
        open(name){
            let API_ROOT = window.location.host === 'erp.gshopper.com' ? 'http://erp.gshopper.com' : 'http://erp.gshopper.stage.com'
            window.open(`${API_ROOT}/opt/b5c-disk/img/${name}`);
        },
        // 新增/编辑
        operating:function (title,data) {
            var dom = document.createElement("a"),_href = "";
            console.log(data);
            console.log(this.tableData[0]);
            if(data) {
                _href = "/index.php?m=company&a=qualification_edit";
                sessionStorage.setItem('qualification_detaile', JSON.stringify(data))
                
            } else {
                _href = "/index.php?m=company&a=qualification_add"
            }
            // dom.setAttribute("onclick", "opennewtab(this,'" + title + "')");

            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang(title) + "')");



            dom.setAttribute("_href", _href);
            dom.click();
            // this.getCompanyData();
            
            // var _this = this;
            // if(_this.$refs.ruleForm){
            //   _this.$refs.ruleForm.resetFields();
            // }

            // if(state == "add"){
            //     this.dialogVisible = true;
            //     _this.popstatus = "add";
            //     _this.dialogForm.qualificationName = ''
            //     _this.dialogForm.ourCompany_cd = ''
            //     _this.dialogForm.ourCompany = []
            //     _this.dialogForm.qualificationAttachment = []
            //     _this.dialogForm.qualificationAttachment2 = []
            //     _this.dialogForm.issuingDay = ''
            //     _this.dialogForm.expiryDate = ''
            //     _this.dialogForm.expiryDateStatus = false
            //     _this.dialogForm.expiryChecked = false
            //     _this.dialogForm.renewalDate = ''
            //     _this.dialogForm.issuingAuthority = ''
            //     _this.dialogForm.renewalLocation = ''
            //     _this.dialogForm.renewalMaterial = ''
            //     _this.dialogForm.correspondingDepartment = ''
            //     _this.dialogForm.precautions = ''
            // }else if(state == "modefied"){
            //     this.dialogVisible = true;
            //     console.log(data)
            //     _this.popstatus = "modefied";
            //     _this.dialogForm.ourCompany_cd = data.our_company_code
            //     _this.dialogForm.qualificationName = data.name
            //     _this.dialogForm.qualificationAttachment = []
            //     _this.dialogForm.qualificationAttachment2 = []
            //     for(let item in data.attachment){
            //         var attachmentObj = {}
            //         var attachmentObj2 = {}
            //         attachmentObj.name = data.attachment[item].original_name
            //         attachmentObj2.original_name = data.attachment[item].original_name
            //         attachmentObj2.save_name = data.attachment[item].save_name
            //         _this.dialogForm.qualificationAttachment.push(attachmentObj)
            //         _this.dialogForm.qualificationAttachment2.push(attachmentObj2)
            //     }

                
            //     // _this.dialogForm.issuingDay = data.issue_date
            //     _this.dialogForm.issuingDay = _this.dateFormatToStandard(data.issue_date)

            //     // if(data.expire_date){
            //         if(data.is_long_time == '1'){
            //             _this.dialogForm.expiryChecked = true
            //         }else if(data.expire_date){
            //             // _this.dialogForm.expiryDate = data.expire_date
            //             _this.dialogForm.expiryDate = _this.dateFormatToStandard(data.expire_date)
            //         }
            //     // }
            //     // _this.dialogForm.renewalDate = data.renew_date
            //     _this.dialogForm.renewalDate = _this.dateFormatToStandard(data.renew_date)
            //     _this.dialogForm.issuingAuthority = data.issue_office
            //     _this.dialogForm.renewalLocation = data.renew_address
            //     _this.dialogForm.renewalMaterial = data.renew_material
            //     _this.dialogForm.correspondingDepartment = data.department
            //     _this.dialogForm.precautions = data.precautions
            //     _this.dialogForm.idd = data.idd
            // }else if(state == 'delete'){

            //     _this.$confirm('是否确认删除此条数据', '提示', {
            //         confirmButtonText: '确定',
            //         cancelButtonText: '取消',
            //         type: 'warning'
            //     }).then(function () {
            //     axios.post("index.php?m=company&a=deleteQualification",{
            //         "id": data.idd
            //     }).then(function (res) {
            //         if (res.data.code == 200) {
            //             _this.$message({
            //                 message: _this.$lang('删除成功'),
            //                 type: 'success'
            //             })
            //             _this.getTabledata()
            //         }else{
            //             _this.$message({
            //                 message: _this.$lang(res.data.msg),
            //                 type: 'error'
            //             })
            //         }
            //     })
            //     }).catch(function () {
            //         _this.$message({
            //         type: 'info',
            //         message: '已取消删除'
            //         });
            //     });

            // }
        },
        // 所属我方公司下拉change
        ourCompanyChange: function (value) {
            console.log(value)
        },
        ourCompanyChangeForm:function(value){
            console.log(value)
        },
        // 附件
        handlePreview:function(file) {
            var fileList = this.dialogForm.qualificationAttachment2
            for(var i = 0; i<fileList.length; i++){
                if(fileList[i].original_name == file.name){
                    var a = document.createElement('a')
                    var event = new MouseEvent('click')
                    a.href = '/index.php?m=order_detail&a=download&file=' + fileList[i].save_name
                    a.dispatchEvent(event)
                }
            }

        },
        beforeRemove:function(file, fileList) {
            // return this.$confirm(this.$lang('确定移除') + file.name);
        },
        handleSuccess: function (res, file, fileList) {
            if(res.status){
                console.log('upload')
                this.dialogForm.qualificationAttachment2.push(file)
                this.dialogForm.qualificationAttachment.push(file)
                this.$message.success(this.$lang("文件上传成功"));
                // validatePass()
                if(this.dialogForm.qualificationAttachment2.length != 0 && document.querySelector('.attachmentWrap .el-form-item__error')){
                    document.querySelector('.attachmentWrap .el-form-item__error').style.display = 'none'
                }else if(this.dialogForm.qualificationAttachment2.length == 0 && document.querySelector('.attachmentWrap .el-form-item__error')){
                    document.querySelector('.attachmentWrap .el-form-item__error').style.display = 'block'
                }

            }else{
                this.$message.error(this.$lang("文件上传失败"));
            }

        },
        handleRemove:function(file, fileList) {
            var attachment = this.dialogForm.qualificationAttachment
            var attachment2 = this.dialogForm.qualificationAttachment2
            // console.log(fileList.length)
            // console.log(attachment.length)
            // console.log(attachment2.length)
            if(fileList.length == 0){
                this.dialogForm.qualificationAttachment = []
                this.dialogForm.qualificationAttachment2 = []
                if(this.dialogForm.qualificationAttachment2.length != 0 && document.querySelector('.attachmentWrap .el-form-item__error')){
                    document.querySelector('.attachmentWrap .el-form-item__error').style.display = 'none'
                }else if(this.dialogForm.qualificationAttachment2.length == 0 && document.querySelector('.attachmentWrap .el-form-item__error')){
                    document.querySelector('.attachmentWrap .el-form-item__error').style.display = 'block'
                }
            }else if(fileList.length != attachment.length){
                console.log('remove')
                for(var i=0;i<attachment.length;i++){
                    if(attachment[i].name == file.name || attachment2[i].original_name == file.name){
                        attachment.splice(i, 1);
                    }
                }
                for(var i=0;i<attachment2.length;i++){
                    if(attachment2[i].name == file.name || attachment2[i].original_name == file.name){
                        attachment2.splice(i, 1);
                    }
                }
                if(this.dialogForm.qualificationAttachment2.length != 0 && document.querySelector('.attachmentWrap .el-form-item__error')){
                    document.querySelector('.attachmentWrap .el-form-item__error').style.display = 'none'
                }else if(this.dialogForm.qualificationAttachment2.length == 0 && document.querySelector('.attachmentWrap .el-form-item__error')){
                    document.querySelector('.attachmentWrap .el-form-item__error').style.display = 'block'
                }
            }
        },
        handleBeforeUpload:function(file) {
            var attachment = this.dialogForm.qualificationAttachment2
            for(var i=0;i<attachment.length;i++){
                if(attachment[i].original_name == file.name || attachment[i].name == file.name){
                    this.$message.warning(this.$lang("文件已经上传"));
                    return false;
                }
            }
        },
        // 表单提交
        // submitButton:function (formName) {
        //     var _this = this;
        //         this.$refs[formName].validate( function(valid) {
        //             if (valid) {
        //                 if((_this.dialogForm.expiryDate == '' || _this.dialogForm.expiryDate == null) && _this.dialogForm.expiryChecked == false){
        //                     console.log('error submit!!');
        //                     _this.expiryDatePrompt = true
        //                     return false;
        //                 }else{
        //                     var fileList = _this.dialogForm.qualificationAttachment2
        //                     var fileListArr = []


        //                     for(var i = 0; i<fileList.length; i++){
        //                         if(fileList[i].name){
        //                             var attachment = {};
        //                             attachment.original_name = fileList[i].name
        //                             attachment.save_name = fileList[i].response.info[0].savename
        //                             fileListArr.push(attachment)
        //                         }else{
        //                             var attachment = {};
        //                             attachment.original_name = fileList[i].original_name
        //                             attachment.save_name = fileList[i].save_name
        //                             fileListArr.push(attachment)
        //                         }
        //                     }


        //                         if(_this.dialogForm.expiryChecked == true){
        //                             var expire_dateVal = ''
        //                             var is_long_time = '1'
        //                         }else{
        //                             // var expire_dateVal = _this.dialogForm.expiryDate
        //                             var expire_dateVal = _this.dateFormatCn(_this.dialogForm.expiryDate)
        //                             var is_long_time = '0'
        //                         }


        //                         var idd = _this.dialogForm.idd

        //                         var our_company_code = _this.dialogForm.ourCompany_cd
        //                         var name = _this.dialogForm.qualificationName
        //                         var attachment = fileListArr
        //                         // var issue_date = _this.dialogForm.issuingDay
        //                         var issue_date = _this.dateFormatCn(_this.dialogForm.issuingDay)
        //                         var expire_date = expire_dateVal
        //                         var is_long_time = is_long_time
        //                         // var renew_date = _this.dialogForm.renewalDate
        //                         var renew_date = _this.dateFormatCn(_this.dialogForm.renewalDate)
        //                         var issue_office = _this.dialogForm.issuingAuthority
        //                         var renew_address = _this.dialogForm.renewalLocation
        //                         var renew_material = _this.dialogForm.renewalMaterial
        //                         var department = _this.dialogForm.correspondingDepartment
        //                         var precautions = _this.dialogForm.precautions



        //                     if(_this.popstatus == 'add'){
        //                         axios.post("index.php?m=company&a=saveQualification",{
        //                             "our_company_code" : our_company_code,
        //                             "name" : name,
        //                             "attachment" : attachment,
        //                             "issue_date" : issue_date,
        //                             "expire_date" : expire_date,
        //                             "is_long_time" : is_long_time,
        //                             "renew_date" : renew_date,
        //                             "issue_office" : issue_office,
        //                             "renew_address" : renew_address,
        //                             "renew_material" : renew_material,
        //                             "department" : department,
        //                             "precautions" : precautions,
        //                         }).then(function (res) {
        //                             console.log(res)
        //                             if (res.data.code == 200) {
        //                                 _this.$message({
        //                                 message: _this.$lang('添加成功'),
        //                                 type: 'success'
        //                                 })
        //                                 _this.getTabledata()
        //                                 _this.dialogVisible = false;
        //                             }else{
        //                                 _this.$message({
        //                                     message: _this.$lang(res.data.msg),
        //                                     type: 'error'
        //                                 })
        //                             }
        //                         })
        //                     }else{
        //                         axios.post("index.php?m=company&a=saveQualification",{
        //                             "id":idd,
        //                             "our_company_code" : our_company_code,
        //                             "name" : name,
        //                             "attachment" : attachment,
        //                             "issue_date" : issue_date,
        //                             "expire_date" : expire_date,
        //                             "is_long_time" : is_long_time,
        //                             "renew_date" : renew_date,
        //                             "issue_office" : issue_office,
        //                             "renew_address" : renew_address,
        //                             "renew_material" : renew_material,
        //                             "department" : department,
        //                             "precautions" : precautions,
        //                         }).then(function (res) {
        //                             console.log(res)
        //                             if (res.data.code == 200) {
        //                               _this.$message({
        //                                 message: _this.$lang('编辑成功'),
        //                                 type: 'success'
        //                               })
        //                               _this.getTabledata()
        //                               _this.dialogVisible = false;
        //                             }else{
        //                               _this.$message({
        //                                   message: _this.$lang(res.data.msg),
        //                                   type: 'error'
        //                               })
        //                             }
        //                         })
        //                     }

 


        //                 }
        //             } else {
        //                 if((_this.dialogForm.expiryDate == '' || _this.dialogForm.expiryDate == null) && _this.dialogForm.expiryChecked == false){
        //                     _this.expiryDatePrompt = true
        //                     return false;
        //                 }
        //                 console.log('error submit!!');
        //                 return false;
        //             }
        //           });
        //     // }
        // },
        dateFormatToStandard:function(val){
            if(val == '' || val == null ){
                return('')
            }else{
                return(new Date(Date.parse(val.replace('年','-').replace('月','-').replace('日',''))))
            }
        },
        dateFormatCn:function(val){
            if(val == '' || val == null ){
                return('')
            }else{
                var myMonth = val.getMonth() + 1
                var myDate = val.getDate()
                if(myMonth<10){
                    myMonth = "0" + myMonth
                }
                if(myDate<10){
                    myDate = "0" + myDate
                }
                return(val.getFullYear()+'-'+myMonth+'-' +myDate)
            }

        },
        //翻页切换不同页面
        handleCurrentChange:function(val) {
            // console.log(val)
            this.page.this_page = val;
            this.getTabledata()
        },
        //切换每页展示的数目
        handleSizeChange:function (val) {
            this.page.this_page = 1;
            this.page.page_count = val;
            this.getTabledata()
        },
        closeDialog:function(){
            this.dialogForm.qualificationAttachment = []
        },

    },
    // computed: {
    //     expiryChecked() {
    //         return this.dialogForm.expiryChecked
    //     },
    //     expiryDate() {
    //         return this.dialogForm.expiryDate
    //     }
    // },
    // watch: {
    //     expiryChecked: {
    //         handler(newValue, oldValue) {
    //             if(newValue == true){
    //                 this.dialogForm.expiryDateStatus = true
    //                 this.dialogForm.expiryDate = null
    //             }else if(newValue == false){
    //                 this.dialogForm.expiryDateStatus = false
    //             }
    //         },
    //         deep: true
    //     },
    //     expiryDate: {
    //         handler(newValue, oldValue) {
    //             if(newValue == null && this.dialogForm.expiryChecked == false){
    //                 this.expiryDatePrompt = true
    //             }else{
    //                 this.expiryDatePrompt = false
    //             }
    //         },
    //         deep: true
    //     },
    //     // page: {
    //     //     handler: function handler(newValue, oldValue) {
    //     //         if(newValue.count == 0){
    //     //             this.paginationStatus = false
    //     //         }else{
    //     //             this.paginationStatus = true
    //     //         }
    //     //     },
    //     //     deep: true
    //     // },
    // },
})