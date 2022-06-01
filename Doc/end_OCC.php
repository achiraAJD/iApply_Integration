<?php
require('global.php');

$errors = array();

if (!isset($_GET['APP_ID'])) {
	error(400, 'No APP_ID provided');
}

$APP_ID = $_GET['APP_ID'];
if (strlen($APP_ID)!=24) {
	error(400, 'Invalid APP_ID');
}

//cURL on to the iApply API to get application details
$dcl = -1; $i = 0;
while ($dcl < 1500 && $i < 50) {
	$ch = curl_init();
	$apiURL = $startURL . "/GetApplication?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID; 
	curl_setopt($ch, CURLOPT_PROXY, $proxy);
	curl_setopt($ch, CURLOPT_URL, $apiURL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	$dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); 
	$i++;
	curl_close($ch);
	sleep(1);
}

$AppJSON = strip_tags($output); //complete app JSON
$ApplicationData = json_decode (strip_tags($output),1);

//check of the API call was sucessful and setup application data variable (bail out if it wasn't with a 400 error)
if($ApplicationData['Success']==1) {
	$oid = $ApplicationData['Application']['_id']['$oid'];
	$currentApplicationFormID = $ApplicationData['Application']['formId'];
} else {
	error(400, 'No application data found on iApply');
}

//////////////////////////////////////
// ------ SHARED VARIABLES -------- //
//////////////////////////////////////
$ApplicationStreams = array ('BLD'=>8, 'ISL'=>9, 'RLA'=>10, 'RSR'=>10, 'MVD'=>11, 'PGE'=>12, 'RCO'=>13, 'LHS'=>28, 'RPM' => 10, "TAT" => 27);
$LicenceClasses = array ('BLD'=>40, 'ISL'=>41, 'PGE'=>42, 'RCO'=>43, 'RLA'=>44, 'RSR'=>45, 'MVD'=>46, 'LHS'=>68);

//import the file that corresponds with the current form
require($currentApplicationFormID . '/import.php');

//////////////////////////////////////
// ------ SHARED FUNCTIONS -------- //
//////////////////////////////////////

function error ($code, $message) {
	http_response_code($code);
	die("FATAL ERROR ($message)");
	}

function getApplicationPDF ($APP_ID) {
	global $startURL, $proxy, $iApplyUN, $iApplyPW;
	$dcl = -1; $i = 0;
	while ($dcl < 1500 && $i < 10) {
		$ch = curl_init();
		$apiURL = $startURL . "/GetApplicationPdf?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID;
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); $i++;
		curl_close($ch);
		sleep(1);
		//decode the JSON
		$json = json_decode(strip_tags($output), true);
		//check if it was sucessful and kick some goals
		if($json['Success']==1) {
			return $json;
			}
		}
	}

function getUploadedFile ($APP_ID, $FileName, $ControlName) {
	global $startURL, $proxy, $iApplyUN, $iApplyPW;
	$dcl = -1; $i = 0;
	while ($dcl < 1500 && $i < 10) {
		$ch = curl_init();
		$apiURL = $startURL . "/GetUploadedFile?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID . "&filename=" . urlencode(html_entity_decode($FileName)) . "&controlName=" . $ControlName;
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); $i++;
		curl_close($ch);
		sleep(1);
		//decode the JSON
		$json = json_decode(strip_tags($output), true);
		//check if it was sucessful and kick some goals
		if($json['Success']==1) {
			return $json;
			}
		}
	}

function getDocGenFile ($APP_ID, $ControlName) {
	global $startURL, $proxy, $iApplyUN, $iApplyPW;
	$dcl = -1; $i = 0;
	while ($dcl < 1500 && $i < 10) {
		$ch = curl_init();
		$apiURL = $startURL . "/GetDocGenFile?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID . "&controlName=" . $ControlName;
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		$dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); $i++;
		curl_close($ch);
		sleep(1);
		//decode the JSON
		$json = json_decode(strip_tags($output), true);
		//check if it was sucessful and kick some goals
		if($json['Success']==1) {
			return $json;
			}
		}
	}

