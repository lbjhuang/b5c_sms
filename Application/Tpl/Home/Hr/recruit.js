/**
 * Created by b5m on 2017/9/8.
 */
var rec = new Vue({
    el: '#recruit',
    data: {
        //状态开关
        dialogFormVisible: false,
        //待搜素人员
        personlist:[{'EMP_SC_NM':'ss'}],
        /*推送面板开关*/
        pullVisible: false,
        //加载中
        loading:false,
        now_name:'',

        /*管理岗位开关*/
        jobVisible: false,
        //修改对话框参数
        editVisible: false,
        distVisible: false,
        telVisible:false,
        chooseVisible:false,
        copyInfo: '',
        formLabelWidth: '120',
        repeatTel:'',
        count: 0,
        checkAll: false,
        showRec: false,
        resumeStatus: [],
        jobs: [],
        detailBtnText: '保存',
        jobtitle: '',
        //resumeStatus: choice,
        sorting: ['默认', '按预约时间','按面试时间', '按岗位'],
        week: '',
        jobstatus: ['未生效', '已生效'],
        keyfield: ['周几', '姓名', '部门', '应聘岗位', '面试官', '电话', '邮箱', '简历来源', '面试评语', '预约人', '链接'],
        key1: ['as'],
        form: {
            checkStatus: [],
            checkSorting: '',
            startTime: '',
            endTime: '',
            keyword: '',
            date: '',
            week: '',
            sePage: 1,
            pageSize: 20,
            changeStatus: '',
            deptData: [],
            leader: [],
            Leader: [],
            choose: 0,
            lastpullId: []
        },
        page: {
            pageSize: 20,
            current: 20
        },
        againPull: false,
        //add
        newForm: {
            ID: '',
            NAME: '',
            NAME1: '',
            JOBS: '',
            JOB_DATE1: '',
            JOB_TIME1: '',
            JOB_DATE2: '',
            JOB_TIME2: '',
            TEL: '',
            WEEKDAYS: '',
            STATUS: '',
            MAIL: '',
            NAME2: [],
            DEPT_ID: '',
            SOURCE: '',
            PIC_URL: '',
            name: '',
            IS_NOT_ARRANGE: false,
            DEPT: '',
            JOB_ID: ''
        },
        URLSOURCE: false,
        formjob: {
            ID: '',
            CD_VAL: '',
            ETC: '',
            USE_YN: 'Y'
        },
        TotalList: [],
        dataList: [],
        choice: {},
        resumeLog: {
            JOB_TIME1: '',
            JOB_TIME2: '',
            NAME2: '',
            DEPT_ID: '',
            JOBS: '',
            STATUS: '',
            JOB_MSG: '',
            UPDATE_TIME: ''
        }
    },
    created: function() {
        this.search();
    },
    methods: {
        //查看
        review: function(item,type='review') {
            noticeId = item.ID;
            this.showRec = true;
            if (type=='review') {
                var id = item.ID;   
            }else{
                var id = item;
                this.telVisible = false;
            }
            
            axios.post("/index.php?m=api&a=recDetail", { params: id })
                .then(function(res) {
                    if (res.data.code === 200) {
                        if (res.data.data.DEPT_ID == 0) {
                            res.data.data.DEPT_ID = '';
                        }
                        for (var k in res.data.data) {
                            rec.newForm[k] = res.data.data[k];
                            status_now = rec.newForm.STATUS;
                        }
                        rec.detailBtnText = "修改"
                    }
                });
            axios.post("/index.php?m=api&a=getResLogData", { params: id })
                .then(function(res) {
                    if (res.data.code === 200) {
                        //console.log(res.data.data);
                        rec.resumeLog = res.data.data;
                    }
                })
        },
        //导出
        exportExcel: function() {
            var ids = [];
            for (var i = rec.dataList.length; i--;) {
                if (rec.dataList[i].checked) {
                    ids.push(rec.dataList[i].ID)
                }
            }
            if (ids.length > 0) {
                location.href = '/index.php?m=api&a=exportrecruit&param=' + ids
            } else {
                rec.$message({
                    message: '未选择人员',
                    type: 'error'
                })
            }

        },
        //导入
        import_rec: function(res) {
            //alert(res)
            console.log(res);
            if (res.code === 200) {
                rec.$message({
                    message: '导入成功',
                    type: 'success'
                });
                this.search();
            } else if(res.code === 501){
                rec.$message({
                    message: res.data,
                    type: 'warning',
                    duration:6000,
                });
                this.search();
            }else{
                rec.$message({
                    message: res.data,
                    type: 'error'
                });
                this.search();
            }
        },
        //跳转链接
        chaining: function(url) {
            window.open(url, '_blank')
            //window.open('http://' + url, '_blank')
        },
        //批量推送
        batchPull: function() {
            var ids = [];
            for (var i = rec.dataList.length; i--;) {
                if (rec.dataList[i].checked) {
                    ids.push(rec.dataList[i].ID)
                }
            }
            this.form.choose = ids.length;
            if (ids.length > 0) {
                if (rec.form.lastpullId.sort().toString() == ids.sort().toString()) {
                    rec.againPull = true;
                } else {
                    this.pullVisible = true;
                }
            } else {
                rec.$message({
                    message: '未选择人员',
                    type: 'error'
                });
            }
        },
        confirmPull: function() {
            this.pullVisible = false;
            this.againPull = false;
            var ids = [];
            var leader = this.form.Leader;
            for (var i = rec.dataList.length; i--;) {
                if (rec.dataList[i].checked) {
                    ids.push(rec.dataList[i].ID)
                }
            }

            if (ids.length > 0) {
                axios.post("/index.php?m=api&a=batchPull", { ids: ids, leader: leader })
                    .then(function(res) {
                        if (res.data.code === 200) {
                            rec.form.lastpullId = ids;
                            rec.$message({
                                message: '推送成功',
                                type: 'success'
                            });
                        } else {
                            rec.$message({
                                message: res.data.res,
                                type: 'error'
                            });
                        }
                    })
            } else {
                rec.$message({
                    message: '未选择人员',
                    type: 'error'
                });
            }
        },
        againPullMail: function() {
            this.confirmPull();
        },
        //批量删除
        batchDel: function() {
            this.$confirm('确认批量删除信息?', '提示', { type: 'warning' })
                .then(function() {
                    var ids = [];
                    for (var i = rec.dataList.length; i--;) {
                        if (rec.dataList[i].checked) {
                            ids.push(rec.dataList[i].ID)
                        }
                    }
                    if (ids.length > 0) {
                        axios.post("/index.php?m=api&a=batchDel", { params: ids })
                            .then(function(res) {
                                //alert(res.data)
                                if (res.data.code === 200) {
                                    rec.$message({
                                        message: '删除成功',
                                        type: 'success',
                                    });
                                    rec.search();
                                } else if (res.data == '没有权限') {
                                    rec.$message({
                                        message: '没有权限',
                                        type: 'error'
                                    });
                                }
                            })
                    } else {
                        rec.$message({
                            message: '未选择人员',
                            type: 'error'
                        })
                    }
                })


        },
        //全选
        checkedAllFun: function() {
            for (var i = rec.dataList.length; i--;) {
                Vue.set(rec.dataList[i], 'checked', this.checkAll);
            }
        },
        //新建账户   form表单形式跳转
        addAccount: function(item) {
            var resid = item.ID;
            //alert(resid);
            var url = '/index.php?m=hr&a=addPerson';
            var form1 = document.createElement('form');
            form1.setAttribute('style', 'display:none');
            form1.setAttribute('target', '');
            form1.setAttribute('method', 'post');
            form1.setAttribute('action', url);
            // 创建一个输入
            var input1 = document.createElement("input");
            input1.type = "text";
            input1.name = 'resid';
            input1.value = resid;
            form1.appendChild(input1);
            // 创建一个输入
            document.body.appendChild(form1);
            form1.submit();
            form1.remove();
        },
        addRec: function() {
            noticeId ='';
            status_now = '';
            this.showRec = true;
            this.detailBtnText = "保存"
            //Vue.set(rec.newForm, 'NAME1', rec.choice.NAME1);
            axios.post("/index.php?m=api&a=meeting_getEnName")
                    .then(function(res){
                        Vue.set(rec.newForm, 'NAME1', res.data.data);
                       // meet.newForm.RECORD_MAN =res.data.data;
                    })
            for (var k in this.newForm) {
                if (k !== 'NAME1' && k !== 'JOB_TIME1' && k !== 'IS_NOT_ARRANGE' && k !== 'JOB_DATE1') {
                    this.newForm[k] = '';
                }
                this.newForm.NAME2 = [];
            }
        },
        showList: function() {
            this.showRec = false;
            this.search();
        },
        showList1: function() {
            location.href = '/index.php?m=hr&a=recuitfollow'
        },
        save: function() {

            this.newForm.NAME = $.trim(this.newForm.NAME);
            this.newForm.NAME1 = $.trim(this.newForm.NAME1);
            this.newForm.TEL = $.trim(this.newForm.TEL);
            console.log(this.newForm);
            var param = this.newForm;
            axios.post("/index.php?m=api&a=addrecruit", { params: param })
                .then(function(res) {
                    console.log(res);
                    rec.dataList = res.data.data;
                    if (res.data.code === 200) {
                        rec.$message({
                            message: res.data.data,
                            type: 'success'
                        });
                        var resid = res.data.msg
                        setTimeout(function() {
                            var leftUrl = document.referrer;
                            var urlStr = leftUrl.slice(-12);
                            var urlStr1 = leftUrl.slice(-7);
                            if (urlStr == 'recuitFollow' || urlStr == 'recuitfollow') {
                                location.href = "/index.php?m=hr&a=recuitfollow"
                            } else {
                                location.href = "/index.php?m=hr&a=recruit&resid=" + resid
                            }
                        }, 500);
                    } else {
                        rec.$message({
                            message: res.data.data,
                            type: 'error'
                        });
                    }
                })
        },
        search: function() {
            status_now = '';
            var leftUrl = document.referrer;
            var urlStr = leftUrl.slice(-12);
            var urlStr1 = leftUrl.slice(-7);
            // alert(leftUrl)
            if (urlStr == 'recuitFollow' || urlStr == 'recuitfollow') {
                this.showRec = true;
                this.URLSOURCE = true;
                axios.post("/index.php?m=api&a=meeting_getEnName")
                    .then(function(res){
                        this.showRec = false;
                        Vue.set(rec.newForm, 'NAME1', res.data.data);
                    })
                /*axios.post("/index.php?m=api&a=choice", { params: param })
                    .then(function(res) {
                        this.showRec = false;
                        rec.choice = res.data[0];
                        Vue.set(rec.newForm, 'NAME1', rec.choice.NAME1);
                    })*/
            }else {
                this.URLSOURCE = false;
                 axios.post("/index.php?m=api&a=meeting_getEnName")
                    .then(function(res){
                        
                        Vue.set(rec.newForm, 'NAME1', res.data.data);
                    })
                axios.post("/index.php?m=api&a=choice", { params: param })
                    .then(function(res) {
                        rec.choice = res.data[0];
                        //rec.search();
                        //Vue.set(rec.newForm, 'NAME1', rec.choice.NAME1);
                        Vue.set(rec.newForm, 'JOBS', rec.newForm.JOBS);

                    })
            }

            var id = document.getElementById('resid').value;
            noticeId = id;
            this.newForm.JOBS = document.getElementById('job').value;
            axios.post("/index.php?m=api&a=recDetail", { params: id })
                .then(function(res) {
                    if (res.data.code === 200) {
                        if (res.data.data.DEPT_ID == '0') {
                            res.data.data.DEPT_ID = '';
                        }
                        rec.newForm = res.data.data;
                        status_now = rec.newForm.STATUS;
                        rec.detailBtnText = "修改"
                    }
                });
            axios.post("/index.php?m=api&a=getResLogData", { params: id })
                .then(function(res) {
                    if (res.data.code === 200) {
                        rec.resumeLog = res.data.data;
                    }
                })

            var param = this.form;
            axios.post("/index.php?m=api&a=recruitlist", { params: param })
                .then(function(res) {
                    if (res.data.code === 200) {
                        rec.dataList = res.data.data;
                        rec.count = +res.data.msg;
                        for (var i = rec.dataList.length; i--;) {
                            Vue.set(rec.dataList[i], 'checked', false);
                        }
                    } else {
                        rec.dataList = [];
                        alert('无数据');
                    }
                });
            axios.post("/index.php?m=api&a=choice", { params: param })
                .then(function(res) {
                    rec.choice = res.data[0];
                    var date = new Date().toLocaleDateString();
                    var time = new Date().getHours() + ':' + new Date().getMinutes() + ':' + new Date().getSeconds();
                 
                    if (!rec.newForm.NAME) {
                        Vue.set(rec.newForm, 'JOB_DATE1', date);
                        Vue.set(rec.newForm, 'JOB_TIME1', time);
                    }

                })

            axios.post("index.php?m=api&a=meeting_getPersonList")
                .then(function(res) {
                        rec.personlist = res.data.data;
                })
        },
        //重置
        reset: function() {
            for (var k in this.form) {
                if (k === "checkStatus") {
                    this.form[k] = []
                } else if (k === 'pageSize') {
                    this.form[k] = 20;
                } else {
                    this.form[k] = '';
                    this.form.pageSize = this.page.current;
                }
            }
            this.search();
        },
        resetNewRec: function() {
            for (var k in this.newForm) {
                if (k !== 'ID' && k !== 'IS_NOT_ARRANGE') {
                    this.newForm[k] = '';
                }

            }
        },
        //下载
        downloadRec: function() {
            location.href = '/index.php?m=Api&a=download&file=rec';
        },
        downloadresume: function(item) {
            if (item) {
                location.href = '/index.php?m=Api&a=download&file=resume&filename=' + item;
            } else {
                rec.$message({
                    message: '简历未上传',
                    type: 'error'
                });
            }
        },
        //上传提示
        uploadFileFun: function(res, file, fileList) {
            if (res.code === 200) {
                this.newForm.PIC_URL = res.data.savename;
                rec.$message({
                    message: '上传成功',
                    type: 'success'
                });
            } else {
                rec.$message({
                    message: res.data.res,
                    type: 'error'
                });
            }
        },

        handleSizeChange: function(size) {
            this.page.pageSize = size;
            this.form.pageSize = size;
            this.page.current = size;
            this.search();
        },
        handleCurrentChange: function(currentPage) {
            this.form.size = currentPage;
            this.search();
        },
        //批量修改
        batchEdit: function() {
            var ids = [];
            for (var i = rec.dataList.length; i--;) {
                if (rec.dataList[i].checked) {
                    ids.push(rec.dataList[i].ID)
                }
            }
            this.form.choose = ids.length;
            if (ids.length > 0) {
                this.editVisible = true;
            } else {
                rec.$message({
                    message: '未选择人员',
                    type: 'error'
                });
            }
        },

        //确认修改
        confirmChange: function() {
            this.editVisible = false;
            var status = this.form.changeStatus;
            var ids = [];
            for (var i = rec.dataList.length; i--;) {
                if (rec.dataList[i].checked) {
                    ids.push(rec.dataList[i].ID)
                }
            }
            var params = { id: ids, status: status };
            axios.post("/index.php?m=api&a=batchrecruit", { params: params })
                .then(function(res) {
                    console.log(res);
                    if (res.data.code === 200) {
                        rec.search();
                        rec.$message({
                            message: '修改成功',
                            type: 'success'
                        });

                    }
                })
        },
        getLeader: function(deptNm) {
            // rec.form.Leader = [];
            axios.post("/index.php?m=api&a=getLeader&param=" + deptNm)
                .then(function(res) {
                    if (res.data.code === 200) {
                        rec.form.leader = res.data.data;
                    } else {
                        rec.form.leader = [];
                        rec.$message({
                            message: res.data.data,
                            type: 'warning'
                        });
                    }
                })
        },
        adminJobs: function() {
            var jobName = this.newForm.JOBS;
            if (jobName) {
                this.jobtitle = '修改岗位';
                rec.formjob.CD_VAL = jobName;
                axios.post("/index.php?m=api&a=getEnJob&param=" + jobName)
                    .then(function(res) {
                        console.log(res);
                        rec.formjob.ETC = res.data.data.etc;
                        rec.formjob.ID = res.data.data.id;
                        rec.formjob.USE_YN = res.data.data.USE_YN;
                    })
            } else {
                rec.formjob.CD_VAL = '';
                rec.formjob.ETC = '';
                rec.formjob.USE_YN = '';
                this.jobtitle = '新增岗位';
            }
            this.jobVisible = true;
        },
        //修改岗位
        changeJob: function() {
            this.jobVisible = false;
            this.formjob.resumeId = rec.newForm.ID;
            var formjob = this.formjob;
            var resjob = formjob.CD_VAL;
            var resumeid = this.formjob.resumeId;
            axios.post("/index.php?m=api&a=changeJob", { params: formjob })
                .then(function(res) {
                    if (res.data.code === 200) {
                        rec.$message({
                            message: res.data.data,
                            type: 'success'
                        });


                        var url = document.referrer;
                        var urlStr = url.slice(-12);
                        //alert(urlStr)
                        //if (urlStr=='recuitFollow') {
                        rec.search();
                        rec.newForm.JOBS = formjob.CD_VAL;
                        //}


                    } else if (res.data.code === 400) {
                        rec.$confirm(res.data.data, '提示', {
                            confirmButtonText: '确定',
                            cancelButtonText: '取消',
                            type: 'warning'
                        }).then(() => {
                            rec.formjob.resumeId = rec.newForm.ID;
                            var formjob = rec.formjob;
                            axios.post("/index.php?m=api&a=setUse", { params: formjob })
                                .then(function(res) {
                                    if (res.data.code === 200) {
                                        rec.search();
                                        rec.$message({
                                            type: 'success',
                                            message: '设置成功'
                                        });
                                    }
                                })
                        }).catch(() => {
                            rec.$message({
                                type: 'info',
                                message: '已取消'
                            });
                        });
                    } else {
                        rec.$message({
                            message: res.data.data,
                            type: 'error'
                        });
                    }
                })

        },
        //新增岗位
        addJob: function() {
            this.jobVisible = false;
            var formjob = this.formjob;
            axios.post("/index.php?m=api&a=addJob", { params: formjob })
                .then(function(res) {
                    if (res.data.code === 200) {
                        rec.$message({
                            message: res.data.data,
                            type: 'success'
                        });
                        rec.search();
                    } else if (res.data.code === 400) {
                        rec.$confirm(res.data.data, '提示', {
                            confirmButtonText: '确定',
                            cancelButtonText: '取消',
                            type: 'warning'
                        }).then(() => {
                            rec.formjob.resumeId = rec.newForm.ID;
                            var formjob = rec.formjob;
                            axios.post("/index.php?m=api&a=setUse", { params: formjob })
                                .then(function(res) {
                                    if (res.data.code === 200) {
                                        rec.search();
                                        rec.$message({
                                            type: 'success',
                                            message: '设置成功'
                                        });
                                    }
                                })
                        }).catch(() => {
                            rec.$message({
                                type: 'info',
                                message: '已取消'
                            });
                        });
                    } else {
                        rec.$message({
                            message: res.data.data,
                            type: 'error'
                        });
                    }
                })
        },
        //识别信息
        distInfoFn: function() {
            if (this.copyInfo) {
                var str = this.copyInfo.replace(/[\r\n]/g, '&').split('&');
                for (var i = 0, len = str.length; i < len; i++) {
                    var childStr = trim(str[i]).replace(/\s/g, "#").split("#");
                    for (var j = 0, cLen = childStr.length; j < cLen; j++) {
                        if (i === 0) {
                            this.newForm.NAME = childStr[0]
                        }
                        var phone = trim(childStr[j]);
                        if (!isNaN(phone) && phone.length === 11) {
                            this.newForm.TEL = childStr[j]
                        }
                        if (childStr[j].indexOf('@') !== -1) {
                            var email = childStr[j].split("：");
                            this.newForm.MAIL = trim(email.filter(function(val) {
                                if (val.indexOf('@') !== -1) {
                                    return val;
                                }
                            })[0]);
                        }
                    }
                }
                this.distVisible = false;
                this.copyInfo = '';
            } else {
                rec.$message({
                    message: '请粘贴内容',
                    type: 'error'
                });
            }
        },

        //自动获取星期
        checkDate: function(date) {
            var date = new Date(date);
            var week = date.getDay();
            switch (week) {
                case 1:
                    week = '周一';
                    break;
                case 2:
                    week = '周二';
                    break;
                case 3:
                    week = '周三';
                    break;
                case 4:
                    week = '周四';
                    break;
                case 5:
                    week = '周五';
                    break;
                case 6:
                    week = '周六';
                    break;
                case 0:
                    week = '周日';
                    break;
                default:
                    week = '';

            }
            this.newForm.WEEKDAYS = week;
        },
        //远程搜索拼音
            remoteMethod: function(query){
                if(query.length>0){
                    this.loading = true;
                    setTimeout(function () {
                        axios.post("index.php?m=api&a=hr_dept_search_people&searchdata=" + query)
                            .then(function (res) {
                                rec.loading = false;
                                rec.personlist = res.data.data;
                            })
                    }, 50)
                }else{
                    axios.post("index.php?m=api&a=meeting_getPersonList")
                    .then(function(res) {
                        rec.personlist = res.data.data;
                    })
                }
            },
        clear:function(){
        },
            repeatNotice:function(){
                var telPhone = $.trim(rec.newForm.TEL);
                axios.post("index.php?m=api&a=repeatNotice&telPhone="+telPhone+"&id="+noticeId)
                    .then(function(res) {
                        if (res.data.code===200) {
                            rec.telVisible = true;
                            rec.repeatTel = res.data.data;
                        }
                    })
            },
            //不安排状态
            controlStatus:function(){
                if (rec.newForm.IS_NOT_ARRANGE) {
                    rec.newForm.STATUS = '不安排';
                }else{
                    rec.newForm.STATUS = status_now;
                }
            },
            //挑选
            confirmChoose:function(){
                rec.chooseVisible = false;
                var ids = [];
                 for (var i = rec.dataList.length; i--;) {
                    if (rec.dataList[i].checked) {
                        ids.push(rec.dataList[i].ID)
                    }
                }


                axios.get("index.php?m=api&a=meeting_getEnName")
                .then(function(res){
                    rec.now_name = res.data.data;
                })

               window.setTimeout(function(){
                   if (ids.length>0) {
                    var zn_name = rec.now_name;
                    var url = "/index.php?m=Hr&a=choose_resume&ids="+ids+"&zn_name="+zn_name;
                    axios.get(url)
                    .then(function(res){
                        if (res.data.code===200) {
                                rec.$message({
                                message: res.data.msg,
                                type: 'success'
                            });
                        }else{
                            rec.$message({
                                message: res.data.msg,
                                type: 'warning'
                            });
                        }
                    })
                }else{
                    rec.chooseVisible = false;
                    rec.$message({
                        type: 'warning',
                         message: '未选择人员'
                    });
                } 
               },500)
                
            }
    }
});

function trim(str) {
    return str.replace(/(^\s*)|(\s*$)/g, "");
}