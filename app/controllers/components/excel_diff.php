<?php
App::import('Component', 'AbstractDiff');
App::import('Vendor', 'PHPExcel', array('file' => 'PHPExcel/PHPExcel/IOFactory.php'));
define('USER_ENTRY_COLOR', PHPExcel_Style_Fill::FILL_NONE);
class ExcelDiffComponent extends AbstractDiff {

	var $__solXLS;
	var $__solWorksheet;
	var $__exoXLS;
	var $__exoWorksheet;
	
	
	function compare($user_file_path, $solution_file_path){
		$correct = true;
		$this->__error = "";
		$this->__html = "";
		try{
		
			$this->__exoXLS = PHPExcel_IOFactory::load($user_file_path);
			$this->__exoXLS->setActiveSheetIndexByName('Exercice');
			$this->__exoWorksheet = $this->__exoXLS->getActiveSheet();

			$this->__solXLS = PHPExcel_IOFactory::load($solution_file_path);
			$this->__solXLS->setActiveSheetIndexByName('Solution');
			$this->__solWorksheet = $this->__solXLS->getActiveSheet();
			
			//check named ranges defined
			$exoRanges = $this->_namedRangesToArray($this->__exoXLS, 'Exercice');
			
			$tabs = new Tabs();
			$tabs->newTab('Cellules Nommées');
			
			$missing_ranges = 0;
			$correct_ranges = array();
			
			foreach ($this->__solXLS->getNamedRanges() as $range) {
				if($range->getWorksheet()->getTitle() == 'Solution'){
					$range_string = $range->getRange();
					if(array_key_exists($range_string, $exoRanges)){
                        //show orange if not string match
						//$color = $range->getName() == $exoRanges[$range_string] ? '#57E964' : '#FE9B12'; 
                        $color = '#57E964';
						$correct_ranges[$range_string] = '<tr style="background-color:' . $color . '">' .
														  '<td>' . $range_string . '</td>' .
														  '<td>' . $exoRanges[$range_string] . '</td>' .
														  '<td>' . $range->getName() . '</td></tr>'; 
						$correct = $correct and true;
						unset($exoRanges[$range_string]);
					}else{
						$missing_ranges++;
						$correct = false;
					}
				}
			}			
			if($missing_ranges > 0){
				$this->__error .= "il manque " . $missing_ranges . " cellule(s) nommée(s)<br/>";
			}
			
			if(count($exoRanges) > 0){
				$this->__error .= "il y a " . count($exoRanges) . " cellule(s) nommée(s) qui ne sont pas dans le corrigé<br />";
				foreach ($exoRanges as $range => $name){
					$this->_setRangeBorder($this->__exoWorksheet, $range, 'FE9B12');
					$correct_ranges[$range] = '<tr style="background-color:#FE9B12">' .
														  '<td>' . $range . '</td>' .
														  '<td>' . $name . '</td>' .
														  '<td>-</td></tr>'; 
				}
			}
			
			ksort($correct_ranges);
			$tabs->write('<table><tr><th>range</th><th>votre nom</th><th>nom du corrigé</th></tr>');
			foreach ($correct_ranges as $range_result){
				$tabs->write($range_result);
			}	
			$tabs->write('</table>');
			
			for($attempt=0;$attempt < 3;$attempt++){
				$attempt_text = "essai " . ($attempt + 1);
				$this->_copyStartDataToExo();
				$errorcolor = 'FF0000';
				$redcolor = 'FF0000';
				$okcolor = '00FF00';
				foreach ($this->__solWorksheet->getRowIterator() as $row) {
					$cellIterator = $row->getCellIterator();
					foreach ($cellIterator as $solCell) {
						if ($this->_cellBgColorIsUserEntry($solCell)) {
							$exoCell = $this->__exoWorksheet->getCell($solCell->getCoordinate());
							//check if is formula
							if($this->_isCellFormula($exoCell)){
								//check value
								$valueSol = $solCell->getCalculatedValue(); 
								$valueExo = $exoCell->getCalculatedValue();
								if( bccomp($valueSol, $valueExo, 12) == 0){
									$this->_setCellBgColorRGB($exoCell, $okcolor);
									$correct = $correct and true;
								}else{
									$this->_setCellBgColorRGB($exoCell, $errorcolor);
									$correct = false;
									$this->__error .= '<a href="#" onclick="$(\'#tabs\').tabs(\'select\', ' . ((2*$attempt) + 1) .')">' . $exoCell->getCoordinate() . '</a>' .
													  " faux (votre valeur: $valueExo solution: $valueSol)<br/>";
									//switch to orange because might be correct if first error is fixed
									$errorcolor = 'FFA500';
								}
							}else{
								$this->__error .= '<a href="#" onclick="$(\'#tabs\').tabs(\'select\', ' . ((2*$attempt) + 1) .')">' . $exoCell->getCoordinate() . "</a> n'est pas une formule<br/>";
								$this->_setCellBgColorRGB($exoCell, $redcolor);
								$correct = false;
							}
						}
					}
				}
				$tabs->newTab("Exercice " . $attempt_text);				
				$tabs->write($this->_printSheet($this->__exoXLS));
				$tabs->newTab("Solution " . $attempt_text);				
				$tabs->write($this->_printSheet($this->__solXLS));
				if(!$correct){
					break;					
				}
			}
			$this->__html .= $tabs->output();
			$this->_desalocateFile($this->__solXLS);
			$this->_desalocateFile($this->__exoXLS);
		} catch (Exception $e) {
			$correct = false;
			$this->__error = $e->getMessage();
		}
		$this->__correct = $correct; //TODO: calculate or a corectness score?
		return $this->__correct;
	}
	