function importAttachment ($OBJ_ID, $file, $DOC_Filename, $DOC_Author, $DOC_Title, $DOC_Subject, $OBJT_Code, $DT_Code, $LastUpdateUser) {
	global $drv, $svr, $db, $un, $pw;
	//Connect to the DB
	try {
		$dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
		$sql = "exec spWebiApplyAddAttachment @OBJ_ID=:OBJ_ID,@DOC_File=:DOC_File,@DOC_Filename=:DOC_Filename,@DOC_Author=:DOC_Author,@DOC_Title=:DOC_Title,@DOC_Subject=:DOC_Subject,@LastUpdateUser=:LastUpdateUser,@OBJT_Code=:OBJT_Code,@DT_Code=:DT_Code";
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->bindParam(':OBJ_ID', $OBJ_ID);
		$sth->bindValue(':DOC_File', base64_decode($file), PDO::PARAM_LOB);
		$sth->bindValue(':DOC_Filename', $DOC_Filename);
		$sth->bindValue(':DOC_Author', $DOC_Author);
		$sth->bindValue(':DOC_Title', $DOC_Title);
		$sth->bindValue(':DOC_Subject', $DOC_Subject);
		$sth->bindValue(':OBJT_Code', $OBJT_Code);
		$sth->bindValue(':DT_Code', $DT_Code);
		$sth->bindValue(':LastUpdateUser', $LastUpdateUser);

		if (!$sth) {
			$response['noresults1'] = 'true';
			$response['fatalerrors'] = $sth->errorInfo();
			//echo json_encode($response);
		}
		$sth->execute();
		$results = $sth->fetchAll(PDO::FETCH_ASSOC);
		$errorsArray = $sth->errorInfo();
		//echo json_encode($errorsArray);

	} catch (PDOException $e) {
		$response['noresults3'] = 'true';
		$response['fatalerrors'] = $e->getMessage();
		//echo json_encode($response);
		}
	}

function insertOnlineAplicationIntoLogic($applicationDetails) {
	//write the JSON into OnlineApplications in LOGIC
	global $drv, $svr, $db, $un, $pw;
	try {
		$dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

		$sql = 'exec spWebAddOnlineApplication ';
		$sql .= '@OA_ReferenceNumber = :OA_ReferenceNumber, ';
		$sql .= '@AT_ID = :AT_ID, ';
		$sql .= '@AS_ID = :AS_ID, ';
		$sql .= '@OA_ClientEmail = :OA_ClientEmail, ';
		$sql .= '@OA_AttachmentsCount = :OA_AttachmentsCount, ';
		$sql .= '@OA_LodgedDate = :OA_LodgedDate, ';
		$sql .= '@OA_ReceiptNumber = :OA_ReceiptNumber, ';
		$sql .= '@OA_ApplicationRequest = :OA_ApplicationRequest, ';
		$sql .= '@OA_PremisesName = :OA_PremisesName, ';
		$sql .= '@OA_ContactName = :OA_ContactName, ';
		$sql .= '@OA_LC_ID = :OA_LC_ID;';

		$params[':OA_ReferenceNumber'] = $applicationDetails['OA_ReferenceNumber'];
		$params[':AT_ID'] = $applicationDetails['AT_ID'];
		$params[':AS_ID'] = $applicationDetails['AS_ID'];
		$params[':OA_ClientEmail'] = $applicationDetails['OA_ClientEmail'];
		$params[':OA_AttachmentsCount'] = $applicationDetails['OA_AttachmentsCount'];
		$params[':OA_LodgedDate'] = $applicationDetails['OA_LodgedDate'];
		$params[':OA_ReceiptNumber'] = $applicationDetails['OA_ReceiptNumber'];
		$params[':OA_ApplicationRequest'] = preg_replace( "/\r|\n/", "", $applicationDetails['OA_ApplicationRequest']);
		$params[':OA_PremisesName'] = $applicationDetails['OA_PremisesName'];
		$params[':OA_ContactName'] = $applicationDetails['OA_ContactName'];
		$params[':OA_LC_ID'] = $applicationDetails['LC_ID'];
		
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute($params);
		$results = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($results)>0) {
			echo json_encode ($results) . "<BR>";
			$appID = $results[0]['OA_ID'];
		} else {
			error(400, "Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
		}
	} catch (PDOException $e) {
		error(400, "Error connecting to LOGIC - ". $e->getMessage());
	} 

	return $appID;
	}

