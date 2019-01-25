<?php
// A webhook collector to convert JSON webhook data to flat file Logs.
// This file simply collects inbound batches and saves to disk as raw text files
// with the filepath format /var/www/html/whc/data_YYYYMMDDHHMMSS.txt

  include('env.php');
  date_default_timezone_set($TZ);
  $verb = $_SERVER['REQUEST_METHOD'];
  if ($verb == "POST") {
   if (($_SERVER['PHP_AUTH_USER'] == $whcuser) AND ($_SERVER['PHP_AUTH_PW'] == $whcpass)){
    $jsonStr = file_get_contents("php://input");
    http_response_code(200);

    $rnum = rand(1000,9999);
    $t = date("YmdHis") . $rnum;
    $Jfile = './data/data_'.$t.'.txt';
    if (file_exists($Jfile)) {
       $fn = basename($Jfile,".txt");
      $seq = 0;
      $ftail = substr($fn,-2,1);
      if ($ftail == "-"){
        $seq = substr($fn,-1);
      }
      $seq++;
      $Jfile = basename($Jfile,".txt")."-".$seq.".txt";
    }
      $fh = fopen($Jfile, "w") or die("Unable to create file!");
      fwrite($fh, $jsonStr);
      fclose($fh);
   }
  }

?>
