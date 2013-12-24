<?php
App::import('Component', 'AbstractDiff');
class HtmlDiffComponent extends AbstractDiff {
	
	var $__blocktags =  array('html', 'head', 'body', 'table', 'tr', 'select');
	var	$__singletags = array('hr', 'br', 'input', 'meta');
	var	$__styletags = array('b','u','br','i');
	var	$__replace_list ='/\s+/';
	var	$__replace_with_list = ' ';
	
	function compare($user_file_path, $solution_file_path){
		$this->__html = "";
		$this->__error = "";
		set_error_handler(array($this, "_customError"));
		$user_xml = $this->_loadNormalizedDocument($user_file_path);
		restore_error_handler();
		$sol_xml = $this->_loadNormalizedDocument($solution_file_path);
		$this->__correct = $this->_compareNode($user_xml->documentElement, $sol_xml->documentElement);
		return $this->__correct;
		
	}

	function _customError($errno, $errstr){
		$this->__error .= "$errstr<br>";
	}	
	
	function _loadNormalizedDocument($file_path){
		$content = file_get_contents($file_path); 
		$content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true)); 
		$content = $this->_removeBOM($content);		 
		$xml = new DOMDocument(); 
		$xml->loadHTML($content); 
		$xml->normalizeDocument(); 
		return $xml;
	}
	function _removeBOM($content){
		if(substr($content, 0,3) == pack("CCC",0xef,0xbb,0xbf)) { 
			$content=substr($content, 3); 
		}
		return $content;
	}
	
	function _compareNode($node, $nodesol, $depth=0){
		if ($node instanceof DOMText and $nodesol instanceof DOMText){
			return $this->_matchDOMText($node, $nodesol);
		}else if($node instanceof DOMElement and $nodesol instanceof DOMElement){
			return $this->_matchDOMElement($node, $nodesol, $depth);
		}else{
			$this->__html .= '<div class="red">'. ($node instanceof DOMElement ? htmlentities('<' . $node->tagName . '>') : 'erreur') .'</div>';
			return false;
		}
	}
	
	function _matchDOMText($node, $nodesol){
		$str1 = preg_replace($this->__replace_list, $this->__replace_with_list, $node->textContent);
		$str2 = preg_replace($this->__replace_list, $this->__replace_with_list, $nodesol->textContent);
		$match = trim($str1) == trim($str2);
		$this->__html .='<span class="' . ($match ? 'green': 'red') . '">';
		$this->__html .= $node->textContent; 
		$this->__html .= '</span>';
		return $match;
	}
	
	function _matchDOMElement($node, $nodesol, $depth){
			$indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
			$match = $node->tagName == $nodesol->tagName;		
			$this->__html .= '<div class="' . ($match ? 'green': 'red') . '">';
			
			//only ident for tags that are not inline with text
			if(!in_array($node->tagName, $this->__styletags)){
				$this->__html .= $indent;
			}
			
			//draw element
			$this->__html .= htmlentities('<') . $node->tagName;
	
			foreach ($nodesol->attributes as $attributesol){
				$attribute = $node->attributes->getNamedItem($attributesol->name);
				if($attribute){
					 $match = $match && ($attributesol->value == $attribute->value);
				}else{
					$match = false;
				}
				$this->__html .= '<span class="' . ($match ? 'green': 'red') . '">';
				if($attribute){
					$this->__html .= ' '.$attribute->name . '="' . $attribute->value .'"';
				}else{
					$this->__html .= " erreur";
				}
				$this->__html .= '</span>';
			}
			//close non single tags
			if(!in_array($node->tagName, $this->__singletags)){
				$this->__html .= htmlentities('>');
			}
			//block tags are displayed on their own line
			if(in_array($node->tagName, $this->__blocktags)){
				$this->__html .= "<br>";
			}
			//continue to explore tree
			if($match && $nodesol->hasChildNodes()){
				$n=0;
				foreach ($nodesol->childNodes as $child) { 
					//if child false stop
					if(!$this->_compareNode($node->childNodes->item($n), $child, $depth+1)){
						$match = false;
						break;
					}
					$n++;
				}
			}
			//add righ ident spaces if
			if(in_array($node->tagName, $this->__blocktags)){
				$this->__html .= $indent;
			}
			//end tag if not hr, br, input, meta
			if(in_array($node->tagName, $this->__singletags)){
				$this->__html .= htmlentities(' />');
			}else{
				$this->__html .= htmlentities('</' . $node->tagName . '>');
			}
			$this->__html .= "</div>\n";
			return $match;
	}
}
?>