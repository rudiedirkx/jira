// build.html#-json_alias,-ifsetor,-array,-class,-serialize,-copy,-array_invoke,-array_unique,-array_each,-array_intersect,-array_diff,-string_camel,-string_repeat,-asset_js,-coords2d,-coords2d_add,-coords2d_subtract,-coords2d_tocss,-coords2d_join,-coords2d_equal,-anyevent_lmrclick,-anyevent_touches,-anyevent_pagexy,-anyevent_summary,-anyevent_subject,-_event_custom_mousenterleave,-event_custom_mousewheel,-event_custom_directchange,-native_extend,-eventable_globalfire,-element_index,-element_attr2method,-element_attr2method_html,-element_attr2method_text,-element_prop,-element_value,-element_toquerystring,-element_empty,-element_position,-element_scroll,-windoc_scroll,-xhr_global

(function(W, D) {

	"use strict";

	var html = D.documentElement,
		head = html.getElementsByTagName('head')[0];

	var r = function r( id, sel ) {
		return r.$(id, sel);
	};

	var domReadyAttached = false;
	var cssDisplays = {};
	r.arrayish = function(obj) {
		return obj instanceof Array || ( typeof obj.length == 'number' && typeof obj != 'string' && ( obj[0] !== undefined || obj.length == 0 ) );
	};

	r.is_a = function(obj, type) {
		return window[type] && obj instanceof window[type];
	};
	r.merge = function(base) {
		for ( var i=1, L=arguments.length; i<L; i++ ) {
			r.each(arguments[i], function(value, name) {
				base[name] = value;
			});
		}
		return base;
	};
	r.each = function(source, callback, context) {
		if ( r.arrayish(source) ) {
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
	};

	r.extend = function(Hosts, proto, Super) {
		if ( !(Hosts instanceof Array) ) {
			Hosts = [Hosts];
		}

		r.each(Hosts, function(Host) {
			if ( Super ) {
				Host.prototype = Super;
				Host.prototype.constructor = Host;
			}

			var methodOwner = Host.prototype ? Host.prototype : Host;
			r.each(proto, function(fn, name) {
				methodOwner[name] = fn;

				if ( Host == Element && !Elements.prototype[name] ) {
					Elements.prototype[name] = function() {
						return this.invoke(name, arguments);
					};
				}
			});
		});
	};

	r.getter = function(Host, prop, getter) {
		Object.defineProperty(Host.prototype, prop, {get: getter});
	};
	r.extend(Array, {
		contains: function(obj) {
			return this.indexOf(obj) != -1;
		},
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
	var indexOf = [].indexOf;

	if (!('classList' in html)) {
		var push = [].push;
		W.DOMTokenList = function DOMTokenList(el) {
			this._el = el;
			el.$classList = this;
			this._reinit();
		}
		r.extend(W.DOMTokenList, {
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
				this._el.className = [].join.call(this, ' ');
			},
			add: function(token) {
				if ( !this.contains(token) ) {
					push.call(this, token);
					this.set();
				}
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
					[].splice.call(this, i, 1);
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

		r.getter(Element, 'classList', function() {
			return this.$classList ? this.$classList._reinit() : new W.DOMTokenList(this);
		});
	}
	function Elements(source, selector) {
		this.length = 0;
		source && r.each(source, function(el, i) {
			el.nodeType === 1 && ( !selector || el.is(selector) ) && this.push(el);
		}, this);
	}
	r.extend(Elements, {
		invoke: function(method, args) {
			var returnSelf = false,
				res = [],
				isElements = false;
			r.each(this, function(el, i) {
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
		this.which = this.key || this.button;
		this.detail = e.detail;

		this.pageX = e.pageX;
		this.pageY = e.pageY;
		this.clientX = e.clientX;
		this.clientY = e.clientY;

		this.data = e.dataTransfer || e.clipboardData;
		this.time = e.timeStamp || e.timestamp || e.time || Date.now();

		this.total = e.total || e.totalSize;
		this.loaded = e.loaded || e.position;
	}
	r.extend(AnyEvent, {
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
		}

	});
	Event.Keys = {enter: 13, up: 38, down: 40, left: 37, right: 39, esc: 27, space: 32, backspace: 8, tab: 9, "delete": 46};
	Event.Custom = {
	};

	function Eventable(subject) {
		this.subject = subject;
		this.time = Date.now();
	}
	r.extend(Eventable, {
		on: function(eventType, matches, callback) {
			callback || (callback = matches) && (matches = null);

			var options = {
				bubbles: !!matches,
				subject: this || W
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
					if ( !(subject = e.target.selfOrAncestor(matches)) ) {
						return;
					}
				}

				if ( customEvent && customEvent.filter ) {
					if ( !customEvent.filter.call(subject, e, arg2) ) {
						return;
					}
				}

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
				r.each(events, function(listener, i) {
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
				r.each(this.$events[eventType], function(listener) {
					listener.callback.call(this, e, arg2);
				}, this);
			}
			return this;
		}
	});
	r.extend([W, D, Element, XMLHttpRequest], Eventable.prototype);
	W.XMLHttpRequestUpload && r.extend([XMLHttpRequestUpload], Eventable.prototype);
	r.extend(Node, {
		ancestor: function(selector) {
			var el = this;
			while ( (el = el.parentNode) && el != D ) {
				if ( el.is(selector) ) {
					return el;
				}
			}
		},
		getNext: function(selector) {
			if ( !selector ) {
				return this.nextElementSibling;
			}

			var sibl = this;
			while ( (sibl = sibl.nextElementSibling) && !sibl.is(selector) );
			return sibl;
		},
		getPrev: function(selector) {
			if ( !selector ) {
				return this.previousElementSibling;
			}

			var sibl = this;
			while ( (sibl = sibl.previousElementSibling) && !sibl.is(selector) );
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
		}
	});

	r.extend(document, {
		el: function(tag, attrs) {
			var el = this.createElement(tag);
			attrs && el.attr(attrs);
			return el;
		}
	});
	var EP = Element.prototype;
	r.extend(Element, {
		is: EP.matches || EP.matchesSelector || EP.webkitMatchesSelector || EP.mozMatchesSelector || EP.msMatchesSelector || function(selector) {
			return $$(selector).contains(this);
		},
		selfOrAncestor: function(selector) {
			return this.is(selector) ? this : this.ancestor(selector);
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

				r.each(name, function(value, name) {
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
			if ( typeof child == 'string' ) {
				child = D.createTextNode(child);
			}
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

				r.each(property, function(value, name) {
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
		}
	});

	r.extend(document, {
		getElement: Element.prototype.getElement,
		getElements: Element.prototype.getElements
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
			if ( D.readyState == 'interactive' || D.readyState == 'complete' ) {
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
		return r.arrayish(selector) ? new Elements(selector) : D.getElements(selector);
	}
	function XHR(url, options) {
		options = r.merge({}, {
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
		xhr.on('load', function(e) {
			var success = this.status == 200,
				eventType = success ? 'success' : 'error',
				t = this.responseText;

			try {
				this.responseJSON = (t[0] == '[' || t[0] == '{') && JSON.parse(t);
			}
			catch (ex) {}
			var response = this.responseJSON || t;

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
					eval.call(W, code);
				});
			}

		});
		xhr.on('error', function(e) {
			this.fire('done', e);

		});
		if ( options.method == 'POST' ) {
			if ( !r.is_a(options.data, 'FormData') ) {
				var encoding = options.encoding ? '; charset=' + encoding : '';
				xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded' + encoding);
			}
		}
		if ( options.send ) {
			options.requester && xhr.setRequestHeader('X-Requested-With', options.requester);

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
	r.$ = $;
	W.$ = W.r = r;

	r.$$ = $$;
	r.xhr = XHR;
	r.get = shortXHR('get');
	r.post = shortXHR('post');
	W.$$ = $$;
	W.Elements = Elements;
	W.AnyEvent = AnyEvent;
	W.Eventable = Eventable;
})(this, this.document);
