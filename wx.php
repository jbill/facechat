<?php
define("TOKEN", "1234567890");
define("FACEAK", "api_secret=5egTRuIda08ROlwtZkv0pGcmQl13s6B7&api_key=e777c6c0639417c4bc65cc729bf8039b");

$wechatObj = new wechatCallback();
$wechatObj->responseMsg();

class wechatCallback
{
	public function valid()
  {
      $echoStr = $_GET["echostr"];
      if($this->checkSignature()){
          echo $echoStr;
          exit;
      }
  }
  
  private function checkSignature()
	{
      $signature = $_GET["signature"];
      $timestamp = $_GET["timestamp"];
      $nonce = $_GET["nonce"];	
        		
      $token = TOKEN;
      $tmpArr = array($token, $timestamp, $nonce);
      sort($tmpArr);
      $tmpStr = implode( $tmpArr );
      $t = $tmpStr;
      $tmpStr = sha1( $tmpStr );
      if( $tmpStr == $signature ){
        return true;
      }else{
        return false;
      }
	}

  public function responseMsg()
  {
      $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
      if (!empty($postStr)){                
          $postObj = simplexml_load_string($postStr, "SimpleXMLElement", LIBXML_NOCDATA);
          $msgType = $postObj->MsgType;
          switch ($msgType) {
              case "text":
                  $this->responseTextMsg($postObj);
                  break;
              case "image":
                  $this->responseImageMsg($postObj);
                  break;
              case "location":
                  $this->responseLocationMsg($postObj);
                  break;
              case "link":
                  $this->responseLinkMsg($postObj);
                  break;
              default:
                  break;
          }  
      }else{
          echo "";
          exit;
      }
  }
  