	function _getCellBgColorRGB($cell){
		$style = $cell->getParent()->getStyle($cell->getCoordinate());
		$fill = $style->getFill();
		if($fill->getFillType() == PHPExcel_Style_Fill::FILL_NONE){
			return PHPExcel_Style_Fill::FILL_NONE;
		}else{
			return $fill->getStartColor()->getRGB();
		}	
	}

	function _setCellBgColorRGB($cell, $rgb){
		$style = $cell->getParent()->getStyle($cell->getCoordinate());
		$fill = $style->getFill();
		$fill->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$fill->getStartColor()->setRGB($rgb);
	}
	
	function _setRangeBorder($sheet, $range, $color){
		$styleArray = array(
			'borders' => array(
				'outline' => array(
					'style' => PHPExcel_Style_Border::BORDER_THICK,
					'color' => array('rgb' => $color),
				),
			),
		);
		$sheet->getStyle($range)->applyFromArray($styleArray);
	}

	function _cellBgColorIsUserEntry($cell){
		return $this->_getCellBgColorRGB($cell) == USER_ENTRY_COLOR;
	}
	
	function _namedRangesToArray($xls, $workbookTitle=null){
		$ranges = array();
		foreach ($xls->getNamedRanges() as $range){
			if(is_null($workbookTitle) or $range->getWorksheet()->getTitle() == $workbookTitle){
				$ranges[$range->getRange()] = $range->getName();
			}
		}
		return $ranges;
	}

	function _isCellFormula($cell){
		return $cell->getDataType() == PHPExcel_Cell_DataType::TYPE_FORMULA;
	}
	
	function _copyStartDataToExo(){
		PHPExcel_Calculation::getInstance()->clearCalculationCache();
		foreach ($this->__solWorksheet->getRowIterator() as $row) {
			$cellIterator = $row->getCellIterator();
			foreach ($cellIterator as $solCell) {
				if ($this->_getCellBgColorRGB($solCell) == 'FFFF99') {
					$exoCell = $this->__exoWorksheet->getCell($solCell->getCoordinate());
					$exoCell->setValue($solCell->getCalculatedValue());
				}
			}
		}
	}
	
	function _printSheet($xls){
		$objWriter = PHPExcel_IOFactory::createWriter($xls, 'HTML');
		$objWriter->setSheetIndex(2);
		$objWriter->setUseInlineCss(true);
		$objWriter->buildCSS();
		return $objWriter->generateSheetData();
	}
	
	
	
	function _desalocateFile($file){
		$file->disconnectWorksheets();
		unset($file);
	}
	
/* we are only using html writter for now...
only xlsx writter seems to support comments
function addComment($cell, $text){
	$comment = $cell->getParent()->getComment($cell->getCoordinate());
	$comment->setAuthor('PHPExcel');
	$objCommentRichText = $comment->getText()->createTextRun('PHPExcel');
	$objCommentRichText->getFont()->setBold(true);
	$comment->getText()->createTextRun("\r\n");
	$comment->getText()->createTextRun($text);
	$comment->setVisible(true);
}
*/
	
}

class Tabs {
	var $_tabs = array();
	var $_currentTab = "default";
	
	function newTab($title){
		$this->_currentTab = $title;
	}
	
	function write($text){
		if(array_key_exists($this->_currentTab, $this->_tabs)){
			$this->_tabs[$this->_currentTab] .= $text;	
		}else{
			$this->_tabs[$this->_currentTab]= $text;
		}
	}
	
	function output(){
		$html_content = "";
		$html_title = "";
		$i=0;
		foreach($this->_tabs as $title => $tab){
			$html_title .= '<li><a href="#tab-' . $i . '">' . $title . '</a></li>';
			$html_content .= '<div id="tab-' . $i . '">' . $tab. '</div>';
			$i++;
		}
		$html = '<div id="tabs">';
		$html .= "<ul>$html_title</ul>";
		$html .= $html_content;
		$html .= '</div>';
		$html .= '<script type="text/javascript">$("#tabs").tabs();</script>';
		return $html;		
	}
		
}


