 layui.define(['layer', 'element'], function(exports) {
    var $ = layui.jquery
        ,layer = layui.layer
        ,element = layui.element;
    exports('adminCommon', {
        // 首页默认加载解析
        // homePageTpl: function() {
        //     $('#main')
        //     .find('.layui-tab-item')
        //     .eq(0)
        //     .load('./page/home.html?v=' + new Date().getTime());
        // },
        // 导航模板加载解析
        reloadTpl: function(url, filter, title, layId) {
            $('#tpl').load(url, function(tpl){
                element.tabAdd(filter, {
                    title: title,
                    content: tpl,
                    id: layId
                });
                element.tabChange(filter, layId);
            });
        }
        // 刷新浏览器历史，去除hash
        ,reloadHistory: function(callback) {
            history.replaceState(null, '', location.pathname + location.search);
            callback && callback();
        }
        ,initUrlHash: function() {
            var _hash = location.hash;
            var _findLayNavUrlHash = $('[data-hash="' + _hash.replace('', '') + '"]');

            if (_hash && _findLayNavUrlHash.length > 0) {
                var _url = _findLayNavUrlHash.data('url') + '?v=' + new Date().getTime();
                var _title = _findLayNavUrlHash.find('span').text();
                var _layId = _findLayNavUrlHash.data('id');

                adminCommon.reloadTpl(_url, 'tabMain', _title, _layId);
            }
        }
         // 初始化设置导航链接Tab的Hash值
        ,initNavForTabUrlHash: function(_this, elem) {
            var url = _this.find('a').data('url') + '?v=' + new Date().getTime();
            var urlHash = _this.find('a').data('hash');
            var title = elem.find('span').text();
            var layId = elem.find('a').attr('data-id');
            var isTabShow = $('#tabBody').children('li[lay-id="' + layId + '"]').length;
            layer.msg(title);
            if (!isTabShow) {
                adminCommon.reloadTpl(url, 'tabMain', title, layId);
            }
            element.tabChange('tabMain', layId);
            adminCommon.reloadHistory(function(){
                location.hash = urlHash;
            });
        }
        // 根据lay-id找到导航对应的hash
        ,setHashByLayId: function() {
            var _findLayId = $('.layui-tab-title').find('.layui-this').attr('lay-id');
            var _findUrlHash = $('.layui-nav-item').find('a[data-id="' + _findLayId + '"]').data('hash');
            if (_findUrlHash) {
                location.hash = _findUrlHash
            } else {
                adminCommon.reloadHistory();
            }
        }
        // 选择日期
        ,selectTime: function(){
            lay('.date-select').each(function(){
              laydate.render({
                elem: this
                ,trigger: 'click'
              });
            });
            lay('.month-select').each(function(){
              laydate.render({
                elem: this,
                type: 'month',
                format: 'MM',
                trigger: 'click'
              });
            });
            lay('.time-select').each(function(){
              laydate.render({
                elem: this,
                type: 'time',
                trigger: 'click'
              });
            });
        }
        // 修改密码选项卡
        ,passwordTab: function(){
            var tabLi = $('.change_li .tab_title li'),
                tabCon = $('.change_password');
                tabLi.on('click',function(){
                    var index = $(this).index();
                    $(this).addClass('on').siblings('li').removeClass('on');
                    tabCon.removeClass('on').eq(index).addClass('on');
                });
        }
        ,checkAll: function(){
            var checkBox = $('.check'),
                checkAll = $('.check_all');
                checkAll.on('click',function(){
                    checkBox.prop("checked",$(this).prop("checked"));
                });
        }
    });
});