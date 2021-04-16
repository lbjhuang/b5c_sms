/*H-ui.admin.js v2.3.1 date:15:42 2015.08.19 by:guojunhui*/
/*获取顶部选项卡总长度*/
function tabNavallwidth() {
    var taballwidth = 0,
        $tabNav = $(".acrossTab"),
        $tabNavWp = $(".Hui-tabNav-wp"),
        $tabNavitem = $(".acrossTab li"),
        $tabNavmore = $(".Hui-tabNav-more");
    if (!$tabNav[0]) { return }
    $tabNavitem.each(function(index, element) {
        taballwidth += Number(parseFloat($(this).width() + 60))
    });
    $tabNav.width(taballwidth + 25);
    var w = $tabNavWp.width();
    if (taballwidth + 25 > w) {
        $tabNavmore.show()
    } else {
        $tabNavmore.hide();
        $tabNav.css({ left: 0 })
    }
}

/*左侧菜单响应式*/
function Huiasidedisplay() {
    if ($(window).width() >= 768) {
        $(".Hui-aside").show()
    }
}

function getskincookie() {
    var v = getCookie("Huiskin");
    if (v == null || v == "") {
        v = "default";
    }
    $("#skin").attr("href", "../Public/skin/" + v + "/skin.css");
}
$(function() {
    getskincookie();
    //layer.config({extend: 'extend/layer.ext.js'});
    Huiasidedisplay();
    var resizeID;
    $(window).resize(function() {
        clearTimeout(resizeID);
        resizeID = setTimeout(function() {
            Huiasidedisplay();
        }, 500);
    });

    $(".Hui-nav-toggle").click(function() {
        $(".Hui-aside").slideToggle();
    });
    $(".Hui-aside").on("click", ".menu_dropdown dd li a", function() {
        if ($(window).width() < 768) {
            $(".Hui-aside").slideToggle();
        }
    });
    /*左侧菜单*/
    if(typeof $.Huifold == 'function'){
        $.Huifold(".menu_dropdown dl dt", ".menu_dropdown dl dd", "fast", 1, "click");
    }
    /*选项卡导航*/

    $(".Hui-aside").on("click", ".menu_dropdown a", function() {
        if ($(this).attr('_href')) {
            var bStop = false;
            var bStopIndex = 0;
            var _href = $(this).attr('_href');
            var _titleName = $(this).html();
            var topWindow = $(window.parent.document);
            var show_navLi = topWindow.find("#min_title_list li");
            show_navLi.each(function() {
                if ($(this).find('span').attr("data-href") == _href) {
                    bStop = true;
                    bStopIndex = show_navLi.index($(this));
                    return false;
                }
            });
            if (!bStop) {
                creatIframe(_href, _titleName);
                min_titleList();
            } else {
                show_navLi.removeClass("active").eq(bStopIndex).addClass("active");
                var iframe_box = topWindow.find("#iframe_box");
                iframe_box.find(".show_iframe").hide().eq(bStopIndex).show() //左侧导航切换 不刷新页面
                // iframe_box.find(".show_iframe").hide().eq(bStopIndex).show().find("iframe").attr("src", _href);
            }
        }
        $(document).click(function(e) {
            var closeAll = $("#closeAll");
            if (closeAll[0] !== e.target && !$.contains(closeAll[0], e.target)) {
                closeAll.hide();
            }
        });
        var tab = $("#Hui-tabNav ul li");
        var closeAll = $("#closeAll");
        tab.bind("contextmenu", function() {
            return false;
        });
        tab.mousedown(function(e) {
            var self = $(this);
            if (self[0] == tab[0]) return false;
            var left = 0,
                offsetLeft = $("aside.Hui-aside")[0].offsetLeft;
            if (offsetLeft == 0) {
                left = e.clientX - 200
            } else {
                left = e.clientX - 50;
            };
            if (e.which == 3) {
                closeAll.css({ display: "block", left: left });
                var closeButton = $("#closeAll .but-div")[0];
                var liSrc = self.find("span").attr("data-href").split("&");
                var liSrcPar = liSrc[liSrc.length - 1];
                //关闭当前
                $(closeButton.children[0]).unbind("click").bind("click", function() {
                    $("#iframe_box iframe").each(function(index, element) {
                        var iframSrc = element.src.split("&");
                        var iframSrcPar = iframSrc[iframSrc.length - 1];
                        if (liSrcPar == iframSrcPar) {
                            //判断页面往前跳 还是往后条
                            if (self.next().length == 0) {
                                $(this).parent().prev().show();
                                //判断是否激活的页面
                                if (self.hasClass("active")) {
                                    self.prev().addClass("active");
                                    self.remove();
                                    $(this).parent().remove();
                                } else {
                                    self.remove();
                                    $(this).parent().remove();
                                }
                            } else {
                                $(this).parent().next().show();
                                if (self.hasClass("active")) {
                                    self.next().addClass("active");
                                    $(this).parent().remove();
                                    self.remove();
                                } else {
                                    self.remove();
                                    $(this).parent().remove();
                                }
                            }
                        }

                        //标签自动排列计算
                        var li = $("#min_title_list").find('li'),
                            wrapWidth = $("#Hui-tabNav").width() - 120;
                        len = li.length,
                            width = (wrapWidth - len * 17) / len - 12;
                        li.each(function(index, tag) {
                            $(tag).width(width);
                        });
                    });
                    $("#closeAll").hide();
                });
                //关闭其他
                $(closeButton.children[1]).unbind("click").bind("click", function() {
                    $("#iframe_box iframe").each(function(index, element) {
                        var iframSrc = element.src.split("&");
                        var iframSrcPar = iframSrc[iframSrc.length - 1];
                        if (liSrcPar == iframSrcPar) {
                            self.siblings().not(":first").remove();
                            $(element).parent().show().siblings().not(":first").remove();
                            if (!self.hasClass("active")) {
                                self.addClass("active");
                            }
                        }
                        //标签自动排列计算
                        var li = $("#min_title_list").find('li'),
                            wrapWidth = $("#Hui-tabNav").width() - 120;
                        len = li.length,
                            width = (wrapWidth - len * 17) / len - 12;
                        li.each(function(index, tag) {
                            $(tag).width(width);
                        });
                    });
                    $("#closeAll").hide();
                });
                //关闭全部
                $(closeButton.children[2]).unbind("click").bind("click", function() {
                    self.parent().children("li:first-child").addClass("active").siblings().remove();
                    $("#iframe_box").children("div:first-child").show().siblings().remove();
                    //标签自动排列计算
                    var li = $("#min_title_list").find('li'),
                        wrapWidth = $("#Hui-tabNav").width() - 120;
                    len = li.length,
                        width = (wrapWidth - len * 17) / len - 12;
                    li.each(function(index, tag) {
                        $(tag).width(width);
                    });
                    $("#closeAll").hide();
                });
            }
        });
        $("#closeAll .cancel").click(function() {
            closeAll.hide();
        });

    });

    function min_titleList() {
        var topWindow = $(window.parent.document);
        var show_nav = topWindow.find("#min_title_list");
        var aLi = show_nav.find("li");
    };

    function creatIframe(href, titleName) {
        var topWindow = $(window.parent.document);
        var show_nav = topWindow.find('#min_title_list');
        //标签自动排列计算
        /*  wrapWidth 父元素宽度 - 激活样式的宽度
         *  len 未激活样式的全部菜单
         *  width 中（17 和 12 分别是为元素和内间距的宽度）
         */
        // debugger;
        var li = show_nav.find('li'),
            wrapWidth = topWindow.find("#Hui-tabNav").width() - 120;
        len = li.length,
            width = (wrapWidth - len * 17) / len - 12;
        if (width < 0) {
            alert('请关闭一些标签,标签太多会导致打开新的无效');
            return false;
        }
        li.each(function(index, tag) {
            $(tag).width(width);
        });

        show_nav.find('li').removeClass("active");
        var iframe_box = topWindow.find('#iframe_box');
        show_nav.append('<li class="active" style="width:' + (width + 12) + 'px" title="' + titleName + '"><span data-href="' + href + '">' + titleName + '</span><b></b></li>');
        tabNavallwidth();
        var iframeBox = iframe_box.find('.show_iframe');
        iframeBox.hide();
        iframe_box.append('<div class="show_iframe"><div class="loading"><svg viewBox="25 25 50 50" class="circular"><circle cx="50" cy="50" r="20" fill="none" class="path"></circle></svg></div><iframe allowfullscreen="true"  webkitallowfullscreen="true" mozallowfullscreen="true" frameborder="0"' +
            ' src=' + href + '   ></iframe></div>');
        var showBox = iframe_box.find('.show_iframe:visible');
        showBox.find('iframe').attr("src", href).load(function() {
            showBox.find('.loading').hide();
            showBox.find('iframe')[0].contentWindow.onclick = function() {
                var cookieFail = getCookie('PHPSESSID');
                if (!cookieFail) {
                    window.location.reload();
                }
            };
        
            // $(showBox.find('iframe')[0].contentDocument).find("html").css('cssText','height: 100% !important; overflow: auto');
            // $(showBox.find('iframe')[0].contentDocument).find("body").css('cssText','height: 100% !important; overflow: auto');
        });
    }

    var num = 0;
    var oUl = $("#min_title_list");
    var hide_nav = $("#Hui-tabNav");
    var dom = $(window.parent.document).find('#iframe_box').find('.show_iframe:visible');
    if(dom.length>0){
        var erdom = $(dom.find('iframe')[0].contentDocument).find('body').find('.toTop')
        $(dom.find('iframe')[0].contentDocument).find('body').on('scroll',function () {
            var top = $(dom.find('iframe')[0].contentDocument).find('body').scrollTop();
            if( top > 0){
                erdom.fadeIn(200)
            }else{
                erdom.fadeOut(200)             
            }
        })
        $(dom.find('iframe')[0].contentDocument).find('body').find('.toTop').click(function(){
            $("html,body").animate({scrollTop:0},"fast");
        })
    }

    $(document).on("click", "#min_title_list li", function() {
        var _this = this;
        var bStopIndex = $(this).index();
        var iframe_box = $("#iframe_box");
        var currentIndex = $("#min_title_list .active").index()
        var currentIndexCoetent = $("#min_title_list .active").text()
        // console.log(bStopIndex)
        // setTimeout(function(){
        //     if($(_this).attr('title') == 'Home Page'){
        //     　　 var _offset = sessionStorage.getItem("offsetTop");
        //         document.querySelectorAll('iframe')[0].contentWindow.scrollTo(0,_offset)
        //     }else if($(_this).attr('title') == '订单列表' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("orderListOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('orderListOffsetTop')
        //     }else if($(_this).attr('title') == '待预派' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("OrderPresentOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('OrderPresentOffsetTop')
        //     }else if($(_this).attr('title') == '待派单' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("OrderPatchOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('OrderPatchOffsetTop')
        //     }else if($(_this).attr('title') == '待开单' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("pendingListOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('pendingListOffsetTop')
        //     }else if($(_this).attr('title') == '待拣货' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("pickingListOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('pickingListOffsetTop')
        //     }else if($(_this).attr('title') == '待分拣' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("PickApartListOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('PickApartListOffsetTop')
        //     }else if($(_this).attr('title') == '待核单' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("checkingListOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('checkingListOffsetTop')
        //     }else if($(_this).attr('title') == '待出库' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("outListOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('outListOffsetTop')
        //     }else if($(_this).attr('title') == '已出库' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("outStorageOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('outStorageOffsetTop')
        //     }else if($(_this).attr('title') == '售后单列表' && bStopIndex>0 && currentIndex!=bStopIndex){
        //         var _offset = sessionStorage.getItem("aftersalelistOffsetTop")
        //         document.querySelectorAll('iframe')[bStopIndex].contentWindow.scrollTo(0,_offset)
        //         // sessionStorage.removeItem('aftersalelistOffsetTop')
        //     }
            
            
        // },50);
    

        $("#min_title_list li").removeClass("active").eq(bStopIndex).addClass("active");
        // iframe_box.find(".show_iframe").hide().eq(bStopIndex).show();
        iframe_box.find(".show_iframe").css("visibility","hidden").eq(bStopIndex).css("visibility","visible");
        iframe_box.find(".show_iframe").css("display","block");
        // iframe_box.find(".show_iframe").css("display","none").eq(bStopIndex).css("display","block");
        var currentIframe = iframe_box.find(".show_iframe").eq(bStopIndex).find('iframe');
        var iframe_boxs = document.querySelector('#iframe_box').querySelectorAll('.show_iframe')
        // for (var item in iframe_boxs) {
        //     iframe_boxs[item].style.display = 'block'
        // }
        // $(currentIframe[0].contentDocument).find("html").css('cssText','height: 100% !important; overflow: auto');
        // $(currentIframe[0].contentDocument).find("body").css('cssText','height: 100% !important; overflow: auto');

    });
    $(document).on("click", "#min_title_list li b", function() {
        var aCloseIndex = $(this).parents("li").index();
        var liLen = $('#min_title_list li').length

        
        
        // 'storage0001 == 'scm需求详情页','storage0002' == '转账换汇详情页','storage0003' == '新调拨详情页';
        var storageHref = $(this).siblings().data('href'),
            locationSearch = '?' + storageHref.split('?')[1],
            urlParams = utils.parseQuery(locationSearch);
        if (urlParams.storageKey) {
            localStorage.setItem(urlParams.storageKey, 1)
        }

        $(this).parent().remove();
        $('#iframe_box').find('.show_iframe').eq(aCloseIndex).remove();
        num == 0 ? num = 0 : num--;
        var li = $("#min_title_list").find('li'),
            wrapWidth = $("#Hui-tabNav").width() - 120;
        len = li.length,
            width = (wrapWidth - len * 17) / len - 12;
      if(len == 1){
        $('#homePage').click();
      }
        li.each(function(index, tag) {
            $(tag).width(width);
        });
        tabNavallwidth();

    });
    $(document).on("dblclick", "#min_title_list li", function() {
        var aCloseIndex = $(this).index();
        var liLen = $('#min_title_list li').length
        
        

        
        var iframe_box = $("#iframe_box");
        if (aCloseIndex > 0) {
            $(this).remove();
            $('#iframe_box').find('.show_iframe').eq(aCloseIndex).remove();
            num == 0 ? num = 0 : num--;
            $("#min_title_list li").removeClass("active").eq(aCloseIndex - 1).addClass("active");
            // iframe_box.find(".show_iframe").css("visibility","hidden").eq(aCloseIndex - 1).show();
            iframe_box.find(".show_iframe").css("visibility","hidden").eq(aCloseIndex - 1).css("visibility","visible");
            // iframe_box.find(".show_iframe").hide().eq(aCloseIndex - 1).show();
            
            var li = $("#min_title_list").find('li')
            len = li.length
            if(len == 1){
                $('#homePage').click();
            }
            
            
            tabNavallwidth();
        } else {
            return false;
        }
    });
    tabNavallwidth();

    $('#js-tabNav-next').click(function() {
        num == oUl.find('li').length - 1 ? num = oUl.find('li').length - 1 : num++;
        toNavPos();
    });
    $('#js-tabNav-prev').click(function() {
        num == 0 ? num = 0 : num--;
        toNavPos();
    });

    function toNavPos() {
        oUl.stop().animate({ 'left': -num * 100 }, 100);
    }

    /*换肤*/
    $("#Hui-skin .dropDown-menu a").click(function() {
        var v = $(this).attr("data-val");
        setCookie("Huiskin", v);
        $("#skin").attr("href", "../Public/skin/" + v + "/skin.css");
    });
});
/*弹出层*/
/*
 参数解释：
 title	标题
 url		请求的url
 id		需要操作的数据id
 w		弹出层宽度（缺省调默认值）
 h		弹出层高度（缺省调默认值）
 */
