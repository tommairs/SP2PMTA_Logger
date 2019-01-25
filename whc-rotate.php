<?php
// Script to simultate Momentum's ec_rotate function.
include ('env.php');
date_default_timezone_set($TZ);

  $logs = array("bouncelog.csv","bouncelog-inband.csv","deliverylog-inband.csv","success-inband.csv");
  $logname = "pmtalogs";  // final storage location of converted log files under /var/log/ 
  $logdir = "/var/log/". $logname ."/"; 
  $filelist1 = scandir($logdir);
  $foundone = "false";
  $maxcount = 0;
  $currfile = "";
  $currentminutes = date('i');
  $min = ($currentminutes % 20);

  while (($currentminutes % 20) != 0){
   echo "$currentminutes , $min - waiting for a 20 minute segment...\r\n";
   sleep(60);
   $currentminutes = date('i');
   $min = ($currentminutes % 20);
 }

// Rotate older files

  // First find the oldest file IDs
  foreach($filelist1 as $f1){
    $fcount = 0;
    if(substr($f1,-3,3) == "bz2"){
      $fileparts = explode(".",$f1);
      $fcount = $fileparts[2];
      if ($fcount == 1){
        $foundone = "true";
      }
      $f2 = $fileparts[0].".".$fileparts[1];
      if ($fcount > $maxcount){
        $filearray[$f2] = $fcount;
        $maxcount = $fcount;
      }
      if ($f2 != $currfile){
        $maxcount = 0;
      }
      $currfile = $f2; // Store the current working file to see if it changes
    }
  }
  // Check to see if a ".1.bz2" file exists first
  // Only rotate if it is there
  if ($foundone == "true"){

    // Now increment all of them
    echo "Starting file rotation \r\n";
    foreach($filearray as $rot=>$iter){
      $max = $iter+1;
      for($next = $max; $next >= 1; $next--){
        if (file_exists($logdir.$rot . "." .$iter .".bz2")){
          echo "Renaming ". $logdir.$rot . "." .$iter .".bz2  to ". $logdir.$rot.".".$next .".bz2 \r\n";
          rename($logdir.$rot.".".$iter.".bz2", $logdir.$rot.".".$next.".bz2");
        }
        $iter--;
      }
    }
  }

// Now compress the .ec files with bzip2
  foreach($logs as $f){
    $ecfile = $logdir.$f;
    $tmpfile = $ecfile.".1";
    $bzfile = $tmpfile .".bz2";
    rename($ecfile,$tmpfile);                // rename to avoid file locking
    echo "Compressing $tmpfile \r\n";
    system("bzip2 $tmpfile");
    chmod($bzfile, 0755);   
    unlink($tmpfile);                        // Now kill the temp file
  }

?>
