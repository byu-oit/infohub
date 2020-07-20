<?php $this->Html->css('swagger', null, ['inline' => false]); ?>

<div class="innerLower">
	<?= $this->Form->create(null, ['type' => 'file']) ?>
		<?= $this->Form->input('url', ['label' => 'URL', 'class' => 'noPlaceHolder']) ?>
		<strong>OR</strong>
		<?= $this->Form->input('swag', ['label' => 'Swagger file', 'type' => 'file', 'class' => 'noPlaceHolder']) ?>
		<?= $this->Form->submit('Submit') ?>
	<?= $this->Form->end() ?>
	<?php if(strpos($_SERVER['HTTP_HOST'],'dev') === false) : ?>
				<a href="https://support.byu.edu/ih?id=swagger_import">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a> 
			<?php else : ?>
				<a href="https://support-test.byu.edu/ih?id=swagger_import">
					<span>
							<input type="button" value="Service Portal IH Beta">
					</span>
				</a>
			<?php endif; ?>
</div>
