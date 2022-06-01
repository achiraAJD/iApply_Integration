<?php
    //acquring the database,iApply api connection details
    require('Global.php');

    //get LicenceClass ID from LogiC
    function getLotteryAppData($LLIC_LicenceNumber){
        global $drv, $svr, $db, $un, $pw;
        $dataArr = [];
        try{    
            $params = [];
            $FSDataArr = [];
            $LAPP_ID_Arr = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "exec spWebiApplyHelpers @Switch = :Switch, @Params = :Params";
              
            $params[':Switch'] = 'GetLotteryLicenceAppDetails'; 
	        $params[':Params'] = $LLIC_LicenceNumber;
                               
            $sth = $dbh->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            $results = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            if (sizeof($results)>0) {
                //echo "Data Fetched from Entity Types table in LOGIC<br>";
                //echo '<pre>';print_r($results);echo '</pre>';
                //replace_key($results,$results[''],$results["json"]);
                //return json_decode($results[0]['LAPP_AAP_JSON'],true);
                return $results;
            } else {
                error(400, "<br>1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }

        }catch (PDOException $e){
            error(400, "<br>getLicenceClassesID 2 - Error connecting to LOGIC - ". $e->getMessage());
        }
    }// end of getLicenceClassesID

    function GetLotteryAppFinanceDetails($LAPP_ID){
        global $drv, $svr, $db, $un, $pw;
        $dataArr = [];
        try{    
            $params = [];
            $dbh = new PDO("odbc:Driver=$drv; Server=$svr; Database=$db; UID=$un; PWD=$pw;" );
            $sql = "exec spWebiApplyHelpers ";
              
            $params['Switch'] = 'GetLotteryAppFinanceDetails'; 
	        $params['Params'] = $LAPP_ID;
                               
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
            /*do {
                $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
                //echo 'values of $rows array<br>';
                //echo '<pre>';print_r($rows);echo '</pre>';
                if ($rows) {
                    if(isset($rows[0]['APP_ID'])) {
                        //echo "hehe<br>";
                        $results = $rows;
                    }
                }
            } while ($sth->nextRowset());*/
            if (sizeof($results)>0) {
                //echo "Data Passed to Applications tbl in LOGIC DB<br>";
                return $results;
            } else {
                error(400, "<br>addApplicationToLOGIC 1 - Error connecting to LOGIC - ". json_encode($sth->errorInfo()));
            }

        }catch (PDOException $e){
            error(400, "<br>getLicenceClassesID 2 - Error connecting to LOGIC - ". $e->getMessage());
        }
    }
    /*
    $data1 = getLotteryAppData('CCP92');
    $data2 = GetLotteryAppFinanceDetails('742');
    //$data[0]['display'] = $data[0]['LLIC_LicenceNumber'];
    echo '<pre>';print_r($data1);echo '</pre>';
    echo '<pre>';print_r($data2);echo '</pre>';
    die();
    */


?>


