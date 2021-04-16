var Vm = new Vue({
    el: '#track_detail',
    data:{
        tableData:'',
        trackDetailData:{},
        tableDataLength:'',
        companyData:'',
        channelData:'',
        DictionaryList:'',
        expeTrack:[],
        
    },

    //加载
    created :function(){
        setTimeout(function () {
            Vm.trackDetail();
        },10)
    },
    methods:{
        //物流详情展示
        trackDetail:function () {
            var Id = $("#id").val();
            var getTrackDetail = '/index.php?g=logistics&m=configs&a=tracking_detail&Id='+Id;
            axios.get(getTrackDetail)
            .then(function(res){
                if (res.data.code===200) {
                    Vm.trackDetailData = res.data.data;
                    if (res.data.data.trackAll.steps) {
                        Vm.expeTrack = res.data.data.trackAll.steps    
                    }   
                }
            })
        },
        goback:function(){
            var trackurl = '/index.php?g=logistics&m=track&a=track_list';
            var route = document.createElement("a");
            route.setAttribute("style", "display: none");
            route.setAttribute("onclick", "backNewtab(this,'API接口日志')");
            route.setAttribute("_href", trackurl);
            route.click();
        }
    
    }
});