<?php
/**
 * 
 * 将JSON转化为HTML，并且可以灵活的进行安全过滤
 * 一种用于富文本数据传输的解决方案，具体的见： http://www.welefen.com/html2json-for-rich-content-transfer.html
 * JSON格式为：
 * [{
 * "tag": "span",
 * "attr": {"id":"welefen", "class":"suredy"},
 * "text":"haha",
 * "child": [
 * ...
 * ]
 * }, ...]
 * @author welefen
 * @license MIT
 * @version 1.0 - 2012.03.01
 *
 */
class json2html {
	/**
	 * 
	 * 版本号
	 * @var const
	 */
	const VERSION = 1.0;
	/**
	 * 
	 * 要处理的JSON文本
	 * @var string
	 */
	public $json = '';
	/**
	 * 
	 * 使用JSON解析是否失败
	 * @var boolean
	 */
	public $jsonParseError = false;
	/**
	 * 
	 * 选项
	 * @var array
	 */
	public $options = array (
		"checkTag" => true,  //是否检测tag
		"checkAttr" => true,  //是否检测属性名
		"filterAttrValue" => true,  //是否过滤属性值
		"escapeHtml" => true, 	//是否进行html转码
		"tagAttrRequired" => true, //标签必须包含的属性
		"tagChildRequired" => true, //标签必须包含的子元素
	);
	/**
	 * 
	 * 标签白名单
	 * @var array
	 */
	public $tagBlankList = array (
		"a", "span", "img", "p", "br", 
		"div", "strong", "b", "ul", "li", "ol", "embed","object","param", "u", "em" 
	);
	/**
	 * 标签属性白名单
	 * @var array
	 */
	public $attrBlankList = array (
		"*" => array ("id", "class", "name", "style", "value" ), 
		"a" => array ("href", "title" ), 
		"img" => array ("width", "src", "height", "alt" ),
		"embed" => array("width", "height", "allowscriptaccess", "type", "src"),
		"param" => array("allowscriptaccess"),
	);
	/**
	 * 
	 * 标签里style值的白名单
	 * @var array
	 */
	public $styleValueBlankList = array (
		'font-family' => "/^(.){2,20}$/", 
		'font-size' => "/^\d+[a-z]{2,5}$/", 
		"color" => "/^(rgb\s*\(\d+\s*,\s*\d+\s*,\s*\d+\))|(\#[0-9a-f]{6})$/",
		"text-align" => array("left", "right", "center"),
		"background-color" => "/^(rgb\s*\(\d+\s*,\s*\d+\s*,\s*\d+\))|(\#[0-9a-f]{6})$/",
	);
	/**
	 * 
	 * tag必须包含的属性
	 * @var array
	 */
	public $tagAttrRequired = array (
		"embed" => array (
			"allowscriptaccess" => "never", 
			"type" => "application/x-shockwave-flash" 
		),
	);
	/**
	 * 
	 * 必须包含子元素
	 * @var array
	 */
	public $tagChildRequired = array(
		"object" => array(
			/**
			 * object里必须包含param,并且是指定的属性，如果有这个子标签但属性值不一致，则覆盖掉
			 */
			"param" => array(
				array("where" => array("name" => "allowscriptaccess"), "value"=> array("value" => "never")),
			)
		)
	);
	/**
	 * 
	 * 单一标签
	 * @var array
	 */
	public $singleTag = array (
		"br", "input", "link", "meta", "!doctype", 
		"basefont", "base", "area", "hr", "wbr", 
		"param", "img", "isindex", "?xml", "embed" 
	);
	
