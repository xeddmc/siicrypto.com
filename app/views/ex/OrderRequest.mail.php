<div style="background-color:#000;height:50px;padding-left:20px;padding-top:10px">
	<img src="https://<?=COMPANY_URL?>/img/logo.png" alt="<?=COMPANY_URL?>">
</div>
<h4>Hi <?=$user['username']?>,</h4>
<p>Your order is placed at <?=COMPANY_URL?>.</p>
<table border="1" >
	<tr>
		<td>Order ID</td>
		<td>Action</td>
		<td>Amount</td>
		<td>Price</td>
		<td>Total Amount</td>
	</tr>
	<tr>
		<td><?=$order['_id']?></td>
		<?php if($order['Action']=="Buy"){?>
		<td><?=$order['Action']?> <?=$order['FirstCurrency']?> with <?=$order['SecondCurrency']?></td>
		<?php }else{?>
		<td><?=$order['Action']?> <?=$order['FirstCurrency']?> get <?=$order['SecondCurrency']?></td>		
		<?php } ?>
		<td><?=number_format($order['Amount'],8);?></td>
		<td><?=number_format($order['PerPrice'],8);?></td>
		<td><?=number_format($order['PerPrice']*$order['Amount'],8);?></td>		
	</tr>
</table>
<p>To view your order please sign in to https://SiiCrypto.com. </p>
<p>If you did not place this order then please contact us at support@SiiCrypto.com.</p>

<p>Thank you,</p>
<p>Support</p>