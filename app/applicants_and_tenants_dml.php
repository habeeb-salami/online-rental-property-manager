<?php

// Data functions (insert, update, delete, form) for table applicants_and_tenants

// This script and data application were generated by AppGini 22.12
// Download AppGini for free from https://bigprof.com/appgini/download/

function applicants_and_tenants_insert(&$error_message = '') {
	global $Translation;

	// mm: can member insert record?
	$arrPerm = getTablePermissions('applicants_and_tenants');
	if(!$arrPerm['insert']) return false;

	$data = [
		'last_name' => Request::val('last_name', ''),
		'first_name' => Request::val('first_name', ''),
		'email' => Request::val('email', ''),
		'phone' => Request::val('phone', ''),
		'birth_date' => Request::dateComponents('birth_date', ''),
		'driver_license_number' => Request::val('driver_license_number', ''),
		'monthly_gross_pay' => Request::val('monthly_gross_pay', ''),
		'additional_income' => Request::val('additional_income', ''),
		'assets' => Request::val('assets', ''),
		'status' => Request::val('status', 'Applicant'),
	];

	if($data['status'] === '') {
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Status': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">' . $Translation['< back'] . '</a></div>';
		exit;
	}

	// hook: applicants_and_tenants_before_insert
	if(function_exists('applicants_and_tenants_before_insert')) {
		$args = [];
		if(!applicants_and_tenants_before_insert($data, getMemberInfo(), $args)) {
			if(isset($args['error_message'])) $error_message = $args['error_message'];
			return false;
		}
	}

	$error = '';
	// set empty fields to NULL
	$data = array_map(function($v) { return ($v === '' ? NULL : $v); }, $data);
	insert('applicants_and_tenants', backtick_keys_once($data), $error);
	if($error)
		die("{$error}<br><a href=\"#\" onclick=\"history.go(-1);\">{$Translation['< back']}</a>");

	$recID = db_insert_id(db_link());

	update_calc_fields('applicants_and_tenants', $recID, calculated_fields()['applicants_and_tenants']);

	// hook: applicants_and_tenants_after_insert
	if(function_exists('applicants_and_tenants_after_insert')) {
		$res = sql("SELECT * FROM `applicants_and_tenants` WHERE `id`='" . makeSafe($recID, false) . "' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)) {
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = makeSafe($recID, false);
		$args=[];
		if(!applicants_and_tenants_after_insert($data, getMemberInfo(), $args)) { return $recID; }
	}

	// mm: save ownership data
	set_record_owner('applicants_and_tenants', $recID, getLoggedMemberID());

	// if this record is a copy of another record, copy children if applicable
	if(strlen(Request::val('SelectedID'))) applicants_and_tenants_copy_children($recID, Request::val('SelectedID'));

	return $recID;
}

function applicants_and_tenants_copy_children($destination_id, $source_id) {
	global $Translation;
	$requests = []; // array of curl handlers for launching insert requests
	$eo = ['silentErrors' => true];
	$safe_sid = makeSafe($source_id);

	// launch requests, asynchronously
	curl_batch($requests);
}

function applicants_and_tenants_delete($selected_id, $AllowDeleteOfParents = false, $skipChecks = false) {
	// insure referential integrity ...
	global $Translation;
	$selected_id = makeSafe($selected_id);

	// mm: can member delete record?
	if(!check_record_permission('applicants_and_tenants', $selected_id, 'delete')) {
		return $Translation['You don\'t have enough permissions to delete this record'];
	}

	// hook: applicants_and_tenants_before_delete
	if(function_exists('applicants_and_tenants_before_delete')) {
		$args = [];
		if(!applicants_and_tenants_before_delete($selected_id, $skipChecks, getMemberInfo(), $args))
			return $Translation['Couldn\'t delete this record'] . (
				!empty($args['error_message']) ?
					'<div class="text-bold">' . strip_tags($args['error_message']) . '</div>'
					: '' 
			);
	}

	// child table: applications_leases
	$res = sql("SELECT `id` FROM `applicants_and_tenants` WHERE `id`='{$selected_id}'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("SELECT COUNT(1) FROM `applications_leases` WHERE `tenants`='" . makeSafe($id[0]) . "'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'applications_leases', $RetMsg);
		return $RetMsg;
	} elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation['confirm delete'];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'applications_leases', $RetMsg);
		$RetMsg = str_replace('<Delete>', '<input type="button" class="btn btn-danger" value="' . html_attr($Translation['yes']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '&delete_x=1&confirmed=1&csrf_token=' . urlencode(csrf_token(false, true)) . '\';">', $RetMsg);
		$RetMsg = str_replace('<Cancel>', '<input type="button" class="btn btn-success" value="' . html_attr($Translation[ 'no']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '\';">', $RetMsg);
		return $RetMsg;
	}

	// child table: residence_and_rental_history
	$res = sql("SELECT `id` FROM `applicants_and_tenants` WHERE `id`='{$selected_id}'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("SELECT COUNT(1) FROM `residence_and_rental_history` WHERE `tenant`='" . makeSafe($id[0]) . "'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'residence_and_rental_history', $RetMsg);
		return $RetMsg;
	} elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation['confirm delete'];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'residence_and_rental_history', $RetMsg);
		$RetMsg = str_replace('<Delete>', '<input type="button" class="btn btn-danger" value="' . html_attr($Translation['yes']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '&delete_x=1&confirmed=1&csrf_token=' . urlencode(csrf_token(false, true)) . '\';">', $RetMsg);
		$RetMsg = str_replace('<Cancel>', '<input type="button" class="btn btn-success" value="' . html_attr($Translation[ 'no']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '\';">', $RetMsg);
		return $RetMsg;
	}

	// child table: employment_and_income_history
	$res = sql("SELECT `id` FROM `applicants_and_tenants` WHERE `id`='{$selected_id}'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("SELECT COUNT(1) FROM `employment_and_income_history` WHERE `tenant`='" . makeSafe($id[0]) . "'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'employment_and_income_history', $RetMsg);
		return $RetMsg;
	} elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation['confirm delete'];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'employment_and_income_history', $RetMsg);
		$RetMsg = str_replace('<Delete>', '<input type="button" class="btn btn-danger" value="' . html_attr($Translation['yes']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '&delete_x=1&confirmed=1&csrf_token=' . urlencode(csrf_token(false, true)) . '\';">', $RetMsg);
		$RetMsg = str_replace('<Cancel>', '<input type="button" class="btn btn-success" value="' . html_attr($Translation[ 'no']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '\';">', $RetMsg);
		return $RetMsg;
	}

	// child table: references
	$res = sql("SELECT `id` FROM `applicants_and_tenants` WHERE `id`='{$selected_id}'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("SELECT COUNT(1) FROM `references` WHERE `tenant`='" . makeSafe($id[0]) . "'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'references', $RetMsg);
		return $RetMsg;
	} elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation['confirm delete'];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'references', $RetMsg);
		$RetMsg = str_replace('<Delete>', '<input type="button" class="btn btn-danger" value="' . html_attr($Translation['yes']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '&delete_x=1&confirmed=1&csrf_token=' . urlencode(csrf_token(false, true)) . '\';">', $RetMsg);
		$RetMsg = str_replace('<Cancel>', '<input type="button" class="btn btn-success" value="' . html_attr($Translation[ 'no']) . '" onClick="window.location = \'applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . '\';">', $RetMsg);
		return $RetMsg;
	}

	sql("DELETE FROM `applicants_and_tenants` WHERE `id`='{$selected_id}'", $eo);

	// hook: applicants_and_tenants_after_delete
	if(function_exists('applicants_and_tenants_after_delete')) {
		$args = [];
		applicants_and_tenants_after_delete($selected_id, getMemberInfo(), $args);
	}

	// mm: delete ownership data
	sql("DELETE FROM `membership_userrecords` WHERE `tableName`='applicants_and_tenants' AND `pkValue`='{$selected_id}'", $eo);
}

