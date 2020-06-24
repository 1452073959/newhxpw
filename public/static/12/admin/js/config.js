layui.config({
	base: 'js/modules/'
	,version: false/*new Date().getTime()*/
    ,debug: true
}).use(['adminMain','element','jquery'],function(adminMain) {
    adminMain.init();
    var element = layui.element,
        $ = layui.jquery;
    // 隐藏侧边栏
    $('.admin-side-toggle').on('click', function () {
        var sideWidth = $('#admin-side').width();
        if (sideWidth === 220) {
            $('#admin-body').animate({
                left: '0'
            });
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
    function fullScreen(){
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
    }
});