function layer_show(title, url, w, h) {
    if (title == null || title == '') {
        title = false;
    };
    if (url == null || url == '') {
        url = "404.html";
    };
    if (w == null || w == '') {
        w = 800;
    };
    if (h == null || h == '') {
        h = ($(window).height() - 50);
    };
    layer.open({
        type: 2,
        area: [w + 'px', h + 'px'],
        fix: false, //不固定
        maxmin: true,
        shade: 0.4,
        title: title,
        content: url
    });
}
/*关闭弹出框口*/
function layer_close() {
    var index = parent.layer.getFrameIndex(window.name);
    parent.layer.close(index);
}
/*设置cookie*/
function setCookie(c_name, value, expiredays) {
    var exdate = new Date()
    exdate.setDate(exdate.getDate() + expiredays)
    document.cookie = c_name + "=" + escape(value) +
        ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString())
}
/*获取cookie*/
function getCookie(c_name) {
    if (document.cookie.length > 0) {
        c_start = document.cookie.indexOf(c_name + "=")
        if (c_start != -1) {
            c_start = c_start + c_name.length + 1
            c_end = document.cookie.indexOf(";", c_start)
            if (c_end == -1) c_end = document.cookie.length
            return unescape(document.cookie.substring(c_start, c_end))
        }
    }
    return ""
}
/**
 * o        this
 * title    新tab标题
 * isAside  是否导航栏进入
 * */
