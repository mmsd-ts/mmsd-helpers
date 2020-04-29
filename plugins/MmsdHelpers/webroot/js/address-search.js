var MMSD = MMSD || {};

MMSD.addressSearchVars = {};

$(document).ready(function(){

	MMSD.thisApp.setDefaultValues();
    $('.address-search-ify').on('focus',function(){
		MMSD.thisApp.addressSearchIfy($(this));
	});
	MMSD.addressSearchVars.modal.on('shown.bs.modal',function(){
		$('#addresssearch-number').trigger('focus');
	});
	MMSD.addressSearchVars.modal.on('hidden.bs.modal',function(){
		MMSD.thisApp.clearAddressSearch();
	});
	$('#address-search-button').on('click',function(){
		MMSD.thisApp.executeAddressSearch();
	});
	$('.address-search-use-anyway').on('click',function(){
		MMSD.thisApp.useUnfoundAddress();
	});

});

MMSD.thisApp = {
    addressSearchIfy: function(ele) {
		MMSD.thisApp.setDefaultValues();
		MMSD.thisApp.hideDivs();
        MMSD.addressSearchVars.modal.modal();
        if (ele.data('address_search_field_display') != undefined) { MMSD.addressSearchVars.fieldDisplay = ele.data('address_search_field_display'); }
		if (ele.data('address_search_field_id') != undefined) { MMSD.addressSearchVars.fieldID = ele.data('address_search_field_id'); }
		if (ele.data('address_search_in_district') != undefined) { MMSD.addressSearchVars.inDistrict = ele.data('address_search_in_district'); }
		if (ele.data('address_search_fields_prefix') != undefined) { MMSD.addressSearchVars.fieldsPrefix = ele.data('address_search_fields_prefix'); }
	},
	executeAddressSearch: function() {
		$('#address-search-button').blur();

		let enteredAddress = {
			'number': MMSD.addressSearchVars.number.val(),
			'prefix': MMSD.addressSearchVars.prefix.val(),
			'street': MMSD.addressSearchVars.street.val(),
			'tag': MMSD.addressSearchVars.tag.val(),
			'apt': MMSD.addressSearchVars.apt.val(),
			'city': MMSD.addressSearchVars.city.val(),
			'state': MMSD.addressSearchVars.state.val(),
			'zip': MMSD.addressSearchVars.zip.val()
		};
		
		MMSD.addressSearchVars.resultDiv.children().detach();
		MMSD.addressSearchVars.resultDiv.append(
			$('<div>')
				.addClass('text-center')
				.append(
					$('<img>')
						.attr({
							'src': '/_resources/img/spinners/small-blue.gif'
						})
				)
		);
		MMSD.addressSearchVars.resultDiv.show();
		MMSD.thisApp.hideDivs(true);
        MMSD.addressSearchVars.modal.modal('handleUpdate');
		$.when(MMSD.addresses.cleanAddressSearch(enteredAddress))
        .done(
            function(status, data) {
				if (status) {
                    // do MMSD address found stuff
					MMSD.addressSearchVars.resultDiv.children().detach();
					let resultUL = $('<ul>');
					$.each(data.addresses, function(idx, address){
						let resultClass = 'oi-circle-check text-success';
						let resultTitle = 'In MMSD';
						if (address.districtID != '200') {
							resultClass = 'oi-warning text-danger';
							resultTitle = 'Out of MMSD';
						}
						$('<li>')
						.append(
							$('<span>')
								.attr({
									'class': 'mock-link font-weight-bold'
								})
								.text(address.fullAddress + ' ')
								.on('click',function(){
									let usedAddress = MMSD.thisApp.useFoundAddress(address);
									if (usedAddress) {
										MMSD.addressSearchVars.modal.modal('hide');
									} else {
										alert('Address must be in the MMSD');
									}
								})
							)
						.append(
								$('<span>',{
									'class':'oi ' + resultClass,
									'aria-hidden':'true',
									'title':resultTitle
								})
							)
						.append(
							$('<span>',{
								'class':'sr-only',
								'text':resultTitle
							})
						)
						.append('<br>')
						.append(MMSD.thisApp.schoolNamesFromAddress(address))
						.appendTo(resultUL)
						;
                    });
                    MMSD.addressSearchVars.resultDiv.append(resultUL);
					MMSD.addressSearchVars.resultDiv.show();
					MMSD.addressSearchVars.ignoreDiv.show();
					MMSD.addressSearchVars.invalidDiv.hide();
					MMSD.addressSearchVars.modal.modal('handleUpdate');
				} else {
					MMSD.thisApp.hideDivs();
					if (data == undefined) {
						// do MMSD no address found stuff
						MMSD.addressSearchVars.failureDiv.show();
					} else {
						MMSD.addressSearchVars.invalidDiv.show();
					}
				}
            }
        )
        .fail(
            function(textStatus, errorThrown) {
				MMSD.main.errorAlert(textStatus,errorThrown);
				MMSD.thisApp.hideDivs();
                MMSD.addressSearchVars.modal.modal('handleUpdate');
			}
        )
        ;
	},
	clearAddressSearch: function() {
		MMSD.addressSearchVars.number.val('');
		MMSD.addressSearchVars.prefix.val('');
		MMSD.addressSearchVars.street.val('');
		MMSD.addressSearchVars.tag.val('');
		MMSD.addressSearchVars.apt.val('');
		MMSD.addressSearchVars.city.val('Madison');
		MMSD.addressSearchVars.state.val('WI');
		MMSD.addressSearchVars.zip.val('');
		MMSD.addressSearchVars.resultDiv.hide();
        MMSD.addressSearchVars.failureDiv.hide();
        MMSD.addressSearchVars.ignoreDiv.hide();
        MMSD.addressSearchVars.invalidDiv.hide();
	},
	schoolNamesFromAddress: function(address) {
		let names = [];
		let k4Name = (address.kfour) ? address.kfour.k4SchoolName : '';
		$.each(address.elementary_boundaries, function(idx, es){
			names.push(es.school.name);
		});
		$.each(address.middle_boundaries, function(idx, ms){
			names.push(ms.school.name);
		});
		$.each(address.high_boundaries, function(idx, hs){
			names.push(hs.school.name);
		});
		if (names.length > 0) {
			let namesString = names.join(', ');
			let displayString = (MMSD.properties.getDocumentLanguage() == 'es') ? 'Escuelas' : 'Schools';
			displayString += ': ' + namesString ;
			if (k4Name.length > 0) {
				displayString += '; K4: ' + k4Name;
			}
			return $('<span>')
				.text(displayString)
			;
		} else {
			return null;
		}
	},
	useFoundAddress: function(address) {
		if ((MMSD.addressSearchVars.inDistrict) && (address.districtID != '200')) {
			return false;
		}
		$(MMSD.addressSearchVars.fieldDisplay).val(address.fullAddress);
		$(MMSD.addressSearchVars.fieldID).val(address.id);
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'line1').val('');
        $('#'+MMSD.addressSearchVars.fieldsPrefix+'line2').val('');
		$('#residency-info').show();
		return true;
	},
	useUnfoundAddress: function() {
		let enteredAddress = {
			'number': MMSD.addressSearchVars.number.val(),
			'prefix': MMSD.addressSearchVars.prefix.val(),
			'street': MMSD.addressSearchVars.street.val(),
			'tag': MMSD.addressSearchVars.tag.val(),
			'apt': MMSD.addressSearchVars.apt.val(),
			'city': MMSD.addressSearchVars.city.val(),
			'state': MMSD.addressSearchVars.state.val(),
			'zip': MMSD.addressSearchVars.zip.val()
		};
		
		$(MMSD.addressSearchVars.fieldID).val('');

		MMSD.addresses.uspsAddressSearch(enteredAddress)
        .done(function(status, data) {
            if (status) {
                // Do address found stuff
                $('#'+MMSD.addressSearchVars.fieldsPrefix+'line1').val(data.line1);
                $('#'+MMSD.addressSearchVars.fieldsPrefix+'line2').val(data.line2);
				let enteredText = data.line1 + ' ' + data.line2;
                $(MMSD.addressSearchVars.fieldDisplay).val(enteredText);
				$('#residency-info').show();
				MMSD.addressSearchVars.modal.modal('hide');
            } else {
                // Do error and/or no address stuff
				MMSD.addressSearchVars.failureDiv.hide();
				MMSD.addressSearchVars.invalidDiv.show();
            }
        })
        .fail(function(textStatus, errorThrown) {
            MMSD.main.errorAlert(textStatus,errorThrown);
        })
        ;
		return true;
	},
	setDefaultValues: function() {
		MMSD.addressSearchVars = {
			'modal': $('#modal-address-search'),
			'fieldDisplay': '#address-textfield',
			'fieldID': '#addressID',
			'inDistrict': false,
			'fieldsPrefix': 'address-',
			'resultDiv': $('#address-search-results-div'),
			'failureDiv': $('#address-search-failure-div'),
			'ignoreDiv': $('#address-search-ignore-div'),
			'invalidDiv': $('#address-search-invalid-div'),
			'number': $('#addresssearch-number'),
			'prefix': $('#addresssearch-prefix'),
			'street': $('#addresssearch-street'),
			'tag': $('#addresssearch-tag'),
			'apt': $('#addresssearch-apt'),
			'city': $('#addresssearch-city'),
			'state': $('#addresssearch-state'),
			'zip': $('#addresssearch-zip')
		};
	},
	hideDivs: function(keepResults = false){
		MMSD.addressSearchVars.failureDiv.hide();
		MMSD.addressSearchVars.ignoreDiv.hide();
		MMSD.addressSearchVars.invalidDiv.hide();
		if (!keepResults) {
			MMSD.addressSearchVars.resultDiv.hide();
			MMSD.addressSearchVars.resultDiv.children().detach();
		}
	}
};
