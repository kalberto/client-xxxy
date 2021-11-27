<?php
/**
 * Created by Alberto de Almeida Guilherme.
 * Date: 30/04/2021
 * Time: 15:32
 */

class LogHandler {

	public static function writeErrorLog($message){
		if(!file_exists('logs/'))
			mkdir('logs/', 0755);
		if(!file_exists('logs/errors/'))
			mkdir('logs/errors/', 0755);
		$file = 'logs/errors/error-log-'.date('Y-m-d').'.txt';
		$handler = fopen($file,'w');
		fclose($handler);
		$handler = fopen($file,'a');
		fwrite($handler,json_encode($message));
	}

	static function writeDebugLog($message){
		if(!file_exists('logs/'))
			mkdir('logs/', 0755);
		if(!file_exists('logs/debug/'))
			mkdir('logs/debug/', 0755);
		$file = 'logs/debug/debug-log-'.date('Y-m-d').'.txt';
		$handler = fopen($file,'w');
		fclose($handler);
		$handler = fopen($file,'a');
		fwrite($handler,json_encode($message));
	}
}