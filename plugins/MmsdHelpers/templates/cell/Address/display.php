
<div class="modal fade" id="modal-address-search" tabindex="-1" role="dialog" aria-labelledby="Address-Search-ModalLabel">
    <div class="modal-dialog modal-lg  modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="Address-Search-ModalLabel"><?= __d('mmsd_helpers','Address Finder'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

<?= $this->Form->create(null,[
    'id' => 'address-search-form',
    'class' => 'needs-validation',
    'novalidate' => true,
]); ?>
<?php $this->BsForm->setDefaults([
    'labelClass' => 'font-weight-bold',
    'labelAppendChar' => ':',
]); ?>

<p><b><?= __d('mmsd_helpers','It is very important to use accurate addresses.'); ?></b></p>

<div class="container-fluid">

	<div class="row align-items-start">
		<div class="col-2">
<?= $this->BsForm->input('AddressSearch.number',[
    'label' => __d('mmsd_helpers','Number'),
    'placeholder' => '1234',
]); ?>
		</div>
		<div class="col-2">
<?= $this->BsForm->input('AddressSearch.prefix',[
    'label' => __d('mmsd_helpers','Direction'),
    'type' => 'select',
    'options' => ['E' => 'E', 'N' => 'N', 'S' => 'S', 'W' => 'W',],
    'empty' => true,
]); ?>
		</div>
		<div class="col-4">
<?= $this->BsForm->input('AddressSearch.street',[
    'label' => __d('mmsd_helpers','Street'),
    'placeholder' => 'Main',
]); ?>
		</div>
		<div class="col-2">
<?= $this->BsForm->input('AddressSearch.tag',[
    'label' => __d('mmsd_helpers','Type'),
    'type' => 'select',
    'options' => $tagList,
    'empty' => true,
]); ?>
		</div>
		<div class="col-2">
<?= $this->BsForm->input('AddressSearch.apt',[
    'label' => __d('mmsd_helpers','Apt'),
]); ?>
		</div>
	</div>
	<div class="row align-items-center">
		<div class="col-4">
<?= $this->BsForm->input('AddressSearch.city',[
    'label' => __d('mmsd_helpers','City'),
    'value' => 'Madison',
]); ?>
		</div>
		<div class="col-3">
<?= $this->BsForm->input('AddressSearch.state',[
    'label' => __d('mmsd_helpers','State'),
    'type' => 'select',
    'options' => $stateList,
    'selected' => 'WI',
    'empty' => true,
]); ?>
		</div>
		<div class="col-2">
<?= $this->BsForm->input('AddressSearch.zip',[
    'label' => __d('mmsd_helpers','Zip code'),
    'invalidMessage' => [
        'contents' => __d('mmsd_helpers','Required field'),
    ],
]); ?>
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
<p><b><span class="mock-link address-search-use-anyway"><?= __d('mmsd_helpers','We are unable to find that address. Use it anyway?'); ?></span></b></p>
</div>

<div id="address-search-notfound-div" style="display: none;">
<p><b>
<?php if ($allowEnteredAddress): ?>
    <span class="mock-link address-search-use-anyway">
<?php endif; ?>
    <?php if (!empty($notFoundMessage)): ?>
        <?= $notFoundMessage ?>
    <?php else: ?>
        <?= __d('mmsd_helpers','Use my address as entered'); ?>
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

