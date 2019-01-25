# SP2PMTA_Logger

A tool to consume SparkPost Webhook data and convert it to PMTA standard CSV logs

**NOTE: THIS CODE IS PROVIDED "AS_IS" WITHOUT WARRANTEE OF ANY KIND AND IS UNSUPPORTED CODE.**
Feel free to drop any comments or issues in the ISSUES on this project and I'll ypdate as I can.


 - written to run on stand-alone CentOS-7 instance
 - consumes SparkPost webhooks (with custom meta-data)
 - decomposes and stores all data
 - writes out as flat file logs (.ec) for customer to consume

  
== To Replicate ==

 1) Create an appropriately sized AWS Centos7 instance to build on.  
  These are the recommended settings that were ised in this project
 - Launch a CentOS 7.x instance IE: (CentOS Linux 7 x86_64 HVM EBS 1704_01 - ami-51076231) 
 - Select instance type m3.medium, (recommended) click NEXT
 - Select "Protect against accidental termination", click NEXT
 - Update the volume size to 100Mb
 - Click NEXT and add a tag so you can find your instance later
 - Select a security group (Sidenote - I use "ALL TCP" and configure firewalld later)
 - Click LAUNCH and select or create a key pair so you can log in for further configuration
 
 2) Before going further, create a resolvable domain in DNS and ensure that it is actually resolvable.

 3) Open a shell and log in 
    IE: ssh -i mykey.pem centos@ec2-52-191-177-235.us-west-2.compute.amazonaws.com

 4) Copy the script "osprep.sh" to the new instance and modify it if necessary, then execute as root.
 
 5) Wait for that to finish, it may be a while....
 
 6) Now replicate the repo to /var/www/html/whc/ or copy the following files there. index.php, filer.php, and whc-rotate.php.
 
 7) After install, chmod 755 filer.php, and whc-rotate.php so they can be run as cron jobs.
 8) Ensure that the cron files were updated.  They should look like this:
 <pre>
   4 -rw-r--r-- 1 root root 86 Jul 18 14:16 whc-filer
      */05 * * * *    root   /bin/php   /var/www/html/whc/filer.php >/var/log/whc-filer.log

   4 -rw-r--r-- 1 root root 92 Jul 18 14:17 whc-rotate
      */20 * * * *    root   /bin/php   /var/www/html/whc/whc_rotate.php >/var/log/whc_rotate.log
      
</pre>
  *Remember to clean out these logs occasionally.*
 
 9) Create a Sparkpost webhook and point it to http://\<yourdomain\>/whc/
  
  == To Manage ==
  
  The rotation script is on a cron to run every 20 minutes, but keep an eye on it anyway.  If the cron stops working for some reason, the logs folder will fill up.
  
  New webhooks are only accepted if they are accompanied by a valid uername and password
  
  New webhook data will create new timestamped files in ***/var/www/html/whc/data/***
  
  The consumer ***filer.php*** will run on a cron job every 5 minutes to consume and delete those files in order and write them out to new \*.csv logs in ***/var/log/<pmtalogname>/***
  
  The rotation script ***whc-rotate.php*** will run on a cron job every 20 minutes moving \*.ec to \*.ec.1.bz2 and rotating \*.csv.1.bz2 to \*.csv.2.bz2, likewize \*.csv.2.bz2 => \*.csv.3.bz2, \*.csv.3.bz2 => \*.csv.4.bz2, etc..
  
  The customer should have some external application consume and delete the \*.csv.\*.bz2 files in reverse order (oldest first) 
  
  During the filing and rotation processes, there are logs being written to at /var/log/filer.log and /var/log/whc-rotate.log.  These should be cleaned out regularly.
  
  If the above log files are not required for debugging purposes, the cron output can be redirected to null.  They are not required for normal operation.  IE: \*/05 * * * *    root   /bin/php   /var/www/html/whc/filer.php  >/dev/null 2>&1
  
  
  
