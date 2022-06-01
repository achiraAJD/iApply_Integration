<?php

    //acquring the database,iApply api connection details
    require('Global.php');

    //getting iApply Application form data 'GetApplication'
    function getiApplyApplicationJSON ($APP_ID) {
        global $startURL, $proxy, $iApplyUN, $iApplyPW ;
        $apiURL = $startURL . "/GetApplication?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID; 
        $appData = curlToiApplyAPI($apiURL,$proxy);
        return $appData;

        /*
        //curl to get the application data from ipply form (starts here)
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
            //echo "<pre>";print_r (curl_getinfo($ch));echo "</pre>";
            print_r (curl_error($ch));echo '<br>';
		    curl_close($ch);
		    sleep(1);
	    }
	    
        echo $i . ' attempts to get the application data<br>';
        $json = json_decode(strip_tags($output), true);
        if($json['Success']==1) {
            return $json;
        }else{
            echo "json failed in getiApplyApplicationJSON function<br>";
        }*/
	}// end getiApplyApplicationJSON function

    //add iApply application data to applications tbl in logic DB
    function addApplicationToLOGIC ($ApplicationData,$AppliinkGroupID,$CaseManager,$APP_Delegate_AU_ID,$lastiApplyApp) {
        global $drv, $svr, $db, $un, $pw;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppAddApplication ';
        
            $params['APP_LIC_ID']  = $ApplicationData['Application']['data']['APP_LIC_ID'];
            $params['AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];
            $params['AT_ID'] = $ApplicationData['Application']['data']['AT_ID'];
            $params['APP_Applicant'] = $ApplicationData['Application']['data']['APP_Applicant'];
            $params['APP_ReceiptCode'] = $ApplicationData['Application']['data']['APP_ReceiptCode'];
            $params['APP_ReceiptDate'] = date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['APP_ReceiptDate']),'Y-m-d');
            $params['APP_ApplicFee'] = $ApplicationData['Application']['data']['APP_ApplicFee'];
            $params['APP_ContactPhone'] = $ApplicationData['Application']['data']['APP_ContactPhone'];
            $params['APP_ContactEmail'] = $ApplicationData['Application']['data']['APP_ContactEmail'];
            $params['APP_ContactName'] = $ApplicationData['Application']['data']['APP_ContactName'];
            $params['APP_ContactAddress1'] = $ApplicationData['Application']['data']['APP_ContactAddress1'];
            $params['APP_ContactAddress2'] = $ApplicationData['Application']['data']['APP_ContactAddress2'];
            $params['APP_ContactTown'] = $ApplicationData['Application']['data']['APP_ContactTown'];
            $params['APP_ContactPostcode'] = $ApplicationData['Application']['data']['APP_ContactPostcode'];
            $params['APP_ContactState'] = $ApplicationData['Application']['data']['APP_ContactState'];
            $params['APP_RemovalPremisesAddress1'] = $ApplicationData['Application']['data']['APP_RemovalPremisesAddress1'];
            $params['APP_RemovalPremisesAddress2'] = $ApplicationData['Application']['data']['APP_RemovalPremisesAddress2'];
            $params['APP_RemovalPremisesTown'] = $ApplicationData['Application']['data']['APP_RemovalPremisesTown'];
            $params['APP_RemovalPremisesPostcode'] = $ApplicationData['Application']['data']['APP_RemovalPremisesPostcode'];
            $params['APP_RemovalPremisesState'] = $ApplicationData['Application']['data']['APP_RemovalPremisesState'];
            $params['GA_MachineQty'] = $ApplicationData['Application']['data']['GA_MachineQty'];
            $params['GA_SellBuyEntitlementQty'] = $ApplicationData['Application']['data']['GA_SellBuyEntitlementQty'];
            $params['File_Allocation'] = 1;
            $params['APP_GRP_ID'] = $AppliinkGroupID[0]['GRP_ID'];
            $params['AU_Name'] = $CaseManager;
            $params['iApply'] = 1;
            $params['HearingType'] = $ApplicationData['Application']['data']['APP_HearingType'];
            $params['APP_HearingDate'] =  date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['APP_HearingDate']),'Y-m-d');
            $params['APP_HearingTime'] =  '00:00'; //store procedure needs hearing time 00:00 coz it concatinates hearing date and hearing time together
            $params['HearingAuthority'] = $ApplicationData['Application']['data']['APP_HearingAuthority']; 
            $params['APP_Notes'] = $ApplicationData['Application']['applicationIdDisplay'];
            $params['APG_Delegate_ID'] = $ApplicationData['Application']['data']['apg_id_delegate'];
            $params['APP_Delegate_AU_ID'] = $APP_Delegate_AU_ID;
            $params['APG_ID'] = $ApplicationData['Application']['data']['apg_id'];
            $params['APP_CB_ID_FileStatus'] = $ApplicationData['Application']['data']['APP_CB_ID_FileStatus'];
            $params['APP_AdvertDateLast'] =  date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['APP_AdvertDateLast']),'Y-m-d');
            $params['iApplylastApp'] = $lastiApplyApp;
            
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
     
            echo "Data Passed to Applications tbl in LOGIC DB<br>$sql<br>";
            echo "value for last iapply variable{$lastiApplyApp}<br>";
            echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    if(isset($rows[0]['APP_ID'])) {
                        $results = $rows;
                    }
                }
             } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                return $results;
            } else {
                error(400, "<br>1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>2 - Error connecting to LOGIC - ". $e->getMessage());
        }       
    } // end of addApplicationToLOGIC function

    //generates the App link Group ID and send to LOGIC DB
    function addAppLinkGroupToLOGIC(){
        global $drv, $svr, $db, $un, $pw;
        try {
        
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebAddAppLinkGroup';
             
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute();
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
        
            if (sizeof($results)>0) {
                return $results;
            } else {
                error(400, "<br>1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
       
        } catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        } 
    } // end addApplinkGroupToLOGIC function

    function addApplicationOrderToLOGIC ($ApplicationData, $InsertedApplication) {
        global $drv, $svr, $db, $un, $pw;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppOrderFinaliseDraft ';
        
            $params['APP_IDs'] = json_encode(Array($InsertedApplication[0]['APP_ID']));
            $params['AU_Name'] = 'iApply';
            $params['AOR_Date'] = date('Y-m-d', strtotime(str_replace('/', '-', $ApplicationData['Application']['data']['AOR_Date'])));
		    $params['AOR_ResultDesc'] = $ApplicationData['Application']['data']['AOR_ResultDesc'];
            $params['AOR_EffectiveDate'] = date('Y-m-d', strtotime(str_replace('/', '-', $ApplicationData['Application']['data']['AOR_EffectiveDate'])));
            $params['AOR_Notes'] = $ApplicationData['Application']['data']['AOR_DecisionNotes'];
		    $params['AOR_AU_ID_Delegate'] = $ApplicationData['Application']['data']['AOR_AU_ID_Delegate'];
        
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            $sql .= join(', ', $sqlParams);
        
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($results)>0) {
                return $results;
            } else {
                error(400, "<br>1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        }       
    }

    function addFeeUpdatesToLOGIC ($ApplicationData, $InsertedApplicationOrder) {
        global $drv, $svr, $db, $un, $pw;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppUpdateFees ';
        
            $params['LIC_ID'] = $ApplicationData['Application']['data']['APP_LIC_ID'];
            $params['AOR_ID'] = $InsertedApplicationOrder[0]['AOR_ID'];
            $params['AOR_OrdNo'] = $InsertedApplicationOrder[0]['AOR_OrdNo'];
        
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            $sql .= join(', ', $sqlParams);
        
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($results)>0) {
                return $results;
            } else {
                error(400, "<br>1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        }       
    }

    //creating json string for LicenceSystemApplicationsTBL in LOGIC DB 
    function createJSONForLicenceSystemApplicationsTBL($ApplicationsTBLDataArr,$appdata){
        $temp_arr = array(
            "v" => 2,
            "info" => array (
                "LIC_ID"=> $ApplicationsTBLDataArr[0]['LIC_ID'],
                "AS_ID" => $appdata['Application']['data']['AS_ID'],
                "AT_ID"=> $appdata['Application']['data']['AT_ID'],
                "AT_Desc"=> "handled by the sotred procedure",
                "APP_ID" => $ApplicationsTBLDataArr[0]['APP_ID'],//this APP_ID is the primary key of applications table 
                "APP_ApplicNumber"=> $ApplicationsTBLDataArr[0]['APP_ApplicNumber'],
                "APP_Applicant"=> $appdata['Application']['data']['APP_Applicant'],
                "LGO_Reference" => $appdata['Application']['applicationIdDisplay'],
                "LIC_PremisesAddress1" => $appdata['Application']['data']['premisesAddress']['LIC_PremisesAddress1']
            )
        );

        $json_str = json_encode($temp_arr);

        return $json_str;
    } // end of createJSONForLicenceSystemApplicationsTBL function

    //adding json string and iApply data to LicenceSystemApplications tbl in LOGIC DB 
    function addLicenceSystemApplicationsTBL($JsonStr,$ApplicationsTBLDataArr){
        global $drv, $svr, $db, $un, $pw;
       
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppAddLSA ';
        
            $sql = $sql . '@LSA_LIC_ID = :LSA_LIC_ID, ';
            $sql = $sql . '@LSA_JSON = :LSA_JSON, ';
            $sql = $sql . '@LSA_APP_ID = :LSA_APP_ID, ';
            $sql = $sql . '@iApply = 1;';

            $params[':LSA_LIC_ID'] = $ApplicationsTBLDataArr[0]['LIC_ID'];
            $params[':LSA_JSON'] = $JsonStr;
            $params[':LSA_APP_ID'] = $ApplicationsTBLDataArr[0]['APP_ID'];

            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);

            if (sizeof($results)>0) {
                return $results;
            } else {
                error(400, "1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "2 Error connecting to LOGIC - ". $e->getMessage());
        } 
    } // end of addLicenceSystemApplicationsTBL function

    //seperating the types of attachement from iApply Form Data 
    function processAttachments($ApplicationData, $ApplicationsTBLData) {
        global $APP_ID;
    
        $DT_ID;
        $AttachmentTypes_DTID = [];
        $AttachmentTypes = [];
        //seperating each attachment types by comma and storing them into array
        $Attachments = json_decode($ApplicationData['Application']['data']['Attachments_Integration'], true);
    
        echo "<pre>";
        echo "Attachments_Integrations<br>";
        echo print_r($Attachments,true);    
    
        $attachments = array();
        
        foreach($Attachments as $Attachment){
        
            //getting the uploaded file name which is belong to specdific attachment type
            if (isset($ApplicationData['Application']['data'][$Attachment['name']]) && strlen($ApplicationData['Application']['data'][$Attachment['name']]) > 0) {
                $FileNameArr = explode (', ', $ApplicationData['Application']['data'][$Attachment['name']]);
            
                foreach($FileNameArr as $FileName){
                    $json = getUploadedFile($APP_ID, $FileName, $Attachment['name']);
                    echo $Attachment['name']."<br>"; // gettin the uploaded doc type from control name in iApply
                    echo "File Name - " .$json['File']['Filename'];
                    echo "<br>";
                    $DT_ID = $Attachment['DT_ID'];
                    echo "DT_ID = " .$DT_ID. "<br>";
                    addAttachmentsToLOGICDB($ApplicationData,$DT_ID,$json['File']['Filename'],$json['File']['Data'],$Attachment['name'],$ApplicationsTBLData);
                    echo "<br>";                    
                }
            
            }else{
                echo "{$Attachment['name']}  attatchment type is not uploaded by the user / is not set<br><br>";
            }
        }

        
        echo "<hr>";
    } // end of process attachments function

    //curl function to get the uploaded file from iApply api 'GetUploadedFile'
    function getUploadedFile ($APP_ID, $fileName, $controlName) {
        global $startURL, $proxy, $iApplyUN, $iApplyPW;

        $apiURL = $startURL . "/GetUploadedFile?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID . "&filename=" . urlencode(html_entity_decode($fileName)) . "&controlName=" . $controlName;
        $uploadedFileData = curlToiApplyAPI($apiURL,$proxy);
        return $uploadedFileData;
        /*
        $dcl = -1; $i = 0;
        while ($dcl < 1500 && $i < 10) {
            $ch = curl_init();
            $apiURL = $startURL . "/GetUploadedFile?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID . "&filename=" . urlencode(html_entity_decode($fileName)) . "&controlName=" . $controlName;
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_URL, $apiURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); 
            $i++;
            //echo "<pre>";print_r (curl_getinfo($ch));echo "</pre>";
            print_r (curl_error($ch));echo '<br>';
            curl_close($ch);
            sleep(1);
        }

            echo $i . ' attempts to get the application data<br>';
            $json = json_decode(strip_tags($output), true);
            if($json['Success']==1) {
                return $json;
            }else{
                echo "json failed in getUploadedFile function<br>";
            }
       */
    }

    //seperating the types of DocGen attachments according to control name from iApply form data 
    function processDocGen($ApplicationData,$ApplicationsTBLData) {
        global $APP_ID;

        $DT_ID;
        $AttachmentTypes_DTID = [];
        $DocGens = json_decode($ApplicationData['Application']['data']['Docgen_Integration'], true);
    
        echo "DocGens_Integrations<br>";
        echo "<pre>";
        echo print_r($DocGens,true);
  
        $counter = 0;
        foreach ($DocGens as $DocGen) {
            $docGenJSON = getDocGenFile($APP_ID,$DocGen['name']);//need to return this to get doc details
            if($docGenJSON['File']['Filename'] !== NULL){
                if(($ApplicationData['Application']['data']['APP_ApplicFee'] == 0) && ($DocGens[$counter]['name'] == 'Payment_summary')){
                    echo 'inside if condition<br>';
                    continue;
                }else{
                    echo 'inside else <br>';
                    echo "File Name - " .$docGenJSON['File']['Filename'].'<br>';
                    $DT_ID = $DocGen['DT_ID'];
                    echo "DT_ID = " .$DT_ID. "<br>";
                    echo $DocGens[$counter]['name'].'<br>';
                    addAttachmentsToLOGICDB($ApplicationData,$DT_ID,$docGenJSON['File']['Filename'],$docGenJSON['File']['Data'],$DocGen['name'],$ApplicationsTBLData);
                }
            
                $counter++;
                echo "<br>";
            }else{
                echo "{$docGenJSON['File']['Filename']} is null <br>";
            }
        }
        echo "<hr>";
    } // end of processDocGen function

    //seperating the types of DocGen attachments according to control name from iApply form data 
    function processOrderDocument($AOR_ID) {
        global $APP_ID;
        global $drv, $svr, $db, $un, $pw;

        $docGenJSON = getDocGenFile($APP_ID,'Order');//need to return this to get doc details
        echo "File Name - " .$docGenJSON['File']['Filename'];
        echo "<br>";
        
        try{
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "EXEC spWebDecisionAppAddOrderDocument @DOC_File = :DOC_File, @AOR_ID = :AOR_ID,  @AU_Name = :AU_Name, @iApply = :iApply;";
            $params['AU_Name'] = 'iApply';
            $params['AOR_ID'] = $AOR_ID;
            $params['DOC_File'] = $docGenJSON['File']['Data'];
            $params['iApply'] = "true";

            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($results)>0) {
                return $results;
            }else {
                error(400, "<br>1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }

        }catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        }
    }

    //curl to getdocgen files from iapply form 'GetDocGen'
    function getDocGenFile ($APP_ID, $ControlName) {
        global $startURL, $proxy, $iApplyUN, $iApplyPW;

        $apiURL = $startURL . "/GetDocGenFile?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID . "&controlName=" . $ControlName;        
        $docGenData = curlToiApplyAPI($apiURL,$proxy);
        return $docGenData;
/*
        $dcl = -1; $i = 0;
        while ($dcl < 500 && $i < 10) {
            $ch = curl_init();
            $apiURL = $startURL . "/GetDocGenFile?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID . "&controlName=" . $ControlName;
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_URL, $apiURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); 
            $i++;
            //echo "<pre>";print_r (curl_getinfo($ch));echo "</pre>";
            print_r (curl_error($ch));echo '<br>';
            curl_close($ch);
            sleep(1);
        }

        echo $i . ' attempts to get the application data<br>';
        $json = json_decode(strip_tags($output), true);
        if($json['Success']==1) {
            return $json;
        }else{
            echo "json failed in getDocGen function<br>";
        }
*/
        
    }

    //add attachements to LOGIC DB
    function addAttachmentsToLOGICDB($ApplicationData,$DTID,$FileName,$Base64Value,$ControlName,$ApplicationTBLAPP_ID){
        global $drv, $svr, $db, $un, $pw;
        $DOC_Author = 'iApply';
        $DOC_Subject = 'File contents';
        $switchValue = 'UploadDocument';
    
        $OnlyFileName = substr($FileName,0,-4); //removes the last 4 charaters in file name eg - Payment confirmation.pdf (Payment confirmation) 
        try{
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebApplicationDocuments ';
   
            $params['Switch'] = $switchValue;
            $params['DT_ID'] = $DTID;//get it from appdata json
            $params['DOC_Filename'] = $FileName;
            $params['DOC_Author'] = $DOC_Author;//"iApply"
            $params['DOC_Title'] = $OnlyFileName;//same file name
            $params['DOC_Subject'] =$ControlName."(".$DOC_Subject.")";//this is temp valu need to delete later once figure out 
            $params['AU_Name'] = $DOC_Author;//iApply
            $params['OBJ_ID'] = $ApplicationTBLAPP_ID[0]['APP_ID'];//app_id
            $params['OBJT_Code'] = 'LAM';//Liquor Application Management (LAM)
            $params['AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];
            $params['AT_ID'] = $ApplicationData['Application']['data']['AT_ID'];
            $params['DOC_FileStream'] = $Base64Value;
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }

            $sql .= join(', ', $sqlParams);
        
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($results)>0) {
                echo "adding attachments to LOGIC <br>";
                return $results;
            }else {
              error(400, "<br>1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        }
    }

    //add iApply application data to ApprovalGamesMachines tbl in LOGIC DB
    function addApprovalGamesMachinesToLOGIC($ApplicationData,$ApplicationTBLArr){
        global $drv, $svr, $db, $un, $pw;
       
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebAddApprovalGamesMachines ';
                          
            $params['AGM_CB_ID_GameApprovalType'] = 1234;//$ApplicationData['Application']['data']['AGM_CB_ID_GameApprovalType'];
            $params['AGM_Manufacturer'] = $ApplicationData['Application']['data']['AGM_Manufacturer'];
            $params['AGM_GameID'] = $ApplicationData['Application']['data']['AGM_GameID'];
            $params['AGM_ShellID'] = $ApplicationData['Application']['data']['AGM_ShellID'];
            $params['AGM_CB_ID_Status'] = $ApplicationData['Application']['data']['AGM_CB_ID_Status'];
            $params['AGM_HasBNA'] = (int)$ApplicationData['Application']['data']['AGM_HasBNA'];
            $params['AGM_HasTITO'] = (int)$ApplicationData['Application']['data']['AGM_HasTITO'];
            $params['AGM_CB_ID_InterstateApproval'] = (int)$ApplicationData['Application']['data']['AGM_CB_ID_InterstateApproval'];
            $params['AGM_APP_ID'] = (int)$ApplicationTBLArr[0]['APP_ID']; 
            $params['AGM_Description'] = $ApplicationData['Application']['data']['AGM_Description'];
            $params['AGM_TestingATF'] = $ApplicationData['Application']['data']['AGM_TestingATF'];
                
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            $sql .= join(', ', $sqlParams);

            echo "Data Passed to ApprovalGamesMAchine TBL in LOGIC DB<br>";
            echo $sql; print_r($params); echo '<br>';
        
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($results)>0) {
                return $results;
            } else {
                error(400, "<br>1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        }
    } // end ofaddApprovalGamesMachinesToLOGIC function

    //constructing financial transaction including FT_Id and Application Fee
    function getApplicationFeeFromiApply($FT_Amount,$iApplyFormData,$FTFI_Items){
        echo "FT_Amount = " . $FT_Amount.'<br>';
        if($FT_Amount > 0) {
            $APP_ApplicFee = $iApplyFormData['Application']['data']['APP_ApplicFee'];
            
            // get application details we need
            $FT_ReceiptNumber = $iApplyFormData['Application']['data']['APP_ReceiptCode']; 
            echo "FT_ReceiptNumber = ". $FT_ReceiptNumber. '<br>';
            $FT_TransactionNumber = $iApplyFormData['Application']['data']['Rrn']; //not $ApplicationData['Txnnumber']; which would make more sense... "it is what it is"
            //Insert a financial transaction record into LOGIC
            $FT_ID = insertFinancialTransactionIntoLogic(json_encode($FTFI_Items), $FT_Amount, $FT_ReceiptNumber, $FT_TransactionNumber);
        } else {
            $FT_ID = NULL;
            if($iApplyFormData['Application']['data']['APP_ApplicFee'] <= 0){
                echo "App_ApplicFee = ".$iApplyFormData['Application']['data']['APP_ApplicFee'].'<br>';
            }else{
                echo "Error inserting financial transaction.<br>";
            }
        }
    } // end of getApplicationFeeFromApply function

    //add the financial trnsaction into LOGIOC DB
    function insertFinancialTransactionIntoLogic($FTFI_Items, $FT_Amount, $FT_ReceiptNumber, $FT_TransactionNumber) {

	    //write the financial transaction and fee items into LOGIC
	    global $drv, $svr, $db, $un, $pw;
	    try {
		    $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

    		$sql = 'exec spWebAddOnlineFinancialTransaction ';
	    	$sql = $sql . '@FT_TransactionNumber = :FT_TransactionNumber, ';
		    $sql = $sql . '@FT_ReceiptNumber = :FT_ReceiptNumber, ';
		    $sql = $sql . '@FT_AmountPaid = :FT_AmountPaid, ';
		    $sql = $sql . '@FTFI_Items = :FTFI_Items;';

		    $params[':FT_TransactionNumber'] = $FT_TransactionNumber; 
		    $params[':FT_ReceiptNumber'] = $FT_ReceiptNumber;
		    $params[':FT_AmountPaid'] = $FT_Amount;
		    $params[':FTFI_Items'] = $FTFI_Items;
            
            //echo 'Parama <br>';
            //echo "<pre>";print_r($params);echo "<hr>";
		    $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		    $sth->execute($params);
		    $results = $sth->fetchAll(PDO::FETCH_ASSOC);
		    if (sizeof($results)>0) {
			    echo "Inserted Financial Transaction FT_ID = " . $results[0]['FT_ID'] . "<br>";
			    return $results[0]['FT_ID'];
            } else {
			    error(400, "1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
		    }
	    } catch (PDOException $e) {
		    error(400, "2 Error connecting to LOGIC - ". $e->getMessage());
	    } 
    } 

    //constructing FT_FI_Items array for the FinancialTransactionFeeItem table
    function constructFTFI_ItemsArr($FeeTypesArr){
        //constructing FTFI_Items array 
        $FT_FI_ItemsArr = [];
        foreach ($FeeTypesArr as $item) {
            $FT_FI_ItemsArr[] = array(
                'LFF_Type' => $item['code'],
                'FTFI_ProductCode' => 'L-'.$item['code'],
                'Amount' => $item['amount']
            );
        }

        return $FT_FI_ItemsArr;          
    }

        //curl to get the application data from ipply form (starts here)
    function curlToiApplyAPI($apiURL,$proxy){
        $dcl = -1; $i = 0;
        while ($dcl < 1500 && $i < 50) {
            $ch = curl_init();
            $apiURL;
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_URL, $apiURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $dcl = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD); 
            $i++;
            //echo "<pre>";print_r (curl_getinfo($ch));echo "</pre>";
            print_r (curl_error($ch));echo '<br>';
            curl_close($ch);
            sleep(1);
        }
        //printing the calculated number of times to get the application,docgen or attachements
        echo "{$i} attempts to get the application data<br>";
        $json = json_decode(strip_tags($output), true);
        if ($json['Success']==1) {
            return $json;
        }else{
            echo "json fails in curlToiApplyAPI function<br>";
        }
    } // end of curlToiApplyAPI function

    //check valid json or not
    function isJson($str) {
        $json = json_decode($string);
        return (is_object($json) && json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    //convert stupid strings to an array 
    function convertStupidStringsToArray($str){
        $tempStr = preg_replace("/,(?!.*,)/", "", $str);
        return json_decode($tempStr,true);
    }

    //error fucntion
    function error ($code, $message) {
        http_response_code($code);
        die("FATAL ERROR ($message)");
    }

?>