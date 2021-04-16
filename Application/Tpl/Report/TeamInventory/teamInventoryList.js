var VM = new Vue({
    el: '#statistics',
    data() {
        return {
            search: {
                api_platform_cd: 'N002850001', // api对接平台cd
                date: [], // 查询数据的时间
                type: 'order_no', // 类型，order_no为订单号，logistics_no为物流运单号
                value: '', // 订单号
            },
            pageIndex: 1,
            pageSize: 10,
            tableData: [],
            total: 0,
            loading: true,
            average: 0, // 日均订单量
            apiTotal: '', // api合计次数
            trackIndex: 4, // 物流轨迹显示多少条标识
            trackData: [], // 物流轨迹数据

        }
    },
    watch: {
        'search.type': function() {
            this.search.value = '';
        }
    },
    methods: {
        // 去现存量页面  传值  销售团队代码 team
        toStock(team) {
            var dom = document.createElement('a');
            var _href = "/index.php?m=stock&a=existing_extend&team=" + team;

            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('现存量') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // 去采购在途页面  传值  销售团队代码 team
        toOnway(team) {
            var dom = document.createElement('a');
            var _href = "/index.php?g=report&m=onway&a=onway_list&team=" + team;
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('在途报表') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },
        // 去调拨在途页面  传值  销售团队代码 team
        toWarehousse(team) {
            var dom = document.createElement('a');
            var _href = "/index.php?m=warehouse&a=onwayIndex&team=" + team;
            dom.setAttribute("onclick", "opennewtab(this,'" + this.$lang('在途库存') + "')");
            dom.setAttribute("_href", _href);
            dom.click();
        },

        // 查询
        onSubmit() {
            this.loading = true;
            var _this = this;
            axios.get("/index.php?g=report&m=teamInventory&a=list_data").then(function(res) {
                _this.loading = false;
                console.log(res);
                if (res.status === 200 && res.data.code === 2000 && res.data.data.data.list) {
                    _this.tableData = res.data.data.data.list.map(item => {
                        return {
                            sale_team: item.sale_team,
                            sale_team_code: item.sale_team_code,
                            standing_existing: _this.format(Number(item.standing_existing).toFixed(2)),
                            purchase_onway: _this.format(Number(item.purchase_onway).toFixed(2)),
                            allocation_onway: _this.format(Number(item.allocation_onway).toFixed(2)),
                            sum: _this.format((Number(item.standing_existing) + Number(item.purchase_onway) + Number(item.allocation_onway)).toFixed(2)),
                            // sum: _this.addNum(Number(item.standing_existing), Number(item.purchase_onway), Number(item.allocation_onway))
                        }
                    });
                };
            })
        },
        // 数字加上千分位
        format(num) {
            var num1 = num.split('.')[0] + ''; //数字转字符串  
            var str = ""; //字符串累加  
            for (var i = num1.length - 1, j = 1; i >= 0; i--, j++) {
                if (j % 3 == 0 && i != 0) { //每隔三位加逗号，过滤正好在第一个数字的情况  
                    str += num1[i] + ","; //加千分位逗号  
                    continue;
                }
                str += num1[i]; //倒着累加数字
            }
            return str.split('').reverse().join("") + '.' + num.split('.')[1]; //字符串=>数组=>反转=>字符串  
        },
        // 去除千分位
        delcommafy(num) {
            console.log(num);
            // if((num+"").Trim()==""){

            // return "";

            // }
            num = num.toString();
            num = num.replace(/,/gi, '');

            return num;
        },

        // 自定义合计
        getSummaries(param) {
            // console.log(param);
            const { columns, data } = param;
            const sums = [];
            columns.forEach((column, index) => {
                if (index === 0) {
                    sums[index] = '合计：';
                    return;
                }
                const values = data.map(item => {
                    // console.log(item[index])
                    console.log(item[column.property]);
                    return Number(this.delcommafy(item[column.property]))
                        // return item.one
                });
                console.log(values)
                if (!values.every(value => isNaN(value))) {
                    sums[index] = values.reduce((prev, curr) => {
                        console.log(prev, curr)
                        const value = Number(curr);
                        if (!isNaN(value)) {
                            return prev + curr;
                        } else {
                            return prev;
                        }
                    }, 0);
                    sums[index] = this.format(sums[index].toFixed(2));
                } else {
                    sums[index] = 'N/A';
                }
            });

            return sums;
        }
    },

    created() {
        this.onSubmit();
    }
})