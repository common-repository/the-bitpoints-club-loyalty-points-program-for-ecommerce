<?php
/*
 * Short Codes
 */
//points-balance
function BitPointsClub_register_points_balance() {
    add_shortcode( 'bitpoints-points-balance', 'BitPointsClub_points_balance' );
} 
function BitPointsClub_points_balance($atts) {
    $a = shortcode_atts( array(
        'text-before' => '',
        'text-after' => '',
    ), $atts );

    if(BitPointsClub_loggedin()) {
        if(isset($_SESSION['bitPoints_CustomerBalance']))
		    return "{$a['text-before']}".number_format($_SESSION['bitPoints_CustomerBalance'])."{$a['text-after']}";
	    return  "{$a['text-before']}0{$a['text-after']}";
    }
    return "";
} 
add_action( 'init', 'BitPointsClub_register_points_balance' );

//points-balance-value
function BitPointsClub_register_points_balance_value() {
    add_shortcode( 'bitpoints-points-balance-value', 'BitPointsClub_points_balance_value' );
} 
function BitPointsClub_points_balance_value($atts) {
    $a = shortcode_atts( array(
        'text-before' => '',
        'text-after' => '',
    ), $atts );

    if(BitPointsClub_loggedin()) {
        if(isset($_SESSION['bitPoints_CustomerValue'])) 
		    return "{$a['text-before']}".number_format($_SESSION['bitPoints_CustomerValue'], 2)."{$a['text-after']}";
	    return  "{$a['text-before']}0.00{$a['text-after']}";
    }
    return "";
} 
add_action( 'init', 'BitPointsClub_register_points_balance_value' );

//transaction-history
function BitPointsClub_register_transaction_history() {
    add_shortcode( 'bitpoints-transaction-history', 'BitPointsClub_transaction_history' );
} 
function BitPointsClub_transaction_history($atts) {
    $a = shortcode_atts( array(
        'text-before' => '',
        'text-after' => '',
    ), $atts );
    
    $BitPointsClub_Transaction_History_Fields = get_option( 'BitPointsClub_Transaction_History_Fields' );
    if(!isset($BitPointsClub_Transaction_History_Fields) || $BitPointsClub_Transaction_History_Fields == "") $BitPointsClub_Transaction_History_Fields = "created=Date; description=Description; transaction_type=Type; amount=Amount; points=Points";
    $fields = explode(";", $BitPointsClub_Transaction_History_Fields);
    if(count($fields) > 0) {
        if(BitPointsClub_loggedin()) {
            $BitPointsClub_Transaction_Type_Translations = get_option( 'BitPointsClub_Transaction_Type_Translations' );
            if(!isset($BitPointsClub_Transaction_Type_Translations) || $BitPointsClub_Transaction_Type_Translations == "") $BitPointsClub_Transaction_Type_Translations = "Join=Join; Earn=Purchase; Credit=Refund; Redeem=Redemption; Refund=Refund; Promotion=Promotion; Expired=Expired";
            $transactionTypes = explode(";", $BitPointsClub_Transaction_Type_Translations);

            if(!isset($_SESSION['bitPoints_History'])) $_SESSION['bitPoints_History'] = BitPointsClub_API_History((int)$_SESSION['bitPoints_CustomerId']);
		    $objects = $_SESSION['bitPoints_History'];
		    if(isset($objects)) {        
			    if(count($objects) == 0)
				    return "{$a['text-before']}No points history yet{$a['text-after']}";

			    else {
                    $columnHeader = "";
                    foreach ($fields as $field) {
                        if(strlen(trim($field)) > 0) {
                            $columns = explode("=", $field);
                            if(count($columns) >= 2 && strlen(trim($columns[0])) > 0 && strlen(trim($columns[1])) > 0) {
                                $columnHeader = $columnHeader.'<th class="bitpoints-history-header-'.trim($columns[0]).'">'.trim($columns[1]).'</th>';
                            }
                        }
                    }

                    if(strlen($columnHeader) > 0) {
				        $table = $a['text-before'].'
    <div class="bitpoints-history">
    <table>
    <thead>
	    <tr>'.$columnHeader.'</tr>
    </thead>
    <tbody>';

				        $blnAlternate = false;
				        foreach ($objects as $object) {        
                            $row = "";
                            foreach ($fields as $field) {
                                if(strlen(trim($field)) > 0) {
                                    $columns = explode("=", trim($field));
                                    if(count($columns) >= 2 && strlen(trim($columns[0])) > 0 && strlen(trim($columns[1])) > 0) {
                                        $rowValue = "";
                                        switch(strtoupper($columns[0])) {
                                            case 'CREATED':
                                                $time = strtotime($object->created);
                                                $rowValue = date('d M Y',$time);
                                                break;
                                            case 'EXPIRY':
                                                $time = strtotime($object->expiry);
                                                $rowValue = date('d M Y',$time);
                                                break;
                                            case 'DESCRIPTION':
                                                $rowValue = $object->description;
                                                break;
                                            case 'TRANSACTION_TYPE':
                                                $rowValue = $object->transaction_type;
                                                foreach ($transactionTypes as $transactionType) {
                                                    if(strtoupper(trim($transactionType)) == strtoupper(trim($rowValue))) $rowValue = trim($transactionType);
                                                }
                                                break;
                                            case 'AMOUNT':
                                                $rowValue = number_format($object->amount,2);
                                                break;
                                            case 'POINTS':
                                                $rowValue = number_format($object->points);
                                                break;
                                        }
                                        $row = $row.'<td class="bitpoints-history-item-'.trim($columns[0]).'">'.$rowValue.'</td>';
                                    }
                                }
                            }
    $table = $table.'
	    <tr'.($blnAlternate ? ' class="bitpoints-history-item-row-alternate"' : '').'>'.$row.'</tr>';
                            $blnAlternate = !$blnAlternate;
				        }
    $table = $table.'
    </tbody>
    </table></div>'.$a['text-after'];

                        return $table;
                    } else
	                    return "{$a['text-before']}{$a['text-after']}";
			    }
		    }
        }
	}
	return "";
} 
add_action( 'init', 'BitPointsClub_register_transaction_history' );

