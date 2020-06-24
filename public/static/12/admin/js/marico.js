var root = "http://test.nongshiye.com";

//if(window.top !== window.self){ window.top.location = window.location;} 
var wAlert = window.alert;
window.alert = function(message) {
	try {
		var iframe = document.createElement("IFRAME");
		iframe.style.display = "none";
		iframe.setAttribute("src", 'data:text/plain,');
		document.documentElement.appendChild(iframe);
		window.frames[0].window.alert(message);
		iframe.parentNode.removeChild(iframe);
	} catch(exc) {
		return wAlert(message);
	}
}
/*var wConfirm = window.confirm;
 window.confirm = function(message) {
 try {
 var iframe = document.createElement("IFRAME");
 iframe.style.display = "none";
 iframe.setAttribute("src", 'data:text/plain,');
 document.documentElement.appendChild(iframe);
 var alertFrame = window.frames[0];
 var iwindow = alertFrame.window;
 if(iwindow == undefined) {
 iwindow = alertFrame.contentWindow;
 }
 iwindow.confirm(message);
 iframe.parentNode.removeChild(iframe);
 } catch(exc) {
 return wConfirm(message);
 }
 }*/

function helps() {
	this.help = function(str) {
		switch(str) {
			case 'tab':
				{
					console.log('####### Help for class tab #########################################');
					console.log("Function 1     :  set_tab(jsonStr)");
					console.log("  PamramType      jsonStr -> json string");
					console.log("  PamramEg        {'tab':'ul>li','content':'section.hall','callback':function(){} }");
					console.log("  PamramExplain   tab->(string)the menus you will click.");
					console.log("  PamramExplain   content->(string)the content you will show when a nemu is clicked.");
					console.log("  PamramExplain   callback->(string)the function body you will call when this sets.");
					console.log('####### End help for class tab #########################################');
				}
				break;
			case 'sizer':
				{
					console.log('####### Help for class sizer #########################################');
					console.log("Function 1     :  set_size(jsonStr)");
					console.log("  PamramType      jsonStr -> json string");
					console.log("  PamramEg        {'dom':'.hall','content':{'height':'100rem','width':'100%'}}");
					console.log("  PamramExplain   dom->(string)the dom you will set size.");
					console.log("  PamramExplain   content->(json string)the css style you set.");
					console.log("");
					console.log("Function 2     :  parseRem(num)");
					console.log("  Usage           parse a number or a string to a number when you use the size unit 'rem'");
					console.log("  PamramType      num -> (string/number) this must contains a num");
					console.log('####### End help for class sizer #########################################');
				}
				break;
			default:
				{
					console.log('####### Help for class MaricoJs #########################################');
					console.log("");
					console.log("Please set the pamram string ,eg. this.help('sizer')");
					console.log("");
					console.log("Pramas List : tab , sizer");
					console.log("");
					console.log('####### End help for class MaricoJs #########################################');
				}
		}
	}
}
//tab
//{'tab':'ul>li','content':'section.hall'}
function tab() {
	this.set_tab = function(jsonStr) {
		this.obj = jsonStr;

		if(this.obj.callback) {

			var callback = this.obj.callback;

		}

		if(this.obj.tab && this.obj.content) {
			var t = this.obj.tab;
			var c = this.obj.content;

			$(t).each(function(index) {
				$(this).on('click', function(event) {
					event.stopPropagation();
					$(this).siblings().removeClass('cur');
					$(this).addClass('cur');
					$(c).siblings().removeClass('cur');
					$(c).eq(index).addClass('cur');
					if(callback) {
						callback();
					}

				});
			});
		} else {
			if(!this.obj.tab) console.log("ERR:Param tab is not exist");
			if(!this.obj.content) console.log("ERR:Param content is not exist");
		}
	}

}

