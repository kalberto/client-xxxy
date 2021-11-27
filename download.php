<?php
/**
 * Created by Alberto de Almeida Guilherme.
 * Date: 04/05/2021
 * Time: 14:29
 */
if($_GET['video']){
	if(file_exists ('resultados/'.$_GET['video'])){
		header("Content-Description: File Transfer");
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename="'.$_GET['video'].'"');
		$file = 'resultados/'.$_GET['video'];
		readfile ($file);
		exit();
	}else{
		echo 'Vídeo não encontrado';
		die();
	}
}
echo 'Nome do vídeo não está presente';
die();