function opennewtab(o, title,isAside) {
    /*增加判断，如果是新开在线反馈 页面，新增获取当前url功能*/
    if ($(o).attr("id") == 'toQuestion') {
        var url = $('.Hui-tabNav-wp ul').find('li.active').find('span').data('href');
        setCookie('questionUrl', url, 1);
        $.getJSON('/index.php?g=question&m=question&a=getReturnUrl', function(json, textStatus) {
            if (json.code == 2000) {
                $(o).attr('_href', '/index.php?g=question&m=question&a=' + json.data)
            }
        });
    }
    setTimeout(function() {
        if ($(o).attr('_href')) {
            var bStop = false;
            var bStopIndex = 0;
            var showIframeIndex = 0;
            var _href = $(o).attr('_href');
            var _titleName = $(o).html();
            var topWindow = $(window.parent.document);
            var show_navLi = topWindow.find("#min_title_list li");
            //导航栏中新建标签 导航
            show_navLi.each(function(index, element) {
                if ($(this).find('span').attr("data-href") == _href) {
                    bStop = true;
                    bStopIndex = index;
                    return false;
                }
            });
            if (!bStop) {
                var topWindow = $(window.parent.document);
                var show_nav = topWindow.find('#min_title_list');
                var isMobile = topWindow.find('.Hui-nav-toggle').is(':visible')
                
                //标签自动排列计算
                /*  wrapWidth 父元素宽度 - 激活样式的宽度
                 *  len 未激活样式的全部菜单
                 *  width 中（17 和 12 分别是为元素和内间距的宽度）
                 */
                var li = show_nav.find('li'),
                    wrapWidth = topWindow.find("#Hui-tabNav").width() - 200;
                    len = li.length,
                    width = (wrapWidth - len * 95);
                    console.log(width)
                if (width < 0 && !isMobile) {
                    alert('请关闭一些标签,标签太多会导致打开新的无效');
                    return false;
                }
                li.each(function(index, tag) {
                    $(tag).width(width);
                });
                // console.log(show_nav.find('.active'),show_nav.find('li'), show_nav.find('.active')[0].innerText)
                var iframe_box = topWindow.find('#iframe_box');
                if(isAside) {
                show_nav.find('li').removeClass("active");
                show_nav.append('<li class="active" style="width:' + (width + 12) + 'px" title="' + title + '"><span data-href="' + _href + '">' + title + '</span><div class="radis-left"></div><div class="radis-left-background"></div><div class="radis-right"></div><div class="radis-right-background"></div><b></b></li>');
                } else {
                    show_nav.find('.active').after('<li class="active" style="width:' + (width + 12) + 'px" title="' + title + '"><span data-href="' + _href + '">' + title + '</span><div class="radis-left"></div><div class="radis-left-background"></div><div class="radis-right"></div><div class="radis-right-background"></div><b></b></li>');
                    show_nav.find('.active').first().removeClass("active");
                    // 这个时候对应ifrem还没渲染出来，所以减1
                    showIframeIndex = show_nav.find('li').index(show_nav.find('.active')) - 1;
                    // console.log(show_nav.find('li').index(show_nav.find('.active')));
                }

                var topWindow = $(window.parent.document);
                var taballwidth = 0,
                    $tabNav = topWindow.find('.acrossTab'),
                    $tabNavWp = topWindow.find(".Hui-tabNav-wp"),
                    $tabNavitem = topWindow.find(".acrossTab li"),
                    $tabNavmore = topWindow.find(".Hui-tabNav-more");
                if (!$tabNav[0]) { return }
                $tabNavitem.each(function(index, element) {
                    taballwidth += Number(parseFloat($(this).width() + 60))
                });
                $tabNav.width(taballwidth + 25);
                var w = $tabNavWp.width();
                if (taballwidth + 25 > w) {
                    $tabNavmore.show()
                } else {
                    $tabNavmore.hide();
                    $tabNav.css({ left: 0 })
                }
                var iframeBox = iframe_box.find('.show_iframe');
                // iframeBox.hide();
                iframeBox.css("visibility","hidden")
                // iframe_box.find(".show_iframe").css("visibility","hidden").eq(bStopIndex).css("visibility","visible")
                if(isAside) {
                    iframe_box.append('<div class="show_iframe"><div class="loading"><svg viewBox="25 25 50 50" class="circular"><circle cx="50" cy="50" r="20" fill="none" class="path"></circle></svg></div><iframe allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"' + ' frameborder="0" src=' + _href + ' ></iframe></div>');
                } else {
                    // .eq(showIframeIndex + 1)
                    console.log(showIframeIndex);
                    iframe_box.find('.show_iframe').eq(showIframeIndex).after('<div class="show_iframe"><div class="loading"><svg viewBox="25 25 50 50" class="circular"><circle cx="50" cy="50" r="20" fill="none" class="path"></circle></svg></div><iframe allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"' + ' frameborder="0" src=' + _href + ' ></iframe></div>');
                }
                var show_iframes = window.parent.document.querySelector('#iframe_box').querySelectorAll('.show_iframe')
                var showIndex = ''
                for (var item in show_iframes) {
                    if(show_iframes[item].style && show_iframes[item].style.cssText == ''){
                        showIndex = item
                        show_iframes[item].querySelector('iframe').src = _href
                        // show_iframes[item].querySelector('.loading').style.display = 'none'

                        show_iframes[item].querySelector('iframe').contentWindow.onclick = function() {
                            var cookieFail = getCookie('PHPSESSID');
                            if (!cookieFail) {
                                window.location.reload();
                            }
                        };
                    }
                }
                
                show_iframes[showIndex].querySelector('iframe').onload = function(){
                    show_iframes[showIndex].querySelector('.loading').style.display = 'none'

                };
                
                // var showBox = iframe_box.find('.show_iframe:visible');
                // showBox.find('iframe').attr("src", _href).load(function() {
                //     showBox.find('.loading').hide();
                //     showBox.find('iframe')[0].contentWindow.onclick = function() {
                //         var cookieFail = getCookie('PHPSESSID');
                //         if (!cookieFail) {
                //             window.location.reload();
                //         }
                //     };
                //     // $(showBox.find('iframe')[0].contentDocument).find("html").css('cssText','height: 100% !important; overflow: auto');
                //     // $(showBox.find('iframe')[0].contentDocument).find("body").css('cssText','height: 100% !important; overflow: auto');
                // });
            } else {
                show_navLi.removeClass("active").eq(bStopIndex).addClass("active");
                var iframe_box = topWindow.find("#iframe_box");
                // iframe_box.find(".show_iframe").hide().eq(bStopIndex).show().find("iframe").attr("src", _href);
                iframe_box.find(".show_iframe").css("visibility","hidden").eq(bStopIndex).css("visibility","visible").find("iframe").attr("src", _href);
            }
        }
    }, 100)
}