function sizer() {
	this.set_size = function(jsonStr) {
		this.obj = jsonStr;
		if(this.obj.dom && this.obj.content) {
			var d = this.obj.dom;
			var c = this.obj.content;
			$(d).css(c);
		} else {
			if(!this.obj.tab) console.log("ERR:Param tab is not exist");
			if(!this.obj.content) console.log("ERR:Param content is not exist");
		}
	}
	this.parseRem = function(num) {
		return num / parseInt($("html").css('font-size'));
	}
}

function dom_ctrl() {
	this.ctrl = function(dom, action, content) {

	}
}

function parseDom() {

	this.str2Dom = function(dom) {
		var div = document.createElement("div");
		div.innerHTML = html;
		return div.children[0];
	}

	this.dom2Str = function(htmlDOM) {
		if(outerHTML in htmlDOM) {
			return htmlDOM.outerHTML;
		} else {
			var div = document.createElement("div");
			div.appendChild(htmlDOM);
			return div.innerHTML
		}
	}

}

function timer(total, step) {
	this.total = total;
	this.step = step;
	this.run = function(dom, callbak) {
		var i = 1;
		dom.html(total - i);
		window.itv_mar = setInterval(function() {
			if(i == total) {
				clearInterval(itv_mar);
				callbak();
			}
			dom.html(total - i);
			i += step;
		}, 1000 * step);
	}
}

function checker() {
	this.check = function(type, str) {
		switch(type) {
			case "tel":
				//手机号检测
				return /^1[34578]\d{9}$/.test(str);
				break;
			case "idcard":
				//身份证检测
				var id1 = /^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/;
				var id2 = /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/;
				return(id1.test(str) || id2.test(str));
				break;
			case "email":
				//检测Email
				return /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/.test(str);
				break;
			case "cn":
				//检测是否是中文
				return /^([\u4E00-\u9FA5])*$/.test(str);
				break;
			case "int":
				return /^[0-9]*$/.test(str);
				//return  /^\d+(\.\d+)?$/.test(str);
				break;
			case "num":
				//return /^[0-9]*$/.test(str);
				return /^\d+(\.\d+)?$/.test(str);
				break;
			default:
				return true;
		}

	}
}

function masks() {
	var that = this;
	that.mask = function() {
		$("body").append("<div class = 'masks'></div>");
		$(".masks").css({
			'position': 'fixed',
			'left': '0',
			'top': '0',
			'background': 'rgba(50,50,50,0.95)'
		});
		$(".masks").css({
			'height': $(window).height() + 'px',
			'z-index': '9999',
			'width': '100%'
		});
		$(".masks").css({
			'display': 'block'
		});
		$(window).resize(function() {
			$(".masks").css({
				'height': $(window).height() + 'px',
				'z-index': '9999',
				'width': '100%'
			});
		});
	}
	that.unmask = function() {
		$(".masks").remove();
	}
	that.content = function(title, content, closebt, settimeout) {
		that.unmask();
		that.mask();
		$(".masks").append('<div class="mloading-bar" style="margin-top: -31px; margin-left: -140px;"><img class="mloading-icon" src="data:image/gif;base64,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"><span class="mloading-text">' + content + '</span></div>');

		$(".masks").find(".mloading-bar").css({
			'width': '300px',
			'min-height': '30px',
			'text-align': 'center',
			'background': '#fff',
			'box-shadow': '0 1px 2px rgba(0, 0, 0, 0.27)',
			'border-radius': '7px',
			'padding': ' 20px 15px',
			'font-size': '14px',
			'color': '#999',
			'position': 'absolute',
			'top': '50%',
			'left': '50%',
			'margin-left': '-140px',
			'margin-top': '-30px',
			'word-break': 'break-all'
		});
	}

}

