<?php
/**
* SonyTV 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 14:04:18 [Apr 28, 2017])
*/
// https://aydbe.com/assets/uploads/2014/11/json.txt
// https://gist.github.com/sash13/0e0a928990f9e5308b51
// https://github.com/chregu/php-control-sony-tv/blob/master/lib.php
// https://community.smartthings.com/t/new-sony-bravia-tv-integration-for-2015-2016-alpha/64357/135 -- set active app

//
class sonytv extends module {
/**
* sonytv
*
* Module class constructor
*
* @access private
*/
function sonytv() {
  $this->name="sonytv";
  $this->title="SonyTV";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='sonytvs' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_sonytvs') {
   $this->search_sonytvs($out);
  }
  if ($this->view_mode=='edit_sonytvs') {
   $this->edit_sonytvs($out, $this->id);
  }
  if ($this->view_mode=='delete_sonytvs') {
   $this->delete_sonytvs($this->id);
   $this->redirect("?data_source=sonytvs");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='sonytvs_commands') {
  if ($this->view_mode=='' || $this->view_mode=='search_sonytvs_commands') {
   $this->search_sonytvs_commands($out);
  }
  if ($this->view_mode=='edit_sonytvs_commands') {
   $this->edit_sonytvs_commands($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
    global $id;

 if ($this->ajax) {
     global $op;
     global $command;
     $result='';

     header("HTTP/1.0: 200 OK\n");
     header('Content-Type: text/html; charset=utf-8');

     $key=$_GET['key'];

     if ($command=='test') {
         $m_result=$this->sendCommand($id,'test');
         echo $m_result;
     }

     if ($op=='search') {
         global $text;
         $op='';
         $key='Home,sleep:3,Up,sleep:1,Right,Confirm,sleep:2,text:'.$text.',sleep:2,Return,sleep:2,Confirm';
     }
     if ($op=='macro') {
         global $macro;
         $macroRec=SQLSelectOne("SELECT * FROM sonytvs_macros WHERE ID=".(int)$macro);
         $macroRec['UPDATED']=date('Y-m-d H:i:s');
         SQLUpdate('sonytvs_macros',$macroRec);
         $id=$macroRec['DEVICE_ID'];
         $key=$macroRec['VALUE'];
     }

     if ($op=='request_token') {
         $result=$this->sendCommand($id,'request_token');
         if ($result) {
             echo "OK";
         } else {
             echo "Error";
         }
     }  elseif ($key!='') {
         $keys=explode(',',$key);
         $total = count($keys);
         for ($i = 0; $i < $total; $i++) {
             if (preg_match('/^app:/',trim($keys[$i]))) {
                 $m_result = $this->sendCommand($id, 'app', trim(str_replace('app:', '', $keys[$i])));
             } elseif (preg_match('/^text:/',trim($keys[$i]))) {
                $m_result=$this->sendCommand($id,'text',trim(str_replace('text:','',$keys[$i])));
             } elseif (preg_match('/^sleep:(\d+)/',trim($keys[$i]),$m)) {
                 sleep($m[1]);
             } else {
                 $m_result=$this->sendCommand($id,'key',trim($keys[$i]));
             }
             $result.=$m_result;
             if (!$m_result) break;
             if ($i<($total-1)) {
                 usleep(500);
             }
         }
         echo '<b>'.$key.'</b> - ';
         if ($result) {
             echo "OK";
         } else {
             echo "Error";
         }
     }
     exit;
 }


    if (!$id) {
        $tvs=SQLSelect("SELECT * FROM sonytvs ORDER BY TITLE");
        if ($tvs[0]['ID']) {
            $out['TVS']=$tvs;
            if (count($tvs)==1) {
                $id=$tvs[0]['ID'];
                $out['HIDE_BACK']=1;
            }
        }
    }

    if ($id) {
        $tv=SQLSelectOne("SELECT * FROM sonytvs WHERE ID=".(int)$id);
        foreach($tv as $k=>$v) {
            $out[$k]=$v;
        }
        $out['MACROS']=SQLSelect("SELECT * FROM sonytvs_macros WHERE DEVICE_ID=".$tv['ID']." ORDER BY UPDATED DESC");
    }



}
/**
* sonytvs search
*
* @access public
*/
 function search_sonytvs(&$out) {
  require(DIR_MODULES.$this->name.'/sonytvs_search.inc.php');
 }
/**
* sonytvs edit/add
*
* @access public
*/
 function edit_sonytvs(&$out, $id) {
  require(DIR_MODULES.$this->name.'/sonytvs_edit.inc.php');
 }
/**
* sonytvs delete record
*
* @access public
*/
 function delete_sonytvs($id) {
  SQLExec("DELETE FROM sonytvs_commands WHERE DEVICE_ID=".(int)$id);
  SQLExec("DELETE FROM sonytvs_macros WHERE DEVICE_ID=".(int)$id);
  SQLExec("DELETE FROM sonytvs WHERE ID='".(int)$id."'");
 }
/**
* sonytvs_commands search
*
* @access public
*/
 function search_sonytvs_commands(&$out) {
  require(DIR_MODULES.$this->name.'/sonytvs_commands_search.inc.php');
 }
/**
* sonytvs_commands edit/add
*
* @access public
*/
 function edit_sonytvs_commands(&$out, $id) {
  require(DIR_MODULES.$this->name.'/sonytvs_commands_edit.inc.php');
 }

 function sendCommand($device_id, $command, $value='') {
     $tv=SQLSelectOne("SELECT * FROM sonytvs WHERE ID=".(int)$device_id);
     $ip=$tv['IP'];
     $url='';
     $result='';
//
     $ch = curl_init();
     $headers = array();

     DebMes("Command request: ".$command.' value:'.$value,'sony');

     @mkdir(ROOT . 'cached/sony', 0777);
     $device_fname = ROOT . 'cached/sony/sonytv_'.$tv['ID'].'_id.txt';
     $tmpfname = ROOT . 'cached/sony/sonytv_'.$tv['ID'].'.txt';

      $device_uniq_id=@LoadFile($device_fname);
      if (!$device_uniq_id) {
       $device_uniq_id=1;
      }

     if ($command=='request_token') {
      @unlink($tmpfname);
      $device_uniq_id++;
      SaveFile($device_fname,$device_uniq_id);
     }

     if ($command=='confirm_token') {
         curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
         curl_setopt($ch, CURLOPT_USERPWD, ":".$tv['TOKEN']);
     }
     if ($command=='request_token' || $command=='confirm_token') {
         $url='http://'.$ip.'/sony/accessControl';
         $data = '{"id":'. $device_uniq_id.',"method":"actRegister","version":"1.0","params":[{"clientid":"MajorDoMo:'.$device_uniq_id.'","nickname":"MajorDoMo"},[{"clientid":"MajorDoMo:'.$device_uniq_id.'","value":"yes","nickname":"MajorDoMo","function":"WOL"}]]}';

     } elseif ($command=='programs') {

       $url='http://'.$ip.'/sony/appControl';
       $data = '{"method":"getApplicationList","params":[],"id":10, "version":"1.0"}';
       $headers[]= 'SOAPACTION:"urn:schemas-sony-com:service:IRCC:1#X_SendIRCC"';

     } elseif ($command=='test') {

         $url='http://'.$ip.'/sony/browser';
         //$data = '{"method":"getMethodTypes","params":[],"id":10, "version":"1.0"}';
         $data = '{"id":7,"method":"setTextUrl","version":"1.0","params":["http://google.com/","","",""]}';
         $headers[]= 'SOAPACTION:"urn:schemas-sony-com:service:IRCC:1#X_SendIRCC"';

     } elseif ($command=='keys') {

         $url='http://'.$ip.'/sony/system';
         $data = '{"method":"getRemoteControllerInfo","params":[],"id":10, "version":"1.0"}';
         $headers[]= 'SOAPACTION:"urn:schemas-sony-com:service:IRCC:1#X_SendIRCC"';

     } elseif ($command=='text') {

         $url='http://'.$ip.'/sony/appControl';
         $data = '{"method":"setTextForm","params":["'.$value.'"],"id":10, "version":"1.0"}';

     } elseif ($command=='app') {

         $url='http://'.$ip.'/sony/appControl';
         $data = '{"method":"setActiveApp","params":[{"uri":"'.$value.'"}],"id":10, "version":"1.0"}';

     } elseif ($command=='key') {

         $url='http://'.$ip.'/sony/IRCC';
         $controllerinfo = json_decode('{"id":20,"result":[{"bundled":true,"type":"RM-J1100"},[{
"name":"PowerOff","value":"AAAAAQAAAAEAAAAvAw=="},{
"name":"Input","value":"AAAAAQAAAAEAAAAlAw=="},{
"name":"GGuide","value":"AAAAAQAAAAEAAAAOAw=="},{
"name":"EPG","value":"AAAAAgAAAKQAAABbAw=="},{
"name":"Favorites","value":"AAAAAgAAAHcAAAB2Aw=="},{
"name":"Display","value":"AAAAAQAAAAEAAAA6Aw=="},{
"name":"Home","value":"AAAAAQAAAAEAAABgAw=="},{
"name":"Options","value":"AAAAAgAAAJcAAAA2Aw=="},{
"name":"Return","value":"AAAAAgAAAJcAAAAjAw=="},{
"name":"Up","value":"AAAAAQAAAAEAAAB0Aw=="},{
"name":"Down","value":"AAAAAQAAAAEAAAB1Aw=="},{
"name":"Right","value":"AAAAAQAAAAEAAAAzAw=="},{
"name":"Left","value":"AAAAAQAAAAEAAAA0Aw=="},{
"name":"Confirm","value":"AAAAAQAAAAEAAABlAw=="},{
"name":"Red","value":"AAAAAgAAAJcAAAAlAw=="},{
"name":"Green","value":"AAAAAgAAAJcAAAAmAw=="},{
"name":"Yellow","value":"AAAAAgAAAJcAAAAnAw=="},{
"name":"Blue","value":"AAAAAgAAAJcAAAAkAw=="},{
"name":"Num1","value":"AAAAAQAAAAEAAAAAAw=="},{
"name":"Num2","value":"AAAAAQAAAAEAAAABAw=="},{
"name":"Num3","value":"AAAAAQAAAAEAAAACAw=="},{
"name":"Num4","value":"AAAAAQAAAAEAAAADAw=="},{
"name":"Num5","value":"AAAAAQAAAAEAAAAEAw=="},{
"name":"Num6","value":"AAAAAQAAAAEAAAAFAw=="},{
"name":"Num7","value":"AAAAAQAAAAEAAAAGAw=="},{
"name":"Num8","value":"AAAAAQAAAAEAAAAHAw=="},{
"name":"Num9","value":"AAAAAQAAAAEAAAAIAw=="},{
"name":"Num0","value":"AAAAAQAAAAEAAAAJAw=="},{
"name":"Num11","value":"AAAAAQAAAAEAAAAKAw=="},{
"name":"Num12","value":"AAAAAQAAAAEAAAALAw=="},{
"name":"VolumeUp","value":"AAAAAQAAAAEAAAASAw=="},{
"name":"VolumeDown","value":"AAAAAQAAAAEAAAATAw=="},{
"name":"Mute","value":"AAAAAQAAAAEAAAAUAw=="},{
"name":"ChannelUp","value":"AAAAAQAAAAEAAAAQAw=="},{
"name":"ChannelDown","value":"AAAAAQAAAAEAAAARAw=="},{
"name":"SubTitle","value":"AAAAAgAAAJcAAAAoAw=="},{
"name":"ClosedCaption","value":"AAAAAgAAAKQAAAAQAw=="},{
"name":"Enter","value":"AAAAAQAAAAEAAAALAw=="},{
"name":"DOT","value":"AAAAAgAAAJcAAAAdAw=="},{
"name":"Analog","value":"AAAAAgAAAHcAAAANAw=="},{
"name":"Teletext","value":"AAAAAQAAAAEAAAA/Aw=="},{
"name":"Exit","value":"AAAAAQAAAAEAAABjAw=="},{
"name":"Analog2","value":"AAAAAQAAAAEAAAA4Aw=="},{
"name":"*AD","value":"AAAAAgAAABoAAAA7Aw=="},{
"name":"Digital","value":"AAAAAgAAAJcAAAAyAw=="},{
"name":"Analog?","value":"AAAAAgAAAJcAAAAuAw=="},{
"name":"BS","value":"AAAAAgAAAJcAAAAsAw=="},{
"name":"CS","value":"AAAAAgAAAJcAAAArAw=="},{
"name":"BSCS","value":"AAAAAgAAAJcAAAAQAw=="},{
"name":"Ddata","value":"AAAAAgAAAJcAAAAVAw=="},{
"name":"PicOff","value":"AAAAAQAAAAEAAAA+Aw=="},{
"name":"Tv_Radio","value":"AAAAAgAAABoAAABXAw=="},{
"name":"Theater","value":"AAAAAgAAAHcAAABgAw=="},{
"name":"SEN","value":"AAAAAgAAABoAAAB9Aw=="},{
"name":"InternetWidgets","value":"AAAAAgAAABoAAAB6Aw=="},{
"name":"InternetVideo","value":"AAAAAgAAABoAAAB5Aw=="},{
"name":"Netflix","value":"AAAAAgAAABoAAAB8Aw=="},{
"name":"SceneSelect","value":"AAAAAgAAABoAAAB4Aw=="},{
"name":"Mode3D","value":"AAAAAgAAAHcAAABNAw=="},{
"name":"iManual","value":"AAAAAgAAABoAAAB7Aw=="},{
"name":"Audio","value":"AAAAAQAAAAEAAAAXAw=="},{
"name":"Wide","value":"AAAAAgAAAKQAAAA9Aw=="},{
"name":"Jump","value":"AAAAAQAAAAEAAAA7Aw=="},{
"name":"PAP","value":"AAAAAgAAAKQAAAB3Aw=="},{
"name":"MyEPG","value":"AAAAAgAAAHcAAABrAw=="},{
"name":"ProgramDescription","value":"AAAAAgAAAJcAAAAWAw=="},{
"name":"WriteChapter","value":"AAAAAgAAAHcAAABsAw=="},{
"name":"TrackID","value":"AAAAAgAAABoAAAB+Aw=="},{
"name":"TenKey","value":"AAAAAgAAAJcAAAAMAw=="},{
"name":"AppliCast","value":"AAAAAgAAABoAAABvAw=="},{
"name":"acTVila","value":"AAAAAgAAABoAAAByAw=="},{
"name":"DeleteVideo","value":"AAAAAgAAAHcAAAAfAw=="},{
"name":"PhotoFrame","value":"AAAAAgAAABoAAABVAw=="},{
"name":"TvPause","value":"AAAAAgAAABoAAABnAw=="},{
"name":"KeyPad","value":"AAAAAgAAABoAAAB1Aw=="},{
"name":"Media","value":"AAAAAgAAAJcAAAA4Aw=="},{
"name":"SyncMenu","value":"AAAAAgAAABoAAABYAw=="},{
"name":"Forward","value":"AAAAAgAAAJcAAAAcAw=="},{
"name":"Play","value":"AAAAAgAAAJcAAAAaAw=="},{
"name":"Rewind","value":"AAAAAgAAAJcAAAAbAw=="},{
"name":"Prev","value":"AAAAAgAAAJcAAAA8Aw=="},{
"name":"Stop","value":"AAAAAgAAAJcAAAAYAw=="},{
"name":"Next","value":"AAAAAgAAAJcAAAA9Aw=="},{
"name":"Rec","value":"AAAAAgAAAJcAAAAgAw=="},{
"name":"Pause","value":"AAAAAgAAAJcAAAAZAw=="},{
"name":"Eject","value":"AAAAAgAAAJcAAABIAw=="},{
"name":"FlashPlus","value":"AAAAAgAAAJcAAAB4Aw=="},{
"name":"FlashMinus","value":"AAAAAgAAAJcAAAB5Aw=="},{
"name":"TopMenu","value":"AAAAAgAAABoAAABgAw=="},{
"name":"PopUpMenu","value":"AAAAAgAAABoAAABhAw=="},{
"name":"RakurakuStart","value":"AAAAAgAAAHcAAABqAw=="},{
"name":"OneTouchTimeRec","value":"AAAAAgAAABoAAABkAw=="},{
"name":"OneTouchView","value":"AAAAAgAAABoAAABlAw=="},{
"name":"OneTouchRec","value":"AAAAAgAAABoAAABiAw=="},{
"name":"OneTouchStop","value":"AAAAAgAAABoAAABjAw=="}]]}', true);
         $codes = array();
         foreach ($controllerinfo['result'] as $k => $v) {
             foreach ($v as $code) {
                 if (isset($code['name'])) {
                     $codes[strtolower($code['name'])] = $code['value'];
                 }
             }
         }

         $data = '<?xml version="1.0"?>
    <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <s:Body>';
         $data .= '<u:X_SendIRCC xmlns:u="urn:schemas-sony-com:service:IRCC:1">
    <IRCCCode>' . $codes[strtolower($value)] . '</IRCCCode>
    </u:X_SendIRCC>';
         $data .= '</s:Body>
    </s:Envelope>';

         $headers[]= 'SOAPACTION:"urn:schemas-sony-com:service:IRCC:1#X_SendIRCC"';

     }