function applicants_and_tenants_update(&$selected_id, &$error_message = '') {
	global $Translation;

	// mm: can member edit record?
	if(!check_record_permission('applicants_and_tenants', $selected_id, 'edit')) return false;

	$data = [
		'last_name' => Request::val('last_name', ''),
		'first_name' => Request::val('first_name', ''),
		'email' => Request::val('email', ''),
		'phone' => Request::val('phone', ''),
		'birth_date' => Request::dateComponents('birth_date', ''),
		'driver_license_number' => Request::val('driver_license_number', ''),
		'monthly_gross_pay' => Request::val('monthly_gross_pay', ''),
		'additional_income' => Request::val('additional_income', ''),
		'assets' => Request::val('assets', ''),
		'status' => Request::val('status', ''),
	];

	if($data['status'] === '') {
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Status': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">' . $Translation['< back'] . '</a></div>';
		exit;
	}
	// get existing values
	$old_data = getRecord('applicants_and_tenants', $selected_id);
	if(is_array($old_data)) {
		$old_data = array_map('makeSafe', $old_data);
		$old_data['selectedID'] = makeSafe($selected_id);
	}

	$data['selectedID'] = makeSafe($selected_id);

	// hook: applicants_and_tenants_before_update
	if(function_exists('applicants_and_tenants_before_update')) {
		$args = ['old_data' => $old_data];
		if(!applicants_and_tenants_before_update($data, getMemberInfo(), $args)) {
			if(isset($args['error_message'])) $error_message = $args['error_message'];
			return false;
		}
	}

	$set = $data; unset($set['selectedID']);
	foreach ($set as $field => $value) {
		$set[$field] = ($value !== '' && $value !== NULL) ? $value : NULL;
	}

	if(!update(
		'applicants_and_tenants', 
		backtick_keys_once($set), 
		['`id`' => $selected_id], 
		$error_message
	)) {
		echo $error_message;
		echo '<a href="applicants_and_tenants_view.php?SelectedID=' . urlencode($selected_id) . "\">{$Translation['< back']}</a>";
		exit;
	}


	$eo = ['silentErrors' => true];

	update_calc_fields('applicants_and_tenants', $data['selectedID'], calculated_fields()['applicants_and_tenants']);

	// hook: applicants_and_tenants_after_update
	if(function_exists('applicants_and_tenants_after_update')) {
		$res = sql("SELECT * FROM `applicants_and_tenants` WHERE `id`='{$data['selectedID']}' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)) $data = array_map('makeSafe', $row);

		$data['selectedID'] = $data['id'];
		$args = ['old_data' => $old_data];
		if(!applicants_and_tenants_after_update($data, getMemberInfo(), $args)) return;
	}

	// mm: update ownership data
	sql("UPDATE `membership_userrecords` SET `dateUpdated`='" . time() . "' WHERE `tableName`='applicants_and_tenants' AND `pkValue`='" . makeSafe($selected_id) . "'", $eo);
}

function applicants_and_tenants_form($selected_id = '', $AllowUpdate = 1, $AllowInsert = 1, $AllowDelete = 1, $separateDV = 0, $TemplateDV = '', $TemplateDVP = '') {
	// function to return an editable form for a table records
	// and fill it with data of record whose ID is $selected_id. If $selected_id
	// is empty, an empty form is shown, with only an 'Add New'
	// button displayed.

	global $Translation;
	$eo = ['silentErrors' => true];
	$noUploads = null;
	$row = $urow = $jsReadOnly = $jsEditable = $lookups = null;

	// mm: get table permissions
	$arrPerm = getTablePermissions('applicants_and_tenants');
	if(!$arrPerm['insert'] && $selected_id == '')
		// no insert permission and no record selected
		// so show access denied error unless TVDV
		return $separateDV ? $Translation['tableAccessDenied'] : '';
	$AllowInsert = ($arrPerm['insert'] ? true : false);
	// print preview?
	$dvprint = false;
	if(strlen($selected_id) && Request::val('dvprint_x') != '') {
		$dvprint = true;
	}


	// populate filterers, starting from children to grand-parents

	// unique random identifier
	$rnd1 = ($dvprint ? rand(1000000, 9999999) : '');
	// combobox: birth_date
	$combo_birth_date = new DateCombo;
	$combo_birth_date->DateFormat = "mdy";
	$combo_birth_date->MinYear = defined('applicants_and_tenants.birth_date.MinYear') ? constant('applicants_and_tenants.birth_date.MinYear') : 1900;
	$combo_birth_date->MaxYear = defined('applicants_and_tenants.birth_date.MaxYear') ? constant('applicants_and_tenants.birth_date.MaxYear') : 2100;
	$combo_birth_date->DefaultDate = parseMySQLDate('', '');
	$combo_birth_date->MonthNames = $Translation['month names'];
	$combo_birth_date->NamePrefix = 'birth_date';
	// combobox: driver_license_state
	$combo_driver_license_state = new Combo;
	$combo_driver_license_state->ListType = 0;
	$combo_driver_license_state->MultipleSeparator = ', ';
	$combo_driver_license_state->ListBoxHeight = 10;
	$combo_driver_license_state->RadiosPerLine = 1;
	if(is_file(__DIR__ . '/hooks/applicants_and_tenants.driver_license_state.csv')) {
		$driver_license_state_data = addslashes(implode('', @file(__DIR__ . '/hooks/applicants_and_tenants.driver_license_state.csv')));
		$combo_driver_license_state->ListItem = array_trim(explode('||', entitiesToUTF8(convertLegacyOptions($driver_license_state_data))));
		$combo_driver_license_state->ListData = $combo_driver_license_state->ListItem;
	} else {
		$combo_driver_license_state->ListItem = array_trim(explode('||', entitiesToUTF8(convertLegacyOptions("AL;;AK;;AS;;AZ;;AR;;CA;;CO;;CT;;DE;;DC;;FM;;FL;;GA;;GU;;HI;;ID;;IL;;IN;;IA;;KS;;KY;;LA;;ME;;MH;;MD;;MA;;MI;;MN;;MS;;MO;;MT;;NE;;NV;;NH;;NJ;;NM;;NY;;NC;;ND;;MP;;OH;;OK;;OR;;PW;;PA;;PR;;RI;;SC;;SD;;TN;;TX;;UT;;VT;;VI;;VA;;WA;;WV;;WI;;WY"))));
		$combo_driver_license_state->ListData = $combo_driver_license_state->ListItem;
	}
	$combo_driver_license_state->SelectName = 'driver_license_state';
	// combobox: status
	$combo_status = new Combo;
	$combo_status->ListType = 2;
	$combo_status->MultipleSeparator = ', ';
	$combo_status->ListBoxHeight = 10;
	$combo_status->RadiosPerLine = 1;
	if(is_file(__DIR__ . '/hooks/applicants_and_tenants.status.csv')) {
		$status_data = addslashes(implode('', @file(__DIR__ . '/hooks/applicants_and_tenants.status.csv')));
		$combo_status->ListItem = array_trim(explode('||', entitiesToUTF8(convertLegacyOptions($status_data))));
		$combo_status->ListData = $combo_status->ListItem;
	} else {
		$combo_status->ListItem = array_trim(explode('||', entitiesToUTF8(convertLegacyOptions("Applicant;;Tenant;;Previous tenant"))));
		$combo_status->ListData = $combo_status->ListItem;
	}
	$combo_status->SelectName = 'status';
	$combo_status->AllowNull = false;

	if($selected_id) {
		// mm: check member permissions
		if(!$arrPerm['view']) return $Translation['tableAccessDenied'];

		// mm: who is the owner?
		$ownerGroupID = sqlValue("SELECT `groupID` FROM `membership_userrecords` WHERE `tableName`='applicants_and_tenants' AND `pkValue`='" . makeSafe($selected_id) . "'");
		$ownerMemberID = sqlValue("SELECT LCASE(`memberID`) FROM `membership_userrecords` WHERE `tableName`='applicants_and_tenants' AND `pkValue`='" . makeSafe($selected_id) . "'");

		if($arrPerm['view'] == 1 && getLoggedMemberID() != $ownerMemberID) return $Translation['tableAccessDenied'];
		if($arrPerm['view'] == 2 && getLoggedGroupID() != $ownerGroupID) return $Translation['tableAccessDenied'];

		// can edit?
		$AllowUpdate = 0;
		if(($arrPerm['edit'] == 1 && $ownerMemberID == getLoggedMemberID()) || ($arrPerm['edit'] == 2 && $ownerGroupID == getLoggedGroupID()) || $arrPerm['edit'] == 3) {
			$AllowUpdate = 1;
		}

		$res = sql("SELECT * FROM `applicants_and_tenants` WHERE `id`='" . makeSafe($selected_id) . "'", $eo);
		if(!($row = db_fetch_array($res))) {
			return error_message($Translation['No records found'], 'applicants_and_tenants_view.php', false);
		}
		$combo_birth_date->DefaultDate = $row['birth_date'];
		$combo_driver_license_state->SelectedData = $row['driver_license_state'];
		$combo_status->SelectedData = $row['status'];
		$urow = $row; /* unsanitized data */
		$row = array_map('safe_html', $row);
	} else {
		$filterField = Request::val('FilterField');
		$filterOperator = Request::val('FilterOperator');
		$filterValue = Request::val('FilterValue');
		$combo_driver_license_state->SelectedText = (isset($filterField[1]) && $filterField[1] == '8' && $filterOperator[1] == '<=>' ? $filterValue[1] : '');
		$combo_status->SelectedText = (isset($filterField[1]) && $filterField[1] == '13' && $filterOperator[1] == '<=>' ? $filterValue[1] : 'Applicant');
	}
	$combo_driver_license_state->Render();
	$combo_status->Render();

	// code for template based detail view forms

	// open the detail view template
	if($dvprint) {
		$template_file = is_file("./{$TemplateDVP}") ? "./{$TemplateDVP}" : './templates/applicants_and_tenants_templateDVP.html';
		$templateCode = @file_get_contents($template_file);
	} else {
		$template_file = is_file("./{$TemplateDV}") ? "./{$TemplateDV}" : './templates/applicants_and_tenants_templateDV.html';
		$templateCode = @file_get_contents($template_file);
	}

	// process form title
	$templateCode = str_replace('<%%DETAIL_VIEW_TITLE%%>', 'Applicant/Tenant Info', $templateCode);
	$templateCode = str_replace('<%%RND1%%>', $rnd1, $templateCode);
	$templateCode = str_replace('<%%EMBEDDED%%>', (Request::val('Embedded') ? 'Embedded=1' : ''), $templateCode);
	// process buttons
	if($AllowInsert) {
		if(!$selected_id) $templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-success" id="insert" name="insert_x" value="1" onclick="return applicants_and_tenants_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save New'] . '</button>', $templateCode);
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="insert" name="insert_x" value="1" onclick="return applicants_and_tenants_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save As Copy'] . '</button>', $templateCode);
	} else {
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '', $templateCode);
	}

	// 'Back' button action
	if(Request::val('Embedded')) {
		$backAction = 'AppGini.closeParentModal(); return false;';
	} else {
		$backAction = '$j(\'form\').eq(0).attr(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;';
	}

	if($selected_id) {
		if(!Request::val('Embedded')) $templateCode = str_replace('<%%DVPRINT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="dvprint" name="dvprint_x" value="1" onclick="$j(\'form\').eq(0).prop(\'novalidate\', true); document.myform.reset(); return true;" title="' . html_attr($Translation['Print Preview']) . '"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print Preview'] . '</button>', $templateCode);
		if($AllowUpdate) {
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '<button type="submit" class="btn btn-success btn-lg" id="update" name="update_x" value="1" onclick="return applicants_and_tenants_validateData();" title="' . html_attr($Translation['Save Changes']) . '"><i class="glyphicon glyphicon-ok"></i> ' . $Translation['Save Changes'] . '</button>', $templateCode);
		} else {
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		}
		if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3) { // allow delete?
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '<button type="submit" class="btn btn-danger" id="delete" name="delete_x" value="1" onclick="return confirm(\'' . $Translation['are you sure?'] . '\');" title="' . html_attr($Translation['Delete']) . '"><i class="glyphicon glyphicon-trash"></i> ' . $Translation['Delete'] . '</button>', $templateCode);
		} else {
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		}
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>', $templateCode);
	} else {
		$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', ($separateDV ? '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>' : ''), $templateCode);
	}

	// set records to read only if user can't insert new records and can't edit current record
	if(($selected_id && !$AllowUpdate && !$AllowInsert) || (!$selected_id && !$AllowInsert)) {
		$jsReadOnly = '';
		$jsReadOnly .= "\tjQuery('#last_name').replaceWith('<div class=\"form-control-static\" id=\"last_name\">' + (jQuery('#last_name').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#first_name').replaceWith('<div class=\"form-control-static\" id=\"first_name\">' + (jQuery('#first_name').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#email').replaceWith('<div class=\"form-control-static\" id=\"email\">' + (jQuery('#email').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#email, #email-edit-link').hide();\n";
		$jsReadOnly .= "\tjQuery('#phone').replaceWith('<div class=\"form-control-static\" id=\"phone\">' + (jQuery('#phone').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#birth_date').prop('readonly', true);\n";
		$jsReadOnly .= "\tjQuery('#birth_dateDay, #birth_dateMonth, #birth_dateYear').prop('disabled', true).css({ color: '#555', backgroundColor: '#fff' });\n";
		$jsReadOnly .= "\tjQuery('#driver_license_number').replaceWith('<div class=\"form-control-static\" id=\"driver_license_number\">' + (jQuery('#driver_license_number').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#monthly_gross_pay').replaceWith('<div class=\"form-control-static\" id=\"monthly_gross_pay\">' + (jQuery('#monthly_gross_pay').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#additional_income').replaceWith('<div class=\"form-control-static\" id=\"additional_income\">' + (jQuery('#additional_income').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#assets').replaceWith('<div class=\"form-control-static\" id=\"assets\">' + (jQuery('#assets').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('input[name=status]').parent().html('<div class=\"form-control-static\">' + jQuery('input[name=status]:checked').next().text() + '</div>')\n";
		$jsReadOnly .= "\tjQuery('.select2-container').hide();\n";

		$noUploads = true;
	} elseif($AllowInsert) {
		$jsEditable = "\tjQuery('form').eq(0).data('already_changed', true);"; // temporarily disable form change handler
		$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', false);"; // re-enable form change handler
	}

	// process combos
	$templateCode = str_replace('<%%COMBO(birth_date)%%>', ($selected_id && !$arrPerm[3] ? '<div class="form-control-static">' . $combo_birth_date->GetHTML(true) . '</div>' : $combo_birth_date->GetHTML()), $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(birth_date)%%>', $combo_birth_date->GetHTML(true), $templateCode);
	$templateCode = str_replace('<%%COMBO(driver_license_state)%%>', $combo_driver_license_state->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(driver_license_state)%%>', $combo_driver_license_state->SelectedData, $templateCode);
	$templateCode = str_replace('<%%COMBO(status)%%>', $combo_status->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(status)%%>', $combo_status->SelectedData, $templateCode);

	/* lookup fields array: 'lookup field name' => ['parent table name', 'lookup field caption'] */
	$lookup_fields = [];
	foreach($lookup_fields as $luf => $ptfc) {
		$pt_perm = getTablePermissions($ptfc[0]);

		// process foreign key links
		if($pt_perm['view'] || $pt_perm['edit']) {
			$templateCode = str_replace("<%%PLINK({$luf})%%>", '<button type="button" class="btn btn-default view_parent" id="' . $ptfc[0] . '_view_parent" title="' . html_attr($Translation['View'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-eye-open"></i></button>', $templateCode);
		}

		// if user has insert permission to parent table of a lookup field, put an add new button
		if($pt_perm['insert'] /* && !Request::val('Embedded')*/) {
			$templateCode = str_replace("<%%ADDNEW({$ptfc[0]})%%>", '<button type="button" class="btn btn-default add_new_parent" id="' . $ptfc[0] . '_add_new" title="' . html_attr($Translation['Add New'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-plus text-success"></i></button>', $templateCode);
		}
	}

	// process images
	$templateCode = str_replace('<%%UPLOADFILE(id)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(last_name)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(first_name)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(email)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(phone)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(birth_date)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(driver_license_number)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(driver_license_state)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(requested_lease_term)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(monthly_gross_pay)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(additional_income)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(assets)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(status)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(notes)%%>', '', $templateCode);

	// process values
	if($selected_id) {
		if( $dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', safe_html($urow['id']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', html_attr($row['id']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode($urow['id']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(last_name)%%>', safe_html($urow['last_name']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(last_name)%%>', html_attr($row['last_name']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(last_name)%%>', urlencode($urow['last_name']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(first_name)%%>', safe_html($urow['first_name']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(first_name)%%>', html_attr($row['first_name']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(first_name)%%>', urlencode($urow['first_name']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(email)%%>', safe_html($urow['email']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(email)%%>', html_attr($row['email']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(email)%%>', urlencode($urow['email']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(phone)%%>', safe_html($urow['phone']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(phone)%%>', html_attr($row['phone']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(phone)%%>', urlencode($urow['phone']), $templateCode);
		$templateCode = str_replace('<%%VALUE(birth_date)%%>', app_datetime($row['birth_date']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(birth_date)%%>', urlencode(app_datetime($urow['birth_date'])), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(driver_license_number)%%>', safe_html($urow['driver_license_number']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(driver_license_number)%%>', html_attr($row['driver_license_number']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(driver_license_number)%%>', urlencode($urow['driver_license_number']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(driver_license_state)%%>', safe_html($urow['driver_license_state']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(driver_license_state)%%>', html_attr($row['driver_license_state']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(driver_license_state)%%>', urlencode($urow['driver_license_state']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(requested_lease_term)%%>', safe_html($urow['requested_lease_term']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(requested_lease_term)%%>', html_attr($row['requested_lease_term']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(requested_lease_term)%%>', urlencode($urow['requested_lease_term']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(monthly_gross_pay)%%>', safe_html($urow['monthly_gross_pay']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(monthly_gross_pay)%%>', html_attr($row['monthly_gross_pay']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(monthly_gross_pay)%%>', urlencode($urow['monthly_gross_pay']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(additional_income)%%>', safe_html($urow['additional_income']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(additional_income)%%>', html_attr($row['additional_income']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(additional_income)%%>', urlencode($urow['additional_income']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(assets)%%>', safe_html($urow['assets']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(assets)%%>', html_attr($row['assets']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(assets)%%>', urlencode($urow['assets']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(status)%%>', safe_html($urow['status']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(status)%%>', html_attr($row['status']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(status)%%>', urlencode($urow['status']), $templateCode);
		if($AllowUpdate || $AllowInsert) {
			$templateCode = str_replace('<%%HTMLAREA(notes)%%>', '<textarea name="notes" id="notes" rows="5">' . safe_html(htmlspecialchars_decode($row['notes'])) . '</textarea>', $templateCode);
		} else {
			$templateCode = str_replace('<%%HTMLAREA(notes)%%>', '<div id="notes" class="form-control-static">' . $row['notes'] . '</div>', $templateCode);
		}
		$templateCode = str_replace('<%%VALUE(notes)%%>', nl2br($row['notes']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(notes)%%>', urlencode($urow['notes']), $templateCode);
	} else {
		$templateCode = str_replace('<%%VALUE(id)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(last_name)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(last_name)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(first_name)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(first_name)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(email)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(email)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(phone)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(phone)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(birth_date)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(birth_date)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(driver_license_number)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(driver_license_number)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(driver_license_state)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(driver_license_state)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(requested_lease_term)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(requested_lease_term)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(monthly_gross_pay)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(monthly_gross_pay)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(additional_income)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(additional_income)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(assets)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(assets)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(status)%%>', 'Applicant', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(status)%%>', urlencode('Applicant'), $templateCode);
		$templateCode = str_replace('<%%HTMLAREA(notes)%%>', '<textarea name="notes" id="notes" rows="5"></textarea>', $templateCode);
	}

	// process translations
	$templateCode = parseTemplate($templateCode);

	// clear scrap
	$templateCode = str_replace('<%%', '<!-- ', $templateCode);
	$templateCode = str_replace('%%>', ' -->', $templateCode);

	// hide links to inaccessible tables
	if(Request::val('dvprint_x') == '') {
		$templateCode .= "\n\n<script>\$j(function() {\n";
		$arrTables = getTableList();
		foreach($arrTables as $name => $caption) {
			$templateCode .= "\t\$j('#{$name}_link').removeClass('hidden');\n";
			$templateCode .= "\t\$j('#xs_{$name}_link').removeClass('hidden');\n";
		}

		$templateCode .= $jsReadOnly;
		$templateCode .= $jsEditable;

		if(!$selected_id) {
			$templateCode.="\n\tif(document.getElementById('emailEdit')) { document.getElementById('emailEdit').style.display='inline'; }";
			$templateCode.="\n\tif(document.getElementById('emailEditLink')) { document.getElementById('emailEditLink').style.display='none'; }";
		}

		$templateCode.="\n});</script>\n";
	}

	// ajaxed auto-fill fields
	$templateCode .= '<script>';
	$templateCode .= '$j(function() {';


	$templateCode.="});";
	$templateCode.="</script>";
	$templateCode .= $lookups;

	// handle enforced parent values for read-only lookup fields
	$filterField = Request::val('FilterField');
	$filterOperator = Request::val('FilterOperator');
	$filterValue = Request::val('FilterValue');

	// don't include blank images in lightbox gallery
	$templateCode = preg_replace('/blank.gif" data-lightbox=".*?"/', 'blank.gif"', $templateCode);

	// don't display empty email links
	$templateCode=preg_replace('/<a .*?href="mailto:".*?<\/a>/', '', $templateCode);

	/* default field values */
	$rdata = $jdata = get_defaults('applicants_and_tenants');
	if($selected_id) {
		$jdata = get_joined_record('applicants_and_tenants', $selected_id);
		if($jdata === false) $jdata = get_defaults('applicants_and_tenants');
		$rdata = $row;
	}
	$templateCode .= loadView('applicants_and_tenants-ajax-cache', ['rdata' => $rdata, 'jdata' => $jdata]);

	// hook: applicants_and_tenants_dv
	if(function_exists('applicants_and_tenants_dv')) {
		$args=[];
		applicants_and_tenants_dv(($selected_id ? $selected_id : FALSE), getMemberInfo(), $templateCode, $args);
	}

	return $templateCode;
}