function changenewtab(o, title) {
    console.log("当前this",o);
    console.log("名称",title);
    if ($(o).attr('_href')) {
        var bStop = false;
        var bStopIndex = 0;
        var _href = $(o).attr('_href');
        var _titleName = $(o).html();
        var topWindow = $(window.parent.document);
        var show_navLi = topWindow.find("#min_title_list li");
        show_navLi.each(function() {
            if ($(this).hasClass('active')) {
                $(this).find('span').html(title)
            }
        })
        var show_iframe = topWindow.find("#iframe_box .show_iframe");
        show_iframe.each(function() {
            if ($(this).css("visibility") != "hidden") {
                var iframe = $(this).find('iframe');
                iframe.attr('src', _href)
                iframe[0].contentWindow.location = iframe.attr('src');
            }
        })
    }
}

function backNewtab(o, title) {
    console.log(111)
    if ($(o).attr('_href')) {
        var bStop = false;
        var bStopIndex = 0;
        var _href = $(o).attr('_href');
        var _titleName = $(o).html();
        var topWindow = $(window.parent.document);
        var show_navLi = topWindow.find("#min_title_list li");
        var backNewtabIndex = 0;
        var show_iframe = topWindow.find("#iframe_box .show_iframe");
        var _hrefYet = '';
        var currentIframe = ""; //当前URL
        show_navLi.each(function() {
            if ($(this).find('span').attr("data-href") == _href) {
                backNewtabIndex = 1;
            }
        })
        if (backNewtabIndex == 1) {
            show_navLi.each(function() {
                if ($(this).hasClass('active')) {
                    currentIframe = $(this).find('span').data("href")
                    $(this).remove()
                }
            })

            show_navLi.each(function() {
                if ($(this).find('span').attr("data-href") == _href) {
                    $(this).addClass('active')
                    show_iframe.each(function() {
                        if ($(this).find("iframe")[0].src.indexOf(_href) > -1) {
                            $(this).removeAttr("style");
                            var iframe = $(this).find('iframe');
                            iframe.attr('src', _href);
                            iframe[0].contentWindow.location = iframe.attr('src');
                        }
                    })
                }
            })

            show_iframe.each(function() {
                if ($(this).find("iframe")[0].src.indexOf(currentIframe) > -1) {
                    $(this).remove();
                }
            })
        } else if (backNewtabIndex == 0) {
            show_navLi.each(function() {
                if ($(this).hasClass('active')) {
                    $(this).find('span').attr("data-href", _href)
                    $(this).find('span').html(title)
                }
            })
            show_iframe.each(function() {
                if ($(this).css("display") == "block") {
                    var iframe = $(this).find('iframe');
                    iframe.attr('src', _href)
                    iframe[0].contentWindow.location = iframe.attr('src');
                }
            })
        }
    }
}