//图片前端压缩
function imgTool() {
	//返回文件的blob形式
	this.getFilePath = function(id) {
		var ids = document.getElementById(id);
		var file = ids.files[0];
		var pre = file.name;
		var URL = window.URL || window.webkitURL;
		var blob = URL.createObjectURL(file);
		return blob;

	}
	this.dealImg = function(id, opt, callback) {
		var path = this.getFilePath(id);
		/**
		 * 图片压缩，默认同比例压缩
		 * @param {Object} path
		 *     pc端传入的路径可以为相对路径，但是在移动端上必须传入的路径是照相图片储存的绝对路径
		 * @param {Object} opt
		 *     obj 对象 有 width， height， quality(0-1)
		 * @param {Object} callback
		 *     回调函数有一个参数，base64的字符串数据
		 */
		var img = new Image();
		img.src = path;
		img.onload = function() {
			var that = this;
			// 默认按比例压缩
			var w = that.width,
				h = that.height,
				scale = w / h;
			if(opt.width && opt.height) {
				var _scale = opt.width / opt.height;
				if(scale >= _scale) {
					//此时图片取宽度按设限 整体尺寸不会超
					if(w >= opt.width) {
						w = opt.width;
						h = w / scale;
					}
				} else {
					//此时图片取高度按设限 整体尺寸不会超
					if(h >= opt.height) {
						h = opt.height;
						w = h * scale;
					}
				}
			} else if(opt.width && w > opt.width) {
				w = opt.width;
				h = w / scale;
			} else if(opt.height && h > opt.height) {
				h = opt.height;
				w = h * scale;
			}
			var quality = 1; // 默认图片质量为0.7
			//生成canvas
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext('2d');
			// 创建属性节点
			var anw = document.createAttribute("width");
			anw.nodeValue = w;
			var anh = document.createAttribute("height");
			anh.nodeValue = h;
			canvas.setAttributeNode(anw);
			canvas.setAttributeNode(anh);
			ctx.drawImage(that, 0, 0, w, h);
			// 图像质量
			if(opt.quality && opt.quality <= 1 && opt.quality > 0) {
				quality = opt.quality;
			}
			// quality值越小，所绘制出的图像越模糊
			var base64 = canvas.toDataURL('image/jpeg', quality);

			// 回调函数返回base64的值
			if(callback) {
				callback(base64);
			}
		}
	}
}

/**
 * @开发时间：2016/5/24
 * @开发人员：boxUnll
 * @改进：Marico   ok@nongshiye.com
 * @改进内容：	1.修改原来容器宽度计算，使之能使用任何宽度
 * 				2.将原来一段实现代码封装为对象，轻松实现复用
 * 				3.修改部分css样式
 * 				4.重新命名变量
 * @param	{String}	MarWrap			滑动验证容器ID
 * @param	{String}	MarSlider		滑块ID
 * @param 	{String} 	MarSliderIcon	滑块内图标ID
 * @param 	{String} 	MarSlided		滑动过后显示内容ID
 * @param 	{String} 	MarSlidedIcon	验证通过后滑块内显示的图标ID
 * @param 	{Function} 	MarSlidedCallback	验证通过后回调函数
 *
 * 开发项目：移动端滑动验证代码
 */
