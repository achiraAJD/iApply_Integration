<?php
/*
	GetLotteryLicenceAppDetails 			- Achira Warnakulasuriya (23/11/2021)
	GetLotteryAppFinanceDetails 			- Achira Warnakulasuriya (23/11/2021)
	GetLotteryWinnersListDetails 			- Achira Warnakulasuriya (23/11/2021)
	GetSelectedLotteryFinancialReturn 		- Achira Warnakulasuriya (23/11/2021)
	GetSelectedLotteryWinnersList 			- Achira Warnakulasuriya (23/11/2021)
	GetSelectedLotteryApplicationDetails 	- Achira Warnakulasuriya (23/11/2021)
	GetApplicationDetailsId 				- Achira Warnakulasuriya (22/04/2022)
	GetApprovedPersonDetails				- Achira Warnakulasuriya (24/04/2022)	
*/
require ("config.php");

$dbh = new PDO("odbc:Driver=$LOGICdrv; Server=$LOGICsvr; Database=$LOGICdb; UID=$LOGICun; PWD=$LOGICpw;" );

# custom results

if ($_GET['function'] == 'GetLicenceDetails') {
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetLicenceDetails'; 
	$params[':Params'] = '{"LIC_ID": '.$_GET['LIC_ID'].', "AS_ID": '.$_GET['AS_ID'].'}';
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
	if (sizeof($results) == 1) {
		$lic = json_decode($results[0]['LSL_JSON'], true);
		# conditions
		foreach ($lic['conditions'] as &$cond) {
			if (isset($cond['FreeText'])) {
				foreach ($cond['FreeText'] as $ft)
				$cond['Desc'] = str_replace('[free-text-'.$ft['ID'].']', $ft['FreeText'], $cond['Desc']);
			}
		}
		# trading hours
		$tradingHours = $lic['tradingHours'];
		if ($_GET['AS_ID'] == 1) {
			$lic['tradingHours'] = array (
				'onPremises' => array_map('formatTradingHours', array_values(array_filter($tradingHours, function($th){return $th['LSTH_LSTHT_ID'] == 1;}))),
				'offPremises' => array_map('formatTradingHours', array_values(array_filter($tradingHours, function($th){return $th['LSTH_LSTHT_ID'] == 2;})))
			);
		} else if ($_GET['AS_ID'] == 2) {
			$lic['tradingHours'] = array_map('formatTradingHours', array_values(array_filter($tradingHours, function($th){return $th['LSTH_LSTHT_ID'] == 3;})));
		}
		echo json_encode($lic);
			die();
	} else {
		$results = [];
	}
}

# results handled the same from here onwards:

