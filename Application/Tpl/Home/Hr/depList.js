/**
 * Created by b5m on 2017/9/8.
 */
function Tree(data, option) {
    this.option = option;
    this.rootData = data;
    this.acSize = 0;
    /**
     * 遍历数据把父节点及层加上去
     * @param data
     */
    this.setLevel = function (data) {
        if (!data.parent && !data.grade) {
            data.grade = 1;
            if (this.acSize < data.grade) {
                this.acSize = data.grade;
            }
        }
        if (data.children) {
            for (var i = 0; i < data.children.length; i++) {
                data.children[i].parent = data;
                data.children[i].grade = data.grade + 1;
                if (this.acSize < data.children[i].grade) {
                    this.acSize = data.children[i].grade;
                }
                this.setLevel(data.children[i]);
            }
        }
    };

    /**
     * 设置坐标
     * @param data
     * @param grade
     */
    this.setCoordination = function (data, grade) {
        //设置顶级节点
        if (data.grade === grade) {
            if (data.children.length > 0) {
                //子节点数量
                var cLen = data.children.length;
                //子节点加起来的总宽
                var allWidth = this.option.width * cLen + (cLen - 1) * this.option.spacing;
                //插入data的X、Y坐标值
                data.x = this.option.startX + allWidth / 2 - this.option.width / 2;
                data.y = this.option.startY;
                //遍历子节点
                for (var i = 0; i < data.children.length; i++) {
                    data.children[i].x = this.option.startX + i * this.option.width + i * this.option.spacing;
                    data.children[i].y = this.option.startY + this.option.height + this.option.gap;
                }
            } else {
                data.x = this.option.startX;
                data.y = this.option.startY;
            }
        } else {
            if (data.children && data.children.length > 0) {
                for (var j = 0; j < data.children.length; j++) {
                    if (data.children[j].grade === grade) {
                        this.calcCoordination(data.children[j]);
                    } else {
                        this.setCoordination(data.children[j], grade);
                    }
                }
            }
        }
    };

    /**
     * 设置x、y值
     * @param data
     */
    this.calcCoordination = function (data) {
        var noteSize = 0;
        if (data.children) noteSize = data.children.length;
        //设置只有一个子节点的情况
        if (noteSize === 1) {
            data.children[0].x = data.x;
            data.children[0].y = data.y + this.option.gap + this.option.height;
        } else if (noteSize > 1) {
            //最左X值
            var LeftX = this.getLeftX(data);
            this.calcValue(data.x - LeftX, data);
            for (var i = 0; i < noteSize; i++) {
                data.children[i].x = LeftX + (this.option.width + this.option.spacing) * i;
                data.children[i].y = data.y + this.option.gap + this.option.height;
            }
        }
    };

    /**
     * 计算最右x值
     * @param data
     * @returns {number}
     */
    this.getLeftX = function (data) {
        var noteSize = data.children.length;
        return (data.x + this.option.width / 2) - (noteSize * this.option.width + (noteSize - 1) * this.option.spacing) / 2;
    };

    /**
     * 左右移
     * @param x
     * @param data
     */
    this.calcValue = function (x, data) {
        //获取其所有父节点id
        var pidArray = [];
        this.getParentIds(data, pidArray, 0);
        if (this.rootData.ID !== data.ID) {
            this.calcXY(data, pidArray, this.rootData, x);
        }
    };

    /**
     * 遍历获取某个节点父节点
     * @param data
     * @param parray
     * @param i
     */
    this.getParentIds = function (data, parray, i) {
        if (data.parent) {
            parray[i] = data.parent.ID;
            this.getParentIds(data.parent, parray, (i + 1));
        }
    };

    /**
     * 左右移动具体实时方法
     * @param data
     * @param parray
     * @param allData
     * @param x
     */
    this.calcXY = function (data, parray, allData, x) {
        if (allData.children) {
            for (var j = 0; j < allData.children.length; j++) {
                if (allData.children[j].x > data.x) {
                    allData.children[j].x = allData.children[j].x + x;
                } else if (allData.children[j].x < data.x) {
                    allData.children[j].x = allData.children[j].x - x;
                }
                this.calcXY(data, parray, allData.children[j], x);
            }
        }
    };

    //获取最左X值
    this.getRX = function (data) {
        if (data.x < this.option.rX)
            this.option.rX = data.x;
        if (data.children && data.children.length > 0) {
            for (var i = 0; i < data.children.length; i++) {
                if (data.children[i].x < this.option.rX) {
                    this.option.rX = data.children[i].x;
                }
                this.getRX(data.children[i]);
            }
        }
    };

    //右偏移
    this.rightMove = function (data, x) {
        data.x = data.x + x;
        if (data.children && data.children.length > 0) {
            for (var i = 0; i < data.children.length; i++) {
                this.rightMove(data.children[i], x);
            }
        }
    }
}

// 获取当前元素
function getParent(data, id) {
    var node;
    (function (data) {
        for (var i = 0; i < data.length; i++) {
            if (data[i].ID === id) {
                node = data[i];
                break;
            } else {
                if (data[i].next_nodes.length) {
                    arguments.callee(data[i].next_nodes)
                }
            }
        }
    })(data);
    return node;
}