function MarSliderChecker(MarWrap, MarSlider, MarSliderIcon, MarSlided, MarSlidedIcon, MarSlidedCallback) {
	loadCSS('http://marico.oschina.io/marjs/MarSliderChecker.css');
	this.oWrap = document.getElementById(MarWrap); //滑动验证容器
	this.oSlider = document.getElementById(MarSlider); //滑块
	this.oSliderIcon = document.getElementById(MarSliderIcon); //滑块内图标
	this.oSlided = document.getElementById(MarSlided); //滑动过后显示内容
	this.oSlidedIcon = document.getElementById(MarSlidedIcon); //验证通过后滑块内显示的图标
	this.fCallback = MarSlidedCallback
	this.sliderWidth = 0;
	this.oLeft = 0;
	this.flag = 1;
	var $this = this;
	$this.oSlider.addEventListener('touchstart', function(e) {
		if($this.flag == 1) {
			//console.log(e);
			var touches = e.touches[0];
			$this.sliderWidth = touches.clientX - $this.oSlider.offsetLeft;
			$this.oSlider.className = "button";
			$this.oSlided.className = "track";
		}
	}, false);
	$this.oSlider.addEventListener("touchmove", function(e) {
		if($this.flag == 1) {
			var touches = e.touches[0];
			$this.oLeft = touches.clientX - $this.sliderWidth;
			if($this.oLeft < 0) {
				$this.oLeft = 0;
			} else if($this.oLeft > $this.oWrap.offsetWidth - $this.oSlider.offsetWidth) {
				$this.oLeft = ($this.oWrap.offsetWidth - $this.oSlider.offsetWidth);
			}
			$this.oSlider.style.left = $this.oLeft + "px";
			$this.oSlided.style.width = $this.oLeft + 'px';
		}

	}, false);

	$this.oSlider.addEventListener("touchend", function() {
		if($this.oLeft >= ($this.oWrap.clientWidth - $this.oSlider.clientWidth)) {
			$this.oSlider.style.left = ($this.oWrap.offsetWidth - $this.oSlider.offsetWidth);
			$this.oSlided.style.width = ($this.oWrap.offsetWidth - $this.oSlider.offsetWidth);
			$this.oSliderIcon.style.display = 'none';
			$this.oSlidedIcon.style.display = 'block';
			$this.flag = 0;
			if($this.fCallback) {
				$this.fCallback();
			}

		} else {
			$this.oSlider.style.left = 0;
			$this.oSlided.style.width = 0;
			$this.oSliderIcon.style.display = 'block';
			$this.oSlidedIcon.style.display = 'none';

		}
		$this.oSlider.className = "button-on";
		$this.oSlided.className = "track-on";
	}, false);

}

function set_pct() {
	var wh = $(window).height();
	var ww = $(window).width();
	$(".pct").each(function() {
		var h = $(this).data('h');
		var w = $(this).data('w');
		if(h) {
			$(this).css('height', wh * h / 100 + 'px');
		}
		if(w) {
			$(this).css('width', ww * w / 100 + 'px');
		}
	});
}

