<?php
// file consumer and converter for PMTA format CSV files

// Define log field separator and other vars
  error_reporting(0);
  $f = "data_1";          // location of raw text file log data
  $logname = "pmtalogs";  // final storage location of converted log files under /var/log/
  $CustomKey = "";        // If you have custom metadata to log from webhooks, set that vriable name here 
  $Sep = ",";       // Set the type of data separator used in the final log output

  $logdir = "/var/log/". $logname ."/";
  $dir    = '/var/www/html/whc/data/';

  $markedfiles=[];
  $logarray = [];
  $bounceloginband = "";
  $deliveryloginband = "";
  $successinband = "";

// catalog all files in data folder
// and read each file into data log
  $filelist = scandir($dir);
  $mids = [];
  foreach ($filelist as $f){
    if (substr($f,0,5) == "data_"){
      $timestamp = date('U');
      print "$timestamp: Processing file $f \r\n";
      $filecontents = file_get_contents($dir.$f);
      $data_array = json_decode($filecontents, true);
      extracttologs($data_array,$logdir);
      if (file_exists($dir.$f)){       // then mark the file for deletion
        array_push($markedfiles,$dir.$f);
      }
    }
  }
  foreach($markedfiles as $mf){
     unlink($mf);                       // delete all the processed files
  }
  unset ($mids);

// End of MAIN
/***************************/


function extracttologs($logarray,$logdir){

//  var_dump($logarray);  // For Debugging only
    foreach($logarray as $num=>$msys){
     foreach($msys as $prime=>$delta){
      foreach($delta as $one=>$two){
        $rm = $two['rcpt_meta'];
        if ($two['rcpt_meta']['$CustomKey']){
          $cp = explode("|||",$two['rcpt_meta']['$CustomKey']);
          $Header_XXX = $cp[1];
          $vctx_mid = $cp[0];
        }
        if ($two['friendly_from']){
          $from = explode("@",$two['friendly_from']);
          $m = $from[0];
          $M = $from[1];
          $origFrom = $two['friendly_from'];
        }
        if ($two['rcpt_to']){
          $to = explode("@",$two['rcpt_to']);
          $r = $to[0];
          $R = $to[1];
          $rcptTo = $two['rcpt_to'];
        }
        $timeLogged = $two['timestamp'];
        $dlvType = $two['delv_method'];
        $JobID = $two['message_id'];
        $g = $two['binding_group'];
        $N = $two['num_retries'];
        $C = $two['bounce_class'];
        $vmta = $two['binding'];
        $dlvDestinationIp = $two['ip_address'];
        $dlvSize = $two['msg_size'];
        $dsnStatus = $two['raw_reason'];
        $dsnStatus = str_replace("\r\n", " ", $dsnStatus);  //Clean up carriage returns in bounce messages

        if (($i) AND ($eventtype)){

        // Verify we are not writing duplicates
          if (!in_array($eventID,$mids)){
            $mids[] = $eventID;

            // Write out the records

        $logstring .= $timeLogged.",";
        $logstring .= $timeLogged.",";
        $logstring .= $timeLogged.",";
        $logstring .= $origFrom.",";
        $logstring .= $rcptTo.",";
        $logstring .= $orcpt.",";
        $logstring .= $dsnAction.",";
        $logstring .= $dsnStatus.",";
        $logstring .= $dsnDiag.",";
        $logstring .= $dsnMta.",";
        $logstring .= $srcType.",";
        $logstring .= $srcMta.",";
        $logstring .= $dlvType.",";
        $logstring .= $dlvSourceIp.",";
        $logstring .= $dlvDestinationIp.",";
        $logstring .= $dlvEsmtpAvailable.",";
        $logstring .= $dlvSize.",";
        $logstring .= $vmta.",";
        $logstring .= $jobId.",";
        $logstring .= $envId.",";
        $logstring .= $header_XXX;
            
        if ($two['type'] == "injection"){
          $bouncelog .= "r,".$logstring;
        }
            
        if ($two['type'] == "out_of_band"){
          $bouncelog .= "b,".$logstring;
        }

        if ($two['type'] == "policy_rejection"){
          $rejectlog .= "b,".$logstring;
        }

        if ($two['type'] == "bounce"){
          $bouncelog .= "b,".$logstring;
        }
        if ($two['type'] == "delay"){
          $bounceloginband .= "t,".$logstring;
        }

        if ($two['type'] == "delivery"){        
          $deliverylog .= "d,".$logstring;
        }
      }

echo "Writing delivery for $eventID\r\n";

            }
          }
        }
      }
    }
  }

      // Write log (create if needed)
      $filelist1 = scandir($logdir);
      if (!$filelist1){
        mkdir($logdir, 0777, true);
        chmod($logdir, 0666);
      }
  
  $file = "bouncelog.csv";
  $filecontents = $bouncelog;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);

  $file = "bouncelog-inband.csv";
  $filecontents = $bounceloginband;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);

  $file = "deliverylog-inband.csv";
  $filecontents = $deliveryloginband;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);

  $file = "success-inband.csv";
  $filecontents = $successinband;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);
  

  chmod($logdir. "/*", 0666);
}


?>

