<?php   
BitPointsClub_API_InitSession();

function BitPointsClub_API_FindCustomer($user_email, $password, $display_name) {  
    if ($user_email && strlen($user_email) > 0) {
        try {
            $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();
        
            //check email doesn't already exist in the db
            $objects = BitPointsClub_API_HTTP('GET', 'program/'.$bitPoints_ProgramId.'/customer/list/?email={"eq":"'.$user_email.'"}', '');
            if(count($objects) == 0) {

                //check password complexity
                if(strlen($password) < 8)
                    $password = str_pad($password, 8);
                if(!preg_match('/[A-z]/', $password))
                    $password = $password + "a";
                if(!preg_match('/[A-Z]/', $password))
                    $password = $password + "A";
                if(!preg_match('/\d/', $password))
                    $password = $password + "1";
            
                //add customer (and assign it to program_id)
                $objects = BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer', 
                  '[{'.        
                    '"email":"'.$user_email.'",'.
                    '"first_name":"'.$display_name.'",'.
                    '"last_name":"",'.
                    '"phone":"",'.
                    '"address":"",'.
                    '"birthdate":"",'.
                    '"password":"'.$password.'"}]'); 
                return BitPointsClub_API_RefreshCustomer($objects[0]->customer_id);

            } else 
                return BitPointsClub_API_RefreshCustomer($objects[0]->customer_id);        
        } catch (Exception $err) {
	        BitPointsClub_API_ErrorLog("BitPointsClub - Failed to find / add customer: ".$err->getMessage());
            return null;
        }
    }
}

function BitPointsClub_API_UpdateCustomer($customer_id, $user_email, $password, $display_name) {
    if ($user_email && strlen($user_email) > 0) {
        try {
            $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();
        
            //check password complexity
            if(strlen($password) < 8)
                $password = str_pad($password, 8);
            if(!preg_match('/[A-z]/', $password))
                $password = $password + "a";
            if(!preg_match('/[A-Z]/', $password))
                $password = $password + "A";
            if(!preg_match('/\d/', $password))
                $password = $password + "1";

            //update customer 
            BitPointsClub_API_HTTP('PUT', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id, 
                '{'.        
                '"email":"'.$user_email.'",'.
                '"first_name":"'.$display_name.'",'.
                '"last_name":"",'.
                '"phone":"",'.
                '"address":"",'.
                '"birthdate":"",'.
                '"password":"'.$password
            .'"}');
            return BitPointsClub_API_RefreshCustomer($objects[0]->customer_id);
        } catch (Exception $err) {
	        BitPointsClub_API_ErrorLog("BitPointsClub - Failed to update customer: ".$err->getMessage());
            return null;
        }
    }
}

