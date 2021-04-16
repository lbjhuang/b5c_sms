"use strict";

var VM = new Vue({
    el: '#orderPrint',
    components: {},
    data: {
        ids: [],
        gridDataTemp: {},
        gridData: [],
        printData: {},
        totalQuantityOfGoods: 0
    },
    methods: {
        //打印预览
        toDoPrint: function toDoPrint() {
            var _this = this;
            var postData = {
                "data": {
                    "query": {
                        "ordId": _this.ids
                    }
                }
            };

            axios.post('/index.php?g=oms&m=picking&a=previewOrder', postData)
                .then(function (response) {
                    if (response.data.code == 2000) {
                        _this.dialogTableVisible = true;
                        _this.gridDataTemp = response.data.data;
                        _this.gridData = [];
                        for (var key in _this.gridDataTemp.pageData) {
                            _this.gridData.push(_this.gridDataTemp.pageData[key]);
                            _this.totalQuantityOfGoods += _this.gridDataTemp.pageData[key]['nums'];
                        }
                        $('#orderPrint').css('visibility', 'visible')

                    _this.printData = {
                        "data": {
                            "query": {
                                "ordId": _this.ids,
                                "pickingNo": _this.gridDataTemp.pickingNo
                            }
                        }
                    };
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
                }
            }).catch(function (err) {
                console.log(err);
            });
        },
        //打印
        doPrint: function doPrint() {
            var _this = this;
            var currentWindow = window;
            $.ajax({
                type: "post",
                url: "/index.php?g=oms&m=picking&a=printOrders",
                async: false,
                data: _this.printData,

                success: function success(data) {
                    if (data.code == 2000) {
                        var newWindow = window.open('', '_blank'); //打开新窗口
                        var codestr = document.getElementById("print-container").innerHTML; //获取需要生成pdf页面的div代码   
                        newWindow.document.write('<link rel="stylesheet" type="text/css" href="./Application/Tpl/Oms/Picking/pickingList.css">');
                        newWindow.document.write(codestr); //向文档写入HTML表达式或者JavaScript代码
                        newWindow.document.write('<script type="text/javascript">this.opener.location.reload()</scipt>');
                        //关闭document的输出流, 显示选定的数据
                        // setTimeout(function () {
                        newWindow.document.close();

                        // },100)
                        newWindow.onload = function () {
                          
                            this.opener.document.addEventListener('visibilitychange',function () {
                                if(!newWindow.opener.document.hidden){
                                    newWindow.close();
                                    var dom = newWindow.opener.document.createElement('a');
                                    var _href = "/index.php?g=oms&m=picking&a=pickingList";
                                    dom.setAttribute("onclick", "backNewtab(this)");
                                    dom.setAttribute("_href", _href);
                                    dom.onclick();
                                }
                            });
                            newWindow.print(); //打印当前窗口 
                        };
                    } else {
                        _this.$message.error($lang(data.msg));
                    }
                }
            });
        },
        checkIsNone: function checkIsNone() {
            if (!this.multipleSelection) {
                return false;
            }
        }
    },
    created: function created() {
        this.ids = getQueryVariable('ids').split('-');
        this.toDoPrint();
    },
    beforeCreate: function beforeCreate() {},

    mounted: function mounted() {},
    computed: {}
});

function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) {
            return pair[1];
        }
    }
    return false;
}