<?php
App::import('Component', 'AbstractDiff');
class JpgDiffComponent extends AbstractDiff {
		
	function compare($user_file_path, $solution_file_path){
		$this->__html = "";
		$this->__error = "";
		set_error_handler(array($this, "_customError"));
		$config = parse_ini_file($solution_file_path);
		
		$filesize = filesize($user_file_path);
		
		$size = getimagesize($user_file_path);
		
		$this->__html .= "<p><b>Votre image</b></p>";
		$this->__html .= "<p>Longeur: " . $size[0] . "px<br/>";
		$this->__html .= "Largeur: " . $size[1] . "px<br/>";
		$this->__html .= "Taille: " . round($filesize/1024) . "kb<br/>";
		$this->__html .= "JPG: " . ($size[2] == IMAGETYPE_JPEG ? 'oui' : 'non') . "</p>";
		
		$this->__html .= "<p><b>Objectif du tp</b></p>";
		$this->__html .= "<p>Longeur: " . $config['width'] . "px<br/>";
		$this->__html .= "Largeur: " . $config['height'] . "px<br/>";
		$this->__html .= "Taille: &lt;" . ($config['filesize']/1024) . "kb<br/>";
		$this->__html .= "JPG: oui</p>";
		
		restore_error_handler();
		
		if($size[0] != $config['width']){
			$this->__error .= "mauvaise longeur! ";
		}
		
		if($size[1] != $config['height']){
			$this->__error .= "mauvaise largeur! ";
		}
		
		if($size[2] != IMAGETYPE_JPEG){
			$this->__error .= "mauvais format! ";
		}
		
		if($filesize > $config['filesize']){
			$this->__error .= "trop grand! ";
		}

		$this->__correct = $size[0]== $config['width'] &&
			$size[1] == $config['height'] &&
			$filesize <= $config['filesize'] &&
			$size[2] == IMAGETYPE_JPEG;
		return $this->__correct;
		
	}

	function _customError($errno, $errstr){
		$this->__error .= "$errstr<br>";
	}	
}
?>