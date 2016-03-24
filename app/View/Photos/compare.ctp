<div class="innerLower">
    <table>
        <thead>
            <tr>
                <th>BYU</th>
                <th>Collibra</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><img src="<?= $this->Html->url(['action' => 'view', $netId]) ?>"></td>
                <td><img src="<?= $this->Html->url(['action' => 'cview', $netId]) ?>"></td>
            </tr>
        </tbody>
    </table>
    <?= $this->Form->create() ?>
    <?= $this->Form->submit('Copy BYU to Collibra') ?>
    <?= $this->Form->end() ?>
</div>