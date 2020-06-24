layui.config({
	base: '/static/12/admin/layui/lay/modules'
	,version: false
    ,debug: true
}).use(['carousel','code','element','flow','form','jquery','laydate','layedit','layer','laypage',
        'laytpl','mobile','table','tree','upload','util'],function() {
    var element = layui.element,
        $ = layui.jquery,
        carousel = layui.carousel,
        code = layui.code,
        flow = layui.flow,
        form = layui.form,
        laydate = layui.laydate,
        layedit = layui.layedit,
        laytpl = layui.laytpl,
        mobile = layui.mobile,
        table = layui.table,
        tree = layui.tree,
        upload = layui.upload,
        util = layui.util;


});
