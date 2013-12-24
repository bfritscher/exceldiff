<div class="view">
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>>SQL</dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link('Exercices', '/admin/assignments'); ?><br />
			<?php echo $this->Html->link('Tp SQL', '/admin/tps'); ?><br />
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>>WEB</dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link('WebExplorer admin shared', '/admin/webexplorer'); ?><br />
			<?php echo $this->Html->link('WebExplorer per user', '/webexplorer'); ?><br />
      <?php echo $this->Html->link('WebExplorer TP rendu', '/admin/webexplorer/rendu'); ?><br />
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>>TP</dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
      <?php echo $this->Html->link('/admin/tp', '/admin/tp'); ?><br />
			<?php echo $this->Html->link('/admin/tp/generate', '/admin/tp/generate'); ?> create custom pdf file<br/>			
			<?php echo $this->Html->link('/admin/tp/imsmanifest', '/admin/tp/imsmanifest'); ?><br/>			
			/admin/tp/create/tpid/type to make new folder (html, pdf, xls)<br/>
			/tp/generate/tpid -> tpid/source/template_scan.pdf + user id codebar<br/>
			/tp/view/tpid -> user get his upload
			&nbsp;
		</dd>
	</dl>
</div>