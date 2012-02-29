/**
 * html2json
 * 
 * @author welefen
 * @lincense MIT
 */
this.html2json1 = (function() {
	var Util = {
		div : null,
		escapable : /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
		meta : {
			'\b' : '\\b',
			'\t' : '\\t',
			'\n' : '\\n',
			'\f' : '\\f',
			'\r' : '\\r',
			'"' : '\\"',
			'\\' : '\\\\'
		},
		quote : function(string) {
			Util.escapable.lastIndex = 0;
			return Util.escapable.test(string) ? '"'
					+ string.replace(Util.escapable, function(a) {
						var c = Util.meta[a];
						return typeof c === 'string' ? c : '\\u'
								+ ('0000' + a.charCodeAt(0).toString(16))
										.slice(-4);
					}) + '"' : '"' + string + '"';
		},
		getJson : function(childNodes) {
			var result = [];
			for ( var i = 0, len = childNodes.length; i < len; i++) {
				var item = childNodes[i];
				if (item.nodeType == 3) {
					result.push([ '{"text":', Util.quote(item.nodeValue), '}' ]
							.join(''));
				} else if (item.nodeType == 1) {
					var obj = [], attr = [];
					obj.push('{"tag":',
							Util.quote(item.nodeName.toLowerCase()),
							',"attr":{');
					if (item.attributes.length) {
						attr = [];
						for ( var n = 0, l = item.attributes.length; n < l; n++) {
							attr.push(Util.quote(item.attributes[n].name), ':',
									Util.quote(item.attributes[n].value));
						}
						obj.push(attr.join(','));
					}
					obj.push('},');
					if (item.childNodes.length == 1) {
						obj.push('"text"', Util.quote(item.innerText));
					} else {
						obj.push('"child":', Util.getJson(item.childNodes));
					}
					obj.push(']');
					result.push(obj.join(''));
				} else {
					// do nothing
				}
			}
			return '[' + result.join(',') + ']';
		}
	};
	var html2json = function(text) {
		if (!Util.div) {
			Util.div = document.createElement('div');
		}
		Util.div.innerHTML = text;
		return Util.getJson(Util.div.childNodes);
	};
	return html2json;
})()