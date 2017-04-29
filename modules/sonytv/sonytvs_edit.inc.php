<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='sonytvs';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  // step: default
  if ($this->tab=='') {
  //updating '<%LANG_TITLE%>' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;
  //updating 'TOKEN' (varchar)
   global $token;
   $rec['TOKEN']=$token;
  }
  // step: data
  if ($this->tab=='data') {
  }
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record

        $command=array();
        $command['DEVICE_ID']=$rec['ID'];
        $command['TITLE']='command';
        $command['ID']=SQLInsert('sonytvs_commands',$command);

        $command=array();
        $command['DEVICE_ID']=$rec['ID'];
        $command['TITLE']='key';
        $command['ID']=SQLInsert('sonytvs_commands',$command);

    }

    if ($this->tab=='' && $rec['TOKEN']!='') {
     $this->sendCommand($rec['ID'],'confirm_token');
    }

    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  }

  // step: remote
  if ($this->tab=='remote') {
      global $remove_id;
      if ($remove_id) {
          SQLExec("DELETE FROM sonytvs_macros WHERE ID=".(int)$remove_id);
      }

      global $new_macros_title;
      global $new_macros_value;
      if ($new_macros_title!='' && $new_macros_value!='') {
          $macro=array();
          $macro['DEVICE_ID']=$rec['ID'];
          $macro['UPDATED']=date('Y-m-d H:i:s');
          $macro['TITLE']=$new_macros_title;
          $macro['VALUE']=$new_macros_value;
          SQLInsert('sonytvs_macros',$macro);
      }
      $macros=SQLSelect("SELECT * FROM sonytvs_macros WHERE DEVICE_ID=".$rec['ID']." ORDER BY UPDATED DESC");
      $total = count($macros);
      for ($i = 0; $i < $total; $i++) {
      }
      $out['MACROS']=$macros;
  }

  // step: data
  if ($this->tab=='data') {
   //dataset2
   $new_id=0;
   global $delete_id;
   if ($delete_id) {
    SQLExec("DELETE FROM sonytvs_commands WHERE ID='".(int)$delete_id."'");
   }
   $properties=SQLSelect("SELECT * FROM sonytvs_commands WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
        /*
      global ${'title'.$properties[$i]['ID']};
      $properties[$i]['TITLE']=trim(${'title'.$properties[$i]['ID']});
        */
      global ${'value'.$properties[$i]['ID']};
      //$properties[$i]['VALUE']=trim(${'value'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
        /*
      global ${'linked_method'.$properties[$i]['ID']};
      $properties[$i]['LINKED_METHOD']=trim(${'linked_method'.$properties[$i]['ID']});
        */
      SQLUpdate('sonytvs_commands', $properties[$i]);

      if (${'value'.$properties[$i]['ID']}!='' && $properties[$i]['TITLE']=='key') {
          $this->sendCommand($properties[$i]['DEVICE_ID'],'key',trim(${'value'.$properties[$i]['ID']}));
      }
      if (${'value'.$properties[$i]['ID']}!='' && $properties[$i]['TITLE']=='command') {
          $this->sendCommand($properties[$i]['DEVICE_ID'],trim(${'value'.$properties[$i]['ID']}));
      }


      $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
   }
   $out['PROPERTIES']=$properties;   
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
