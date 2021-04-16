    var meet = new Vue({
        el: '#meeting',
        data: {
            //会议状态
            test: false,
            //加载中
            loading:false,
            meetingStatus: ['完成', '待跟进'],
            //待搜素人员
            personlist:[{'EMP_SC_NM':'ss'}],
            //搜索条件
            form: {
                timeType: '',
                meetStatus: [],
                startMeetTime: '',
                endMeetTime: '',
                keyWord: '',
                keyValue: '',
            },
            //新建会议、编辑会议
            newForm: {
                ID: '',
                MEETING_THEME: '',
                RECORD_MAN: '',
                MEETING_PLACE: '',
                PARTCIPANT: [],
                MEETING_DATE: '',
                MEETING_TIME: '',
                STATUS: '',
                MEETING_CONTENT: '',
                edit: '1',
            },
            //待办事项form
            waitForm: {
                wait: [
                ],
            },
            //会议列表
            dataList: {

            },
            page: {
                sePage: 1,
                pageSize: 20,
                sePage: 1,
            },
            count: 0,
            showMet: false,
            //关键词搜索
            keyfield: ['会议地点', '记录人', '参与人', '主题'],
            keyfield: [
                { 'val': 'MEETING_PLACE', 'label': '会议地点' },
                { 'val': 'RECORD_MAN', 'label': '记录人' },
                { 'val': 'PARTCIPANT', 'label': '参与人' },
                { 'val': 'MEETING_THEME', 'label': '主题' },
            ],
            //编辑参数
            /*  edit: {
                  show: true,
              },*/
            sorting: '',
            import_rec: '',
            checkAll: false,
            showDet: false,
            //下拉数据
            choice: {},
            countId: 1,
            //状态编辑
            editVisible: false,
            formLabelWidth: '60',
            //状态
            changeStatus: '',
            checkCount: 0,
            ue: {},
            //erp账号
            ScEnName:'',
            allman:{},
        },

        created: function() {
            this.search();
        },

        methods: {
            search: function() {
                
                axios.post("/index.php?m=api&a=choice")
                    .then(function(res) {
                        meet.choice = res.data[0];
                        if (!meet.newForm.ID) {
                            var day = new Date().getMonth() + 1;
                            var date = new Date().getFullYear() + '-' + day + '-' + new Date().getDate();
                            var time = new Date().getHours() + ':' + new Date().getMinutes() + ':' + new Date().getSeconds();
                            meet.newForm.MEETING_TIME = time;
                            meet.newForm.MEETING_DATE = date;
                        }
                    })
                    //获取参与人数据
                axios.post("index.php?m=api&a=meeting_getPersonList")
                    .then(function(res) {
                        meet.personlist = res.data.data;
                    })
                axios.post("/index.php?m=api&a=meeting_getEnName")
                    .then(function(res){
                        meet.newForm.RECORD_MAN =res.data.data;
                    })
                var params = {
                    'pagenow': this.page.sePage,
                    'pageSize': this.page.pageSize,
                    'meetStatus': this.form.meetStatus,
                    'timeType': this.form.timeType,
                    'startMeetTime': this.form.startMeetTime,
                    'endMeetTime': this.form.endMeetTime,
                    'keyWord': this.form.keyWord,
                    'keyValue': this.form.keyValue
                }
                if (this.form.meetStatus.indexOf("全部") >= 0) {
                    delete params.meetStatus;
                }
                axios.post("/index.php?m=api&a=meeting_dataList", { param: params })
                    .then(function(res) {
                        if (res.data.code === 200) {
                            
                            count = parseInt(res.data.data.count); 
                         
                            delete res.data.data.count
                            meet.count = count;
                            meet.dataList = res.data.data;
                            for (k in meet.dataList) {
                                meet.dataList[k].sort = Number(meet.page.pageSize * (meet.page.sePage - 1)) + Number(k) + 1;
                            }
                            //数量
                            for (var key in meet.dataList) {
                                Vue.set(meet.dataList[key], 'edit', false);
                            }
                            for (var i = meet.dataList.length; i--;) {
                                Vue.set(meet.dataList[i], 'checked', false);
                            }
                        }

                    })
            },
            //全选逻辑
            checkAllFn: function(item) {
                this.form.meetStatus = item ? this.meetingStatus : [];
            },
            checks: function(item) {
                // debugger;
                for (var i = this.form.meetStatus.length; i--;) {
                    if (item == this.form.meetStatus[i] && this.form.meetStatus.length == this.meetingStatus.length) {
                        this.test = true;
                        break;
                    } else {
                        this.test = false;
                    }
                }
            },
            handleSizeChange: function(size) {
                this.page.pageSize = size;
                this.search();
            },
            handleCurrentChange: function(currentPage) {

                this.page.sePage = currentPage;
                this.search();
            },
            //新建简历
            addRec: function() {
                this.personlist = [];
                this.showDet = true;
                this.loadUe('show','');
                for (var k in this.newForm) {
                    if (k !== 'PARTCIPANT'&&k!=='MEETING_DATE' && k!=='MEETING_TIME' && k!=='RECORD_MAN') {
                        this.newForm[k] = '';
                    }
                    this.newForm.PARTCIPANT = [];
                }
                this.search();
            },
            loadUe: function(type='',content='') {
                if(type=='show'){
                        setTimeout(function() {
                        UE.getEditor('editor', {
                            toolbars: [
                                ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat','insertimage', 
                                'rowspacingtop','rowspacingbottom',  'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 
                                'justifyleft', 'justifyright',  'justifycenter',  'justifyjustify','forecolor', 
                                'insertorderedlist','simpleupload',  'insertunorderedlist', 'selectall', 'cleardoc', 'fullscreen', 'source', 'undo', 'redo',
                                'backcolor', 'imagecenter', 'wordimage', ]
                            ],

                        }).setContent(content)
                    }, 200)
                }else{
                    setTimeout(function(){
                        this.ue = UE.getEditor('editor', {
                        toolbars: [
                            ['bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat','insertimage', 
                                 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist','simpleupload',
                                 'justifyleft', 'justifyright',  'justifycenter',  'justifyjustify','forecolor', 
                                 'rowspacingtop','rowspacingbottom',  'insertunorderedlist', 'selectall', 'cleardoc', 'fullscreen', 'source', 'undo', 'redo',
                                 'backcolor','imagecenter', 'wordimage', 
                            ]
                        ],

                        });
                    },100)                    
                }
                //}, 200)
            },

            //打开详情页
            show: function(id) {
                this.loadUe();
                this.search();
                idNow = id;
                meet.showDet = true;
                axios.post("/index.php?m=api&a=meeting_getMeetingDet&id=" + id)
                    .then(function(res) {
                        if (res.data.code === 200) {
                            //alert(res.data.data.MEETING_CONTENT)
                            meet.loadUe('show',res.data.data.MEETING_CONTENT);
                            meet.newForm = res.data.data;
                        }
                    })
                axios.post("/index.php?m=api&a=meeting_getWait_Data&id=" + id)
                    .then(function(res) {
                        var sortid = 1;
                        for (k in res.data.data) {
                            res.data.data[k].edit = false;
                            res.data.data[k].SORT_ID = sortid;
                            sortid++;
                        }

                        if (res.data.code === 200) {
                            meet.waitForm.wait = res.data.data;
                        }
                    })
            },

            //全选
            checkedAllFun: function() {
                for (i in meet.dataList) {
                    Vue.set(meet.dataList[i], 'checked', this.checkAll);
                }
            },
            //返回
            retGo: function() {
                this.loadUe();
                this.showDet = false;

            },
            //保存、修改
            save: function() {
                var MEETING_CONTENT = UE.getEditor('editor').getContent();
                var data = this.newForm;
                data.MEETING_CONTENT = MEETING_CONTENT;
                data['MEETING_THEME'] = $.trim(data['MEETING_THEME']);
                axios.post("/index.php?m=api&a=meeting_operationData", { param: data })
                    .then(function(res) {
                        if (res.data.code === 200) {
                            meet.$message({ message: '操作成功', type: 'success' });
                            if (meet.newForm.ID.length != 0) {
                                meet.showDet = false;
                            }
                            var id = res.data.data;
                            idNow = id;
                            axios.post("/index.php?m=api&a=meeting_getMeetingDet&id=" + id)
                                .then(function(res) {
                                    if (res.data.code === 200) {
                                        meet.newForm = res.data.data;
                                    }
                                })
                            meet.search();
                        } else {
                            meet.$message({ message: res.data.msg, type: 'warning' });
                        }

                    })

            },
            //列表修改
            saveList: function(id) {
                for (k in this.dataList) {
                    if (this.dataList[k].ID == id) {
                        var data = this.dataList[k];

                        axios.post("/index.php?m=api&a=meeting_operationData", { param: data })
                            .then(function(res) {
                                if (res.data.code == 200) {
                                    meet.$message({ message: '保存成功', type: 'success' });
                                }
                            })
                        this.dataList[k].edit = false;
                    }
                }
            },
            //新建待办事项
            addThing: function() {
                var item = {};
                item = {
                    WAIT_ID: '',
                    MEETING_ID: this.newForm.ID,
                    THINGS_THEME: '',
                    END_DATE: '',
                    END_TIME: '',
                    FOLLOW_MAN: '',
                    STATUS: '',
                    edit: true,
                    ARR_FOLLOW_MAN: [],
                }
                this.waitForm['wait'].push(item);
                this.countRet();
            },
            //重置序号   给事项加id
            countRet: function() {
                var len = 0;
                for (k in this.waitForm['wait']) {
                    this.waitForm['wait'][k].SORT_ID = ++len;
                }
            },

            //删除待办事项(选框删除)
            waitDel: function(item) {
                var waitArr = this.waitForm['wait'],
                    i = waitArr.length;
                for (i; i--;) {
                    if (waitArr[i] === item) {
                        waitArr.splice(i, 1)
                    }
                }
                this.countRet();
            },
            //删除待办事项(真实删除)
            trueWaitDel: function(item) {
                this.$confirm('确认删除该事项?', '提示', { type: 'warning' })
                    .then(function() {
                        axios.post("/index.php?m=api&a=meeting_delWaitThings", { params: item })
                            .then(function(res) {
                                if (res.data.code === 200) {
                                    meet.updateWait(idNow);
                                    meet.$message({
                                        message: '删除成功',
                                        type: 'success',
                                    });
                                }
                            })
                    })
            },
            //批量删除
            batchDel: function() {

                this.$confirm('确认批量删除信息?', '提示', { type: 'warning' })
                    .then(function() {
                        meet.checkedSelect();
                        
                        if (ids.length > 0) {
                            axios.post("/index.php?m=api&a=meeting_batchDel", { params: ids })
                                .then(function(res) {

                                    if (res.data.code === 200) {
                                        meet.$message({
                                            message: '删除成功',
                                            type: 'success',
                                        });
                                        meet.search();
                                    } else if (res.data == '没有权限') {
                                        meet.$message({
                                            message: '没有权限',
                                            type: 'error'
                                        });
                                    }
                                })
                        } else {
                            //alert(5)
                            meet.$message({
                                message: '未选择记录',
                                type: 'warning'
                            })
                        }
                    })
            },
            //新增该会议下的待办事项
            saveWait: function(meetid, waitid) {
                var loopID = waitid - 1;
                for (k in this.waitForm['wait']) {
                    if (loopID == k) {
                        var waitData = this.waitForm.wait[k];
                    }
                }
                var params = { 'meetid': meetid, 'waitid': waitid, waitData: waitData }
                axios.post("/index.php?m=api&a=meeting_save_waitThings", { params: params })
                    .then(function(res) {

                    })
            },
            //更新待办事项
            updateWait: function(id) {
                axios.post("/index.php?m=api&a=meeting_getWait_Data&id=" + id)
                    .then(function(res) {
                        var sortid = 1;
                        for (k in res.data.data) {
                            res.data.data[k].edit = false;
                            res.data.data[k].SORT_ID = sortid;
                            sortid++;
                        }
                        if (res.data.code === 200) {
                            if (res.data.data.length == 0) {
                                meet.waitForm.wait = {};
                            } else {
                                meet.waitForm.wait = res.data.data;
                            }
                        }
                    })
            },
            //修改待办事项(会议下)
            waitSave: function(meetid, waitid) {
                var waitData = this.waitForm.wait;
                for (k in waitData) {
                    delete waitData[k].END_TIME;
                    if (waitData[k]['WAIT_ID']) {
                        if (waitData[k]['WAIT_ID'] === waitid) {
                            waitDataNow = waitData[k];
                        }
                    } else {
                        if (waitData[k]['ID'] == waitid) {
                            waitDataNow = waitData[k];
                            waitDataNow['meetid'] = meetid;
                        }
                    }
                }
                waitDataNow['RECORD_MAN'] = this.newForm.RECORD_MAN;
                waitDataNow['PID'] = 1;
                waitDataNow.THINGS_THEME = $.trim(waitDataNow.THINGS_THEME);
                var params = { 'waitData': waitDataNow }
                axios.post("/index.php?m=api&a=meeting_save_waitThings", { params: params })
                    .then(function(res) {
                        if (res.data.code === 200) {
                            meet.updateWait(idNow);
                            meet.$message({
                                message: '保存成功',
                                type: 'success',
                            });
                        } else {
                            meet.$message({
                                message: res.data.msg,
                                type: 'warning',
                            });
                        }
                    })
            },

            //状态面板
            openChangeStatus: function() {
                var checkCount = 0;
                for (i in meet.dataList) {
                    if (meet.dataList[i].checked) {
                        checkCount++;
                    }
                }
                this.checkCount = checkCount;
                if (this.checkCount > 0) {
                    this.editVisible = true;
                } else {
                    meet.$message({ message: '未勾选会议', type: 'warning', });
                }


            },
            checkedSelect: function() {
                ids = [];

                for (i in meet.dataList) {
                    if (meet.dataList[i].checked) {
                        ids.push(meet.dataList[i].ID);
                    }
                }
            },
            //批量修改状态
            confirmChange: function() {
                meet.checkedSelect();
                if (this.changeStatus == '') {
                    meet.$message({ message: '请选择状态', type: 'warning', });
                }
                var params = { ids: ids, 'status': this.changeStatus }
                axios.post("/index.php?m=api&a=meeting_batchChangeStatus", { param: params })
                    .then(function(res) {
                        if (res.data.code === 200) {
                            meet.editVisible = false;
                            meet.search();

                            meet.$message({ message: '修改成功', type: 'success' });
                        } else {
                            meet.$message({ message: res.data.msg, type: 'error' });
                        }
                    })
            },
            //重置
            reset: function() {
                for (var k in this.form) {
                    if (k === "meetStatus") {
                        this.form[k] = []
                    } else if (k === 'pageSize') {
                        this.form[k] = 20;
                    } else {
                        this.form[k] = '';
                        this.form.pageSize = this.page.current;
                    }

                }
            },
            //导出
            exportMeet: function() {
                this.checkedSelect();
                if (ids.length > 0) {
                    location.href = '/index.php?m=api&a=meeting_exportList&param=' + ids
                } else {
                    meet.$message({ message: '未选择记录', type: 'warning' })
                }
            },

            //编辑
            meetedit: function(edit, sortid) {
                var k = 1;
                for (var i = 0; i < meet.waitForm['wait'].length; i++) {
                    //编辑状态限制
                    if (meet.waitForm['wait'][i].edit === true) {
                        k++;
                    }
                }
                if (k > 1) {
                    meet.$message({ message: '当前编辑尚未完成', type: 'warning' });
                    return false;
                } else {
                    for (var i = 0; i < meet.waitForm['wait'].length; i++) {
                        if (meet.waitForm['wait'][i].SORT_ID == sortid) {
                            //字符串转数组
                            meet.waitForm['wait'][i].FOLLOW_MAN = meet.waitForm['wait'][i].FOLLOW_MAN.split(",");
                            meet.waitForm['wait'][i].edit = true;
                        }

                    }
                }
            },
            cancel:function(){
                this.search();
            },
            resetNewRec: function() {
                for (var k in this.newForm) {
                    if (k !== 'ID' &&k!='PARTCIPANT'&&k!='WAIT_ID') {
                        this.newForm[k] = '';
                    }

                }
                this.loadUe("show",'');
                this.newForm.PARTCIPANT = [];
        },
            //远程搜索拼音
            remoteMethod: function(query){
                if(query.length>0){
                    this.loading = true;
                    setTimeout(function () {
                        axios.post("index.php?m=api&a=hr_dept_search_people&searchdata=" + query)
                            .then(function (res) {
                                meet.loading = false;
                                meet.personlist = res.data.data;
                            })
                    }, 300)
                }else{
                    axios.post("index.php?m=api&a=meeting_getPersonList")
                    .then(function(res) {
                        meet.personlist = res.data.data;
                    })
                }
            },

        }
    })  