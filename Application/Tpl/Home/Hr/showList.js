var vm = new Vue({
    el: "#showList",
    data: {
        labelPosition: 'left',
        labelModal: 'top',
        exportDialog: false,
        //formLabelWidth:50,
        form: {
            seWorkNum: '',
            seDept: '',
            seWorkplace: '',
            seStatus: '',
            seScNm: '',
            seLeader: '',
            seComPh: '',
            seJobCd: '',
            seTrName: '',
            seEmail: '',
            seCellPh: '',
            seJobType: '',
            seName: '',
            seMonk: '',
            seKey: '',
            sePage: 1,
            pageSize: 20
        },
        editForm: {
            //修改选项
            editOption: 'perJobDate',
            //修改内容
            perJobDate: '',
            deptName: '',
            deptId:'',
            emplGroup: '',
            jobCd: '',
            JobEnCd: '',
            workPlace: '',
            directLeader: '',
            departHead: '',
            dockingHr: '',
            rank: '',
            sex: '',
            perIsSmoking: '',
            perIsMarried: '',
            perPolitical: '',
            hosehold: '',
            perNational: '',
            status: ''
        },
        edit: {
            status: false,
            checkNum: 0,
            checkedAll: false
        },
        data: [],
        selData: [],
        batch: [
            {
                label: "入职时间",
                value: 'perJobDate'
            }, {
                label: "部门",
                value: 'deptName'
            }, {
                label: "组别",
                value: 'emplGroup'
            }, {
                label: "中文职位",
                value: 'jobCd'
            }, {
                label: "工作地点",
                value: 'workPlace'
            }, {
                label: "直接领导",
                value: 'directLeader'
            }, {
                label: "部门总监",
                value: 'departHead'
            }, {
                label: "对接HR",
                value: 'dockingHr'
            }, {
                label: "职级",
                value: 'rank'
            }, {
                label: "性别",
                value: 'sex'
            }, {
                label: "是否吸烟",
                value: 'perIsSmoking'
            }, {
                label: "婚姻状况",
                value: 'perIsMarried'
            }, {
                label: "政治面貌",
                value: 'perPolitical'
            }, {
                label: "户口性质",
                value: 'hosehold'
            }, {
                label: "民族",
                value: 'perNational'
            }, {
                label: "状态",
                value: 'status'
            }
        ],
        //导出选中例子
        exportCheckList: [],
        //导出选项
        exportOption: [
            {
                label: "工号",
                value: 'workNum'
            }, {
                label: "入职时间",
                value: 'perJobDate'
            }, {
                label: "司龄",
                value: 'companyAge'
            }, {
                label: "真名",
                value: 'empNm'
            }, {
                label: "花名",
                value: 'EmpScNm'
            }, {
                label: "ERP账号",
                value: 'erpAct'
            }, {
                label: "中文职位",
                value: 'jobCd'
            }, {
                label: "英文职位",
                value: 'JobEnCd'
            }, {
                label: "部门",
                value: 'deptName'
            }, {
                label: "组别",
                value: 'deptGroup'
            }, {
                label: "工作地点",
                value: 'workPlace'
            }, {
                label: "身份证号码",
                value: 'perCartId'
            }, {
                label: "出生日期",
                value: 'perBirthDate'
            }, {
                label: "年龄",
                value: 'age'
            }, {
                label: "性别",
                value: 'sex'
            }, {
                label: "户籍",
                value: 'perResident'
            }, {
                label: "婚姻状态",
                value: 'perIsMarried'
            }, {
                label: "联系方式",
                value: 'prePhone'
            }, {
                label: "是否吸烟",
                value: 'perIsSmoking'
            }, {
                label: "毕业学院",
                value: 'graSCHOOL'
            }, {
                label: "学历",
                value: 'eduBACK'
            }, {
                label: "专业",
                value: 'MAJORS'
            }, {
                label: "毕业时间",
                value: 'endTIME'
            }, {
                label: "毕业证书编号",
                value: 'biyezhengshubianhao'
            }, {
                label: "前一家公司",
                value: 'prevCompany'
            }, {
                label: "再前一家公司",
                value: 'prevPrevCompany'
            }, {
                label: "户籍地址",
                value: 'detail'
            }, {
                label: "现住址",
                value: 'presentAddress'
            }, {
                label: "姓名",
                value: 'fullname'
            }, {
                label: "关系",
                value: 'relationship'
            }, {
                label: "联系方式",
                value: 'contactInformation'
            }, {
                label: "银行",
                value: 'bank'
            }, {
                label: "卡号",
                value: 'cardNumber'
            }, {
                label: "公积金账号",
                value: 'fundAccount'
            }, {
                label: "政治面貌",
                value: 'perPolitical'
            }, {
                label: "私人邮箱",
                value: 'email'
            }, {
                label: "花名邮箱",
                value: 'scEmail'
            }, {
                label: "状态",
                value: 'status'
            }, {
                label: "离职时间",
                value: 'depJobDate'
            }, {
                label: "离职编号",
                value: 'depJobNum'


            // }, {
            //     label: "直接领导",
            //     value: 'directLeader'
            // }, {
            //     label: "部门总监",
            //     value: 'departHead'
            // }, {
            //     label: "对接HR",
            //     value: 'dockingHr'
            // }, {
            //     label: "职级",
            //     value: 'rank'
            // }, {
            //     label: "ERP密码",
            //     value: 'erpPwd'
            // }, {
            //     label: "分机号",
            //     value: 'offTel'
            // }, {
            //     label: "职位类别",
            //     value: 'jobTypeCd'
            // }, {
            //     label: "籍贯",
            //     value: 'perAddress'
            // }, {
            //     label: "子女数",
            //     value: 'childNum'
            // }, {
            //     label: "户口性质",
            //     value: 'hosehold'
            // }, {
            //     label: "民族",
            //     value: 'perNational'
            // }, {
            //     label: "微信",
            //     value: 'weChat'
            // }, {
            //     label: "QQ",
            //     value: 'qqAccount'
            // }, {
            //     label: "爱好及特长",
            //     value: 'hobbySpa'
            }],
        departmentlist:[],
        importLogList:{data:[]},
        importLogDialogIsVisible:false
    },
    created: function () {
        this.form.seStatus ='在职'
        this.search();
        this.getSelData();
        this.getDeptlist();
    },
    methods: {
        search: function () {
        
            axios.post('/index.php?m=Api&a=search', {params: JSON.stringify(this.form)})
                .then(function (res) {
                    if (res.data.code === 200) {
                        vm.data = res.data.data;
                        for (var i = vm.data.length; i--;) {
                            Vue.set(vm.data[i], 'checked', false);
                        }
                    } else {
                        vm.$message({
                            message: res.data.data,
                            type: 'warning'
                        });
                    }

                });
        },
        reset: function () {
            this.form = {
                seWorkNum: '',
                seDept: '',
                seWorkplace: '',
                seStatus: '',
                seScNm: '',
                seLeader: '',
                seComPh: '',
                seJobCd: '',
                seTrName: '',
                seEmail: '',
                seCellPh: '',
                seJobType: '',
                seName: '',
                seMonk: '',
                seKey: '',
                sePage: 1,
                pageSize: 20
            }
            this.search();
        },
        //sort
        jsSortList: function(event){
            //event.currentTarget
            var tNum = event.currentTarget.getAttribute('data-sort');
            // default sort
            this.form.ordertype=tNum;
            this.form.orderinfo='asc';
            // cur span
            var is_asc_dsc = '';
            var tmpObj = baseNormal.getElementsByClassName(event.currentTarget,'*','arrow');
            var count=tmpObj.length;
            if(count!=0){
                for(var i=0;i<count;i++){
                    var obj = tmpObj[i];
                    if( baseNormal.hasClass(obj, 'asc') ){
                        is_asc_dsc = 'asc';
                    }
                }
            }
            // all
            var tmpObjAll = baseNormal.getElementsByClassName(document,'*','arrow');
            var countAll=tmpObjAll.length;
            if(countAll!=0){
                for(var i=0;i<countAll;i++){
                    var obj = tmpObjAll[i];
                    baseNormal.removeClass(obj,'asc');
                    baseNormal.removeClass(obj,'dsc');
                }
            }
            // cur span again
            if(count!=0){
                for(var i=0;i<count;i++){
                    var obj = tmpObj[i];
                    if(is_asc_dsc=='asc'){
                        baseNormal.addClass(obj,'dsc');
                    }else{
                        baseNormal.addClass(obj,'asc');
                    }
                }
                if(is_asc_dsc=='asc'){
                    this.form.orderinfo='dsc';
                }
            }
            this.search();
        },
        //获取下拉数据
        getSelData: function (param, option) {
            axios.post('/index.php?m=Api&a=choice', param)
                .then(function (res) {
                    vm.selData = res.data[0];
                    vm.selData.sex = [{id: 0, name: '男'}, {id: 1, name: '女'}];
                    if (option === 'jobEn') {
                        vm.editForm.JobEnCd = vm.selData.joben.ETC;
                    }
                });
        },
        // 获取部门数据
        getDeptlist: function() {
            var _this = this;
            axios.post('/index.php?m=api&a=hr_dept_list', {})
                .then(function(res) {
                    _this.departmentlist = res.data.data;
                })
        },
        onClosedByBatch(done){
            console.log("关闭执行了");
             Object.keys(this.editForm).forEach((key)=>{
                 this.editForm[key]=null;
             });
            this.editForm.editOption='perJobDate';
            this.edit.status=false;
            done();
        },
        // 点击取消按钮关闭
        onClosedBatchDiglog(){
            this.edit.status = false;
            Object.keys(this.editForm).forEach((key)=>{
                this.editForm[key]=null;
            });
            this.editForm.editOption='perJobDate';
            console.log("触发了没",this.editForm);
        },
        handleSizeChange: function (size) {
            this.form.pageSize = size;
        },
        handleCurrentChange: function (currentPage) {
            this.search();
        },
        checkJob: function (value) {
            var param = {jobzh: value};
            this.getSelData(param, 'jobEn');
        },
        //批量操作
        batchEdit: function () {
            this.edit.checkNum = 0;
            var flag = true,
                i = this.data.length;
            for (i; i--;) {
                if (this.data[i].checked) {
                    flag = false;
                    this.edit.checkNum++;
                }
            }
            if (flag) {
                this.$message({
                    message: '请至少勾选一个人员',
                    type: 'warning'
                });
            } else {
                this.edit.status = true;
            }
        },
        checkAll: function () {
            var i = this.data.length;
            for (i; i--;) {
                this.data[i].checked = this.edit.checkedAll;
            }
        },
        //批量编辑
        saveBatch: function () {
            if (this.editForm[this.editForm.editOption]) {
                var i = this.data.length;
                for (i; i--;) {
                    if (this.data[i].checked) {
                        this.data[i][this.editForm.editOption] = this.editForm[this.editForm.editOption];
                        //计算司龄
                        if (this.editForm.editOption === 'perJobDate') {
                            var startDate = new Date(this.editForm.perJobDate),
                                endDate = this.data[i].DEP_JOB_DATE ? new Date() : new Date();
                            this.data[i].COMPANY_AGE = Math.floor((endDate - startDate) / (60 * 60 * 24 * 30 * 1000));
                        }
                        //匹配英文职位
                        if (vm.editForm.editOption === 'jobCd') {
                            (function (i) {
                                axios.post('/index.php?m=Api&a=choice', {jobzh: vm.editForm.jobCd})
                                    .then(function (res) {
                                        vm.data[i].JOB_EN_CD = res.data[0].joben.ETC;
                                        var param = JSON.stringify(vm.data[i]);
                                        vm.callInter(param);
                                    });
                            })(i);
                            
                        }else{
                            var param = JSON.stringify(vm.data[i]);
                            vm.callInter(param);
                        }
                        
                    }
                }
            } else {
                vm.$message({
                    message: '修改内容不能为空',
                    type: 'warning'
                });
            }
        },
        //批量保存
        callInter:function (params) {
            var _this = this;
            var param = JSON.parse(params);
            Object.keys(this.departmentlist).forEach((key)=>{
                if(this.departmentlist[key].DEPT_NM=== param.deptName){
                    param.deptId = this.departmentlist[key].ID;

                }
            })
            console.log("当前参数",param);
            axios.post('/index.php?m=Api&a=batchChange', param)

                .then(function (res) {
                    console.log(res);
                    if (res.data.code === 200) {
                        vm.edit.status = false;
                        Object.keys(_this.editForm).forEach((key)=>{
                            _this.editForm[key]=null;
                        });
                        _this.editForm.editOption='perJobDate';
                        vm.$message({
                            message: '修改成功',
                            type: 'success'
                        });
                        vm.search();

                    } else {
                        vm.$message({
                            message: (typeof(res.data.data)=='undefined')?(JSON.stringify(res.data)):(JSON.stringify(res.data.data)),
                            type: 'error'
                        });
                        vm.search();
                    }
                })
        },
        //导入模板
        importExcel: function (res, file, fileList) {
            console.log("导入信息",res.data);
            if (res.code === 200) {
                vm.$message({
                    message: res.data,
                    type: 'success'
                });
                this.search();
            } else {
                vm.$message({
                    message: (typeof(res.data)=='undefined')?(JSON.stringify(res)):(res.data),
                    type: 'error'
                });
                let log={};
                Object.keys(res.data).forEach((key)=>{
                    log[key] = res.data[key];
                    this.importLogList.data.push(log);
                    log= {};
                })
                this.importLogDialogIsVisible = true;
            }
        },
        onClosedByLog(){
            this.importLogList.data=[]
        },
        onClosed(){
            this.importLogDialogIsVisible = false;
            this.importLogList.data=[];
        },
        //导出模板
        exportEmp: function () {
            var url = '/index.php?m=Api&a=export_emp&EMPL_ID=',
                flag = true,
                i = this.data.length;
            for (i; i--;) {
                if (this.data[i].checked) {
                    flag = false;
                    url += this.data[i].EMPL_ID + ',';
                }
            }
            if (flag) {
                this.$message({
                    message: '请至少勾选一个人员',
                    type: 'warning'
                });
            } else {
                this.exportDialog = true;
                // var param = url.substring(0, url.length - 1);
                // var form = document.createElement('form');
                // form.setAttribute('style', 'display:none');
                // form.setAttribute('target', '');
                // form.setAttribute('method', 'post');
                // form.setAttribute('action', param);
                // document.body.appendChild(form);
                // form.submit();
                // form.remove();
            }
        },
        //  自定义导出
        /*  confirmExport:function () {
         if(!this.exportCheckList.length){
         this.$confirm('确认删除此条信息？', '提示',{type:'warning'})
         .then(function () {

         })
         }
         /!*var param = url.substring(0,url.length - 1);
         var form = document.createElement('form');
         form.setAttribute('style','display:none');
         form.setAttribute('target','');
         form.setAttribute('method','post');
         form.setAttribute('action',param);
         document.body.appendChild(form);
         form.submit();
         form.remove();*!/
         },*/
        //  in pop - 自定义导出
        confirmExport:function () {
            if(!this.exportCheckList.length){
                this.$message({
                    message: '请至少勾选一个',
                    type: 'warning'
                });
                return;
            }
            // ids
            var idsLen = this.data.length;
            var ids = [];
            for(var i=0;i<idsLen;i++){
                if (this.data[i].checked) {
                    ids.push(this.data[i].EMPL_ID);
                }
            }
            // make info
            var url = '/index.php?m=Api&a=export_emp';
            var form1 = document.createElement('form');
            form1.setAttribute('style','display:none');
            form1.setAttribute('target','');
            form1.setAttribute('method','post');
            form1.setAttribute('action',url);
            // 创建一个输入 
            var input1 = document.createElement("input");
            input1.type = "text"; 
            input1.name = "need_info"; 
            input1.value = JSON.stringify(this.exportCheckList);
            form1.appendChild(input1);
            // 创建一个输入 
            var input2 = document.createElement("input");
            input2.type = "text"; 
            input2.name = "EMPL_ID"; 
            input2.value = ids.join(',');
            form1.appendChild(input2);
            document.body.appendChild(form1);
            form1.submit();
            form1.remove();
        },
        downloadTemp: function () {
            // 直接跳转
            var url = '/index.php?m=Api&a=download';
            location.href=url;
        },
        //查看人员详细信息
        seeDetail: function (id) {
            // 直接跳转
            var url = "/index.php?m=Hr&a=editPerson&id=" + id;
            location.href=url;
        },
        //删除人员信息
        delInfo: function (id,erpAct) {
            this.$confirm('确认删除此条信息？', '提示', {type: 'warning'})
                .then(function () {
                    axios.post('/index.php?m=Api&a=emplDelele', {emplId: id,erpAct:erpAct})
                        .then(function (res) {
                            if (res.data.code === 200) {
                                vm.$message({
                                    message: '删除成功',
                                    type: 'success'
                                });
                                vm.search();
                            } else {
                                vm.$message({
                                    message: '删除失败'+JSON.stringify(res.data),
                                    type: 'warning'
                                });
                                vm.search();
                            }
                        })
                });
        }
    }
});