//due-to-expire
function BitPointsClub_register_due_to_expirey() {
    add_shortcode( 'bitpoints-due-to-expire', 'BitPointsClub_due_to_expire' );
} 
function BitPointsClub_due_to_expire($atts) {
    $a = shortcode_atts( array(
        'text-before' => '',
        'text-after' => '',
    ), $atts );
        
    if(BitPointsClub_loggedin()) {        
        if(!isset($_SESSION['bitPoints_DueToExpire'])) $_SESSION['bitPoints_DueToExpire'] = BitPointsClub_API_Due_To_Expire((int)$_SESSION['bitPoints_CustomerId']);
		$objects = $_SESSION['bitPoints_DueToExpire'];
		if(isset($objects)) {        
			if(count($objects) == 3) {
				$table = $a['text-before'].'
<div class="bitpoints-due-to-expire">
<table>
<thead>
<tr>
    <th class="bitpoints-due-to-expire-header-month1">'.trim($objects[0][1]).'</th>
    <th class="bitpoints-due-to-expire-header-month2">'.trim($objects[1][1]).'</th>
    <th class="bitpoints-due-to-expire-header-month3">'.trim($objects[2][1]).'</th>
</tr>
</thead>
<tbody>
<tr>
    <td class="bitpoints-due-to-expire-item-month1">'.number_format($objects[0][0]).'</td>
    <td class="bitpoints-due-to-expire-item-month2">'.number_format($objects[1][0]).'</td>
    <td class="bitpoints-due-to-expire-item-month3">'.number_format($objects[2][0]).'</td>
</tr>
</tbody>
</table></div>'.$a['text-after'];

                return $table;
			}
		}
	}
	return "";
} 
add_action( 'init', 'BitPointsClub_register_due_to_expirey' );