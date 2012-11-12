<?php

/**
 * DancingBrave Html Parser like a jquery Class.
 *
 * Parse And Get Html data handler like a jquery.
 * @author    Hideyuki Ishiwata
 * @version   0.1
 * @copyright Hideyuki Ishiwata
 */
class DBRHtmlParse {
  // Attributes
  /**
   * split character.
   *
   * @var   string
   * @access const
   */
  const SPLIT_CHAR = ' ';

  /**
   * selector attr list.
   *
   * @var  array 
   * @access private
   */
  private static $SELECTOR_ATTR_SHORTCUT_LIST = array(
	'#'=>'id',
	'.'=>'class',
	':selected'=>'selected',
	':checked'=>'checked'
  );

  /**
   * tag type list.
   *
   * @var  array 
   * @access private
   */
  private static $TAG_TYPE_LIST = array(
	'open'=>1,
	'close'=>2,
	'comment'=>10
  );

  /**
   * self tag close list.
   *
   * @var  array 
   * @access private
   */
  private static $SELF_CLOSE_TAG_TYPE_LIST = array(
	'img',
	'br',
	'input',
	'nbr',
	'meta',
	'link',
  );

  /**
   * html data comment out string list.
   *
   * @var  array 
   * @access private
   */
  private static $HTML_COMMENT_OUT_LIST = array(
	  'open'=>'<!--',
	  'close'=>'-->'
	  );

  /**
   * attribute open close list.
   *
   * @var  array 
   * @access private
   */
  private static $SELECTOR_ATTR_OPEN_CLOSE_LIST = array(
	array(
	  'open'=>'[',
	  'close'=>']'
	)
  );

  /**
   * attribute key-value join character.
   *
   * @var   string
   * @access const
   */
  const ATTR_KEY_VAL_JOIN_CHAR = '=';

  /**
   * attribute matching list.
   *
   * @var  array 
   * @access private
   */
  private static $ATTRIBUTE_MATCHING_LIST = array(
	'^'=>'head',//first child
	'|'=>'headHyphen',//last child
	'*'=>'contain',//first selected element
	'~'=>'headSpace',//last selected element
	'$'=>'last',//all child.remove selected parent.
	'!'=>'not'//all data.
	  );

  /**
   * extra choice list.
   *
   * @var  array 
   * @access private
   */
  private static $EXTRA_SELECTOR = array(
	':first-child'=>'chf',//first child
	':last-child'=>'chl',//last child
	':first'=>'ef',//first selected element
	':last'=>'el',//last selected element
	'>'=>'cha',//all child.remove selected parent.
	'*'=>'all'//all data.
	//':text'=>'text',
	//':first-text'=>'^text',
	//':last-text'=>'$text'
	  );

  /**
   * back up start original html content list.
   *
   * @var  array 
   * @access private
   */
  private static $ORIGINAL_HTML_LIST = array();

  /**
   * back up start original html content.
   *
   * @var   int
   * @access private
   */
  private $originalHtmlNum = -1;

  /**
   * current data nesting html content string.
   *
   * @var   string
   * @access private
   */
  private $currentHtml = '';

  /**
   * attribute data push array.
   *
   * @var    array 
   * @access private
   */
  private $attrList = array();

  /**
   * selector string.
   *
   * @var   string
   * @access private
   */
  private $selectorString = '';

  /**
   * selector data push array.
   *
   * @var    array 
   * @access private
   */
  private $selectList = array();

  /**
   * selector data parent relation value.This value is $selectList's index number. -1 is no parent in selectList
   *
   * @var    int 
   * @access private
   */
  private $selectParentIdx = -1;

  /**
   * object tag name.
   *
   * @var   string
   * @access private
   */
  private $tagName = '';

  /**
   * constructor.
   */
  public function __construct($originalHtml = ''){
	$this->init($originalHtml);
  }

  /**
   * initialize object data.
   * 
   * @param string originalHtml string data.
   * @access public
   */
  public function init($originalHtml) {
	$this->clear();

	//fix html data
	$originalHtml = preg_replace('/< */' , '<' , $originalHtml );
	$originalHtml = preg_replace('/<\/ */' , '</' , $originalHtml );
	$originalHtml = preg_replace('/ *>/' , '>' , $originalHtml );

	$this->originalHtmlNum = self::pushOriginalHtmlList( $originalHtml );
  }