//获取所有父级的ID
var getParentIds =  function (d){
    var ids = [];
    (function (d) {
        if(d.parent){
            ids.push(d.parent.ID)
        }
        if(d.parent && d.parent.parent){
            arguments.callee(d.parent)
        }
    })(d);
    return ids;
}
var dep = new Vue({
    el: '#depList',
    data: {
        parameter: {},
        loading: false,
        depDialog: false,
        showDetail: false,
        btnGroupVisible: false,
        personVisible: false,
        editDep:false,
        form: {
            ID: '',
            DEPT_NM: '',
            DEPT_SHORT_NM: '',
            SORT: '',
            TYPE: '',
            STATUS: '',
            REG_TIME: '',
            PAR_DEPT_ID: '',
            CREATE_TIME:'',
            LEGAL_PERSON:''
        },
        depChoice: [],
        depInfo: {},
        person: [],
        typeLevel: '',
        personList: [],
        employeesData: [],
    },
    created: function () {
        this.drawChart();
        this.getChoice();
    },
    methods: {
        callForm: function (data) {
            var formData = [];
            var i = 0,
                len = data.length;
            for (i; i < len; i++) {
                var obj = {}
                if (data[i].next_nodes.length) {
                    obj = {
                        name: data[i].DEPT_NM ? data[i].DEPT_NM : data[i].node_name ? data[i].node_name : data[i].EMP_SC_NM,
                        peopleNum: data[i].people_num || 0,
                        ID: data[i].NEW_ID,
                        job: data[i].JOB_CD || null,
                        person: data[i].people_employees && data[i].people_employees.length ? data[i].people_employees : null,
                        children: arguments.callee(data[i].next_nodes),
                        tId: data[i].ID,
                        type: data[i].node_type,
                        hide: data[i].hide,
                        sort:data[i].SORT
                    }
                    formData.push(obj)
                } else {
                    obj = {
                        name: data[i].DEPT_NM ? data[i].DEPT_NM : data[i].node_name ? data[i].node_name : data[i].EMP_SC_NM,
                        peopleNum: data[i].people_num || 0,
                        job: data[i].JOB_CD || null,
                        person: data[i].people_employees && data[i].people_employees.length ? data[i].people_employees : null,
                        ID: data[i].NEW_ID,
                        tId: data[i].ID,
                        type: data[i].node_type,
                        hide: data[i].hide,
                        sort:data[i].SORT
                    }
                    formData.push(obj)
                }
            }
            return formData;
        },
        drawChart: function () {
            var _this = this;
            axios.post("/index.php?m=api&a=hr_dept_list_level_all")
                .then(function (res) {
                    var d = {
                        "code": 200,
                        "msg": "",
                        "data": [{
                            "ID": "75",
                            "DEPT_NM": "Gshopper",
                            "DEPT_EN_NM": "Gshopper",
                            "DEPT_CN_NM": "Gshopper",
                            "DEPT_SHORT_NM": null,
                            "TYPE": "0",
                            "SORT": "0",
                            "LEGAL_PERSON": "",
                            "STATUS": "N001490300",
                            "DEPT_LEVEL": "0",
                            "REG_TIME": "0000-00-00 00:00:00",
                            "PAR_DEPT_ID": "0",
                            "CREATE_TIME": "2017-09-26 15:19:45",
                            "UPDATE_TIME": "2017-09-26 15:19:45",
                            "CREATE_USER_ID": "63",
                            "UPDATE_USER_ID": "63",
                            "node_type": "dept",
                            "node_name": "",
                            "people_employees": [{
                                "ID": "1474",
                                "ERP_ACT": "Blake.Jiang",
                                "EMPL_ID": "1583",
                                "EMP_NM": "",
                                "EMP_SC_NM": "Blake.Jiang",
                                "SEX": "\u4e0d\u586b",
                                "EMAIL": "",
                                "WORK_NUM": "999990",
                                "STATUS": "\u5728\u804c",
                                "DIRECT_LEADER": "",
                                "JOB_CD": "Overseas Business\u526f\u603b\u88c1",
                                "PER_JOB_DATE": "2020-03-03",
                                "employee_type": "\u9ed8\u8ba4",
                                "employee_type_id": 0,
                                "employee_type_level": "0",
                                "job_rank": null,
                                "PERCENT": "100"
                            }, {
                                "ID": "1467",
                                "ERP_ACT": "again",
                                "EMPL_ID": "1576",
                                "EMP_NM": "",
                                "EMP_SC_NM": "again",
                                "SEX": "\u4e0d\u586b",
                                "EMAIL": "",
                                "WORK_NUM": "963852741",
                                "STATUS": "\u5728\u804c",
                                "DIRECT_LEADER": "",
                                "JOB_CD": "\u9ad8\u7ea7Overseas B2C\u603b\u76d1",
                                "PER_JOB_DATE": "2019-12-26",
                                "employee_type": "\u9ed8\u8ba4",
                                "employee_type_id": 0,
                                "employee_type_level": "1",
                                "job_rank": "10",
                                "PERCENT": "100"
                            }, {
                                "ID": "1464",
                                "ERP_ACT": "A",
                                "EMPL_ID": "1573",
                                "EMP_NM": "",
                                "EMP_SC_NM": "a",
                                "SEX": "\u4e0d\u586b",
                                "EMAIL": "",
                                "WORK_NUM": "1111111",
                                "STATUS": "\u5728\u804c",
                                "DIRECT_LEADER": "",
                                "JOB_CD": "Overseas Business\u526f\u603b\u88c1",
                                "PER_JOB_DATE": "2019-12-25",
                                "employee_type": "\u9ed8\u8ba4",
                                "employee_type_id": 0,
                                "employee_type_level": "0",
                                "job_rank": "5",
                                "PERCENT": "100"
                            }, {
                                "ID": "1458",
                                "ERP_ACT": "jeli.fang",
                                "EMPL_ID": "1567",
                                "EMP_NM": "",
                                "EMP_SC_NM": "jeli fang",
                                "SEX": "\u4e0d\u586b",
                                "EMAIL": "",
                                "WORK_NUM": "10096",
                                "STATUS": "\u5728\u804c",
                                "DIRECT_LEADER": "",
                                "JOB_CD": "\u4ea7\u54c1\u7ecf\u7406",
                                "PER_JOB_DATE": "2019-12-18",
                                "employee_type": "\u9ed8\u8ba4",
                                "employee_type_id": 0,
                                "employee_type_level": "0",
                                "job_rank": null,
                                "PERCENT": "100"
                            }, {
                                "ID": "1445",
                                "ERP_ACT": "ceshi0001",
                                "EMPL_ID": "1554",
                                "EMP_NM": "",
                                "EMP_SC_NM": "\u6d4b\u8bd5",
                                "SEX": "\u4e0d\u586b",
                                "EMAIL": "",
                                "WORK_NUM": "000522",
                                "STATUS": "\u5728\u804c",
                                "DIRECT_LEADER": "",
                                "JOB_CD": "\u9ad8\u7ea7Overseas B2C\u603b\u76d1",
                                "PER_JOB_DATE": "2019-12-17",
                                "employee_type": "\u9ed8\u8ba4",
                                "employee_type_id": 0,
                                "employee_type_level": "0",
                                "job_rank": "10",
                                "PERCENT": "100"
                            }, {
                                "ID": "1433",
                                "ERP_ACT": "Teresa.Wang",
                                "EMPL_ID": "1542",
                                "EMP_NM": "",
                                "EMP_SC_NM": "Teresa Wang",
                                "SEX": "\u4e0d\u586b",
                                "EMAIL": "",
                                "WORK_NUM": "946543",
                                "STATUS": "\u5728\u804c",
                                "DIRECT_LEADER": "",
                                "JOB_CD": "\u4ea7\u54c1\u5f00\u53d1",
                                "PER_JOB_DATE": "2019-11-07",
                                "employee_type": "\u8d1f\u8d23\u4eba",
                                "employee_type_id": 1,
                                "employee_type_level": "0",
                                "job_rank": "0",
                                "PERCENT": "100"
                            }],
                            "people_num": 11,
                            "next_nodes": [{
                                "ID": "79",
                                "DEPT_NM": "China Purchasing",
                                "DEPT_EN_NM": "China Purchasing",
                                "DEPT_CN_NM": "China Purchasing",
                                "DEPT_SHORT_NM": "China Purchasing",
                                "TYPE": "N002510400",
                                "SORT": "4",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490200",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:21:16",
                                "UPDATE_TIME": "2019-05-24 09:26:07",
                                "CREATE_USER_ID": "0",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [{
                                    "ID": "1469",
                                    "ERP_ACT": "sherry.huang",
                                    "EMPL_ID": "1578",
                                    "EMP_NM": "",
                                    "EMP_SC_NM": "\u53cc\u513f",
                                    "SEX": "\u4e0d\u586b",
                                    "EMAIL": "",
                                    "WORK_NUM": "20001",
                                    "STATUS": "\u5728\u804c",
                                    "DIRECT_LEADER": "",
                                    "JOB_CD": "\u62db\u5546\u603b\u76d1",
                                    "PER_JOB_DATE": "2020-02-07",
                                    "employee_type": "\u9ed8\u8ba4",
                                    "employee_type_id": 0,
                                    "employee_type_level": "0",
                                    "job_rank": "15",
                                    "PERCENT": "100"
                                }, {
                                    "ID": "1457",
                                    "ERP_ACT": "ccc",
                                    "EMPL_ID": "1566",
                                    "EMP_NM": "",
                                    "EMP_SC_NM": "ccc",
                                    "SEX": "\u4e0d\u586b",
                                    "EMAIL": "",
                                    "WORK_NUM": "0099",
                                    "STATUS": "\u5728\u804c",
                                    "DIRECT_LEADER": "",
                                    "JOB_CD": "\u62db\u5546\u603b\u76d1",
                                    "PER_JOB_DATE": "2019-12-05",
                                    "employee_type": "\u9ed8\u8ba4",
                                    "employee_type_id": 0,
                                    "employee_type_level": "0",
                                    "job_rank": "15",
                                    "PERCENT": "100"
                                }],
                                "people_num": 2,
                                "next_nodes": [{
                                    "ID": "173",
                                    "DEPT_NM": "Brand Purchasing",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "Brand Purchasing",
                                    "TYPE": "N002510400",
                                    "SORT": "1",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "79",
                                    "CREATE_TIME": "2018-11-22 19:53:17",
                                    "UPDATE_TIME": "2019-03-14 21:20:42",
                                    "CREATE_USER_ID": "9320",
                                    "UPDATE_USER_ID": "109",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "210",
                                    "DEPT_NM": "Export Sales",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "Export Sales",
                                    "TYPE": "N002510400",
                                    "SORT": "2",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "79",
                                    "CREATE_TIME": "2019-03-14 21:20:12",
                                    "UPDATE_TIME": "2019-03-14 21:20:26",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "109",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "81",
                                "DEPT_NM": "Consultant",
                                "DEPT_EN_NM": "Consultant",
                                "DEPT_CN_NM": "Consultant",
                                "DEPT_SHORT_NM": "Consultant",
                                "TYPE": "N002510300",
                                "SORT": "7",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490200",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:21:37",
                                "UPDATE_TIME": "2019-05-24 09:27:04",
                                "CREATE_USER_ID": "0",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [{
                                    "ID": "1460",
                                    "ERP_ACT": "test0001",
                                    "EMPL_ID": "1569",
                                    "EMP_NM": "",
                                    "EMP_SC_NM": "测试0001",
                                    "SEX": "\u4e0d\u586b",
                                    "EMAIL": "",
                                    "WORK_NUM": "000000002",
                                    "STATUS": "\u5728\u804c",
                                    "DIRECT_LEADER": "",
                                    "JOB_CD": "\u9ad8\u7ea7Overseas B2C\u603b\u76d1",
                                    "PER_JOB_DATE": "2019-12-19",
                                    "employee_type": "\u8d1f\u8d23\u4eba",
                                    "employee_type_id": 1,
                                    "employee_type_level": "3",
                                    "job_rank": "8",
                                    "PERCENT": "100"
                                }, {
                                    "ID": "1450",
                                    "ERP_ACT": "cyrus",
                                    "EMPL_ID": "1570",
                                    "EMP_NM": "",
                                    "EMP_SC_NM": "yuanzong",
                                    "SEX": "\u4e0d\u586b",
                                    "EMAIL": "",
                                    "WORK_NUM": "0039",
                                    "STATUS": "\u5728\u804c",
                                    "DIRECT_LEADER": "",
                                    "JOB_CD": "\u9ad8\u7ea7Overseas B2C\u603b\u76d1",
                                    "PER_JOB_DATE": "2019-12-12",
                                    "employee_type": "\u8d1f\u8d23\u4eba",
                                    "employee_type_id": 1,
                                    "employee_type_level": "0",
                                    "job_rank": "5",
                                    "PERCENT": "100"
                                }],
                                "people_num": 2,
                                "next_nodes": []
                            }, {
                                "ID": "84",
                                "DEPT_NM": "Big Data & AI",
                                "DEPT_EN_NM": "Insight Center",
                                "DEPT_CN_NM": "Insight Center",
                                "DEPT_SHORT_NM": "Big Data & AI",
                                "TYPE": "N002510200",
                                "SORT": "10",
                                "LEGAL_PERSON": "\u767e\u6653\u751f,test\u82b1\u540d",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:22:16",
                                "UPDATE_TIME": "2020-01-21 16:03:44",
                                "CREATE_USER_ID": "65",
                                "UPDATE_USER_ID": "9186",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": []
                            }, {
                                "ID": "85",
                                "DEPT_NM": "Strategy",
                                "DEPT_EN_NM": "Strategy",
                                "DEPT_CN_NM": "Strategy",
                                "DEPT_SHORT_NM": "Strategy",
                                "TYPE": "N002510200",
                                "SORT": "12",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:22:32",
                                "UPDATE_TIME": "2019-05-24 09:28:14",
                                "CREATE_USER_ID": "0",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": []
                            }, {
                                "ID": "86",
                                "DEPT_NM": "Finance",
                                "DEPT_EN_NM": "FIN",
                                "DEPT_CN_NM": "FIN",
                                "DEPT_SHORT_NM": "FIN",
                                "TYPE": "N002510200",
                                "SORT": "13",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490200",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:22:40",
                                "UPDATE_TIME": "2019-05-24 09:28:25",
                                "CREATE_USER_ID": "0",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "101",
                                    "DEPT_NM": "ShangHai-Treasury",
                                    "DEPT_EN_NM": "ShangHai",
                                    "DEPT_CN_NM": "ShangHai",
                                    "DEPT_SHORT_NM": "ShangHai-Treasury",
                                    "TYPE": "1",
                                    "SORT": "2",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "86",
                                    "CREATE_TIME": "2017-09-26 15:27:07",
                                    "UPDATE_TIME": "2019-06-10 10:52:44",
                                    "CREATE_USER_ID": "400",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "153",
                                    "DEPT_NM": "ShangHai-Accounting",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "ShangHai-Accounting",
                                    "TYPE": "N002510200",
                                    "SORT": "1",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "86",
                                    "CREATE_TIME": "2018-11-22 19:27:52",
                                    "UPDATE_TIME": "2018-11-28 18:34:27",
                                    "CREATE_USER_ID": "9320",
                                    "UPDATE_USER_ID": "9320",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "87",
                                "DEPT_NM": "IT",
                                "DEPT_EN_NM": "IT",
                                "DEPT_CN_NM": "IT",
                                "DEPT_SHORT_NM": "IT",
                                "TYPE": "N002510200",
                                "SORT": "15",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:22:57",
                                "UPDATE_TIME": "2019-05-24 09:28:52",
                                "CREATE_USER_ID": "65",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [{
                                    "ID": "1444",
                                    "ERP_ACT": "aaa",
                                    "EMPL_ID": "1553",
                                    "EMP_NM": "",
                                    "EMP_SC_NM": "aaa",
                                    "SEX": "\u4e0d\u586b",
                                    "EMAIL": "",
                                    "WORK_NUM": "13123",
                                    "STATUS": "\u5728\u804c",
                                    "DIRECT_LEADER": "",
                                    "JOB_CD": "\u521b\u59cb\u4eba&\u9996\u5e2d\u6267\u884c\u5b98",
                                    "PER_JOB_DATE": "2019-12-11",
                                    "employee_type": "\u9ed8\u8ba4",
                                    "employee_type_id": 0,
                                    "employee_type_level": "0",
                                    "job_rank": "1",
                                    "PERCENT": "100"
                                }],
                                "people_num": 1,
                                "next_nodes": [{
                                    "ID": "106",
                                    "DEPT_NM": "SA",
                                    "DEPT_EN_NM": "SA",
                                    "DEPT_CN_NM": "SA",
                                    "DEPT_SHORT_NM": null,
                                    "TYPE": "1",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2017-09-26 15:27:38",
                                    "PAR_DEPT_ID": "87",
                                    "CREATE_TIME": "2017-09-26 15:27:38",
                                    "UPDATE_TIME": "2017-09-26 15:27:38",
                                    "CREATE_USER_ID": "400",
                                    "UPDATE_USER_ID": "400",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "107",
                                    "DEPT_NM": "Helpdesk",
                                    "DEPT_EN_NM": "Helpdesk",
                                    "DEPT_CN_NM": "Helpdesk",
                                    "DEPT_SHORT_NM": "11",
                                    "TYPE": "1",
                                    "SORT": "0",
                                    "LEGAL_PERSON": ",test\u82b1\u540d,\u6d4b\u8bd5",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "87",
                                    "CREATE_TIME": "2017-09-26 15:27:51",
                                    "UPDATE_TIME": "2020-01-21 16:04:43",
                                    "CREATE_USER_ID": "400",
                                    "UPDATE_USER_ID": "9186",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": [{
                                        "ID": "257",
                                        "DEPT_NM": "12",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "12",
                                        "TYPE": "N002510200",
                                        "SORT": "5",
                                        "LEGAL_PERSON": "test\u82b1\u540d",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "2020-01-21 16:05:02",
                                        "PAR_DEPT_ID": "107",
                                        "CREATE_TIME": "2020-01-21 16:05:02",
                                        "UPDATE_TIME": "2020-01-21 16:05:02",
                                        "CREATE_USER_ID": "9186",
                                        "UPDATE_USER_ID": "9186",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }]
                                }]
                            }, {
                                "ID": "88",
                                "DEPT_NM": "HR",
                                "DEPT_EN_NM": "HR",
                                "DEPT_CN_NM": "HR",
                                "DEPT_SHORT_NM": "HR",
                                "TYPE": "N002510200",
                                "SORT": "14",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:23:04",
                                "UPDATE_TIME": "2019-05-24 09:28:38",
                                "CREATE_USER_ID": "65",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "223",
                                    "DEPT_NM": "GCafe",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "GCafe",
                                    "TYPE": "N002510200",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "88",
                                    "CREATE_TIME": "2019-04-28 19:00:36",
                                    "UPDATE_TIME": "2019-05-22 18:02:41",
                                    "CREATE_USER_ID": "9699",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "224",
                                    "DEPT_NM": "ShangHai",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "ShangHai",
                                    "TYPE": "N002510200",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-04-28 19:02:27",
                                    "PAR_DEPT_ID": "88",
                                    "CREATE_TIME": "2019-04-28 19:02:27",
                                    "UPDATE_TIME": "2019-04-28 19:02:27",
                                    "CREATE_USER_ID": "9699",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "225",
                                    "DEPT_NM": "ShenZhen",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "ShenZhen",
                                    "TYPE": "N002510200",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-04-28 19:03:02",
                                    "PAR_DEPT_ID": "88",
                                    "CREATE_TIME": "2019-04-28 19:03:02",
                                    "UPDATE_TIME": "2019-04-28 19:03:02",
                                    "CREATE_USER_ID": "9699",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "226",
                                    "DEPT_NM": "Japan",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "Japan",
                                    "TYPE": "N002510200",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-04-28 19:03:33",
                                    "PAR_DEPT_ID": "88",
                                    "CREATE_TIME": "2019-04-28 19:03:33",
                                    "UPDATE_TIME": "2019-04-28 19:03:33",
                                    "CREATE_USER_ID": "9699",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "89",
                                "DEPT_NM": "Legal",
                                "DEPT_EN_NM": "Legal",
                                "DEPT_CN_NM": "Legal",
                                "DEPT_SHORT_NM": "Legal",
                                "TYPE": "N002510200",
                                "SORT": "16",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2017-09-26 15:23:11",
                                "UPDATE_TIME": "2019-05-24 09:28:59",
                                "CREATE_USER_ID": "65",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": []
                            }, {
                                "ID": "149",
                                "DEPT_NM": "Creative & SNS",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "Creative & SNS",
                                "TYPE": "N002510200",
                                "SORT": "8",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2018-11-22 16:11:05",
                                "UPDATE_TIME": "2019-05-24 09:27:17",
                                "CREATE_USER_ID": "9186",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "214",
                                    "DEPT_NM": "SNS",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "SNS",
                                    "TYPE": "N002510200",
                                    "SORT": "2",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-03-14 21:30:41",
                                    "PAR_DEPT_ID": "149",
                                    "CREATE_TIME": "2019-03-14 21:30:41",
                                    "UPDATE_TIME": "2019-03-14 21:30:41",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "109",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "154",
                                "DEPT_NM": "Korea",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "Korea",
                                "TYPE": "N002510300",
                                "SORT": "2",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2018-11-22 19:34:29",
                                "UPDATE_TIME": "2019-05-22 19:16:45",
                                "CREATE_USER_ID": "9320",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "179",
                                    "DEPT_NM": "KRSales",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "KRSales",
                                    "TYPE": "N002510300",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2018-11-22 20:10:51",
                                    "PAR_DEPT_ID": "154",
                                    "CREATE_TIME": "2018-11-22 20:10:51",
                                    "UPDATE_TIME": "2018-11-22 20:10:51",
                                    "CREATE_USER_ID": "9320",
                                    "UPDATE_USER_ID": "9320",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "180",
                                    "DEPT_NM": "KRSupporting",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "KRSupporting",
                                    "TYPE": "N002510300",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2018-11-22 20:11:22",
                                    "PAR_DEPT_ID": "154",
                                    "CREATE_TIME": "2018-11-22 20:11:22",
                                    "UPDATE_TIME": "2018-11-22 20:11:22",
                                    "CREATE_USER_ID": "9320",
                                    "UPDATE_USER_ID": "9320",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "155",
                                "DEPT_NM": "Warehouse",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "Warehouse",
                                "TYPE": "N002510300",
                                "SORT": "11",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2018-11-22 19:37:39",
                                "UPDATE_TIME": "2019-05-24 09:28:04",
                                "CREATE_USER_ID": "9320",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": []
                            }, {
                                "ID": "157",
                                "DEPT_NM": "China B2C - SB",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "China B2C - SB",
                                "TYPE": "N002510300",
                                "SORT": "6",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2018-11-22 19:39:39",
                                "UPDATE_TIME": "2019-05-24 09:26:37",
                                "CREATE_USER_ID": "9320",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": []
                            }, {
                                "ID": "158",
                                "DEPT_NM": "China - SY",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "China - SY",
                                "TYPE": "N002510300",
                                "SORT": "1",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2018-11-22 19:40:11",
                                "UPDATE_TIME": "2019-05-22 11:32:30",
                                "CREATE_USER_ID": "9320",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "216",
                                    "DEPT_NM": "B2B Sales - SY",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "B2B Sales - SY",
                                    "TYPE": "N002510300",
                                    "SORT": "1",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "158",
                                    "CREATE_TIME": "2019-03-27 12:40:01",
                                    "UPDATE_TIME": "2019-05-27 10:34:29",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "217",
                                    "DEPT_NM": "B2B Purchasing - SY",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "B2B Purchasing - SY",
                                    "TYPE": "N002510400",
                                    "SORT": "2",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "158",
                                    "CREATE_TIME": "2019-03-27 12:41:15",
                                    "UPDATE_TIME": "2019-05-27 10:34:46",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "218",
                                    "DEPT_NM": "B2C - SY",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "B2C - SY",
                                    "TYPE": "N002510300",
                                    "SORT": "3",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "158",
                                    "CREATE_TIME": "2019-03-27 12:42:20",
                                    "UPDATE_TIME": "2019-05-27 10:35:01",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "219",
                                    "DEPT_NM": "BD - SY",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "BD - SY",
                                    "TYPE": "N002510300",
                                    "SORT": "4",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "158",
                                    "CREATE_TIME": "2019-03-27 12:43:38",
                                    "UPDATE_TIME": "2019-05-27 10:35:17",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "159",
                                "DEPT_NM": "Overseas Business",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "Overseas",
                                "TYPE": "N002510300",
                                "SORT": "3",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2018-11-22 19:40:52",
                                "UPDATE_TIME": "2019-05-24 09:25:23",
                                "CREATE_USER_ID": "9320",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "160",
                                    "DEPT_NM": "Overseas B2C - QQ",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "Overseas B2C - QQ",
                                    "TYPE": "N002510300",
                                    "SORT": "0",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "159",
                                    "CREATE_TIME": "2018-11-22 19:42:04",
                                    "UPDATE_TIME": "2019-05-22 13:27:55",
                                    "CREATE_USER_ID": "9320",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": [{
                                        "ID": "164",
                                        "DEPT_NM": "KR&JP",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "KR&JP",
                                        "TYPE": "N002510300",
                                        "SORT": "5",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:44:34",
                                        "UPDATE_TIME": "2019-05-22 15:22:32",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9699",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "165",
                                        "DEPT_NM": "Aliexpress ",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Aliexpress ",
                                        "TYPE": "N002510300",
                                        "SORT": "1",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:45:01",
                                        "UPDATE_TIME": "2018-11-29 10:34:15",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9320",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "166",
                                        "DEPT_NM": "Amazon",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Amazon",
                                        "TYPE": "N002510300",
                                        "SORT": "2",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:45:35",
                                        "UPDATE_TIME": "2018-11-29 10:34:25",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9320",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "167",
                                        "DEPT_NM": "Ebay",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Ebay",
                                        "TYPE": "N002510300",
                                        "SORT": "4",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:46:02",
                                        "UPDATE_TIME": "2018-11-29 10:34:52",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9320",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "168",
                                        "DEPT_NM": "Wish",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Wish",
                                        "TYPE": "N002510300",
                                        "SORT": "7",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:46:29",
                                        "UPDATE_TIME": "2018-11-29 10:35:43",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9320",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "169",
                                        "DEPT_NM": "Cdiscount",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Cdiscount",
                                        "TYPE": "N002510300",
                                        "SORT": "3",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:47:09",
                                        "UPDATE_TIME": "2018-11-29 10:34:42",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9320",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "170",
                                        "DEPT_NM": "Shopee",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Shopee",
                                        "TYPE": "N002510300",
                                        "SORT": "6",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2018-11-22 19:47:34",
                                        "UPDATE_TIME": "2018-11-29 10:35:36",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9320",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "220",
                                        "DEPT_NM": "Supporting",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Supporting",
                                        "TYPE": "N002510300",
                                        "SORT": "9",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "160",
                                        "CREATE_TIME": "2019-04-12 14:10:36",
                                        "UPDATE_TIME": "2019-07-03 09:59:03",
                                        "CREATE_USER_ID": "9699",
                                        "UPDATE_USER_ID": "109",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }]
                                }, {
                                    "ID": "162",
                                    "DEPT_NM": "Overseas LC",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "Overseas LC",
                                    "TYPE": "N002510300",
                                    "SORT": "3",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "0000-00-00 00:00:00",
                                    "PAR_DEPT_ID": "159",
                                    "CREATE_TIME": "2018-11-22 19:43:12",
                                    "UPDATE_TIME": "2019-05-22 15:29:25",
                                    "CREATE_USER_ID": "9320",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": [{
                                        "ID": "171",
                                        "DEPT_NM": "Overseas Sourcing",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Overseas Sourcing",
                                        "TYPE": "N002510400",
                                        "SORT": "2",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "162",
                                        "CREATE_TIME": "2018-11-22 19:48:23",
                                        "UPDATE_TIME": "2019-03-27 12:52:30",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "109",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "172",
                                        "DEPT_NM": "Overseas Sales",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Overseas Sales",
                                        "TYPE": "N002510400",
                                        "SORT": "0",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "162",
                                        "CREATE_TIME": "2018-11-22 19:48:48",
                                        "UPDATE_TIME": "2019-05-22 15:29:59",
                                        "CREATE_USER_ID": "9320",
                                        "UPDATE_USER_ID": "9699",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }, {
                                        "ID": "206",
                                        "DEPT_NM": "Japan",
                                        "DEPT_EN_NM": null,
                                        "DEPT_CN_NM": null,
                                        "DEPT_SHORT_NM": "Japan",
                                        "TYPE": "N002510400",
                                        "SORT": "3",
                                        "LEGAL_PERSON": "\u767e\u6653\u751f",
                                        "STATUS": "N001490100",
                                        "DEPT_LEVEL": "3",
                                        "REG_TIME": "0000-00-00 00:00:00",
                                        "PAR_DEPT_ID": "162",
                                        "CREATE_TIME": "2018-12-21 09:52:34",
                                        "UPDATE_TIME": "2019-05-22 15:30:56",
                                        "CREATE_USER_ID": "9645",
                                        "UPDATE_USER_ID": "9699",
                                        "node_type": "dept",
                                        "node_name": "",
                                        "people_employees": [],
                                        "people_num": 0,
                                        "next_nodes": []
                                    }]
                                }, {
                                    "ID": "221",
                                    "DEPT_NM": "Logistics",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "Logistics",
                                    "TYPE": "N002510300",
                                    "SORT": "3",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-04-12 14:16:26",
                                    "PAR_DEPT_ID": "159",
                                    "CREATE_TIME": "2019-04-12 14:16:26",
                                    "UPDATE_TIME": "2019-04-12 14:16:26",
                                    "CREATE_USER_ID": "9699",
                                    "UPDATE_USER_ID": "9699",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }, {
                                "ID": "208",
                                "DEPT_NM": "Product Development",
                                "DEPT_EN_NM": null,
                                "DEPT_CN_NM": null,
                                "DEPT_SHORT_NM": "Product Development",
                                "TYPE": "N002510400",
                                "SORT": "5",
                                "LEGAL_PERSON": "\u767e\u6653\u751f",
                                "STATUS": "N001490100",
                                "DEPT_LEVEL": "1",
                                "REG_TIME": "0000-00-00 00:00:00",
                                "PAR_DEPT_ID": "75",
                                "CREATE_TIME": "2019-03-14 20:38:27",
                                "UPDATE_TIME": "2019-05-24 09:26:23",
                                "CREATE_USER_ID": "109",
                                "UPDATE_USER_ID": "9699",
                                "node_type": "dept",
                                "node_name": "",
                                "people_employees": [],
                                "people_num": 0,
                                "next_nodes": [{
                                    "ID": "211",
                                    "DEPT_NM": "New Product",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "New Product",
                                    "TYPE": "N002510400",
                                    "SORT": "1",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-03-14 21:25:07",
                                    "PAR_DEPT_ID": "208",
                                    "CREATE_TIME": "2019-03-14 21:25:07",
                                    "UPDATE_TIME": "2019-03-14 21:25:07",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "109",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }, {
                                    "ID": "212",
                                    "DEPT_NM": "ODM Development",
                                    "DEPT_EN_NM": null,
                                    "DEPT_CN_NM": null,
                                    "DEPT_SHORT_NM": "ODM Development",
                                    "TYPE": "N002510400",
                                    "SORT": "2",
                                    "LEGAL_PERSON": "\u767e\u6653\u751f",
                                    "STATUS": "N001490100",
                                    "DEPT_LEVEL": "2",
                                    "REG_TIME": "2019-03-14 21:26:29",
                                    "PAR_DEPT_ID": "208",
                                    "CREATE_TIME": "2019-03-14 21:26:29",
                                    "UPDATE_TIME": "2019-03-14 21:26:29",
                                    "CREATE_USER_ID": "109",
                                    "UPDATE_USER_ID": "109",
                                    "node_type": "dept",
                                    "node_name": "",
                                    "people_employees": [],
                                    "people_num": 0,
                                    "next_nodes": []
                                }]
                            }]
                        }]
                    }
                    var depData = res.data.data;
                    /**
                     * 因有些负责人没有ID情况  所以重新设置新的ID
                     * @param data
                     * @param newId
                     */
                    var setId = function (data, newId) {
                        for (var i = 0; i < data.length; i++) {
                            data[i].NEW_ID = newId ? newId + '-' + (i + 1) : (i + 1).toString();
                            data[i].NEW_PID = newId ? newId : i.toString();
                            /**
                             * 判断是否有部门和人员同级别的情况
                             * true-->插入一个空部门
                             * 即人员展示位置
                             * @param hide是否展示站部门
                             */
                            if (data[i].people_employees && data[i].people_employees.length) {
                                for (var per = 0; per < data[i].people_employees.length; per++) {
                                    if (!data[i].people_employees[per].employee_type_id) {
                                        data[i].next_nodes.push({
                                            DEPT_NM: "",
                                            NEW_ID: "",
                                            next_nodes: [],
                                            hide: true,
                                            SORT:9999,
                                        })
                                        break;
                                    }
                                }
                            }
                            if (data[i].next_nodes.length) {
                                setId(data[i].next_nodes, data[i].NEW_ID);
                            }
                        }
                    };
                    setId(depData);
                    var resData = res.data.data;
                    /**
                     * 重新组织数据归类
                     */
                    var rootData = dep.callForm(depData);
                    //通过sort字段排序部门顺序
                    var loopSort = function (data) {
                        data.sort(function (a, b) { return a.sort - b.sort; })
                        for (var i = 0; i < data.length; i++) {
                            if (data[i].children && data[i].children.length) {
                                loopSort(data[i].children)
                            }
                        }
                    }
                    loopSort(rootData);

                    //自定义参数
                    var option = {
                        width: 140,         //方框宽度
                        height: 0,         //方框高度
                        spacing: 30,        //方框横向间距
                        gap: 50,            //方框纵向间距
                        startX: 100,
                        startY: 100,
                        rX: 100
                    };

                    /**
                     * 设置X、Y值
                     * @type {Tree}
                     */
                    var tree = new Tree(rootData[0], option);
                    var drawData = function (data) {
                        tree.setLevel(data);
                        for (var i = 0; i < tree.acSize; i++) {
                            tree.setCoordination(data, (i + 1));
                        }
                        tree.getRX(data);
                        if (option.rX < option.startX) {
                            var pv = option.startX - option.rX;
                            tree.rightMove(data, pv);
                        }
                    };
                    drawData(rootData[0]);
                    //动态设置svg宽度
                    var domTree = $('#tree');
                    domTree.width(rootData[0].x * 2 + 100);
                    domTree.highcharts({
                        chart: {
                            backgroundColor: 'white',
                            events: {
                                load: function () {
                                    var ren = this.renderer, verLen = 50, horLen = 200, gap = 140 / 2,
                                        attrArg = { 'stroke-width': 1, stroke: '#69C0FF', 'stroke-linecap': 'square' },
                                        verFn = function (x, y) {
                                            return ren.path(['M', 0, 0, 'L', 0, verLen / 2]).attr(attrArg).add().translate(x, y);
                                        },
                                        horFn = function (x, y, w) {
                                            var width = w || horLen;
                                            return ren.path(['M', 0, 0, 'L', width, 0]).attr(attrArg).add().translate(x, y);
                                        },
                                        /**
                                         * 绘制方框
                                         */
                                        generateTag = function (param) {
                                            var obj = {
                                                text: '',
                                                x: 0,
                                                y: 0,
                                                background: '#1E7EB4',
                                                stroke: null,
                                                radius: null,
                                            };
                                            Object.assign(obj, param)
                                            var attr = {
                                                'stroke-width': 1,
                                                'stroke': obj.stroke || obj.background,
                                                'stroke-linecap': 'square',
                                                'fill': obj.background,
                                                'r': obj.radius || 30,
                                                'padding': 10,
                                                'text-align': 'center',
                                                'width': 120
                                            };
                                            return ren.label(obj.text, 0, 0, 'rect', 5, 5, true, false, obj.className).attr(attr).add().translate(obj.x, obj.y);
                                        };

                                    //绘制
                                    /**
                                     * 存储方式   {id:height} 方便取对应高度
                                     * allNodes 记录所有绘制的节点  
                                     * managerNodes 记录所有部门负责人 
                                     */
                                    var allNodes = {}, managerNodes = {};
                                    function draw(data) {
                                        var textStyle = "margin: 5px 0; text-align: center; cursor: pointer; white-space: normal; width: 130px; font-family: PingFang-SC-Heavy; font-size: 13px; letter-spacing: 0;",
                                            fontWeight = "font-weight: 600;",
                                            whiteColor = "color: #FFFFFF;",
                                            blueColor = "color: #096DD9;",
                                            idStyle = " color:transparent; text-align:center; cursor:pointer; width: 130px;";
                                        var axisDepartmentY = 0,    //部门Y轴高度
                                            parentIds = getParentIds(data),    //获取所有父节点ID
                                            axisManagerY = 0;       //获取所有负责人Y轴高度

                                        parentIds.forEach(function (item) {
                                            axisDepartmentY += (allNodes[item] || 0)
                                            axisManagerY += (managerNodes[item] || 0)
                                        })

                                        
                                        //在上面地阿呆循环时 插入了一个空部门
                                        if (!data.hide) {
                                            var html = "<div style='" + textStyle + whiteColor + "'>" + data.name + "</div><div style='" + whiteColor + textStyle + fontWeight + "'>" + data.peopleNum + "</div>";
                                            var node = generateTag({
                                                text: html,
                                                x: data.x,
                                                y: data.y + axisDepartmentY + axisManagerY,
                                                background: '#EA5513',
                                            });
                                            //绘制隐藏的方框 获取ID时使用
                                            generateTag({
                                                text: "<div style='height:" + (node.height - 20) + "px;" + idStyle + "'>" + data.tId + "</div>",
                                                x: data.x,
                                                y: data.y + axisDepartmentY + axisManagerY,
                                                background: 'transparent',
                                                color:'transparent'
                                            });
                                            allNodes[data.ID] = node.height;
                                        } else {
                                            /**
                                             * 把普通员工岗位相同的归类
                                             * jobArr      归类好的数组
                                             * tmp         去重用的临时对象
                                             */
                                            var jobArr = [], tmp = {}, job1, job2;
                                            for (job1 in data.parent.person) {
                                                var names = [];
                                                for (job2 in data.parent.person) {
                                                    if (data.parent.person[job2].employee_type_id === 0 && data.parent.person[job1].JOB_CD === data.parent.person[job2].JOB_CD) {
                                                        names.push(data.parent.person[job2].EMP_SC_NM);
                                                    }
                                                }

                                                if (names.length && !tmp[names]) {
                                                    tmp[names] = 1
                                                    /**
                                                     * job      岗位
                                                     * names    人员名字
                                                     * department  同级别的部门数量
                                                     * job_rank  职位级别
                                                     */
                                                    jobArr.push({ job: data.parent.person[job1].JOB_CD, names: names, department: data.parent.children, jobRank: (data.parent.person[job1].job_rank || 0) })
                                                }
                                            }
                                            jobArr.sort(function (a, b) { return a.jobRank - b.jobRank });
                                            /**
                                             * employeeCount 统计人员展示次数
                                             * directlyEmployeeNodes 已绘制的人员统计
                                             */
                                            var employeeCount = 0, directlyEmployeeNodes = [];
                                            for (var k = 0; k < jobArr.length; k++) {
                                                /**
                                                 * personLine 判断同级别是否有部门
                                                 * directlyEmployeeHeight 人员统计总高度
                                                 */
                                                var personLine = jobArr[k].department.length > 1 ? 0 : 1, directlyEmployeeHeight = 0;
                                                directlyEmployeeNodes.forEach(function (item) {
                                                    directlyEmployeeHeight += (item.height || 0)
                                                })

                                                verFn(data.x + gap, data.y + axisDepartmentY + directlyEmployeeHeight + axisManagerY + 25 * (employeeCount - 1 - personLine));
                                                var html = "<div style='" + textStyle + blueColor + "'>" + jobArr[k].job + "</div><div style='" + textStyle + blueColor + fontWeight + "'>" + jobArr[k].names + "</div>",
                                                    directlyEmployee = generateTag({
                                                        text: html,
                                                        x: data.x,
                                                        y: data.y + axisDepartmentY + directlyEmployeeHeight + axisManagerY + 25 * (employeeCount - personLine),
                                                        background: '#E6F7FF',
                                                        stroke: '#096DD9',
                                                        radius: 5
                                                    });
                                                directlyEmployeeNodes.push(directlyEmployee);
                                                employeeCount++;
                                            }
                                        }

                                        /**
                                         * axisDepY 叠加统计部门负责人的高度
                                         * axisManagerLineY 获取当前负责人方框高度
                                         */
                                        var axisDepY = 0, axisManagerLineY = 0;
                                        if (data.person) {
                                            //data.person.sort(function (a, b) { return a.job_rank - b.job_rank });
                                            var depNodes = [];
                                            for (var k = 0; k < data.person.length; k++) {
                                                if (data.person[k].employee_type_id == 1) {
                                                    axisDepY = depNodes.reduce(function (init, item) {
                                                        init += item.height;
                                                        return init;
                                                    }, 0)

                                                    verFn(data.x + gap, data.y + axisDepartmentY + node.height + axisManagerY + axisDepY + 25 * depNodes.length);
                                                    var html = "<div style='" + textStyle + whiteColor + "'>" + data.person[k].JOB_CD + "</div><div style='" + whiteColor + textStyle + fontWeight + "'>" + data.person[k].EMP_SC_NM + "</div>",
                                                        depNode = generateTag({
                                                            text: html,
                                                            x: data.x,
                                                            y: data.y + axisDepartmentY + node.height + axisManagerY + axisDepY + 25 * (depNodes.length + 1),
                                                            background: '#096DD9',
                                                            radius: 5
                                                        });
                                                    depNodes.push(depNode)
                                                    managerNodes[data.ID] = managerNodes[data.ID] || 0;
                                                    managerNodes[data.ID] += depNode.height + option.gap / 2;
                                                }
                                            }

                                            var axisManagerLineY = (managerNodes[data.ID] || 0);
                                        }

                                        if (data.children && data.children.length === 1) {
                                            draw(data.children[0]);
                                            verFn(data.x + gap, data.children[0].y + axisDepartmentY + axisManagerY + node.height - 50);
                                            // 判断是否存在 部门和负责人同级别的
                                            if (!data.children[0].hide) {
                                                verFn(data.children[0].x + gap, data.children[0].y + axisDepartmentY + axisManagerY + node.height - 25);
                                            }
                                        } else if (data.children && data.children.length > 1) {
                                            verFn(data.x + option.width / 2, data.y + node.height + axisDepartmentY + axisManagerY + axisManagerLineY);
                                            var zzX = data.children[0].x + gap;
                                            //获取最右X + 1/2宽
                                            var zyX = data.children[data.children.length - 1].x + gap;
                                            //获取横线长度
                                            var horLineLen = zyX - zzX;
                                            //画横线
                                            horFn(zzX, data.y + node.height + axisDepartmentY + option.gap / 2 + axisManagerY + axisManagerLineY, horLineLen);
                                            for (var i = 0; i < data.children.length; i++) {
                                                //画子节点上边竖线
                                                verFn(data.children[i].x + gap, data.y + node.height + axisDepartmentY + option.gap / 2 + axisManagerY + axisManagerLineY);
                                                draw(data.children[i]);
                                            }
                                        }
                                    }
                                    draw(rootData[0])
                                },
                                click: function (event) {
                                    var id = event.target.innerHTML, item = getParent(resData, id);
                                    if (id && item) {
                                        dep.btnGroup(item);
                                    }else if(event.target.tagName == 'DIV'){
                                        _this.$message.warning('只可以操作部门信息，编辑人员信息请到人员列表',2000);
                                    }
                                }
                            }
                        },
                        title: {
                            text: '',
                            style: {
                                color: 'black'
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        exporting: {
                            allowHTML:true,
                            buttons: {
                                contextButton: {
                                    align: 'left',
                                    menuItems: [{
                                        text: '导出为 PNG 图片',
                                        onclick: function () {
                                            this.exportChart({filename:'组织架构'});
                                        }
                                    }, {
                                        text: '导出为 PDF 文件',
                                        onclick: function () {
                                            this.exportChart({ 
                                                type: 'application/pdf',
                                                filename: '组织架构',
                                            });
                                        },
                                        separator: false,
                                    }]
                                }
                            }
                        }
                    });
                });
        },
        btnGroup: function (item) {
            dep.parameter = item;
            dep.btnGroupVisible = true;
        },
        //部门相关选项数据
        getChoice: function () {
            axios.post("/index.php?m=api&a=hr_dept_choice")
                .then(function (res) {
                    if (res.data.code === 200) {
                        dep.depChoice = res.data.data
                    }
                })
        },
        //添加部门
        addDep: function (item) {
            this.depInfo = {};
            this.depDialog = true;
            this.editDep = false;
            for (var k in this.form) {
                if (k === "PAR_DEPT_ID") {
                    Vue.set(this.form, k, item.ID)
                } else {
                    Vue.set(this.form, k, '')
                }
            }
        },
        //修改部门信息
        modDep: function (item) {
            this.depInfo = {};
            for (var k in this.form) {
                if(k == 'LEGAL_PERSON'){
                    Vue.set(this.form, k, item[k].split())
                }else{
                    Vue.set(this.form, k, item[k])
                }
            }
            this.depDialog = true;
            this.editDep = true;
        },
        setAddDep: function () {
            var param = {
                "ID": this.form.ID,
                "DEPT_NM": this.form.DEPT_NM,
                "DEPT_SHORT_NM": this.form.DEPT_SHORT_NM,
                "TYPE": this.form.TYPE,
                "STATUS": this.form.STATUS,
                "PAR_DEPT_ID": this.form.PAR_DEPT_ID,
                "SORT": this.form.SORT,
                "LEGAL_PERSON": this.form.LEGAL_PERSON.join()
            },
                url = this.form.ID ? "hr_dept_edit_one" : "hr_dept_add_one",
                msg = this.form.ID ? "修改成功" : "添加成功";

            axios.post("/index.php?m=api&a=" + url, param)
                .then(function (res) {
                    if (res.data.code === 200) {
                        dep.$message({
                            message: msg,
                            type: 'success'
                        });
                        dep.depDialog = false;
                        dep.btnGroupVisible = false;
                        dep.drawChart();
                    } else {
                        dep.$message({
                            message: res.data.msg,
                            type: 'error'
                        });
                    }
                })
        },
        //删除部门
        delDep: function (item) {
            // this.$confirm('确认要删除<span style="color: #F44336; font-size: 15px; padding: 0 8px;">\"'+ item.DEPT_NM + '\"</span>这个部门吗？', '提示', {
            this.$confirm('确认要删除 \"'+ item.DEPT_NM + '\" 这个部门吗？', '提示', {
                type: 'warning',
                dangerouslyUseHTMLString: true,
            }).then(function () {
                axios.get("/index.php?m=api&a=hr_dept_delete&dept_id=" + item.ID)
                    .then(function (res) {
                        if (res.data.code === 200) {
                            dep.$message({
                                message: '删除成功',
                                type: 'success'
                            });
                            dep.btnGroupVisible = false;
                            dep.drawChart();
                        } else {
                            dep.$message({
                                message: res.data.msg,
                                type: 'error'
                            });
                        }
                    })
            }).catch(function(res){
                console.log(res)
            })

        },
        //设置负责人显示
        setDep: function (item) {
            var employeesData = [];
            this.personList = [];
            console.log("当前项",item);
            if (item.people_employees.length) {
                for (var i = 0; i < item.people_employees.length; i++) {
                    if (item.people_employees[i].employee_type_id === 1) {
                        employeesData.push(item.people_employees[i].EMPL_ID);
                        this.personList.push({
                            EMPL_ID: item.people_employees[i].EMPL_ID,
                            EMP_SC_NM: item.people_employees[i].EMP_SC_NM
                        });
                    }
                }
            }
            this.employeesData = employeesData;
            this.personVisible = true;
            this.depInfo = item;
        },
        //人员搜索
        remoteMethod: function (query) {
            if (query) {
                this.loading = true;
                setTimeout(function () {
                    var deptId =  dep.depInfo.ID ? "&dept_id=" + dep.depInfo.ID : '';
                    axios.post("index.php?m=api&a=hr_dept_search_people&searchdata=" + query + deptId)
                        .then(function (res) {
                            dep.loading = false;
                            dep.btnGroupVisible = false;
                            console.log("部门负责人员",res.data);
                            dep.personList = res.data.data;
                        })
                }, 300)
            } else {
                this.personList = [];
            }
        },
        //设置人员
        setDepPerson: function () {
            axios.post("/index.php?m=api&a=hr_dept_set_person_in_charge&empl_id=" + this.employeesData.join() + "&dept_id=" + this.depInfo.ID)
                .then(function (res) {
                    if (res.data.code === 200) {
                        dep.employeesData = [{ personId: '', typeLevel: '2' }];
                        dep.$message({
                            message: '设置成功',
                            type: 'success'
                        });
                        dep.personVisible = false;
                        dep.btnGroupVisible = false;
                        dep.drawChart()
                    } else {
                        dep.$message({
                            message: res.data.msg,
                            type: 'error'
                        });
                    }
                })
        }
    }
});