     if ($url!='' && $data!='') {

      $headers[] = 'X-Auth-PSK: '.$tv['TOKEN'];
      $headers[] = 'Content-Type: text/xml; charset=UTF-8';
      $headers[] = 'Content-Length: ' . strlen($data);
      curl_setopt($ch, CURLOPT_COOKIEJAR, $tmpfname);
      curl_setopt($ch, CURLOPT_COOKIEFILE, $tmpfname);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // connection timeout
      curl_setopt($ch, CURLOPT_TIMEOUT, 5);  // operation timeout
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      //curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $result=curl_exec($ch);

      DebMes("Request: ".$url."\n".$data,'sony');
      DebMes("Response:\n".$result,'sony');

     }
     curl_close($ch);     

    return $result;

 }

 function propertySetHandle($object, $property, $value) {
   $table='sonytvs_commands';
   $properties=SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
        if ($properties[$i]['TITLE']=='command') {
            $commands=explode(',',$value);
            $totalc = count($commands);
            for ($ic = 0; $ic < $totalc; $ic++) {
                $this->sendCommand($properties[$i]['DEVICE_ID'],trim($commands[$ic]));
                if ($ic < ($totalc-1)) {
                 usleep(500);
                }
            }
        }
        if ($properties[$i]['TITLE']=='key') {
            $commands=explode(',',$value);
            $totalc = count($commands);
            for ($ic = 0; $ic < $totalc; $ic++) {
                $this->sendCommand($properties[$i]['DEVICE_ID'],'key',trim($commands[$ic]));
                if ($ic < ($totalc-1)) {
                 usleep(500);
                }
            }
        }
    }
   }
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS sonytvs');
  SQLExec('DROP TABLE IF EXISTS sonytvs_commands');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data = '') {
/*
sonytvs - 
sonytvs_commands - 
*/
  $data = <<<EOD
 sonytvs: ID int(10) unsigned NOT NULL auto_increment
 sonytvs: TITLE varchar(100) NOT NULL DEFAULT ''
 sonytvs: IP varchar(255) NOT NULL DEFAULT ''
 sonytvs: TOKEN varchar(255) NOT NULL DEFAULT ''
 
 sonytvs_commands: ID int(10) unsigned NOT NULL auto_increment
 sonytvs_commands: TITLE varchar(100) NOT NULL DEFAULT ''
 sonytvs_commands: VALUE varchar(255) NOT NULL DEFAULT ''
 sonytvs_commands: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 sonytvs_commands: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 sonytvs_commands: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 sonytvs_commands: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 
 sonytvs_macros: ID int(10) unsigned NOT NULL auto_increment
 sonytvs_macros: TITLE varchar(100) NOT NULL DEFAULT ''
 sonytvs_macros: VALUE varchar(255) NOT NULL DEFAULT ''
 sonytvs_macros: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 sonytvs_macros: UPDATED datetime 
  
 
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXByIDI4LCAyMDE3IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
