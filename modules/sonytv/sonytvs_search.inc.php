<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['sonytvs_qry'];
  } else {
   $session->data['sonytvs_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_sonytvs="ID DESC";
  $out['SORTBY']=$sortby_sonytvs;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM sonytvs WHERE $qry ORDER BY ".$sortby_sonytvs);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
