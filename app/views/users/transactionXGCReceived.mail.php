<!DOCTYPE html>
<html>
<head>
<title><?=COMPANY_NAME?></title>
<style>@font-face {
 font-family: Calibre;
 src: url(https://siicrypto.com/css/calibre-regular.woff);
}
div{
	padding:20px;
	align:auto;
	text-align:center;
	background-color:#eee
}
body{
	font-family: Calibre;
	font-size:20px;
	margin:10px;
}
td{
	padding: 20px 20px 20px 20px;
	text-align:left;
}
a{
text-decoration: none
}
</style>
</head>
<body>
<div style="border-radius: 10px;-webkit-border-radius: 10px;-moz-border-radius: 10px;">
	<div style="background-color:black;border-radius: 10px;-webkit-border-radius: 10px;-moz-border-radius: 10px;">
		<a href="https://siicrypto.com" target="_blank"><img src="https://siicrypto.com/img/logo.png" alt="SiiCrypto.com" text="SiiCrypto.com"/></a>
	</div>
	<div>
		<table style="width:95%;	align:auto;	margin:auto;	border:0px;	background-color:white;">
			<tr>
				<th style="	padding: 20px 0 20px 0;	background-color:#ddd; font-size:24px">Received GreenCoinX XGC
				</th>
			</tr>
			<tr>
				<td><h4>Hi <?=$compact['data']['username']?>,</h4>
						<p class="">We have received GreenCoinX (XGC) in your SiiCrypto account</p>
							<table class="">
							<tr class="">
							<th>Date</th>
							<th>Amount XGC</th>
							<th>Address</th>
							<th>Transaction Hash</th>
							</tr>
							<tr>
							<td><?=gmdate('Y-M-d H:i:s',$compact['data']['DateTime']->sec)?></td>
							<td><?=number_format($compact['data']['Amount'],8)?></td>
							<td><?=$compact['data']['address']?></td>
							<td><?=$compact['data']['TransactionHash']?></td>
							</tr>
							</table>
				</td>
			</tr>
			<tr>
				<td>IP: <?=$_SERVER['REMOTE_ADDR'];?><br>
Date and time: <?=gmdate('Y-m-d H:i:s',time())?>
</p></td>
			</tr>
			<tr>
			<td>
			Thanks,<br><?=NOREPLY?>
			</td>
			</tr>
		</table>
	</div>
	<p>SiiCrypto is a REALLY safe crypto currency exchange.</p>
	<p><a href="https://greencoinx.com">GreenCoinX.com</a> - <a href="https://xgcwallet.org">XGCWallet.org</a> - <a href="https://KYCGlobal.net">KYCGlobal.net</a></p>
	<div style="padding:30px;font-size:10px">
	<p>Please do not reply to this email. </p>
	<p>This email was sent to you as you tried to register on <?=COMPANY_URL?> with the email address. 
	If you did not register, then you can delete this email.</p>
	<p>We do not spam. </p>
	</div>
</div>
</body>
</html>
