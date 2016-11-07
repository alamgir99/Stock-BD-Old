
<?php
//backsup data and stores them to S3

$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

$backupdir = '/s3backup';

 // get the trading date year
 $date_yr= date("Y");

 //whole date
 $date_sig = date("Y-m-d");


 //csv file
 $csvfname = 'dse-'.$date_sig.'.csv';
 $tgzfname = 'dse-'.$date_sig.'.tgz';
 $csvpath = SBD_ROOT.'/data/csvdata/'.$date_yr;
 chdir($csvpath);
 $command = 'tar cvzf  '.$backupdir.'/'.$tgzfname.'  '.$csvfname;
 exec($command);
 //upload
 $command = '/usr/bin/s3cmd/./s3cmd put '.$backupdir.'/'.$tgzfname.'  s3://stockbd/csvdata/'.$tgzfname;
 exec($command);
 //delete the file
 unlink($backupdir.'/'.$tgzfname);
 
 //mst file
 $mstfname = 'mst-'.$date_sig.'.txt';
 $tgzfname = 'mst-'.$date_sig.'.tgz';
 $mstpath = SBD_ROOT.'/data/mstdata/'.$date_yr;
 chdir($mstpath);
 $command = 'tar cvzf  '.$backupdir.'/'.$tgzfname.'  '.$mstfname;
 exec($command);
 //upload
 $command = '/usr/bin/s3cmd/./s3cmd put '.$backupdir.'/'.$tgzfname.'  s3://stockbd/mstdata/'.$tgzfname;
 exec($command);
 //delete the file
 unlink($backupdir.'/'.$tgzfname);


 
 //folder contaning min data
 $tgzfname = 'min-'.$date_sig.'.tgz';
 $minpath = SBD_ROOT.'/data/mindata/'.$date_yr;
 chdir($minpath);
 $command = 'tar cvzf  '.$backupdir.'/'.$tgzfname.'  '.$date_sig;
 exec($command);
 //upload
 $command = '/usr/bin/s3cmd/./s3cmd put '.$backupdir.'/'.$tgzfname.'  s3://stockbd/mindata/'.$tgzfname;
 exec($command);
 //delete the file
 unlink($backupdir.'/'.$tgzfname);

 //sectoral data folder
 //$secdata = SBD_ROOT.'/data/mindata/'.$date_yr.'/'.$date_sig;
 $tgzfname = 'sec-'.$date_sig.'.tgz';
 $minpath = SBD_ROOT.'/data/secdata/'.$date_yr;
 chdir($minpath);
 $command = 'tar cvzf  '.$backupdir.'/'.$tgzfname.'  '.$date_sig;
 exec($command);
 //upload
 $command = '/usr/bin/s3cmd/./s3cmd put '.$backupdir.'/'.$tgzfname.'  s3://stockbd/secdata/'.$tgzfname;
 exec($command);

 //delete the file
 unlink($backupdir.'/'.$tgzfname);