function ImgView(elm, img_class) {
	var $this = this;
	$this.elm = elm;
	$this.imgs = img_class ? $this.elm.find(img_class) : $this.elm.find('img');
	$this.cur_img = 0;
	$this.init = function(i) {
		$this.background();
		$this.wrap();
		$this.add_img(i)
	}
	$this.config = function(opt) {
		$this.rotatable = opt.rotatale ? true : false;
	}
	$this.background = function() {
		$("body").append("<div class='img_viewer_bg'></div>");
		$(".img_viewer_bg").css({
			'position': 'fixed',
			'width': $(window).width(),
			'height': $(window).height() * 1.5,
			'top': 0,
			'left': 0,
			'z-index': '12000',
			'background': 'rgba(0,0,0,0.95)'
		})
		$(document.body).css({
			'height': $(window).height(),
			'overflow': 'hidden'
		})
	}

	$this.wrap = function() {
		var html = "<div class='img_viewer_body'>";
		html += "<div class='tool_bar'><a href = 'javascript:void(0)' class='img_prev tool_img' >< 上一张</a><a  href = 'javascript:void(0)' class='img_next tool_img'>下一张 ></a>";
		if($this.rotatable) {
			html += "<a  href = 'javascript:void(0)' class='rotate_left tool_img'>向左旋转</a><a  href = 'javascript:void(0)' class='rotate_right tool_img'>向右旋转</a>";
		}
		html += "<a  href = 'javascript:void(0)' class='img_close tool_img'>关闭</a>";
		html += "<p class='img_title_info'></p>";
		html += "</div>";
		html += "<p class='img_wrap'></p>";
		html += "</div>";
		$(".img_viewer_bg").append(html)
		var style = '<style>';
		style += '.img_viewer_body{margin-left:auto;margin-right:auto;}';
		style += '.img_wrap{height:' + $(window).height() * 0.75 + 'px}';
		style += '.img_title_info{line-height:2.5rem;color:#fff;margin-top:3%;min-height:3rem;text-align:center;}';
		style += '.tool_bar{width:30%;margin:0 auto;text-align:center;max-width:700px;}';
		style += '.tool_img{height:3rem;line-height:3rem;margin:0 1.5rem;color:#fff;}';
//		style += '.img_prev,.img_next{float:left;}';
//		style += '.img_close{float:right;}';
		style += '</style>';
		$("body").append(style);
		$(".img_prev").on('click', function() {
			$this.next(-1);
		})
		$(".img_next").on('click', function() {
			$this.next(1);
		})
		if($this.rotatable) {
			$(".rotate_left").on('click', function() {
				$this.rotate(-1);
			});
			$(".rotate_right").on('click', function() {
				$this.rotate(1);
			});
		}
		$(".img_close").on('click', function() {
			$this.close();
		})
	}
	$this.add_img = function(i) {
		if(!i) {
			i = 0;
		}
		$this.cur_img = i;
		var img_wrap = $(".img_viewer_body").find('.img_wrap');

		img_wrap.html("<img src='" + $($this.imgs[i]).prop('src') + "' />");
		img_wrap.css({
			'text-align': 'center',
			'margin-top': $(window).height() * 0.05
		});
		img_wrap.find('img').css({
			'border': '0.15rem solid #fff'
		});

		img_wrap.find('img').css({
			'max-width': $(window).width() * .75,
			'max-height': $(window).height() * .75
		});
		$(".img_viewer_body").find('.img_wrap').css({
			'height': $(".img_viewer_body").find('.img_wrap').find('img').height() + $(window).height() * .05
		})
		$(".img_viewer_body").find('.img_title_info').html($($this.imgs[i]).prop('title'));
	}

	$this.next = function(d) {
		var size = $this.imgs.length;
		if(d == -1) {
			$this.cur_img--;
		} else if(d == 1) {
			$this.cur_img++;
		}
		if($this.cur_img >= size) {
			$this.cur_img = 0;
		} else if($this.cur_img < 0) {
			$this.cur_img = size - 1
		}

		$this.add_img($this.cur_img);
	}
	$this.close = function() {
		$(".img_viewer_bg").remove();
		$("body").css({
			'height': 'auto',
			'overflow': 'scroll'
		})
		$this = null;
	}

	$this.rotate = function(direction) {
		var img = $(".img_viewer_body").find('.img_wrap').find('img');
		var cur_deg = img.prop('rotate') ? img.prop('rotate') : 0;
		var degs = 0;
		if(direction == 1) {
			degs = cur_deg + 90;
		} else if(direction == '-1') {
			degs = cur_deg - 90;
		} else {
			degs = direction;
		}
		img.prop('rotate', degs);
		img.css({
			'transform': 'rotate(' + degs + 'deg)',
			'max-height': 'auto'
		})
		if(degs / 90 % 2) {
			img.animate({
				'margin-top': ($(".img_viewer_body").find('.img_wrap').offset().top - img.offset().top) * 0.5,
				'max-width': $(window).height() * 0.75,
				'max-height': $(window).width() * 0.75
			})
		} else {
			img.animate({
				'margin-top': '1rem',
				'max-width': $(window).width() * 0.75,
				'max-height': $(window).height() * 0.75

			})
		}

	}
}

