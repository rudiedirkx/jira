(function(W, D) {

	"use strict";


	var html = D.documentElement,
		head = html.getElementsByTagName('head')[0];

	JSON.encode = JSON.stringify;
	JSON.decode = JSON.parse;

	var domReadyAttached = false,
		domIsReady = false;

	var cssDisplays = {};

	function $ifsetor(pri, sec) {
		return pri !== undefined ? pri : sec;
	}

	function $arrayish(obj) {
		return typeof obj.length == 'number' && typeof obj != 'string' && obj.constructor != Object;
	}

	function $array(list) {
		var arr = [];
		$each(list, function(el, i) {
			arr.push(el);
		});
		return arr;
	}

	function $class(obj) {
		var code = String(obj.constructor);
		return code.match(/ (.+?)[\(\]]/)[1];
	}

	function $is_a(obj, type) {
		return window[type] && obj instanceof window[type];
	}

	function $serialize(o, prefix) {
		var q = [];
		$each(o, function(v, k) {
			var name = prefix ? prefix + '[' + k + ']' : k,
			v = o[k];
			if ( typeof v == 'object' ) {
				q.push($serialize(v, name));
			}
			else {
				q.push(name + '=' + encodeURIComponent(v));
			}
		});
		return q.join('&');
	}

	function $copy(obj) {
		return JSON.parse(JSON.stringify(obj));
	}

	function $merge(base) {
		for ( var i=1, L=arguments.length; i<L; i++ ) {
			$each(arguments[i], function(value, name) {
				base[name] = value;
			});
		}
		return base;
	}


	function $each(source, callback, context) {
		if ( $arrayish(source) ) {
			for ( var i=0, L=source.length; i<L; i++ ) {
				callback.call(context, source[i], i, source);
			}
		}
		else {
			for ( var k in source ) {
				if ( source.hasOwnProperty(k) ) {
					callback.call(context, source[k], k, source);
				}
			}
		}

		return source;
	}

	function $extend(Hosts, proto, Super) {
		if ( !(Hosts instanceof Array) ) {
			Hosts = [Hosts];
		}

		$each(Hosts, function(Host) {
			if ( Super ) {
				Host.prototype = Super;
				Host.prototype.constructor = Host;
			}

			var methodOwner = Host.prototype ? Host.prototype : Host;
			$each(proto, function(fn, name) {
				methodOwner[name] = fn;

				if ( Host == Element && !Elements.prototype[name] ) {
					Elements.prototype[name] = function() {
						return this.invoke(name, arguments);
					};
				}
			});
		});
	}

	function $getter(Host, prop, getter) {
		Object.defineProperty(Host.prototype, prop, {get: getter});
	}

	$extend(Array, {
		invoke: function(method, args) {
			var results = [];
			this.forEach(function(el) {
				results.push( el[method].apply(el, args) );
			});
			return results;
		},

		contains: function(obj) {
			return this.indexOf(obj) != -1;
		},

		unique: function() {
			var els = [];
			this.forEach(function(el) {
				els.contains(el) || els.push(el);
			});
			return els;
		},

		each: Array.prototype.forEach,

		first: function() {
			return this[0];
		},
		last: function() {
			return this[this.length-1];
		}
	});
	Array.defaultFilterCallback = function(item) {
		return !!item;
	};

	$extend(String, {
		camel: function() {
			return this.replace(/\-([^\-])/g, function(a, m) {
				return m.toUpperCase();
			});
		},
		uncamel: function() {
			return this.replace(/([A-Z])/g, function(a, m) {
				return '-' + m.toLowerCase();
			});
		}
	});

	var indexOf = [].indexOf,
		slice = [].slice,
		push = [].push,
		splice = [].splice,
		join = [].join,
		pop = [].join;

	typeof Date.now == 'function' || (Date.now = function() {
		return +new Date;
	});

	if (!('classList' in html)) {
		W.DOMTokenList = function DOMTokenList(el) {
			this._el = el;
			el.$classList = this;
			this._reinit();
		}
		$extend(W.DOMTokenList, {
			_reinit: function() {
				this.length = 0;

				var classes = this._el.className.trim();
				classes = classes ? classes.split(/\s+/g) : [];
				for ( var i=0, L=classes.length; i<L; i++ ) {
					push.call(this, classes[i]);
				}

				return this;
			},
			set: function() {
				this._el.className = join.call(this, ' ');
			},
			add: function(token) {
				push.call(this, token);
				this.set();
			},
			contains: function(token) {
				return indexOf.call(this, token) !== -1;
			},
			item: function(index) {
				return this[index] || null;
			},
			remove: function(token) {
				var i = indexOf.call(this, token);
				if ( i != -1 ) {
					splice.call(this, i, 1);
					this.set();
				}
			},
			toggle: function(token) {
				if ( this.contains(token) ) {
					return !!this.remove(token);
				}

				return !this.add(token);
			}
		});

		$getter(Element, 'classList', function() {
			return this.$classList ? this.$classList._reinit() : new W.DOMTokenList(this);
		});
	}

	function $loadJS(src) {
		return document.el('script', {src: src, type: 'text/javascript'}).inject(head);
	}

	function Elements(source, selector) {
		this.length = 0;
		source && $each(source, function(el, i) {
			el.nodeType === 1 && ( !selector || el.is(selector) ) && this.push(el);
		}, this);
	}
	$extend(Elements, {
		invoke: function(method, args) {
			var returnSelf = false,
				res = [],
				isElements = false;
			$each(this, function(el, i) {
				var retEl = el[method].apply(el, args);
				res.push( retEl );
				if ( retEl == el ) returnSelf = true;
				if ( retEl instanceof Element ) isElements = true;
			});
			return returnSelf ? this : ( isElements || !res.length ? new Elements(res) : res );
		},
		filter: function(filter) {
			if ( typeof filter == 'function' ) {
				return new Elements([].filter.call(this, filter));
			}
			return new Elements(this, filter);
		}
	}, new Array);

	function Coords2D(x, y) {
		this.x = x;
		this.y = y;
	}
	$extend(Coords2D, {
		add: function(coords) {
			return new Coords2D(this.x + coords.x, this.y + coords.y);
		},

		subtract: function(coords) {
			return new Coords2D(this.x - coords.x, this.y - coords.y);
		},

		toCSS: function() {
			return {
				left: this.x + 'px',
				top: this.y + 'px'
			};
		},

		join: function(glue) {
			glue == null && (glue = ',');
			return [this.x, this.y].join(glue);
		},

		equal: function(coord) {
			return this.join() == coord.join();
		}
	});

	function AnyEvent(e) {
		if ( typeof e == 'string' ) {
			this.originalEvent = null;
			e = {"type": e, "target": null};
		}
		else {
			this.originalEvent = e;
		}

		this.type = e.type;
		this.target = e.target || e.srcElement;
		this.relatedTarget = e.relatedTarget;
		this.fromElement = e.fromElement;
		this.toElement = e.toElement;
		this.key = e.keyCode || e.which;
		this.alt = e.altKey;
		this.ctrl = e.ctrlKey;
		this.shift = e.shiftKey;
		this.button = e.button || e.which;
		this.leftClick = this.button == 1;
		this.rightClick = this.button == 2;
		this.middleClick = this.button == 4 || this.button == 1 && this.key == 2;
		this.leftClick = this.leftClick && !this.middleClick;
		this.which = this.key || this.button;
		this.detail = e.detail;

		this.pageX = e.pageX;
		this.pageY = e.pageY;
		this.clientX = e.clientX;
		this.clientY = e.clientY;

		this.touches = e.touches ? $array(e.touches) : null;

		if ( this.touches && this.touches[0] ) {
			this.pageX = this.touches[0].pageX;
			this.pageY = this.touches[0].pageY;
		}

		if ( this.pageX != null && this.pageY != null ) {
			this.pageXY = new Coords2D(this.pageX, this.pageY);
		}
		else if ( this.clientX != null && this.clientY != null ) {
			this.pageXY = new Coords2D(this.clientX, this.clientY).add(W.getScroll());
		}

		this.data = e.dataTransfer || e.clipboardData;
		this.time = e.timeStamp || e.timestamp || e.time || Date.now();

		this.total = e.total || e.totalSize;
		this.loaded = e.loaded || e.position;
	}
	$extend(AnyEvent, {

		preventDefault: function(e) {
			if ( e = this.originalEvent ) {
				e.preventDefault();
				this.defaultPrevented = true;
			}
		},
		stopPropagation: function(e) {
			if ( e = this.originalEvent ) {
				e.stopPropagation();
				this.propagationStopped = true;
			}
		},

		setSubject: function(subject) {
			this.subject = subject;
			if ( this.pageXY ) {
				this.subjectXY = this.pageXY;
				if ( this.subject.getPosition ) {
					this.subjectXY = this.subjectXY.subtract(this.subject.getPosition());
				}
			}
		}
	});

	Event.Keys = {"enter": 13, "up": 38, "down": 40, "left": 37, "right": 39, "esc": 27, "space": 32, "backspace": 8, "tab": 9, "delete": 46};

	Event.Custom = {
		mouseenter: {
			type: 'mouseover',
			filter: function(e) {
				return e.fromElement != this && !this.contains(e.fromElement);
			}
		},
		mouseleave: {
			type: 'mouseout',
			filter: function(e) {
				return e.toElement != this && !this.contains(e.toElement);
			}
		},

		mousewheel: {
			type: 'onmousewheel' in W ? 'mousewheel' : 'mousescroll'
		},

	};

	'onmouseenter' in html && delete Event.Custom.mouseenter;
	'onmouseleave' in html && delete Event.Custom.mouseleave;

	$each([
		window, 
		document, 
		Element,
		Elements
	], function(Host) {
		Host.extend = function(methods) {
			$extend([this], methods);
		};
	});

	function Eventable(subject) {
		this.subject = subject;
		this.time = Date.now();
	}
	$extend(Eventable, {
		on: function(eventType, matches, callback) {
			callback || (callback = matches) && (matches = null);

			var options = {
				bubbles: !!matches,
				subject: this
			};

			var baseType = eventType,
				customEvent = false;
			if ( Event.Custom[eventType] ) {
				customEvent = Event.Custom[eventType];
				customEvent.type && (baseType = customEvent.type);
			}

			function onCallback(e, arg2) {
				e && !(e instanceof AnyEvent) && (e = new AnyEvent(e));

				var subject = options.subject;
				if ( e && e.target && matches ) {
					if ( !(subject = e.target.selfOrFirstAncestor(matches)) ) {
						return;
					}
				}

				if ( customEvent && customEvent.filter ) {
					if ( !customEvent.filter.call(subject, e, arg2) ) {
						return;
					}
				}

				e.subject || e.setSubject(subject);
				return callback.call(subject, e, arg2);
			}

			if ( customEvent && customEvent.before ) {
				if ( customEvent.before.call(this, options) === false ) {
					return this;
				}
			}

			var events = options.subject.$events || (options.subject.$events = {});
			events[eventType] || (events[eventType] = []);
			events[eventType].push({type: baseType, original: callback, callback: onCallback, bubbles: options.bubbles});

			options.subject.addEventListener && options.subject.addEventListener(baseType, onCallback, options.bubbles);
			return this;
		},

		off: function(eventType, callback) {
			if ( this.$events && this.$events[eventType] ) {
				var events = this.$events[eventType],
					changed = false;
				$each(events, function(listener, i) {
					if ( !callback || callback == listener.original ) {
						changed = true;
						delete events[i];
						this.removeEventListener && this.removeEventListener(listener.type, listener.callback, listener.bubbles);
					}
				}, this);
				changed && (this.$events[eventType] = events.filter(Array.defaultFilterCallback));
			}
			return this;
		},

		fire: function(eventType, e, arg2) {
			if ( this.$events && this.$events[eventType] ) {
				e || (e = new AnyEvent(eventType));
				$each(this.$events[eventType], function(listener) {
					listener.callback.call(this, e, arg2);
				}, this);
			}
			return this;
		},

		globalFire: function(globalType, localType, originalEvent, arg2) {
			var e = originalEvent ? originalEvent : new AnyEvent(localType),
				eventType = (globalType + '-' + localType).camel();
			e.target = e.subject = this;
			e.type = localType;
			e.globalType = globalType;
			W.fire(eventType, e, arg2);
			return this;
		}
	});

	$extend([W, D, Element, XMLHttpRequest], Eventable.prototype);
	W.XMLHttpRequestUpload && $extend([XMLHttpRequestUpload], Eventable.prototype);

	$extend(Node, {
		firstAncestor: function(selector) {
			var el = this;
			while ( (el = el.parentNode) && el != D ) {
				if ( el.is(selector) ) {
					return el;
				}
			}
		},

		getNext: function() {
			if ( this.nextElementSibling !== undefined ) {
				return this.nextElementSibling;
			}

			var sibl = this;
			while ( (sibl = sibl.nextSibling) && sibl.nodeType != 1 );

			return sibl;
		},
		getPrev: function() {
			if ( this.previousElementSibling !== undefined ) {
				return this.previousElementSibling;
			}

			var sibl = this;
			while ( (sibl = sibl.previousSibling) && sibl.nodeType != 1 );

			return sibl;
		},

		remove: function() {
			return this.parentNode.removeChild(this);
		},

		getParent: function() {
			return this.parentNode;
		},

		insertAfter: function(el, ref) {
			var next = ref.nextSibling; 
			if ( next ) {
				return this.insertBefore(el, next);
			}
			return this.appendChild(el);
		},

		nodeIndex: function() {
			return indexOf.call(this.parentNode.childNodes, this);
		}
	});

	$extend(document, {
		el: function(tag, attrs) {
			var el = this.createElement(tag);
			attrs && el.attr(attrs);
			return el;
		}
	});


	var EP = Element.prototype;
	$extend(Element, {
		is: EP.matches || EP.webkitMatches || EP.mozMatches || EP.msMatches || EP.oMatches || EP.matchesSelector || EP.webkitMatchesSelector || EP.mozMatchesSelector || EP.msMatchesSelector || EP.oMatchesSelector || function(selector) {
			return $$(selector).contains(this);
		},

		getValue: function() {
			if ( !this.disabled ) {
				if ( this.nodeName == 'SELECT' && this.multiple ) {
					return [].filter.call(this.options, function(option) {
						return option.selected;
					});
				}
				else if ( this.type == 'radio' || this.type == 'checkbox' && !this.checked ) {
					return;
				}
				return this.value;
			}
		},

		toQueryString: function() {
			var els = this.getElements('input[name], select[name], textarea[name]'),
				query = [];
			els.forEach(function(el) {
				var value = el.getValue();
				if ( value instanceof Array ) {
					value.forEach(function(val) {
						query.push(el.name + '=' + encodeURIComponent(val));
					});
				}
				else if ( value != null ) {
					query.push(el.name + '=' + encodeURIComponent(value));
				}
			});
			return query.join('&');
		},

		selfOrFirstAncestor: function(selector) {
			return this.is(selector) ? this : this.firstAncestor(selector);
		},

		contains: Element.prototype.contains || function(child) {
			return this.getElements('*').contains(child);
		},

		getChildren: function(selector) {
			return new Elements(this.children || this.childNodes, selector);
		},

		getFirst: function() {
			if ( this.firstElementChild !== undefined ) {
				return this.firstElementChild;
			}

			return this.getChildren().first();
		},
		getLast: function() {
			if ( this.lastElementChild !== undefined ) {
				return this.lastElementChild;
			}

			return this.getChildren().last();
		},

		attr: function(name, value, prefix) {
			prefix == null && (prefix = '');
			if ( value === undefined ) {
				if ( typeof name == 'string' ) {

					return this.getAttribute(prefix + name);
				}

				$each(name, function(value, name) {
					if ( value === null ) {
						this.removeAttribute(prefix + name);
					}
					else {

						this.setAttribute(prefix + name, value);
					}
				}, this);
			}
			else if ( value === null ) {
				this.removeAttribute(prefix + name);
			}
			else {
				if ( typeof value == 'function' ) {
					value = value.call(this, this.getAttribute(prefix + name));
				}


				this.setAttribute(prefix + name, value);
			}

			return this;
		},

		data: function(name, value) {
			return this.attr(name, value, 'data-');
		},

		getHTML: function() {
			return this.innerHTML;
		},
		setHTML: function(html) {
			this.innerHTML = html;
			return this;
		},

		getText: function() {
			return this.innerText || this.textContent;
		},
		setText: function(text) {
			this.textContent = this.innerText = text;
			return this;
		},

		getElement: function(selector) {
			return this.querySelector(selector);
		},

		getElements: function(selector) {
			return $$(this.querySelectorAll(selector));
		},

		removeClass: function(token) {
			this.classList.remove(token);
			return this;
		},
		addClass: function(token) {
			this.classList.add(token);
			return this;
		},
		toggleClass: function(token) {
			this.classList.toggle(token);
			return this;
		},
		replaceClass: function(before, after) {
			return this.removeClass(before).addClass(after);
		},
		hasClass: function(token) {
			return this.classList.contains(token);
		},

		injectBefore: function(ref) {
			ref.parentNode.insertBefore(this, ref);
			return this;
		},
		injectAfter: function(ref) {
			ref.parentNode.insertAfter(this, ref);
			return this;
		},
		inject: function(parent) {
			parent.appendChild(this);
			return this;
		},
		injectTop: function(parent) {
			parent.firstChild ? parent.insertBefore(this, parent.firstChild) : parent.appendChild(this);
			return this;
		},

		append: function(child) {
			this.appendChild(child);
			return this;
		},

		getStyle: function(property) {
			return getComputedStyle(this).getPropertyValue(property);
		},

		css: function(property, value) {
			if ( value === undefined ) {
				if ( typeof property == 'string' ) {
					return this.getStyle(property);
				}

				$each(property, function(value, name) {
					this.style[name] = value;
				}, this);
				return this;
			}

			this.style[property] = value;
			return this;
		},

		show: function() {
			if ( !cssDisplays[this.nodeName] ) {
				var el = document.el(this.nodeName).inject(this.ownerDocument.body);
				cssDisplays[this.nodeName] = el.getStyle('display');
				el.remove();
			}
			return this.css('display', cssDisplays[this.nodeName]);
		},
		hide: function() {
			return this.css('display', 'none');
		},
		toggle: function() {
			return this.getStyle('display') == 'none' ? this.show() : this.hide();
		},

		empty: function() {
			try {
				this.innerHTML = '';
			}
			catch (ex) {
				while ( this.firstChild ) {
					this.removeChild(this.firstChild);
				}
			}
			return this;
		},

		elementIndex: function() {
			return this.parentNode.getChildren().indexOf(this);
		},

		getPosition: function() {
			var bcr = this.getBoundingClientRect();
			return new Coords2D(bcr.left, bcr.top).add(W.getScroll());
		},

		getScroll: function() {
			return new Coords2D(this.scrollLeft, this.scrollTop);
		}
	});

	$extend(document, {
		getElement: Element.prototype.getElement,
		getElements: Element.prototype.getElements
	});

	$extend([W, D], {
		getScroll: function() {
			return new Coords2D(
				document.documentElement.scrollLeft || document.body.scrollLeft,
				document.documentElement.scrollTop || document.body.scrollTop
			);
		}
	});

	Event.Custom.ready = {
		before: function() {
			if ( this == document ) {
				domReadyAttached || attachDomReady();
			}
		}
	};

	function attachDomReady() {
		domReadyAttached = true;

		D.on('DOMContentLoaded', function(e) {
			this.fire('ready');
		});
	}

	function $(id, selector) {
		if ( typeof id == 'function' ) {
			if ( domIsReady ) {
				setTimeout(id, 1);
				return D;
			}

			return D.on('ready', id);
		}

		if ( !selector ) {
			return D.getElementById(id);
		}

		return D.getElement(id);
	}

	function $$(selector) {
		return $arrayish(selector) ? new Elements(selector) : D.getElements(selector);
	}

	function XHR(url, options) {
		options = $merge({}, {
			method: 'GET',
			async: true,
			send: true,
			data: null,
			url: url,
			requester: 'XMLHttpRequest',
			execScripts: true
		}, options || {});
		options.method = options.method.toUpperCase();

		var xhr = new XMLHttpRequest;
		xhr.open(options.method, options.url, options.async, options.username, options.password);
		xhr.options = options;
		xhr.on('readystatechange', function(e) {
			if ( this.readyState == 4 ) {
				var success = this.status == 200,
					eventType = success ? 'success' : 'error',
					t = this.responseText;

				try {
					this.responseJSON = (t[0] == '[' || t[0] == '{') && JSON.parse(t);
				}
				catch (ex) {}
				var response = this.responseJSON || this.responseXML || t;

				if ( this.options.execScripts ) {
					var scripts = [];
					if ( typeof response == 'string' ) {
						var regex = /<script[^>]*>([\s\S]*?)<\/script>/i,
							script;
						while ( script = response.match(regex) ) {
							response = response.replace(regex, '');
							if ( script = script[1].trim() ) {
								scripts.push(script);
							}
						}
					}
				}

				this.fire(eventType, e, response);
				this.fire('done', e, response);

				if ( this.options.execScripts && scripts.length ) {
					scripts.forEach(function(code) {
						eval(code);
					});
				}

				this.globalFire('xhr', eventType, e, response);
				this.globalFire('xhr', 'done', e, response);
			}
		});
		if ( options.method == 'POST' ) {
			if ( !$is_a(options.data, 'FormData') ) {
				var encoding = options.encoding ? '; charset=' + encoding : '';
				xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded' + encoding);
			}
		}
		if ( options.send ) {
			options.requester && xhr.setRequestHeader('X-Requested-With', options.requester);

			xhr.globalFire('xhr', 'start');
			xhr.fire('start');

			options.async ? setTimeout(function() { xhr.send(options.data); }, 1) : xhr.send(options.data);
		}
		return xhr;
	}

	Event.Custom.progress = {
		before: function(options) {
			if ( this instanceof XMLHttpRequest && this.upload ) {
				options.subject = this.upload;
			}
		}
	};

	function shortXHR(method) {
		return function(url, data, options) {
			options || (options = {});
			options.method = method;
			options.data = data;
			var xhr = XHR(url, options);
			return xhr;
		};
	}



	W.$ifsetor = $ifsetor;
	W.$arrayish = $arrayish;
	W.$array = $array;
	W.$class = $class;
	W.$is_a = $is_a;
	W.$serialize = $serialize;
	W.$copy = $copy;
	W.$merge = $merge;
	W.$each = $each;
	W.$extend = $extend;
	W.$getter = $getter;

	W.$ = $;

	W.$$ = $$;
	W.Elements = Elements;

	W.AnyEvent = AnyEvent;

	W.Eventable = Eventable;

	W.Coords2D = Coords2D;

	W.$.xhr = XHR;
	W.$.get = shortXHR('get');
	W.$.post = shortXHR('post');

	W.$.js = $loadJS;


})(this, this.document);