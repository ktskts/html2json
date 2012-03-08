#!/usr/bin/env python3.2
"""
json2html for python.
"""
import os
import re
import json
import sys

reload(sys)
sys.setdefaultencoding("UTF-8")

class json2html:
	
	VERSION = "1.0"

	json = ''

	parseJsonError = False

	#options
	options = {
		"checkTag" : True,
		"checkAttr" : True,
		"filterAttrValue" : True,
		"escapeHtml" : True,
		"tagAttrRequired" : True,
		"tagChildRequired" : True
	}
	#tag blank for filter
	tagBlankList = ["a", "span", "img", "p", "br", "div", "strong", "b", "ul", "li", "ol", "embed","object","param", "u", "em" ]
	#tag attr blank for filter
	attrBlankList = {
		"*" : ["id", "class", "name", "style", "value" ], 
		"a" : ["href", "title" ], 
		"img" : ["width", "src", "height", "alt" ],
		"embed" : ["width", "height", "allowscriptaccess", "type", "src"],
		"param" : ["allowscriptaccess"],
	}
	tagAttrRequired = {
		"embed" : {
			"allowscriptaccess" : "never",
			"type": "application/x-shockwave-flash"
		}
	}
	#tag child required
	tagChildRequired = {
		"object" : {
			"param" : [{
				"where" : {"name" : "allowscriptaccess"},
				"value" : {"value" : "never"}
			}]
		}
	}
	#single tag
	singleTag = ["br", "input", "link", "meta", "!doctype", 
		"basefont", "base", "area", "hr", "wbr", 
		"param", "img", "isindex", "?xml", "embed" ]
	# style value blank pattern
	styleValueBlankList =  {
		'font-family' : "^(.){2,20}$", 
		'font-size' : "^\d+(?:[a-z]{2,5})?$", 
		"color" : "^(rgb\s*\(\d+\s*,\s*\d+\s*,\s*\d+\))|(\#[0-9a-f]{6})$",
		"text-align" : ["left", "right", "center"],
		"background-color" : "^(rgb\s*\(\d+\s*,\s*\d+\s*,\s*\d+\))|(\#[0-9a-f]{6})$",
	};

	def __init__(self, json = "", options = {}):
		self.json = json
		self.options.update(options)

	def run(self):
		#try:
		jsonObj = json.loads(self.json)
			#print(jsonObj)
		result = self.toHtml(jsonObj)
		return result;
		#except ValueError:
		#	pass
		self.parseJsonError = True;
		return self.escapeHtml(self.json)
	# json to html
	def toHtml(self, json):
		result = []
		#print(json)
		for item in json:
			if "tag" not in item:
				if "text" in item:
					result.append(self.escapeHtml(str(item["text"])))
				continue
			tag = str(item["tag"])
			if  self.checkTagName(tag) == False:
				continue
			#text
			text = '<' + tag
			attr = {}
			if "attr" in item:
				attr = item["attr"]
			tagAttrs = self.getTagAttrs(attr, tag)
			attrs = []
			for (name, value) in tagAttrs.items():
				if not self.checkTagAttr(tag, name):
					continue
				value = self.filterAttrValue(value, name, tag)
				attrs.append(name + '="' + self.escapeHtml(value)+'"')
			text += " " + " ".join(attrs)
			if tag in self.singleTag:
				text += '/>'
			else :
				text = text.strip()
				text += '>'
				if "text" in item:
					text += item['text']
				if "child" in item:
					child = self.getTagChild(item['child'], tag)
					if isinstance(child, list):
						text += self.toHtml(child)
				text += '</' +  tag + '>'
			result.append(text);
		return ''.join(result)

	def getTagChild(self, child, tag):
		if not isinstance(child, list):
			child = []
		if not self.options['tagChildRequired']:
			return child
		if tag not in self.tagChildRequired:
			return child
		mustChild = self.tagChildRequired[tag]
		first = True
		result = []
		for item in child:
			if 'tag' not in item:
				result.append(item)
			else :
				childTag = item['tag'].lower()
				if childTag not in mustChild:
					result.append(item)
				else : 
					mustAttrs = mustChild[childTag]
					if "attr" in item:
						attrs = item["attr"]
					else:
						attrs = {}
					for (k, it) in mustAttrs.items():
						where = it['where']
						addValue = it['value']
						equal = True
						flag = False
						for (name, value) in attrs.items():
							nameLower = name.lower()
							valueLower = value.lower()
							if nameLower in where and valueLower == where[nameLower]:
								del attrs[name]
								attrs[nameLower] = valueLower
								flag = True
								break
						if flag:
							for (name, value) in attrs.items():
								nameLower = name.lower()
								if nameLower in addValue:
									del attrs[name]
									attrs = attrs.update(addValue)
									flag = True
							del mustAttrs[k]

					mustChild[childTag] = mustAttrs
					result.append(item)

		for (t, tagChild) in mustChild.items():
			for i in tagChild.items():
				where = i['where']
				value = i['value']
				a = where.update(value)
				result.append({
					'text' : '',
					'tag' : t,
					'attr' : a,
					'child' : []
				})
		
		return result
	#filter attr value
	def filterAttrValue(self, value = '', attrName = '' , tag = ''):
		if not self.options['filterAttrValue']:
			return value
		method = '_filter' + (attrName[0].upper() + attrName[1:].lower()) + 'AttrValue'
		if method in dir(self):
			m  = getattr(self, method)
			return m(value, tag)
		return value
	#check tag attr
	def checkTagAttr(self, tag, name):
		if not self.options["checkAttr"]:
			return True
		attrBlankList = self.attrBlankList["*"]
		if tag in self.attrBlankList:
			attrBlankList.extend(self.attrBlankList[tag])
		name = name.lower()
		if name in attrBlankList:
			return True
		return False
	#check tag
	def checkTagName(self, tag):
		if self.options["checkTag"] != True:
			return True
		if tag in self.tagBlankList:
			return True
		return False
	# get tag attrs
	def getTagAttrs(self, attrs, tag):
		if not isinstance(attrs, dict):
			attrs = {}
		if not self.options["tagAttrRequired"] :
			return arrts
		if tag in self.tagAttrRequired:
			attrs.update(self.tagAttrRequired[tag])
		return attrs
	# escape html
	def escapeHtml(self, text):
		if self.options["escapeHtml"]:
			return text.replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;').replace("'", '&#39;')
		return text
	#filter src value 
	def _filterSrcAttrValue(self, value, tag):
		pattern = "^http(?:s)?\:\/\/"
		if re.match(pattern, value):
			return value
		return ""
	#filter href attr value
	def _filterHrefAttrvalue(self, value, tag):
		return self._filterSrcAttrValue(value, tag)
	#filter style attr value
	def _filterStyleAttrValue(self, value, tag):
		#
		items = value.split(';')
		result = []
		for item in items:
			item = item.strip()
			mix = item.split(':', 1)
			if len(mix) < 2:
				continue
			name = str(mix[0]).strip().lower()
			val = str(mix[1]).strip()
			if not name or not len(val) or name not in self.styleValueBlankList:
				continue
			pattern = self.styleValueBlankList[name];
			if not pattern:
				result.append(name + ':' + val)
			else :
				flag = False
				if isinstance(pattern, list):
					if pattern.__contains__(val):
						flag = True
				else :
					try:
						if re.match(pattern, val):
							flag = True
					except ValueError:
						if pattern == val:
							flag = True
				if flag:
					result.append(name + ':' + val)

		return ';'.join(result)

	def addTagBlank(self, tag):
		if not isinstance(tag, list):
			tag = [tag]
		self.tagBlankList = self.tagBlankList.extend(tag)

	def removeTagBlank(self, tag):
		if not isinstance(tag, list):
			tag = [tag]
		result = []
		for item in tag:
			if item not in self.tagBlankList:
				result.append(item)
		self.tagBlankList = result

	def addAttrBlank(self, attr, tag = '*'):
		if not tag:
			tag = "*"
		if not isinstance(attr, list):
			attr = [attr]
		oldAttrs = []
		if tag in self.attrBlankList:
			oldAttrs = self.attrBlankList[tag]
		self.attrBlankList[tag] = oldAttrs.extend[attr]

	def removeAttrBlank(self, attr, tag = ''):
		if not tag :
			tag = '*'
		if not isinstance(attr, list):
			attr = [attr]
		if tag not in self.attrBlankList:
			return True
		attrs = self.attrBlankList[tag];
		result = {}
		for item in attrs:
			if item not in attr:
				result.append(item)
		self.attrBlankList[tag] = result

	def addStyleValueBlank(self, style, value):
		self.styleValueBlankList[style] = value

	def removeStyleValueBlank(self, style):
		if style in self.styleValueBlankList:
			del self.styleValueBlankList[style]

	def addTagAttrRequired(self, attrs, tag):
		if not isinstance(attrs, dict):
			return False
		if not tag:
			return False
		attr = {}
		if tag in self.tagAttrRequired:
			attr = self.tagAttrRequired[tag]
		self.tagAttrRequired = attr.update(attrs)

	def removeTagAttrRequired(self, attrs, tag):
		"""
		 remove tag attr required
		"""
		if not isinstance(attrs, list) or not tag:
			return False
		if tag not in self.tagAttrRequired:
			return False
		result = {}
		for (key, value) in self.tagAttrRequired[tag].items():
			if key not in attrs:
				result[key] = value
		self.tagAttrRequired[tag] = result
if __name__ == '__main__':
	jsonFile = "../test/json/1.json"
	if len(sys.argv) == 2:
		jsonFile = sys.argv[1]
	with open(jsonFile) as f:
		content = open(jsonFile).read()
	instance = json2html(content)
	result = instance.run();
	print(result);
