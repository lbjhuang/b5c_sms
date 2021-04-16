    var meet = new Vue({
        el: '#wait',
        data: {
            //会议状态
            test: false,
            //搜索是否加载情况
            loading:false,
            //跟进人数据
            personlist1:[{'EMP_SC_NM':'test'}],
            
            meetingStatus: ['完成', '待跟进'],
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
                THINGS_THEME: '',
                RECORD_MAN: '',
                FOLLOW_MAN: '',
                END_DATE: '',
                END_TIME: '',
                STATUS: '',
                edit: '1',
            },
            //待办事项form
            waitForm: {
                wait: [],
            },
            //会议列表
            dataList: [],

            page: {
                sePage: 1,
                pageSize: 20,
                sePage: 1,
            },
            hasCount: 0,
            waitCount: 0,
            showMet: false,
            choice: {

            },
            //关键词搜索
            keyfield: [
                { 'val': 'RECORD_MAN', 'label': '记录人' },
                { 'val': 'FOLLOW_MAN', 'label': '跟进人' },
                { 'val': 'THINGS_THEME', 'label': '事项主题' },
            ],
            sorting: '',
            count: 1,
            import_rec: '',
            checkAll: false,
            showDet: false,
            //下拉数据
            choice: {},
            countId: 0,
            //状态编辑
            editVisible: false,
            formLabelWidth: '60',
            //状态
            changeStatus: '',
            checkCount: 0,
            showChild: true,
            ids: '',
        },

        created: function() {
            this.search();
        },

        methods: {
            search: function(childid = '', type = '') {
                for (key in this.newForm) {
                    this.newForm[key] = '';
                }
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
                  //获取跟进人数据
                axios.post("index.php?m=api&a=meeting_getWaitPersonList")
                    .then(function(res) {
                        meet.personlist1 = res.data.data;
                    })

                axios.post("/index.php?m=api&a=meeting_getEnName")
                    .then(function(res){
                        meet.newForm.RECORD_MAN =res.data.data;
                    })

                axios.post("/index.php?m=api&a=choice")
                    .then(function(res) {
                        //meet.newForm.RECORD_MAN = res.data[0].NAME1;
                        meet.choice = res.data[0];
                        if (!meet.newForm.ID) {
                            var day = new Date().getMonth() + 1;
                            var date = new Date().getFullYear() + '-' + day + '-' + new Date().getDate();
                            var time = new Date().getHours() + ':' + new Date().getMinutes() + ':' + new Date().getSeconds();
                            meet.newForm.END_TIME = time;
                            meet.newForm.END_DATE = date;
                        }

                    })
                    if (this.form.meetStatus.indexOf("全部")>=0) {
                        delete params.meetStatus;
                    }
                axios.post("/index.php?m=api&a=meeting_waiting_List", { param: params })               
                    .then(function(res) {
                        meet.count = parseInt(res.data.data.count);
                        if (res.data.code === 200) {
                           if(res.data.data.child){
                             for (var i = 0; i < res.data.data.child.length; i++) {
                                    res.data.data.child[i].edit = false;
                                }
                           }
                               if(res.data.data.show){
                                for (var i = 0; i < res.data.data.show.length; i++) {
                                    res.data.data.show[i].edit = false;
                                }
                               }
                 
                            
                            meet.dataList = res.data.data.show;
                            var k=0;   //该元素子元素(内容)数据顺序
                            
                            for (var i = 0; i < meet.dataList.length; i++) {
                                meet.dataList[i].SID = Number(meet.page.pageSize * (meet.page.sePage - 1)) + Number(i) + 1;          
                                var len = 0;
                                if(res.data.data.child){
                                    for (var j = 0; j < res.data.data.child.length; j++) {
                                        if (res.data.data.child[j].PID == meet.dataList[i].ID) {
                                                len++
                                            }
                                    }
                                }                              
                                    if (len>0) {
                                        meet.dataList[i].showChild = false;
                                    }else{
                                        meet.dataList[i].showChild = true;
                                    }
                                if (type == 'open') {
                                    if(meet.dataList[i].showChild===false){
                                        if (meet.dataList[i].ID == childid) {
                                            meet.dataList[i].showChild = true;
                                            //var sid = 1;                                  
                                            for (var j = 0; j < res.data.data.child.length; j++) {
                                                if (res.data.data.child[j].PID == childid) {
                                                    k++
                                                    res.data.data.child[j].CHILD_ID = k;
                                                    meet.dataList.splice(i +k, 0, res.data.data.child[j]);
                                                }
                                            }
                                        }
                                    }                                  
                                }
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

            //展开
            openChild: function(id) {
                this.search(id, 'open');
            },

            //关闭
            closeChild: function(id) {
                this.search(id, 'close');

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
                this.search();
                this.showDet = true;
            },
            //打开详情页
            show: function(id) {
                this.search();
                idNow = id;
                meet.showDet = true;
                axios.post("/index.php?m=api&a=meeting_getWaitingDet&id=" + id)
                    .then(function(res) {
                        if (res.data.code === 200) {
                            for (k in res.data.data.contents) {
                                res.data.data.contents[k].edit = false;
                            }
                            meet.newForm = res.data.data.title;
                            meet.waitForm['wait'] = res.data.data.contents;
                            for (var i = 0; i < meet.waitForm['wait'].length; i++) {
                                meet.waitForm['wait'][i].SORT_ID = i + 1;
                            }
                        }
                    })

            },

            //全选
            checkedAllFun: function() {
                for (var i = meet.dataList.length; i--;) {
                    Vue.set(meet.dataList[i], 'checked', this.checkAll);
                }
            },
            //返回
            retGo: function() {
                this.showDet = false;
            },
            //保存、修改
            save: function() {
                var data = this.newForm;
                data.THINGS_THEME = $.trim(data.THINGS_THEME);
                axios.post("/index.php?m=api&a=meeting_wait_operationData", { params: data })
                    .then(function(res) {
                        if (res.data.code === 200) {
                            delete idNow;
                            meet.$message({ message: '操作成功', type: 'success' });
                            if (meet.newForm.ID.length != 0) {
                                meet.showDet = false;
                            }
                            var id = res.data.data;
                            idNow = id;
                            axios.post("/index.php?m=api&a=meeting_getWaitingDet&id=" + id)
                                .then(function(res) {
                                    if (res.data.code === 200) {
                                        meet.newForm = res.data.data.title;
                                        meet.waitForm['wait'] = res.data.data.contents;
                                    }
                                })
                            meet.search();
                        } else {
                            meet.$message({ message: res.data.msg, type: 'warning' });
                        }

                    })

            },
            //修改新建待办事项内容
            waitSave: function(sortid, pid) {
                var waitData = this.waitForm.wait;
                for (k in waitData) {
                    if (waitData[k]['SORT_ID'] == sortid) {
                        delete waitData[k].END_TIME;
                        waitDataNow = waitData[k];
                    }
                }
                waitDataNow.THINGS_THEME = $.trim(waitDataNow.THINGS_THEME);
                waitDataNow['RECORD_MAN'] = this.newForm.RECORD_MAN;
                var params = { 'waitData': waitDataNow }
                axios.post("/index.php?m=api&a=meeting_wait_operationData", { params: params })
                    .then(function(res) {
                        //alert(5)
                        if (res.data.code === 200) {
                            meet.updateWait(idNow);
                            meet.search();
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
            //列表修改
            saveList: function(id) {
                for (k in this.dataList) {
                    if (this.dataList[k].ID == id) {
                        delete this.dataList[k].END_TIME;
                        var data = this.dataList[k];      
                        //var data = {'STATUS':this.dataList[k].STATUS,'ID':this.dataList[k].ID}
                        axios.post("/index.php?m=api&a=meeting_wait_operationData", { params: data })
                            .then(function(res) {
                                if (res.data.code == 200) {
                                    meet.$message({ message: '保存成功', type: 'success' });
                                }
                            })
                        this.dataList[k].edit = false;
                    }
                }
            },
            //新建事项内容(选框)
            addThing: function() {
                var item = {};
                item = {
                    WAIT_ID: '',
                    PID: this.newForm.ID,
                    THINGS_THEME: '',
                    END_DATE: '',
                    FOLLOW_MAN: [],
                    STATUS: '',
                    edit: true,
                    RECORD_MAN: '',
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
                        axios.post("/index.php?m=api&a=meeting_delWaitContent", { params: item })
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
                        var ids = [];
                        for (var i = meet.dataList.length; i--;) {
                            if (meet.dataList[i].checked) {
                                ids.push(meet.dataList[i].ID)
                            }
                        }
                        if (ids.length > 0) {
                            axios.post("/index.php?m=api&a=meeting_wait_batchDel", { params: ids })
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
                            meet.$message({
                                message: '未选择记录',
                                type: 'warning'
                            })
                        }
                    })
            },
            //更新列表
            updateWait: function(id) {
                axios.post("/index.php?m=api&a=meeting_getWaitingDet&id=" + id)
                    .then(function(res) {
                        if (res.data.code === 200) {
                            for (k in res.data.data.contents) {
                                res.data.data.contents[k].edit = false;
                            }
                            meet.newForm = res.data.data.title;
                            //标记序号  
                            meet.waitForm['wait'] = res.data.data.contents;
                            for (var i = 0; i < meet.waitForm['wait'].length; i++) {
                                meet.waitForm['wait'][i].SORT_ID = i + 1;
                            }
                        }
                    })
            },

            //状态面板
            openChangeStatus: function() {
                var checkCount = 0;
                for (var i = meet.dataList.length; i--;) {
                    if (meet.dataList[i].checked) {
                        checkCount++;
                    }
                }
                this.checkCount = checkCount;
                if (this.checkCount > 0) {
                    this.editVisible = true;
                } else {
                    meet.$message({ message: '未勾选事项', type: 'warning', });
                }


            },
            //全选选框
            checkedSelect: function() {
                ids = [];
                for (var i = meet.dataList.length; i--;) {
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
                axios.post("/index.php?m=api&a=meeting_wait_batchChangeStatus", { param: params })
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

            //子集内容编辑
            waitedit: function(edit, sortid) {
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
                        if (meet.waitForm['wait'][i].SORT_ID==sortid) {
                        //字符串转数组
                        meet.waitForm['wait'][i].FOLLOW_MAN = meet.waitForm['wait'][i].FOLLOW_MAN.split(",");
                        meet.waitForm['wait'][i].edit=true;
                        }

                    }
                }
            },
            //状态面板
            openChangeStatus: function() {
                var checkCount = 0;
                for (var i = meet.dataList.length; i--;) {
                    if (meet.dataList[i].checked) {
                        checkCount++;
                    }
                }
                this.checkCount = checkCount;
                if (this.checkCount > 0) {
                    this.editVisible = true;
                } else {
                    meet.$message({ message: '未勾选事项', type: 'warning', });
                }
            },
            //导出信息提示
            exportWait:function(){

                this.checkedSelect();
                if (ids.length > 0) {
                    location.href = '/index.php?m=api&a=meeting_wait_exportList&param=' + ids
                } else {
                    meet.$message({ message: '未选择记录', type: 'warning' })
                }
            },
            //取消
            cancel:function(){
                this.search();
            },
            //重置详情页面
            resetNewRec: function() {
                for (var k in this.newForm) {
                    if (k !== 'ID' &&k!='FOLLOW_MAN'&&k!='PID') {
                        this.newForm[k] = '';
                    }
                }
                this.newForm.FOLLOW_MAN = [];
            },
            remoteMethod:function (query) {
                if(query.length>0){
                    this.loading = true;
                    setTimeout(function () {
                        axios.post("index.php?m=api&a=hr_dept_search_people&searchdata=" + query)
                            .then(function (res) {
                                meet.loading = false;
                                meet.personlist1 = res.data.data;
                            })
                    }, 300)
                }else{
                    axios.post("index.php?m=api&a=meeting_getWaitPersonList")
                    .then(function(res) {
                        meet.personlist1 = res.data.data;
                    })
                }
            },
         
        }
    })