<?php 
namespace app\extensions\command;

use app\models\Transactions;
use app\models\Details;
use app\models\Parameters;


use app\extensions\action\Greencoin;

class Walletxgcnotify extends \lithium\console\Command {
    public function index($s=null) {
			$greencoin = new Greencoin('http://'.GREENCOIN_WALLET_SERVER.':'.GREENCOIN_WALLET_PORT,GREENCOIN_WALLET_USERNAME,GREENCOIN_WALLET_PASSWORD);
			$paytxfee = Parameters::find('first');
			$txfee = $paytxfee['payxgctxfee'];
// print_r($s);
		$getrawtransaction = $greencoin->getrawtransaction($s);
		$decoderawtransaction = $greencoin->decoderawtransaction($getrawtransaction);		

			foreach($decoderawtransaction['vout'] as $out){
				foreach($out['scriptPubKey']['addresses'] as $address){
				
					$username = $greencoin->getaccount($address);
//				print_r($username);
					$Amount = (float)$out['value'];
					if($greencoin->getaccount($address)!=""){
						$Transactions = Transactions::find('first',array(
							'conditions'=>array('TransactionHash' => $s)
						));
						if($Transactions['_id']==""){
							$t = Transactions::create();
							$Amount = $Amount;
							$comment = "Move from User: ".$username."; Address: ".GREENCOINX_ADDRESS."; Amount:".$Amount.";";
							$transfer = $greencoin->sendfrom($username, GREENCOINX_ADDRESS, (float)$Amount,(int)0,$comment);

							if(isset($transfer['error'])){
								$error = $transfer['error']; 
							}else{
								$error = $transfer;
							}
						$data = array(
							'DateTime' => new \MongoDate(),
							'TransactionHash' => $s,
							'username' => $username,
							'address'=>$address,							
							'Currency'=>'XGC',							
							'Amount'=> $Amount,
							'Added'=>true,
							'Transfer'=>$comment,
						);							
						$t->save($data);
						$userName = str_replace("SiiCrypto-","",$username);
						$details = Details::find('first',
							array('conditions'=>array('username'=> (string) $userName))
						);
						$user = Users::find('first',array(
							'conditions'=>array(
								'_id'=>$userid,
								'username'=>$details['username']
							)
						));
						$email = $user['email'];

// Send email to client for payment receipt, if invoice number is present. or not
					/////////////////////////////////Email//////////////////////////////////////////////////
					$emaildata = array(
						'email'=>$email,
						'transactions'=>$data
					);
						$function = new Functions();
						$compact = array('data'=>$emaildata);
						$from = array(NOREPLY => "noreply@".COMPANY_URL);
						$email = $email;
						$function->sendEmailTo($email,$compact,'ex','transactionXGCReceived',"SiiCrypto.com - Received coins",$from,'','','',null);
					/////////////////////////////////Email//////////////////////////////////////////////////				

// email send function	
						
						
									
						$dataDetails = array(
								'balance.XGC' => (float)((float)$details['balance.XGC'] + (float)$Amount),
								'XGCnewaddress'=>'Yes'						
							);
						
							$details = Details::find('all',
								array(
										'conditions'=>array('username'=>(string)$userName)
									))->save($dataDetails);

						}else{
							$Transactions = Transactions::find('first',array(
								'conditions'=>array('TransactionHash' => $s)
							))->save($data);
						}
					}
				}
			}
		}
} 
?>