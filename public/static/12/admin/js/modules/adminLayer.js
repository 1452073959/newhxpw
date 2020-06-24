layui.define(['layer'], function(exports) {
    var $ = layui.jquery
        ,layer = layui.layer;
    exports('adminLayer', {
        showDatas: function(url,title) {
            $('#tpl').load(url, function(tplContent){
                layer.open({
                    type: 1
                    ,shape:0.2
                    ,shadeClose:true
                    ,maxmin: true
                    ,area:['auto !important','auto !important']
                    ,title: title
                    ,content: tplContent
                });
            });
        }
    });
 });