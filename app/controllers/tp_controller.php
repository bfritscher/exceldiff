<?php
App::import('Vendor', 'tcpdf/tcpdf');
App::import('Vendor', 'fpdi/fpdi');
App::import('Sanitize');

class TpController extends AppController {

	var $name = 'Tp';
	var $uses = array('Log', 'User', 'Webpage');
	var $components = array('Auth', 'ExcelDiff', 'ExcelDiffDev', 'HtmlDiff', 'JpgDiff', 'IMSManifest');
	
	function beforeFilter() {
		parent::beforeFilter();
        $this->Auth->allow('upload', 'compare', 'uploaddev', 'comparedev');
	}
		
	function generate($tpid){
		$this->_generate_helper($tpid, $this->Auth->user('matricule'), $this->Auth->user('full_name'));
	}
	
	function _generate_helper($tpid, $matricule, $full_name){
		//TODO: move to view
		$this->autoRender = false;
		if($this->__getTpType($tpid) == 'pdf'){
			$filename = 'template_scan.pdf';
			$templatePath = $this->__validTpPathOrDie($tpid, $filename, 'source') .$filename;
			$pdf =& new FPDI("P", "mm", "A4");
			$pdf->SetFont("Helvetica", "", 14);
			$pagecount = $pdf->setSourceFile($templatePath);
			
			// set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('hec.unil.ch/info1ere/');
			$pdf->SetTitle('TP '.$tpid);

			// remove default header/footer
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
			
			//set margins
			$pdf->SetMargins(7, 7, 7);
			
			for ($n = 1; $n <= $pagecount; $n++) { 
				$tplidx = $pdf->ImportPage($n);
				$pdf->AddPage();
				$pdf->useTemplate($tplidx);
				if($n==1){
					$style = array(
						'position' => 'R',
						'border' => false,
						'padding' => 'auto',
						'fgcolor' => array(0,0,0),
						'bgcolor' => false, //array(255,255,255),
						'text' => true,
						'font' => 'helvetica',
						'fontsize' => 8,
						'stretchtext' => 4
					);
					
					$pdf->write1DBarcode($matricule, 'C128B', '', '', 90, 20, 0.7, $style, 'M');
					$pdf->setXY(109, 35);
					$pdf->Cell(90, 5, $full_name, 0, 1,'R');
					
				}else{
					$pdf->SetXY(10, 5);
					$pdf->SetFont("Helvetica", "", 8);
					$pdf->Cell(0, 10, $full_name . " (" . $matricule . ") p." . $pdf->getAliasNumPage(), 0, 1,'L');
				}
			}
			$pdf->Output('tp_' . $tpid . '_'. $matricule . '.pdf', 'D');
		}else{
			echo "wrong TP type";
		}
	}
	
	function admin_generate(){
		if (!empty($this->data)) {
			$this->autoRender = false;
			$this->_generate_helper($this->data['tpid'], $this->data['matricule'], $this->data['full_name']);
		}else{
			$this->set('tps', $this->_tpList());
		}
	}
	
	function view($tpid){
		$this->view = 'Media';
		$type = $this->__getTpType($tpid);
		switch($type){
			case 'pdf':
				$name = $this->Auth->user('matricule');
				break;
			default:
				preg_match("/(.*)@/", $this->Auth->user('email'), $matches);
				$name = $matches[1];
				break;
		}
		$tpPath = $this->__validTpPathOrDie($tpid, $name . '.' . $type, 'rendu');		
        $params = array(
              'id' => $name.'.'.$type,
              'name' => 'tp_'. $tpid . '_' . $name,
              'download' => true,
              'extension' => $type,
              'path' => $tpPath
       );
       $this->set($params);
	}
	function upload(){
		$this->layout = 'sco';
	}
	
	function uploaddev(){
		$this->layout = 'sco';
	}
	

	function admin_create($tpid, $type){
		if(mkdir(DATA_TP_ROOT . $tpid)){
			$result = mkdir(DATA_TP_ROOT . $tpid . DS .'source');
			$result = $result && mkdir(DATA_TP_ROOT . $tpid . DS .'rendu');
			$result = $result && file_put_contents(DATA_TP_ROOT . $tpid . DS . 'type', $type);
		}
		$this->autoRender = false;
		echo $result;
	}
	
