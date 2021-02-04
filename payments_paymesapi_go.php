<?php
include ( "../admin/function/db.php" );
include ( "payments_settings.php" );
/*
echo "<br>secret:".$_POST["paymes_api"];
echo "<br>operationId:".$_POST["product_id"];
echo "<br>number:".$_POST["cardnumber"];
echo "<br>installmentsNumber:1";
echo "<br>expiryMonth:".$_POST["cardmonth"];
echo "<br>expiryYear:".$_POST["cardyear"];
echo "<br>cvv:".$_POST["cardcvc"];
echo "<br>owner:".$_POST["owner"];
echo "<br>billingFirstname:".$_POST["bfirstname"];
echo "<br>billingLastname:".$_POST["blastname"];
echo "<br>billingEmail:".$_POST["bemail"];
echo "<br>billingPhone:".$_POST["bphone"];
echo "<br>billingCountrycode:TR";
echo "<br>billingAddressline1:".$_POST["badress"];
echo "<br>billingCity:".$_POST["bcity"];
echo "<br>deliveryFirstname:".$_POST["bfirstname"];
echo "<br>deliveryLastname:".$_POST["blastname"];
echo "<br>deliveryPhone:".$_POST["bphone"];
echo "<br>deliveryAdressline1:".$_POST["badress"];
echo "<br>deliveryCity:".$_POST["bcity"];
echo "<br>clientIp:".$_POST["bip"];
echo "<br>productName:".$_POST["product_name"];
echo "<br>productSku:1";
echo "<br>productQuantity:1";
echo "<br>productPrice:".$_POST["product_total"];
echo "<br>currency:USD";
echo "<br>comment:".$_POST["product_type"]."<br><br><br>";*/

$slac = str_replace("/", "", $_POST["cardexp"]);
$cardyear = substr($slac, -2, 4);
$cardmonth = substr($slac, 0, -2);

$url = 'https://web.paym.es/api/authorize';
$data = array(
	'secret' => $_POST["paymes_api"],
    'operationId' => $_POST["product_id"],
	'number' => $_POST["cardnumber"],
	'installmentsNumber' => "1",
	'expiryMonth' => $cardmonth,
	'expiryYear' => $cardyear,
	'cvv' => $_POST["cardcvc"],
	'owner' => $_POST["owner"],
	'billingFirstname' => $_POST["bfirstname"],
	'billingLastname' => $_POST["blastname"],
	'billingEmail' => $_POST["bemail"],
	'billingPhone' => $_POST["bphone"],
	'billingCountrycode' => "TR",
	'billingAddressline1' => $_POST["badress"],
	'billingCity' => $_POST["bcity"],
	'deliveryFirstname' => $_POST["bfirstname"],
	'deliveryLastname' => $_POST["blastname"],
	'deliveryPhone' => $_POST["bphone"],
	'deliveryAdressline1' => $_POST["badress"],
	'deliveryCity' => $_POST["bcity"],
	'clientIp' => $_POST["bip"],
	'productName' => $_POST["product_id"],/*$_POST["product_name"] Paymeste id eşleştirmek için*/
	'productSku' => "1",
	'productQuantity' => "1",
	'productPrice' => $_POST["product_total"],
	'currency' => "USD", /* ???? */
	'comment' => $_POST["product_name"], /* product_type değiştirildi*/
	'amount' => "1" /*çekerken fiyat - gönderirken birim Hata olabilir*/
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
header("Refresh: 0; url=/members/payments_result.php?errorCode=444&errorMsg=Your card information is missing or your profile information is missing!");
}
 
$dizi = json_decode($result,true);

/* print dont remove
echo "<br>".$dizi["payuPaymentReference"];
echo "<br>".$dizi["status"];
echo "<br>".$dizi["paymentResult"]["payuResponseCode"];
echo "<br>".$dizi['paymentResult']['url'];
echo "<br>".$dizi['paymentResult']['type'];
echo "<br>message:".$dizi["message"];
echo "<br>code:".$dizi["code"];
echo "<br>fiyat TL:".$dizi["amount"];
*/


if($_POST["product_type"]=="order"){

$query = ("update order set id_paymentReference=".$dizi["payuPaymentReference"]." where id=".$_POST["product_id"]."");
	$db->execute( $query );
	
}
else if($_POST["product_type"]=="credits"){

	$query = ("update credits_list set id_paymentReference=".$dizi["payuPaymentReference"]." where id_parent=".$_POST["product_id"]."");
	$db->execute( $query );
}
else if($_POST["product_type"]=="subscription"){
	
	$query = ("update subscription_list set id_paymentReference=".$dizi["payuPaymentReference"]." where id_parent=".$_POST["product_id"]."");
	$db->execute( $query );
}
else{
	header("Refresh: 0; url=/members/payments_result.php?errorCode=888&errorMsg=GO_SQL ORDER Payment Failed Contact Us: support@micromediabox.com");
}

$status=$dizi["status"];

if ($dizi["code"] == "400") {
header("Refresh: 0; url=/members/payments_result.php?errorCode=400&errorMsg=Card information is missing!");
}
else if($status == "SUCCESS"){ /*kart bilgileri doğru ise veya code 200*/

$message = $dizi["message"];
$ds = $dizi["paymentResult"]["payuResponseCode"];

if($message == "Authorized"){ /*ödeme 3d securesiz yapıldı*/
	
$autpost=$dizi["payuPaymentReference"];

echo "
   <form action=\"payments_paymes_rs.php\" method=\"post\" id=\"dateForm\">
    <input name=\"payuPaymentReference\" type=\"hidden\" value=\"$autpost\">
    <input type=\"submit\">
   </form>

  <script type=\"text/javascript\">
	 document.getElementById('dateForm').submit();
  </script>
";      
}
else if($message == "3DS Enrolled Card." and $ds == "3DS_ENROLLED"){
	

header("Location: ".$dizi['paymentResult']['url'].""); /* 3D SECURE LİNK*/

}
else{
	header("Refresh: 0; url=/members/payments_result.php?errorCode=555&errorMsg=3D security not approved Payment Failed Contact Us: support@micromediabox.com");
}	
}
else{
	header("Refresh: 0; url=/members/payments_result.php?errorCode=333&errorMsg=Paym.es Operation failed Contact Us: support@micromediabox.com");
	
}
?>