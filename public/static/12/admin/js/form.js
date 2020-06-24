$(document).ready(function () {

	window.step = parseInt($(".next_step").attr('step')); //记录当前步数
	//window.step = 4
	if (typeof(data.step5) !== "undefined") {
		var has_yunying_init = 0;
		$.each(data.step5, function (index, v) {
			if ($.isEmptyObject(v)) {
				return true;
			}

			if (v.id * 1 === 28 && has_yunying_init === 0) {
				has_yunying_init = index + 1;
			}

			if (v.id * 1 === 28 && has_yunying_init > 0 && has_yunying_init !== index + 1) {
				data.step5.splice(index, 1);
			}
		});

		if (!has_yunying_init) {
			data.step5.push({
				id : 28,
				T  : 'yunying',
				R  : 1
			});
		}
	}
	//console.log(data);
	window.item  = data; //接收系统设置数据
	window.items = {
		step1 : step1, //个人信息
		step2 : step2, //联系人信息
		step3 : step3, //职业信息
		step4 : step4, //身份证照片
		step5 : step5 //41是芝麻信用,其他用户不可用 ； 28,运营商
	};
	_init();
});

function _init() {

	init();
	$(".prev_step").on('click', nexts);
	$(".next_step").on('click', nexts);
}

function init() {
	set_guideBar(); //设置顶部导航显示

	window.dealed_data = deal_data('depart'); //处理数据，得到已选择项目数组和未选择项目数组
	set_selected(dealed_data.items_selected, dealed_data.items_notRequired); //显示已经选择项目
	set_notSelected(dealed_data.items_notSelected); //显示未经选择项目
	//	set_drag(); //设置拖动
	//	drag.init('panel');
	//	drag2.init('form');
	set_tab_fun(dealed_data.items_selected); //设置标签切换
	set_step_bottons(); //设置下一步、上一步按钮
	set_items_odd(dealed_data); //防止项目是单数时最后一项居中
	set_prev(dealed_data.items_selected); //设置预览

	settings(); //额外配置

}

function settings() {
	var funs = {
		add_yun : function () { //运营商自动选中

			if ($.inArray('28', dealed_data.items_selected) == -1 && step === 5) {
				$('div[data-id = 28]').prependTo($('#main').find('#form'));
				set_items_odd();
			}
		}
	}
	$.each(funs, function (index) {
		funs[index]()

	})
}

function set_guideBar() {
	$(".pcd").filter('.cur').removeClass('cur');
	$(".pcd").filter(".pcd" + step).addClass('cur');
}

function set_selected(items_selected, items_notRequired) {

	//$(".form").html('');
	var html = '', vhtml = '';
	$.each(items_selected, function (k, v) {
		vhtml = $("#elm_" + v).html();
		if (v == 55) {
			vhtml = vhtml.replace('placeholder="请输入标签名称"', 'placeholder="请输入标签名称" value="' + item['step' + step][k].N + '"');
		}
		html += vhtml;
	});
	$(".form").html(html);
	//以下设置非必填选项 checkbox为非选中
	$.each(items_notRequired, function (k, v) {
		// console.log(items_notRequired);return false;
		if (v == 0) {
			$("#main").find('.form').find('.elm').not('.blank').eq(k).find('.required').prop('checked', false);
		} else {
			$("#main").find('.form').find('.elm').not('.blank').eq(k).find('.required').prop('checked', 'checked');
		}
	});
}

function set_notSelected(items_notSelected) {
	var html = '';
	$.each(items_notSelected, function (k, v) {
		html += $("#elm_" + v).html();
	});
	$("section.add-field").find('.panel-body').html(html);
}

