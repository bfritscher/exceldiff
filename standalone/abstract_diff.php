<?php
abstract class AbstractDiff {
	var $__html = "";
	var $__error = "";
	var $__correct = false;
	
	function getHtml(){
		return $this->__html;
	}
	
	function getCorrect(){
		return $this->__correct;
	}
	
	function getError(){
		return $this->__error;
	}
	
	function getJsonResult(){
        $data = new stdClass();
		$data->error = base64_encode($this->__error);
		$data->html =  base64_encode($this->__html);
		$data->correct = $this->__correct;
		echo json_encode($data);
	}
	
	abstract function compare($user_file_path, $solution_file_path);
}