<?php
    // acquring the relevant functions to proceed the integration 
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
    $UpgradeLicence = '5dc4ae70ad9c5c09cc248fdc'; // Upgrade a Licence / Variation to Conditions - Upgrade 
    $DowngradeLicense = '5dcb60dead9c5c1ab068e3f6' ; // Downgrade a Licence / Variations to conditions - Downgrade 
    $WageringSystemsOrEquipment = '5f59c3b2ad9c5930bc39d3a8'; //Application for the approval of wagering systems or equipment 
    $ReviewBarringOrder = '5f59cac9ad9c5930bc39e3d0';//Application to Review a barring order
    $NotifyCommissionerOfChangeInDetails = '5dcb8df3ad9c5c1ab0690844'; //Notify the commissioner of a change in details form
    $CasinoAppForApproval = '597584b7ad9c5a1c8072111c';//Casino Application for Approval
    $OfferToSellGamingMachineEntitlements = '59ed48d9ad9c5a823c3a9234'; //Offer to Sell Gaming Machine Entitlements
    $InCreaseDecreaseGamingMachine = '5fc45a6dad9c59394824f299'; //Application to Increase Decrease Gaming Machines
    $ReviewOfDecisionWithholdWinnings = '5f4c8932ad9c582d1485f12c'; // Application for Review of decision to withhold winnings
    $ApprovalForGameMachineModification = '59e9708bad9c5a211c5cd464'; //Application for the approval of game machine modification
    $RenewalOfWageringLicence = '5ff54acaad9c5b0b8cf2d8e2'; // Renewel of a wagering license
    $EmployementOfMinors = '5de890f3ad9c5d3684bbe871'; // Employement of minors
    $OfferToPurchaseGamingMachineEntitlements = '59e9eaeead9c5a211c5d4d9a'; // Offer To Purchase Gaming Machine Entitlements
    $OfferToPurschaseGamingMachineEntitlementsClubOne = '' ; //Offer To Purschase Gaming Machine Entitlements - Club One
    $OfferToSellGamingMachineEntitlementsClubOne = ''; // Offer To Sell Gaming Machine Entitlements - Club One
    $Achira_Notes_Test_Form = '61148326ad9c5b4cecf418af'; //temp form delete it later once finishied everything
    
    $CaseManagerID = ''; // store case manager ID for file allocation
    $DelegateID = ''; // store delegae ID for hearings    
    $lastiApply = 0; //setting last iapply value to 0
    
    //getting iApply JSON from iApply
    $iApplyAppFormData = getiApplyApplicationJSON($APP_ID);
    //echo "<strong>iApply Data for APP_ID - {$APP_ID}</strong><br>";
    //echo "<pre>";echo print_r($iApplyAppFormData,true);echo "<hr>";
    
    $tempiApplyFormData = $iApplyAppFormData; // assigning all iApply App data into temp array
  
    //catching correct form according to form id in iApply form json
    switch ($iApplyAppFormData['Application']['formId']) {
        //catching the correct iApply form based on formid
        case $RelocateLicense:
        case $ChangeAlterRedefineLicensePremises:
        case $InCreaseDecreaseGamingMachine:
        
            //displaying the correct iApply form name 
            echo "<strong>{$iApplyAppFormData['Application']['data']['Form_name']}({$iApplyAppFormData['Application']['data']['Form_code']}) iApply Form</strong><br><hr>";

            //validating whether AS_ID and AT_ID are json or not before constructing AT_ID & AS_ID array
            if(isJson($tempiApplyFormData['Application']['data']['AS_ID']) && isJson($tempiApplyFormData['Application']['data']['AT_ID'])){
                $AT_ID_Arr = json_decode($tempiApplyFormData['Application']['data']['AT_ID']);
                $AS_ID_Arr = json_decode($tempiApplyFormData['Application']['data']['AS_ID']);
            }else{
                $AS_ID_Arr = convertStupidStringsToArray($tempiApplyFormData['Application']['data']['AS_ID']);
                $AT_ID_Arr = convertStupidStringsToArray($tempiApplyFormData['Application']['data']['AT_ID']);
            }

            $AT_ID_ArrSize = count($AT_ID_Arr);

            echo "AS_ID Details<br>";
            echo '<pre>';print_r($AS_ID_Arr);echo '</pre>';
            echo "AT_ID Details<br>";
            echo '<pre>';print_r($AT_ID_Arr);echo '<hr>';
                
            //check if Application is having more than 1 application types to get Applinkgroup ID
            if($AT_ID_ArrSize > 1){
                //add iApply data to AppLinkGroup TBL in LOGIC DB
                $AppliinkGroup  =  addAppLinkGroupToLOGIC();
                echo "App Link Group Details for {$AT_ID_ArrSize} applications<br>";
                echo "<pre>";print_r($AppliinkGroup);echo "<hr>";
            
            } // end of if(count($AT_ID_Arr) > 1)
        
            //looping through all the available applications 
            for($counter = 0; $counter < count($AT_ID_Arr); $counter++){
                echo "Integration is for Application({$counter}) [AS_ID Number ({$AS_ID_Arr[$counter]}) & AT_ID Number ({$AT_ID_Arr[$counter]}])<br>";

                //assigning AS_ID & AT_ID values to tempiApply arr
                $tempiApplyFormData['Application']['data']['AS_ID'] = $AS_ID_Arr[$counter];
                $tempiApplyFormData['Application']['data']['AT_ID'] = $AT_ID_Arr[$counter];

                //sending attachements with first Application, assigning same applinkgroup id, casemanager id and delegate id for file allocation to the rest of the applications
                if($counter === 0){   
                    //reassign lastiapply to 1 if aT_ID array has 1 value
                    if($AT_ID_ArrSize == 1){
                        $lastiApply = 1;
                    }                               
                    
                    //add iApply data to Applications in LOGIC DB
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppliinkGroup,'','',$lastiApply);
                    $CaseManagerID = $ApplicationsTBLData[0]['AU_Name'];
                    $DelegateID = $ApplicationsTBLData[0]['APP_Delegate_AU_ID'];
                    echo "App Details for first Application<br>";
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
            
                    //sending attachments to LOGIC DB to process upload attachements from iApply 
                    processAttachments($tempiApplyFormData,$ApplicationsTBLData);
                               
                }else if(($AT_ID_ArrSize > 1) && ($counter == $AT_ID_ArrSize-1)){
                    $lastiApply = 1;
                    echo "App Details for last Application if single form has more than 1 Application<br>";
                    //add iApply last application to generate 1 email containing all the details about other applications
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppliinkGroup,$CaseManagerID,$DelegateID,$lastiApply); //$allocato
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";

                }else{
                    //add iApply data and AU_Name which was grapped from 1st iteration to LOGIC DB
                    $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData,$AppliinkGroup,$CaseManagerID,$DelegateID); //$allocato
                    echo "App Details for rest of the applications excluding 1st and last applications<br>";
                    echo "<pre>";print_r($ApplicationsTBLData);echo "<hr>";
                } // end of else condition
                
                //generates json string for LicenceSystemApplications in LOGIC DB
                $JsonStrForLicenSysAppTBL = createJSONForLicenceSystemApplicationsTBL($ApplicationsTBLData,$tempiApplyFormData);
                echo "JSON string for LicenceSystemApplications TBL <br>";
                echo "<pre>";print_r($JsonStrForLicenSysAppTBL);echo '<br>';
                      
                //add iapply data and json string to LicenceSystemApplications in LOGIC DB
                $LicenseSysAppTBLData = addLicenceSystemApplicationsTBL($JsonStrForLicenSysAppTBL,$ApplicationsTBLData);
                echo "<br>Values in LicenceSystemApplications TBL";
                echo "<pre>";print_r($LicenseSysAppTBLData);echo "<hr>";

                //process docgen files from iApply
                processDocGen($tempiApplyFormData,$ApplicationsTBLData);
                
                //add iApply Form data to ApprovalGamesMachines in LOGIC DB
                /*if(($iApplyAppFormData['Application']['formId'] === )){
                    $ApprovalGamesMachinesTBLData = addApprovalGamesMachinesToLOGIC($iApplyAppFormData,$ApplicationsTBLData);
                    echo "<strong>Approval Games Machine TBL Data</strong> <br>";
                    echo "<pre>";print_r($ApprovalGamesMachinesTBLData);echo "<hr>"; 
                } */// end of if condition
               
            }// end of for loop
           
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
                echo '<hr>';

            }else{
                echo "<strong>No Financial Transaction is allocated to {$iApplyAppFormData['Application']['data']['Form_name']}({$iApplyAppFormData['Application']['data']['Form_code']}) iApply Form</strong><br><hr>";
            }// end of if else condition
             
            break;

            //catching the Application for "Liquor Licence Fee Waiver"  iApply form
        case $LiquorLicenceFeeWaiver:
            echo "<strong>Form ID - </strong> {$iApplyAppFormData['Application']['formId']}<br>";
            
            //assigning AS_ID & AT_ID values to tempiApply arr
            $tempiApplyFormData['Application']['data']['AS_ID'] = $AS_ID_Arr[0];
            $tempiApplyFormData['Application']['data']['AT_ID'] = $AT_ID_Arr[0];

             //add iApply data to Applications in LOGIC DB
             $ApplicationsTBLData = addApplicationToLOGIC($tempiApplyFormData);
             echo "<pre>";print_r($ApplicationsTBLData);echo "</pre>"; 
             
             //Add application order
             $ApplicationOrderTBLData = addApplicationOrderToLOGIC($tempiApplyFormData, $ApplicationsTBLData);
             echo "<pre>";print_r($ApplicationOrderTBLData);echo "</pre>"; 
                 
             //Add Docgen Order document if present
             if(isset($tempiApplyFormData['Application']['data']['Docgen_Integration'])) {
                 echo "<br>Processing an Order document<br>";
                 //Remove Order DocGen from $iApplyAppFormData because we dont want to process it again later
                 $DocGenArray = json_decode($tempiApplyFormData['Application']['data']['Docgen_Integration'], true);
                 echo '<pre>';echo print_r($DocGenArray,true);echo '<hr>';
                 foreach ($DocGenArray as $index => $DocGen) {
                     if($DocGen['name'] == 'Order') {
                         unset($DocGenArray[$index]);
                     }
                 }
                 $DocGenArray = array_values($DocGenArray);
                 echo 'after array_values function<br>';
                 echo '<pre>';echo print_r($DocGenArray,true);echo '<hr>';
                 $tempiApplyFormData['Application']['data']['Docgen_Integration'] = json_encode($DocGenArray);
 
                 //add order to documents table in the 'orders' way
                 $DocumentsTBLData = processOrderDocument($ApplicationOrderTBLData[0]['AOR_ID']);
                 echo "<pre>";print_r($DocumentsTBLData);echo "</pre>"; 
             }
             
             //Process fee adjustments
             $FeeUpdateTBLData = addFeeUpdatesToLOGIC($tempiApplyFormData, $ApplicationOrderTBLData);
             echo "<pre>";print_r($FeeUpdateTBLData);echo "</pre>"; 
             
             //used to process upload attachements from iApply
             processAttachments($tempiApplyFormData,$ApplicationsTBLData);
     
             //process remaining docgen files from iApply
             processDocGen($tempiApplyFormData,$ApplicationsTBLData);
                 
        break;

        default:
            echo "<strong>Couldn't Locate {$iApplyAppFormData['Application']['data']['Form_name']}({$iApplyAppFormData['Application']['data']['Form_code']}) iApply Form or Form has not been Integrated yet</strong><br>";                       
    }//end of switch case   
?>