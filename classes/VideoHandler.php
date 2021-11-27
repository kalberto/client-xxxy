<?php
/**
 * Created by Alberto de Almeida Guilherme.
 * Date: 03/05/2021
 * Time: 00:04
 */

use Intervention\Image\ImageManagerStatic as Image;

class VideoHandler {

	protected $current_hash = '';
	protected $name_1 = '';
	protected $name_2 = '';
	protected $text = '';
	protected $texts = [];
	protected $user_img_name = '';
	protected $colour = '#ffffff';
	protected $colour_1 = '#ffffff';
	protected $colour_2 = '#ffffff';
	protected $colour_text = '#ffffff';
	protected $font_size = 120;
	protected $font_size_text = 60;
	protected $ffmpeg_path = null;
	protected $video_path = null;
	protected $video_result = '';
	protected $video_temp = '';
	protected $black_bones_font = 'fonts/black_bones.ttf';
	protected $arial_font = 'fonts/arial.ttf';
	protected $sofia_font = 'fonts/sofia_pro_regular.ttf';

	public function     __construct($current_hash,$name_1,$name_2,$user_img_name,$text) {
		$this->current_hash = $current_hash;
		$this->name_1 = $name_1;
		$this->name_2 = $name_2;
		$this->text = $text;
		$this->user_img_name = $user_img_name;
		$ffmpeg_path = strpos(php_uname('s'), 'Windows') === FALSE ? ( strpos(php_uname('s'), 'Linux') === FALSE ? 'binaries/ffmpeg' : 'binaries/linux/ffmpeg') : 'binaries\ffmpeg.exe';
		$this->ffmpeg_path = $ffmpeg_path;
		$this->video_path = getcwd().'/videos/video.mp4';
		$this->createDirs();
	}

	private function createDirs(){
		if(!is_dir('videos/users/'))
			mkdir('videos/users/',0755);
		if(!is_dir('videos/users/'.$this->current_hash))
			mkdir('videos/users/'.$this->current_hash,0755);
		if(!is_dir('resultados/'))
			mkdir('resultados/',0755);
		if(!is_dir('images/users/'))
			mkdir('images/users/',0755);
		if(!is_dir('images/users/'.$this->current_hash))
			mkdir('images/users/'.$this->current_hash,0755);
	}

	public function makeVideo(){
		$this->handleUserText();
		$this->handleUserImage();
		$this->video_temp = getcwd().'/videos/users/'.$this->current_hash.'/temp.mp4';
		$command = $this->mountPutImageOnVideo($this->video_path,getcwd().'/images/users/'.$this->current_hash.'/background.jpg',$this->video_temp);
		$output = null;
		$retval = null;
		$this->executeCommand($command,$output,$retval);
		if($retval !== 0){
			LogHandler::writeErrorLog(json_encode(['command' => $command, 'output' => $output]));
		}
		$this->writeMessage();
		$temp = getcwd().'/videos/users/'.$this->current_hash.'/temp4.mp4';
		$command = $this->mountRemoveFirstFrame($this->video_temp, $temp);
		$output = null;
		$retval = null;
		$this->executeCommand($command,$output,$retval);
		if($retval !== 0){
			LogHandler::writeErrorLog(json_encode(['command' => $command, 'output' => $output]));
		}
		$this->video_temp = $temp;
		$this->video_result = getcwd().'/resultados/'.$this->current_hash.'.mp4';
		$command = $this->ffmpeg_path.' -i '.$this->video_temp.' -i videos/audio.mp3 -map 0:v -map 1:a -c:v copy '.$this->video_result;
		$output = null;
		$retval = null;
		$this->executeCommand($command,$output,$retval);
		if($retval !== 0){
			LogHandler::writeErrorLog(json_encode(['command' => $command, 'output' => $output]));
		}
	}

	protected function handleUserText(){
		$words = explode(' ',$this->text);
		$text = '';
		$this->texts = [];
		foreach ($words as $word){
			if(strlen($text . $word) < 20){
				if($text != '')
					$text .= ' ';
				$text .=$word;
			}else{
				$this->texts[] = $text;
				$text = $word;
			}
		}
		$this->texts[] = $text;
	}