function set_prev(items_selected) {

	var x = $('.tab_content').filter('.cur');
	$(".add-field").find('.mobile').find('.browse').html(''); //清空原有html

	if (x.hasClass('precheck')) {
		//设置显示样式
		var set_common_style = function () {
			var img_w          = 494,
				img_h          = 956,
				img_top_gap    = 112,
				img_bottom_gap = 110;
			var rate_img       = img_w / img_h;
			var w              = x.width();
			if (w > 550) {
				w = 550;
			}
			x.width(w);
			x.css('margin', '2rem auto')
			var rate_size = w / 540;
			x.height(w / rate_img);
			x.find('.mobile').height(w / rate_img);
			x.find('.gap').css('height', img_top_gap * rate_size);
			x.find('.browse').height(w / rate_img - img_top_gap * rate_size - img_bottom_gap * rate_size - 30); //后面的-20是为了给浏览区设置margin:15px 0 用

		}();
		if ($("#main").find('.form').find('.elm').not('.blank').size()) {
			// 显示预览内容
			$(".add-field").find('.mobile').find('.browse').html($("#main").find('.form').html());

			//背景手机图片设置 （空心）
			x.css({
				'background-image' : 'url(' + SKIN_IMG + 'tel2.png)',
				'background-size'  : 'cover'
			});

		} else {

			//背景手机图片设置 （实心）
			x.css({
				'background-image' : 'url(' + SKIN_IMG + 'tel.png)',
				'background-size'  : 'cover'
			});
		}

	}

	//set_addr_picker();

}

function set_items_odd() {

	var blank = $("#elm_99").html();
	$(".blank").remove();
	if ($("section.add-field").find('.panel-body').find('.elm').not('.blank').size() % 2) {
		$("section.add-field").find('.panel-body').append(blank);
	} else {
		$("section.add-field").find('.panel-body').find('.blank').remove();
	}

	if (step == 4 || step == 5) {
		if ($("#main").find('.form').find('.elm').not('.blank').size() % 2) {
			$("#main").find('.form').append(blank);
			$("#main").find('.form').find('.blank').addClass('flag');
		} else {
			$("#main").find('.form').find('.blank').remove();
		}

	}
}

function set_prev_odd() {
	var blank = $("#elm_99").html();
	if ($('.tab_content').filter('.cur').hasClass('precheck')) {
		var x = $("section.add-field").find('.mobile').find('.browse');
		x.append(blank);
		x.find('.blank').addClass('flag');
	} else {
		$("section.add-field").find('.mobile').find('.browse').find('.blank').remove();
	}
}

function deal_data(opt, callback) {
	switch (opt) {
		//1.get data
		case 'get':
			break;
		//2.depart data
		case 'depart':
			var items_selected    = []; //已选项目数组
			var items_notSelected = []; //剩余可选项目数组
			var items_notRequired = []; //必选项
			if (item['step' + step]) {
				$.each(item['step' + step], function (index, v) {
					if (!(index === undefined || v === undefined)) {
						items_selected.push(parseInt(v['id']));
						if (item['step' + step][index]['R'] == 0) {
							items_notRequired.push(0);
						} else {
							items_notRequired.push(1);
						}
					}

				});

			}
			items_notSelected = items_selected.difference(items['step' + step], items_selected);

			if (!items_notSelected.contains(55) && step in [0,1,2,3]) {
				items_notSelected.push(55);
			}
			return {
				'items_selected'    : items_selected,
				'items_notSelected' : items_notSelected,
				'items_notRequired' : items_notRequired
			};

			break;
		//3.update data
		case 'update':
			var tmp             = item["step" + step];
			item["step" + step] = [];
			// 检查是否已添加yunying项目
			if (typeof(item['step' + step]) !== "undefined") {
				var has_yunying = 0;
				$.each(item['step' + step], function (index, v) {
					if ($.isEmptyObject(v)) {
						return true;
					}

					if (v.id * 1 === 28 && has_yunying === 0) {
						has_yunying = index + 1;
					}

					if (v.id * 1 === 28 && has_yunying > 0 && has_yunying !== index + 1) {
						item['step' + step].splice(index, 1);
					}
				});
			}

			if (step == 5 && (item['step1'].length || item['step2'].length || item['step3'].length || item['step4'].length || $.isEmptyObject(item['step5']) || !item['step5'].length || item['step5'].length)) {
				if (!has_yunying) {
					item["step" + step].push({
						id : 28,
						T  : 'yunying',
						R  : 1
					});
				}
			}
			var s = 1;
			
			$(".form").find('.elm').not('.blank').each(function (index) {
				if ($(this).data('id') != 28) {
					var data_this = '';
					if ($(this).data('id') == 55) {
						// 自定义
						if ($(this).find('.elmname').val()) {
							data_this = {
								id : $(this).data('id') * 1,
								T  : $(this).data('field'),
								R  : $(this).find('.required').prop('checked') + 0,
								N  : $(this).find('.elmname').val(),
								K  : 'zdy' + s
							}
						}
						s++;
					} else {
						data_this = {
							id : $(this).data('id') * 1,
							T  : $(this).data('field'),
							R  : $(this).find('.required').prop('checked') + 0
						};
					}
					// console.log(item);
					if (data_this) {
						item["step" + step].push(data_this);
					}

					/*item["step" + step][index] = {};
					 item["step" + step][index]["id"] = $(this).data('id') * 1;
					 item["step" + step][index]["T"] = $(this).data('field');
					 item["step" + step][index]["R"] = $(this).find('.required').prop('checked') + 0;*/
				}

				//step1:{"1":{"id":2,"T":"name","R":1},...}
				//{{step1:{"1":{"id":2,"T":"name","R":1}},...},{step2:{"1":{"id":2,"T":"name","R":1}},...}...}
				//index:序号,id:模板ID,T:字段名,R:是否必填
			});
			//请求存储77
			// console.log(item);return false;
			$.post(URL, {
				id   : form_id,
				data : JSON.stringify(item)
			}, function (data) {
					callback();
			}, 'json');
			break;
		default:
			break;
	}

}
function nexts() {
	var mask = new masks()
	mask.content('', '数据保存中 ... ... ')
	var $this = $(this);
	deal_data('update', function () {
		mask.unmask();
		var i = ($this.attr('id') == 'prev_step') ? -1 : 1;

		if (step + i < 1) {
			window.history.go(-1);
			return false;
		}
		if (step + i >= 6) {
			window.location.href = INI_URL;
			return false;
		}

		step += i; //更新步数
		$('.stepBox').css('background', 'url("./skin/images/z-step' + step + '.png")  no-repeat 0 20px');
		$(".next_step").attr('step', step);
		init();
	});

}
// function nexts() {
// 	//var mask = new masks()
// 	//mask.content('', '数据保存中 ... ... ')
// 	var $that = $(this);
// 	var haha  = $that.attr('id');
// 	deal_data('update',a());
// 	 function a() {
// 		//mask.unmask();
// 		var i = (haha == 'prev_step') ? -1 : 1;