	/**
	 * 
	 * 构造函数
	 * @param string $json
	 * @param array $options
	 */
	public function __construct($json = '', $options = array()) {
		$this->json = $json;
		$this->options = array_merge ( $this->options, $options );
		$this->init();
	}
	/**
	 * init method
	 * @return [null]
	 */
	public function init(){

	}
	/**
	 * 
	 * 生成html
	 */
	public function run() {
		try {
			$json = json_decode ( $this->json, true );
			if (is_array($json)){
				return $this->toHtml ( $json );
			}			
		} catch ( Exception $e ) {}
		$this->jsonParseError = true;
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
				$result [] = $this->escapeHtml ( $item ['text'] );
				continue;
			}
			if (! $this->checkTagName ( $tag )) {
				continue;
			}
			//标签节点
			$text = '<' . $tag;
			$tagAttrs = $this->getTagAttrs($item ['attr'], $tag);
			$attrs = array ();
			foreach ( $tagAttrs as $name => $value ) {
				//如果标签属性不合法，直接过滤
				if (! $this->checkTagAttr ( $tag, $name )) {
					continue;
				}
				$value = $this->filterAttrValue ( $value, $name, $tag );
				$attrs [] = $name . '="' . $this->escapeHtml ( $value ) . '"';
			}
			$text .= ' ' . join ( ' ', $attrs );
			if (in_array ( $tag, $this->singleTag )) {
				$text .= '/>';
			} else {
				$text .= '>' . $item ['text'];
				$child = $this->getTagChild($item['child'], $tag);
				if ( count ( $child )) {
					$text .= $this->toHtml ( $child );
				}
				$text .= '</' . $tag . '>';
			}
			$result [] = $text;
		}
		return join ( '', $result );
	}
	/**
	 * 
	 * 获取标签属性
	 * @param string or array $attrs
	 * @param string $tag
	 */
	public function getTagAttrs($attrs, $tag){
		if (! is_array($attrs)){
			$attrs = array();
		}
		if (! $this->options['tagAttrRequired']){
			return $attrs;
		}
		$attrRequired = $this->tagAttrRequired[$tag];
		if (is_array($attrRequired)){
			$attrs = array_merge($attrs, $attrRequired);
		}
		$attrs = array_unique($attrs);
		return $attrs;
	}
	/**
	 * 
	 * 获取元素的子集
	 * @param array $child
	 * @param string $tag
	 */
	public function getTagChild($child, $tag){
		if (! is_array($child)){
			$child = array();
		}
		if (! $this->options['tagChildRequired']){
			return $child;
		}
		$mustChild = $this->tagChildRequired[$tag];
		if (! $mustChild){
			return $child;
		}
		$first = true;
		$result = array();
		foreach ($child as $item){
			$childTag = strtolower($item['tag']);
			if ($childTag && array_key_exists($childTag, $mustChild)){
				$mustAttrs = $mustChild[$childTag];
				$attrs = & $item['attr'];
				if(!is_array($attrs)){
					$attrs = array();
				}
				foreach ($mustAttrs as $k => $it){
					$where = $it['where'];
					$addValue = $it['value'];
					$equal = true;
					$flag = false;
					foreach ($attrs as $name=>$value){
						$nameLower = strtolower($name);
						$valueLower = strtolower($value);
						if (array_key_exists($nameLower, $where) && $valueLower === $where[$nameLower]){
							unset($attrs[$name]);
							$attrs[$nameLower] = $valueLower;
							$flag = true;
							break;
						}
					}
					if ($flag){
						foreach ($attrs as $name=>$value){
							$nameLower = strtolower($name);
							if (array_key_exists($nameLower, $addValue)){
								unset($attrs[$name]);
								$attrs = array_merge($attrs, $addValue);
								$flag = true;
							}
						}
						unset($mustAttrs[$k]);
						break;
					}
				}
				$mustChild[$childTag] = $mustAttrs;
				$result[] = $item;
			}else{
				$result[] = $item;
			}
		}
		foreach ($mustChild as $t => $tagChild){
			foreach ($tagChild as $i){
				$a = array_merge($i['where'], $i['value']);
				$result [] = array('text'=>'', "tag"=> $t, "attr"=> $a, "child"=> array());
			}
		}
		return $result;
	}
	/**
	 * 
	 * 检测标签名是否合法
	 * @param string $tag
	 */
	public function checkTagName($tag) {
		if (! $this->options ['checkTag']) {
			return true;
		}
		return in_array ( $tag, $this->tagBlankList );
	}
	/**
	 * 
	 * 检测标签的属性是否合法
	 * @param string $tag
	 * @param string $attrName
	 */
	public function checkTagAttr($tag, $attrName) {
		if (! $this->options ['checkAttr']) {
			return true;
		}
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
		if ($this->options ['escapeHtml']) {
			return str_replace ( array ('<', '>', '"', "'" ), array ('&lt;', '&gt;', "&quot;", "&#39;" ), $string );
		}
		return $string;
	}
	/**
	 * 
	 * 过滤属性值
	 * @param string $value
	 */
	public function filterAttrValue($value = '', $attrName = '', $tag = '') {
		if (! $this->options ['filterAttrValue']) {
			return $value;
		}
		$method = '_filter' . $attrName . 'AttrValue';
		if (method_exists ( $this, $method )) {
			return $this->$method ( $value, $tag );
		}
		return $value;
	}
	/**
	 * 
	 * 过滤src的值
	 * @param string $value
	 * @param string $tag
	 */
	protected function _filterSrcAttrValue($value = '', $tag = '') {
		$httpPattern = "/^http(?:s)?\:\/\//ies";
		if (preg_match ( $httpPattern, $value )) {
			return $value;
		}
		return '';
	}
	/**
	 * 
	 * 过滤href的值
	 * @param string $value
	 * @param string $tag
	 */
	protected function _filterHrefAttrValue($value = '', $tag = '') {
		return $this->_filterSrcAttrValue ( $value, $tag );
	}
	/**
	 * 
	 * 过滤style的值
	 * @param string $value
	 * @param string $tag
	 */
	protected function _filterStyleAttrValue($value = '', $tag = '') {
		//@TODO, 这里暂时不做词法分析，只用;和:进行切割，首先达到保证安全即可
		$items = explode ( ";", $value );
		$result = array ();
		foreach ( $items as $item ) {
			$item = trim ( $item );
			$mix = explode ( ":", $item, 2 );
			$name = strtolower ( trim ( $mix [0] ) );
			$val = trim ( $mix [1] );
			if (! $name || ! $val || ! array_key_exists ( $name, $this->styleValueBlankList )) {
				continue;
			}
			$pattern = $this->styleValueBlankList [$name];
			if (! $pattern) {
				$result [] = $name . ":" . $val;
			} else {
				$flag = false;
				if (is_array ( $pattern )) {
					if (in_array ( $val, $pattern )) {
						$flag = true;
					}
				} else if (substr ( $pattern, 0, 1 ) === '/') {
					if (preg_match ( $pattern, $val )) {
						$flag = true;
					}
				} else {
					if ($pattern === $val) {
						$flag = true;
					}
				}
				if ($flag) {
					$result [] = $name . ":" . $val;
				}
			}
		}
		return join(";", $result);
	}

	/**
	 * 
	 * 添加标签白名单，必须是小写
	 * @param string or array $tag
	 */
	public function addTagBlank($tag){
		if (! is_array($tag)){
			$tag = array($tag);
		}
		$this->tagBlankList = array_merge($this->tagBlankList, $tag);
	}
	/**
	 * 
	 * 删除标签白名单
	 * @param string or array $tag
	 */
	public function removeTagBlank($tag){
		if (! is_array($tag)){
			$tag = array($tag);
		}
		$result = array();
		foreach ($this->tagBlankList as $tagItem){
			if (! in_array($tagItem, $tag)){
				$result[] = $tagItem;
			}
		}
		$this->tagBlankList = $result;
	}
	/**
	 * 
	 * 添加标签属性白名单
	 * @param string or array $attr
	 * @param string $tag
	 */
	public function addAttrBlank($attr, $tag = ''){
		if (! $tag){
			$tag = '*';
		}
		if (!is_array($attr)){
			$attr = array($attr);
		}
		$attrs = $this->attrBlankList[$tag];
		if (!is_array($attrs)){
			$attrs = array();
		}
		$this->attrBlankList[$tag] = array_merge($attrs, $attr);
	}
	/**
	 * 
	 * 移除属性白名单
	 * @param string or array $attr
	 * @param string $tag
	 */
	public function removeAttrBlank($attr, $tag = ''){
		if (! $tag){
			$tag = '*';
		}
		if (!is_array($attr)){
			$attr = array($attr);
		}
		$attrs = $this->attrBlankList[$tag];
		if (!is_array($attrs)){
			return false;
		}
		$result = array();
		foreach ($attrs as $item){
			if (!in_array($item, $attr)){
				$result [] = $item;
			}
		}
		$this->attrBlankList[$tag] = $result;
	}
	/**
	 * 
	 * 添加CSS白名单
	 * @param string $style
	 * @param string $value
	 */
	public function addStyleValueBlank($style, $value){
		$this->styleValueBlankList[$style] = $value;
	}
	/**
	 * 
	 * 移除CSS白名单
	 * @param string $style
	 */
	public function removeStyleValueBlank($style){
		unset($this->styleValueBlankList[$style]);
	}
	/**
	 * 
	 * 添加标签必须的属性
	 * @param array $attrs
	 * @param string $tag
	 */
	public function addTagAttrRequired($attrs = array(), $tag){
		if (! $tag){
			return false;
		}
		$attr = $this->tagAttrRequired[$tag];
		if (!is_array($attr)){
			$attr = array();
		}
		$this->tagAttrRequired[$tag] = array_merge($attr, $attrs);
	}
	/**
	 * 
	 * 移除标签必须的属性
	 * @param array $attrs
	 * @param string $tag
	 */
	public function removeTagAttrRequired($attrs = array(), $tag){
		if (! array_key_exists($tag, $this->tagAttrRequired)){
			return false;
		}
		$result = array();
		foreach ($this->tagAttrRequired[$tag] as $key=>$value){
			if (!in_array($key, $attrs)){
				$result[$key] = $value;
			}
		}
		$this->tagAttrRequired[$tag] = $result;
	}
}