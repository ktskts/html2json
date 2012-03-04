/**
 * html2json
 * 
 * @author welefen
 * @lincense MIT
 * @version 1.0 - 2012.03.02
 */
this.html2json = (function() {
	var Util = {
		div : null,
		attrs: "id,name,style,class,value,src,href,width,height,title,type".split(","),
		getJson : function(childNodes, attrs, length) {
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
					var flag = false;
					//for ie6
					if(attrs){
						for(var j = 0; j < length; j++){
							if(attrs[j] == 'style'){
								var sStyle = item.getAttribute('style').cssText;
								if(sStyle){
									obj.attr["style"] = sStyle;
									flag = true;
								}
							}else{
								var attrNode = item.attributes[attrs[j]];
								if(attrNode && attrNode.nodeType === 2){
									var value = attrNode.value;
									if(value){
										obj.attr[attrs[j]] = value;
										flag = true;
									}
								}
							}
						}
					}else{
						if (item.attributes.length) {
							for ( var n = 0, l = item.attributes.length; n < l; n++) {
								var value = item.attributes[n].value;
								if(value){
									flag = true;
									obj.attr[item.attributes[n].name] = value;
								}
							}
						}
					}
					if(! flag){
						delete obj.attr;
					}
					if (item.childNodes.length < 1) {
						var text = item.innerText;
						if(text){
							obj.text = item.innerText;
						}
					} else {
						obj.child = Util.getJson(item.childNodes, attrs, length);
					}
					result.push(obj);
				} else {
					// do nothing
				}
			}
			return result;
		}
	}
	/**
	 * [html2json]
	 * @param  {[text]} text 
	 * @param  {[function]} stringify
	 * @return {[text]}
	 */
	var html2json = function(text, stringify, attrs) {
		if (!Util.div) {
			Util.div = document.createElement('div');
		}
		Util.div.innerHTML = text;
		var ie6 = /MSIE 6/.test(navigator.userAgent), allAttrs, len;
		if(ie6){
			allAttrs = Util.attrs;
			if(Object.prototype.toString.call(stringify).indexOf("Array")){
				attrs = stringify;
			}
			attrs = attrs || [];
			for(var i = 0, len = attrs.length; i < len; i++){
				allAttrs.push(attrs[i]);
			}
			len = allAttrs.length;
		}
		var result = Util.getJson(Util.div.childNodes, allAttrs, len);
		return (window.JSON && JSON.stringify || stringify)(result);
	};
	return html2json;
})();