function getLICIDfromLOGIC ($LC_Code, $LicNum) {
	global $drv, $svr, $db, $un, $pw;
	try {
		$dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
		$params[':LC_Code'] = $LC_Code;
		$params[':LicNum'] = $LicNum;
		
		$sql = 'select * from vwWebGetLicenceDetails where LC_Code = :LC_Code and LIC_LicenceNumberOccupational = :LicNum and ED_ClientID is not NULL';
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		if (!$sth) {
			die ('{"error":"'.implode(',', $sth->errorInfo()).'"}');
			}
		$sth->execute($params);
		$results = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		if (sizeof($results)==0) {error (400, 'Licence not found');}
		elseif (sizeof($results)>1) {error (400, 'Multiple licences found');}
		else {
			return $results[0]['LIC_ID'];
			}
		} 
	catch (PDOException $e) {
		die ('{"error":"'.implode(',', $e->getMessage()) . ", " . implode(',', $sth->errorInfo()).'"}');
		}
	}

function createLogicFinancialTransaction ($FT_TransactionNumber, $FT_ReceiptNumber, $FT_Amount) {
	global $drv, $svr, $db, $un, $pw;
	try {
		$dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

		$sql = 'exec spWebAddOnlineFinancialTransactionOCC ';
		$sql .= '@FT_TransactionNumber = :FT_TransactionNumber, ';
		$sql .= '@FT_ReceiptNumber = :FT_ReceiptNumber, ';
		$sql .= '@FT_AmountPaid = :FT_AmountPaid';

		$params[':FT_TransactionNumber'] = $FT_TransactionNumber; 
		$params[':FT_ReceiptNumber'] = $FT_ReceiptNumber;
		$params[':FT_AmountPaid'] = $FT_Amount;
		
		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute($params);
		$results = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($results)>0) {
			echo "Inserted Financial Transaction FT_ID = " . $results[0]['FT_ID'] . "<BR>";
			return $results[0]['FT_ID'];
		} else {
			error(400, "Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
		}
	} catch (PDOException $e) {
		error(400, "Error connecting to LOGIC - ". $e->getMessage());
		} 
	}

function insertOnlineApplicationEntityIntoLogic($FirstName, $SecondName, $Surname, $DateOfBirth, $Gender, $OA_ID, $ET_ID, $CompanyName, $ACN, $iApplyReference, $ApplicationRequest) {
	global $drv, $svr, $db, $un, $pw;
	try {
		$dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

		$sql = 'exec spWebAddOnlineEntities ';
		$sql .= '@OAE_FirstName = :OAE_FirstName, ';
		$sql .= '@OAE_SecondName = :OAE_SecondName, ';
		$sql .= '@OAE_ThirdName = :OAE_ThirdName, ';
		$sql .= '@OAE_SurName = :OAE_SurName, ';
		$sql .= '@OAE_DateOfBirth =  :OAE_DateOfBirth, ';
		$sql .= '@OAE_Gender = :OAE_Gender, ';
		$sql .= '@OAE_ENT_ID_Matched = null, ';
		$sql .= '@OAE_OA_ID = :OAE_OA_ID, ';
		$sql .= '@OAE_ET_ID = :OAE_ET_ID, ';
		$sql .= '@OAE_CompanyName = :OAE_CompanyName, ';
		$sql .= '@OAE_ACN = :OAE_ACN, ';
		$sql .= '@OAE_iApplyReference = :OAE_iApplyReference, ';
		$sql .= '@OAE_ApplicationRequest = :OAE_ApplicationRequest;';

		$params[':OAE_FirstName'] = strtoupper($FirstName);
		$params[':OAE_SecondName'] = strtoupper($SecondName);
		$params[':OAE_ThirdName'] = null;
		$params[':OAE_SurName'] = strtoupper($Surname);
		$params[':OAE_DateOfBirth'] = date ('Y-m-d', strtotime(str_replace('/', '-', $DateOfBirth)));
		$params[':OAE_Gender'] = substr($Gender,0,1);
		$params[':OAE_OA_ID'] = $OA_ID;
		$params[':OAE_ET_ID'] = $ET_ID;
		$params[':OAE_CompanyName'] = strtoupper($CompanyName);
		$params[':OAE_ACN'] = preg_replace('/\D/', '', $ACN);
		$params[':OAE_iApplyReference'] = $iApplyReference;
		$params[':OAE_ApplicationRequest'] = $ApplicationRequest;
		
		foreach ($params as $key => $value) {
			if (strlen($value) == 0) {
				$sql = str_replace($key, 'null', $sql);
				unset($params[$key]);       
			}
		}

		$sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth->execute($params);
		$results = $sth->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($results)>0) {
			echo json_encode ($results) . "<br>";
			//$appID = $results[0]['OAE_ID'];
		} else {
			error(400, "Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
			}
		} catch (PDOException $e) {
			error(400, "Error connecting to LOGIC - ". $e->getMessage());
		} 
	}
?>