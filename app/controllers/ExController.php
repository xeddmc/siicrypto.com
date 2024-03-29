<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace app\controllers;
use app\models\Users;
use app\models\Details;
use app\models\Settings;
use app\models\Orders;
use app\models\Trades;
use app\models\Pages;
use app\models\Parameters;
use lithium\data\Connections;
use MongoID;
use \Graph;
use \StockPlot;
use \LinePlot;
use \BarPlot;
use \Mgraph;
use \DateScaleUtils;
use lithium\util\String;
use lithium\security\Auth;
use lithium\storage\Session;
use app\extensions\action\MultiSig;
use app\extensions\action\Functions;
use app\extensions\action\OrderFunctions;
use app\controllers\UpdatesController;
use \lithium\template\View;
use \Swift_MailTransport;
use \Swift_Mailer;
use \Swift_Message;
use \Swift_Attachment;

class ExController extends \lithium\action\Controller {

	public function index() {

	}
	public function x($currency = null) {


	if($this->request->query['json']==true){
		$this->_render['type'] = 'json';		
	}
		if($currency==null){$this->redirect(array('controller'=>'ex','action'=>'dashboard/'));}

		$first_curr = strtoupper(substr($currency,0,3));
		$second_curr = strtoupper(substr($currency,4,3));
		$title = $first_curr . "/" . $second_curr;
		$currencyStatus = Trades::find('first',array(
				'conditions' => array('trade'=>$title)
		));
		$this->SetGraph($first_curr,$second_curr);
		
		$user = Session::read('member');
		$id = $user['_id'];
//		if($id==null){$this->redirect(array('controller'=>'ex','action'=>'dashboard/'));}		
		$details = Details::find('first',
			array('conditions'=>array('user_id'=>$id))
		);
		if(count($details['addresses'][$first_curr])>0){
			foreach($details['addresses'][$first_curr] as $check){
				if($check['checkbalance']=='Y'){
						$multiSig = new MultiSig();
						$multiSig->UpdateBalance($first_curr,$id);
				}
			}
		}
		if(count($details['addresses'][$second_curr])>0){
			foreach($details['addresses'][$second_curr] as $check){
				if($check['checkbalance']=='Y'){
						$multiSig = new MultiSig();
						$multiSig->UpdateBalance($second_curr,$id);
				}
			}
		}
		// if trade order is submitted by post ----------------
		if(($this->request->data)){
				if(stristr( $_SERVER['HTTP_REFERER'],COMPANY_URL)===FALSE){return $this->redirect('/login');exit;}
			$data = array(
			'page.refresh' => true
			);
			Details::find('all')->save($data);
			$data = array(
				'refresh' => true
			);
			Trades::find('all',array(
				'conditions' => array('trade'=>$title)
			))->save($data);
			$commission = Parameters::find('first',
				array('conditions'=>array('commission'=>true))
			);
			$SellMultiple = $this->request->data['SellMultiple'];
			$BuyMultiple = $this->request->data['BuyMultiple'];
			$SellIDs = $this->request->data['SellIDs'];
			$BuyIDs = $this->request->data['BuyIDs'];

			

			$Action = $this->request->data['Action'];
			if($Action == "Buy"){
				if((float)$this->request->data['BuyAmount']<=0){
					$this->redirect($this->request->params);
				}
				if((float)$this->request->data['BuyPriceper']<=0){
					$this->redirect($this->request->params);
				}				
				if((float)$this->request->data['BuyCommission']<=0){
					$this->redirect($this->request->params);
				}				
				if((float)$this->request->data['BuyCommissionAmount']<=0){
					$this->redirect($this->request->params);
				}				
				
				$PendingAction = 'Sell';
				$FirstCurrency = $this->request->data['BuyFirstCurrency'];
				$SecondCurrency = $this->request->data['BuySecondCurrency'];
				$Commission = $this->request->data['BuyCommission'];
//				$CommissionAmount = $this->request->data['BuyCommissionAmount'];				
				
				$CommissionCurrency = $this->request->data['BuyCommissionCurrency'];								
				$Amount = $this->request->data['BuyAmount'];
				$PerPrice = $this->request->data['BuyPriceper'];
				$BalanceAmount = $details['balance'][$SecondCurrency];
				
				$CommissionAmount = $Amount * $commission['value'] / 100 ;
				
				$NewBalanceAmount = round($BalanceAmount - ($Amount * $PerPrice),8);
				$Currency = 'balance.'.$SecondCurrency;
				// Update balance of user with NewBalance Amount
				$data = array(
					'balance.'.$SecondCurrency => (float)($NewBalanceAmount),
				);
				Details::find('all',
					array('conditions'=>array('user_id'=>$id))
				)->save($data);
			}
			
			if($Action == "Sell"){
				if((float)$this->request->data['SellAmount']<=0){
					$this->redirect($this->request->params);
				}
				if((float)$this->request->data['SellPriceper']<=0){
					$this->redirect($this->request->params);
				}				
				if((float)$this->request->data['SellCommission']<=0){
					$this->redirect($this->request->params);
				}				
				if((float)$this->request->data['SellCommissionAmount']<=0){
					$this->redirect($this->request->params);
				}				
				$PendingAction = 'Buy';			
				$FirstCurrency = $this->request->data['SellFirstCurrency'];
				$SecondCurrency = $this->request->data['SellSecondCurrency'];
				$Commission = $this->request->data['SellCommission'];
//				$CommissionAmount = $this->request->data['SellCommissionAmount'];				
				$CommissionCurrency = $this->request->data['SellCommissionCurrency'];								
				$Amount = $this->request->data['SellAmount'];
				$PerPrice = $this->request->data['SellPriceper'];				
				$BalanceAmount = $details['balance'][$FirstCurrency];
				$CommissionAmount = $Amount * $PerPrice * $commission['value'] / 100;
				$NewBalanceAmount = round($BalanceAmount - ($Amount),8);
				$Currency = 'balance.'.$FirstCurrency;
				// Update balance of user with NewBalance Amount				
				$data = array(
					'balance.'.$FirstCurrency => (float)($NewBalanceAmount),
				);
				Details::find('all',
					array('conditions'=>array('user_id'=>$id))
				)->save($data);
			}
			$data = array(
				'Action' => $Action,
				'FirstCurrency' => $FirstCurrency,
				'SecondCurrency' => $SecondCurrency,
				'CommissionPercent' => (float)($Commission),
				'Commission.Amount' => (float)($CommissionAmount),
				'Commission.Currency' => $CommissionCurrency,				
				'OpeningBalance.'.$FirstCurrency => $details['balance'][$FirstCurrency],
				'OpeningBalance.'.$SecondCurrency => $details['balance'][$SecondCurrency],
				'Amount' => (float)($Amount),
				'PerPrice' => (float)($PerPrice),
				'DateTime' => new \MongoDate(),
				'Completed' => 'N',
				'IP' => $_SERVER['REMOTE_ADDR'],
				'username' => $user['username'],
				'user_id' => $user['_id'],
			);
					// Create Order for the user
			$orders = Orders::create();			
			$orders->save($data);
			$order_id = $orders->_id;

			
			$this->SendEmails($order_id,$user['_id']);
//			$this->SendFriendsEmails($order_id,$user['_id']);			
/*
			if($PendingAction=="Buy"){
				$PendingOrders = Orders::find('all',
				array(
					'conditions'=> array(
						'Action' => $PendingAction,
						'FirstCurrency' => $FirstCurrency,
						'SecondCurrency' => $SecondCurrency,
						'Completed' => 'N',
						'user_id' => array('$ne' => $user['_id']),
						'PerPrice' => array('$gte'=>(float)($PerPrice)),
					),
					'order'=>array('DateTime'=>'ASC')
				));
			}
			if($PendingAction=="Sell"){
				$PendingOrders = Orders::find('all',
				array(
					'conditions'=> array(
						'Action' => $PendingAction,
						'FirstCurrency' => $FirstCurrency,
						'SecondCurrency' => $SecondCurrency,
						'Completed' => 'N',
						'user_id' => array('$ne' => $user['_id']),
						'PerPrice' => array('$lte'=>(float)($PerPrice)),
					),
					'order'=>array('DateTime'=>'ASC')
				));
			}
			
				foreach($PendingOrders as $PO){
					$PreviousOrder = 	$this->ChangePreviousOrder($PO['_id'],$order_id,$PerPrice,$user);
					$this->updateBalance($PO['_id']);
					$this->SendOrderCompleteEmails($PO['_id'],$PO['user_id']);						
					
					$NewOrder = $this->ChangeCurrentOrder($order_id,$PO['_id'],$PO['Amount'],$user);					
					$this->updateBalance($order_id);
					$this->SendOrderCompleteEmails($order_id,$user['_id']);						
					
					if($NewOrder==false){
						break;
					}
					$order_id = $NewOrder;
				}
//				 exit;
			$this->redirect($this->request->params);			// get out of this page and load the page again without POST!			
			*/
			
			
			if($SellMultiple=="Y" || $BuyMultiple=="Y"){
				if($SellMultiple=="Y"){
					$IDs = $SellIDs;
				}
				if($BuyMultiple=="Y"){
					$IDs = $BuyIDs;
				}
				
				$ids = explode(",",$IDs);

				foreach ($ids as $id){
					if($id!=""){
						$PO = Orders::find('first', array(
							'conditions' => array('_id' => (string)($id))
						));
//							print_r($id);
//							print_r($PO['user_id']);
//							print_r($PO['username']);
//							print_r($PO['_id']);
								
						$data = array(
							'Completed' => 'Y',
							'Transact.id'=> $order_id,
							'Transact.username' => $user['username'],
							'Transact.user_id' => $user['_id'],
							'Transact.DateTime' => new \MongoDate(),
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>(string)$PO['_id']))
						)->save($data);

						$orders = Orders::find('first',
							array('conditions'=>array('_id'=>(string)$PO['_id']))
						);
						
						$data = array(
							'Completed' => 'Y',
							'Transact.id'=> $PO['_id'],
							'Transact.username' => $PO['username'],
							'Transact.user_id' => $PO['user_id'],
							'Transact.DateTime' => new \MongoDate(),														
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>(string)$order_id))
						)->save($data);

						$this->updateBalance($order_id);
						$this->updateBalance($PO['_id']);
						$this->SendOrderCompleteEmails($order_id,$user['_id']);
						$this->SendOrderCompleteEmails($PO['_id'],$PO['user_id']);						
					}
				}
		
				$this->redirect($this->request->params);
			}
			
			
			
				//Start FOR loop-----------------------------------------------------
				$PendingOrders = Orders::find('all',
				array(
					'conditions'=> array(
						'Action' => $PendingAction,
						'FirstCurrency' => $FirstCurrency,
						'SecondCurrency' => $SecondCurrency,
						'Completed' => 'N',
						'user_id' => array('$ne' => $user['_id']),
						'PerPrice' => (float)$PerPrice,
					),
					'order'=>array('DateTime'=>'ASC')
				));

				foreach ($PendingOrders as $PO){
					if((float)$PO['Amount']==(float)($Amount)){
 						$data = array(
							'Completed' => 'Y',
							'Transact.id'=> $order_id,
							'Transact.username' => $user['username'],
							'Transact.user_id' => $user['_id'],
							'Transact.DateTime' => new \MongoDate(),
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>$PO['_id']))
						)->save($data);

						$orders = Orders::find('first',
							array('conditions'=>array('_id'=>$PO['_id']))
						);
						
						$data = array(
							'Completed' => 'Y',
							'Transact.id'=> $PO['_id'],
							'Transact.username' => $PO['username'],
							'Transact.user_id' => $PO['user_id'],
							'Transact.DateTime' => new \MongoDate(),														
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>$order_id))
						)->save($data);

						$this->updateBalance($order_id);
						$this->updateBalance($PO['_id']);
						$this->SendOrderCompleteEmails($order_id,$user['_id']);
						$this->SendOrderCompleteEmails($PO['_id'],$PO['user_id']);						
						break;
					}
					
					if((float)$PO['Amount']>(float)($Amount)){
						// Update Previous Order with New Order Amount and New Commission and Transact User 
						if($PO['Action']=="Buy"){
							$PrevCommAmount = round(($PO['CommissionPercent'] * ($Amount) )/100,8);
							$CurrCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount'] - $Amount) * $PO['PrePrice'])/100,8);							
							$PrevCommCurr = $PO['FirstCurrency'];
							$CurrCommCurr = $PO['SecondCurrency'];							
						}else{
							$PrevCommAmount = round((float)$PO['CommissionPercent'] * (float)($Amount) * (float)($PerPrice)/100,8);
							$CurrCommAmount = round(($PO['CommissionPercent'] * ($Amount) )/100,8);														
							$PrevCommCurr = $PO['SecondCurrency'];
							$CurrCommCurr = $PO['FirstCurrency'];														
						}

						$data = array(
							'Commission.Amount' => (float)$PrevCommAmount,
							'Amount' => (float)($Amount),
							'Completed' => 'Y',
							'Transact.id'=> $order_id,
							'Transact.username' => $user['username'],
							'Transact.user_id' => $user['_id'],
							'Transact.DateTime' => new \MongoDate(),														
							'Order'=>'P>C: Update Previous Commission and Amount and Complete Order'							
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>$PO['_id']))
						)->save($data);

						// --------------------Complete
						// Create new Order for Previous Order so that the order tallies
						if($PO['Action']=="Buy"){
							$PrevCommAmount = round((float)(round((float)$PO['Amount'] - (float)($Amount),8)) * (float)($PO['CommissionPercent']) /100,8);
						}else{
							$PrevCommAmount = round((float)(round((float)$PO['Amount'] - (float)($Amount),8)) * (float)($PO['PerPrice']) * (float)($PO['CommissionPercent']) /100,8);						
						}
						$data = array(
							'Amount' => (float)(round((float)$PO['Amount'] - (float)($Amount),8)),
							'Action' => $PO['Action'],
							'FirstCurrency' => $PO['FirstCurrency'],
							'SecondCurrency' => $PO['SecondCurrency'],
							'CommissionPercent' => (float)($PO['CommissionPercent']),
/////////////////////////////////////////////////////////////////////////////////////////////
							'Commission.Amount' => (float)($PrevCommAmount),
							'Commission.Currency' => $PrevCommCurr,				
/////////////////////////////////////////////////////////////////////////////////////////////							
				'OpeningBalance.'.$PO['FirstCurrency'] => $PO['OpeningBalance'][$PO['FirstCurrency']],
				'OpeningBalance.'.$PO['SecondCurrency'] => $PO['OpeningBalance'][$PO['SecondCurrency']],

							'PerPrice' => (float)($PO['PerPrice']),
							'DateTime' => $PO['DateTime'],
							'Completed' => 'N',
							'IP' => $PO['IP'],
							'username' => $PO['username'],
							'user_id' => $PO['user_id'],
							'Order'=>'P>C: Create New Previous Order with Balance details'
						);
						$orders = Orders::create();	
						$orders->save($data);
						//-------------------Complete
						//Update New order with Transact User
						$data = array(
							'Completed' => 'Y',
							'Transact.id'=> $PO['_id'],
							'Transact.username' => $PO['username'],
							'Transact.user_id' => $PO['user_id'],
							'Transact.DateTime' => new \MongoDate(),														
							'Order'=>'P>C: Update current order no change in commission or amount'							
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>$order_id))
						)->save($data);

						//---------------------Complete
						//To update Balance						
						$this->updateBalance($order_id);
						$this->updateBalance($PO['_id']);
						$this->SendOrderCompleteEmails($order_id,$user['_id']);
						$this->SendOrderCompleteEmails($PO['_id'],$PO['user_id']);						
						break;
					}
					if((float)$PO['Amount']<(float)($Amount)){
						// Update Previous Order with New Order Amount and New Commission and Transact User 
					
						if($PO['Action']=="Buy"){
							$PrevCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount'] - $Amount) )/100,8);
							$CurrCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount'] - $Amount) * $PO['PrePrice'])/100,8);							
							$PrevCommCurr = $PO['FirstCurrency'];
							$CurrCommCurr = $PO['SecondCurrency'];							
						}else{
							$PrevCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount'] - $Amount) * $PO['PerPrice'])/100,8);
							$CurrCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount'] - $Amount) )/100,8);														
							$PrevCommCurr = $PO['SecondCurrency'];
							$CurrCommCurr = $PO['FirstCurrency'];														
						}
						if($PO['Action']=="Buy"){
							$PrevCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount']) )/100,8);
						}else{
							$PrevCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount']) * $PO['PerPrice'])/100,8);
						}
						$data = array(
							'Commission.Amount' => (float)$PrevCommAmount,
							'Amount' => (float)($PO['Amount']),						
							'Completed' => 'Y',
							'Transact.id'=> $order_id,
							'Transact.username' => $user['username'],
							'Transact.user_id' => $user['_id'],
							'Transact.DateTime' => new \MongoDate(),														
							'Order'=>'P<C: Update Previous Record'
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>$PO['_id']))
						)->save($data);

						//--------------------Complete
						// Update current order with new commission and amount
						if($PO['Action']=="Buy"){
							$CurrCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount']) * $PO['PerPrice'] /100 ),8);
						}else{
							$CurrCommAmount = round(($PO['CommissionPercent'] * ($PO['Amount']) /100 ),8);;
						}
						$data = array(
							'Commission.Amount' => (float)$CurrCommAmount,
							'Amount' => (float)($PO['Amount']),
							'Completed' => 'Y',
							'Transact.id'=> $PO['_id'],
							'Transact.username' => $PO['username'],
							'Transact.user_id' => $PO['user_id'],
							'Transact.DateTime' => new \MongoDate(),														
							'Order'=>'P<C: Update current record'							
						);
						$orders = Orders::find('all',
							array('conditions'=>array('_id'=>$order_id))
						)->save($data);

						//--------------------Complete
						//Create a new order of pending amount 
						if($PO['Action']=='Buy'){
							$CurrCommAmount = round(($PO['CommissionPercent'] * ((float)(round((float)($Amount) - (float)$PO['Amount'],8))) * $PerPrice /100 ),8);
						}else{
							$CurrCommAmount = round(($PO['CommissionPercent'] * ((float)(round((float)($Amount) - (float)$PO['Amount'],8)))/100 ),8);;
						}
						$data = array(
							'Action' => $Action,
							'FirstCurrency' => $FirstCurrency,
							'SecondCurrency' => $SecondCurrency,
							'CommissionPercent' => (float)($Commission),
///////////////////////////////////////////////////////////////////////////////////////////////////////
							'Commission.Amount' => (float)($CurrCommAmount),
							'Commission.Currency' => $CurrCommCurr,				
///////////////////////////////////////////////////////////////////////////////////////////////////////							
							'Amount' => (float)(round((float)($Amount) - (float)$PO['Amount'],8)),
							'PerPrice' => (float)($PerPrice),
							'DateTime' => new \MongoDate(),
							'Completed' => 'N',
							'IP' => $_SERVER['REMOTE_ADDR'],
							'username' => $user['username'],
							'user_id' => $user['_id'],
							'Order'=>'P<C: Create New Previous Order with Balance details'
						);
						$orders = Orders::create();	
						$orders->save($data);
						$this->updateBalance($order_id);
						$this->updateBalance($PO['_id']);
						$this->SendOrderCompleteEmails($order_id,$user['_id']);
						$this->SendOrderCompleteEmails($PO['_id'],$PO['user_id']);						
						break;
					}
			}
			// End FOR Loop -----------------------------------
