(function () {
	'use strict';

	var byId = function (id) { return document.getElementById(id); },

		loadScripts = function (desc, callback) {
			var deps = [], key, idx = 0;

			for (key in desc) {
				deps.push(key);
			}

			(function _next() {
				var pid,
					name = deps[idx],
					script = document.createElement('script');

				script.type = 'text/javascript';
				script.src = desc[deps[idx]];

				pid = setInterval(function () {
					if (window[name]) {
						clearTimeout(pid);

						deps[idx++] = window[name];

						if (deps[idx]) {
							_next();
						} else {
							callback.apply(null, deps);
						}
					}
				}, 30);

				document.getElementsByTagName('head')[0].appendChild(script);
			})()
		},

		console = window.console;


	if (!console.log) {
		console.log = function () {
			alert([].join.apply(arguments, ' '));
		};
	}


//	Sortable.create(byId('foo'), {
//		group: "words",
//		animation: 150,
//		store: {
//			get: function (sortable) {
//				var order = localStorage.getItem(sortable.options.group);
//				return order ? order.split('|') : [];
//			},
//			set: function (sortable) {
//				var order = sortable.toArray();
//				localStorage.setItem(sortable.options.group, order.join('|'));
//			}
//		},
//		onAdd: function (evt){ console.log('onAdd.foo:', [evt.item, evt.from]); },
//		onUpdate: function (evt){ console.log('onUpdate.foo:', [evt.item, evt.from]); },
//		onRemove: function (evt){ console.log('onRemove.foo:', [evt.item, evt.from]); },
//		onStart:function(evt){ console.log('onStart.foo:', [evt.item, evt.from]);},
//		onSort:function(evt){ console.log('onStart.foo:', [evt.item, evt.from]);},
//		onEnd: function(evt){ console.log('onEnd.foo:', [evt.item, evt.from]);}
//	});
//
//
//	Sortable.create(byId('bar'), {
//		group: "words",
//		animation: 150,
//		onAdd: function (evt){ console.log('onAdd.bar:', evt.item); },
//		onUpdate: function (evt){ console.log('onUpdate.bar:', evt.item); },
//		onRemove: function (evt){ console.log('onRemove.bar:', evt.item); },
//		onStart:function(evt){ console.log('onStart.foo:', evt.item);},
//		onEnd: function(evt){ console.log('onEnd.foo:', evt.item);}
//	});


	// Multi groups
//	Sortable.create(byId('multi'), {
//		animation: 150,
//		draggable: '.tile',
//		handle: '.tile__name'
//	});
//
//	[].forEach.call(byId('multi').getElementsByClassName('tile__list'), function (el){
//		Sortable.create(el, {
//			group: 'photo',
//			animation: 150
//		});
//	});


	// Editable list
//	var editableList = Sortable.create(byId('editable'), {
//		animation: 150,
//		filter: '.js-remove',
//		onFilter: function (evt) {
//			evt.item.parentNode.removeChild(evt.item);
//		}
//	});


//	byId('addUser').onclick = function () {
//		Ply.dialog('prompt', {
//			title: 'Add',
//			form: { name: 'name' }
//		}).done(function (ui) {
//			var el = document.createElement('li');
//			el.innerHTML = ui.data.name + '<i class="js-remove">✖</i>';
//			editableList.el.appendChild(el);
//		});
//	};

	Sortable.create(byId('advanced-1'), {
		sort: (1 != 1),
		group: {
			name: 'advanced',
			pull: true,
			put: true
		},
		animation: 150,
		onRemove:function(s){
			if($(s.item).attr('data-id') == 55){
				return false;
			}
		}
	});
	Sortable.create(byId('advanced-2'), {
		sort: (1 != 1),
		group: {
			name: 'advanced',
			pull: true,
			put: true
		},
		animation: 150,
		onRemove:function(s){
			if($(s.item).attr('data-id') == 55){
				$('#advanced-2').append('<li class="elm" data-id="55" data-field="custom">' +
					'<div class="label">' +
					'<i class="iconfont icon-edit icon"></i>' +
					'<span class="elmtext">自定义标签</span>' +
					'</div>' +
					'<div class="preview">' +
					'<p class="bd">' +
					'<i class="iconfont icon-edit icon"></i>' +
					'<input type="text" name="custom[]" class="elmname"  placeholder="请输入标签名称">' +
					'</p>' +
					'<input type="checkbox" class="required" checked="checked" />必填' +
					'</div>' +
					'<div class="item">' +
					'<p class="bd">' +
					'<i class="iconfont icon-edit icon"></i>' +
					'<label class="mar_label">自定义标签</label>' +
					'<input type="hidden" class="chk" name="elmname[]" value="自定义标签"/>' +
					'<input class="chk" type="text" name="custom[]"  placeholder="请输入自定义标签" chk=\'notNull\' />' +
					'</p>' +
					'<p class="tip red tl" data-tip="自定义标签"></p>' +
					'</div>' +
					'</li>');
			}
		}
	});

	// Advanced groups




	// 'handle' option
//	Sortable.create(byId('handle-1'), {
//		handle: '.drag-handle',
//		animation: 150
//	});s


	// Angular example
	angular.module('todoApp', ['ng-sortable'])
		.constant('ngSortableConfig', {onEnd: function() {
			console.log('default onEnd()');
		}})
		.controller('TodoController', ['$scope', function ($scope) {
			$scope.todos = [
				{text: 'learn angular', done: true},
				{text: 'build an angular app', done: false}
			];

			$scope.addTodo = function () {
				$scope.todos.push({text: $scope.todoText, done: false});
				$scope.todoText = '';
			};

			$scope.remaining = function () {
				var count = 0;
				angular.forEach($scope.todos, function (todo) {
					count += todo.done ? 0 : 1;
				});
				return count;
			};

			$scope.archive = function () {
				var oldTodos = $scope.todos;
				$scope.todos = [];
				angular.forEach(oldTodos, function (todo) {
					if (!todo.done) $scope.todos.push(todo);
				});
			};
		}])
		.controller('TodoControllerNext', ['$scope', function ($scope) {
			$scope.todos = [
				{text: 'learn Sortable', done: true},
				{text: 'use ng-sortable', done: false},
				{text: 'Enjoy', done: false}
			];

			$scope.remaining = function () {
				var count = 0;
				angular.forEach($scope.todos, function (todo) {
					count += todo.done ? 0 : 1;
				});
				return count;
			};

			$scope.sortableConfig = { group: 'todo', animation: 150 };
			'Start End Add Update Remove Sort'.split(' ').forEach(function (name) {
				$scope.sortableConfig['on' + name] = console.log.bind(console, name);
			});
		}]);
})();



// Background
document.addEventListener("DOMContentLoaded", function () {
	function setNoiseBackground(el, width, height, opacity) {
		var canvas = document.createElement("canvas");
		var context = canvas.getContext("2d");

		canvas.width = width;
		canvas.height = height;

		for (var i = 0; i < width; i++) {
			for (var j = 0; j < height; j++) {
				var val = Math.floor(Math.random() * 255);
				context.fillStyle = "rgba(" + val + "," + val + "," + val + "," + opacity + ")";
				context.fillRect(i, j, 1, 1);
			}
		}

		el.style.background = "url(" + canvas.toDataURL("image/png") + ")";
	}

	setNoiseBackground(document.getElementsByTagName('body')[0], 50, 50, 0.02);
}, false);