	function compare(){
		$this->autoRender = false;
		$tpid = $this->params['form']['assignment'];
		$mail = $this->params['form']['student_mail'];
		$type = $this->__getTpType($tpid);		
		preg_match("/(.*)@/", $mail, $matches);
		$uploadfile = DATA_TP_ROOT . $tpid . DS . 'rendu' . DS . basename($matches[1]) . '.' . $type;
		$fileok = false;
        if(isset($this->params['form']['webexplorer'])){
            $webpage = $this->Webpage->find('first', array('conditions' => array('User.email ILIKE' => $mail, 'Webpage.name'=> strtolower($this->params['form']['assignment']))));
            if($webpage && file_put_contents($uploadfile, $webpage['Webpage'][$type])){
                $fileok = true;
            }
        }else{
            $fileok = move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile);
        }
		if($fileok){
			if(method_exists($this, '_compare_' . $type)){
				call_user_func(array($this, '_compare_' . $type), $tpid, $type, $uploadfile);
			}else{
				$data->error = base64_encode('no corrector for type ' . $type);
				$data->html = base64_encode('');
				$data->correct = false;
				echo json_encode($data);
			}
		}else{
			$data->error = base64_encode('upload failed or webexplorer page not found!');
			$data->html = base64_encode('');
			$data->correct = false;
			echo json_encode($data);
		}
		$this->Log->set('query', null);
		$this->Log->set('activity', $tpid);
		$this->Log->set('ip', (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));
		$this->Log->set('question_id', null);
		$this->Log->set('user', strtolower($mail));
		$this->Log->save();
	}
    function admin_compare($tpid, $user){
        $this->autoRender = false;
        echo "<p>Result:";
		echo $this->ExcelDiff->compare(DATA_TP_ROOT . $tpid . DS . 'rendu' . DS . $user . '.xlsx',
								  DATA_TP_ROOT . $tpid . DS . 'source' . DS . 'sol.xlsx');
        echo "</p>";
		echo "<hr />";
		echo $this->ExcelDiff->getHtml();
		echo "<hr />";
		echo $this->ExcelDiff->getError();
    }

	function _compare_html($tpid, $type, $userfile){
		$this->Log->set('result', $this->HtmlDiff->compare($userfile, DATA_TP_ROOT . $tpid . DS . 'source' . DS . 'sol.' . $type));
		$this->Log->set('error', $this->HtmlDiff->getError());
		echo $this->HtmlDiff->getJsonResult();
		
	}
	
	function _compare_jpg($tpid, $type, $userfile){
		$this->Log->set('result', $this->JpgDiff->compare($userfile, DATA_TP_ROOT . $tpid . DS . 'source' . DS . 'sol.txt'));
		$this->Log->set('error', $this->JpgDiff->getError());
		echo $this->JpgDiff->getJsonResult();	
	}
	
	function _compare_xlsx($tpid, $type, $userfile){
		$this->Log->set('result', $this->ExcelDiff->compare($userfile, DATA_TP_ROOT . $tpid . DS . 'source' . DS . 'sol.' . $type));
		$this->Log->set('error', $this->ExcelDiff->getError());
		echo $this->ExcelDiff->getJsonResult();
	}
	
	function _compare_xlsx_dev($tpid, $type, $userfile){
		$this->Log->set('result', $this->ExcelDiffDev->compare($userfile, DATA_TP_ROOT . $tpid . DS . 'source' . DS . 'sol.xlsx'));
		$this->Log->set('error', $this->ExcelDiffDev->getError());
		echo $this->ExcelDiffDev->getJsonResult();
	}
	
	//test function
	function admin_css(){
		$this->autoRender = false;
		Configure::write('debug', 2);
		App::import('Vendor', 'csstidy', array('file' => 'csstidy/class.csstidy.php'));
$css_code = <<<CSSCODE
* {margin: 0; padding: 0;}
body {font: 0.8125em Verdana, sans-serif; line-height: 1; color: #333; background: #FFF;}

a {text-decoration: none;}
a.test{
color:red; background-color:#FFffFF
} 
a.test, a.test2 {
    color: blue;
}
a img {border: none;}
a:link, a:visited {color: #555; border-bottom: 1px solid #555;}
html body a:hover {color: #000; background-color: #F4F2E4;
  border-bottom: 1px solid #9A8E51;}
#masthead a, #ish a, #navbar a, #banners a, a.button, #main h1 a, .title a:link, .title a:visited, .byline a:link, .byline a:visited {border-bottom-width: 0;}
#main h1 a:hover, .title a:hover, .byline a:hover {border-bottom-width: 1px;}

h2 {font: 1.5em Georgia, "Times New Roman", serif; letter-spacing: 1px;}
h3 {font: bold 1em Verdana, Arial, sans-serif; letter-spacing: 2px;
  text-transform: uppercase;}
h4 {font: bold 1.1em Georgia, "Times New Roman", serif; letter-spacing: 1px;}

ul, ol {list-style: none;}
blockquote, pre {padding: 0.25em 40px;}
blockquote {font: italic 1.05em Georgia, Times, serif;
  background: url(/pix/quote_wh.gif) 10px 0.75em no-repeat;
  margin: 0.25em 0;}
pre, code {font: 1.05em Courier, monospace;}
pre {line-height: 1.5em;}
pre strong {font-size: 1em; font-weight: bold;}

pre code {font-size: 1em; line-height: 1.5em;} /* handle legacy articles until markup gets a scrubbing */

table {border-bottom: 3px solid #B2B2B2; margin: 0 0 2em;}
caption {padding: 0.75em; font: 1.5em Georgia, Times, serif;
  border: 1px solid #B2B2B2; border-width: 1px 0 2px;
  background: #EEE;}
th, td {padding: 0.5em 1em;
  border: 1px solid #CCC;
  border-width: 0 0 1px 1px;}
th.first, td.first, tbody th {border-left: none;}
thead th {text-transform: uppercase; text-align: left;}
tbody th {width: 20%;}
tfoot {display: none;}

#masthead {position: absolute; z-index: 5; top: 0; left: 22px;}
#masthead a {display: block; background: #81817C; width: 156px;}
#masthead a:hover {background: #000;}
#ish {position: relative; z-index: 10; border-top: 1px solid #666;
  font: bold 10px Arial, sans-serif; letter-spacing: 1px;}
#ish a:link, #ish a:visited {position: absolute; top: -33px; left: 150px;
  width: 65px; height: 52px; padding-top: 13px; text-align: center;
  background: url(/pix/ishbug.gif) top left no-repeat;
  color: #FFF;}
#ish a:hover {background-position: bottom right;}
#ish a em {display: block; margin-top: -0.2em;
  font: 2.33em Georgia, Times, serif; letter-spacing: 0;}

#content .ishinfo {font: 0.9em Verdana, sans-serif;
  text-transform: uppercase; letter-spacing: 0.33em;}
#content .ishinfo b {font: 1.2em Georgia, Times, serif; letter-spacing: 1px;}

#navbar {height: 2.4em;
  padding: 0 0 0 215px;
  background: #FBFAF4;
  border-top: 5px solid #333;
  font: 18px Georgia, Times, serif; overflow: hidden;
  min-width: 750px;}
#navbar li {float: left; padding: 0 23px 0 13px; margin-right: 5px;
  background: url(/pix/diamond-black.gif) 100% 66% no-repeat;}
#navbar li a {display: block; padding: 0.75em 0 0.25em;
  text-transform: uppercase; color: #000;}
#navbar #feed {background: none;}
#navbar a:hover,
  .articles #navbar #articles a,
  .topics #navbar #topics a,
  .about #navbar #about a,
  .contact #navbar #contact a,
  .contribute #navbar #contribute a,
  .feed #navbar #feed a {
 background: url(/pix/navbarlinkbg.gif) top left repeat-x; color: #555;
}

