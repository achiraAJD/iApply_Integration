<?php
    /*
        FileZilla Path UAT - /home/www/htdocs/LGPubReg/UAT/iApply
        FileZilla Path PROD -  /home/www/htdocs/LGPubReg/iApply
        FileZilla Path APIs - /home/www/htdocs/OccLicPubReg/iApply/TEST
    */
    //acquring the database,iApply api connection details
    require('Global.php');
    
    $CommonAT_ID = ''; //AT_ID number contains in both iApply Form and Application Type talbe in LOGIC (To capture iApply forms which needs to create a new licence in ProcessForm.php)
    $LastUpdateUser = 'iApply'; 

    //getting iApply Application form data 'GetApplication'
    function getiApplyApplicationJSON ($APP_ID) {
        global $startURL, $proxy, $iApplyUN, $iApplyPW ;
        $apiURL = $startURL . "/GetApplication?username=$iApplyUN&password=$iApplyPW&applicationId=" . $APP_ID; 
        $appData = curlToiApplyAPI($apiURL,$proxy);
        return $appData;        
	}// end getiApplyApplicationJSON function

    //check AS_ID or AT)ID are valid json or not
    function isJson($str) {
        $json = json_decode($string);
        return (is_object($json) && json_last_error() == JSON_ERROR_NONE) ? true : false;
    }// end of isJson

    /**
    *convert stupid strings to an array 
    *EG. if any string comes like [1,2,3,] this function removes the comma at the end and make it an array like [1,2,3]
    */
    function convertStupidStringsToArray($str){
        $tempStr = preg_replace("/,(?!.*,)/", "", $str);
        return json_decode($tempStr,true);
    } // convertStupidStringsToArray

    /**
     * this function is mainly used to construct an array based on the AT_ID and AS_ID numbers
     * if AT_ID number which is getting from iApply is availeble in ApplicationTypes under AT_RelatedLicenceRecord = 'C'
     * bring that AT_ID number in the array into 0 index
     * bring the coresponding number in ASID array into 0 index
     * Eg: if AT_ID = [1,2,3,235,6] & AS_ID = [1,2,3,4,5]   
     * 235 is availble in ApplicationTypes under AT_RelatedLicenceRecord = 'C' needs to recreate a new licence therefore AT_ID = [235,1,2,3,6] & AS_ID = [4,1,2,3,5] 
     */
    function construct_ATIDs_ASIDs_Array($ATIDs,$ASIDs){
        global $CommonAT_ID;      
        $temp_AT_ID_array = array();
        $temp_AS_ID_array = array();
        $IDArr = getAT_IDsFromApplicationTypes();

        //constructing AT_ID and AS_ID arrays
        foreach($ATIDs as $key=>$val){
            if(in_array($val,$IDArr)){
                $CommonAT_ID = $val;
                array_unshift($temp_AT_ID_array,$val);
                array_unshift($temp_AS_ID_array,$ASIDs[$key]);
            }else{
                $temp_AT_ID_array[] = $val;
                $temp_AS_ID_array[] = $ASIDs[$key];            
            }        
        } 
        
        //echo 'AT_ID<br>';print_r($temp_AT_ID_array);echo '<hr>';
        //echo 'AS_ID<br>';print_r($temp_AS_ID_array);echo '<hr>';
                
        return array($temp_AT_ID_array,$temp_AS_ID_array);
    } //end of construct_ATIDs_ASIDs_Array

    //get AT_ID numbers where AT_RelatedLicenceRecord = 'C' in ApplicationTypes  
    function getAT_IDsFromApplicationTypes(){
        $tempArray = [];
        global $drv, $svr, $db, $un, $pw;
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $dbh->prepare("SELECT AT_ID FROM vwWebApplicationTypes WHERE AT_RelatedLicenceRecord = 'C'");
            $stmt->execute();            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);// set the resulting array to associative
            if (sizeof($results)>0) {
                echo "Data Fetched from Application Types table in LOGIC<br>";
                foreach($results as $value){
                    $tempArray[] = $value['AT_ID'];
                }
                //echo '<pre>';print_r($tempArray);echo '</pre>';
                return $tempArray;
            } else {
                error(400, "<br>getAT_IDsFromApplicationTypes 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch(PDOException $e){
            error(400, "<br>getAT_IDsFromApplicationTypes 2 - Error connecting to LOGIC - ". $e->getMessage());
        }

        $dbh = null;
    } // end of getAT_IDsFromApplicationTypes function
    
    //generates the Application Group ID
    function getAppGroupID(){
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
                error(400, "<br>getAppGroupID 1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
       
        } catch (PDOException $e) {
            error(400, "<br>getAppGroupID 2 Error connecting to LOGIC - ". $e->getMessage());
        } 
    } // end getAppGroupID function
    
    //Create a new licence record for 'C' types
    function createNewLicence($ApplicationData){
        $LC_Code = $ApplicationData['Application']['data']['LC_Code'];
        $LC_IDArr = getLicenceClassesID($LC_Code);
        echo 'LC_Code <br>';
        echo '<pre>';print_r($LC_IDArr);echo '</pre>';

        global $drv, $svr, $db, $un, $pw, $LastUpdateUser;
        try{
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebTmpAddApplication ';

            $params['CreateLicence'] = $ApplicationData['Application']['data']['TRG_CreateLicence'];
            $params['AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];
            $params['AT_ID'] = $ApplicationData['Application']['data']['AT_ID'];
            $params['PN_Name'] = $ApplicationData['Application']['data']['PN_Name'];
            $params['APP_LC_ID'] = $LC_IDArr[0]['LC_ID'];        
            $params['CreateLicenceOnly'] = 1;
            $params['LIC_PremisesTown'] = $ApplicationData['Application']['data']['LIC_PremisesTown'];
            $params['LIC_PremisesAddress1'] = $ApplicationData['Application']['data']['LIC_PremisesAddress1']; 
            $params['LIC_PremisesPostcode'] = $ApplicationData['Application']['data']['LIC_PremisesPostcode'];
            $params['LIC_PremisesPhone'] = $ApplicationData['Application']['data']['LIC_PremisesPhone'];
            $params['LIC_PremisesEmail'] = $ApplicationData['Application']['data']['LIC_PremisesEmail'];
            $params['LIC_PremisesState'] = $ApplicationData['Application']['data']['LIC_PremisesState'];
            $params['LIC_InterstateJursidiction'] = $ApplicationData['Application']['data']['LIC_InterstateJursidiction'];
            $params['LIC_InterstateLicenceNumber'] = $ApplicationData['Application']['data']['LIC_InterstateLicenceNumber'];
            $params['LIC_PremisesAddress2'] = $ApplicationData['Application']['data']['LIC_PremisesAddress2'];  
            $params['LIC_PremisesWeb'] = $ApplicationData['Application']['data']['LIC_PremisesWeb'];
            $params['LIC_PremisesMobile'] = $ApplicationData['Application']['data']['LIC_PremisesMobile'];
            $params['LastUpdateUser'] = $LastUpdateUser;
                   
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);  
                    $sqlParams[] = "@$key = null";      
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['LIC_ID'])) {
                        $results = $rows;
                    }
                }             
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB - values in results array<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                //echo "LIC_ID - {$results[0]['LIC_ID']}<br>";
                return $results;
            } else {
                error(400, "<br>createNewLicence 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e){
            error(400, "<br>createNewLicence 2 - Error connecting to LOGIC - ". $e->getMessage());
            
        }
        
    }// end of createNewLicence function

    //get LicenceClass ID from LogiC
    function getLicenceClassesID($LC_Code){
        global $drv, $svr, $db, $un, $pw;
        try{    
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "SELECT LC_ID FROM vwWebLicenceClasses WHERE LC_Code = :LC_Code";
            
            $params['LC_Code'] = $LC_Code;
                               
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                return $results;
            } else {
                error(400, "<br>1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }

        }catch (PDOException $e){
            error(400, "<br>getLicenceClassesID 2 - Error connecting to LOGIC - ". $e->getMessage());
        }
    }// end of getLicenceClassesID

    //adding newly created licence's licence postal details to logic
    function addNewLicencePostalDetails($ApplicationData,$APP_LIC_ID){
        global $drv, $svr, $db, $un, $pw;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebAddLicencePostalDetails ';
        
            $params['LPD_Address1'] = $ApplicationData['Application']['data']['LPD_Address1'];
            $params['LPD_Address2'] = $ApplicationData['Application']['data']['LPD_Address2'];
            $params['LPD_State'] = $ApplicationData['Application']['data']['LPD_State'];	
            $params['LPD_PostCode'] = $ApplicationData['Application']['data']['LPD_PostCode'];
            $params['LPD_Town'] = $ApplicationData['Application']['data']['LPD_Town'];
            $params['LastUpdateUser'] = $LastUpdateUser;
            $params['LPD_Email'] = $ApplicationData['Application']['data']['LPD_Email'];
            $params['LPD_MobileNotification'] = $ApplicationData['Application']['data']['LPD_MobileNotification'];
            $params['LPD_CB_ID_PreferredDelivery'] = $ApplicationData['Application']['data']['LPD_CB_ID_PreferredDelivery'];
            $params['LPD_EmailLastReject'] = $ApplicationData['Application']['data']['LPD_EmailLastReject'];
            $params['LPD_SMSLastReject'] = $ApplicationData['Application']['data']['LPD_SMSLastReject'];
            $params['LPD_SendSMSNotifications'] = $ApplicationData['Application']['data']['LPD_SendSMSNotifications']; 
            $params['LIC_ID'] = $APP_LIC_ID;
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);    
                    $sqlParams[] = "@$key = null"; 
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo "value for last iapply variable{$lastiApplyApp}<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['LPD_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>addNewLicencePostalDetails 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>addNewLicencePostalDetails 2 - Error connecting to LOGIC - ". $e->getMessage());
        }       
    }// end of addNewLicencePostalDetails

    //add iApply application data to applications tbl in logic DB
    function addApplicationToLOGIC ($ApplicationData,$AppliinkGroupID,$CaseManager,$APP_Delegate_AU_ID,$lastiApplyApp,$App_Lic_Id) {
        global $drv, $svr, $db, $un, $pw;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppAddApplication ';
        
            $params['APP_LIC_ID']  = $App_Lic_Id;
            $params['AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];
            $params['AT_ID'] = $ApplicationData['Application']['data']['AT_ID'];
            $params['APP_Applicant'] = html_entity_decode($ApplicationData['Application']['data']['APP_Applicant']);
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
                    $sqlParams[] = "@$key = null";   
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo "value for last iapply variable{$lastiApplyApp}<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['APP_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>addApplicationToLOGIC 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>addApplicationToLOGIC 2 - Error connecting to LOGIC - ". $e->getMessage());
        }       
    } // end of addApplicationToLOGIC function
    
     //get Entity Details 
     function getEntityIDs($ERDataArr,$ApplicationData){  
        //echo '<pre>';print_r($ERDataArr);echo '</pre>';
        //assinging correct ET_ID based on ET_Code in Entity Types Table 
        echo "ENT_ET_Code IN iAPPLY- {$ERDataArr['ENT_ET_Code']}<br>"; 
        $ET_ID = getEntityTypesDetails($ERDataArr['ENT_ET_Code']);
        $ERDataArr['ENT_ET_ID'] =  $ET_ID;
        echo "ENT_ET_ID VALUE iApply {$ERDataArr['ENT_ET_ID']}<br>"; 

        $ENT_ID = getExistingEntityDetails($ERDataArr);

        //echo '<pre>';print_r($ERDataArr);echo '</pre>';        
        if(is_null($ENT_ID)){
            $ENT_ID = addNewEntity($ERDataArr,$ApplicationData);
            echo "New ENT_ID - {$ENT_ID}<hr>";
        }else{
            echo "Old ENT_ID - {$ENT_ID}<hr>";
        }

        return  $ENT_ID;
    } // end of getEntityIDs function

    //used to get Entity Type ID and Entity Type Code from LOGIC
    function getEntityTypesDetails($ET_Code){
        $params = [];
        global $drv, $svr, $db, $un, $pw;
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "SELECT ET_ID FROM vwWebEntityTypes WHERE ET_Code = :ET_Code";

            $params['ET_Code'] = $ET_Code;
            
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                return $results[0]['ET_ID'];
            } else {
                error(400, "<br> getEntityTypesDetailsFromLOGIC 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch(PDOException $e){
            error(400, "<br> getEntityTypesDetailsFromLOGIC 2 - Error connecting to LOGIC - ". $e->getMessage());
        }

        $dbh = null;
    }// end of getEntityTypesDetailsFromLOGIC

    //fetching all the existing Entity Details from LOGIC
    function getExistingEntityDetails($ERDataArr){
        global $drv, $svr, $db, $un, $pw;
        //echo strtolower(substr($ERDataArr['ED_Name1'],0,1)).'<br>';
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebGetEntityDetails ';
        
            $params['ED_Name1']  = trim(strtolower(html_entity_decode(substr($ERDataArr['ED_Name1'],0,1))));
            $params['ED_Surname'] = trim(strtolower(html_entity_decode($ERDataArr['ED_Surname'])));
            $params['ED_TrusteeName'] = trim(strtolower(html_entity_decode($ERDataArr['ED_TrusteeName'])));
            $params['ENT_DOB'] = date_format(date_create($ERDataArr['ENT_DOB']),"Y-m-d H:i:s");
            $params['ET_Code'] = $ERDataArr['ENT_ET_Code'];
            $params['ED_ABN'] = $ERDataArr['ED_ABN']; 

            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) <= 0) {
                    unset($params[$key]);
                    $sqlParams[] = "@$key = null";     
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
     
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['ENT_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>=0) {
                //echo "New ENT_ID is {$results[0]['ENT_ID']}<hr>";
                return $results[0]['ENT_ID'];
            } else {
                error(400, "<br>getExistingEntityDetails 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>getExistingEntityDetails 2 - Error connecting to LOGIC - ". $e->getMessage());
        }   
    }//end of getExistingEntityDetails   

    //generating new entity
    function addNewEntity($ERDataArr,$ApplicationData){
        global $drv, $svr, $db, $un, $pw, $LastUpdateUser;

        try {
           $params = [];
           $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
           $sql = 'EXEC spWebAddEntities ';
       
           $params['LastUpdateUser']  = $LastUpdateUser;
           $params['PIDType'] = 'N';
           $params['AP_CriminalHistory'] = 0;
           $params['ENT_ET_ID'] = $ERDataArr['ENT_ET_ID'];
           $params['AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];        
           $params['ENT_PostalState'] = $ERDataArr['ENT_PostalState'];
           $params['ENT_PostalPostCode'] = $ERDataArr['ENT_PostalPostCode'];
           $params['ENT_PostalTown'] = $ERDataArr['ENT_PostalTown'];
           $params['ENT_PostalAddress1'] = $ERDataArr['ENT_PostalAddress1'];
           $params['ENT_PostalAddress2'] = $ERDataArr['ENT_PostalAddress2'];
           $params['ENT_State'] = $ERDataArr['ENT_State'];
           $params['ENT_Town'] = $ERDataArr['ENT_Town'];
           $params['ENT_Address1'] = $ERDataArr['ENT_Address1'];
           $params['ENT_Address2'] = $ERDataArr['ENT_Address2'];
           $params['ENT_PostCode'] = $ERDataArr['ENT_PostCode'];
           $params['ED_ABN'] = $ERDataArr['ED_ABN'];
           $params['ED_ACN'] = $ERDataArr['ED_ACN'];
           $params['ENT_Email'] = $ERDataArr['ENT_Email'];
           $params['ENT_Mobile'] = $ERDataArr['ENT_Mobile'];
           $params['ENT_Gender'] = $ERDataArr['ENT_Gender'];
           $params['ENT_DOB'] = $ERDataArr['ENT_DOB'];
           $params['ED_TrusteeName'] = $ERDataArr['ED_TrusteeName'];
           $params['ED_Surname'] = html_entity_decode($ERDataArr['ED_Surname']);
           $params['ED_Name1'] = html_entity_decode($ERDataArr['ED_Name1']);
           $params['ED_Name2'] = html_entity_decode($ERDataArr['ED_Name2']);
           $params['ED_Name3'] = html_entity_decode($ERDataArr['ED_Name3']);
           $params['Categories'] = $ERDataArr['Categories'];
           $params['AP_DateLodged'] = date("Y-m-d");
           $params['APP_ID'] = $ERDataArr['APP_ID'];
           $params['ENT_Phone'] = $ERDataArr['ENT_Phone'];
           $params['ENT_PreferredName'] = $ERDataArr['ENT_PreferredName'];

           $sqlParams = [];
           foreach ($params as $key => $value) {
               if (strlen($value) == 0) {
                   unset($params[$key]);
                   $sqlParams[] = "@$key = null";     
               } else {
                   $sqlParams[] = "@$key = :$key";
               }
           }
       
           $sql .= join(', ', $sqlParams);
    
           //echo $sql."<br>";
           //echo '<pre>';print_r($params);echo '<pre>';
           $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
           $sth->execute($params);
           do {
               $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
               //echo 'values of $rows array<br>';
               //echo '<pre>';print_r($rows);echo '</pre>';
               if ($rows) {
                   if(isset($rows[0]['ENT_ID'])) {
                       //echo "hehe<br>";
                       $results = $rows;
                   }
               }
           } while ($sth->nextRowset());
           if (sizeof($results)>0) {
               //echo "New ENT_ID is {$results[0]['ENT_ID']}<hr>";
               return $results[0]['ENT_ID'];
           } else {
               error(400, "<br>addNewEntity 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
           }
       } catch (PDOException $e) {
           error(400, "<br>addNewEntity 2 - Error connecting to LOGIC - ". $e->getMessage());
       }   
    }  // end of addNewEntity

    //adding record to Licensees and LicenceEntities table in LOGIC
    function addLicensees($ENT_ID,$ApplicationData,$APP_LIC_ID){
        global $drv, $svr, $db, $un, $pw, $LastUpdateUser;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebAddLicensees ';
            
            $params['LastUpdateUser']  = $LastUpdateUser;
            if(strlen(html_entity_decode($ApplicationData['Application']['data']['APP_Applicant'])) > 40){
                $params['LEE_Name1'] = substr(html_entity_decode($ApplicationData['Application']['data']['APP_Applicant']),0,40);
                $params['LEE_Name2'] = substr(html_entity_decode($ApplicationData['Application']['data']['APP_Applicant']),40,80);
            }else{
                $params['LEE_Name1'] = html_entity_decode($ApplicationData['Application']['data']['APP_Applicant']);
            }
            $params['LE_ENT_ID'] = $ENT_ID;        
            $params['LEE_AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];        
            $params['LEE_LIC_ID'] = $APP_LIC_ID;
            $params['LEE_DateFrom'] = date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['LEE_DateFrom']),'Y-m-d');
            $params['LEE_DateTo'] = $ApplicationData['Application']['data']['LEE_DateTo'];
            $params['LEE_Status'] = $ApplicationData['Application']['data']['LEE_Status'];//Scott needs to send the correct 'A'
            $params['LEE_Notes'] = $ApplicationData['Application']['data']['LEE_Notes'];

            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);
                    $sqlParams[] = "@$key = null";     
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo "{$ENT_ID}<br>";
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['LEE_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "New LEE_ID is {$results[0]['LEE_ID']}<br>";
                //echo "record inserted to Licensees and LicenceEntities table<br>";
                return $results;
            } else {
                error(400, "<br>addLicenseesEntities 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>addLicenseesEntities 2 - Error connecting to LOGIC - ". $e->getMessage());
        }   

    }// end of addLicensees_LicenceEntities    

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
    function createJSONForLicenceSystemApplicationsTBL($ApplicationsTBLDataArr,$ApplicationData,$APP_LIC_ID,$NewLicenceDetails,$LicenseeData,$EntityRepeaterArr,$ENT_IDArr){
        $ENT_JSON_Arr = [];
       
        $LSA_JSON = array(
            "v" => 2,
            "info" => array (
                "LIC_ID"=> (int)$APP_LIC_ID,
                "AS_ID" => (int)$ApplicationData['Application']['data']['AS_ID'],
                "AT_ID"=> (int)$ApplicationData['Application']['data']['AT_ID'],
                "AT_Desc"=> "handled by the sotred procedure",
                "APP_ID" => (int)$ApplicationsTBLDataArr[0]['APP_ID'],//this APP_ID is the primary key of applications table 
                "APP_ApplicNumber"=> $ApplicationsTBLDataArr[0]['APP_ApplicNumber'],
                "APP_Applicant"=> $ApplicationData['Application']['data']['APP_Applicant'],
                "LGO_Reference" => $ApplicationData['Application']['applicationIdDisplay']                
            ),
        );


        if(isset($ApplicationData['Application']['data']['TRG_LSA_licenceClass']) && $ApplicationData['Application']['data']['TRG_LSA_licenceClass'] == 1){
            $LSA_JSON["licenceClass"] =  array(
                "LC_Desc" => $NewLicenceDetails[0]['LC_Desc'],
                "LC_ID" => (int)$NewLicenceDetails[0]['LC_ID']
            );
        }

        if(isset($ApplicationData['Application']['data']['TRG_LSA_licenceStatus']) && $ApplicationData['Application']['data']['TRG_LSA_licenceStatus'] == 1){
            $LSA_JSON["licenceStatus"] =  array(
                $ApplicationData['Application']['data']['LSA_LS_Status_1'] => array(
                    "LS_DateFrom" => $ApplicationData['Application']['data']['LSA_LS_DateFrom_1'],
                    "LS_DateTo" =>  $ApplicationData['Application']['data']['LSA_LS_DateTo_1'],
                    "deletable_class" => $ApplicationData['Application']['data']['LSA_deletable_class_1']
                ),
                
            );
        }

        if(isset($ApplicationData['Application']['data']['TRG_LSA_premisesAddress']) && $ApplicationData['Application']['data']['TRG_LSA_premisesAddress'] == 1){
            $LSA_JSON["premisesAddress"] = array(
                "LIC_PremisesAddress1" => $ApplicationData['Application']['data']['LIC_PremisesAddress1'],
                "LIC_PremisesAddress2" => $ApplicationData['Application']['data']['LIC_PremisesAddress2'],
                "LIC_PremisesPostCode" => $ApplicationData['Application']['data']['LIC_PremisesPostcode'],
                "LIC_PremisesState" => $ApplicationData['Application']['data']['LIC_PremisesState'],
                "LIC_PremisesTown" => $ApplicationData['Application']['data']['LIC_PremisesTown']
            );
        }

        if(isset($ApplicationData['Application']['data']['TRG_LSA_premisesName']) && $ApplicationData['Application']['data']['TRG_LSA_premisesName'] == 1){
            $LSA_JSON["premisesName"] =  array(
                "PN_Name" => $NewLicenceDetails[0]['PN_Name']
            );
        }

        if(isset($ApplicationData['Application']['data']['TRG_LSA_licensee']) && $ApplicationData['Application']['data']['TRG_LSA_licensee'] == 1){
            $LSA_JSON ["licensee"] = array(
                "action" => 'add',
                "LEE_ID" => (int)$LicenseeData[0]['LEE_ID'],
                "LEE_Name" => html_entity_decode($ApplicationData['Application']['data']['APP_Applicant'])
            );
        }

        if(isset($ApplicationData['Application']['data']['TRG_LSA_Entities']) && $ApplicationData['Application']['data']['TRG_LSA_Entities'] == 1){

            //echo 'ENT_IDArr<br><pre>';print_r($ENT_IDArr);echo '</pre>';
            //echo 'ENT IDs<br><pre>';print_r($ENT_IDArr);echo '</pre>';
            //constructing entities for the LSA JSON
            foreach($EntityRepeaterArr as $counter => $ENT){
                $ENT_JSON_Arr[$counter] = array(
                    "action" => "add",
                    "ED_ABN" => $ENT['ED_ABN'],
                    "ED_ACN" => $ENT['ED_ACN'],
                    "ED_Name1" => html_entity_decode($ENT['ED_Name1']),
                    "ED_Name2" => html_entity_decode($ENT['ED_Name2']),
                    "ED_Name3" => null,//$ENT['ED_Name3'],
                    "ED_Surname" => html_entity_decode($ENT['ED_Surname']),
                    "ED_TradingName" => $ENT['ED_TradingName'],
                    "ED_TrusteeName" => $ENT['ED_TrusteeName'],
                    "ENT_Address" => array(
                        "ENT_Address1" => $ENT['ENT_Address1'],
                        "ENT_Address2" => $ENT['ENT_Address2'],
                        "ENT_PostCode" => $ENT['ENT_PostCode'],
                        "ENT_State" => $ENT['ENT_State'],
                        "ENT_Town" => $ENT['ENT_Town']
    
                    ),
                    "ENT_DOB" => $ENT['ENT_DOB'],
                    "ENT_Email" => $ENT['ENT_Email'],
                    "ENT_ID" => (int)$ENT_IDArr[$counter],//need values
                    "ENT_Mobile" => $ENT['ENT_Mobile'],
                    "ENT_Phone" => 'no values in iApply',
                    "ENT_PostalAddress" => array(
                        "ENT_PostalAddress1" => $ENT['ENT_PostalAddress1'],
                        "ENT_PostalAddress2" => $ENT['ENT_PostalAddress2'],
                        "ENT_PostalPostCode" => $ENT['ENT_PostalPostCode'],
                        "ENT_PostalState" => $ENT['ENT_PostalState'],
                        "ENT_PostalTown" => $ENT['ENT_PostalTown']
                    ),
                    "ET_Code" => $ENT['ENT_ET_Code'],
                );
    
            }

            //echo 'ENT_JSON_Arr<br><pre>';print_r($ENT_JSON_Arr);echo '</pre>';
            array_push($LSA_JSON["licensee"]["Entities"] = $ENT_JSON_Arr);
        }        
        
        $json_str = json_encode($LSA_JSON);
        return $json_str;

    } // end of createJSONForLicenceSystemApplicationsTBL function

    //adding json string and iApply data to LicenceSystemApplications tbl in LOGIC DB 
    function addLicenceSystemApplicationsTBL($JsonStr,$APP_LIC_ID,$ApplicationsTBLDataArr){
        global $drv, $svr, $db, $un, $pw;
       
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppAddLSA ';
        
            $sql = $sql . '@LSA_LIC_ID = :LSA_LIC_ID, ';
            $sql = $sql . '@LSA_JSON = :LSA_JSON, ';
            $sql = $sql . '@LSA_APP_ID = :LSA_APP_ID, ';
            $sql = $sql . '@iApply = 1;';

            $params[':LSA_LIC_ID'] = $APP_LIC_ID;
            $params[':LSA_JSON'] = $JsonStr;
            $params[':LSA_APP_ID'] = $ApplicationsTBLDataArr[0]['APP_ID'];

            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);

            if (sizeof($results)>0) {
                echo "Data Passed to LicenceSystemApplications TBL in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "2 Error connecting to LOGIC - ". $e->getMessage());
        } 
    } // end of addLicenceSystemApplicationsTBL function

    //creating json string for LicenceSystemApplicationsTBL in LOGIC DB 
    function createJSONForLicenceSystemLicences($APP_LIC_ID,$LicenseeData,$NewLicenceDetails,$ENT_IDArr,$ApplicationsTBLDataArr,$ApplicationData){
        
        if($ApplicationData['Application']['data']['LEE_Status'] == 'A'){
            $keyName = 'C';    
        }else{
            $keyName = $ApplicationData['Application']['data']['LEE_Status'];
        }
        
        $ENT_ID_JSON_Arr = [];

        foreach ($ENT_IDArr as $counter => $ENT_ID){
            $ENT_ID_JSON_Arr[$counter] = array(
                "ENT_ID" => (int)$ENT_ID
            );
        }

        //mak all the variable check whether null or not
        $LSL_JSON = array(
            "v" => 2,
            "info" => array (
                "LIC_ID"=> (int)$APP_LIC_ID,
                "AS_ID" => (int)$ApplicationData['Application']['data']['AS_ID'],
                "LN_ID" => (int)$NewLicenceDetails[0]['LN_ID'],
                "LN_LicenceNumber"=> $NewLicenceDetails[0]['LN_LicenceNumber'],
                "Licensee" => array(
                    $keyName => array(//"C" letter means LEE_Status get it from form
                        "LEE_ID" =>(int)$LicenseeData[0]['LEE_ID'],
                        "LEE_Name" => html_entity_decode($ApplicationData['Application']['data']['APP_Applicant']),
                    ),
                ),
                "PN_ID" => (int)$NewLicenceDetails[0]['PN_ID'], 
                "PN_Name" => $NewLicenceDetails[0]['PN_Name'],
                "LIC_PremisesAddress" => array(
                    "LIC_PremisesAddress1" => $ApplicationData['Application']['data']['LIC_PremisesAddress1'],
                    "LIC_PremisesAddress2" => $ApplicationData['Application']['data']['LIC_PremisesAddress2'],
                    "LIC_PremisesTown" => $ApplicationData['Application']['data']['LIC_PremisesTown'],
                    "LIC_PremisesPostCode" => $ApplicationData['Application']['data']['LIC_PremisesPostcode'],
                    "LIC_PremisesState" => $ApplicationData['Application']['data']['LIC_PremisesState']
                ),
                "LD_OverallCapacity" => $ApplicationData['Application']['data']['LD_OverallCapacity'],
                "LD_Status" => array(
                    "A" => array(
                        "LS_DateFrom" => date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['APP_ReceiptDate']),'Y-m-d'),
                        "LS_DateTo" => null
                    ),
                ),
                "LC_LicenceClass" => array(
                    "LC_ID" => (int)$NewLicenceDetails[0]['LC_ID'],
                    "LC_Desc" => $NewLicenceDetails[0]['LC_Desc']
                ),
            ),
            "conditions" => array(),
            "authorisations" => array(),
            "exemptions" => array(),   
            "endorsements" => array(),    
            "tradingHours" => array(),
            "outlets" => array()
        );  
        
        array_push($LSL_JSON["info"]["Licensee"]["C"]["Entities"] = $ENT_ID_JSON_Arr);
               
        $json_str = json_encode($LSL_JSON);
        return $json_str;
    } // end of createJSONForLicenceSystemApplicationsTBL function

    //adding json string and iApply data to LicenceSystemApplications tbl in LOGIC DB 
    function addLicenceSystemLicencesTBL($JsonStr,$APP_LIC_ID,$NewLicenceDetails,$ApplicationData){
        global $drv, $svr, $db, $un, $pw;
       
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebDecisionAppAddLSL ';

            $params['LSL_LIC_ID'] = $APP_LIC_ID;
            $params['LSL_JSON'] = $JsonStr;
            $params['LSL_User'] = 'iApply';
            $params['LSL_LSLC_ID'] = $NewLicenceDetails[0]['LC_ID'];
            $params['LSL_AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];

            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);
                    $sqlParams[] = "@$key = null";     
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);

            if (sizeof($results)>0) {
                echo "Data Passed to LicenceSystemLicences TBL in LOGIC DB<hr>";
                return $results;
            } else {
                error(400, "addLicenceSystemLicencesTBL 1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "addLicenceSystemLicencesTBL 2 Error connecting to LOGIC - ". $e->getMessage());
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
    
        //echo "<pre>";
        echo "Attachments_Integrations<br>";
        //echo print_r($Attachments,true);    
    
        $attachments = array();
        
        foreach($Attachments as $Attachment){
        
            //getting the uploaded file name which is belong to specdific attachment type
            if (isset($ApplicationData['Application']['data'][$Attachment['name']]) && strlen($ApplicationData['Application']['data'][$Attachment['name']]) > 0) {
                $FileNameArr = explode (', ', $ApplicationData['Application']['data'][$Attachment['name']]);
            
                foreach($FileNameArr as $FileName){
                    $json = getUploadedFile($APP_ID, $FileName, $Attachment['name']);
                    //echo $Attachment['name']."<br>"; // gettin the uploaded doc type from control name in iApply
                    //echo "File Name - " .$json['File']['Filename'];
                    //echo "<br>";
                    $DT_ID = $Attachment['DT_ID'];
                    //echo "DT_ID = " .$DT_ID. "<br>";
                    //echo '<pre>';print_r($json);echo '</pre>';
                    addAttachmentsToLOGICDB($ApplicationData,$DT_ID,$json['File']['Filename'],$json['File']['Data'],$Attachment['name'],$ApplicationsTBLData);
                    echo "<br>";                    
                }
            
            }else{
                echo "<br>{$Attachment['name']}  attatchment type is not uploaded by the user<br><br>";
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
    } // end of getUploadedFile function  

    //seperating the types of DocGen attachments according to control name from iApply form data 
    function processDocGen($ApplicationData,$ApplicationsTBLData) {
        global $APP_ID;

        $DT_ID;
        $AttachmentTypes_DTID = [];
        $DocGens = json_decode($ApplicationData['Application']['data']['Docgen_Integration'], true);
    
        echo "DocGens_Integrations<br>";
        //echo "<pre>";
        //echo print_r($DocGens,true);

        foreach ($DocGens as $counter => $DocGen) {
            $docGenJSON = getDocGenFile($APP_ID,$DocGen['name']);//need to return this to get doc details
            if($docGenJSON['File']['Filename'] !== NULL){
                if(($ApplicationData['Application']['data']['APP_ApplicFee'] == 0) && ($DocGens[$counter]['name'] == 'Payment_summary')){
                    //echo 'inside if condition<br>';
                    continue;
                }else{
                    //echo 'inside else <br>';
                    //echo "File Name - " .$docGenJSON['File']['Filename'].'<br>';
                    $DT_ID = $DocGen['DT_ID'];
                    //echo "DT_ID = " .$DT_ID. "<br>";
                    //echo $DocGens[$counter]['name'].'<br>';
                    addAttachmentsToLOGICDB($ApplicationData,$DT_ID,$docGenJSON['File']['Filename'],$docGenJSON['File']['Data'],$DocGen['name'],$ApplicationsTBLData, $OBJT_Code);
                }
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
    } // end of getDocGenFile

    //add attachements to LOGIC DB
    function addAttachmentsToLOGICDB($ApplicationData,$DTID,$FileName,$Base64Value,$ControlName,$ApplicationTBLAPP_ID,$ATPrefix,$docnotes,$DocValidToDate,$subject){
        global $drv, $svr, $db, $un, $pw;
        $DOC_Author = 'iApply';
        $DOC_Subject = 'File contents';
        $switchValue = 'UploadDocument';
        $OnlyFileName = substr($FileName,0,-4); //removes the last 4 charaters in file name eg - Payment confirmation.pdf (Payment confirmation) 
        
        if($ApplicationData['Application']['data']['OBJT_Code'] == 'LAM'){
            $OBJ_ID = $ApplicationTBLAPP_ID[0]['APP_ID'];
        }else if($ApplicationData['Application']['data']['OBJT_Code'] == 'LLAM'){
            $OBJ_ID =  $ApplicationData['Application']['data']['LAPP_ID'];
        }else if($ApplicationData['Application']['data']['OBJT_Code'] == 'COMP'){
            $OBJ_ID =  $ApplicationData['Application']['data']['ComplaintID'];
        }
        echo 'OBJT_Code - '.$ApplicationData['Application']['data']['OBJT_Code'].'<br>';
        echo 'OBJ_ID - '.$OBJ_ID.'<br>';
        echo 'File Name - '.$FileName.'<br>';
        
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
            $params['OBJ_ID'] = $OBJ_ID;//app_id                      
            $params['OBJT_Code'] = $ApplicationData['Application']['data']['OBJT_Code'];//'LAM';//Liquor Application Management (LAM)
            $params['AS_ID'] = $ApplicationData['Application']['data']['AS_ID'];
            $params['AT_ID'] = $ApplicationData['Application']['data']['AT_ID'];               
            $params['DOC_FileStream'] = $Base64Value;
            if(isset($ATPrefix) && !empty($ATPrefix)){
                $params['AT_Prefix'] = $ATPrefix;
                $params['DOC_Notes'] = $docnotes;
                $params['DOC_ValidToDate'] = $DocValidToDate;
                $params['DOC_Subject'] = $subject;
            }
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);
                    $sqlParams[] = "@$key = null";      
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }

            $sql .= join(', ', $sqlParams);
        
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            if (sizeof($results)>0) {
                echo "adding attachments to Documents TBL in LOGIC <br>";
                return $results;
            }else {
              error(400, "<br>addAttachmentsToLOGICDB 1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "<br>addAttachmentsToLOGICDB 2 Error connecting to LOGIC - ". $e->getMessage());
        }
    } // end of addAttachmentsToLOGICDB function

     //to get available licence numbers from LOGIC
     function getExistingLicenceNumber($LN_ID){
        global $drv, $svr, $db, $un, $pw;
        $params = [];

        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "SELECT LN_LicenceNumber FROM vwWebLicenceAll WHERE LN_ID = :LN_ID";
            
            $params['LN_ID'] = $LN_ID;

            //$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                return $results;
            } else {
                error(400, "<br> getExistingLicenceNumber 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch(PDOException $e){
            error(400, "<br> getExistingLicenceNumber 2 - Error connecting to LOGIC - ". $e->getMessage());
        }

        $dbh = null;
    }// end of getExistingLicenceNumber

    //adding LPDE Data To LOGIC
    function addLPDEDataToLOGIC($iApplyLPDEDataArr,$LicenceNumber){
        global $drv, $svr, $db, $un, $pw;
        $params = [];
        $EmailArr = [];

        foreach($iApplyLPDEDataArr as $counter => $LPDEData){
            $EmailArr[$counter] = $LPDEData['LPDE_Email'];        
        }
        echo "LPDE - Email Details<br>";
        //echo json_encode($EmailArr).'<br>';
        echo '<pre>';print_r($EmailArr);echo '</pre>';
        
        try{       
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );      
            $sql = 'EXEC spWebPreferredDeliveryMethodLiquor ';
                
            $params['LicenceNumber'] = (int)$LicenceNumber;
            $params['LiquorEmail'] = json_encode($EmailArr);     
                
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);
                    $sqlParams[] = "@$key = null";
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            echo "adding LPDE data to LOGIC<hr>";
        }catch (PDOException $e) {
            error(400, "<br>addLPDEDataToLOGIC 2 - Error connecting to LOGIC - ". $e->getMessage());
        } 
    } // end of addLPDEDataToLOGIC function

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
        //echo "FT_Amount = " . $FT_Amount.'<br>';
        if($FT_Amount > 0) {
            $APP_ApplicFee = $iApplyFormData['Application']['data']['APP_ApplicFee'];
            
            // get application details we need
            $FT_ReceiptNumber = $iApplyFormData['Application']['data']['APP_ReceiptCode']; 
            //echo "FT_ReceiptNumber = ". $FT_ReceiptNumber. '<br>';
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
            $params['FT_TransactionNumber'] = $FT_TransactionNumber; 
            $params['FT_ReceiptNumber'] = $FT_ReceiptNumber;
            $params['FT_AmountPaid'] = $FT_Amount;
            $params['FTFI_Items'] = $FTFI_Items;

            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            $sql .= join(', ', $sqlParams);

            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
		    $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		    $sth->execute($params);
		    $results = $sth->fetchAll(PDO::FETCH_ASSOC);
		    if (sizeof($results)>0) {
			    echo "Data Passed to FinancialTransaction TBL & FinancialTransactionFeeItem TBL in LOGIC DB - FT_ID({$results[0]['FT_ID']})<hr>";
                return $results[0]['FT_ID'];
            } else {
			    error(400, "1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
		    }
	    } catch (PDOException $e) {
		    error(400, "2 Error connecting to LOGIC - ". $e->getMessage());
	    } 
    } // end of insertFinancialTransactionIntoLogic function

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
    } //end of constructFTFI_ItemsArr function

    // adding values to Objections and ApplicationObjection table in LOGIC
    function addObjections($ApplicationData){
        global $drv, $svr, $db, $un, $pw;
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebAddObjections ';
        
            $params['OBJ_ObjectorName']  = $ApplicationData['Application']['data']['OBJ_ObjectorName'];
            $params['OBJ_ObjDate'] = date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['OBJ_ObjDate']),'Y-m-d');
            $params['OBJ_Phone'] = $ApplicationData['Application']['data']['OBJ_Phone'];
            $params['OBJ_Address1'] = $ApplicationData['Application']['data']['OBJ_Address1'];
            $params['OBJ_Address2'] = $ApplicationData['Application']['data']['OBJ_Address2'];
            $params['OBJ_Town'] = $ApplicationData['Application']['data']['OBJ_Town'];
            $params['OBJ_Postcode'] = $ApplicationData['Application']['data']['OBJ_Postcode'];
            $params['OBJ_State'] = $ApplicationData['Application']['data']['OBJ_State'];
            $params['OBJ_InterventionOrObjection'] = $ApplicationData['Application']['data']['OBJ_InterventionOrObjection'];
            $params['OBJ_Notes'] = $ApplicationData['Application']['data']['OBJ_Notes'];
            $params['OBJ_RC_ID'] = $ApplicationData['Application']['data']['OBJ_RC_ID'];
            $params['OBJ_Email'] = $ApplicationData['Application']['data']['OBJ_Email'];
            $params['LastUpdateUser'] = $LastUpdateUser;
            $params['AO_APP_ID'] = $ApplicationData['Application']['data']['AO_APP_ID'];                        
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]); 
                    $sqlParams[] = "@$key = null";   
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo "value for last iapply variable{$lastiApplyApp}<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>addObjections 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>addObjections 2 - Error connecting to LOGIC - ". $e->getMessage());
        }       
    }//  end of addObjections function

    function getATandASIDs($APP_ID){
        global $drv, $svr, $db, $un, $pw;
        $params = [];
        // /SELECT LN_LicenceNumber FROM vwWebLicenceAll WHERE LN_ID = :LN_ID
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "SELECT AS_ID, AT_ID FROM vwWebDecisionAppApplicationData WHERE app_id = :APP_ID";
            
            $params['APP_ID'] = $APP_ID;

            //$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
         
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                return $results;
            } else {
                error(400, "<br> getExistingLicenceNumber 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch(PDOException $e){
            error(400, "<br> getExistingLicenceNumber 2 - Error connecting to LOGIC - ". $e->getMessage());
        }

        
        $dbh = null;
    } // end of getATandASIDs

    //adding note to LOGIC
    function addNoteToLOGIC($ApplicationData,$application_Number){
        global $drv, $svr, $db, $un, $pw;

        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

            $sql = 'exec spWebAddNote ';
	    
		    $params['Content'] = $ApplicationData['Application']['data']['NT_FullNote'];
            $params['NT_IsSensitive'] = $ApplicationData['Application']['data']['NT_IsSensitive']; 
            $params['OBJT_Code'] = $ApplicationData['Application']['data']['OBJT_Code'];
            if($params['OBJT_Code'] == 'LLM'){
                $params['NT_OBJ_ID'] = $ApplicationData['Application']['data']['LIC_ID'];
            }else if($params['OBJT_Code'] == 'LAM'){
                $params['NT_OBJ_ID'] = $ApplicationData['Application']['data']['APP_ID'];
                getDocumentsFromAdd_Notes_Document_Form($ApplicationData,$application_Number);
            }
		    
            $params['NTS_Code'] = $ApplicationData['Application']['data']['NTS_Code'];
		    $params['NTYP_Code'] = $ApplicationData['Application']['data']['NTYP_Code'];		    
            $params['AU_Name_Allocated'] = $ApplicationData['Application']['data']['AU_Name_Allocated'];
            $params['NT_FollowUpDate'] = $ApplicationData['Application']['data']['NT_FollowUpDate'];
            $params['AU_Name'] = $ApplicationData['Application']['data']['AU_Name'];
            $params['Notification'] = 'Y';
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
     
            echo "Data Passed to Note tbl in LOGIC DB<br>";
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            try{
                $results = $sth->fetchAll(PDO::FETCH_ASSOC);
                echo "Adding note to LOGIC Note table Successfull<br>";
            }catch(PDOException $e){
                error(400, "<br>1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "<br>2 Error connecting to LOGIC - ". $e->getMessage());
        }
    }

    //this function is used to upload documents from Add Notes/Document Form not from direct iApply form submission
    function getDocumentsFromAdd_Notes_Document_Form($ApplicationData,$application_ID){
        global $APP_ID;
        $ControlName = json_decode($ApplicationData['Application']['data']['Attachments_Integration'], true);
        $DocArr = $ApplicationData['Application']['data']['Upload_Documents'];
        $AT_Prefix = $ApplicationData['Application']['data']['AT_Prefix'];
        /*echo "<pre>";
        echo "Attachments_Integrations<br>";
        echo print_r($ControlName,true); echo '<hr>';   
    
        echo "<pre>";
        echo "Upload_Documents array<br>";
        echo print_r($DocArr, true);echo '<hr>';*/

        foreach($DocArr as $counter => $Doc){
            $json = getUploadedFile($application_ID, $Doc['upload_document'], $ControlName[0]['name']);
            //echo '<pre>';print_r($json);echo '</pre>';
            $subject = $ApplicationData['Application']['data']['Upload_Documents'][$counter]['DOC_Subject'];
            $APP_ID_array[0]['APP_ID'] = $ApplicationData['Application']['data']['APP_ID'];
            addAttachmentsToLOGICDB($ApplicationData,$Doc['DT_ID'],$json['File']['Filename'],$json['File']['Data'],$ControlName[0]['name'],$APP_ID_array,$AT_Prefix,$Doc['DOC_Notes'],$Doc['DOC_ValidToDate'],$subject);
            echo '<hr>';
        }
        
    }//end of function

    //curl to get the application data as JSON format from iApply JSON API 
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
        if (!isset($GLOBALS['time'])) {
            $GLOBALS['time'] = time(); echo 'STARTING...';
        }

        echo "{$i} attempts to get the application data<br>" . (time() - $GLOBALS['time']) . ' seconds<br>'; 
        $GLOBALS['time'] = time();
        $json = json_decode(strip_tags($output), true);
        if ($json['Success']==1) {
            return $json;
        }else{
            echo "json from iApply API fails in curlToiApplyAPI function<br>";
        }
    }// end of curlToiApplyAPI function
    
    function updateLotteryDataToFinancialStatements($FS_ID,$FS_DateReceived,$FS_GrossProceeds,$FS_NettProceeds,$FS_AmountDistributed,$FS_Notes,$FS_UpdatedOnline){
        global $drv, $svr, $db, $un, $pw;
       
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebGetLotteryDetails ';
                          
            $params['FS_DateReceived'] = $FS_DateReceived;
            $params['FS_GrossProceeds'] = $FS_GrossProceeds;
            $params['FS_NettProceeds'] = $FS_NettProceeds;
            $params['FS_AmountDistributed'] = $FS_AmountDistributed;
            $params['FS_Notes'] = $FS_Notes;
            $params['FS_UpdatedOnline'] = $FS_UpdatedOnline;
            $params['FS_ID'] = $FS_ID;
            $params['Switch'] = 'UpdateFS';
                            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            $sql .= join(', ', $sqlParams);

            /*echo "<br>updated in Financial Statements table in LOGIC<br>";
            echo $sql.'<br>'; 
            echo '<pre>';print_r($params); echo '<br>';*/
            
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                echo '<pre>';print_r($results);echo '</pre>';
                return $results;
            } else {
                error(400, "<br>updateLotteryDataToFinancialStatements 1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>updateLotteryDataToFinancialStatements 2 Error connecting to LOGIC - ". $e->getMessage());
        }
    }

    function updateLotteryDataToWinnersList($WL_LAPP_ID,$WL_ReceivedDate){
        global $drv, $svr, $db, $un, $pw;
       
        try {
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = 'EXEC spWebGetLotteryDetails ';
                  
            $params['WL_LAPP_ID'] = $WL_LAPP_ID;
            $params['WL_ReceivedDate'] = $WL_ReceivedDate;
            $params['Switch'] = 'UpdateWL';
                            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]);       
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
            $sql .= join(', ', $sqlParams);

            //echo "updated in WinnersList table in LOGIC<br>";
            //echo $sql; print_r($params); echo '<br>';
        
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (sizeof($results)>0) {
                echo '<pre>';print_r($results);echo '</pre>';
                return $results;
            } else {
                error(400, "<br>updateLotteryDataToWinnersList 1 Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        } catch (PDOException $e) {
            error(400, "<br>updateLotteryDataToWinnersList 2 Error connecting to LOGIC - ". $e->getMessage());
        }
    }

    //adding all the data to LOGIC CCS_Complaint table
    function addCCS_ComplaintToLOGIC($ApplicationData){
        global $drv, $svr, $db, $un, $pw, $LastUpdateUser;
      
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

            $sql = 'EXEC spWebAddCCSTablesToLOGIC ';
	    
            if(isset($ApplicationData['Application']['data']['IsOpen']) || !is_null($ApplicationData['Application']['data']['IsOpen'])){
                $params['DateReceived'] = date_format(date_create_from_format('d/m/Y', $ApplicationData['Application']['data']['DateReceived']),'Y-m-d ');
                $params['Description'] = $ApplicationData['Application']['data']['Description']; 
                $params['IsOpen'] = $ApplicationData['Application']['data']['IsOpen'];
                $params['CB_ID_CCS_ComplaintType'] = $ApplicationData['Application']['data']['CB_ID_CCS_ComplaintType'];
                $params['CB_ID_CCS_Source'] = $ApplicationData['Application']['data']['CB_ID_CCS_Source'];		    
                $params['CB_ID_CCS_FileLocation'] = $ApplicationData['Application']['data']['CB_ID_CCS_FileLocation'];
                $params['TraderContactName'] = $ApplicationData['Application']['data']['TraderContactName'];
                $params['TraderBusinessName'] = $ApplicationData['Application']['data']['TraderBusinessName'];
                $params['TraderMobile'] = $ApplicationData['Application']['data']['TraderMobile'];
                $params['TraderEMail'] = $ApplicationData['Application']['data']['TraderEMail'];
                $params['CB_ID_CCS_LicenceRequirement'] = $ApplicationData['Application']['data']['CB_ID_CCS_LicenceRequirement'];
                $params['LastUpdateUser'] = $LastUpdateUser;
                $params['Switch'] = 'CCS_Complaint';
            }else{
                echo "value for IsOpen is not set in the iApply JSON (Not Null Constraint [LOGIC])<br>";
            }   

            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]); 
                    $sqlParams[] = "@$key = null";   
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['Complaint_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>CCS_Complaint 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "<br>CCS_Complaint 2 Error connecting to LOGIC - ". $e->getMessage());
        }
    } // end of addCCS_ComplaintToLOGIC

    //adding all the data to LOGIC CCS_Complaint table
    function addCCS_ConsumerToLOGIC($ApplicationData, $ComplaintID){
        global $drv, $svr, $db, $un, $pw, $LastUpdateUser;
               
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

            $sql = 'EXEC spWebAddCCSTablesToLOGIC ';
	    
		    $params['FullName'] = $ApplicationData['Application']['data']['FullName'];
            $params['Mobile'] = $ApplicationData['Application']['data']['Mobile']; 
            $params['EMail'] = $ApplicationData['Application']['data']['EMail'];
            $params['ComplaintID'] = $ComplaintID;
            $params['LastUpdateUser'] = $LastUpdateUser;
            $params['IsOpen'] = 1;
            $params['Switch'] = 'CCS_Consumer';
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]); 
                    $sqlParams[] = "@$key = null";   
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['Consumer_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>CCS_Consumer 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "<br>CCS_Consumer 2 Error connecting to LOGIC - ". $e->getMessage());
        }
    } // end of addCCS_ConsumerToLOGIC

    //get product id from CCS_PRoduct table in LOGIC
    function getCCS_ProductID($ProductCode){
        $params = [];
        global $drv, $svr, $db, $un, $pw;
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "SELECT ID FROM vwWebCCS_Product WHERE ProductCode = :ProductCode";

            $params['ProductCode'] = $ProductCode;
            
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                return $results[0]['ID'];
            } else {
                error(400, "<br> getCCS_ProductID 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch(PDOException $e){
            error(400, "<br> getCCS_ProductID 2 - Error connecting to LOGIC - ". $e->getMessage());
        }

        $dbh = null;
    } // end of getCCS_ProductID()

    //get practice id from CCS_Practice table in LOGIC
    function getCCS_Practice($PracticeCode){
        $params = [];
        global $drv, $svr, $db, $un, $pw;
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "SELECT ID FROM vwWebCCS_Practice WHERE Code = :PracticeCode";

            $params['PracticeCode'] = $PracticeCode;
            
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                return $results[0]['ID'];
            } else {
                error(400, "<br> getCCS_Practice 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch(PDOException $e){
            error(400, "<br> getCCS_Practice 2 - Error connecting to LOGIC - ". $e->getMessage());
        }

        $dbh = null;
    }// end of getCCS_Practice

    //adding all the data to LOGIC CCS_Complaint table
    function addCCS_ComplaintProductPracticeToLOGIC($ApplicationData, $ComplaintID){
        global $drv, $svr, $db, $un, $pw, $LastUpdateUser;
        $ProductCode = $ApplicationData['Application']['data']['ProductCode'];
        $PracticeCode = $ApplicationData['Application']['data']['PracticeCode'];
       
        try{
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );

            $sql = 'EXEC spWebAddCCSTablesToLOGIC ';
	    
            $params['ProductID'] = getCCS_ProductID($ProductCode);
            $params['PracticeID'] = getCCS_Practice($PracticeCode); 
            $params['ISPrimary'] = 1;
            $params['ComplaintID'] = $ComplaintID;                
            $params['IsOpen'] = 1;
		    $params['Switch'] = 'CCS_ComplaintProductPractice';
            $params['LastUpdateUser'] = $LastUpdateUser;
            
            $sqlParams = [];
            foreach ($params as $key => $value) {
                if (strlen($value) == 0) {
                    unset($params[$key]); 
                    $sqlParams[] = "@$key = null";   
                } else {
                    $sqlParams[] = "@$key = :$key";
                }
            }
        
            $sql .= join(', ', $sqlParams);
            //echo $sql."<br>";
            //echo '<pre>';print_r($params);echo '<pre>';
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['CCS_ComplaintProductPractice_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>CCS_ComplaintProductPractice 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }
        }catch (PDOException $e) {
            error(400, "<br>CCS_ComplaintProductPractice 2 Error connecting to LOGIC - ". $e->getMessage());
        }
    } // end of addCCS_ComplaintProductPracticeToLOGIC


    //error fucntion
    function error ($code, $message) {
        http_response_code($code);
        die("FATAL ERROR ($message)");
    }//end of error function
?>