/**
 * 跳转界面
 * newUrl  新跳转的页面
 * title   新界面标题
 * */
// 新开界面
// newTab(newUrl, title) 
// 返回界面
// backTab(newUrl, title)

function backTab(newUrl, title) {
    var topWindow = $(window.parent.document),
        iframs = $(topWindow).find("#iframe_box .show_iframe"),
        tabList = $(topWindow).find("#min_title_list li"),
        a = window.parent.document.createElement("a"),
        currentUrl = "";
    //获取当前激活的URL
    $(tabList).each(function(key, item) {
        if ($(item).hasClass('active')) {
            currentUrl = $(item).find('span').data("href");
        }
    });

    //移除对应的iframe
    $(iframs).each(function(key, item) {
        var iframWarp = $(item).find("iframe")[0].src;
        if (iframWarp.indexOf(currentUrl) > -1 || iframWarp.indexOf(newUrl) > -1) {
            setTimeout(function () {//兼容ie
                item.parentNode.removeChild(item);
            }, 0);
        }
    });
    
    //移除对应的li标签
    $(tabList).each(function(key, item) {
        var herf = $(item).find("span").data("href");
        if (herf.indexOf(currentUrl) > -1 || herf.indexOf(newUrl) > -1) {
            $(item).remove();
        }
    });
    
    a.setAttribute("style", "display: none");
    a.setAttribute("onclick", "opennewtab(this,'" + title + "',"+ true+")");
    a.setAttribute("_href", newUrl);
    a.onclick();
    $(a).remove();
}