// 		if (step + i < 1) {
// 			window.history.go(-1);
// 			return false;
// 		}
// 		if (step + i >= 6) {
// 			alert('121');return false;
// 			window.location.href = INI_URL;
// 			return false;
// 		}

// 		step += i; //更新步数
// 		$('.stepBox').css('background', 'url("./skin/images/z-step' + step + '.png")  no-repeat 0 20px');
// 		$(".next_step").attr('step', step);
// 		init();
// 	};
// }

function set_step_bottons() {
	switch (step) {
		case 1:
			$(".prev_step").addClass('hide');
			$(".next_step").html('下一步');
			break;
		case 5:
			$(".prev_step").removeClass('hide');
			$(".next_step").html('完成');
			break;
		default:
			$(".prev_step").removeClass('hide');
			$(".next_step").html('下一步');
			break;

	}
}

function set_tab_fun(items_selected) {
	var tabs = new tab();
	tabs.set_tab({
		'tab'      : '#tabs>.tab_item',
		'content'  : '.tab_content',
		'callback' : set_prev,
		'opt'      : items_selected
	});
}

function set_drag() {
	if (sort1) {
		sort1.destroy();
	}
	if (sort2) {
		sort2.destroy();
	}
	var bar   = document.getElementById("list");
	var main  = document.getElementById("form");
	var sort1 = Sortable.create(bar, {
		"group" : {
			"name" : "marico"
			/*, "pull": "clone"*/
		},

		//handle: ".my-handle", // 点击目标元素约束开始
		sort        : false,
		draggable   : ".elm", // 指定那些选项需要排序
		ghostClass  : "dragging",
		animation   : 350,
		chosenClass : 'shit',
		onStart     : function (/**Event*/ evt) { // 拖拽
			var itemEl = evt.item;
			//itemEl.classAdd('dragging');
		},
		onEnd       : function (/**Event*/ evt) { // 拖拽
			var itemEl = evt.item;
			//itemEl.classRemove('dragging');
			/*===================================*/
			set_items_odd(dealed_data); //防止项目是单数时最后一项居中
			set_prev(dealed_data.items_selected); //设置预览
		},
		onAdd       : function (/**Event*/ evt) {
			var itemEl = evt.item;
		},
		onUpdate    : function (/**Event*/ evt) {
			var itemEl = evt.item; // 当前拖拽的html元素
		},
		onRemove    : function (/**Event*/ evt) {
			var itemEl = evt.item;
			var step   = $(itemEl);
			if (step.data('id') == 55) {
				var elm_html = $("#elm_55").html();
				// 				elm_html=elm_html.replace('data-id="55"','data-id="56"');
				$('#list').append(elm_html);
			}
		}
	});
	var sort2 = Sortable.create(main, {
		"group"    : "marico",
		//handle: ".my-handle", // 点击目标元素约束开始
		draggable  : ".elm", // 指定那些选项需要排序
		ghostClass : "dragging",
		animation  : 350,
		onStart    : function (/**Event*/ evt) { // 拖拽
			var itemEl = evt.item;
			//itemEl.classAdd('dragging');

		},

		onEnd : function (/**Event*/ evt) { // 拖拽
			var itemEl = evt.item;
			//itemEl.classRemove('dragging');
			set_items_odd(dealed_data); //防止项目是单数时最后一项居中
			set_prev(dealed_data.items_selected); //设置预览
		},

		onAdd : function (/**Event*/ evt) {
			var itemEl = evt.item;

		},

		onUpdate : function (/**Event*/ evt) {
			var itemEl = evt.item; // 当前拖拽的html元素

		},

		onRemove : function (/**Event*/ evt) {
			var itemEl = evt.item;


		}
	});
}

