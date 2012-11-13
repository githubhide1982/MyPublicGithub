<?php

include_once("DBRHtmlParse.php");

//parser generation.
$parser = new DBRHtmlParse();

//pintarest top page photo get
$uri = "http://pinterest.com/";
$htmlDataStr = implode("",file($uri));

//set html data
$parser->init($htmlDataStr);
$parser->select("#ColumnContainer");//choice ColumnContainer area html data.

//get photo img src list
$imgList = $parser->find(".pin .PinHolder .PinImage img")->get();

//get photo upload user list
$userList = $parser->find(".pin .convo a.ImgLink")->find("img")->get();

//display browser
$contentHtml = '';

for($i = 0; $i < count($imgList);$i++ ){
  $contentHtml .= '<img src="' . $imgList[$i]->attr('src') . '" />';
  $contentHtml .= '<img src="' . $userList[$i]->attr('src') . '" />';
}

$FP = fopen('sample.html', 'w');

$str =<<<HTML
<html>
<body>
{$contentHtml}
</body>
</html>

HTML;

fwrite($FP,$str);

fclose($FP);

?>