  public function responseTextMsg($po)
  {  
      $fromUsername = $po->FromUserName;
      $toUsername = $po->ToUserName;
      $content = trim($po->Content);
      $time = time();
      $tpl = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[text]]></MsgType>
                  <Content><![CDATA[%s]]></Content>
                  <FuncFlag>0</FuncFlag>
              </xml>";             
      if (!empty( $content )){
          $contentStr = "你说:".$fromUsername;
          $resultStr = sprintf($tpl, $fromUsername, $toUsername, $time, $contentStr);
          echo $resultStr;
      }else{
          echo "Input something...";
      }
  }	
  
  public function responseLocationMsg($po)
  {  
      $fromUsername = $po->FromUserName;
      $toUsername = $po->ToUserName;
      $lat = $po->Location_X;
      $lng = $po->Location_Y;
      $scale = $po->Scale;
      $label = $po->Label;
      $time = time();
      $tpl = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[text]]></MsgType>
                  <Content><![CDATA[%s]]></Content>
                  <FuncFlag>0</FuncFlag>
              </xml>";
              
      $geoJson = file_get_contents('http://api.map.baidu.com/ag/coord/convert?from=2&to=4&x='.$lng.'&y='.$lat);
      $geoObj = json_decode($geoJson);    
      $blat = base64_decode($geoObj->y);
      $blng = base64_decode($geoObj->x);        
        
      $bmUrl = "http://api.map.baidu.com/geocoder/v2/?ak=87cf210cfcc83968caf6eb55952331e7&location=".$blat.','.$blng."&output=json&pois=1";//&coordtype=gcj02ll";  
      $json = file_get_contents($bmUrl);          
      $obj = json_decode($json); 
      $r = $obj->result;
      $loc = $r->location;
      $addr = $r->formatted_address;
      $pois = $r->pois;
      $pc = count($pois);
      
      $str = "百度地址：".$addr."\n";
      $str = $str."转换位置：".$blat.','.$blng."\n";
      $str = $str."百度位置：".$loc->lat.','.$loc->lng."\n";
      $str = $str."微信地址：".$label."\n";
      $str = $str."微信位置：".$lat.','.$lng."\n\n";
      
      $str = $str."附近地点：".$pc."个\n";   
      for ($i=0;$i<$pc;$i++){
        $poi = $pois[$i];
        $paddr = $poi->addr;
        $pdis = $poi->distance;
        $pname = $poi->name;
        $ptype = $poi->poiType;
        $ptel = $poi->tel;        
        $pn = $i + 1;
        $str = $str."No.".$pn."\n";
        $str = $str."名称：".$pname."\n";
        $str = $str."地址：".$paddr."\n";
        $str = $str."距离：".$pdis."米\n";
        $str = $str."类型：".$ptype."\n";
        $str = $str."电话：".$ptel."\n";
        $str = $str." \n";
      }
      
      //$contentStr = $json.'p:'.$lng.','.$lat;
      $resultStr = sprintf($tpl, $fromUsername, $toUsername, $time, $str);
      echo $resultStr;
  }
  
  private function GrabImage($url, $filename=""){
      if($url == "")
          return;
      $ext = ".jpg";
      if($filename == "")
          $filename = time()."$ext"; 
      ob_start(); 
      readfile($url); 
      $img = ob_get_contents(); 
      ob_end_clean(); 
      $size = strlen($img); 
      $fp2 = fopen($filename , "a"); 
      fwrite($fp2, $img); 
      fclose($fp2);
  } 
  
  public function responseImageMsg($po)
  {  
      $fromUsername = $po->FromUserName;
      $toUsername = $po->ToUserName;
      $picUrl = trim($po->PicUrl);
      $time = time();
      
      $tpl = "<xml>
                 <ToUserName><![CDATA[%s]]></ToUserName>
                 <FromUserName><![CDATA[%s]]></FromUserName>
                 <CreateTime>%s</CreateTime>
                 <MsgType><![CDATA[news]]></MsgType>
                 <ArticleCount>1</ArticleCount>
                 <Articles>
                   <item>
                     <Title><![CDATA[title]]></Title>
                     <Description><![CDATA[%s]]></Description>
                     <PicUrl><![CDATA[%s]]></PicUrl>
                     <Url><![CDATA[%s]]></Url>
                   </item>
                 </Articles>
                 <FuncFlag>1</FuncFlag>
              </xml>";             
      if (!empty( $picUrl )){
          $contentStr = "pic:".$picUrl;
          $faceUrl = "http://apicn.faceplusplus.com/v2/detection/detect?url=".$picUrl."&".FACEAK; 
          //$faceUrl = "http://www.baidu.com"; 
          $json = file_get_contents($faceUrl);
          $obj = json_decode($json); 
          $faces = $obj->face;
          $fc = count($faces) ;
          $str = "识别结果：\n";
          $pUrl = "http://apicn.faceplusplus.com/v2/person/add_face?api_secret=5egTRuIda08ROlwtZkv0pGcmQl13s6B7&".FACEAK."&person_name=范冠军&face_id=";   
          for ($i=0;$i<$fc;$i++){
            $face = $faces[$i];
            $attr = $face->attribute;
            $fid = $face->face_id;
            $pUrl = $pUrl.$fid;
            $age = $attr->age;
            $gender = $attr->gender;
            $race = $attr->race;
            $smiling = $attr->smiling;
            $pn = $i + 1;
            $str = $str."第".$pn."张脸 \n";
            $age1 = $age->value - $age->range;
            $age2 = $age->value + $age->range;
            $str = $str."年龄：".$age1." ~ ".$age2."岁 \n";
            $str = $str."性别：".$gender->value." \n";
            $str = $str."人种：".$race->value." \n";
            $str = $str."笑容：".$smiling->value." \n";
            $str = $str.$pUrl."\n";
            $str = $str." \n";
            
          } 
          //$this->GrabImage($picUrl);   
          $resultStr = sprintf($tpl, $fromUsername, $toUsername,$time,$str,$picUrl,$pUrl);
          $this->GrabImage($picUrl);
          echo $resultStr;
      }else{
          echo "Input something...";
      }
  }		
}

?>