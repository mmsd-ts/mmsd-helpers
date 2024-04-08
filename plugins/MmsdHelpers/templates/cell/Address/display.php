<div class="modal fade" id="modal-address-search" tabindex="-1" role="dialog" aria-labelledby="Address-Search-ModalLabel">
    <div class="modal-dialog modal-lg  modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="Address-Search-ModalLabel"><?= __d('mmsd_helpers','Address Finder'); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

<?= $this->Form->create(null,[
    'id' => 'address-search-form',
    'class' => 'needs-validation',
    'novalidate' => true,
]); ?>

<p><b><?= __d('mmsd_helpers','It is very important to use accurate addresses.'); ?></b></p>

<div class="container-fluid">

	<div class="row align-items-start">
		<div class="col-2">
            <?= $this->Form->label('address-search-number',__d('mmsd_helpers','Number'),[
                'id' => 'label-address-search-number',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->text('AddressSearch.number',[
                'id' => 'address-search-number',
                'class' => 'form-control',
                'placeholder' => '1234',
            ]); ?>
		</div>
		<div class="col-2">
            <?= $this->Form->label('address-search-prefix',__d('mmsd_helpers','Direction'),[
                'id' => 'label-address-search-prefix',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->select('AddressSearch.prefix',
                ['E' => 'E', 'N' => 'N', 'S' => 'S', 'W' => 'W',],
                [
                    'id' => 'address-search-prefix',
                    'class' => 'form-select',
                    'empty' => true,
                ]
            ); ?>
		</div>
		<div class="col-4">
            <?= $this->Form->label('address-search-street',__d('mmsd_helpers','Street'),[
                'id' => 'label-address-search-street',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->text('AddressSearch.street',[
                'id' => 'address-search-street',
                'class' => 'form-control',
                'placeholder' => 'Main',
            ]); ?>
		</div>
		<div class="col-2">
            <?= $this->Form->label('address-tag',__d('mmsd_helpers','Type'),[
                'id' => 'label-address-tag',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->select('Address.tag',$tagList,[
                'id' => 'address-tag',
                'class' => 'form-select',
                'empty' => true,
            ]); ?>
		</div>
		<div class="col-2">
            <?= $this->Form->label('address-search-apt',__d('mmsd_helpers','Apt'),[
                'id' => 'label-address-search-apt',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->text('AddressSearch.apt',[
                'id' => 'address-search-apt',
                'class' => 'form-control',
            ]); ?>
		</div>
	</div>
	<div class="row align-items-center">
		<div class="col-4">
            <?= $this->Form->label('address-search-city',__d('mmsd_helpers','City'),[
                'id' => 'label-address-search-city',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->text('AddressSearch.city',[
                'id' => 'address-search-city',
                'class' => 'form-control',
                'value' => 'Madison',
            ]); ?>
		</div>
		<div class="col-3">
            <?= $this->Form->label('address-search-state',__d('mmsd_helpers','State'),[
                'id' => 'label-address-search-state',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->select('AddressSearch.state',$stateList,[
                'id' => 'address-search-state',
                'class' => 'form-select',
                'value' => 'WI',
                'empty' => true,
            ]); ?>
		</div>
		<div class="col-2">
            <?= $this->Form->label('address-search-zip',__d('mmsd_helpers','Zip code'),[
                'id' => 'label-address-search-zip',
                'class' => 'form-label fw-bold',
            ]); ?>
            <?= $this->Form->text('AddressSearch.zip',[
                'id' => 'address-search-zip',
                'class' => 'form-control',
                'required' => true,
            ]); ?>
            <div class="invalid-feedback"><?= __d('mmsd_helpers','Required field') ?></div>
		</div>
		<div class="col-sm-3">
<button type="button" class="btn btn-primary" id="address-search-button">
	<?= __d('mmsd_helpers','Find Address'); ?>
	<span class="fas fa-search" title="" aria-hidden="true"></span>
</button>
		</div>
	</div>

<div id="address-search-results-div" style="display: none;"></div>

<div id="address-search-failure-div" style="display: none;">
<p><b>
<?php if ($allowEnteredAddress): ?>
    <span class="mock-link address-search-use-anyway">
<?php endif; ?>
<?php if (!empty($notFoundMessage)): ?>
    <?= $notFoundMessage ?>
<?php else: ?>
    <?php if ($allowEnteredAddress): ?>
        <?= __d('mmsd_helpers','We are unable to find that address. Use it anyway?'); ?>
    <?php else: ?>
        <?= __d('mmsd_helpers','We are unable to find that address.'); ?>
    <?php endif; ?>
<?php endif; ?>
<?php if ($allowEnteredAddress): ?>
    </span>
<?php endif; ?>
</b></p>
</div>

<div id="address-search-notfound-div" style="display: none;">
<p><b>
<?php if ($allowEnteredAddress): ?>
    <span class="mock-link address-search-use-anyway">
<?php endif; ?>
<?php if (!empty($notFoundMessage)): ?>
    <?= $notFoundMessage ?>
<?php else: ?>
    <?php if ($allowEnteredAddress): ?>
        <?= __d('mmsd_helpers','Use my address as entered'); ?>
    <?php else: ?>
        <?= __d('mmsd_helpers','We are unable to find that address.'); ?>
    <?php endif; ?>
<?php endif; ?>
<?php if ($allowEnteredAddress): ?>
    </span>
<?php endif; ?>
</b></p>
</div>

<div id="address-search-invalid-div" style="display: none;">
<p><b class="text-danger"><?= __d('mmsd_helpers','That is not a valid address'); ?></b></p>
</div>

</div>

<?= $this->Form->end(); ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="address-search-cancel"><?= __d('mmsd_helpers','Cancel'); ?></button>
            </div>
        </div>
    </div>
</div>

