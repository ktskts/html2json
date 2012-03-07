<?php
require_once '../src/json2html.class.php';
/**
 * blogEditor class
 * @author  welefen <welefen@gmail.com>
 * @version 1.0
 * @license MIT 
 */
class blogEditor extends json2html{
	/**
	 * [init description]
	 * @return [null]
	 */
	public function init(){
		//add html5 tag blank list
		$this->addTagBlank(array('article', 'section', 'nav', 'header'));
		//add user define attr for div tag
		$this->addAttrBlank(array('data-vote', 'date-video'), 'div');
	}
	/**
	 * [_filterClassAttrValue]
	 * @param  string $value [attr value]
	 * @param  string $tag   [tag name]
	 * @return [string]
	 */
	public function _filterClassAttrValue($value = '', $tag = ''){
		$clsPattern = "/^[\w\s]+$/ies";
		if(preg_match($clsPattern, $value)){
			return $value;
		}
		return '';
	}
	/**
	 * [_filterTargetAttrValue]
	 * @param  string $value [attr value]
	 * @param  string $tag   [tag name]
	 * @return [string]
	 */
	public function _filterTargetAttrValue($value = '', $tag = ''){
		$targetValues = array("_blank", "_self", "_parent", "_top", /*"framename"*/);
		$value = strtolower($value);
		if(in_array($value, $targetValues)){
			return $value;
		}
		return '';
	}
}