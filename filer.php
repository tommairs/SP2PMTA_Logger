<?php
// file consumer and converter for PMTA format CSV files

// Define log field separator and other vars
  error_reporting(0);
  $f = "data_1";          // location of raw text file log data
  $logname = "pmtalogs";  // final storage location of converted log files under /var/log/
  $CustomKey = "";        // If you have custom metadata to log from webhooks, set that vriable name here 
  $Separator = ",";       // Set the type of data separator used in the final log output
  $BouncelogName = "boouncelog";  // Filename for the log
  $DeliverylogName = "boouncelog";  // Filename for the log
  $ReceptionlogName = "boouncelog";  // Filename for the log
  $Name = "boouncelog";  // Filename for the log

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
          $vctx_sip = $cp[1];
          $vctx_mid = $cp[0];
        }
        if ($two['friendly_from']){
          $from = explode("@",$two['friendly_from']);
          $m = $from[0];
          $M = $from[1];
        }
        if ($two['rcpt_to']){
          $to = explode("@",$two['rcpt_to']);
          $r = $to[0];
          $R = $to[1];
        }
        $t = $two['timestamp'];
        $i = $two['message_id'];
        $g = $two['binding_group'];
        $N = $two['num_retries'];
        $C = $two['bounce_class'];
        $b = $two['binding'];
        $H = $two['ip_address'];
        $s = $two['msg_size'];
        $n = $two['raw_reason'];
        $n = str_replace("\r\n", " ", $n);  //Clean up carriage returns in bounce messages

        if (($i) AND ($eventtype)){

        // Verify we are not writing duplicates
          if (!in_array($eventID,$mids)){
            $mids[] = $eventID;

            // Write out the records

        if ($two['type'] == "out_of_band"){
          $bouncelog .= "".$t."@".$i."@<bid>@<cid>@B@".$r."@".$R."@".$m."@".$M."@".$g."@".$b."@21@".$C."@".$s."@".$H."@".$n."";
        }

        if ($two['type'] == "policy_rejection"){
          $bounceloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$vctx_mid."||".$N."||".$C."||".$n."\r\n";
          $deliveryloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$b."||".$vctx_mid."||".$N."||".$C."||P||".$n."\r\n";
        }

        if ($two['type'] == "bounce"){
          $bounceloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$vctx_mid."||".$N."||".$C."||".$n."\r\n";
          $deliveryloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$b."||".$vctx_mid."||".$N."||".$C."||P||".$n."\r\n";
        }
        if ($two['type'] == "delay"){
          $bounceloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$vctx_mid."||".$N."||".$C."||".$n."\r\n";
          $deliveryloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$b."||".$vctx_mid."||".$N."||".$C."||T||".$n."\r\n";
        }

        if ($two['type'] == "delivery"){
          //type, timeLogged,timeLogged,timeQueued,timeImprinted,origFrom,rcptTo,orcpt,dsnAction,dsnStatus,dsnDiag,dsnMta,srcType,srcMta,dlvType,dlvSourceIp,dlvDestinationIp,
          //... dlvEsmtpAvailable,dlvSize,vmta,jobId,envId,header_XXX
                    
          // d, 1191435989,,,, testfrom@port25.com, testto@port25.com,,relayed,2.0.0(success),, [192.168.0.10](192.168.0.10),success,api,,smtp,10.25.25.211,10.25.25.20,,316,vmta0,0,0 
          $deliveryloginband .= "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$b."||".$vctx_mid."||".$N."||".$C."||D||".$n."\r\n";  
          $successinband .=  "".$t."||".$vctx_sip."||".$m."||".$M."||".$i."||".$r."||".$R."||".$g."||".$b."||".$vctx_mid."||".$N."||".$C."||".$n."\r\n";  
  
          

        
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


  $file = $BouncelogName;
  $filecontents = $bouncelog;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);

  $file = $BouncelogName;
  $filecontents = $bounceloginband;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);

  $file = $DeliverylogName;
  $filecontents = $deliveryloginband;
  file_put_contents($logdir.$file, $filecontents, FILE_APPEND);

  chmod($logdir. "/*", 0666);
}


?>