	protected function handleUserImage(){
		$user = Image::make('images/users/'.$this->current_hash.'/'.$this->user_img_name);
		$perfect = 966/702;
		$image_s = $user->width() / $user->height();
		if($perfect == $image_s){
			$user->resize(966,702);
			$position = [60,382];
		}elseif ($perfect > $image_s){
			$user->resize(966, null, function ($constraint) {
				$constraint->aspectRatio();
			});
			$y = ($user->height() - 702)*0.5;
			$position = [60,382-$y];
		}else{
			$user->resize(null, 702, function ($constraint) {
				$constraint->aspectRatio();
			});
			$x = ($user->width() - 966)*0.5;
			$position = [60-$x,302];
		}
		$user->save('images/users/'.$this->current_hash.'/'.$this->user_img_name);
		$background = Image::make('images/layouts/clean_image.png');
		$background->insert('images/users/'.$this->current_hash.'/'.$this->user_img_name,'top-left',(int)$position[0],(int)$position[1]);
		$background->save('images/users/'.$this->current_hash.'/background.jpg', 60);
	}

	protected function writeMessage(){
		$temp = getcwd().'/videos/users/'.$this->current_hash.'/temp2.mp4';
		$texts = [];
		$texts[] = [$this->name_2,'(w-text_w)/2',1725,$this->colour_2,3,5,3,1,5,0,$this->black_bones_font,$this->font_size];
		$texts[] = [$this->name_1,'(w-text_w)/2',1280, $this->colour_1,1,5,1,1,5,0,$this->black_bones_font,$this->font_size];
		$filter_complex = '';
		foreach ($texts as $text){
			if($filter_complex !== '')
				$filter_complex .= ',';
			$filter_complex .= $this->mountDrawText($text[10],$text[11],$text[0],$text[3],$text[4],$text[5],$text[6],$text[7],$text[8],$text[9],$text[1],$text[2]);
		}
		$this->writeText($this->video_temp,$filter_complex,$temp);
		$this->video_temp = $temp;
		$temp = getcwd().'/videos/users/'.$this->current_hash.'/temp3.mp4';
		$filter_complex = '';
		$texts = [];
		$size = 75;
		foreach ($this->texts as $key => $text){
			$texts[] = [$text,'(w-text_w)/2',1150 + ($size*0.5 + $key*$size),$this->colour,10,15,10,1,15,0,$this->sofia_font,$this->font_size_text];
		}
		for ($i = (sizeof($texts)-1); $i >= 0;$i--){
			if($filter_complex !== '')
				$filter_complex .= ',';
			$filter_complex .= $this->mountDrawText($texts[$i][10],$texts[$i][11],$texts[$i][0],$texts[$i][3],$texts[$i][4],$texts[$i][5],$texts[$i][6],$texts[$i][7],$texts[$i][8],$texts[$i][9],$texts[$i][1],$texts[$i][2]);
		}
		$this->writeText($this->video_temp,$filter_complex,$temp);
		$this->video_temp = $temp;
	}

	protected function writeText($input,$filter_complex,$output){
		$command = $this->mountCommandText($input,$filter_complex,$output);
		$output = null;
		$retval = null;
		$this->executeCommand($command,$output,$retval);
		if($retval !== 0){
			LogHandler::writeErrorLog($output);
		}
	}


	protected function mountPutImageOnVideo($input,$image_path, $output){
		$command = $this->ffmpeg_path.' -i '.$image_path.' -i '.$input.' -filter_complex "[1:v]colorkey=0x0CE304:0.3:0.0[ckout];[0:v][ckout]overlay[out]" -map "[out]" -bsf:v h264_mp4toannexb -preset:v ultrafast '.$output;
		return $command;
	}

	protected function mountDrawText($font,$font_size,$text,$colour,$start_time,$end_time,$fade_in_start,$fade_in_duration,$fade_out_start,$fade_out_duration,$x,$y){
		$command = "drawtext=fontfile='$font':fontsize=$font_size:fontcolor=$colour:text='$text':x=$x:y=$y:enable='between(t,$start_time,$end_time)',fade=t=in:start_time=$fade_in_start:d=$fade_in_duration:alpha=1,fade=t=out:start_time=$fade_out_start:d=$fade_out_duration:alpha=1[fg];[0][fg]overlay=format=auto,format=yuv420p";
		return $command;
	}


	protected function mountRemoveFirstFrame($input,$output){
		$command = "$this->ffmpeg_path -i $input -vf trim=start_frame=1:end_frame=600+1 -an -bsf:v h264_mp4toannexb -preset:v ultrafast $output";
		return $command;
	}

	protected function mountCommandText($input,$filter_complex,$output){
		$command = "$this->ffmpeg_path -i $input -filter_complex \"$filter_complex\" -codec:v libx264 -c:a copy -preset:v ultrafast $output";
		return $command;
	}

	protected function executeCommand($command,&$output,&$retval){
		exec( $command, $output,$retval);
	}
}