/**
 * newUrl  新跳转的页面
 * title   新界面标题
 * */
function newTab(newUrl, title) {
    var a = window.parent.document.createElement("a");
    a.setAttribute("style", "display: none");
    a.setAttribute("onclick", "opennewtab(this,'" + title + "')");
    a.setAttribute("_href", newUrl);
    a.onclick();
    $(a).remove();
}

/**
 * 关闭当前界面
 */

function closeTab() {
    var topWindow = $(window.parent.document),
        iframs = $(topWindow).find("#iframe_box .show_iframe"),
        tabList = $(topWindow).find("#min_title_list li"),
        currentUrl = "";
    //获取当前激活的URL
    $(tabList).each(function (key, item) {
        if ($(item).hasClass('active')) {
            currentUrl = $(item).find('span').data("href");
        }
    });

    //移除对应的iframe
    $(iframs).each(function (key, item) {
        var iframWarp = $(item).find("iframe")[0].src;
        if (iframWarp.indexOf(currentUrl) > -1) {
            setTimeout(function () {//兼容ie
                if ($(item).next().length) {
                    $(item).next().css("visibility","visible");
                    // $(item).next().show();
                }else{
                    $(item).prev().css("visibility","visible");
                    // $(item).prev().show();
                }
                item.parentNode.removeChild(item);
            }, 0);
        }
    });
    // //移除对应的li标签
    $(tabList).each(function (key, item) {
        var herf = $(item).find("span").data("href");
        if (herf.indexOf(currentUrl) > -1) {
            if ($(item).next().length) {
                $(item).next().addClass("active");
            }else{
                $(item).prev().addClass("active");
            }
            $(item).remove();
        }
    });

    if($(topWindow).find("#min_title_list li").length === 1){
        $('#homePage').addClass('active')
    }
}

/**
 * 获取当前激活的tab页面的连接地址
 * @returns {string}
 */
function getTab(){
    var topWindow = $(window.parent.document),
        iframs = $(topWindow).find("#iframe_box .show_iframe"),
        tabList = $(topWindow).find("#min_title_list li"),
        currentUrl = "";
    //获取当前激活的URL
    $(tabList).each(function (key, item) {
        if ($(item).hasClass('active')) {
            currentUrl = $(item).find('span').data("href");
        }
    });
    return currentUrl
}
//document.write('<script src="//static-web.gshopper.com/tongji/stat4.min.js?v=20200512"></script>')
var v =  Number(new Date().getMonth()+''+new Date().getDate());
var t = ''
if(location.host.indexOf('erp.gshopper.com') !== -1){
    t = '<script src="//static-web.gshopper.com/tongji/stat4.min.js?v='+v+'" async></script>'
}else{
   t = '<script src="//stage-static-web.gshopper.com/tongji/stat4.js?v='+v+'" async></script>'
}
document.write(t)
//document.write('<script src="./Application/Tpl/Home/Public/js/stage.js?v='+Date.parse(new  Date())+'"></script>')
