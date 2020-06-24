// 全局定义
var $, element, form, laydate, laypage,table,
	adminCommon,adminLayer;
layui.define(['element', 'form', 'laydate', 'jquery', 'table', 'laypage', 'adminCommon','adminLayer'], function(exports){
	// 全局变量赋值
	$ = layui.jquery
	,element = layui.element
	,table = layui.table
	,form = layui.form
	,laydate = layui.laydate
	,laypage = layui.laypage
	// admin模块
	,adminCommon = layui.adminCommon
	,adminLayer = layui.adminLayer;
	// 初始化UrlHash
	adminCommon.initUrlHash();
	// 监听导航变动
	element.on('nav(navBar)', function(elem) {
		adminCommon.initNavForTabUrlHash($(this), elem);
	});
	// 监听tab变动
	element.on('tab(tabMain)', function(data) {
		adminCommon.setHashByLayId();
	});
	exports('adminMain', {
		// 初始化
		init: function() {
			// adminCommon.homePageTpl();
			fullScreen = adminCommon.fullScreen;
			selectTime = adminCommon.selectTime;
			passwordTab = adminCommon.passwordTab;
			checkAll = adminCommon.checkAll;
		}
	});
});
