// DON'T FORGET TO MINIFY
// resources/js-mini.bat

var MMSD = MMSD || {};

MMSD.addressSearchVars = {};

$(document).ready(function(){

	MMSD.helperAddressSearch.setDefaultValues();
    $('.address-search-ify').on('focus',function(){
		MMSD.helperAddressSearch.addressSearchIfy($(this));
	});
	MMSD.addressSearchVars.modalTarget.on('shown.bs.modal',function(){
		$('#address-search-number').trigger('focus');
	});
	MMSD.addressSearchVars.modalTarget.on('hidden.bs.modal',function(){
		MMSD.helperAddressSearch.clearAddressSearch();
	});
	$('#address-search-button').on('click',function(){
		MMSD.helperAddressSearch.executeAddressSearch();
	});
	$('.address-search-use-anyway').on('click',function(){
		MMSD.helperAddressSearch.useUnfoundAddress();
	});
	$('#address-search-cancel').on('click',function(){
		MMSD.helperAddressSearch.addressSearchCancel();
	});
});

MMSD.helperAddressSearch = {
    addressSearchIfy: function(ele) {
		MMSD.helperAddressSearch.setDefaultValues();
		MMSD.helperAddressSearch.hideDivs();
        MMSD.addressSearchVars.modalObject.show();
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
		MMSD.helperAddressSearch.hideDivs(true);
        MMSD.addressSearchVars.modalObject.handleUpdate();
		$.when(MMSD.addresses.cleanAddressSearch(enteredAddress))
        .done(
            function(status, data) {
				if (status) {
                    // do MMSD address found stuff
					MMSD.addressSearchVars.resultDiv.children().detach();
					let resultUL = $('<ul>');
					$.each(data.addresses, function(idx, address){
						let resultClass = 'fa-check-circle text-success';
						let resultTitle = 'In MMSD';
						if (address.districtID != '200') {
							resultClass = 'fa-exclamation-triangle text-danger';
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
									let usedAddress = MMSD.helperAddressSearch.useFoundAddress(address);
									if (usedAddress) {
										MMSD.addressSearchVars.modalObject.hide();
									} else {
										alert('Address must be in the MMSD');
									}
								})
							)
						.append(
								$('<span>',{
									'class':'fas ' + resultClass,
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
						.append(MMSD.helperAddressSearch.schoolNamesFromAddress(address))
						.appendTo(resultUL)
						;
                    });
                    MMSD.addressSearchVars.resultDiv.append(resultUL);
					MMSD.addressSearchVars.resultDiv.show();
					MMSD.addressSearchVars.notFoundDiv.show();
					MMSD.addressSearchVars.invalidDiv.hide();
					MMSD.addressSearchVars.modalObject.handleUpdate();
				} else {
					MMSD.helperAddressSearch.hideDivs();
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
				MMSD.helperAddressSearch.hideDivs();
                MMSD.addressSearchVars.modalObject.handleUpdate();
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
		MMSD.addressSearchVars.notFoundDiv.hide();
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
		if (address.kfour) {
			if (address.kfour.k4SchoolCode) {
				$('#'+MMSD.addressSearchVars.fieldsPrefix+'k4-school-codes').val(JSON.stringify([address.kfour.k4SchoolCode]));
			}
			if (address.kfour.k4SchoolName) {
				$('#'+MMSD.addressSearchVars.fieldsPrefix+'k4-school-names').val(JSON.stringify([address.kfour.k4SchoolName]));
			}
			if (address.kfour.k4SchoolID) {
				$('#'+MMSD.addressSearchVars.fieldsPrefix+'k4-school-ids').val(JSON.stringify([address.kfour.k4SchoolID]));
			}
		}
		let schoolInfo = {
			codes: [],
			names: [],
			IDs: []
		};
		let esInfo = {
			codes: [],
			names: [],
			IDs: []
		};
		$.each(address.elementary_boundaries, function(idx, es){
			esInfo.codes.push(es.school.SchoolCode);
			esInfo.names.push(es.school.name);
			esInfo.IDs.push(es.school.schoolID);
			schoolInfo.codes.push(es.school.SchoolCode);
			schoolInfo.names.push(es.school.name);
			schoolInfo.IDs.push(es.school.schoolID);
		});
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'es-school-codes').val(JSON.stringify(esInfo.codes));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'es-school-names').val(JSON.stringify(esInfo.names));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'es-school-ids').val(JSON.stringify(esInfo.IDs));
		let msInfo = {
			codes: [],
			names: [],
			IDs: []
		};
		$.each(address.middle_boundaries, function(idx, ms){
			msInfo.codes.push(ms.school.SchoolCode);
			msInfo.names.push(ms.school.name);
			msInfo.IDs.push(ms.school.schoolID);
			schoolInfo.codes.push(ms.school.SchoolCode);
			schoolInfo.names.push(ms.school.name);
			schoolInfo.IDs.push(ms.school.schoolID);
		});
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'ms-school-codes').val(JSON.stringify(msInfo.codes));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'ms-school-names').val(JSON.stringify(msInfo.names));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'ms-school-ids').val(JSON.stringify(msInfo.IDs));
		let hsInfo = {
			codes: [],
			names: [],
			IDs: []
		};
		$.each(address.high_boundaries, function(idx, hs){
			hsInfo.codes.push(hs.school.SchoolCode);
			hsInfo.names.push(hs.school.name);
			hsInfo.IDs.push(hs.school.schoolID);
			schoolInfo.codes.push(hs.school.SchoolCode);
			schoolInfo.names.push(hs.school.name);
			schoolInfo.IDs.push(hs.school.schoolID);
		});
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'hs-school-codes').val(JSON.stringify(hsInfo.codes));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'hs-school-names').val(JSON.stringify(hsInfo.names));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'hs-school-ids').val(JSON.stringify(hsInfo.IDs));

		$('#'+MMSD.addressSearchVars.fieldsPrefix+'all-school-codes').val(JSON.stringify(schoolInfo.codes));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'all-school-names').val(JSON.stringify(schoolInfo.names));
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'all-school-ids').val(JSON.stringify(schoolInfo.IDs));

		$('#'+MMSD.addressSearchVars.fieldsPrefix+'line1').val(address.line1);
        $('#'+MMSD.addressSearchVars.fieldsPrefix+'line2').val(address.line2);
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'fullAddress').val(address.fullAddress);
		$('#residency-info').show();
		$(MMSD.addressSearchVars.fieldDisplay).trigger('address:ic');
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
				$.each(['k4','es','ms','hs','all'],function(ixl, lvl){
					$.each(['codes','names','ids'],function(ixf, field){
						$('#'+MMSD.addressSearchVars.fieldsPrefix+lvl+'-school-' + field).val('');
					});
				});
                $('#'+MMSD.addressSearchVars.fieldsPrefix+'line1').val(data.line1);
                $('#'+MMSD.addressSearchVars.fieldsPrefix+'line2').val(data.line2);
				let enteredText = data.line1 + ' ' + data.line2;
				$('#'+MMSD.addressSearchVars.fieldsPrefix+'fullAddress').val(enteredText);
                $(MMSD.addressSearchVars.fieldDisplay).val(enteredText);
				$('#residency-info').show();
				MMSD.addressSearchVars.modalObject.hide();
				$(MMSD.addressSearchVars.fieldDisplay).trigger('address:usps');
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
	addressSearchCancel: function() {
		$.each(['k4','es','ms','hs','all'],function(ixl, lvl){
			$.each(['codes','names','ids'],function(ixf, field){
				$('#'+MMSD.addressSearchVars.fieldsPrefix+lvl+'-school-'+field).val('');
			});
		});
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'line1').val('');
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'line2').val('');
		$('#'+MMSD.addressSearchVars.fieldsPrefix+'fullAddress').val('');
		$(MMSD.addressSearchVars.fieldID).val('');
		$(MMSD.addressSearchVars.fieldDisplay).val('');
		MMSD.addressSearchVars.modalObject.hide();
		$(MMSD.addressSearchVars.fieldDisplay).trigger('address:cancel');
	},
	setDefaultValues: function() {
		MMSD.addressSearchVars = {
			'modalTarget': $('#modal-address-search'),
			'fieldDisplay': '#address-textfield',
			'fieldID': '#addressID',
			'inDistrict': false,
			'fieldsPrefix': 'address-',
			'resultDiv': $('#address-search-results-div'),
			'failureDiv': $('#address-search-failure-div'),
			'notFoundDiv': $('#address-search-notfound-div'),
			'invalidDiv': $('#address-search-invalid-div'),
			'number': $('#address-search-number'),
			'prefix': $('#address-search-prefix'),
			'street': $('#address-search-street'),
			'tag': $('#address-search-tag'),
			'apt': $('#address-search-apt'),
			'city': $('#address-search-city'),
			'state': $('#address-search-state'),
			'zip': $('#address-search-zip')
		};
		MMSD.addressSearchVars.modalObject = new bootstrap.Modal(document.getElementById('modal-address-search'));
	},
	hideDivs: function(keepResults = false) {
		MMSD.addressSearchVars.failureDiv.hide();
		MMSD.addressSearchVars.notFoundDiv.hide();
		MMSD.addressSearchVars.invalidDiv.hide();
		if (!keepResults) {
			MMSD.addressSearchVars.resultDiv.hide();
			MMSD.addressSearchVars.resultDiv.children().detach();
		}
	}
};
