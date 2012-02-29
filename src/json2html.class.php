<?php
/**
 * 
 * 将JSON转化为HTML
 * 一种用于富文本数据传输的解决方案
 * JSON格式为：
 * [
 * {
 * "tag": "span",
 * "attr": {"id":"welefen", "class":"suredy"},
 * "text":"haha",
 * "child": [
 * ....
 * ]
 * }
 * ]
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
	 * 是否对不在白名单里的标签属性进行过滤
	 * @var boolean
	 */
	public $checkTagAttr = true;
	/**
	 * 
	 * 标签名正则
	 * @var string
	 */
	public $tagPattern = "/^([A-Za-z\!]{1}[A-Za-z0-9\!]*)$/";
	/**
	 * 标签属性白名单
	 */
	private $_attrBlankList = array ("*" => array ("id", "class", "name" ), "a" => array ("href" ), "img" => array ("width", "src", "height", "alt" ) );
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
		$this->_attrBlankList = array_merge ( $this->_attrBlankList, $options );
	}
	/**
	 * 
	 * 添加标签属性白名单
	 */
	public function addBlank($tag, $attr) {
		$tagAttrs = $this->_attrBlankList [$tag];
		if (! $tagAttrs) {
			$tagAttrs = array ();
		}
		if (! is_array ( $attr )) {
			$attr = array ($attr );
		}
		$this->_attrBlankList [$tag] = array_merge ( $tagAttrs, $attr );
	}
	/**
	 * 
	 * 移除标签属性白名单
	 */
	public function removeBlank($tag, $attr) {
		if (! array_key_exists ( $tag, $this->_attrBlankList )) {
			return true;
		}
		if (! is_array ( $attr )) {
			$attr = array ($attr );
		}
		$attrs = array ();
		foreach ( $this->_attrBlankList [$tag] as $item ) {
			if (! in_array ( $item, $attr )) {
				$attrs [] = $item;
			}
		}
		$this->_attrBlankList [$tag] = $attrs;
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
			if (! $this->isTag ( $tag )) {
				continue;
			}
			//标签节点
			$text = '<' . $tag;
			if (count ( $item ['attr'] )) {
				$attrs = array ();
				foreach ( $item ['attr'] as $name => $value ) {
					//如果标签属性不合法，直接过滤
					if (! $this->checkTagAttr ( $tag, $name )) {
						continue;
					}
					$attrs [] = $name . '="' . $this->escapeHtml ( $value ) . '"';
				}
				$text .= ' ' . join ( ' ', $attrs );
			}
			if (in_array ( $tag, $this->singleTag )) {
				$text .= '/>';
			} else {
				$text .= '>'. $item['text'];
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
	 * 检测标签的属性是否合法
	 * @param string $tag
	 * @param string $attr
	 */
	public function checkTagAttr($tag, $attr) {
		$tagAttr = $this->_attrBlankList [$tag];
		$attrList = $this->_attrBlankList ['*'];
		if ($tagAttr) {
			$attrList = array_merge ( $attrList, $tagAttr );
		}
		$attr = strtolower ( $attr );
		return in_array ( $attr, $attrList );
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
	 * 检测标签名是否合法
	 * @param string $tag
	 */
	public function isTag($tag) {
		return preg_match ( $this->tagPattern, $tag );
	}
}