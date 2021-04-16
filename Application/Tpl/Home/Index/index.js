//打印界面功能
function Print() {
    var hrefStr = "",
        frameSrc, contentHtml;
    $("#min_title_list li").each(function(key, value) {
        var findActive = $(value).hasClass('active');
        if (findActive) {
            hrefStr = $(value).find("span").data("href")
        }
    });

    $(document.getElementsByTagName("iframe")).each(function(key, value) {
        frameSrc = $(value).attr('src');
        if (frameSrc === hrefStr) {
            contentHtml = $(value.contentDocument).find("html").html()
        }
    });
    if (contentHtml) {
        var printCont = window.open("print.htm", "print");
        printCont.document.write(contentHtml);
        printCont.document.close();
        setTimeout(function() {
            printCont.print();
        }, 300);
    }
}
/*资讯-添加*/
function article_add(title, url) {
    var index = layer.open({
        type: 2,
        title: title,
        content: url
    });
    layer.full(index);
}
/*图片-添加*/
function picture_add(title, url) {
    var index = layer.open({
        type: 2,
        title: title,
        content: url
    });
    layer.full(index);
}
/*产品-添加*/
function product_add(title, url) {
    var index = layer.open({
        type: 2,
        title: title,
        content: url
    });
    layer.full(index);
}
/*用户-添加*/
function member_add(title, url, w, h) {
    layer_show(title, url, w, h);
}

/*管理员-编辑*/
function admin_edit(title, url, id, w, h) {
    layer_show(title, url, w, h);
}

function displaynavbar(obj) {
    //mean-box 是二级列表的box，如果dom上没有tab-list-id，说明二级列表是空白，禁止展开
    var tabListId= $('#mean-box').attr('tab-list-id')
    if(!tabListId){
        return
    }
    if($(obj).attr('bool') === 'false'){
        $('#iconshouqix').removeClass('iconcaidanlanshouqix').addClass('iconcaidanlanzhankaix')
        $('#mean-box').removeClass('mean-box').addClass('mean-box-open').show().css('top',68)
        $('.Hui-article-box').css('left',269)
        $('[tabid='+tabListId+']').addClass('nav-tab-click')
        $(obj).attr('bool','true')
    }else{
        $('#iconshouqix').removeClass('iconcaidanlanzhankaix').addClass('iconcaidanlanshouqix')
        $('#mean-box').removeClass('mean-box-open').addClass('mean-box').hide()
        $('.Hui-article-box').css('left',112)
        $('.nav-tab-click').removeClass('nav-tab-click')
        $(obj).attr('bool','false')
    }
    return
    if ($(obj).hasClass("open")) {
        $(obj).removeClass("open");
        $("body").removeClass("big-page");
        $(".slideBar").hide();
        $(".slideBar dl").remove();
    } else {
        $(obj).addClass("open");
        $("body").addClass("big-page");
        var icon = $("aside dt i:first-child").text();
        var iconClass = $("aside dt i:first-child");
        var iconHtml = "";
        for (var i = 0, len = icon.length; i < len; i++) {
            iconHtml += "<dl><dt><i class=" + iconClass[i].className + ">" + icon[i] + "</i></dt></dl>";
        }
        $(".slideBar").append(iconHtml).show(500).click(function() {
            $(obj).removeClass("open");
            $("body").removeClass("big-page");
            $(".slideBar").hide();
            $(".slideBar dl").remove();
        })
        $('aside dl dd li').each(function(){
            var selectMenu = $(this).hasClass('active');
            if(selectMenu){
                $('.slideBar dt i').eq($(this).parents('dl').index()).css({color:'#1E7EB4'});
                return false;
            }
        })
    }
}
$().ready(function() {
    //获取当前语言，获取langJSON
    var postHash =  {
        "data_type":"get_hash"
    };
    var postData = {
        "data_type":"get_data"
    }
    $.ajax({
       url: '/index.php?&g=common&m=language&a=index',
       type: 'post',
       data:JSON.stringify(postHash),
       dataType: 'json',
       beforeSend: function(request) {
           request.setRequestHeader("Content-Type", "application/json");
       },
       success: function (data) {
          if(data.code === 1){
              localStorage.setItem('hashJSON', JSON.stringify(data.data));
          }
       }
    })
    $.ajax({
        url: '/index.php?&g=common&m=language&a=index',
        type: 'post',
        data:JSON.stringify(postData),
        dataType: 'json',
        beforeSend: function(request) {
            request.setRequestHeader("Content-Type", "application/json");
        },
        success: function (data) {
           if(data.code === 1){
               localStorage.setItem('dataJSON', JSON.stringify(data.data));
           }
        }
     })
    $("aside dd ul li").click(function() {
        $("aside dd ul li").each(function(){
            $(this).removeClass('active');
        })
        setTimeout(function() {
            $("section ul li").unbind('click').click(function() {
                var activeLi = $(this).find('span').data('href'),
                    dt = '',dd = '',li= '',iconIndex ='';
                $('.slideBar dt i').each(function(index){
                    $(this).css({color:'#E8E6E6'})
                })
                $("aside dl a").each(function(index) {
                    $(this).parents('li').removeClass('active');
                    $(this).parents('dl').find('dt').removeClass('selected')
                    $(this).parents('dd').css({display:'none'});
                    if (activeLi === $(this).attr('_href')) {
                        iconIndex = $(this).parents('dl').index();
                        dt = $(this).parents('dl').find('dt');
                        dd = $(this).parents('dd');
                        li = $(this).parents('li');
                    }else{
                        $(this).parents('li').removeClass('active');
                        $(this).parents('dt').removeClass('selected')
                        $(this).parents('dd').css({display:'none'});
                    }
                });
                if(dt){
                    dt.addClass('selected')
                    dd.css({display:'block'});
                    li.addClass('active');
                }
                $('.slideBar dt i').eq(iconIndex).css({color:'#1E7EB4'})
            })
        }, 500);
        $(this).addClass('active');
    });
    $(".Hui-aside .menu_dropdown").on("click", "#menu-article dd ul li", function() {
        $(this).siblings().removeClass("active")
        $(this).addClass("active")
    })
    $(".navbar_userInfo").hover(function() {
        $(this).find("dl").show();
        $(this).find("span i").css({ "transform": "rotateY(90deg)", "transition": "2s" })
    }, function() {
        $(this).find("dl").hide()
        $(this).find("span i").css({ "transform": "rotateY(0deg)" })
    });


    $(".index_reload button").click(function() {
        $("#iframe_box .show_iframe").each(function() {
            if ($(this).css("visibility") != "hidden") {
            // if ($(this).css("display") == "block") {
                var iframe = $(this).find('iframe');
                //商品模块个别页面 单独使用reload()
                if (/#/.test(iframe.attr('src'))) {
                    iframe[0].contentWindow.location.reload();
                } else {
                    iframe[0].contentWindow.location = iframe.attr('src');
                }
                // console.log(iframe[0].contentWindow.localStorage.page)
            }
        })
    })
    if ($.trim($("#languagesType").html()) == 'Chinese') {
        var m_loginname = $("#scName").html();
        $.post("/index.php?m=api&a=business_card&prepared_by=" + m_loginname, function(res) {
            res = JSON.parse(res)
            if (res.code === 200) {
                $("#scName").html(res.data.EMP_SC_NM);
            }
        });
    }


});

//获取cookie名称
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = $.trim(ca[i]);
        if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
    }
    return "";
}

window.onclick = function() {
    var cookieFail = getCookie('PHPSESSID');
    if (!cookieFail) {
        window.location.reload();
    }
};