#main {float: left; font-size: 0.88em;
  width: 750px; padding: 1.5em 0 1.5em 210px;
  background: url(/pix/threecolbg.gif) 794px 0 repeat-y;}
#main p {text-align: left; line-height: 1.8em;
  margin: 0 0 1em;}

.column {float: left;}

#content {width: 540px; padding: 0 25px 0 20px;}
#content .title {font: 1.8em Georgia, Times, serif; margin-bottom: 0.5em;}
.title {text-transform: none; letter-spacing: 1px;}
.title a:link, .title a:visited {color: #333;}
.title a:hover {color: #000;}
.byline {font: italic 1.1em Times, serif; letter-spacing: 1px; margin: 0 0 1.5em;}
.byline a:link, .byline a:visited {font: bold 0.85em Verdana, sans-serif;
  text-transform: uppercase; letter-spacing: 2px;
  margin-left: 0.25em;}

#secondary {width: 215px;}
#secondary .title {margin-bottom: 0.25em;}

#choice {border-top: 1px solid #D9D9D9;
  padding: 1.5em 20px;}
#choice h3 {color: #333; font: 0.9em Verdana, sans-serif;
  text-transform: uppercase; letter-spacing: 0.33em;}
#choice .info {font-style: italic; font-size: 0.9em;
  color: #666;}

