<?php
$html->script('codemirror/codemirror',array('inline' => false));
$html->scriptStart(array('inline' => false));
?>
var editor;
 $(document).ready(function() {
	editor = new CodeMirror(CodeMirror.replace("html"), {
	  path: "../js/codemirror/",
	  parserfile: "parsexml.js",
	  stylesheet: "../css/codemirror/xmlcolors.css",
	  readOnly: true,
	  lineNumbers: true,
	  height: '800px'
	});
	$('a.file_html').live('click', getHtml);
	$('a.file_pdf').live('click', getPdf);
	$('a.file_xls').live('click', getXls);
    $('a.file_xlsx').live('click', getXls);
});

	function lookup() {
		var inputString = $("#inputString")[0].value;
		if(inputString.length == 0) {
			// Hide the suggestion box.
			$('#suggestions').hide();
		} else {
			$.post("<?php echo $html->url("/admin/tp/search");?>/" + $("#tp")[0].value + "/" + inputString, function(data){
				$('#suggestions').show();
				if(data.length >0) {					
					$('#autoSuggestionsList').html(data);
				}else{
					$('#autoSuggestionsList').html("not found");
				}
			});
		}
	} // lookup
	
	function admin_view_url(a){
		name = $(a).attr('data-file');
		return '<?php echo $html->url("/admin/tp/view");?>/' + $("#tp").val() + '/' + name;
	}
	
	function getHtml() {
		$("#pdf").hide().empty();
		$(editor.wrapping).show();
		$('#name').text("Loading...");
		editor.setCode("");
		$.post(admin_view_url(this), function(data){
			if(data.length >0) {
				$('#name').text(name);
				editor.setCode(data);
			}else{
				$('#name').text("error empty");
			}
		});
	}
	function getPdf(){
		$(editor.wrapping).hide();
		$("#pdf").show();
        $(this).attr('href', admin_view_url(this));
		return true;		
	}
	function getXls(){
		$(this).attr('href', admin_view_url(this));
		return true;		
	}
<?php
$html->scriptEnd();

?>
    <style type="text/css"> 
      .CodeMirror-line-numbers {
        width: 2.2em;
        color: #aaa;
        background-color: #eee;
        text-align: right;
        padding-right: .3em;
        font-size: 10pt;
        font-family: monospace;
        padding-top: .4em;
      }
    </style> 
	<div>
		<select id="tp" onchange="lookup();">
			<?php foreach($tps as $tp):?>
			<option value="<?php echo $tp;?>"><?php echo $tp;?></option>
			<?php endforeach; ?>
		</select>
		<input type="text" size="30" value="" id="inputString" onkeyup="lookup();" />
	</div>
	
	<div class="suggestionsBox" id="suggestions" style="display: none;">
		<div class="suggestionList" id="autoSuggestionsList">
			&nbsp;
		</div>
		<br style="clear:both"/>
	</div>
	<h2 id="name"></h2>
	<div id="html">
	</div>
	<div id="pdf">
	</div>