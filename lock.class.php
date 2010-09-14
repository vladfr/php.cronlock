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
 * Use lock::getData and lock::setData for a simple data persistence layer.
 * You can use getData whenever, but after calling lock::lock();
 * CAVEAT! Data doesn't get saved when lock is locked.
 * If you want to save it regardless, you need to call lock::saveData(); manually
 *
 *
 * @author Vlad Fratila
 * @version 1.0.0
 */

/**
 * @package cronlock
 */
class lock{

	const LOCK_ERR_PROCESS_FAILED = 1;
	const LOCK_ERR_STILL_RUNNING = 2;
	const LOCK_FINISHED_SUCCESS = 3;
	const LOCK_STARTING = 4;
	const LOCK_FAILED = 5;

	public static $lock_dir = 'tmp/';
	public static $timeout;
	public static $lid = null;
	public static $debug = true;
	
	private static $pid = null;
	private static $last_pid = null;
	private static $lock_file = null;
	private static $lock_data_file = null;
	private static $log_file = null;
	private static $db = null;
	private static $data = null;

	protected static $start;
	protected static $end;
	protected static $time;
	protected static $lock_aquired = false;
	protected static $lock_status;
	protected static $lock_error;

	function __construct(){}
	function __clone(){}
	
	public static function lock(){
		self::$lock_file = self::$lock_dir.self::$lid.'.lock';
		self::$lock_data_file = self::$lock_dir.self::$lid.'.dat';
		self::$log_file = self::$lock_dir.self::$lid.'.log';
		self::$pid = getmypid();
		self::$start = microtime(true);
		
		if(file_exists(self::$lock_data_file)){
			self::$data = unserialize(file_get_contents(self::$lock_data_file));
		}
		
		if(file_exists(self::$lock_file)){
			$contents = file_get_contents(self::$lock_file);
			list(self::$last_pid, $old_timestamp) = explode(PHP_EOL, $contents);
			if( ($old_timestamp + self::$timeout*60) < time() ){
				self::$lock_aquired = true;
				self::log('a previous process timed out:'.self::$last_pid);
				self::log('lock aquired. starting');
			}
			else{
				self::$lock_aquired = false;
				self::log('exiting. overlapped with '.self::$last_pid);
			}
		}
		else{
			self::$lock_aquired = true;
			self::log('lock aquired. starting');
		}
		
		if(self::$lock_aquired){
			$h = fopen(self::$lock_file, 'w');
			fwrite($h, self::$pid . PHP_EOL . time());
		}
	return self::$lock_aquired;
	}
	
	public static function saveData(){
		$h = fopen(self::$lock_data_file, 'w');
		fwrite($h, serialize(self::$data));
		fclose($h);
	}
	
	public static function getData(){
		return self::$data;
	}
	
	public static function setData($data){
		self::$data = $data;
	}
	
	public static function unlock(){
		self::$end = microtime(true);
		self::$time = self::$end - self::$start;
		self::saveData();
		unlink(self::$lock_file);
		self::log('finished successfully in '.self::$time.' ms');
	return true;
	}
	
	private static function log($m){
		$message = date('Y-m-d H:i:s').' # '.self::$pid.' - '.$m.PHP_EOL;
		$h = fopen(self::$log_file, 'a+');
		fwrite($h, $message);
		fclose($h);
	}
	
	
}