if ($_GET['function'] == 'GetFees') {
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetFees'; 
	$params[':Params'] = $_GET['Codes'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
}

if ($_GET['function'] == 'GetApplicationDetails') {
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetApplicationDetails'; 
	$params[':Params'] = $_GET['ReferenceNumber'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
	foreach ($results as &$r) {
		$r['display'] = $r['CustomerReferenceNumber'] . ' - ' . $r['AT_Desc'];
	}
}

##Achira....
#returns ED_ClientName based on Lottery Licence Number
if ($_GET['function'] == 'GetLotteryLicenceAppDetails') {
    $params = [];
	$sql = 'exec spWebGetLotteryDetails @Switch = :Switch, @Params = :Params, @Display = :Display';
	$params[':Switch'] = 'GetEDClientName'; 
	$params[':Params'] = $_GET['LLIC_LicenceNumber'];
	$params[':Display'] = 1;
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$LotteryData = $sth->fetchAll(PDO::FETCH_ASSOC);
	$ClientName = str_replace([':', '.', '-', '*'], '', strtolower($LotteryData[0]['ED_ClientName']));
	$ED_ClientName = str_replace('( ', '(', ucwords(str_replace('(', '( ', $ClientName)));
}

if ($_GET['function'] == 'GetLotteryLicenceAppDetails') {
	$params = [];
	$LLIC_LicenceNumber = $_GET['LLIC_LicenceNumber']; 
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetLotteryLicenceAppDetails'; 
	$params[':Params'] = $_GET['LLIC_LicenceNumber'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
	if (sizeof($results)>0) {
		$results = array_values(array_filter($results, function($r) {return $r['OutstandingFS'] > 0 || $r['OutstandingWL'] > 0;}));
		if (sizeof($results)>0) {
			$results[0]["display"] = $ED_ClientName.' - '.$results[0]['LLIC_LicenceNumber'];
			foreach($results as &$res){
				array_push($res["display"] = $ED_ClientName.' - '.$results[0]['LLIC_LicenceNumber']);
				array_push($res["ED_ClientName"] = $ED_ClientName);
			}
		} else {
			$results[0]["display"] = "The licence number you have entered does not have any outstanding financial statements.";
		}
	} else {
		$results[0]["display"] = "Invalid Licence Number";
	}
}

if ($_GET['function'] == 'GetLotteryAppFinanceDetails') {
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetLotteryAppFinanceDetails'; 
	$params[':Params'] = $_GET['LAPP_ID'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
	
	if(sizeof($results) > 0){
		foreach($results as &$res){
			$date = date_format(date_create($res['FS_DateDue']),'d-m-Y');
			$displayFsValue = "Return No.{$res['FS_ReturnNumber']} (Due date: {$date})";
			array_push($res["Display_Value"] = $displayFsValue);
		}
		
		$results[0]['display'] = $results[0]['FS_LAPP_ID'];
	}
}

if($_GET['function'] == 'GetLotteryWinnersListDetails'){
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetLotteryWinnersListDetails'; 
	$params[':Params'] = $_GET['LAPP_ID'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
	
	if(sizeof($results) > 0){
		foreach($results as &$res){
			$date = date_format(date_create($res['WL_DueDate']),'d-m-Y');
			$displayFsValue = "Winners List - Due date: {$date}";
			array_push($res["Display_Value"] = $displayFsValue);
		}
		$results[0]['display'] = 'WL_LAPP_ID - '.$results[0]['WL_LAPP_ID'];
	}else{
		$results[0]['display'] = "The licence number you have entered does not have any outstanding financial statements.";
	}
}

if($_GET['function'] == 'GetSelectedLotteryFinancialReturn'){
	$params = [];
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetSelectedLotteryFinancialReturn'; 
	$params[':Params'] = $_GET['FS_ID'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
    
	if(sizeof($results) > 0){
		$results[0]['display'] = 'FS_ID - '.$results[0]['FS_ID'];
	}
}

if($_GET['function'] == 'GetSelectedLotteryWinnersList'){
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetSelectedLotteryWinnersList'; 
	$params[':Params'] = $_GET['WL_ID'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);

	if(sizeof($results) > 0){
		$results[0]['display'] = 'WL_ID - '.$results[0]['WL_ID'];
	}
}

if($_GET['function'] == 'GetSelectedLotteryApplicationDetails'){
    $sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetSelectedLotteryApplicationDetails'; 
	$params[':Params'] = $_GET['LAPP_ID'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
    
	if(sizeof($results) > 0){
		$results[0]['display'] = 'LAPP_ID - '.$results[0]['LAPP_ID'];
	}
}

if ($_GET['function'] == 'GetApplicationDetailsId') {
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetApplicationDetailsId'; 
	$params[':Params'] = $_GET['APP_ID'];
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);
	
	if(sizeof($results) > 0){
		$results[0]['display'] =  $results[0]['CustomerReferenceNumber'] . ' - ' . $results[0]['AT_Desc'];
	}
}

if($_GET['function'] == 'GetApprovedPersonDetails'){
	$sql = 'exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params';
	$params[':Switch'] = 'GetApprovedPersonDetails'; 
		
	$tempArry = array(
		"ED_Name1" => $_GET['ED_Name1'],
		"ED_Surname" => $_GET['ED_Surname'],
		"ENT_DOB" => str_replace("/","-",$_GET['ENT_DOB'])
	);
	
	if (empty($_GET['EC_Code'])){
		//$params[':Params'] = '{"ED_Name1": "'.$_GET['ED_Name1'].'", "ED_Surname": "'.$_GET['ED_Surname'].'", "ENT_DOB": "'.str_replace("/","-",$_GET['ENT_DOB']).'"}';
		$params[':Params'] = json_encode($tempArry);
	}else{
		array_push($tempArry["EC_Code"] = $_GET['EC_Code']);
		$params[':Params'] = json_encode($tempArry);
	}
	
	$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth->execute($params);
	$results = $sth->fetchAll(PDO::FETCH_ASSOC);

	if(sizeof($results) > 0){
		$results[0]['display'] =  'ED_Name1 - '.$results[0]['ED_Name1'];
		$results = $results[0];
	}
}

$response = array(
	'success' => !!sizeof($results),
	'display' => sizeof($results) ? (isset($results[0]['display']) ? $results[0]['display'] : '') : 'No results found',
	'count' => sizeof($results),
	'data' => $results
);

echo json_encode ($response);

function formatTradingHours ($th) { 
	$th["LSTH_StartTime_Display"] = strtotime($th["LSTH_StartTime"]) ? date("g:i a", strtotime($th["LSTH_StartTime"])) : null;
	$th["LSTH_EndTime_Display"] = strtotime($th["LSTH_EndTime"]) ? date("g:i a", strtotime($th["LSTH_EndTime"])) : null;
	if(strtotime($th["LSTH_StartTime"]) == null || strtotime($th["LSTH_EndTime"]) == null ) {$th["NextSameClosed"] = 'Closed';}
	else if ($th['LSTH_NextDay'] == 1) {$th["NextSameClosed"] = 'the following day';}
	else {$th["NextSameClosed"] = 'the same day';}
	return $th;
}

?>