function scrollWinow(dom) {
	var $this = this;
	$this.section = $(dom);
	$this.sec_of_top = [];
	$this.sec_hei = [];
	$this.sec_hei_whole = [];
	$this.h_win = $(window).height();
	$this.direction = 0;
	$this.top_mem = 0;
	// direction : 0 为未滚动;1为向下滚动;-1为向上滚动;

	$this.section.resize(function() {
		//console.log('resize')
		$this.init();
	});
	$this.init = function() {
		$this.set_of_top();
		$this.getCurSection();
		$(window).scroll(function() {
			$this.get_direction();
			$this.curSection = $this.getCurSection();
		});

		//
	}
	$this.set_of_top = function() {
		$.each($this.section, function(k) {
			var $sec = $this.section[k];
			$this.sec_of_top[k] = $($sec).offset().top;
			$this.sec_hei[k] = $($sec).height();
			$this.sec_hei_whole[k] = $($sec).height() + $($sec).offset().top;
		});
	}
	$this.getCurSection = function() {
		var top = $(window).scrollTop();
		var cur_index = 0;
		if($this.direction > 0) {
			$.each($this.sec_of_top, function(index) {
				if($this.sec_of_top[index] < (top + $this.h_win * .7)) {
					cur_index = index;
				}
			});

		} else {
			$.each($this.sec_of_top, function(index) {
				if($this.sec_of_top[index] < (top + $this.h_win * .3)) {
					cur_index = index;
				}
			});
		}
		//console.log('cur_index: '+cur_index);
		return cur_index;
	}
	$this.get_direction = function() {
		// direction : 0 为未滚动;1为向下滚动;-1为向上滚动;
		var top = $(window).scrollTop();
		if(top > $this.top_mem) {

			$this.direction = 1;
		} else if(top < $this.top_mem) {

			$this.direction = -1;
		} else if(top = $this.top_mem) {
			//$this.direction = 0;
		}
		$this.top_mem = top;
		//return direction;
	}
	$this.is_visual = function(dom) {
		var oft = $(dom).offset().top;
		var ht = $(dom).height();
		var top = $(window).scrollTop();
		if(oft + ht <= top || (oft - $(window).height()) >= top) {
			return false;
		} else {
			return true;
		}
	}
	$this.init()
}

function loadCSS(url) {
	var cssLink = document.createElement("link");
	cssLink.rel = "stylesheet";
	cssLink.rev = "stylesheet";
	cssLink.type = "text/css";
	cssLink.media = "screen";
	cssLink.href = url;
	document.getElementsByTagName("head")[0].appendChild(cssLink);
}

function letusgo(url) {
	window.location.href = url;
}

function get_param(name) {
	var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
	var r = window.location.search.substr(1).match(reg);
	if(r != null) return unescape(r[2]);
	return null;
}

(function($, window, undefined) {
	//监听元素尺寸变化
	var elems = $([]),
		jq_resize = $.resize = $.extend($.resize, {}),
		timeout_id,
		str_setTimeout = 'setTimeout',
		str_resize = 'resize',
		str_data = str_resize + '-special-event',
		str_delay = 'delay',
		str_throttle = 'throttleWindow';
	jq_resize[str_delay] = 250;
	jq_resize[str_throttle] = true;
	$.event.special[str_resize] = {
		setup: function() {
			if(!jq_resize[str_throttle] && this[str_setTimeout]) {
				return false;
			}
			var elem = $(this);
			elems = elems.add(elem);
			$.data(this, str_data, {
				w: elem.width(),
				h: elem.height()
			});
			if(elems.length === 1) {
				loopy();
			}
		},
		teardown: function() {
			if(!jq_resize[str_throttle] && this[str_setTimeout]) {
				return false;
			}
			var elem = $(this);
			elems = elems.not(elem);
			elem.removeData(str_data);
			if(!elems.length) {
				clearTimeout(timeout_id);
			}
		},
		add: function(handleObj) {
			if(!jq_resize[str_throttle] && this[str_setTimeout]) {
				return false;
			}
			var old_handler;

			function new_handler(e, w, h) {
				var elem = $(this),
					data = $.data(this, str_data);
				data.w = w !== undefined ? w : elem.width();
				data.h = h !== undefined ? h : elem.height();
				old_handler.apply(this, arguments);
			}
			if($.isFunction(handleObj)) {
				old_handler = handleObj;
				return new_handler;
			} else {
				old_handler = handleObj.handler;
				handleObj.handler = new_handler;
			}
		}
	};

	function loopy() {
		timeout_id = window[str_setTimeout](function() {
			elems.each(function() {
				var elem = $(this),
					width = elem.width(),
					height = elem.height(),
					data = $.data(this, str_data);
				if(width !== data.w || height !== data.h) {
					elem.trigger(str_resize, [data.w = width, data.h = height]);
				}
			});
			loopy();
		}, jq_resize[str_delay]);
	}
})(jQuery, this);