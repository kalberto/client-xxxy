<?php
/**
 * Created by Alberto de Almeida Guilherme.
 * Date: 27/04/2021
 * Time: 10:34
 */
require 'vendor/autoload.php';
require 'classes/LogHandler.php';
require 'classes/DeleteHandler.php';
require 'classes/VideoHandler.php';
require 'classes/OSHelper.php';

function makeVideo($current_hash,$nome_filho,$nome_mae,$file_name,$text){
	$starttime = microtime(true);
	$endtime = microtime(true);
	$video_handler_time = $endtime - $starttime;
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$video_handler = new VideoHandler($current_hash,ucfirst(strtolower($nome_filho)),ucfirst(strtolower($nome_mae)),$file_name,$text);
	$video_handler->makeVideo();
	$response = [
		'msg' => 'O vídeo foi gerado com sucesso',
		'link' => $actual_link.'resultados/'.$current_hash.'.mp4',
		'download' => $actual_link.'download.php?video='.$current_hash.'.mp4',
		'vid_handler_time' => $video_handler_time,
		'ios' => OSHelper::isIOS(),
		'video' => $current_hash.'.mp4'
	];
	http_response_code(200);
	DeleteHandler::deleteUserUpload($current_hash);
	echo json_encode($response);
}

switch($_SERVER['REQUEST_METHOD'])
{
	case 'POST':
		$the_request = &$_POST;
		$errors = [];
		if(!isset($_POST['nome']) || $_POST['nome'] == '' ){
			$errors['nome'] = 'O primeiro nome é obrigatorio';
		}
		if(!isset($_POST['nome_amor']) || $_POST['nome_amor'] == ''){
			$errors['nome_amor'] = 'O segundo nome é obrigatório';
		}
		if(!isset($_POST['texto_carinho']) || $_POST['texto_carinho'] == ''){
			$errors['texto_carinho'] = 'A mensagem é obrigatória';
		}else{
			if(strlen($_POST['texto_carinho']) > 76){
				$errors['texto_carinho'] = 'A mensagem não pode ter mais de 75 caracteres';
			}
		}
		if(!isset($_FILES['file']) || getimagesize($_FILES['file']['tmp_name']) === false){
			$errors['file'] = 'Uma imagem é obrigatório';
		}
		if(sizeof($errors) > 0){
			$response = [
				'msg' => 'Preencha os campos corretamente.',
				'errors' => $errors
			];
			http_response_code(422);
			echo json_encode($response);
		}else{
			$current_hash = ''.strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['nome']))).time().'';
			if(!file_exists('images/users/'))
				mkdir('images/users/', 0755);
			$upload_dir = 'images/users/'.$current_hash.'/';
			$upload_file = $upload_dir . basename($_FILES['file']['name']);
			if(!file_exists($upload_dir))
				mkdir($upload_dir, 0755);
			if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
				makeVideo($current_hash,$_POST['nome'],$_POST['nome_amor'],basename($_FILES['file']['name']),$_POST['texto_carinho']);
			}else{
				LogHandler::writeErrorLog('index.php 65. Ocorreu um erro no upload do arquivo.');
				$response = [
					'msg' => 'Falha no upload do vídeo',

				];
				http_response_code(500);
			}
		}
	break;
	default:
		print_r($_SERVER['REQUEST_METHOD']);
		print_r('Essa rota só funciona com POST');
}