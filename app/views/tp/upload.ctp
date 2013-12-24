<?php $html->script('ajaxupload',array('inline' => false)); ?>
<?php $html->script('jquery.base64',array('inline' => false)); ?>
<?php $html->scriptStart(array('inline' => false));?>
function noAPIConnectionError(){
	$("#upload_button").after('<div class="error" id="error2" style="display:block;">' + "ATTENTION! La connection avec moodle n'a pas pu être faites, vos résultats ne seront pas enregistrés!<br />Rechargez la page, si l'erreur persiste contactez un assistant.</div>");
	$("#upload_button").remove();		
}
function questionPassed(){
	$("#upload_button").replaceWith('<p><a href="http://hec.unil.ch/info1ere/tp/view/' + doLMSGetValue("cmi.launch_data") + '">consulter votre fichier</a></p>');
	$("#error").before("<h1>Question R&eacute;ussie !</h1>");		
}
	
$(document).ready(function(){			

    function compareComplete (response){
        $("#result").html($.base64Decode(response.html));
        $("#error").html($.base64Decode(response.error));
        if($("#error").html() != ""){
            $("#error").show();
        }
        if(response.correct){
            questionPassed();
        }
        if(scorm_api){
            counter =  doLMSGetValue("cmi.suspend_data");
            counter++;
            doLMSSetValue("cmi.suspend_data", counter);
            if(response.correct){
                doLMSSetValue('cmi.core.score.raw', 1);
                doLMSSetValue('cmi.core.lesson_status','passed');
            }else{
                doLMSSetValue('cmi.core.score.raw', 0);
                doLMSSetValue('cmi.core.lesson_status','failed');
            }
        }
    }
    new AjaxUpload('upload_button', {
        action: '<?php echo $html->url('/tp/compare')?>',
        responseType: 'json',
        autoSubmit: true,
        onSubmit: function(file, extension) {
            $("#result").html('Chargement... <?php echo $html->image('ajax-loader.gif');?>');
            $("#error").html("").hide();
            if(scorm_api){
                this.setData({
                    'student_mail' : doLMSGetValue("cmi.core.student_id"),
                    'student_name' : doLMSGetValue("cmi.core.student_name"),
                    'assignment' : doLMSGetValue("cmi.launch_data")
                });
            }
        },
        onComplete : function(file, response){
           compareComplete(response);
        }		
    });
    
    $('#webexplorer_button').click(function(){
        $.ajax({
          type: 'POST',
          url: '<?php echo $html->url('/tp/compare')?>',
          data:  {'student_mail' : doLMSGetValue("cmi.core.student_id"),
                        'student_name' : doLMSGetValue("cmi.core.student_name"),
                        'assignment' : doLMSGetValue("cmi.launch_data"),
                        'webexplorer': true},
          success: compareComplete,
          dataType: 'json'
        });
    });
});
<?php $html->scriptEnd();?>
<div id="text">
<h1><span>TP</span> Corrector</h1>
<h2>Fichier</h2>
<input type="button" id="upload_button" value="Valider un fichier" />
<h2>Résultat</h2>
<div id="error" class="error"></div>
<div id="result"></div>
</div>