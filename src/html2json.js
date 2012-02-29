/**
 * html2json
 * 
 * @author welefen
 * @lincense MIT
 */
this.html2json = (function() {
	var Util = {
		div : null,
		getJson : function(childNodes) {
			var result = [];
			for ( var i = 0, len = childNodes.length; i < len; i++) {
				var item = childNodes[i];
				if (item.nodeType == 3) {
					result.push({
						text : item.nodeValue
					})
				} else if (item.nodeType == 1) {
					var obj = {
						tag : item.nodeName.toLowerCase(),
						attr : {}
					};
					if (item.attributes.length) {
						for ( var n = 0, l = item.attributes.length; n < l; n++) {
							obj.attr[item.attributes[n].name] = item.attributes[n].value;
						}
					}
					if (item.childNodes.length <= 1) {
						obj.text = item.innerText;
					} else {
						obj.child = Util.getJson(item.childNodes);
					}
					result.push(obj);
				} else {
					// do nothing
				}
			}
			return result;
		}
	}
	var html2json = function(text) {
		if (!Util.div) {
			Util.div = document.createElement('div');
		}
		Util.div.innerHTML = text;
		var result = Util.getJson(Util.div.childNodes);
		return JSON.stringify(result);
	}
	return html2json;
})()