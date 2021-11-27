<?php
/**
 * Created by Alberto de Almeida Guilherme.
 * Date: 04/05/2021
 * Time: 14:29
 */
$dir = 'resultados';
if(is_dir($dir)){
	$array = glob($dir.'/*.*');
	echo json_encode(
		['total' => sizeof($array)]
	);
	die();
}
http_response_code(500);
die("Ocorreu um erro com os vídeos, contate o suporte");