/**
 * each是一个集合迭代函数，它接受一个函数作为参数和一组可选的参数
 * 这个迭代函数依次将集合的每一个元素和可选参数用函数进行计算，并将计算得的结果集返回
 var a = [1,2,3,4].each(function(x){return x > 2 ? x : ''});
 var b = [1,2,3,4].each(function(x){return x < 0 ? x : ''});
 alert(a);
 alert(b);
 * @param {Function} fn 进行迭代判定的函数
 * @param more ... 零个或多个可选的用户自定义参数
 * @returns {Array} 结果集，如果没有结果，返回空集
 */
Array.prototype.each = function (fn) {
	fn       = fn || Function.K;
	var a    = [];
	var args = Array.prototype.slice.call(arguments, 1);
	for (var i = 0; i < this.length; i++) {
		var res = fn.apply(this, [this[i], i].concat(args));
		if (res != '') {
			a.push(res);
		}
	}
	return a;
};

/**
 * 得到一个数组不重复的元素集合<br/>
 * 唯一化一个数组
 * @returns {Array} 由不重复元素构成的数组
 */
Array.prototype.uniquelize = function () {
	var ra = new Array();
	for (var i = 0; i < this.length; i++) {
		if (!ra.contains(this[i])) {
			ra.push(this[i]);
		}
	}
	return ra;
};

/**
 * 求两个集合的补集

 var a = [1,2,3,4];
 var b = [3,4,5,6];
 alert(Array.complement(a,b));

 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的补集
 */
Array.prototype.complement = function (a, b) {
	return Array.difference(Array.union(a, b), Array.intersect(a, b));
};

/**
 * 求两个集合的交集

 var a = [1,2,3,4];
 var b = [3,4,5,6];
 alert(Array.intersect(a,b));

 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的交集
 */
Array.prototype.intersect = function (a, b) {
	return a.uniquelize().each(function (o) {
		return b.contains(o) ? o : ''
	});
};

/**
 * 求两个集合的差集

 var a = [1,2,3,4];
 var b = [3,4,5,6];
 alert(Array.difference(a,b));

 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的差集
 */
Array.prototype.difference = function (a, b) {
	return a.uniquelize().each(function (o) {
		return b.contains(o) ? '' : o
	});
};

/**
 * 求两个集合的并集
 var a = [1,2,3,4];
 var b = [3,4,5,6];
 alert(Array.union(a,b));
 * @param {Array} a 集合A
 * @param {Array} b 集合B
 * @returns {Array} 两个集合的并集
 */
Array.prototype.union = function (a, b) {
	return a.concat(b).uniquelize();
};

/**
 * 判断数组是否包含某个元素
 */
Array.prototype.contains = function (item) {
	//return RegExp(item).test(this);
	var key = this.indexOf(item);
	if (key == '-1') {
		return false;
	}
	return this[key];
};







