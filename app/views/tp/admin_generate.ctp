<div class="assignments form">
<?php echo $this->Form->create(false);?>
	<fieldset>
 		<legend><?php __('Generate custom PDF'); ?></legend>
		<select id="tpid" name="data[tpid]">
			<?php foreach($tps as $tp):?>
			<option value="<?php echo $tp;?>"><?php echo $tp;?></option>
			<?php endforeach; ?>
		</select>
	<?php
		echo $this->Form->input('matricule', array( 'type'=>'text'));
		echo $this->Form->input('full_name', array( 'type'=>'text'));
	?>
	</fieldset>
<?php echo $this->Form->end(__('Generate', true));?>
</div>