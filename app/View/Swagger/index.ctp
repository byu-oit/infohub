<div class="innerLower">
	<?= $this->Form->create(null, ['type' => 'file']) ?>
		<?= $this->Form->input('url', ['label' => 'URL', 'class' => 'noPlaceHolder']) ?>
		<strong>OR</strong>
		<?= $this->Form->input('swag', ['label' => 'Swagger file', 'type' => 'file', 'class' => 'noPlaceHolder']) ?>
		<?= $this->Form->submit('Submit') ?>
	<?= $this->Form->end() ?>
</div>
