<?php
     /*
        Start Date : 08/04/2021
        Developer : Achira Warnakulasuriya

        PROD / UAT path for php files
        FileZilla Path UAT - /home/www/htdocs/LGPubReg/UAT/iApply
        FileZilla Path PROD -  /home/www/htdocs/LGPubReg/iApply
        FileZilla Path APIs - /home/www/htdocs/OccLicPubReg/iApply/TEST

        SQL queries for test in LOGIC
        for LSA Json
        select * from LicenceSystemApplications where LSA_ID in(25137,25138,25139) 

        for LSL json
        select * from LicenceSystemLicences where LSL_ID = 23550
        
        for Finantial Transaction in LOGIC -
        select * from FinancialTransaction ft
        inner join FinancialTransactionFeeItem fft on ft.FT_ID = fft.FTFI_FT_ID
        where ft.FT_ID = 2165765

        to check email html generated code in LOGIC
        select top 10 * from LGO_NotificationQueue order by NQ_ID desc

        to check attachments and docgens in LOGIC
        select top 10 * from Documents order by DOC_Id desc;

        to check weather a perticular store procedure is created in sql management
        SELECT name, OBJECT_DEFINITION(object_id)
        FROM sys.procedures 
        WHERE OBJECT_DEFINITION(object_id) LIKE '%IFR_Investigation%'

    */

    require('CommonFunctions.php');
     
    //check GET parameters have been set appropriately
    if (!isset($_GET['APP_ID'])) {
         error(400, 'No APP_ID provided');
    }
 
    $APP_ID = $_GET['APP_ID']; //getting APP_ID
 
    //validating the APP_ID
    if (strlen($APP_ID)!=24) {
         error(400, 'Invalid APP_ID');
    }
 
    //list of supported iApply application form IDs
    $RelocateLicense = '5dcaa259ad9c5b43c0649f7e'; // Application for Relocate a Licence Form AKA - Move a Licence
    $LiquorLicenceFeeWaiver  = '608107aead9c5837d873c80b'; //Application for Liquor Licence Fee Waiver form
    $ChangeAlterRedefineLicensePremises = '5dcb7d04ad9c5c1ab068f976'; // Application for Change, Alter Redefine Extend the License Premises Form
    $ApplicationNotes = '61148326ad9c5b4cecf418af'; // to get Application notes
    $InterstateDirectSalesLicence = '61415831ad9c5a24502f9baf'; // Application for an Interstate Direct Sales Licence
    $MakeSubmission = '5dcdd01aad9c593d38b9912a'; //Lodge a Submission
    $LodgeFinancialStatement = '6181cc14ad9c5867fc531c99'; // lodge a financial statement
    $AlterReplaceOdometer = '59639f9bad9c5a3aa8f4652a'; //Application to alter or replace an odometer 
    $EmployementofMinor = '621d7030ad9c597e1c7a00b3'; // Employement of minor on licensede premises iApply form
    $VaryTradingRights = '62046241ad9c581f4083f0cb'; // Vary Trading Rights iApply form
	$NotifyTheCommissionerChangeDetails = '62302596ad9c5a6978ebe33b'; //Notify the Commissioner of a change in details iApply form
    $ReviewDecisionWithholdWinnings = '62b16397ad9c5c838c630062';// old form id = 5f4c8932ad9c582d1485f12c'; // Application for review of Decision to withhold winnings iApply form
    /*$OfferToSellGamingMachineEntitlementsClubOne = '5a1634f7ad9c5a07f06d1496'; // offer to sell gaming machine entitlements Club One
    $OfferToPurchaseGamingMAchineEntitlementsCasino = '59edf09bad9c5a07f060e1d3'; // Offer to purchase gaming machine entitlements - casino
    $OfferToSellGamingMachineEntitlements = '59ed48d9ad9c5a823c3a9234'; //offer to sell gaming machine entitlements
    $OfferToPurchaseGamingMachineEntitlements = '59e9eaeead9c5a211c5d4d9a'; // offer to purchase gaming machine entitlements*/
    $WageringSystemEquipment = '5f59c3b2ad9c5930bc39d3a8'; //Application for the approval of wagering systems or equipment
    $ConsumerComplaints = '62848e20ad9c5a99b42122df'; //Consumer Complaints 
     
    $CaseManagerID = ''; // store case manager ID for file allocation
    $DelegateID = ''; // store delegae ID for hearings    
    $lastiApply = 0; //setting last iapply value to 0
    $APP_LIC_ID = ''; // to store LIC id
    $NewLicenNumber = 0; // new licence number
    $EntityRepeaterArr = []; //to store entity values

    //getting iApply JSON from iApply API
    $iApplyAppFormData = getiApplyApplicationJSON($APP_ID);
    //echo "<strong>iApply Data for APP_ID - {$APP_ID}</strong><br>";
    //echo "<pre>";echo print_r($iApplyAppFormData,true);echo "<hr>";
    
    //assigning all iApply App data into temp array
    $tempiApplyFormData = $iApplyAppFormData;
    
    //displaying the correct iApply form name 
    if(isset($iApplyAppFormData['Application']['data']['Form_code'])){
        echo "<strong>{$iApplyAppFormData['Application']['data']['Form_name']} ({$iApplyAppFormData['Application']['data']['Form_code']})</strong><br><hr>";
    }else{
        echo "<strong>{$iApplyAppFormData['Application']['data']['Form_name']} </strong><br><hr>";
    }
    
    //checking weather AS_ID and AT_ID are set in iApply json
    if(isset($tempiApplyFormData['Application']['data']['AS_ID']) && isset($tempiApplyFormData['Application']['data']['AT_ID'])){
        
        //validating whether AS_ID and AT_ID are json or not before constructing AT_ID & AS_ID array
        if(isJson($tempiApplyFormData['Application']['data']['AS_ID']) && isJson($tempiApplyFormData['Application']['data']['AT_ID'])){
            $AT_ID_Arr = json_decode($tempiApplyFormData['Application']['data']['AT_ID']);
            $AS_ID_Arr = json_decode($tempiApplyFormData['Application']['data']['AS_ID']);
            //echo "valid json for AS_Id and AT_ID<br>";
        }else{
            //echo "Converted stupid AS_ID and AT_ID arrays into valid json<br>";
            $AS_ID_Arr = convertStupidStringsToArray($tempiApplyFormData['Application']['data']['AS_ID']);
            $AT_ID_Arr = convertStupidStringsToArray($tempiApplyFormData['Application']['data']['AT_ID']);
        }
                  
        //$AT_ID_Arr = array(119,120,235,121);//235,6
        //$AS_ID_Arr = array(0,1,2,3);//1,1,2,1,1
        echo "AS_ID Details in iApply JSON<br>";
        echo '<pre>';print_r($AS_ID_Arr);echo '</pre>';
        echo "AT_ID Details in iApply JSON<br>";
        echo '<pre>';print_r($AT_ID_Arr);echo '<hr>';
         
        if($tempiApplyFormData['Application']['formId'] == $InterstateDirectSalesLicence){
            $ATASIDs = construct_ATIDs_ASIDs_Array($AT_ID_Arr,$AS_ID_Arr);
            $AT_ID_Arr = $ATASIDs[0];
            $AS_ID_Arr = $ATASIDs[1];
        
            //echo "common value in both array is {$CommonAT_ID}<br>";
            echo "Final AS_ID Details<br>";
            echo '<pre>';print_r($AS_ID_Arr);echo '<br>';
            echo "Final AT_ID Details<br>";
            echo '<pre>';print_r($AT_ID_Arr);echo '<hr>';
        }
        
        $AT_ID_ArrSize = count($AT_ID_Arr);
        
        //check if Application is having more than 1 application types to get Applinkgroup ID
        if($AT_ID_ArrSize > 1){
            $AppGroupID  =  getAppGroupID();
            echo "GRP_ID (AppLinkGroup) = {$AppGroupID[0]['GRP_ID']}<hr>";            
        } else{
            echo "Single Application No GRP_ID (AppLinkGroup)<hr>";
        }

    }else{
        echo "AS_ID and AT_ID are not set in iApply JSON<hr>";
    }
   
    //checking APP_LIC_ID is set or not
    if(isset($tempiApplyFormData['Application']['data']['APP_LIC_ID'])){
        $APP_LIC_ID = $tempiApplyFormData['Application']['data']['APP_LIC_ID'];
        echo "APP_LIC_ID - {$tempiApplyFormData['Application']['data']['APP_LIC_ID']}<hr>";
    }else{
        echo "APP_LIC_ID is not set in iApply JSON<hr>";
    }
    
    //checking OBJT_Code is set or not
    if(!(isset($tempiApplyFormData['Application']['data']['OBJT_Code']))){
        array_push($tempiApplyFormData['Application']['data']["OBJT_Code"] = "LAM");
        echo "Manually Assigned OBJT_Code (N/A in iApply Data)- {$tempiApplyFormData['Application']['data']['OBJT_Code']}<hr>";
    }else{
        echo "OBJT_Code Avilable in iApply JSON- {$tempiApplyFormData['Application']['data']['OBJT_Code']}<hr>";
    }
    //echo '<pre>';print_r($tempiApplyFormData);echo '</pre>';
    
    //catching correct form according to form id in iApply form json
    switch ($iApplyAppFormData['Application']['formId']) {
        
        //catching the correct iApply form based on formid
        case $ChangeAlterRedefineLicensePremises:
        case $RelocateLicense:
        case $EmployementofMinor:
        case $NotifyTheCommissionerChangeDetails:
        case $ReviewDecisionWithholdWinnings:
        /*case $WageringSystemEquipment:
        //case $VaryTradingRights:// need to commented only in PROD
		case $OfferToPurchaseGamingMachineEntitlements:
        case $OfferToPurchaseGamingMAchineEntitlementsCasino:
        case $OfferToSellGamingMachineEntitlements:
        case $OfferToSellGamingMachineEntitlementsClubOne:*/
            
            //looping through all the available applications from iApply form submission
            for($counter = 0; $counter < $AT_ID_ArrSize; $counter++){
                echo "<strong>AS_ID Number ({$AS_ID_Arr[$counter]}) & AT_ID Number ({$AT_ID_Arr[$counter]})</strong><br>";
                
                //assigning AS_ID & AT_ID values to tempiApply arr
                $tempiApplyFormData['Application']['data']['AS_ID'] = $AS_ID_Arr[$counter];
                $tempiApplyFormData['Application']['data']['AT_ID'] = $AT_ID_Arr[$counter]; 
                
                //sending attachements with first Application, assigning same applinkgroup id, casemanager id and delegate id for file allocation to the rest of the applications
                if($counter === 0){   
                     //reassign lastiapply to 1 if aT_ID array has 1 value
                     if($AT_ID_ArrSize == 1){
                        $lastiApply = 1;
                        //echo "lastiApply - {$lastiApply}<br>";
                    }

                    //add iApply data to Applications in LOGIC DB
                    //echo "lastiApply - {$lastiApply}<br>"; 
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppGroupID,'','',$lastiApply,$APP_LIC_ID);
                    $CaseManagerID = $ApplicationsTBLData[0]['AU_Name'];
                    $DelegateID = $ApplicationsTBLData[0]['APP_Delegate_AU_ID'];
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";

                    //sending upload attachments to LOGIC DB to process 
                    processAttachments($tempiApplyFormData,$ApplicationsTBLData);
                   
                }else if(($AT_ID_ArrSize > 1) && ($counter == $AT_ID_ArrSize-1)){
                    $lastiApply = 1;
                    //echo "App Details for last Application if single form has more than 1 Application to LOGIC / lastiApply value - ({$lastiApply})<br>";
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppGroupID,$CaseManagerID,$DelegateID,$lastiApply,$APP_LIC_ID); //$allocato
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
                }else{
                    //echo "Adding App Details for rest of the applications excluding 1st and last applications to LOGIC / lastiApply value {$lastiApply}<br>";
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppGroupID,$CaseManagerID,$DelegateID,$lastiApply,$APP_LIC_ID); //$allocato
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
                } // end of else condition

                //generates json string for LicenceSystemApplications in LOGIC DB
                $JsonStrForLicenSysAppTBL = createJSONForLicenceSystemApplicationsTBL($ApplicationsTBLData,$tempiApplyFormData,$APP_LIC_ID,$NewLicenceDetails,$LicenseeData,$EntityRepeaterArr,$ENT_IDArr);
                //echo "JSON string for LicenceSystemApplications TBL <br>";
                //echo "<pre>";print_r($JsonStrForLicenSysAppTBL);echo '<br><br>';
                     
                //add iapply data and json string to LicenceSystemApplications in LOGIC DB
                $LicenseSysAppTBLData = addLicenceSystemApplicationsTBL($JsonStrForLicenSysAppTBL,$APP_LIC_ID,$ApplicationsTBLData);
                //echo "<br>Values in LicenceSystemApplications TBL";
                echo "<pre>";print_r($LicenseSysAppTBLData);echo "<hr>";             
                     
                //process docgen files from iApply
                processDocGen($tempiApplyFormData,$ApplicationsTBLData);
                
                //sending data to ApprovalGamesMachines table in LOGIC if form is Application for the approval of wagering systems or equipment
                if($iApplyAppFormData['Application']['formId'] == $WageringSystemEquipment){
                    $ApprovalGamesMachineTBLData =  addApprovalGamesMachinesToLOGIC($tempiApplyFormData,$ApplicationsTBLData);  
                } 

            } // end of for loop

            //check if Application has a Application Fee to pay
            if($tempiApplyFormData['Application']['data']['APP_ApplicFee'] > 0){
 
                // get the total amount paid by the user, it's not included in the BPoint payment details to be uniform across every form 
                $FT_Amount = $tempiApplyFormData['Application']['data']['APP_ApplicFee']; 
                $FeeType = $tempiApplyFormData['Application']['data']['Fee_types'];

                //constructing FeeTypes Array 
                if(isJson($FeeType)){
                    $FeeTypesArr = json_decode($FeeType, true);                                     
                }else{
                    $FeeTypesArr = convertStupidStringsToArray($FeeType);
                } 
                
                //sending Fee Types array to construct FT_FI_Items Array
                $FT_FI_ItemsArr = constructFTFI_ItemsArr($FeeTypesArr);
                echo "Fee Types Details<br>";
                echo '<pre>';echo print_r($FeeTypesArr,true);echo '<hr>';
                echo "FT_FIArr Array Details <br>";
                echo "<pre>";print_r($FT_FI_ItemsArr);echo "<hr>";
 
                //get Application Fee and adding the financial transactions details to LOGIC DB 
                getApplicationFeeFromiApply($FT_Amount,$tempiApplyFormData,$FT_FI_ItemsArr);
                //echo '<hr>';

            }else{
                echo "<strong>No Financial Transaction is allocated to {$iApplyAppFormData['Application']['data']['Form_name']}({$iApplyAppFormData['Application']['data']['Form_code']}) iApply Form</strong><br><hr>";
            }// end of if else condition

        break;

        case $InterstateDirectSalesLicence:         
                     
            //looping through all the available applications from iApply form submission
            for($counter = 0; $counter < $AT_ID_ArrSize; $counter++){
                echo "<strong>Integration is for Application({$counter}) - AS_ID Number ({$AS_ID_Arr[$counter]}) & AT_ID Number ({$AT_ID_Arr[$counter]})</strong><br>";
                
                 //assigning AS_ID & AT_ID values to tempiApply arr
                $tempiApplyFormData['Application']['data']['AS_ID'] = $AS_ID_Arr[$counter];
                $tempiApplyFormData['Application']['data']['AT_ID'] = $AT_ID_Arr[$counter]; 
                 
                //sending attachements with first Application, assigning same applinkgroup id, casemanager id and delegate id for file allocation to the rest of the applications
                if($counter === 0){   
                     //reassign lastiapply to 1 if aT_ID array has 1 value
                     if($AT_ID_ArrSize == 1){
                         $lastiApply = 1;
                         //echo "lastiApply - {$lastiApply}<br>";
                     }
                     
                    //Creating a licence record if iApply form doesn't generate one
                    if(($AT_ID_Arr[$counter] == $CommonAT_ID)){
                        echo "creating new licence for AT_ID = {$CommonAT_ID}<br>";
                        $NewLicenceDetails = createNewLicence($tempiApplyFormData);
                        $APP_LIC_ID = $NewLicenceDetails[0]['LIC_ID'];
                        $NewLicenNumber = $NewLicenceDetails[0]['LN_LicenceNumber'];
                        echo 'New Licence Details<br>';
                        echo '<pre>';print_r($NewLicenceDetails);echo '<br>';
 
                        $LicencePostalDetails = addNewLicencePostalDetails($tempiApplyFormData,$APP_LIC_ID);
                        echo "<br>LicencePostalDetails details";
                        echo "<pre>";print_r($LicencePostalDetails);echo "<hr>";
 
                    }else{
                        $APP_LIC_ID = $tempiApplyFormData['Application']['data']['APP_LIC_ID'];
                        echo "No New Licence Created (App LIC # - {$APP_LIC_ID})<hr>";
                        //echo "<strong>Don't Need to create a New Licence</strong>";
                    }                    
                     
                    //add iApply data to Applications in LOGIC DB
                    echo "App Detailes for AT_ID - {$AT_ID_Arr[$counter]} to LOGIC (lastiApply - {$lastiApply})<br>";                    
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppGroupID,'','',$lastiApply,$APP_LIC_ID);
                    $CaseManagerID = $ApplicationsTBLData[0]['AU_Name'];
                    $DelegateID = $ApplicationsTBLData[0]['APP_Delegate_AU_ID'];
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
 
                    //checking wether Entities are declared in iApply form
                    if(isset($tempiApplyFormData['Application']['data']['Entity_repeater'])){
                        $EntityRepeaterArr = $tempiApplyFormData['Application']['data']['Entity_repeater'];
                        $ENT_IDArr = array(); 
 
                        //echo '<pre>';print_r($tempEntityRepeater);echo '<hr>';
                        foreach($EntityRepeaterArr as $ER){
                            $ENT_ID = getEntityIDs($ER,$tempiApplyFormData);
                            array_push($ENT_IDArr,$ENT_ID);                                   
                        }
 
                        echo 'ENT IDs<br><pre>';print_r($ENT_IDArr);echo '</pre>';
                        echo '<hr>';
                        $LicenseeData = addLicensees(json_encode($ENT_IDArr),$tempiApplyFormData,$APP_LIC_ID);
                        echo 'Licensee Details<br>';
                        echo '<pre>';print_r($LicenseeData);echo '</pre>';
                        echo '<hr>';
 
                        //generates json string for LicenceSystemApplications in LOGIC DB
                        $JsonStrForLicenSysLicenseeTBL = createJSONForLicenceSystemLicences($APP_LIC_ID,$LicenseeData,$NewLicenceDetails,$ENT_IDArr,$ApplicationsTBLDataArr,$tempiApplyFormData);
                        //echo "JSON string for LicenceSystemLicensee TBL <br>";
                        //echo "<pre>";print_r($JsonStrForLicenSysLicenseeTBL);echo '<br><br>';
                         
                        //add iapply data and json string to LicenceSystemApplications in LOGIC DB
                        $LicenseSysLicencesTBLData = addLicenceSystemLicencesTBL($JsonStrForLicenSysLicenseeTBL,$APP_LIC_ID,$NewLicenceDetails,$tempiApplyFormData);
                        echo "<br>Values in LicenceSystemLicensee TBL";
                        echo "<pre>";print_r($LicenseSysLicencesTBLData);echo "<hr>";                       
                    }else{
                        echo "No Entity,Licensee,LSL_JSON details (<strong>iApply form name - {$iApplyAppFormData['Application']['data']['Form_name']}</strong>) is available in iApply respond JSON<hr>";
                    }                    

                    //sending upload attachments to LOGIC DB to process 
                    processAttachments($tempiApplyFormData,$ApplicationsTBLData,$OBJT_Code);
                              
                }else if(($AT_ID_ArrSize > 1) && ($counter == $AT_ID_ArrSize-1)){
                    $lastiApply = 1;
                    echo "App Details for last Application if single form has more than 1 Application to LOGIC / lastiApply value - ({$lastiApply})<br>";
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppGroupID,$CaseManagerID,$DelegateID,$lastiApply,$APP_LIC_ID); //$allocato
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
                }else{
                    echo "Adding App Details for rest of the applications excluding 1st and last applications to LOGIC / lastiApply value {$lastiApply}<br>";
                    $lastiApply = 0;
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppGroupID,$CaseManagerID,$DelegateID,$lastiApply,$APP_LIC_ID); //$allocato
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
                } // end of else condition             
                 
                //generates json string for LicenceSystemApplications in LOGIC DB
                $JsonStrForLicenSysAppTBL = createJSONForLicenceSystemApplicationsTBL($ApplicationsTBLData,$tempiApplyFormData,$APP_LIC_ID,$NewLicenceDetails,$LicenseeData,$EntityRepeaterArr,$ENT_IDArr);
                //echo "JSON string for LicenceSystemApplications TBL <br>";
                //echo "<pre>";print_r($JsonStrForLicenSysAppTBL);echo '<hr>';
                 
                //add iapply data and json string to LicenceSystemApplications in LOGIC DB
                $LicenseSysAppTBLData = addLicenceSystemApplicationsTBL($JsonStrForLicenSysAppTBL,$APP_LIC_ID,$ApplicationsTBLData);
                echo "<br>Values in LicenceSystemApplications TBL";
                echo "<pre>";print_r($LicenseSysAppTBLData);echo "<hr>";             
                 
                //process docgen files from iApply
                processDocGen($tempiApplyFormData,$ApplicationsTBLData,$OBJT_Code);               
                         
            }// end of for loop 
 
             //getting Licence Postasl Details Email table data from iapply form
             if(isset($tempiApplyFormData['Application']['data']['LOGIC_LPDE_Data']) && sizeof($tempiApplyFormData['Application']['data']['LOGIC_LPDE_Data']) > 0){
                 echo "Licence Postal Details Emails For - (<strong>{$iApplyAppFormData['Application']['data']['Form_name']}</strong>) <br>";
                 $LicenceNumber;
                 
                 if($NewLicenNumber == 0){
                     $LicenceNumber = getExistingLicenceNumber($ApplicationsTBLData[0]['LN_ID'])[0]['LN_LicenceNumber'];
                     echo "Existing licence number - {$LicenceNumber}<br>";
                 }else{
                     //echo '<pre>';print_r($LicenceNumber);echo '<br>';
                     $LicenceNumber = (int)$NewLicenNumber; 
                     echo "New licence number {$LicenceNumber}<br>";
                 }
                                 
                 $iApplyLPDEData = $tempiApplyFormData['Application']['data']['LOGIC_LPDE_Data'];
                 addLPDEDataToLOGIC($iApplyLPDEData,$LicenceNumber);                
                 //echo '<pre>';print_r($LPDEArr);echo '<hr>';
             }else{
                 echo "No Licence Postal Details Emails - (<strong>iApply{$iApplyAppFormData['Application']['data']['Form_name']}</strong>) is available in iApply respond JSON<hr>";
             }// end of if condition validation of LOGIC_LPDE_Data array
 
             //check if Application has a Application Fee to pay
             if($tempiApplyFormData['Application']['data']['APP_ApplicFee'] > 0){
 
                 // get the total amount paid by the user, it's not included in the BPoint payment details to be uniform across every form 
                 $FT_Amount = $tempiApplyFormData['Application']['data']['APP_ApplicFee']; 
                 $FeeType = $tempiApplyFormData['Application']['data']['Fee_types'];
 
                 //constructing FeeTypes Array 
                 if(isJson($FeeType)){
                     $FeeTypesArr = json_decode($FeeType, true);                                     
                 }else{
                     $FeeTypesArr = convertStupidStringsToArray($FeeType);
                 } 
                 
                 //sending Fee Types array to construct FT_FI_Items Array
                 $FT_FI_ItemsArr = constructFTFI_ItemsArr($FeeTypesArr);
                 echo "Fee Types Details<br>";
                 echo '<pre>';echo print_r($FeeTypesArr,true);echo '<hr>';
                 echo "FT_FIArr Array Details <br>";
                 echo "<pre>";print_r($FT_FI_ItemsArr);echo "<hr>";
  
                 //get Application Fee and adding the financial transactions details to LOGIC DB 
                 getApplicationFeeFromiApply($FT_Amount,$tempiApplyFormData,$FT_FI_ItemsArr);
                 //echo '<hr>';
 
             }else{
                 echo "<strong>No Financial Transaction is allocated to {$iApplyAppFormData['Application']['data']['Form_name']}({$iApplyAppFormData['Application']['data']['Form_code']}) iApply Form</strong><br><hr>";
             }// end of if else condition
              
        break;
            

        //As per Request by Scott, this is a temporary don't remove comment until he is OKKKKKKK -Comment onjly in PROD not in UAT
        //catching the Application for "make a Submission"  iApply form
        case $MakeSubmission:         
            //sending values to Objections table             
            $ObjectionsDataArr = addObjections($tempiApplyFormData);
            echo '<pre>';print_r($ObjectionsDataArr);echo '</pre>';

            //getting AS_ID and AT_ID numbers and adding them to $tempiApplyFormData
            $AS_AT_ID_Numbers = getATandASIDs($ObjectionsDataArr[0]['AO_APP_ID']);
            $tempiApplyFormData['Application']['data']["AS_ID"] = $AS_AT_ID_Numbers[0]['AS_ID'];
            $tempiApplyFormData['Application']['data']["AT_ID"] = $AS_AT_ID_Numbers[0]['AT_ID'];

            //adding aPP_ID into ApplicationsTBLData for process attachements and docgens
            $ApplicationsTBLData[0]["APP_ID"] = $ObjectionsDataArr[0]['AO_APP_ID'];
            echo '<pre>';print_r($AS_AT_ID_Numbers);echo '<hr>';

            //sending upload attachments to LOGIC DB to process 
            processAttachments($tempiApplyFormData,$ApplicationsTBLData,$OBJT_Code);

            //process docgen files from iApply
            processDocGen($tempiApplyFormData,$ApplicationsTBLData,$OBJT_Code);

            break;        
        
        case $LodgeFinancialStatement:
            //assigning values from iApply json to variables
            $FS_DateReceived = $tempiApplyFormData['Application']['data']['FS_DateReceived'];
            $FS_GrossProceeds = $tempiApplyFormData['Application']['data']['FS_GrossProceeds'];
            $FS_NettProceeds = $tempiApplyFormData['Application']['data']['FS_NettProceeds'];
            $FS_AmountDistributed = $tempiApplyFormData['Application']['data']['FS_AmountDistributed'];
            $FS_Notes = $tempiApplyFormData['Application']['data']['FS_Notes'];
            $FS_UpdatedOnline = $tempiApplyFormData['Application']['data']['FS_UpdatedOnline'];
            $FS_ID = $tempiApplyFormData['Application']['data']['FS_ID'];
            $WL_LAPP_ID = $tempiApplyFormData['Application']['data']['WL_LAPP_ID'];
            $WL_ReceivedDate = $tempiApplyFormData['Application']['data']['WL_ReceivedDate'];
            $OBJT_Code = $tempiApplyFormData['Application']['data']['OBJT_Code'];

            //updating record in Financial Statements table in logic
            updateLotteryDataToFinancialStatements($FS_ID,$FS_DateReceived,$FS_GrossProceeds,$FS_NettProceeds,$FS_AmountDistributed,$FS_Notes,$FS_UpdatedOnline);
            
            //updating record in Winners List table in logic
            updateLotteryDataToWinnersList($WL_LAPP_ID,$WL_ReceivedDate);
            
            echo '<hr>';
            //sending upload attachments to LOGIC DB to process 
            processAttachments($tempiApplyFormData,'');

            //process docgen files from iApply
            processDocGen($tempiApplyFormData,'');

            break;        

        //catching the Application for "Liquor Licence Fee Waiver"  iApply form
        case $LiquorLicenceFeeWaiver:
             echo "<strong>Form ID - </strong> {$iApplyAppFormData['Application']['formId']}<br>";
             
             //add iApply data to Applications in LOGIC DB
             $ApplicationsTBLData = addApplicationToLOGIC($iApplyAppFormData);
             echo "<pre>";print_r($ApplicationsTBLData);echo "</pre>"; 
              
              //Add application order
              $ApplicationOrderTBLData = addApplicationOrderToLOGIC($iApplyAppFormData, $ApplicationsTBLData);
              echo "<pre>";print_r($ApplicationOrderTBLData);echo "</pre>"; 
                  
              //Add Docgen Order document if present
              if(isset($iApplyAppFormData['Application']['data']['Docgen_Integration'])) {
                  echo "<br>Processing an Order document<br>";
                  //Remove Order DocGen from $iApplyAppFormData because we dont want to process it again later
                  $DocGenArray = json_decode($iApplyAppFormData['Application']['data']['Docgen_Integration'], true);
                  echo '<pre>';echo print_r($DocGenArray,true);echo '<hr>';
                  foreach ($DocGenArray as $index => $DocGen) {
                      if($DocGen['name'] == 'Order') {
                          unset($DocGenArray[$index]);
                      }
                  }
                  $DocGenArray = array_values($DocGenArray);
                  echo 'after array_values function<br>';
                  echo '<pre>';echo print_r($DocGenArray,true);echo '<hr>';
                  $iApplyAppFormData['Application']['data']['Docgen_Integration'] = json_encode($DocGenArray);
  
                  //add order to documents table in the 'orders' way
                  $DocumentsTBLData = processOrderDocument($ApplicationOrderTBLData[0]['AOR_ID']);
                  echo "<pre>";print_r($DocumentsTBLData);echo "</pre>"; 
              }
              
              //Process fee adjustments
              $FeeUpdateTBLData = addFeeUpdatesToLOGIC($iApplyAppFormData, $ApplicationOrderTBLData);
              echo "<pre>";print_r($FeeUpdateTBLData);echo "</pre>"; 
              
              //used to process upload attachements from iApply
              processAttachments($iApplyAppFormData,$ApplicationsTBLData);
      
              //process remaining docgen files from iApply
              processDocGen($iApplyAppFormData,$ApplicationsTBLData);
                  
         break;

        case $AlterReplaceOdometer:
            
            $CCS_ComplaintID =  addCCS_ComplaintToLOGIC($tempiApplyFormData);
            $ComplanitID = $CCS_ComplaintID[0]['Complaint_ID'];
            
            echo "<pre>";print_r($CCS_ComplaintID);echo "</pre>";
            array_push($tempiApplyFormData['Application']['data']['ComplaintID'] = $ComplanitID); 
            //echo "<pre>";echo print_r($tempiApplyFormData,true);echo "<hr>";
            //echo $tempiApplyFormData['Application']['data']['ComplaintID'];
            
            $CCS_ConsumerID =  addCCS_ConsumerToLOGIC($tempiApplyFormData,$ComplanitID);
            echo "<pre>";print_r($CCS_ConsumerID);echo "</pre>"; 
                   
            $CCS_ComplaintProductPracticeID =  addCCS_ComplaintProductPracticeToLOGIC($tempiApplyFormData, $ComplanitID);
            echo "<pre>";print_r($CCS_ComplaintProductPracticeID);echo "<hr>"; 

            //used to process upload attachements from iApply
            processAttachments($tempiApplyFormData,'');
      
            //process remaining docgen files from iApply
            processDocGen($tempiApplyFormData,'');
            
        break;

        case $ConsumerComplaints:
            echo "Consumer Complaints form :)<br>";
        break;
 
         default:
             echo "<strong>{$iApplyAppFormData['Application']['data']['Form_name']} ({$iApplyAppFormData['Application']['data']['Form_code']}) is Not Integrated  to LOGIC <br>COMING SOON.......(in 2050)  be patient &#x1F60a &#x1F60a &#x1F60a</strong><br>";                       
     }//end of switch case
?>