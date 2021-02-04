<?php 
include ( "../admin/function/db.php" );
include ( "payments_settings.php" );

/*
$_POST["payuPaymentReference"];
$_POST["message"];
$_POST["amount"];
$_POST["currency"];
*/
$deger=$_POST["payuPaymentReference"];

$url = 'https://web.paym.es/api/status';
$data = array(
	'paymentReference' => $deger
);

// use key 'http' even if you send the request to https://...
$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) { 
header("Refresh: 0; url=/members/payments_result.php?errorCode=111&errorMsg=Confirmation service does not work Contact Us: support@micromediabox.com");
 }
 
$dizi = json_decode($result,true);

	if ($dizi["status"] == "success") {	

$sql = "select * from " . PVS_DB_PREFIX . "credits_list where id_paymentReference=".$deger."";
	$ds->open($sql);
	$rse=$ds->row["id_paymentReference"];
	
$sqla = "select * from " . PVS_DB_PREFIX ."orders where id_paymentReference=".$deger."";
	$rs->open($sqla);
	$drr=$rs->row["id_paymentReference"];	

$sqlb = "select * from " . PVS_DB_PREFIX ."subscription_list where id_paymentReference=".$deger."";
	$dr->open($sqlb);
	$crr=$dr->row["id_paymentReference"];

	
	if($rse==$deger){
		$id=$ds->row["id_parent"];
		$product_type="credits";
	}
	else if($drr==$deger){
		$id=$rs->row["id"];
		$product_type="order";
	}
	else if($crr==$deger){
		$id=$dr->row["id_parent"];
		$product_type="subscription";
	}
	else{
     header("Refresh: 0; url=/members/payments_result.php?errorCode=999&errorMsg=Critical system Failure Payment Failed Contact Us: support@micromediabox.com");
	}
	
		$transaction_id = pvs_transaction_add( "paymesapi", '', $product_type, $id );


		if ( $product_type == "credits" ) {

			pvs_credits_approve( $id, $transaction_id );

			pvs_send_notification( 'credits_to_user', $id );

			pvs_send_notification( 'credits_to_admin', $id );

		}


		if ( $product_type == "subscription" ) {

			pvs_subscription_approve( $id );

			pvs_send_notification( 'subscription_to_user', $id );

			pvs_send_notification( 'subscription_to_admin', $id );

		}
	

		if ( $product_type == "order" ) {

			pvs_order_approve( $id );

			pvs_commission_add( $id );

			pvs_coupons_add( pvs_order_user( $id ) );

			pvs_send_notification( 'neworder_to_user', $id );

			pvs_send_notification( 'neworder_to_admin', $id );

		}
		
		header("Refresh:0; url=/members/payments_result.php?d=1");

	}
	else if ($dizi["status"] == "failed"){
		header("Refresh: 0; url=/members/payments_result.php?errorCode=665&errorMsg=Payment not approved Contact Us: support@micromediabox.com");
	}
	else{
		header("Refresh: 0; url=/members/payments_result.php?errorCode=666&errorMsg=Payment not approved Contact Us: support@micromediabox.com");
	}
	
?>