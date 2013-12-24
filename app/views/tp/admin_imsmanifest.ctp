<?php
echo $form->create(false, array('url'=>'/admin/tp/imsmanifest/download'));
echo $form->input('tp', array('label'=>'tp', 'options'=>array_combine($tps, $tps)));
echo $form->input('title', array('label'=>'title'));
echo $form->end('Generate ZIP');