//			exit; // Temporary EXIT 
			$this->redirect($this->request->params);			// get out of this page and load the page again without POST!

		} // submitted by post!
		
		// without POST... load the page!
		$details = Details::find('first',
			array('conditions'=>array('user_id'=>$id))
		);
		
		$mongodb = Connections::get('default')->connection;
		$OrderFunctions = new OrderFunctions();
		
		$TotalSellOrders = $OrderFunctions->TotalSellOrders($first_curr,$second_curr);
		$TotalBuyOrders = $OrderFunctions->TotalBuyOrders($first_curr,$second_curr);
		$SellOrders = $OrderFunctions->SellOrders($first_curr,$second_curr);
		$BuyOrders = $OrderFunctions->BuyOrders($first_curr,$second_curr);
		$YourOrders = $OrderFunctions->ExYourOrders($first_curr,$second_curr,$id);
		$YourCompleteOrders = $OrderFunctions->ExYourCompleteOrders($first_curr,$second_curr,$id);
		
		$page = Pages::find('first',array(
			'conditions'=>array('pagename'=>'ex/x/'.$currency)
		));
		$this->SetForecastGraph($SellOrders,$BuyOrders,$first_curr,$second_curr);
		$title = $page['title'];
		$keywords = $page['keywords'];
		$description = $page['description'];
		$settings = Settings::find('first');

		return compact('title','details','SellOrders','BuyOrders','TotalSellOrders','TotalBuyOrders','YourOrders','YourCompleteOrders','keywords','description','settings','currencyStatus');
	}
	
	public function dashboard() {
	if($this->request->query['json']==true){
		$this->_render['type'] = 'json';		
	}
	
		$user = Session::read('member');
		$id = $user['_id'];
		if ($user==""){		return $this->redirect('/login');exit;}
		$details = Details::find('first',
			array('conditions'=>array('user_id'=>$id))
		);
		
		
		
		$trades = Trades::find('all');
		$YourOrders = array();
		foreach($trades as $t){
			$YourOrders['Buy'] = $this->YourOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$YourOrders['Sell'] = $this->YourOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));			
			$YourCompleteOrders['Buy'] = $this->YourCompleteOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$YourCompleteOrders['Sell'] = $this->YourCompleteOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));			
		}
		$Commissions = $this->TotalCommissions($id);
		$CompletedCommissions = $this->CompletedTotalCommissions($id);		
		$RequestFriends = $this->RequestFriend($id);
		$UsersRegistered = Details::count();
		$functions = new Functions();
		$OnlineUsers = 	$functions->OnlineUsers();
		foreach($trades as $t){
			$TotalOrders['Buy'] = $this->TotalOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$TotalOrders['Sell'] = $this->TotalOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));			
			$TotalCompleteOrders['Buy'] = $this->TotalCompleteOrders($id,'Buy',substr($t['trade'],0,3),substr($t['trade'],4,3));
			$TotalCompleteOrders['Sell'] = $this->TotalCompleteOrders($id,'Sell',substr($t['trade'],0,3),substr($t['trade'],4,3));						
		}
		$title = "Dashboard";
		$keywords = "Dashboard, trading platform, bitcoin exchange, ";
		$description = "Dashboard for trading platform for bitcoin exchange";
		$settings = Settings::find('first');
		return compact('title','details','YourOrders','Commissions','CompletedCommissions','YourCompleteOrders','RequestFriends','UsersRegistered','OnlineUsers','TotalOrders','TotalCompleteOrders','keywords','description','settings','user');
	}

	public function TotalCommissions($id){
		$mongodb = Connections::get('default')->connection;
		$Commissions = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Completed'=>'$Completed',
					'user_id'=>'$user_id',					
					'CommissionAmount'=>'$Commission.Amount',
					'CommissionCurrency'=>'$Commission.Currency',					
				)),
				array('$match'=>array(
					'Completed'=>'N',
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'CommissionCurrency'=>'$CommissionCurrency',						
						),
					'Commission' => array('$sum' => '$CommissionAmount'),  
					'No' => array('$sum'=>1)					
				)),
			)
		));
		return $Commissions;
	}
	public function CompletedTotalCommissions($id){
		$mongodb = Connections::get('default')->connection;
		$Commissions = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Completed'=>'$Completed',
					'user_id'=>'$user_id',					
					'CommissionAmount'=>'$Commission.Amount',
					'CommissionCurrency'=>'$Commission.Currency',					
				)),
				array('$match'=>array(
					'Completed'=>'Y',
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'CommissionCurrency'=>'$CommissionCurrency',						
						),
					'Commission' => array('$sum' => '$CommissionAmount'),  
					'No' => array('$sum'=>1)					
				)),
			)
		));
		return $Commissions;
	}

	public function YourOrders($id,$Action,$FirstCurrency,$SecondCurrency){
		$mongodb = Connections::get('default')->connection;
		$YourOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action' => '$Action',
					'user_id' => '$user_id',					
					'Amount'=>'$Amount',
					'PerPrice'=>'$PerPrice',
					'Completed'=>'$Completed',
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),					
				)),
				array('$match'=>array(
					'Completed'=>'N',
					'Action'=>$Action,										
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'Action'=>'$Action',				
						'FirstCurrency'=>'$FirstCurrency',
						'SecondCurrency'=>'$SecondCurrency',						
						),
					'Amount' => array('$sum' => '$Amount'),  
					'TotalAmount' => array('$sum' => '$TotalAmount'), 										
					'No' => array('$sum'=>1)					
				)),
				array('$sort'=>array(
					'_id.Action'=>1,
				))
			)
		));
	return $YourOrders;
	
	}

	public function TotalOrders($id,$Action,$FirstCurrency,$SecondCurrency){
		$mongodb = Connections::get('default')->connection;
		$TotalOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action' => '$Action',
					'user_id' => '$user_id',					
					'Amount'=>'$Amount',
					'PerPrice'=>'$PerPrice',
					'Completed'=>'$Completed',
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),					
				)),
				array('$match'=>array(
					'Completed'=>'N',
					'Action'=>$Action,										
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'Action'=>'$Action',				
						'FirstCurrency'=>'$FirstCurrency',
						'SecondCurrency'=>'$SecondCurrency',						
						),
					'Amount' => array('$sum' => '$Amount'),  
					'TotalAmount' => array('$sum' => '$TotalAmount'), 										
					'No' => array('$sum'=>1)					
				)),
				array('$sort'=>array(
					'_id.Action'=>1,
				))
			)
		));
	return $TotalOrders;
	
	}

	public function YourCompleteOrders($id,$Action,$FirstCurrency,$SecondCurrency){
		$mongodb = Connections::get('default')->connection;
		$YourCompleteOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action' => '$Action',
					'user_id' => '$user_id',					
					'Amount'=>'$Amount',
					'PerPrice'=>'$PerPrice',
					'Completed'=>'$Completed',
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),					
				)),
				array('$match'=>array(
					'Completed'=>'Y',
					'Action'=>$Action,										
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'Action'=>'$Action',				
						'FirstCurrency'=>'$FirstCurrency',
						'SecondCurrency'=>'$SecondCurrency',						
						),
					'Amount' => array('$sum' => '$Amount'),  
					'TotalAmount' => array('$sum' => '$TotalAmount'), 										
					'No' => array('$sum'=>1)					
				)),
				array('$sort'=>array(
					'_id.Action'=>1,
				))
			)
		));
	return $YourCompleteOrders;
	}
	public function TotalCompleteOrders($id,$Action,$FirstCurrency,$SecondCurrency){
		$mongodb = Connections::get('default')->connection;
		$TotalCompleteOrders = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'Action' => '$Action',
					'user_id' => '$user_id',					
					'Amount'=>'$Amount',
					'PerPrice'=>'$PerPrice',
					'Completed'=>'$Completed',
					'FirstCurrency'=>'$FirstCurrency',
					'SecondCurrency'=>'$SecondCurrency',					
					'TotalAmount' => array('$multiply' => array('$Amount','$PerPrice')),					
				)),
				array('$match'=>array(
					'Completed'=>'Y',
					'Action'=>$Action,										
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'Action'=>'$Action',				
						'FirstCurrency'=>'$FirstCurrency',
						'SecondCurrency'=>'$SecondCurrency',						
						),
					'Amount' => array('$sum' => '$Amount'),  
					'TotalAmount' => array('$sum' => '$TotalAmount'), 										
					'No' => array('$sum'=>1)					
				)),
				array('$sort'=>array(
					'_id.Action'=>1,
				))
			)
		));
	return $TotalCompleteOrders;
	}
	
	public function RemoveOrder($OrderID,$ID,$back){
		$Orders = Orders::find('first', array(
			'conditions' => array('_id' => new MongoID($ID))
		));
		$data = array(
			'page.refresh' => true
		);
		Details::find('all')->save($data);

		if($Orders['Completed']=='N')		{
			$details = Details::find('first', array(
				'conditions' => array('user_id'=>(string)$Orders['user_id'])
			));
			if($Orders['Action']=='Buy'){
				$balanceFirst = 'balance.'.$Orders['FirstCurrency'];
				$balanceSecond = 'balance.'.$Orders['SecondCurrency'];
				$data = array(
					$balanceSecond => (float)($details[$balanceSecond] + $Orders['PerPrice']*$Orders['Amount'])
				);

				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['user_id'], 'username'=>$Orders['username']
						)
				))->save($data);
			}
			if($Orders['Action']=='Sell'){
				$balanceFirst = 'balance.'.$Orders['FirstCurrency'];
				$balanceSecond = 'balance.'.$Orders['SecondCurrency'];
				$data = array(
					$balanceFirst => (float)($details[$balanceFirst] + (float)$Orders['Amount'])
				);
		
				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['user_id'], 
						'username'=>$Orders['username']
						)
				))->save($data);
			}
			if(String::hash($Orders['_id'])==$OrderID){
				$Remove = Orders::remove(array('_id'=>$ID));
			}
				$data = array(
				'page.refresh' => true
				);
				Details::find('all')->save($data);
			
		}
		$this->redirect(array('controller'=>'ex','action'=>"x/".$back));		
	}
	public function updateBalance($id){
		$Orders = Orders::find('first', array(
			'conditions' => array('_id' => new MongoID($id))
		));

		$details = Details::find('first', array(
			'conditions' => array(
				'user_id'=>(string)$Orders['user_id'], 
				)
		));
		$CommissionAmount = $Orders['Commission.Amount'];
		$CommissionCurrency = $Orders['Commission.Currency'];
		$Action = $Orders['Action'];
		
			$balance = 'balance.'.$CommissionCurrency;
/* 			print_r("---------------<br>");
			print_r("Balance".$balance."<br>");
			print_r("DetailsBalance".$details[$balance]."<br>");
			print_r("CommissionAmount".$CommissionAmount."<br>");
 */
			$data = array(
				$balance => (float)$details[$balance] - (float)$CommissionAmount,
			);
//			print_r($data);
			$details = Details::find('all', array(
				'conditions' => array(
					'user_id'=>$Orders['user_id'], 
					)
			))->save($data);
			
 		if($Action=="Buy"){
				$Amount = (float)$Orders['Amount'];			
				$balance = 'balance.'.$Orders['FirstCurrency'];
				$details = Details::find('first', array(
					'conditions' => array(
						'user_id'=>(string)$Orders['user_id'], 
						)
				));
/* 			print_r("Buy------------<br>");
			print_r("Balance".$balance."<br>");
			print_r("DetailsBalance".$details[$balance]."<br>");
			print_r("Amount".$Amount."<br>");
 */				
				$data = array(
					$balance => (float)$details[$balance] + (float)$Amount,
				);
//			print_r($data);			
				$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['user_id'], 
						)
				))->save($data);
			}
			if($Action=="Sell"){
				$Amount = (float)$Orders['Amount'] * (float)$Orders['PerPrice'];			
				$balance = 'balance.'.$Orders['SecondCurrency'];
				$details = Details::find('first', array(
					'conditions' => array(
						'user_id'=>(string)$Orders['user_id'], 
						)
				));
/* 			print_r("Sell------------<br>");
			print_r("Balance".$balance."<br>");
			print_r("DetailsBalance".$details[$balance]."<br>");
			print_r("Amount".$Amount."<br>");
 */
				
				$data = array(
					$balance => (float)$details[$balance] + (float)$Amount,
				);
//			print_r($data);

					$details = Details::find('all', array(
					'conditions' => array(
						'user_id'=>$Orders['user_id'], 
						)
				))->save($data);
			}
			
			$multiSig = new MultiSig();
			$multiSig->transferMultiSig($id);
 	}
	public function RequestFriend($id){
	$mongodb = Connections::get('default')->connection;
		$RequestFriend = Orders::connection()->connection->command(array(
			'aggregate' => 'orders',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'user_id' => '$user_id',					
					'TransactUsername'=>'$Transact.username',
					'TransactUser_id'=>'$Transact.user_id',
					'TransactDateTime'=>'$Transact.DateTime',
					'Completed'=>'$Completed',
				)),
				array('$match'=>array(
					'Completed'=>'Y',
					'user_id'=>$id
					)),
				array('$group' => array( '_id' => array(
						'TransactUsername'=>'$TransactUsername',						
						'TransactUser_id'=>'$TransactUser_id',						
						),
						)),
				array('$sort'=>array(
					'TransactUsername'=>1,
				)),
				array('$limit'=>20)
			)
		));
	return $RequestFriend;
	}
	
	public function AddFriend($hashuser_id,$user_id,$username){
		if(String::hash($user_id)==$hashuser_id){
			$user = Session::read('member');
			$id = $user['_id'];
			$details = Details::find('first',
				array('conditions'=>array('user_id'=>$id))
			);
			$friends = $details['Friend'];

			$addfriend = array();
			if(count($friends)!=0){
				foreach ($friends as $ra){
					array_push($addfriend, $ra);
				}
			}
			array_push($addfriend,$username);
			$data = array('Friend'=>$addfriend);
//			print_r($data);
			$details = Details::find('all',
				array('conditions'=>array('user_id'=>$id))
			)->save($data);
		}
		$this->redirect(array('controller'=>'ex','action'=>"dashboard/",'locale'=>$locale));				
	}
	
	public function RemoveFriend($hashuser_id,$user_id,$username){
		if(String::hash($user_id)==$hashuser_id){
			$user = Session::read('member');
			$id = $user['_id'];
			$details = Details::find('first',
				array('conditions'=>array('user_id'=>$id))
			);
			$friends = $details['Friend'];

			$addfriend = array();
			if(count($friends)!=0){
				foreach ($friends as $ra){
					if($ra!=$username){
						array_push($addfriend, $ra);
					}
				}
			}
			$data = array('Friend'=>$addfriend);
//			print_r($data);
			$details = Details::find('all',
				array('conditions'=>array('user_id'=>$id))
			)->save($data);
		}
		$this->redirect(array('controller'=>'ex','action'=>"dashboard/",'locale'=>$locale));				
	}
	
	public function SendOrderCompleteEmails($order_id,$user_id){
		$order = Orders::find('first', array(
			'conditions'=>array('_id'=>new MongoID($order_id))
		));
		$user = Users::find('first', array(
			'conditions'=>array('_id'=>new MongoID($user_id))
		));
		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('order','user'),
			array(
				'controller' => 'ex',
				'template'=>'Complete',
				'type' => 'mail',
				'layout' => false
			)
		);

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Your order is complete");
		$message->setFrom(array(NOREPLY => 'Your order is complete'));
		$message->setTo($user['email']);
