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

function makeVideo($current_hash,$opcao,$nome_filho,$nome_mae,$file_name,$identifier){
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	$actual_link = str_replace('whatsapp.php','',$actual_link);
	$video_handler = new VideoHandler($opcao,$current_hash,ucfirst(strtolower($nome_filho)),ucfirst(strtolower($nome_mae)),$file_name);
	LogHandler::writeDebugLog(json_encode(['step:'=>'começou a fazer o vídeo']));
	$video_handler->makeVideo();
	LogHandler::writeDebugLog(json_encode(['step:'=>'terminou o vídeo']));
	$data = (object)[
		"url"=> $actual_link.'resultados/'.$current_hash.'.mp4',
		"identifier"=> $identifier,
	];
	$options = array(
		'http' => array(
			'method'  => 'POST',
			'content' => json_encode( $data ),
			'header'=>  "Content-Type: application/json\r\n" .
			            "Accept: application/json\r\n"
		)
	);
	$url = 'https://flows.messagebird.com/flows/invocations/webhooks/290551d4-c55d-4e90-8492-0e75703ea7e5';
	LogHandler::writeDebugLog(json_encode(['dados_request:'=>$options,'url' => $url]));
	$context  = stream_context_create( $options );
	$result = file_get_contents( $url, false, $context );
	LogHandler::writeDebugLog(json_encode(['result_request:'=>$result]));
	http_response_code(200);
	$response = [
		'msg' => 'O vídeo foi gerado com sucesso',
		'link' => $actual_link.'resultados/'.$current_hash.'.mp4',
		'download' => $actual_link.'download.php?video='.$current_hash.'.mp4',
		'ios' => OSHelper::isIOS(),
		'video' => $current_hash.'.mp4'
	];
	DeleteHandler::deleteUserUpload($current_hash);
	echo json_encode($response);
}

$identifier ='retorno';
switch($_SERVER['REQUEST_METHOD'])
{
	case 'POST':
		LogHandler::writeDebugLog(json_encode(['data:'=>$_POST['data']]));
		$data = (object)[];
		if(isset($_POST['data'])){
			$data = json_decode($_POST['data']);
		}
		$errors = [];
		if(!isset($data->opcao) || (intval($data->opcao) !== 1 && intval($data->opcao) !== 2 && intval($data->opcao) !== 3 && intval($data->opcao) !== 4)){
			$errors['opcao'] = 'A opção é obrigatória';
		}
		if(!isset($data->nome_filho) || $data->nome_filho == '' ){
			$errors['nome_filho'] = 'O nome é obrigatorio';
		}
		if(!isset($data->nome_mae) || $data->nome_mae == ''){
			$errors['nome_mae'] = 'O nome da mãe é obrigatório';
		}
		if(!isset($data->image) || !isset($data->image->url)){
			$errors['file'] = 'Um link para uma imagem é obrigatório';
		}
		if(isset($data->identifier))
			$identifier = $data->identifier;
		if(sizeof($errors) > 0){
			$response = [
				'msg' => 'Preencha os campos corretamente.',
				'errors' => $errors
			];
			http_response_code(422);
			echo json_encode($response);
		}else{
			LogHandler::writeDebugLog(json_encode(['dados whats:'=>$data]));
			$current_hash = ''.strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data->nome_filho))).time().'';
			$upload_dir = 'images/users/'.$current_hash.'/';
			$upload_file = $upload_dir . basename($data->image->url);
			if(!file_exists($upload_dir))
				mkdir($upload_dir.basename(''), 0755);
			if(file_put_contents( $upload_file,file_get_contents($data->image->url))){
				makeVideo($current_hash,$data->opcao,$data->nome_filho,$data->nome_mae,basename($data->image->url),$identifier);
			}else{
				LogHandler::writeDebugLog('index.php 65. Ocorreu um erro no download do arquivo.');
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