  /**
   * tag is open tag or not checker.
   * 
   * @param string tag string data.
   * @return bool if tag is open tag, return tag string data.
   * @access public
   */
  public static function CHECK_TAG_TYPE($tag) {
	$char = substr($tag,0,1);
	if( $char == '/'){
	  return self::$TAG_TYPE_LIST['close'];
	}
	else if( $char == '!'){
	  return self::$TAG_TYPE_LIST['comment'];
	}
	else{
	  return self::$TAG_TYPE_LIST['open'];
	}
  }

  /**
   * check tag and condition data is matching or not.
   * 
   * @param string tag string data.
   * @param array condition data.see parseSelector return format.
   * @param bool if true, tag is lower case check.false, raw check
   * @return bool if tag condition is matching, return true. if not, return false.
   * @access public
   */
  public static function IS_MATCH($tag , $conditionList , $isLower = true ) {
	$checkData = self::GET_TAG_DATA( $tag,$isLower );

	//start check data
	try{
	  //tag check
	  if( !empty($conditionList['tag']) && $conditionList['tag'] != $checkData['tag'] ) return false;

	  //attribute check
	  if( count($conditionList['attr']) == 0 ) return true;//no check

	  foreach( $conditionList['attr'] as $attrName=>$attrVal){
		if(empty($attrVal)) continue;
		//attrName check
		$lastChar = substr($attrName,strlen($attrName) - 1 );
		if( isset( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] ) ){
		  $attrName = substr( $attrName,0,strlen($attrName) - 1);

		  if( !isset($checkData['attr'][$attrName]) ) return false;
		  $checkAttrVal = $checkData['attr'][$attrName] ;

		  //matching check
		  if( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] == 'head'){
			if( strpos( $checkAttrVal, $attrVal ) !== 0 ) return false;
		  }
		  else if( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] == 'headHyphen'){
			if( $checkAttrVal != $attrVal ){
			  $cutPos = strpos( $checkAttrVal, $attrVal . '-');
			  if($cutPos === false) return false;
			  if($cutPos === 0 ) continue;
			  $cutStr = substr( $checkAttrVal , $cutPos - 1);

			  if( substr($cutStr,0,1) !== ' ' ) return false;

			}

		  }
		  else if( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] == 'contain'){
			if( strpos( $checkAttrVal, $attrVal ) === false ) return false;
		  }
		  else if( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] == 'headSpace'){
			$cutPos = strpos( $checkAttrVal, ' ' . $attrVal );
			if( $cutPos === false ) return false;
			$cutStr = substr( $checkAttrVal,$cutPos );
			if( $cutStr == ' ' . $attrVal ) continue;
			if( substr( $cutStr , strlen(' ' . $attrVal) + 1 , 1 ) !== ' ' ) return false;

		  }
		  else if( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] == 'last'){
			if( !preg_match("/" . preg_quote($attrVal , '/') . "$/" , $checkAttrVal ) ) return false;
		  }
		  else if( self::$ATTRIBUTE_MATCHING_LIST[$lastChar] == 'not'){
			if( strpos( $checkAttrVal, $attrVal ) !== false ) return false;
		  }
		  else{
		  }
		}
		else if( !isset($checkData['attr'][$attrName]) ) return false;
		else{
		  $tmpList = explode( self::SPLIT_CHAR ,$checkData['attr'][$attrName] );
		  $attrValList = explode( self::SPLIT_CHAR , $attrVal );

		  foreach( $attrValList as $atVal ){
			if(empty($atVal)) continue;
			if( !in_array( $atVal , $tmpList) ) return false;
		  }
		}
	  }
	  return true;
	}catch(Exception $e){
	  //no match
	}
	return false;
  }

  /**
   * parse tag string.
   * 
   * @param string tag string data.
   * @param string attribute key string is lower case or not.
   * @return array array("tag"=>tagstring,"attr"=>array(attrName=>attrValue)).
   * @access public
   */
  public static function GET_TAG_DATA($tag , $isLower = true ) {
	$retval = array(
		'tag'=>'',
		'attr'=>array()
		);

	//$tag = ltrim($tag , "<" );
	//$tag = rtrim($tag , ">" );
	$tag = rtrim($tag , "/" );
	$tmpData = explode( self::SPLIT_CHAR , $tag , 2 );
	$retval['tag'] = $tmpData[0];
	if($isLower) $retval['tag'] = strtolower($retval['tag']);
	//if(isset($tmpData[1])) $tag = trim($tmpData[1]);
	if(isset($tmpData[1])) $tag = $tmpData[1];
	else $tag = '';

	//parse attr data list
	$nowNesting = false;
	$nestChar = '';
	$tmpTag = '';
	$sPos = -1;//one attr start pos point
	$lPos = -1;//checked last space position
	$isEscape = false;//checked last space position
	$tag = ' ' . $tag;//for process
	$attrStrList = array();

	for($i = 0; $i < strlen($tag); $i++){
	  $tmpChar = substr($tag , $i , 1);
	  //space char check
	  if( $tmpChar === ' ' ){
		$lPos = $i;
		if( $nowNesting ){}//noexec
		else $sPos = $i;//attribute start!
	  }
	  //nest check
	  else if( $tmpChar === '"' || $tmpChar === "'" ){
		if($nowNesting){
		  if(empty($nestChar)){
			$nestChar = $tmpChar;	
		  }
		  else if($nestChar === $tmpChar && !$isEscape ){
			$nowNesting = false;// nest end	
			$nestChar = "";
			//push attr string.
			$attrStrList[] = substr( $tag,$sPos , $i - $sPos + 1 );
			$sPos = -1;
		  }
		  else{}
		}
		else{
		  $nestChar = $tmpChar;
		  $nowNesting = true;// nest start
		}
	  }
	  else if( $tmpChar === '='){
		if( !$nowNesting ){
		  //start nesting
		  $nowNesting = true;
		}
		else if(empty($nestChar) ){
		  //no nest char,get before attr list
		  $attrStrList[] = substr( $tag,$sPos , $lPos - $sPos );
		  $sPos = $lPos;
		}
		else{}
	  }
	  else{}

	  //escape char check
	  if( $tmpChar == '\\' ){
		$isEscape = !$isEscape;//for "\\\\"
	  }
	  else{
		$isEscape = false;
	  }

	}
	if($sPos !== -1){
	  $attrStrList[] = substr( $tag,$sPos );
	}

	foreach( $attrStrList as $tmpStr ){
	  $tmpStr = trim($tmpStr);
	  if(empty($tmpStr)) continue;
	  $tmpList = explode( "=" , $tmpStr ,2 );
	  if($isLower) $tmpList[0] = strtolower($tmpList[0]);
	  if( count($tmpList) == 1 ){
		//no value attribute
		$retval['attr'][$tmpList[0]] = true;
	  }
	  else{
		$tmpList[1] = trim( $tmpList[1] , "'\"");
		$tmpList[1] = str_replace("\\","",$tmpList[1]);
		$retval['attr'][$tmpList[0]] = $tmpList[1];
	  }

	}
	return $retval;

  }

  /**
   * push original html data.
   * 
   * @param string originalHtml string data.
   * @access public
   */
  private static function pushOriginalHtmlList($originalHtml) {
	array_push( self::$ORIGINAL_HTML_LIST , $originalHtml);
	return count( self::$ORIGINAL_HTML_LIST ) - 1;
  }

  /**
   * clear member variable.
   * 
   * @access public
   */
  public function clear() {
	$this->originalHtmlNum = -1;
	$this->currentHtml  = '';
	$this->attrList = array();
	$this->selectorString = '';
	$this->selectList = array();
	$this->tagName = '';
	$this->selectParentIdx = -1;
  }

  /**
   * copy current dom data.
   * 
   * @param DBRHtmlParse DBRHtmlParse object.
   * @return DBRHtmlParse DBRHtmlParse object.
   * @access public
   */
  public function copy($DBRHtmlParse) {
	$this->originalHtmlNum = $DBRHtmlParse->getOriginalHtmlNum();
	$this->currentHtml  = $DBRHtmlParse->getCurrentHtml();
	$this->attrList = $DBRHtmlParse->getAttrList();
	$this->selectorString = $DBRHtmlParse->getSelectorString();
	$this->selectList = $DBRHtmlParse->getSelectList();
	$this->tagName = $DBRHtmlParse->getTagName();
	$this->selectParentIdx = $DBRHtmlParse->getSelectParentIdx();
  }


  /**
   * select dom data.
   * 
   * @param string selector string.
   * @return DBRHtmlParse DBRHtmlParse object.
   * @access public
   */
  public function select($selector) {
	//check selector.
	$selector = preg_replace("/\s+/",' ',$selector);
	$parseHtmlData = $this->getOriginalHtml();
	$this->selectorString = '';
	$this->setCurrentHtml($parseHtmlData);

	$retval= $this->executeParse($parseHtmlData , $selector );

	//set this.
	$this->currentHtml = '';
	$this->attrList = array();
	$this->tagName = '';
	$this->selectList = $retval;
	$this->setSelectorString($selector);
	$this->setSelectParentIdx(-1);
	return $this;

  }

  /**
   * select dom data.
   * 
   * @param string selector string.this is added to selectorString and search select list data.
   * @param boolean if true,return new DBRHtmlParse Object.false,rewrite this object data and return this calling object.
   * @return DBRHtmlParse DBRHtmlParse object.
   * @access public
   */
  public function find($selector , $isNew = true) {
	$selector = preg_replace("/\s+/",' ',$selector);

	$parseHtmlData = '';
	foreach( $this->selectList as $selObj ){
	  if($selObj->getSelectParentIdx() > 0) continue;
	  $parseHtmlData .= $selObj->getWrapHtml();
	}

	$retval= $this->executeParse($parseHtmlData , $selector );

	$fullSelectStr = $this->selectorString;
	if( !empty($fullSelectStr) ) $fullSelectStr .= self::SPLIT_CHAR  ;
	$fullSelectStr .= $selector;

	//set return data.
	if($isNew){
	  $retObj = new DBRHtmlParse();
	  $retObj->copy($this);
	  $retObj->currentHtml = '';
	  $retObj->attrList = array();
	  $retObj->tagName = '';
	  $retObj->selectList = $retval;
	  $retObj->setSelectorString($fullSelectStr);
	  $retObj->setSelectParentIdx(-1);
	  return $retObj;
	}
	else{
	  $this->currentHtml = '';
	  $this->attrList = array();
	  $this->tagName = '';
	  $this->selectList = $retval;
	  $this->setSelectorString($fullSelectStr);
	  $this->setSelectParentIdx(-1);
	  return $this;
	}
  }

  /**
   * execute parse function.
   * 
   * @param string parse html string.
   * @param string selector string.
   * @param bool attribute key string is lower case or not.
   * @return array DBRHtmlParse object list.
   * @access public
   */
  public function executeParse($parseHtmlData,$selector,$isLower = true) {
	$fullSelectStr = $this->selectorString;
	if( !empty($fullSelectStr) ) $fullSelectStr .= self::SPLIT_CHAR  ;
	$fullSelectStr .= $selector;

	//parse selector string
	$selectList = self::PARSE_SELECTOR($selector,$isLower);

	//parse html data and choose select data.
	$retval= self::PARSE_HTML($parseHtmlData , $selectList,-1,$isLower );

	foreach($retval as $obj ){
	  $obj->setOriginalHtmlNum( $this->getOriginalHtmlNum() );
	  $obj->setSelectorString( $fullSelectStr );
	}

	return $retval;

  }




  /**
   * parse selector.
   * 
   * @param string selector string.
   * @param boolean attribute key string is lower case or not.
   * @return array each element:{tag:tagName,attr:{attrName:attrVal,...},text:string,extra:['chf',...]}.extra data = private static extraSelectors.
   * @access private
   */
  public static function PARSE_SELECTOR($selector,$isLower = true) {
	$selector = $selector;
	$retval = array();
	//first,split char
	$elemList = explode(self::SPLIT_CHAR , $selector );

	foreach( $elemList as $eachElem ){
	  if(empty($eachElem )) continue;
	  $pushData = array(
		  'tag'=>'',
		  'attr'=>array(),
		  'extra'=>array()
	  );

	  //first shortcut character check
	  foreach( self::$SELECTOR_ATTR_SHORTCUT_LIST as $S=>$A ){
		$tmpList = explode( $S , $eachElem );
		if( count( $tmpList ) > 1 ){
		  $tmpStr = $tmpList[1];
		  //get selector string
		  $pushData['attr'][$A] = preg_replace("/[^0-9a-zA-Z_-].*$/","", $tmpStr);
		  if(empty($pushData['attr'][$A])) $pushData['attr'][$A] = true;
		  $tmpList[1] = preg_replace( "/^[0-9a-zA-Z_-]*/","", $tmpStr);
		}
		$eachElem = implode("",$tmpList);
	  }

	  //second attribute check
	  foreach( self::$SELECTOR_ATTR_OPEN_CLOSE_LIST as $L ){
		$openChar = $L['open'];
		$closeChar = $L['close'];

		$openPos = strpos($eachElem,$openChar);
		while( $openPos !== false ){
		  $closePos = strpos($eachElem,$closeChar);
		  $attrStr = '';
		  if($closePos === false ){
			//cut from open pos to string end,set attribute
			$openPos = false;

			//cut from 0 to open pos,set element.
		  }
		  else{
			//cut from open pos to string end,set attribute
			$attrStr = substr($eachElem , $openPos + 1, $closePos - $openPos - 1);
			//cut from 0 to open pos,set element.
			$tmpStr1 = substr($eachElem , 0,$openPos );
			//cut from close pos to string end,set element.
			$tmpStr2 = substr($eachElem , $closePos + 1 );
			$eachElem =  $tmpStr1 . $tmpStr2;
			$openPos = strpos($eachElem,$openChar);
			//parse attrStr
			$tmpList2 = explode( self::ATTR_KEY_VAL_JOIN_CHAR , $attrStr );
			if(count($tmpList2) == 2)$pushData['attr'][$tmpList2[0]] = trim($tmpList2[1]);
		  }

		}

	  }

	  //last,extra data check
	  foreach( self::$EXTRA_SELECTOR as $S=>$A ){
		$exPos = strpos($eachElem,$S);
		if( $exPos === false ) continue;
		$eachElem = str_replace( $S , "" , $eachElem );
		$pushData['extra'][] = $A;
	  }

	  $pushData['tag'] = trim($eachElem);


	  //lower case set
	  if($isLower){
		$pushData['tag'] = strtolower($pushData['tag']);
		foreach( $pushData['attr'] as $idx=>$val){
		  $pushData['attr'][strtolower($idx)] =$val;
		}
	  }

	  $retval[] = $pushData;
	}


	return $retval;


  }

  /**
   * parse html data and choice data.
   * 
   * @param string html data string.
   * @param array select condition list.
   * @param int parent obj selector List index.
   * @param bool if true, tag is lower case check.false, raw check
   * @return array choice html data.each element is DBRHtmlParse
   * @access private
   */
  public static function PARSE_HTML($htmlData , $selectList,$parentIdx = -1 ,$isLower = true) {
	$retval = array();
	$openTagList = array();//key:tag,
	$level = 0;
	$mIdx = -1;//match
	$ePos = 0;
	$isScripting = false;
	$setUpCurHtml = '';
	$setUpTagAttrList = array();
	$matchingIdxList = array();
	$maxSelectCount = count($selectList);

	//start html parse
	//get one tag string
	while(!empty($htmlData)){
	  $openTagPos = strpos($htmlData , '<'  ) ;
	  if( $openTagPos === false){
		$htmlData = "";
		break;
	  }
	  else if( $openTagPos > 0){
		$htmlData = substr($htmlData , $openTagPos);
	  }
	  else{}//first char is "<"

	  $closeTagPos = strpos($htmlData , '>' ) ;
	  if( $closeTagPos === false){
		if(!empty($htmlData)) $htmlData .= ">";//"<" exists.
		$closeTagPos = strlen($htmlData) - 1;
	  }

	  $tag = substr($htmlData , 1 , $closeTagPos - 1 );
	  $htmlData = substr($htmlData , $closeTagPos + 1 );

	  //tag type check
	  if( self::CHECK_TAG_TYPE($tag) == self::$TAG_TYPE_LIST['open'] ){
		//condition check
		if( $isScripting  ){
		  continue;
		}

		$tmpData = explode( self::SPLIT_CHAR , $tag );

		if($isLower) $tmpData[0] = strtolower($tmpData[0]);
		$openTagList[] = $tmpData[0];
		if( $tmpData[0] == 'script') $isScripting = true;

		$isContinue = false;

		for( $i = $level; $i < $maxSelectCount;$i++ ){
		  if( self::IS_MATCH( $tag , $selectList[$i] ) ){
			$matchingIdxList[] = count($openTagList) - 1;
			//check extra
			foreach( $selectList[$i]['extra'] as $val ){
			  if( $val == 'cha' ){//choice child
				$isContinue = true;
			  }
			  else if($val == 'all' ){
				//no exec
			  }
			  else{}
			}
			if($isContinue){
			  //count up $level
			  $i++;
			  break;
			}
		  }
		  else break;
		}

		$level = $i;
		if($isContinue)continue;
		if( $maxSelectCount <= $level ){
		  //save tag attribute
		  $tmpList = self::GET_TAG_DATA($tag);
		  $setUpTagAttrList = $tmpList['attr'];

		  //get matching close tag position and get current html data
		  $openTagLastIdx = count($openTagList) - 1;
		  $matchTag = $openTagList[$openTagLastIdx];
		  if( in_array($matchTag,self::$SELF_CLOSE_TAG_TYPE_LIST) ){
			array_pop($openTagList);
			$openTagLastIdx--;
			$setUpCurHtml = '';
		  }
		  else{
			$endPos = 0;
			$nextPos = 0;
			$maxLen = strlen($htmlData);
			$isFirst = true;
			for($i = $openTagLastIdx; $i > -1;$i-- ){
			  $checkTag = $openTagList[$i];
			  while($endPos < $maxLen){
				$searchNextPos = strpos( $htmlData , '<' . $checkTag .'>',$nextPos);
				if($searchNextPos === false) $nextPos = strpos( $htmlData , '<' . $checkTag .' ',$nextPos);
				else $nextPos = $searchNextPos;

				$endPos = strpos( $htmlData , '</' . $checkTag . '>',$endPos );
				if($endPos === false){
				  //no close tag
				  break;
				}
				else if($nextPos === false || $endPos < $nextPos){
				  break;
				}
				else{
				  //next loop
				  $endPos += strlen('</' . $checkTag . '>');
				  $nextPos = strpos($htmlData,'>' , $nextPos) + 1;
				}
			  }
			  if( $endPos !== false && $endPos < $maxLen ){
				if($isFirst){
				  array_pop( $openTagList );
				  $openTagLastIdx--;
				}
				else $isFirst = false;
				break;
			  }
			  array_pop($openTagList);
			  $openTagLastIdx--;
			}

			$setUpCurHtml = substr($htmlData,0,$endPos );
			if($isFirst) $htmlData = substr($htmlData,$endPos + strlen('</' . $matchTag . '>') );
			else $htmlData = substr($htmlData,$endPos );

		  }

		  //save retval select list
		  $selectObj = new DBRHtmlParse();
		  $selectObj->setAttrList( $setUpTagAttrList );
		  $selectObj->setCurrentHtml( $setUpCurHtml );
		  $selectObj->setTagName( $matchTag );
		  $selectObj->setSelectParentIdx( $parentIdx );
		  $retval[] = $selectObj;
		  $setUpCurHtml = '';//clear

		  //matching level fix
		  $matchingLastIdx = count($matchingIdxList)-1;
		  for($j = $matchingLastIdx; $j > -1; $j-- ){
			if( $openTagLastIdx < $matchingIdxList[$j] ){
			  $level--;
			  array_pop($matchingIdxList);
			}
			else break;
		  }
		}
	  }
	  else if( self::CHECK_TAG_TYPE($tag) == self::$TAG_TYPE_LIST['close'] ){

		//get tag name
		$tag = substr($tag , 1 );
		//$tmpList = explode(' ' , $tag );
		//if($isLower) $tmpList[0] = strtolower($tmpList[0]);
		//$tag = $tmpList[0];
		if($isLower) $tag = strtolower($tag);

		//condition check
		if( $isScripting && $tag !== 'script' ){
		  continue;
		}
		if( $tag == 'script') $isScripting = false;

		$openTagLastIdx = count($openTagList) - 1;

		for($i = $openTagLastIdx; $i > -1;$i-- ){
		  if( $openTagList[$i] == $tag ){
			break;
		  }
		}

		//close tag
		if($i == -1){
		  //get true close tag
		  for($i = $openTagLastIdx; $i > -1;$i-- ){
			$endPos = strpos( $htmlData  ,'</' . $openTagList[$i] . '>' );
			if($endPos !== false){
			  $i++;
			  break;
			}
		  }
		  if($i == -1) $i = 0;
		}
		for($j = $openTagLastIdx; $j >= $i;$j-- ){
		  array_pop( $openTagList );
		}

		$openTagLastIdx = $j;

		$mIdx = -1;
		$matchingLastIdx = count($matchingIdxList)-1;
		for($j = $matchingLastIdx; $j > -1; $j-- ){
		  if( $openTagLastIdx < $matchingIdxList[$j] ){
			$level--;
			array_pop($matchingIdxList);
		  }
		  else break;
		}


	  }
	  else if( self::CHECK_TAG_TYPE($tag) == self::$TAG_TYPE_LIST['comment'] ){
	  }
	  else{}

	}

	if(count($retval) == 0) return $retval;

	//if tag is open , push open tag list and check selector
	//check child data
	$addList = array();
	$retvalOrder = array();

	//if extra last condition = '>',no loop
	$isSkip = false;
	$selectLastIdx = count($selectList) - 1;
	for($i = $selectLastIdx;$i > -1; $i--){
	  $extras = $selectList[$i]['extra'];
	  if( in_array('cha',$extras) ){
		$isSkip = true;
		break;
	  }
	  if( in_array('all',$extras) ){
		continue;
	  }
	  break;
	}
	if($isSkip) return $retval;

	for($i = 0;$i < $selectLastIdx; $i++){//last is not
	  if(  in_array( 'chf' , $selectList[$i]['extra'])
		|| in_array( 'ef' , $selectList[$i]['extra'])){
		$retval = array( $retval[0] );
		break;
	  }
	  if(  in_array( 'chl' , $selectList[$i]['extra'])
		|| in_array( 'el' , $selectList[$i]['extra'])){
		$retval = array( $retval[count($retval) - 1] );
		break;
	  }
	}

	//choice extra option data
	$extraData = $selectList[$selectLastIdx]['extra'];
	if( in_array('ef' , $extraData ) ) return array($retval[0]);
	else{}

	$setUpIdx = $parentIdx;
	foreach( $retval as $parseObj ){
	  $retvalOrder[] = $parseObj;
	  $setUpIdx++;

	  $tmpList = self::PARSE_HTML($parseObj->getCurrentHtml() , array($selectList[$selectLastIdx]),$setUpIdx );
	  foreach( $tmpList as $addElem){
		$retvalOrder[] = $addElem;
		$setUpIdx++;
	  }
	}

	//choice child option
	if( $parentIdx == -1 ){
	  if( in_array('chf' , $extraData ) ){
		$retval = array();
		$pushKeyList = array();
		foreach( $retvalOrder as $keyIdx=>$obj){
		  $idx = $obj->getSelectParentIdx();
		  if( !isset($retval[$idx]) ){
			if( !in_array( $idx , $pushKeyList ) ) $obj->setSelectParentIdx(-1);
			$pushKeyList[] = $keyIdx;
			$retval[$idx] = $obj;
		  }
		}
		return array_values($retval);
	  }
	  else if( in_array('chl' , $extraData ) ){
		$retval = array();
		foreach( $retvalOrder as $keyIdx=>$obj){
		  $idx = $obj->getSelectParentIdx();
		  $pushKeyList[$idx] = $keyIdx;
		  $retval[$idx] = $obj;
		}

		//check no parent data is set or not
		foreach( $retval as $obj){
		  if( !in_array( $obj->getSelectParentIdx(),$pushKeyList) ){
			$obj->setSelectParentIdx(-1);
		  }
		}

		return array_values($retval);
	  }
	  else if( in_array('el' , $extraData ) ){
		return array($retvalOrder[count($retvalOrder) - 1]);
	  }
	  else{}
	}

	return $retvalOrder;
  }

  /**
   * originalHtmlNum getter function.
   * 
   * @return string originalHtml string.
   * @access public
   */
  public function getOriginalHtmlNum() {
	return $this->originalHtmlNum;
  }

  /**
   * originalHtmlNum setter function.
   * 
   * @param string originalHtml Num.
   * @access public
   */
  public function setOriginalHtmlNum($num) {
	$this->originalHtmlNum = $num;
  }

  /**
   * originalHtml getter function.
   * 
   * @return string originalHtml string.
   * @access public
   */
  public function getOriginalHtml() {
	return self::$ORIGINAL_HTML_LIST[$this->originalHtmlNum];
  }

  /**
   * currentHtml getter function.
   * 
   * @return string currentHtml string.
   * @access public
   */
  public function getCurrentHtml() {
	return $this->currentHtml;
  }

  /**
   * currentHtml setter function.
   * 
   * @param string currentHtml string.
   * @access public
   */
  public function setCurrentHtml($currentHtml) {
	$this->currentHtml = $currentHtml;
  }

  /**
   * attribute data getter function.
   * 
   * @return array attrList.
   * @access public
   */
  public function getAttrList() {
	return $this->attrList;
  }

  /**
   * attribute data setter function.
   * 
   * @param array attrList.
   * @access public
   */
  public function setAttrList($attrList) {
	$this->attrList = $attrList;
  }

  /**
   * selector data getter function.
   * 
   * @return string selectorString.
   * @access public
   */
  public function getSelectorString() {
	return $this->selectorString;
  }

  /**
   * selector data setter function.
   * 
   * @param string selectorString.
   * @access public
   */
  public function setSelectorString($selectorString) {
	$this->selectorString = $selectorString;
  }

  /**
   * select data getter function.
   * 
   * @return array selectList.
   * @access public
   */
  public function getSelectList() {
	return $this->selectList;
  }

  /**
   * get select data length function.
   * 
   * @return int $this->selectList element count.
   * @access public
   */
  public function length() {
	return count($this->selectList);
  }

  /**
   * get select data by index function.
   * 
   * @param int index.
   * @return mixed all or $this->selectList[$param index] element.
   * @access public
   */
  public function get($idx = -1) {
	if($idx == -1) return $this->getSelectList();
	if(isset($this->selectList[$idx])) return $this->selectList[$idx];
	return null;
  }

  /**
   * get select data attribute data by name function.
   * 
   * @param string attribute name.
   * @return mixed attribute data exists, return data.if not,return null.
   * @access public
   */
  public function attr($name) {
	if( count($this->selectList) > 0 && isset($this->selectList[0]->attrList[$name])) return $this->selectList[0]->attrList[$name];
	if( isset($this->attrList[$name]) ) return $this->attrList[$name];
	return '';
  }

  /**
   * get select data attribute data "value" function.
   * 
   * @return mixed value attribute data exists, return data.if not,return null.
   * @access public
   */
  public function val() {
	return $this->attr("value");
  }

  /**
   * getCurrentHtml alias function.
   * 
   * @return string currentHtml String.
   * @access public
   */
  public function html() {
	if( count($this->selectList) > 0 && isset($this->selectList[0])) return $this->selectList[0]->getCurrentHtml();
	return $this->getCurrentHtml();
  }

  /**
   * getCurrentHtml escape alias function.
   * 
   * @return string currentHtml String.
   * @access public
   */
  public function text() {
	if( count($this->selectList) > 0 && isset($this->selectList[0])) return htmlspecialchars($this->selectList[0]->getCurrentHtml());
	 return htmlspecialchars($this->getCurrentHtml());
  }

  /**
   * getCurrentHtml strip tag function.
   * 
   * @param boolean if true, return only string data that is not nested in tag. false , return tag strip data.
   * @return string currentHtml strip tag String.
   * @access public
   */
  public function strip( $isOnlyString = true ) {
	if( count($this->selectList) > 0 && isset($this->selectList[0])) $retval = $this->selectList[0]->getCurrentHtml();
	 $retval = $this->getCurrentHtml();
	 if($isOnlyString) $retval = preg_replace("?<[^>]*[^/]>[^<]*< *[/][^>]*>?","",$retval);
	 return strip_tags($retval);
  }

  /**
   * getCurrentObjectTagHtml function.
   * 
   * @return string html data currentHtml wrapped with current tag data.
   * @access public
   */
  public function getWrapHtml() {
	$tag = $this->getTagName();
	$attrList = $this->getAttrList();
	$wrapStr = '<' . $tag ;
	foreach( $attrList as $atName=>$atVal ){
	  if(is_bool($atVal) ) $wrapStr .= ' ' . $atName ;
	  else $wrapStr .= ' ' . $atName . '="' . $atVal . '"';
	}
	$wrapStr .= '>';
	if( in_array($tag,self::$SELF_CLOSE_TAG_TYPE_LIST) ) return $wrapStr;

	return $wrapStr . $this->getCurrentHtml() . '</' . $tag . '>';
  }

  /**
   * tagName getter function.
   * 
   * @return string $this->tagName 
   * @access public
   */
  public function getTagName() {
	return $this->tagName;
  }

  /**
   * tagName setter function.
   * 
   * @param string setting $this->tagName value.
   * @access public
   */
  public function setTagName($tagName){
	$this->tagName = $tagName;
  }

  /**
   * selectParentIdx setter function.
   * 
   * @param int setting $this->selectParentIdx value.
   * @access public
   */
  public function setSelectParentIdx($selectParentIdx){
	$this->selectParentIdx = $selectParentIdx;
  }

  /**
   * selectParentIdx getter function.
   * 
   * @return int $this->selectParentIdx value.
   * @access public
   */
  public function getSelectParentIdx(){
	return $this->selectParentIdx ;
  }


}


?>