#sidebar {width: 140px; padding-left: 15px;}
#sidebar h3 {font: 1.5em Georgia, Times, serif; letter-spacing: 0; text-transform: none;
  margin-bottom: 0.25em; color: #333;}

#search {width: 80px;}
#search, #submit {vertical-align: bottom;}

#sidebar div {border-bottom: 1px dashed #B2B2B2; padding: 10px 0.5em;}
#sidebar div.first {padding-top: 0;}
#sidebar li {padding: 0.5em 0 0.5em;}
#sidebar li a:link, #sidebar li a:visited {padding-left: 12px;
  background: url(/pix/diamond-gray.gif) 0 0.4em no-repeat;}
#sidebar p {font-size: 0.85em; margin-top: 0.25em;}

#lucre, #lucre p {margin: 0.5em 0 0;}
#lucre p {text-align: center;}
#lucre p.ads {text-align: left; line-height: 1.5;}
#lucre p a:link, #lucre p a:visited {color: #666;}
#lucre p a:hover {color: #000; border-bottom-width: 1px;}

#sidebar #colophon {border-bottom-width: 0;}
#colophon p {text-transform: uppercase; letter-spacing: 0.25em; text-align: right;
  width: 121px; margin: 0 auto; color: #666;}
#colophon img {background: #333;}
#colophon a:hover img {background: #555;}

#topiclist a, #lucre a, #colophon a {border-bottom-width: 0;}

#footer {clear: both; border: 1px solid #666; border-width: 1px 0;
  margin-bottom: 3em; font-size: 0.85em;
  background: #FBFAF4 url(/pix/pixelstoprose.gif) 20px 50% no-repeat;}
#footer p {margin-left: 200px; padding: 1em 20px; border-left: 1px solid #666;
  background: #FFF;}
#footer a:link, #footer a:visited {border-bottom-width: 0;}
#footer a:hover {border-bottom-width: 1px;}

.issn {font: 0.9em Verdana, sans-serif; text-transform: uppercase; letter-spacing: 0.33em;}
.issn b {font: 1.2em Georgia, Times, serif; letter-spacing: 1px;}

#footer .copyright {padding-left: 25px; background: url(/pix/diamond-gray.gif) 10px 50% no-repeat;}

#search, input[type="text"], input[type="password"], textarea { background: #FBFAF4;
  border: 2px solid; border-color: #999 #D9D9D9 #D9D9D9 #999;}

/* IE5.x/Win hacks */

#main {width: 960px; voice-family: "\"}\""; voice-family: inherit; width: 750px;}
#content {width: 585px; voice-family: "\"}\""; voice-family: inherit; width: 540px;}
#sidebar {width: 155px; voice-family: "\"}\""; voice-family: inherit; width: 140px;}
#ish a:link, #ish a:visited {height: 65px; voice-family: "\"}\""; voice-family: inherit; height: 52px;}

/* IE5/Mac hacks */
/*\*//*/
#navbar {padding-top: 0.75em; height: 1.66em;}
#navbar li a {display: inline;}
/**/