function BitPointsClub_API_RefreshCustomer($customer_id) {
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //return program/customer object
        return BitPointsClub_API_HTTP('GET', 'customer/'.$customer_id.'/program/'.$bitPoints_ProgramId.'/', '');
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to refresh customer: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_Earn($customer_id, $amount, $description) {
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //add earn transaction (and assign it to program_id)
        BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction', 
          '[{'.               
            '"transaction_type":"Earn",'.
            '"amount":"'.$amount.'",'.
            '"description":"'.$description
        .'"}]');
        $_SESSION['bitPoints_History'] = null;
        return BitPointsClub_API_RefreshCustomer($customer_id);
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to post Earn transaction: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_Credit($customer_id, $amount, $description) {
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //add earn transaction (and assign it to program_id)
        BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction', 
          '[{'.               
            '"transaction_type":"Credit",'.
            '"amount":"'.$amount.'",'.
            '"description":"'.$description
        .'"}]');
        $_SESSION['bitPoints_History'] = null;
        return BitPointsClub_API_RefreshCustomer($customer_id);
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to post Earn transaction: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_Redeem($customer_id, $amount, $description) {
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //add redeem transaction (and assign it to program_id)
        BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction', 
          '[{'.            
            '"transaction_type":"Redeem",'.
            '"amount":"'.$amount.'",'.
            '"description":"'.$description
        .'"}]');
        $_SESSION['bitPoints_History'] = null;
        return BitPointsClub_API_RefreshCustomer($customer_id);
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to post Redeem transaction: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_Refund($customer_id, $amount, $description) {  
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //add refund transaction (and assign it to program_id)
        BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction', 
          '[{'.
            '"transaction_type":"Refund",'.
            '"amount":"'.$amount.'",'.
            '"description":"'.$description
        .'"}]');
        $_SESSION['bitPoints_History'] = null;
        return BitPointsClub_API_RefreshCustomer($customer_id);
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to post Refund transaction: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_Manual_Adjustment($customer_id, $points, $description) {  
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //add refund transaction (and assign it to program_id)
        BitPointsClub_API_HTTP('POST', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction', 
          '[{'.
            '"transaction_type":"Adjustment",'.
            '"points":"'.$points.'",'.
            '"description":"'.$description
        .'"}]');
        $_SESSION['bitPoints_History'] = null;
        return BitPointsClub_API_RefreshCustomer($customer_id);
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to post Refund transaction: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_History($customer_id) {
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();

        //return customer history
        return BitPointsClub_API_HTTP('GET', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction/List', '');
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to get transaction history: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_Due_To_Expire($customer_id) {
    try {
        $bitPoints_ProgramId = BitPointsClub_API_GetProgramId();
        
        $ret = array();
        $curMonth = date('n');
        $curYear  = date('Y');
        if ($curMonth == 12)
            $date = mktime(0, 0, 0, 0, 0, $curYear+1);
        else
            $date = mktime(0, 0, 0, $curMonth+1, 1);

        //Next month
        $date1 = date('Y-m-d', $date);
        $date2 = date('Y-m-d', strtotime('+1 month', $date));
        $objects = BitPointsClub_API_HTTP('GET', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction/List?expiry={"gte":"'.$date1.'","lt":"'.$date2.'"}&points={"gt":"0"}&count=10000', '');
        $sumMonth = 0;
        foreach ($objects as $object) {
            $sumMonth = $sumMonth + $object->points;
        }
        $ret[0] = array($sumMonth, date_format(date_create_from_format('Y-m-d', $date2)->modify('-1 day'), "d M Y"));

        //Next Next month
        $date1 = date('Y-m-d', strtotime('+1 month', $date));
        $date2 = date('Y-m-d', strtotime('+2 month', $date));
        $objects = BitPointsClub_API_HTTP('GET', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction/List?expiry={"gte":"'.$date1.'","lt":"'.$date2.'"}&points={"gt":"0"}&count=10000', '');
        $sumMonth = 0;
        foreach ($objects as $object) {
            $sumMonth = $sumMonth + $object->points;
        }
        $ret[1] = array($sumMonth, date_format(date_create_from_format('Y-m-d', $date2)->modify('-1 day'), "d M Y"));

        //Next Next Next month
        $date1 = date('Y-m-d', strtotime('+2 month', $date));
        $date2 = date('Y-m-d', strtotime('+3 month', $date));
        $objects = BitPointsClub_API_HTTP('GET', 'program/'.$bitPoints_ProgramId.'/customer/'.$customer_id.'/transaction/List?expiry={"gte":"'.$date1.'","lt":"'.$date2.'"}&points={"gt":"0"}&count=10000', '');
        $sumMonth = 0;
        foreach ($objects as $object) {
            $sumMonth = $sumMonth + $object->points;
        }
        $ret[2] = array($sumMonth, date_format(date_create_from_format('Y-m-d', $date2)->modify('-1 day'), "d M Y"));
        
        BitPointsClub_API_ErrorLog_Object($ret);
        return $ret;
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to get transaction history: ".$err->getMessage());
        return null;
    }
}

function BitPointsClub_API_HTTP($method, $url, $postData, $BasicAuthorization = "") {
    if ($BasicAuthorization == "")
	    $bitPoints_APIKey = "key=".esc_attr(get_option( 'BitPointsClub_API_KEY'));
    else
        $bitPoints_APIKey = $BasicAuthorization;

    $bitPoints_URL = esc_attr(get_option('BitPointsClub_API_URL'));
    if($bitPoints_URL == "") $bitPoints_URL = "https://bitpoints.club/api/v1/";
    BitPointsClub_API_ErrorLog("BitPointsClub_API_HTTP $method $url $postData $bitPoints_APIKey $bitPoints_URL");

    $curl = curl_init();
    $options = array();

    //GET/DELETE options
    if($method == "GET" || $method == "DELETE") 
        $options = array(
            CURLOPT_URL => $bitPoints_URL.$url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => [
                'Authorization: '.$bitPoints_APIKey,
                'Content-Type: application/json'
            ]);

    //POST/PUT options
    else 
        $options = array(
            CURLOPT_URL => $bitPoints_URL.$url,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => [
                'Authorization: '.$bitPoints_APIKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => $postData);

    curl_setopt_array($curl, $options);

    //Send request
    $response = curl_exec($curl);

    //Check results
    $http_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if(FALSE === $response)
    {
        $curlErr = curl_error($curl);
        $curlErrNum = curl_errno($curl);
        curl_close($curl);
        throw new Exception($curlErrNum." ".$curlErr);

    } else if($http_status_code >= 400) {
        curl_close($curl);
        $HandledError = json_decode($response);
        throw new Exception($HandledError->error);
        
    } else {         
        //Return Object
        curl_close($curl);
        return json_decode($response);
    }
}

function BitPointsClub_API_InitSession() {
    if (!session_id()) session_start();
}

function BitPointsClub_API_GetProgramId() {
    if(isset($_SESSION['bitPoints_ProgramId']))
        return (int)$_SESSION['bitPoints_ProgramId'];
    else {        
        $BitPointsClub_Program_Name = esc_attr(get_option('BitPointsClub_Program_Name' ));
        if(strlen($BitPointsClub_Program_Name) > 0) {
            
            //Find a program_id (i.e. the first program), we suggest using a variable for this instead but you can use this code to find what the program_id is
            try {
                $objects = BitPointsClub_API_HTTP('GET', 'program/List?program_type={"eq":"Points"}', '');
                if(count($objects) > 0) {
                    foreach ($objects as $object) {
                        if(strtoupper($object->program_name) == strtoupper($BitPointsClub_Program_Name)) {
                            $_SESSION['bitPoints_ProgramId'] = $object->program_id;
                            return $object->program_id;
                        }
                    }
                }
            } catch (Exception $err) {
	            BitPointsClub_API_ErrorLog("BitPointsClub - Failed to find Program: ".$err->getMessage());
            }
        }
    }
    return 0;
}

function BitPointsClub_API_QuickSetup($email, $password) {
    //Find a program_id (i.e. the first program), we suggest using a variable for this instead but you can use this code to find what the program_id is
    try {
        $objects = BitPointsClub_API_HTTP('GET', 'account', '');
        if(count($objects) > 0) {
            foreach ($objects as $object) {
                if(strtoupper($object->program_name) == strtoupper($BitPointsClub_Program_Name)) {
                    $_SESSION['bitPoints_ProgramId'] = $object->program_id;
                    return $object->program_id;
                }
            }
        }
    } catch (Exception $err) {
	    BitPointsClub_API_ErrorLog("BitPointsClub - Failed to find Program: ".$err->getMessage());
    }
}

function BitPointsClub_API_ErrorLog($message) {
    if(true === WP_DEBUG) {
        error_log($message);

        if(!isset($_SESSION['bitPoints_Log'])) $_SESSION['bitPoints_Log'] = "";
        $_SESSION['bitPoints_Log'] = $_SESSION['bitPoints_Log']."
".$message;
        if(strlen($_SESSION['bitPoints_Log']) > 8000) $_SESSION['bitPoints_Log'] = substr($_SESSION['bitPoints_Log'], -8000);
    }    
}

function BitPointsClub_API_ErrorLog_Object($object = null) {
    ob_start();
    var_dump( $object );
    $contents = ob_get_contents();
    ob_end_clean();
    BitPointsClub_API_ErrorLog($contents);
}

?>