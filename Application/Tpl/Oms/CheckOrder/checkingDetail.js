'use strict';

var VM = new Vue({
    el: '#checkingDetail',
    components: {},
    data: {
        trackingNumber: '',
        successState: false,
        errorState: false,
        isAdded: [],
        scanNum: "",
        id: "",
        tableData: [],
        orderId: "",
        isAllChecked: false
    },
    methods: {
        getCheckingDetail: function getCheckingDetail() {
            var _this = this;
            var postData = {
                "data": {
                    "query": {
                        "trackingNumber": _this.id
                    }
                }
            };
            axios.post('/index.php?g=oms&m=CheckOrder&a=getScanData', postData).then(function (response) {
                // _this.tableData = [{
                //     skuid: "12312414",
                //     gudsOptUpcId: "9311770589994",
                //     gudsNm: "爱的风格发",
                //     occupyNum: "2",

                // }, {
                //     skuid: "12312414",
                //     gudsOptUpcId: "8801244313923",
                //     gudsNm: "爱的风格发",
                //     occupyNum: "1",
                // }]
                // for (var ind in _this.tableData) {
                //     _this.tableData[ind]['currentNm'] = 0
                //     _this.isAdded[ind] = false;
                // }
                $('#show').css('visibility', 'visible');

                if (response.data.code == 2000) {
                    for (var key in response.data.data.pageData) {
                        _this.tableDataTemp = response.data.data.pageData[key];
                        _this.orderId = key;
                    };

                    for (var key in _this.tableDataTemp) {
                        _this.tableDataTemp[key]['skuid'] = key;
                        _this.tableDataTemp[key]['currentNm'] = 0;

                        _this.tableData.push(_this.tableDataTemp[key]);
                    };
                    VM.trackingNumber = response.data.data.parmeterMap.trackingNumber;
                    $('#checkInput').focus();
                } else {
                    _this.$message.error(_this.$lang(response.data.msg));
                }
            }).catch(function (err) {
                console.log(err);
            });
        },
        doBack: function doBack() {
            var dom = document.createElement('a');
            var _href = "/index.php?g=oms&m=check_order&a=checkingList";
            dom.setAttribute("onclick", "backNewtab(this)");
            dom.setAttribute("_href", _href);
            dom.onclick();
        },
        doChange: function doChange(val) {
            var _this = this;
            var objTemp = {};
            var time01 = null;
            var time02 = null;
            var isAllChecked = true;
            _this.successState = false;
            _this.errorState = false;
            var ordId = '';
            for (var key in _this.tableDataTemp) {
                ordId = _this.tableDataTemp[key].ordId;
            };
            if (val == 'gshopper') {
                var postData = {
                    "data": {
                        "query": {
                            "ordId": [ordId],
                            from:'N001820700'
                        }
                    }
                };
                axios.post('/index.php?g=oms&m=commonData&a=oneKeyThrough', postData)
                    .then(function (response) {
                        if (response.data.data.pageData[0].code == 2000) {
                            sessionStorage.setItem('isFirstOpen', 'false');
                            playVoice('finished')
                            setTimeout(function () {
                                _this.isAllChecked = true;
                            }, 200)
                            setTimeout(function () {
                                _this.doBack();
                            }, 2500)
                        } else {
                            _this.$message.error(_this.$lang('万能码扫描失败') + '：' + response.data.msg);
                        }
                    })
                    .catch(function (err) {
                        console.log(err);
                    });
            } else {
                for (var key in _this.isAdded) {
                    _this.isAdded[key] = false;
                }
                if (val) {
                    var flag = false;
                    for (var ind in _this.tableData) {
                        if (_this.tableData[ind]['gudsOptUpcId'].indexOf(val) != -1 || _this.tableData[ind]['skuid'] == val) {
                        // if (_this.tableData[ind]['gudsOptUpcId'] == val || _this.tableData[ind]['skuid'] == val) {
                            var num = +_this.tableData[ind]['currentNm'] + 1;
                            _this.tableData[ind]['currentNm'] = num;
                            _this.tableData = Object.assign([], _this.tableData)
                            objTemp[_this.tableData[ind]['skuid']] = {
                                "scanNums": num
                            };
                            flag = true;
                            _this.isAdded[ind] = true;
                            _this.isAdded = Object.assign({}, _this.isAdded)
                        }
                        if (_this.tableData[ind]['occupyNum'] != _this.tableData[ind]['currentNm']) {
                            isAllChecked = false;
                            if (_this.tableData[ind]['occupyNum'] < _this.tableData[ind]['currentNm']) {
                                _this.$alert(_this.$lang('扫描数量超过应发数量'));
                            }
                        }
                    }
                    _this.successState = false;
                    _this.errorState = false
                    time01 = setTimeout(function () {
                        if (flag) {
                            _this.successState = true;
                        } else {
                            playVoice('warn')
                            _this.errorState = true
                        }
                    }, 200)

                    time02 = setTimeout(function () {
                        if (flag) {
                            _this.successState = false;
                        } else {
                            _this.errorState = false
                        }
                    }, 700)
                }
                this.scanNum = '';
                if (isAllChecked) {
                    var obj = {};
                    obj[_this.orderId] = objTemp;
                    var postData = {
                        "data": {
                            "query": {
                                "trackingNumber": _this.id,
                                "scanData": obj
                            }
                        }
                    };
                    _this.doCheckOrder(postData)
                }
            }
        },
        doCheckOrder: function doCheckOrder(postData) {
            var _this = this;
            if (!_this.isAllChecked) {
                axios.post('/index.php?g=oms&m=CheckOrder&a=scanCheckOrder', postData)
                    .then(function (response) {
                        if (response.data.code == 2000) {
                            playVoice('finished')
                            sessionStorage.setItem('isFirstOpen', 'false');
                            _this.errorState = false;
                            _this.successState = false;
                            setTimeout(function () {
                                _this.isAllChecked = true;
                            }, 1000)
                            setTimeout(function () {
                                _this.doBack();
                            }, 3000)
                        } else {
                            playVoice('warn')

                            _this.errorState = false;
                            _this.successState = false;
                            _this.$alert(response.data.msg);

                        }
                    })
                    .catch(function (err) {
                        console.log(err);
                    });
            }
        },
        doBlur: function doBlur() {
            this.$refs.checkInput.$el.querySelector('input').focus();
        }
    },
    computed: {},
    created: function created() {
        var _this = this;
        _this.id = getQueryVariable('id');
        _this.getCheckingDetail();
    }

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
    return (false);
}

function playVoice(type) {
    var borswer = window.navigator.userAgent.toLowerCase();
    var src = "";

    if (type == "finished") {
        src = './Application/Tpl/Oms/CheckOrder/Win.wav'
    } else if (type == "warn") {
        src = './Application/Tpl/Oms/CheckOrder/Warn.wav'
    }
    if (borswer.indexOf("ie") >= 0) {
        //IE内核浏览器  
        var strEmbed = '<embed id="audioPlay" name="embedPlay" src="' + src + '" autostart="true" hidden="true" loop="false"></embed>';
        if ($("body").find("embed").length <= 0) {
            $("body").append(strEmbed);
        }
        var embed = document.embedPlay;

        //浏览器不支持 audio，则使用 embed 播放  
        embed.volume = 100;
    } else {
        //非IE内核浏览器  
        var strAudio = "<audio id='audioPlay' src='" + src + "' hidden='true'>";
        if ($("body").find("audio").length <= 0) {
            $("body").append(strAudio);
        }
        var audio = document.getElementById("audioPlay");
        //浏览器支持 audio  

        audio.play();
    }
    setTimeout(function () {  
        $('#audioPlay').remove()
    },1000)
}  
