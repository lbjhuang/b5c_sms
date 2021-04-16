/**
 * Created by b5m on 2017/10/24.
 */
$(function () {
    // #tab_demo 父级id
    // #tab_demo .tabBar span 控制条
    // #tab_demo .tabCon 内容区
    // click 事件 点击切换，可以换成mousemove 移动鼠标切换
    // 1	默认第2个tab为当前状态（从0开始）
    $.Huitab("#tab_demo .tabBar span", "#tab_demo .tabCon", "current", "click", "0")
});