//		$message->addBcc(MAIL_1);
//		$message->addBcc(MAIL_2);			
//		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		$mailer->send($message);

	}

	public function SendFriendsEmails($order_id,$user_id){
	
		$order = Orders::find('first', array(
			'conditions'=>array('_id'=>new MongoID($order_id))
		));

		$user = Users::find('first', array(
			'conditions'=>array('_id'=>new MongoID($user_id))
		));


//*****************************************************************************
//*****************************************************************************
		$mongodb = Connections::get('default')->connection;
		$Friends = Details::connection()->connection->command(array(
			'aggregate' => 'details',
			'pipeline' => array( 
				array( '$project' => array(
					'_id'=>0,
					'user_id' => '$user_id',					
					'username'=>'$username',
					'Friend'=>'$Friend'
				)),
				array('$unwind'=>'$Friend'),
				array('$match'=>array(
					'Friend'=>$user['username'],
					)),
			),
			
		));
		$friends = array();
		if(count($Friends['result'])>0){
			foreach($Friends['result'] as $friend){
				array_push($friends,$friend['username']);
			}
		}
//		print_r($friends);
		$usersToSend = Users::find('all',array(
			'conditions' => array('username'=>array('$in'=>$friends)),
			'fields'=>array('email', 'username')
		));
		foreach($usersToSend as $userToSend){
				$sendEmailTo = $userToSend['email'];
				$sendUserName = $userToSend['username'];

//*****************************************************************************
//*****************************************************************************
	
		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));

		$body = $view->render(
			'template',
			compact('order','sendUserName'),
			array(
				'controller' => 'ex',
				'template'=>'FriendRequest',
				'type' => 'mail',
				'layout' => false
			)
		);

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Your friend placed an order");
		$message->setFrom(array(NOREPLY => 'Your friend placed an order'));
		$message->setTo($sendEmailTo);
