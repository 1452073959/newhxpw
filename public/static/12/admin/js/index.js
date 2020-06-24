layui.use(['element','jquery'],function(){
    var
        element = layui.element,
        $ = layui.jquery;
    $(".layui-nav .layui-nav-item a").on("click",function(){
        $(this).parent("li").siblings().removeClass("layui-nav-itemed");
    });
    // 隐藏侧边栏
    $('.admin-side-toggle').on('click', function () {
        var sideWidth = $('#admin-side').width();
        if (sideWidth === 220) {
            $('#admin-body').animate({
                left: '0'
            }); //admin-footer
            $('#admin-footer').animate({
                left: '0'
            });
            $('#admin-side').animate({
                width: '0'
            });
        } else {
            $('#admin-body').animate({
                left: '220px'
            });
            $('#admin-footer').animate({
                left: '220px'
            });
            $('#admin-side').animate({
                width: '220px'
            });
        }
    });
    // 全屏操作
    $('.admin-body-fullscreen').on('click', function () {
        var docElm = document.documentElement;
        //W3C
        if (docElm.requestFullscreen) {
            docElm.requestFullscreen();
        }
        //FireFox
        else if (docElm.mozRequestFullScreen) {
            docElm.mozRequestFullScreen();
        }
        //Chrome等
        else if (docElm.webkitRequestFullScreen) {
            docElm.webkitRequestFullScreen();
        }
        //IE11
        else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
        layer.msg('按Esc即可退出全屏');
    });
    // 首页选项卡
    function homeTab(){
        var tabTitle = $('.content .tab_title a'),
            tabContent = $('.content .tab_content');
            tabTitle.on('click',function(){
                var index = $(this).index();
                $(this).addClass('on').siblings('a').removeClass('on');
                $(this).parents('.content').find('.tab_content').removeClass('on').eq(index).addClass('on');
            });
    }
    homeTab();
})