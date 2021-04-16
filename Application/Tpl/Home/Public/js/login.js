/**
 * Created by b5m on 2016/7/28.
 */
$(function () {
    if(top !== window){
        top.location.href = '/index.php?m=public&a=login&type=first'
    }
    var version = utils.IEVersion();
    if (version > 0) {
        if (version < 9) {
            layer.open({
                type: 1,
                title: false,
                offset: '160px',
                closeBtn: false,
                area: '350px',
                shade: 0.5,
                id: 'LAY_layuipro',
                btnAlign: 'c',
                moveType: 1,
                content: '<div style="text-align: center; height: 300px; background: #186590; color: #fff; font-size: 17px; line-height: 60px; box-sizing: border-box; padding-top: 60px;">'
                    + '浏览器版本过低，不支持 IE' + utils.IEVersion() + ' 版本</br>'
                    + '请使用 <b style="color:#fb5326;font-size: 18px;">新版本IE浏览器</b> 或 <b style="color:#fb5326;font-size: 18px;">其他浏览器</b></div>'
            });
        } else if (version < 11) {
            var tmep = '<div style="position: fixed; top: 0; background: #FEF282; width: 100%; text-align: center; height: 40px; line-height: 40px; color: #ff5224; font-size: 15px;">'
            +'当前版本较低，某些功能可能无法正常使用。为了更好的操作体验，建议您使用<b> 新版本IE浏览器 </b> 或 <b> 其他浏览器 </b> ' + 
            '</div>';
            $('body').append(tmep)
        }
    }

    $("#login").click(function () {
        var username = $("#username").val();
        var password = $("#password").val();

        var nusername = username.replace(/\s+/g, "");
        var npassword = password.replace(/\s+/g, "");

        if (nusername == '') {
            layer.msg('Please fill in the username');
            $('#username').focus();
            return false;
        }

        if (npassword == '') {
            layer.msg('Please fill in the password');
            $('#password').focus();
            return false;
        }

        //todo:记住密码暂时注释。
        // var is_remember = document.getElementsByName('is_remember')[0].checked;
        var is_remember = is_remember ? 1 : 0;

        $.ajax({
            url: requestUrl,
            type: 'post',
            data: { username: nusername, password: npassword, is_remember: is_remember },
            dataType: 'json',
            beforeSend: function () {
                utils.lazy_loading("show")
            },
            success: function (r) {
                utils.lazy_loading()
                if (r.status == 1) {
                    layer.msg(r.info);
                    setTimeout(function () {
                        window.location.href = backUrl;// + "&l="+ r.data;
                    }, 1000);

                } else {
                    layer.msg(r.info);
                    return false;
                }
            }
        })
    })
    $(".remember #is_remember").click(function () {
        if ($(this).prop("checked")) {
            $(this).prev().addClass("remember_checkbox_active");
        } else {
            $(this).prev().removeClass("remember_checkbox_active");
        }
    })
})