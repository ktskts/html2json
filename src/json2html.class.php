<?php
/**
 * 
 * 将JSON转化为HTML
 * 一种用于富文本数据传输的解决方案
 * JSON格式为：
 * [{
 * "tag": "span",
 * "attr": {"id":"welefen", "class":"suredy"},
 * "text":"haha",
 * "child": [
 * 		...
 * 	]
 * }, ...]
 * @author welefen
 * @license MIT
 * @version 1.0
 *
 */
class json2html {
	/**
	 * 
	 * 要处理的JSON文本
	 * @var string
	 */
	public $json = '';
	/**
	 * 
	 * 选项
	 * @var array
	 */
	public $options = array ("checkTag" => true, "checkAttr" => true, "maxAttrValueLength" => 50, "filterValue" => true );
	/**
	 * 
	 * 标签白名单
	 * @var array
	 */
	public $tagBlankList = array ("a", "span", "img", "p", "br", "div", "strong", "b", "ul", "li", "ol" );
	/**
	 * 标签属性白名单
	 */
	public $attrBlankList = array ("*" => array ("id", "class", "name", "style" ), "a" => array ("href", "title" ), "img" => array ("width", "src", "height", "alt" ) );
	/**
	 * 
	 * 单一标签
	 * @var array
	 */
	public $singleTag = array ("br", "input", "link", "meta", "!doctype", "basefont", "base", "area", "hr", "wbr", "param", "img", "isindex", "?xml", "embed" );
	
	/**
	 * 
	 * 构造函数
	 * @param string $json
	 * @param array $options
	 */
	public function __construct($json = '', $options = array()) {
		$this->json = $json;
		$this->options = array_merge ( $this->options, $options );
	}
	/**
	 * 
	 * 添加标签白名单
	 * @param string or array $tag
	 */
	public function addTagBlank($tag) {
		if (! is_array ( $tag )) {
			$tag = array ($tag );
		}
		$this->tagBlankList = array_merge ( $this->tagBlankList, $tag );
	}
	/**
	 * 
	 * 移除标签白名单
	 * @param string or array $tag
	 */
	public function removeTagBlank($tag) {
		if (! is_array ( $tag )) {
			$tag = array ($tag );
		}
		$result = array ();
		foreach ( $this->tagBlankList as $item ) {
			if (! in_array ( $item, $tag )) {
				$result [] = $item;
			}
		}
		$this->tagBlankList = $result;
	}
	/**
	 * 
	 * 添加标签属性白名单
	 */
	public function addAttrBlank($tag, $attr) {
		$tagAttrs = $this->attrBlankList [$tag];
		if (! $tagAttrs) {
			$tagAttrs = array ();
		}
		if (! is_array ( $attr )) {
			$attr = array ($attr );
		}
		$this->attrBlankList [$tag] = array_merge ( $tagAttrs, $attr );
	}
	/**
	 * 
	 * 移除标签属性白名单
	 */
	public function removeAttrBlank($tag, $attr) {
		if (! array_key_exists ( $tag, $this->attrBlankList )) {
			return true;
		}
		if (! is_array ( $attr )) {
			$attr = array ($attr );
		}
		$attrs = array ();
		foreach ( $this->attrBlankList [$tag] as $item ) {
			if (! in_array ( $item, $attr )) {
				$attrs [] = $item;
			}
		}
		$this->attrBlankList [$tag] = $attrs;
	}
	/**
	 * 
	 * 生成html
	 */
	public function run() {
		try {
			$json = json_decode ( $this->json, true );
			if (! is_array ( $json )) {
				return $this->escapeHtml ( $this->json );
			}
			return $this->toHtml ( $json );
		} catch ( Exception $e ) {
			return $this->escapeHtml ( $this->json );
		}
		return $this->escapeHtml ( $this->json );
	}
	/**
	 * 
	 * 转化为html
	 * @param array $json
	 */
	public function toHtml($json) {
		$result = array ();
		foreach ( $json as $item ) {
			$tag = strtolower ( $item ['tag'] );
			//文本节点
			if (! $tag) {
				$result [] = $item ['text'];
				continue;
			}
			if ($this->options ['checkTag'] && ! $this->checkTagName ( $tag )) {
				continue;
			}
			//标签节点
			$text = '<' . $tag;
			if (count ( $item ['attr'] )) {
				$attrs = array ();
				foreach ( $item ['attr'] as $name => $value ) {
					//如果标签属性不合法，直接过滤
					if ($this->options ['checkAttr'] && ! $this->checkTagAttr ( $tag, $name )) {
						continue;
					}
					if ($this->options ['filterValue']) {
						$value = $this->filterAttrValue ( $value );
					}
					if ($this->options ['maxAttrValueLength'] && strlen ( $value ) > $this->options ['maxAttrValueLength']) {
						$value = substr ( $value, 0, $this->options ['maxAttrValueLength'] );
					}
					$attrs [] = $name . '="' . $this->escapeHtml ( $value ) . '"';
				}
				$text .= ' ' . join ( ' ', $attrs );
			}
			if (in_array ( $tag, $this->singleTag )) {
				$text .= '/>';
			} else {
				$text .= '>' . $item ['text'];
				if (count ( $item ['child'] )) {
					$text .= $this->toHtml ( $item ['child'] );
				}
				$text .= '</' . $tag . '>';
			}
			$result [] = $text;
		}
		return join ( '', $result );
	}
	/**
	 * 
	 * 检测标签名是否合法
	 * @param string $tag
	 */
	public function checkTagName($tag) {
		return in_array ( $tag, $this->tagBlankList );
	}
	/**
	 * 
	 * 检测标签的属性是否合法
	 * @param string $tag
	 * @param string $attrName
	 */
	public function checkTagAttr($tag, $attrName) {
		$tagAttr = $this->attrBlankList [$tag];
		$attrList = $this->attrBlankList ['*'];
		if ($tagAttr) {
			$attrList = array_merge ( $attrList, $tagAttr );
		}
		$attrName = strtolower ( $attrName );
		return in_array ( $attrName, $attrList );
	}
	/**
	 * 
	 * 使用html方式进行转义
	 * @param string $string
	 */
	public function escapeHtml($string) {
		return str_replace ( array ('<', '>', '"', "'" ), array ('&lt;', '&gt;', "&quot;", "&#39;" ), $string );
	}
	/**
	 * 
	 * 过滤属性值
	 * @param string $value
	 */
	public function filterAttrValue($value = '') {
		//移除expression
		$value = preg_replace ( "/\:\s*expression\s*\(/ies", "", $value );
		//移除base64编码
		$value = preg_replace ( "/\+[a-z0-9]+\-/ies", "", $value );
		return $value;
	}
}