CSSCODE;

	$css = new csstidy();
	$css->set_cfg('remove_bslash', true);
	$css->set_cfg('compress_colors', true);
	$css->set_cfg('compress_font-weight', true);
	$css->set_cfg('lowercase_s', true);
	$css->set_cfg('optimise_shorthands', 1);
	$css->set_cfg('remove_last_;', false);
	$css->set_cfg('case_properties', 1);
	$css->set_cfg('sort_properties', false);
	$css->set_cfg('sort_selectors', false);
	$css->set_cfg('merge_selectors', 2);
	$css->set_cfg('discard_invalid_properties', true);
	$css->set_cfg('css_level', 'CSS2.1');
    $css->set_cfg('preserve_css', false);
    $css->set_cfg('timestamp', false);

	$css->parse($css_code);
	
	echo $css->print->formatted();
	debug($css->css);
	debug($css->template);
	debug($css->tokens);
	debug($css->charset);
	debug($css->import);
	debug($css->namespace);
	

	}
	
	//test function
	function admin_excel(){
		$this->autoRender = false;
		echo $this->ExcelDiff->compare(DATA_TP_ROOT . 'a01' . DS . 'source' . DS . 'test.xls',
								  DATA_TP_ROOT . 'a01' . DS . 'source' . DS . 'sol.xls');
		echo "<hr />";
		echo $this->ExcelDiff->getHtml();
		echo "<hr />";
		echo $this->ExcelDiff->getError();
		
	}
	
	function admin_index(){
		$this->set('tps', $this->_tpList());
	}	
	
	function _tpList(){
		chdir(DATA_TP_ROOT);
		return glob('*', GLOB_ONLYDIR );		
	}
	
	function admin_view($tpid, $name){		
		$this->view = 'Media';
		$type = $this->__getTpType($tpid);
		$tpPath = $this->__validTpPathOrDie($tpid, $name . '.' . $type, 'rendu');		
        $params = array(
              'id' => $name.'.'.$type,
              'name' => 'tp_'. $tpid . '_' . $name,
              'download' => true,
              'extension' => $type,
              'path' => $tpPath
       );
       $this->set($params);
	}
	
	function admin_search($tpid, $query){
		$type = $this->__getTpType($tpid);
		$response = '';
		$files = scandir(DATA_TP_ROOT.$tpid.DS.'rendu'.DS);
        $list  =  array();
        foreach($files as $file){
            //strip extensions
            if(strlen($file) > 3){
                $list[] = strtolower(substr($file, 0, strrpos($file, '.')));
            }
		}
        
        $condition = array("OR" => array(
											'User.matricule ILIKE' => "%$query%",
											'User.first_name ILIKE' => "%$query%",
											'User.last_name ILIKE' => "%$query%",
											'User.email ILIKE' => "%$query%"
										)
                    );
        
        if($type == 'pdf'){
            $condition["User.matricule"] = $list;
        } else{
            $condition["lower(substring(User.email from '(.*)@'))"] = $list;
        }
		$users = $this->User->find('all', array(
										'recursive' => 0,
                                        'limit' => 20,
										'conditions' => $condition
                                        ));
		foreach($users as $user){
            if($type == 'pdf'){
                $filename = $user['User']['matricule'];
            }else{
                $filename = strtolower(substr($user['User']['email'] , 0, strrpos($user['User']['email'], '@')));
            }
            
            $response .= '<a href="#" class="file_' . $type . '" data-file="' . $filename . '">'.$user['User']['first_name'] . ' ' . $user['User']['last_name'] . '(' . $user['User']['matricule'] .")</a>\n";
            if($type == 'xlsx'){
                //display compare
                $response .= "<a href='tp/compare/$tpid/$filename'>[compare]</a>\n";
            }
		}
		echo $response;
		$this->autoRender = false;
	}
	
	function __getTpType($tpid){
		return file_get_contents($this->__validTpPathOrDie($tpid, 'type').'type');
	}
	
	function __validTpPathOrDie($tpid, $filename, $customDir=""){
		$tpid = Sanitize::paranoid($tpid);
		$templatePath = DATA_TP_ROOT . $tpid . DS;
		if($customDir){
			$templatePath .= $customDir . DS;
		}
		if(!file_exists($templatePath.$filename)){
			die("Impossible de trouver le fichier du TP $tpid");
		}
		return $templatePath;
	}
	
	function admin_imsmanifest($action=null){
		if($action == 'download' && $this->data != null){
			$this->autoRender = false;
			$this->IMSManifest->scoURL = 'http://moodle2.unil.ch/hec/info1ere/tp/upload';
			
			$question['title'] = 'Envoyer';
			$question['datafromlms'] = $this->data['tp']; 
			  
			
			$this->IMSManifest->generateIMSManifest($this->data['tp'], $this->data['title'], array($question));
		} else {
			$this->set('tps', $this->_tpList());
		}
	}
		
}
?>