//		$message->addBcc(MAIL_1);
//		$message->addBcc(MAIL_2);			
//		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		$mailer->send($message);
		}
	}
	
	public function SendEmails($order_id,$user_id){
		$order = Orders::find('first', array(
			'conditions'=>array('_id'=>new MongoID($order_id))
		));
		$user = Users::find('first', array(
			'conditions'=>array('_id'=>new MongoID($user_id))
		));
	
		$view  = new View(array(
			'loader' => 'File',
			'renderer' => 'File',
			'paths' => array(
				'template' => '{:library}/views/{:controller}/{:template}.{:type}.php'
			)
		));
		$body = $view->render(
			'template',
			compact('order','user'),
			array(
				'controller' => 'ex',
				'template'=>'OrderRequest',
				'type' => 'mail',
				'layout' => false
			)
		);

		$transport = Swift_MailTransport::newInstance();
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance();
		$message->setSubject("Your order is placed");
		$message->setFrom(array(NOREPLY => 'Your order is placed'));
		$message->setTo($user['email']);
//		$message->addBcc(MAIL_1);
//		$message->addBcc(MAIL_2);			
//		$message->addBcc(MAIL_3);		

		$message->setBody($body,'text/html');
		$mailer->send($message);

	}
	public function SetForecastGraph($SellOrders,$BuyOrders,$first_curr,$second_curr){
	$datay1 = array();$datay2 = array(); $labels = array();
	$total = 0;
	for($i=0;$i<count($SellOrders['result']);$i++){
		$total = $total + $SellOrders['result'][$i]['Amount'];
	}
	$totalx = 0;
	rsort($SellOrders['result']);
	for($i=0;$i<count($SellOrders['result']);$i++){
		$datay2[$i] = 0;
		$totalx = $totalx + (float)$SellOrders['result'][$i-1]['Amount'];		
		$datay1[$i] = $total - $totalx;
		$labels[$i+count($BuyOrders['result'])] = round($SellOrders['result'][$i]['_id']['PerPrice'],1);
	}
	$total = 0;
//	print_r("/");
	for($i=count($SellOrders['result']);$i<count($SellOrders['result'])+count($BuyOrders['result']);$i++){
//	print_r($i);
		$total = $total + $BuyOrders['result'][$i-count($SellOrders['result'])]['Amount'];
		$datay2[$i] = $total;
		$datay1[$i] = 0;
		$labels[$i-count($SellOrders['result'])] = round($BuyOrders['result'][$i-count($SellOrders['result'])]['_id']['PerPrice'],1);
	}
sort($datay1);
rsort($datay2);
sort($labels);
for($i=0;$i<count($labels);$i++){
	if($i%2){
	$labels[$i] = ".";
	}
}
if(count($datay1)<=1){$datay1 = array(0,1);}
if(count($datay2)<=1){$datay2 = array(1,0);}
if(count($labels)<=1){$labels = array(0,1);}

// Setup the graph
$graph = new Graph(700,300);
$graph->SetScale("textlin");


$graph->img->SetAntiAliasing(false);
$graph->title->Set('Orders');
$graph->SetBox(false);

$graph->img->SetAntiAliasing();

$graph->yaxis->HideZeroLabel();
$graph->yaxis->HideLine(false);
$graph->yaxis->HideTicks(false,false);

$graph->xgrid->Show();
$graph->xgrid->SetLineStyle("solid");
$graph->xaxis->SetTickLabels($labels);
$graph->xgrid->SetColor('#E3E3E3');

// Create the first line
$p1 = new LinePlot($datay1);
$graph->Add($p1);
$p1->SetColor("#B22222");
$p1->SetLegend('Sell');

// Create the second line
$p2 = new LinePlot($datay2);
$graph->Add($p2);
$p2->SetColor("#6495ED");
$p2->SetLegend('Buy');


$graph->legend->SetFrameWeight(1);

// Output line
	$image = $graph->Stroke(_IMG_HANDLER);
	$fileName = LITHIUM_APP_PATH . '/webroot/documents/'. $first_curr."_".$second_curr."-T.png";
	$graph->img->Stream($fileName);
	
	
	
	}
	public function SetGraph($first_curr,$second_curr){
		$updates = new UpdatesController();
		$values = $updates->OHLC($first_curr,$second_curr);
			$datay = array();
			$days = array();
			$datav = array();
			$alts = array();
			$targ = array();
			if(count($values['result'])<=1){
				$datay = array(0,0,0,0,0,0,0,0);
				$datav = array(0,0);
				$days = array('N','N');
			}
			$i = 0;
			foreach($values['result'] as $result){
				array_push($datay, $result['Open']);
				array_push($datay, $result['High']);	
				array_push($datay, $result['Low']);		
				array_push($datay, $result['Close']);		
				array_push($datav, $result['Volume']);		
				array_push($alts, $result['Volume']);
				array_push($targ,"#");
				if($i%2){
				array_push($days,$result['_id']['day']."/".$result['_id']['month']."\n ".$result['_id']['hour']."h");
				}else{
				array_push($days,"");
				}
				$i++;
			}
		$graph = new Graph(750,300);

		$stock_color_list = array(
			'pos_lcolor' => '#555555',
			'pos_color'  => '#5555ee',
			'neg_lcolor' => '#555555',
			'neg_color'  => '#ee5555',
		);

		$color_list = array(
			'#cc0055',
			'#00cc55',
		);
		$graph->SetScale("textlin");
		$graph->SetFrame(true);
		$graph->yaxis->HideTicks(true);
		$graph->SetColor('#00fff');
		$graph->tabtitle->Set($first_curr . " / " . $second_curr." - Volume");
		$p1 = new StockPlot($datay);
		$p1->SetCenter();
		$p1->SetWidth(6);
		$p1->SetColor(
			$stock_color_list['pos_lcolor'],
			$stock_color_list['pos_color'],
			$stock_color_list['neg_lcolor'],
			$stock_color_list['neg_color']
		);
		$p1->HideEndLines(false);
		$p1->SetCSIMTargets($targ,$alts);
		$p1->SetLegend($first_curr . " / " . $second_curr);
		
		$gb = new BarPlot($datav);
		$gb->SetCSIMTargets($targ,$alts);
		$gb->SetFillColor('#ffffdd');
		$gb->SetWidth(6);
		
		$gb->SetLegend('Volume');
		
		$graph->xaxis->SetTickLabels($days);
		$graph->SetY2Scale('lin');
		$graph->y2scale->SetAutoMin(min($datav)-.01);
		$graph->y2scale->SetAutoMax(max($datav)+1);
		$graph->y2axis->HideTicks(true);
		$graph->Add($p1);
		$graph->AddY2($gb);
		$graph->SetMargin(50,50,50,30);
		
		$graph->legend->SetFrameWeight(0);
		$graph->legend->SetShadow(false);
		
		$graph->legend->SetFillColor('#aaaaaa@0.7');
		$graph->legend->SetLineSpacing(8);
		$graph->legend->SetMarkAbsSize(8);
		$graph->legend->SetVColMargin(10);
		$graph->legend->SetHColMargin(15);
		$graph->legend->SetLeftMargin(15);
		$graph->legend->SetPos(0.08, 0.09);

		$image = $graph->Stroke(_IMG_HANDLER);
		$fileName = LITHIUM_APP_PATH . '/webroot/documents/'. $first_curr."_".$second_curr.".png";
		$graph->img->Stream($fileName);
	}
	
	public function ChangePreviousOrder($PO_id,$order_id,$PerPrice,$user){
		
		$PO = Orders::find('first',array(
			'conditions'=>array('_id'=>$PO_id)
		));
		if($PO['Action']=="Buy"){
			$PrevCommAmount = round(((float)$PO['CommissionPercent'] * ((float)$PO['Amount']) )/100,8);
		}else{
			$PrevCommAmount = round((float)$PO['CommissionPercent'] * (float)($PO['Amount']) * (float)($PerPrice)/100,8);
		}
		
		$POData = array(
			'Action' => $PO['Action'],
			'FirstCurrency' => $PO['FirstCurrency'],
			'SecondCurrency' => $PO['SecondCurrency'],
			'CommissionPercent' => (float)$PO['CommissionPercent'],
			'Commission.Amount' => (float)$PrevCommAmount,
			'Commission.Currency' => $PO['Commission']['Currency'],
			'Amount' => (float)$PO['Amount'],
			'PerPrice' => (float)$PerPrice,
			'DateTime' => $PO['DateTime'],
			'username' => $PO['username'],
			'IP' => $PO['IP'],			
			'user_id' => $PO['user_id'],
			'Completed' => 'Y',
			'Transact.id'=> $order_id,
			'Transact.username' => $user['username'],
			'Transact.user_id' => $user['_id'],
			'Transact.DateTime' => new \MongoDate(),														
			'Order'=>'P>C: Update Previous Commission and Amount and Complete Order'							
		);
//	print_r($POData)		;
		$PO = Orders::find('all',array(
			'conditions'=>array('_id'=>$PO_id)
		))->save($POData);




	}

	public function ChangeCurrentOrder($order_id,$PO_id,$PO_Amount,$user){
		$PO = Orders::find('first',array(
			'conditions'=>array('_id'=>$PO_id)
		));
		$CO = Orders::find('first',array(
			'conditions'=>array('_id'=>$order_id)
		));

		if($CO['Action']=="Buy"){
			$CurrCommAmount = round(((float)$CO['CommissionPercent'] * ((float)$PO_Amount) )/100,8);														
			$NewCommAmount = round(((float)$CO['CommissionPercent'] * ((float)$CO['Amount'] - (float)$PO_Amount) )/100,8);																	
		}else{
			$CurrCommAmount = round(((float)$CO['CommissionPercent'] * (float)($PO_Amount) * (float)$CO['PrePrice'])/100,8);							
			$NewCommAmount = round(((float)$CO['CommissionPercent'] * ((float)$CO['Amount'] - (float)$PO_Amount) * (float)$CO['PrePrice'])/100,8);										
		}

		$COData = array(
			'Action' => $CO['Action'],
			'FirstCurrency' => $CO['FirstCurrency'],
			'SecondCurrency' => $CO['SecondCurrency'],
			'CommissionPercent' => (float)$CO['CommissionPercent'],
			'Commission.Amount' => (float)$CurrCommAmount,
			'Commission.Currency' => $CO['Commission']['Currency'],
			'Amount' => (float)$PO_Amount,
			'PerPrice' => (float)$CO['PerPrice'],
			'DateTime' => $CO['DateTime'],
			'username' => $CO['username'],
			'IP' => $CO['IP'],			
			'user_id' => $CO['user_id'],
			'Completed' => 'Y',
			'Transact.id'=> $PO_id,
			'Transact.username' => $PO['username'],
			'Transact.user_id' => $PO['_id'],
			'Transact.DateTime' => new \MongoDate(),														
			'Order'=>'P>C: Update Current Commission and Amount and Complete Order'							
		);
		
//	print_r($COData)		;
			$data = array(
				'Action' => $CO['Action'],
				'FirstCurrency' => $CO['FirstCurrency'],
				'SecondCurrency' => $CO['SecondCurrency'],
				'CommissionPercent' => (float)$CO['CommissionPercent'],
				'Commission.Amount' => (float)$NewCommAmount,
				'Commission.Currency' => $CO['Commission']['Currency'],				
				'Amount' => (float)($CO['Amount'] - $PO_Amount),
				'PerPrice' => (float)$CO['PerPrice'],
				'DateTime' => new \MongoDate(),
				'Completed' => 'N',
				'IP' => $_SERVER['REMOTE_ADDR'],
				'username' => $user['username'],
				'user_id' => $user['_id'],
			);
	
		$CO = Orders::find('all',array(
			'conditions'=>array('_id'=>$order_id)
		))->save($COData);
		
		// Create Order for the user
		if((float)($CO['Amount'] - $PO_Amount)>0){
			$orders = Orders::create();			
			$orders->save($data);
			$order_id = $orders->_id;
			$this->SendEmails($order_id,$user['_id']);						
			return $order_id;						
		}
		return false;
	}

		public function getKYC(){
		$kyc = $this->request->query['kyc'];
		
			$opts = array(
			  'http'=> array(
					'method'=> "GET",
					'user_agent'=> "MozillaXYZ/1.0"));
			$context = stream_context_create($opts);
			
		$json = file_get_contents('https://kycglobal.net/kyc/info/'.$kyc, false, $context);
		
		$details = json_decode($json);
		if($details->percent>80){
				return $this->render(array('json' => array(
				'success'=>1,
				'email'=>$details->email,
				'name'=>$details->details->Name->first . " ".$details->details->Name->last ,
				'phone'=>$details->details->Mobile,
				'score'=>$details->score,
				'percent'=>$details->percent
				)));
		}else{
				return $this->render(array('json' => array(
				'success'=>0)));
		}
	}
	
	public function Transfer($order_id){
		$multiSig = new MultiSig();
		$multiSig->transferMultiSig($order_id);
		
		return $this->render(array('json' => array(
				'success'=>0,
		)));
	}
	
		public function say($username=null,$amount=null,$currency=null){
		if(strtoupper($currency)=="BTC"){$currencyname=" Bitcoins.";}
		if(strtoupper($currency)=="XGC"){$currencyname=" GreenCoins.";}
		$layout = false;
		$view  = new View(array(
		'paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php',
			'layout'   => '{:library}/views/layouts/{:layout}.{:type}.php',
			)
			));
			$data = $username . " deposited " . $amount . $currencyname;
		echo $view->render(
		'all',
		compact('data'),
		array(
			'controller' => 'ex',
			'template'=>'say',
			'type' => 'xml',
			'layout' =>'default'
			)
		);	
		return $this->render(array('layout' => false));
	}


	public function saythis($code=null){
		$newcode = '';
		for($i=0;$i<=strlen($code);$i++){
			$newcode = $newcode . substr($code,$i,1).',,,,,';
		}

		$layout = false;
		$view  = new View(array(
		'paths' => array(
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php',
			'layout'   => '{:library}/views/layouts/{:layout}.{:type}.php',
			)
			));
			
		echo $view->render(
		'all',
		compact('newcode'),
		array(
			'controller' => 'ex',
			'template'=>'saythis',
			'type' => 'xml',
			'layout' =>'default'
			)
		);	
		return $this->render(array('layout' => false));
	}

}
?>