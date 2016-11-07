<?php
//#!/usr/local/bin/php
/* this scripts performs all the initialisation tasks
before a tradning session.

Runs each trading day, just before the trading begins.

*/
$dir = dirname(dirname(__FILE__)); // location of  root dir
include  $dir.'/config.php';

if($sb_market_open === false)
exit(0);

// clean up the temp folder
unlink(SBD_ROOT.'/prv/temp/dsesnap.htm');
unlink(SBD_ROOT.'/prv/temp/dsegen.htm');
unlink(SBD_ROOT.'/prv/temp/snapshot.txt');
unlink(SBD_ROOT.'/prv/temp/dgencap.php'); 

// reset snapshot counter
unlink(SBD_ROOT.'/prv/temp/snapcounter.txt');

//
// get the trading date year
 $date_yr= date("Y");
 //create mstdata /<year> folder if needed
 $mstdir = SBD_ROOT.'/data/mstdata/'.$date_yr;
 if(is_dir($mstdir) === false){
	mkdir($mstdir);
 }
 
 //create csvdata/<year> folder
 $csvdir = SBD_ROOT.'/data/csvdata/'.$date_yr;
if(is_dir($csvdir) === false){
	mkdir($csvdir);
 }
 
 //create mindata/<year> folder
 $mindir = SBD_ROOT.'/data/mindata/'.$date_yr;
if(is_dir($mindir) === false){
	mkdir($mindir);
 }
 
 //create the mindata/<year>/<date> folder
 $tdate = date("Y-m-d");
 $mindir = SBD_ROOT.'/data/mindata/'.$date_yr.'/'.$tdate;
if(is_dir($mindir) === false){
	mkdir($mindir);
 }
 
  
 //create secdata/<year> folder
 $secdir = SBD_ROOT.'/data/secdata/'.$date_yr;
if(is_dir($secdir) === false){
    mkdir($secdir);
 }
 
 //create the secdata/<year>/<date> folder
 $tdate = date("Y-m-d");
 $secdir = SBD_ROOT.'/data/secdata/'.$date_yr.'/'.$tdate;
if(is_dir($secdir) === false){
    mkdir($secdir);
 }
