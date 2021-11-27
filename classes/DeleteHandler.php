<?php
/**
 * Created by Alberto de Almeida Guilherme.
 * Date: 30/04/2021
 * Time: 15:36
 */

class DeleteHandler {

	public static function deleteUserUpload($current_hash){
		$images = 'images/users/'.$current_hash.'/';
		DeleteHandler::deleteFolder($images);
		$generated = 'images/generated/'.$current_hash;
		DeleteHandler::deleteFolder($generated);
		$generated = 'videos/users/'.$current_hash;
		DeleteHandler::deleteFolder($generated);
	}

	protected static function deleteFolder($dir){
		if(is_dir($dir)){
			array_map('unlink', glob($dir.'/*'));
			rmdir($dir);
		}
	}
}