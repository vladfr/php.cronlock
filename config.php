<?php 
/**
 * cronlock
 * This class implements cronjob locking to prevent concurent processes from running.
 * Locks are kept on the filesystem.
 * 
 * Setup:
 * You need to include config.php inside this directory in your cron job file.
 * See config options there.
 * 
 * Usage: You need to wrap your entire cron job like this:
 * if(lock::lock() !== FALSE){
 * 	//execute your job here
 * 	lock::unlock();
 * }
 * 
 * 
 * @author Vlad Fratila
 * @version 1.0.0
 */

error_reporting(E_ALL);
ini_set('memory_limit','32M');
ini_set('max_execution_time','300');

//requiring cron lock class
require dirname(__FILE__).'/lock.class.php';

//set the identifier (lock id). 
//If you use this class in multiple jobs, you need to set this from the cron job file
lock::$lid = 'my_function';

//your temp dir, it will hold lock files and logs for all the cron jobs
lock::$lock_dir = dirname(__FILE__).'/tmp/';

/**
 * If a job fails, the cleanup step will be skipped and the lock will not be removed.
 * For this case, we write a timestamp inside the .lock file that allows us to implement
 * timeouts. 
 * 
 * A script times out when $timeout minutes have passed since its inception.
 * If the script detects a timed out lock, it will overwrite it with a new lock.
 * Therefore, our jobs will keep running even if one of them failed
 * 
 * (You will see these events in the logs.)
 */
lock::$timeout = 4;