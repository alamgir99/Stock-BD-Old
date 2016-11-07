
<?php
 //run once a day
 //compress the gz files in /s3backup/mysql and stores them to S3
 $backupsrc = '/s3backup/mysql';
 $backupdir = '/s3backup';
   
 //whole date
 $date_sig = date("Y-m-d");
     
 //tar the file
 $tgzfname = 'mysql-'.$date_sig.'.tgz';
 $command = 'tar cf  '.$backupdir.'/'.$tgzfname.'  '.$backupsrc.'/*.*';
 exec($command);
 
 //delete backed up files
 $command = 'rm -rf '.$backupsrc .'/*.*';
 exec($command);
 
 //upload
 $command = '/usr/bin/s3cmd put '.$backupdir.'/'.$tgzfname.'  s3://stockbd/'.$tgzfname;
 echo $command;
 exec($command);
 //delete the file
 unlink($backupdir.'/'.$tgzfname);
 
