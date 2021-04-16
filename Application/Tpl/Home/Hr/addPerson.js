var Vm = new Vue({
    el: '#hrAddEdit',
    data: {
        //标题文字方向
        loading: false,
        title: '',
        labelPosition: 'left',
        cropStatus: false,
        //控制展示页面
        control: {
            admin: '',
            staff: '',
            id: ''
        },
        //编辑参数
        edit: {
            show: false,
        },
        //查看时的url
        url: {
            perCardPic: '',
            resume: '',
            graCert: '',
            degCert: '',
            learnProve: ''
        },
        //切换菜单选项
        menu: {
            personInfo: true
        },
        //第一页定义字段
        form: {
            //hr 模块
            Pic: '',
            picName: '',
            workNum: '',
            EmpScNm: '',
            perJobDate: '',
            deptName: '',
            deptGroup: '',
            companyAge: '',
            jobCd: '',
            rank: '',
            JobEnCd: '',
            workPlace: '',
            directLeader: '',
            departHead: '',
            dockingHr: '',
            isFirstCom: '',
            status: '',
            depJobDate: '',
            depJobNum: '',
            erpAct: '',
            erpPwd: 'gshopper@123',
            department: [{
                ID1: '',
                PERCENT: '',
            }],
            //个人信息模块
            empNm: '',
            prePhone: '',
            offTel: '',
            jobTypeCd: '',
            is_filed: '',
            perCartId: '',
            sex: '',
            perIsSmoking: '',
            perBirthDate: '',
            houAdderss: {
                proh: '',
                cityH: '',
                areaH: '',
                detailH: ''
            },
            livingAddress: {
                provL: '',
                cityL: '',
                areaL: '',
                detailL: ''
            },
            age: '',
            perAddress: '',
            perResident: '',
            perIsMarried: '',
            childNum: '',
            childBoyNum: '',
            childGirlNum: '',
            perPolitical: '',
            hosehold: '',
            fundAccount: '',
            perNational: '',
            scEmail: '',
            email: '',
            weChat: '',
            qqAccount: '',
            firstLan: '',
            firstLanLevel: '',
            secondLan: '',
            secondLanLevel: '',
            hobbySpa: '',
            //紧急联系人
            concatRel: '',
            concatName: '',
            concatWay: '',
            //教育经历
            eduExp: [{
                eduStartTime: '',
                eduEndTime: '',
                schoolName: '',
                eduMajors: '',
                eduDegNat: '',
                isDegree: '',
                certiNo: '',
                validateRes: ''
            }],
            //工作经历
            workExp: [{
                wordStartTime: '',
                wordEndTime: '',
                companyName: '',
                posi: '',
                depReason: ''
            }],
            //家庭情况
            home: [{
                homeRes: '',
                homeName: '',
                homeAge: '',
                occupa: '',
                workUnits: ''
            }],
            //培训经验
            training: [{
                trainingName: '',
                trainingStartTime: '',
                trainingEndTime: '',
                trainingIns: '',
                trainingDes: ''
            }],
            //资格证书
            certificate: [{
                certiName: '',
                certifiTime: '',
                certifiunit: ''
            }],
            //银行卡信息
            bankCard: [{
                bankAct: '',
                bankName: '',
                swiftCood: '',
                bankDeposit: '',
                BankEndeposit: ''
            }]
        },
        //修改密码弹窗
        changePwdVisible: false,
        //重置密码弹窗
        resetPwdVisible: false,
        sendResetEmailStatus: false,
        //输入密码
        pwdForm: {
            erpPwd: '',
            confirmPwd: '',
        },
        // 第二页字段定义
        form2: {
            //合同信息
            contract: [{
                conCompany: '',
                natEmploy: '',
                trialEndTime: '',
                conStartTime: '',
                conEndTime: '',
                conStatus: '',
                isdisabled: false,
            }],
            reward: [{
                rewardName: '',
                rewardContent: ''
            }],
            promo: [{
                promoType: '',
                promoTime: '',
                promoContent: ''
            }],
            paperMiss: [{
                paperMissTime: '',
                paperMissCon: ''
            }],
            interArr: [{
                interType: '',
                interTime: '',
                interObj: '',
                interPerson: '',
                interContent: '',
                afterCase: ''
            }],
            hrRecord: [{
                reContent: '',
                reTime: '',
            }],
        },
        //下拉选项数据
        departmentlist: {},
        selData: {},
        deptNameList: [],
        deptGroupList: [],
        //个人信息数据
        infoData: {},
        address: {
            houProvinceData: [],
            houCityData: [],
            houAreaData: [],
            livingProvinceData: [],
            livingCityData: [],
            livingAreaData: []
        },

        //验证是否为空prop
        rules: {},
        rulesHr: {
            workNum: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            EmpScNm: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perJobDate: [
                { type: 'date', required: true, message: ' ', trigger: 'blur' }
            ],
            deptName: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            deptGroup: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            companyAge: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            jobCd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            JobEnCd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            workPlace: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            directLeader: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            departHead: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            dockingHr: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            rank: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            erpAct: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            erpPwd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            status: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            scEmail: [
                { required: true, message: ' ', trigger: 'blur' }
            ]
        },
        rulesPer: {
            workNum: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            EmpScNm: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perJobDate: [
                { type: 'date', required: true, message: ' ', trigger: 'blur' }
            ],
            deptName: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            deptGroup: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            companyAge: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            jobCd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            JobEnCd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            workPlace: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            directLeader: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            departHead: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            dockingHr: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            rank: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            erpAct: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            erpPwd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            status: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            empNm: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            prePhone: [
                { required: true, message: ' ', trigger: 'blur' }
            ],

            jobTypeCd: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perCartId: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            sex: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perIsSmoking: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perBirthDate: [
                { type: 'date', required: true, message: ' ', trigger: 'change' }
            ],
            age: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perAddress: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perResident: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perIsMarried: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            childNum: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            perPolitical: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            hosehold: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            perNational: [
                { required: true, message: ' ', trigger: 'change' }
            ],
            fundAccount: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            email: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            weChat: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            qqAccount: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            houAdderss: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            livingAddress: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            hobbySpa: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            concatRel: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            concatName: [
                { required: true, message: ' ', trigger: 'blur' }
            ],
            concatWay: [
                { required: true, message: ' ', trigger: 'blur' }
            ]
        },
        storeTransfer:false,
        storeTransferPerson:'',
        in_handover_num:'',
        no_handover_num:'',
        storeTransferPersonList:[],
        idd:''
    },
    created: function() {

        this.sendResetEmailStatus = false;
        var param = $("#ctrl").val(),
            staff = param.indexOf('card') > -1,
            admin = param.indexOf('addPerson') > -1,
            getId = param.split("=").reverse()[0],
            id = isNaN(Number(getId)) ? null : Number(getId);
        var resid = $('resctrl').val();
        
        if (!admin) admin = param.indexOf('editPerson') > -1;
        //获取个人信息
        if (staff || id) {
            this.card(id);
            this.idd = id
        }
        this.getSelData();
        this.getDeptlist();
        this.getUserData(id);


        if (admin && !id) {
            this.rules = this.rulesHr;
        }

        this.control.admin = admin;
        this.control.staff = staff;
        this.control.id = id;

        if (staff) {
            this.title = "我的名片";
        } else if (admin && id) {
            this.title = "员工名片";
        } else if (admin) {
            this.title = "新建人员";
        }

    },
    methods: {
        //获取职位列表
        getDeptlist: function() {
            var _this = this;
            axios.post('/index.php?m=api&a=hr_dept_list', {})
                .then(function(res) {
                    _this.departmentlist = res.data.data;
                })
        },
        // 获取店铺用户
        getUserData:function(){
            var _this = this;
            axios.post('/index.php?m=api&a=store_getHrUserData', {})
            .then(function(res) {
                console.log(res);
                if (res.data.code == 200) {
                    _this.storeTransferPersonList = res.data.data
                }
            })
        },
        //获取个人信息
        card: function(id) {

            axios.post('/index.php?m=Api&a=card', { emplID: id })
                .then(function(res) {
                    if (res.data.code === 200) {
                        var data = res.data.data;
                        //个人信息
                        Vm.form = data.cardInfo;
                        //教育经历
                        Vue.set(Vm.form, 'eduExp', data.eduInfo);
                        //所属部门
                        if (data.department[0] && data.department) {
                            Vue.set(Vm.form, 'department', data.department || []);
                        } else {
                            Vue.set(Vm.form, 'department', [{ ID1: '', PERCENT: '', }]);
                        }

                        //工作经历
                        Vue.set(Vm.form, 'workExp', data.workExp);

                        //家庭情况
                        Vue.set(Vm.form, 'home', data.home);

                        //培训经验
                        Vue.set(Vm.form, 'training', data.training);

                        //资格证书
                        Vue.set(Vm.form, 'certificate', data.certificate);

                        //银行卡信息
                        Vue.set(Vm.form, 'bankCard', data.bankCard);

                        //紧急联系人
                        for (var key in data.friInfo) {
                            Vue.set(Vm.form, key, data.friInfo[key])
                        }

                        //跟踪信息
                        if (data.conInfo.length) {
                            Vm.form2.contract = [];
                            var i = 0;
                            contract = data.conInfo;

                            for (i; i < contract.length; i++) {
                                contract[i].isdisabled = false;
                                if (contract[i].trialEndTime == '') {
                                    contract[i].isdisabled = true;
                                }

                                Vm.form2.contract.push(contract[i]);
                            }

                        }
                        if (data.reward.length) {
                            Vm.form2.reward = res.data.data.reward;
                        }
                        if (data.promo.length) {
                            Vm.form2.promo = res.data.data.promo;
                        }
                        if (data.paper.length) {
                            Vm.form2.paperMiss = res.data.data.paper;
                        }
                        if (data.inter.length) {
                            Vm.form2.interArr = res.data.data.inter;
                        }
                        if (data.hrRecord.length) {
                            Vm.form2.hrRecord = res.data.data.hrRecord;
                        }
                        if (Vm.control.staff && !Vm.form.empNm) {
                            Vm.rules = Vm.rulesPer
                        }
                    }
                })
        },
        chooseNo: function(isdisabled, key) {
            for (k in this.form2.contract) {
                if (k == key && isdisabled) {
                    this.form2.contract[k].trialEndTime = '';
                }
            }
        },
        //编辑按钮
        editContent: function() {
            var _this = this
            axios.post('/index.php?m=api&a=store_examineShop', {
                "ERP_ACT":_this.form.erpAct
            })
            .then(function(res) {
                if (res.data.code == 200) {
                    _this.in_handover_num = res.data.data.in_handover_num
                    _this.no_handover_num = res.data.data.no_handover_num
                }
            })
            _this.edit.show = !_this.edit.show;
        },
        //获取下拉数据
        getSelData: function(param, option) {
            axios.post('/index.php?m=Api&a=choice', param)
                .then(function(res) {
                    Vm.selData = res.data[0];
                });
            resid = $('#resctrl').val();
            if (resid) {
                axios.post('/index.php?m=Api&a=getResData', { resid: resid })
                    .then(function(res) {
                        var data = res.data.data
                        for (var k in data) {
                            Vue.set(Vm.form, k, data[k])
                        }
                    })
            }
        },
        // dept
        remoteDeptNameList: function(query) {
            var obj_this = this;
            if (query.length >= 0) {
                this.loading = true;
                setTimeout(function() {
                    axios.post("/index.php?m=api&a=hr_dept_search_department&test=0&searchdata=" + query)
                        .then(function(res) {
                            obj_this.loading = false;
                            obj_this.deptNameList = res.data.data;
                            //relate
                            var parent_id = '';
                            var name = obj_this.form.deptGroup;
                            if (obj_this.form.deptGroup) {
                                for (var key in obj_this.deptGroupList) {
                                    if (name == obj_this.deptGroupList[key].ID) {
                                        parent_id = obj_this.deptGroupList[key].PAR_DEPT_ID
                                    }
                                }
                                for (var key in obj_this.deptNameList) {
                                    if (parent_id == obj_this.deptNameList[key].ID) {
                                        obj_this.form.deptName = obj_this.deptNameList[key].ID;
                                    }
                                }
                            }
                        })
                }, 300)
            } else {
                this.deptNameList = [];
            }
        },
        // dept group
        remoteDeptGroupList: function(query) {
            var obj_this = this;
            if (query.length >= 0) {
                this.loading = true;
                setTimeout(function() {
                    axios.post("/index.php?m=api&a=hr_dept_search_department&searchdata=" + query)
                        .then(function(res) {
                            obj_this.loading = false;
                            obj_this.deptGroupList = res.data.data;
                        })
                }, 300)
            } else {
                this.deptGroupList = [];
            }
        },
        // dept onchange relate
        relateDept: function(name) {
            this.remoteDeptNameList('');
        },
        //保存第一页
        saveForm: function() {
            console.log(this.form);
            
            if(this.form.status == '离职' &&  (this.in_handover_num != 0 || this.no_handover_num != 0)){
                this.storeTransfer = true
            }else{
                var interfaceName = this.form.emplid ? 'editCard' : 'AddPersonnel';

                axios.post('/index.php?m=Api&a=' + interfaceName, { params: JSON.stringify(this.form) })
                .then(function(res) {
                    if (res.data.code === 200) {
                        Vm.$message({
                            message: Vm.form.emplid ? res.data.data : res.data.data.res,
                            type: 'success'
                        });
                        //保存生成的ID 带到第二页
                        if (interfaceName === 'editCard') {
                            Vm.card(Vm.form.emplid);
                            Vm.edit.show = false;
                            Vm.edit.text = '编辑';
                        } else {
                            var route = document.createElement("a");
                            route.setAttribute("style", "display: none");
                            route.setAttribute("onclick", "opennewtab(this,'人员列表')");
                            route.setAttribute("_href", '/index.php?m=hr&a=showList');
                            route.click();
                        }
                    } else {
                        if(res.data.data){
                            Vm.$message({
                                message: res.data.data,
                                type: 'error'
                            });
                        }else{
                            Vm.$message({
                                message: '保存失败',
                                type: 'error'
                            });
                        }

                    }
                })
            }
            

        },
        //取消保存
        cancel: function() {
            Vm.edit.show = false;
            Vm.cropStatus = false;
            if (this.form.emplid) {
                Vm.card(this.form.emplid)
            } else {
                var form = document.getElementsByTagName("form");
                for (var i = form.length; i--;) {
                    form[i].reset();
                }
            }
        },

        //保存第二页
        saveForm2: function() {
            var interfaceName1 = this.form.emplid ? 'changeTrack' : 'addTrack';
            this.form2.emplid = this.form.emplid;
            axios.post('/index.php?m=Api&a=' + interfaceName1, { params: JSON.stringify(this.form2) })
                .then(function(res) {
                    if (res.data.code === 200) {
                        Vm.$message({
                            message: res.data.data,
                            type: 'success'
                        });
                        if (interfaceName1 === 'addTrack') {
                            var route = document.createElement("a");
                            route.setAttribute("style", "display: none");
                            route.setAttribute("onclick", "opennewtab(this,'人员列表')");
                            route.setAttribute("_href", '/index.php?m=hr&a=showList');
                            route.click();
                        } else {
                            Vm.edit.show = false;
                            Vm.edit.text = '编辑';
                            Vm.card(Vm.form.emplid);
                        }
                    } else {
                        Vm.$message({
                            message: res.data.data,
                            type: 'error'
                        });
                    }
                });
        },
        // handleChange: async function(file){
        handleChange: function(file) {
            // console.log(file);
            
            // Vm.cropStatus = true;
            // this.form.Pic = file.url;
            
            
            // await setTimeout(function() {
            // setTimeout(function() {
                // var avatar = $('#avatar');
                // $('#avatar').cropper({
                //     aspectRatio: 2 / 3,
                //     minCropBoxWidth: 150,
                //     minCropBoxHeight: 260,
                //     autoCropArea: 0,
                //     cropBoxResizable: false,
                //     crop: function() {
                //         if (Vm.form.Pic) {
                //             var avatar = $('#avatar');
                //             avatar.cropper('getCroppedCanvas').toBlob(function(blob) {
                //                 var formData = new FormData();
                //                 formData.append('Pic', blob);
                //                 Vm.cropStatus = false;
                //                 Vm.form.Pic = window.URL.createObjectURL(blob);
                //                 axios.post('/index.php?m=Api&a=hrUpload', formData).then(function(res) {
                //                     if (res.data.code === 200) {
                //                         Vm.$message({
                //                             message: '上传成功',
                //                             type: 'success'
                //                         });
                //                         Vm.form.picName = res.data.data.savename;
                //                     } else {
                //                         //清空文件列表
                //                         Vm.$message({
                //                             message: res.data.data.res,
                //                             type: 'error'
                //                         });
                //                         Vm.form.Pic = '';
                //                         Vm.$refs[res.data.data.name].clearFiles();
                //                     }
                //                 })
                //             })
                //         } else {
                //             Vm.$message({
                //                 message: '请先选择图片',
                //                 type: 'error'
                //             });
                //         }
                //     },
                // });
            // }, 100)


            // setTimeout(function() {
            //     var avatar = $('#avatar');
            //     avatar.cropper({
            //         aspectRatio: 2 / 3,
            //         minCropBoxWidth: 150,
            //         minCropBoxHeight: 260,
            //         autoCropArea: 0,
            //         cropBoxResizable: false
            //     });
            // }, 100)
        },
        handleAvatarSuccess:function(res, file){
            // Vm.cropStatus = true;
            this.form.Pic = file.url;
            if(res.code == 200){
                Vm.form.picName = res.data.savename
            }
            
        },
        //上传文件成功
        uploadFileFun: function(res, file, fileList) {
            //只留一个文件
            if (fileList.length > 1) {
                fileList.splice(0, 1);
            }
            if (res.code === 200) {
                Vm.$message({
                    message: '上传成功',
                    type: 'success'
                });
                //保存时发送的名称
                this.url[res.data.name] = file.url;
                this.form[res.data.name] = res.data.savename;
            } else {
                //清空文件列表
                this.$refs[res.data.name].clearFiles();
                Vm.$message({
                    message: res.data.res,
                    type: 'error'
                });
            }
        },
        crop: function() {
            console.log(111);
            
            if (Vm.form.Pic) {
                var avatar = $('#avatar');
                avatar.cropper('getCroppedCanvas').toBlob(function(blob) {
                    var formData = new FormData();
                    formData.append('Pic', blob);
                    Vm.cropStatus = false;
                    Vm.form.Pic = window.URL.createObjectURL(blob);
                    axios.post('/index.php?m=Api&a=hrUpload', formData).then(function(res) {
                        if (res.data.code === 200) {
                            Vm.$message({
                                message: '上传成功',
                                type: 'success'
                            });
                            Vm.form.picName = res.data.data.savename;
                        } else {
                            //清空文件列表
                            Vm.$message({
                                message: res.data.data.res,
                                type: 'error'
                            });
                            Vm.form.Pic = '';
                            Vm.$refs[res.data.data.name].clearFiles();
                        }
                    })
                })
            } else {
                Vm.$message({
                    message: '请先选择图片',
                    type: 'error'
                });
            }
        },
        //选择部门
        pickDep: function(item) {
            this.$set(item, 'PAR_DEPT_NM', this.departmentlist[item.ID1].PAR_DEPT_NM);
            this.$set(item, 'leader_dept', this.departmentlist[item.ID1].leader_dept);
            this.$set(item, 'TYPE_NM', this.departmentlist[item.ID1].TYPE_NM);
            this.$set(item, 'leader_direct', this.departmentlist[item.ID1].leader_direct);
        },
        addDepart: function() {
            this.form.department.push({ ID1: '', PERCENT: '' });
        },
        minDepart: function(key) {
            this.form.department.splice(key, 1);
        },
        reset: function() {
            $('#avatar').cropper('reset');
        },
        //查看预览功能
        preview: function(param) {
            if (this.url[param]) {
                window.open(this.url[param], '查看文件')
            } else {
                Vm.$message({
                    title: '警告',
                    message: '未上传附件',
                    type: 'warning',
                    duration: 1500
                });
            }
        },
        //查看附件
        previewAnnex: function(key, data) {
            if (data[key]) {
                var url = "/index.php?m=Api&a=checkAttach&" + key + "=" + data[key];
                var aEle = document.createElement("a");
                aEle.setAttribute("style", "display: none");
                aEle.setAttribute("target", "_blank");
                aEle.setAttribute("href", url);
                aEle.click();
            } else {
                Vm.$message({
                    title: '警告',
                    message: '未找到附件',
                    type: 'warning',
                    duration: 1500
                });
            }
        },


        //添加条目信息
        addEntry: function(key) {
            var item = {};
            switch (key) {
                case 'eduExp':
                    item = {
                        eduStartTime: '',
                        eduEndTime: '',
                        schoolName: '',
                        eduMajors: '',
                        eduDegNat: '',
                        isDegree: '',
                        certiNo: ''
                    };
                    break;
                case 'workExp':
                    item = {
                        wordStartTime: '',
                        wordEndTime: '',
                        companyName: '',
                        posi: '',
                        depReason: ''
                    };
                    break;
                case 'home':
                    item = {
                        homeRes: '',
                        homeName: '',
                        homeAge: '',
                        occupa: '',
                        workUnits: ''
                    };
                    break;
                case 'training':
                    item = {
                        trainingName: '',
                        trainingStartTime: '',
                        trainingEndTime: '',
                        trainingIns: '',
                        trainingDes: ''
                    };
                    break;
                case 'certificate':
                    item = {
                        certiName: '',
                        certifiTime: '',
                        certifiunit: ''
                    };
                    break;
                case 'bankCard':
                    item = {
                        bankAct: '',
                        bankName: '',
                        swiftCood: '',
                        bankDeposit: '',
                        BankEndeposit: ''
                    };
                    break;
                default:
                    Vm.$message({
                        title: '警告',
                        message: '无法增加条目',
                        type: 'warning',
                        duration: 1500
                    });
                    break;
            }
            this.form[key].push(item);
        },
        //删减条目信息
        delEntry: function(key, item) {
            var expArr = this.form[key],
                i = expArr.length;
            for (i; i--;) {
                if (expArr[i] === item) {
                    if (expArr.length > 1) {
                        expArr.splice(i, 1)
                    }
                }
            }
        },
        //添加员工交谈
        addChat: function() {
            var item = {
                interType: '',
                interTime: '',
                interObj: '',
                interPerson: '',
                interContent: '',
                afterCase: ''
            };
            this.form2.interArr.push(item);
        },
        //添加hr记录
        addrecord: function() {
            var item = {
                reContent: '',
                reTime: '',
            };
            this.form2.hrRecord.push(item);
        },
        //添加合同
        addContract: function() {
            var item = {
                conCompany: '',
                natEmploy: '',
                trialEndTime: '',
                conStartTime: '',
                conStatus: '',
                conEndTime: '',
                isdisabled: false,
            };
            this.form2.contract.push(item);
            //console.log(this.form2.contract)
        },
        //删除合同
        delContract: function(item) {
            var contract = this.form2.contract,
                i = contract.length;
            for (i; i--;) {
                if (contract[i] === item) {
                    if (contract.length > 1) {
                        contract.splice(i, 1)
                    } else {
                        this.$message({
                            title: '警告',
                            message: '不能删除最后一条',
                            type: 'warning',
                            duration: 1500
                        });
                    }
                }
            }
        },
        //添加奖惩信息
        addReward: function() {
            var item = {
                rewardName: '',
                rewardContent: ''
            };
            this.form2.reward.push(item);
        },
        //添加晋升记录
        addPromo: function() {
            var item = {
                promoType: '',
                promoTime: '',
                promoContent: '',
            };
            this.form2.promo.push(item);
        },
        //添加日报缺失
        addPaperMiss: function() {
            var item = {
                paperMissTime: '',
                paperMissCon: ''
            };
            this.form2.paperMiss.push(item);
        },
        //省市联动 选取省市
        getAdd: function(areaNo, addressData) {
            axios.post('/index.php?m=Api&a=address', { areaNo: areaNo })
                .then(function(res) {
                    var addressObj = JSON.parse(res.data[0]);
                    Vue.set(Vm.address, addressData, addressObj.data);
                })
        },
        checkJob: function(value) {
            var job = this.selData.job.filter(function(item) {
                return item.CD_VAL == value
            })[0];
            this.form.JobEnCd = job ? job.ETC : '';
            this.form.JOB_ID = job ? job.ID : '';
        },
        readCardId: function() {
            var cardId = this.form.perCartId;
            if (cardId.length < 18) {
                this.$message({
                    title: '警告',
                    message: '请输入18位号码',
                    type: 'warning',
                    duration: 1500
                });
            } else {
                var nowDate = new Date();
                this.form.perBirthDate = new Date(cardId.substring(6, 10), cardId.substring(10, 12) - 1, cardId.substring(12, 14));
                this.form.age = nowDate.getFullYear() - cardId.substring(6, 10);
                if (cardId.substring(16, 17)) {
                    this.form.sex = cardId.substring(16, 17) % 2 === 0 ? '1' : '0';
                } else {
                    this.form.sex = '2';
                }

            }
        },
        //计算司龄
        countComAge: function() {
            if (this.form.perJobDate) {
                var startDate = new Date(this.form.perJobDate),
                    endDate = this.form.depJobDate ? new Date(this.form.depJobDate) : new Date(),
                    month = Math.floor((endDate - startDate) / (60 * 60 * 24 * 30 * 1000));
                //alert(5);
                this.form.companyAge = month > 0 ? month + "月" : "未满一个月";
            }
        },

        //取消修改密码
        cancelChange: function() {
            this.changePwdVisible = false;
            this.pwdForm.erpPwd = '';
            this.pwdForm.confirmPwd = '';
        },
        //取消重置
        cancelReset: function() {
            this.resetPwdVisible = false;
        },

        //修改密码
        confirmChangePwd: function(erpAct, erpid) {
            var erpPwd = $.trim(this.pwdForm.erpPwd);
            var confirmPwd = $.trim(this.pwdForm.confirmPwd)
            if (erpPwd !== confirmPwd) {
                this.$message({
                    message: '俩次密码输入不一致',
                    type: 'warning',
                });
                return false;
            }
            if (erpPwd == '' || erpPwd == '') {
                this.$message({
                    message: '请输入密码',
                    type: 'warning',
                });
                return false;
            }

            axios.post('/index.php?m=Api&a=changePwd&erpPwd=' + confirmPwd + '&erpAct=' + erpAct + '&erpid=' + erpid)
                .then(function(res) {
                    if (res.data.code === 200) {
                        Vm.changePwdVisible = false;
                        Vm.$message({
                            message: '修改成功',
                            type: 'success',
                        });

                        window.setTimeout(function() {
                            // var url = location.hostname;
                            var route = document.createElement("a");
                            route.setAttribute("style", "display: none");
                            route.setAttribute("onclick", "opennewtab(this,'退出登录')");
                            route.setAttribute("_href", '/index.php?m=public&a=logout');
                            route.click();
                            // location.href = "http://"+url+"/index.php?m=public&a=logout"
                        }, 800)

                    }
                })
        },

        //重置密码
        resetPwd: function(erpAct, erpid, EmpScNm) {
            Vm.resetPwdVisible = false;
            axios.post('/index.php?m=Api&a=emailResetPwd&erpAct=' + erpAct + '&erpid=' + erpid + '&EmpScNm=' + EmpScNm)
                .then(function(res) {
                    if (res.data.code === 200) {
                        Vm.sendResetEmailStatus = true; //发送邮件按钮置灰
                        Vm.$message({
                            message: res.data.data,
                            type: 'success',
                        });
                    } else {
                        Vm.$message({
                            message: res.data.data,
                            type: 'warning',
                        });
                    }
                })
        },
        // 设置店铺交接人
        storeTransferSubmit:function(){
            var _this = this
            if(_this.no_handover_num != '0'){
                axios.post('/index.php?m=api&a=store_oneClick', {
                    // "ERP_ACT":_this.idd,
                    "ERP_ACT":_this.form.erpAct,
                    "handover_by":_this.storeTransferPerson
                })
                .then(function(res) {
                    console.log(res);
                    if (res.data.code == 200) {
                        _this.storeTransfer = false
                        _this.edit.show = false
                        _this.storeTransferPerson = ''
                        _this.card(_this.idd);
                        _this.$message({
                            message: "设置成功",
                            type: 'success'
                        });

                        setTimeout(() => {
                            _this.$alert('交接暂未完成，还不可以离职哦，请等该员工交接完成后再来将此员工设置为离职', '提示', {
                                confirmButtonText: '确定',
                            })
                        }, 1000);

                    }else{
                        _this.$message({
                            message: res.data.msg,
                            type: 'warning',
                        });
                    }
                })
            }else{
                _this.storeTransfer = false
                _this.edit.show = false
                _this.storeTransferPerson = ''
                _this.card(_this.idd);
                setTimeout(() => {
                    _this.$alert('交接暂未完成，还不可以离职哦，请等该员工交接完成后再来将此员工设置为离职', '提示', {
                        confirmButtonText: '确定',
                    })
                }, 1000);
            }

        },
        storeTransferCancel:function(){
            this.storeTransfer = false
            this.storeTransferPerson = ''
        }

    }
});