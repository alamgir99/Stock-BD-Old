
<?php
 //run once a month
 //backs up php files  and stores them to S3
 $backupdir = '/s3backup';

 // get the trading date year
 $date_yr= date("Y");

 //whole date
 $date_sig = date("Y-m-d");


 //csv file
 $tgzfname = 'stockbd-php-'.$date_sig.'.tgz';
 chdir('/www/stockbd');
 $command = 'tar cvzf  '.$backupdir.'/'.$tgzfname.' prv pub bare.php config.php ';
 exec($command);
 //upload
 $command = '/usr/bin/s3cmd put '.$backupdir.'/'.$tgzfname.'  s3://stockbd/'.$tgzfname;
 echo $command;
 exec($command);
 //delete the file
 unlink($backupdir.'/'.$tgzfname);
 
