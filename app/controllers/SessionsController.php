<?php
namespace app\controllers;

use lithium\security\Auth;
use lithium\util\String;
use app\models\Users;
use app\models\Pages;
use app\models\Logins;
use app\models\Details;
use lithium\storage\Session;
use app\extensions\action\Functions;
use app\extensions\action\GoogleAuthenticator;

class SessionsController extends \lithium\action\Controller {

    public function add() {
					
					
		   //assume there's no problem with authentication
			$noauth = false;
			//perform the authentication check and redirect on success
			
			Session::delete('default');		
			Session::delete('member');			
			
			$opts = array(
			  'http'=> array(
					'method'=> "GET",
					'user_agent'=> "MozillaXYZ/1.0"));
			$context = stream_context_create($opts);
			
//			$response = file_get_contents("http://ipinfo.io/{$_SERVER['REMOTE_ADDR']}/json", false, $context);
			$response = file_get_contents("http://ip-api.com/json/{$_SERVER['REMOTE_ADDR']}",false, $context);
//			$response = file_get_contents("http://ip-api.com/json/107.6.11.112",false, $context);
//			print_r($response);
			$IPResponse = json_decode($response);
//			print_r($IPResponse);
			if($IPResponse->tor) {
		    // Display error message or something
					Auth::clear('member');
					Session::delete('default');
					return false;
			}
			if (Auth::check('member', $this->request)){
				//Redirect on successful login
				$loginpassword = $this->request->data['loginpassword'];
				$default = Auth::check('member', $this->request);
				$details = Details::find('first',array(
					'conditions' => array(
						'username'=>$default['username'],
						'user_id'=>(string)$default['_id']
						)
				));
				if($details['active']=="No"){
					Auth::clear('member');
					Session::delete('default');
					return $this->redirect('/');
					exit;
				}
				
				if($details["EmailPasswordSecurity"]==="true" || $details["EmailPasswordSecurity"]===null){
					if($details['oneCode']===$this->request->data['loginpassword']){
						$data = array(
							'oneCodeused'=>'Yes',
							'lastconnected'=>array(									
								'IP' => $IPResponse->query,
								'ISO'=> $IPResponse->countryCode,
								'country'=> $IPResponse->country,
								'hostname'=> $IPResponse->as,
								'city'=> $IPResponse->city,
								'region'=> $IPResponse->regionName,									
								'regionName'=> $IPResponse->region,									
								'lat'=> $IPResponse->lat,
								'lon'=> $IPResponse->lon,
								'org'=> $IPResponse->isp,									
								'postal'=> $IPResponse->zip,									
								'DateTime' => new \MongoDate(),
							)
						);
						$details = Details::find('first',array(
							'conditions' => array(
								'username'=>$default['username'],
								'user_id'=>(string)$default['_id']
							)
						))->save($data);
						$details = Details::find('first',array(
							'conditions' => array(
								'username'=>$default['username'],
								'user_id'=>(string)$default['_id']
								)
						));
					if($details["TOTP.Validate"]==1 && $details["TOTP.Login"]==true){
						$totp = $this->request->data['totp'];
						$ga = new GoogleAuthenticator();
						if($totp==""){
							Auth::clear('member');
							Session::delete('default');
						}else{
							$checkResult = $ga->verifyCode($details['secret'], $totp, 2);		
							if ($checkResult==1) {
								Session::write('default',$default);
								$user = Session::read('default');

/////////////////////////////////////////////////////////////////////////////////
								$function = new Functions();
								$IP = $function->get_ip_address();

								$data = array(
									'username' => $user['username'],
								'IP' => $IPResponse->query,
								'ISO'=> $IPResponse->countryCode,
								'country'=> $IPResponse->country,
								'hostname'=> $IPResponse->as,
								'city'=> $IPResponse->city,
								'region'=> $IPResponse->regionName,									
								'regionName'=> $IPResponse->region,									
								'lat'=> $IPResponse->lat,
								'lon'=> $IPResponse->lon,
								'org'=> $IPResponse->isp,									
								'postal'=> $IPResponse->zip,									
									'DateTime' => new \MongoDate(),
								);
								Logins::create()->save($data);
/////////////////////////////////////////////////////////////////////////////////								
								return $this->redirect('ex::dashboard');
								exit;
							}else{
								Auth::clear('member');
								Session::delete('default');
							}
						}
					}else{
						Session::write('default',$default);
						$user = Session::read('default');
/////////////////////////////////////////////////////////////////////////////////
								$function = new Functions();
								$IP = $function->get_ip_address();

								$data = array(
									'username' => $user['username'],
								'IP' => $IPResponse->query,
								'ISO'=> $IPResponse->countryCode,
								'country'=> $IPResponse->country,
								'hostname'=> $IPResponse->as,
								'city'=> $IPResponse->city,
								'region'=> $IPResponse->regionName,									
								'regionName'=> $IPResponse->region,									
								'lat'=> $IPResponse->lat,
								'lon'=> $IPResponse->lon,
								'org'=> $IPResponse->isp,									
								'postal'=> $IPResponse->zip,									
									'DateTime' => new \MongoDate(),
								);
								Logins::create()->save($data);
						/////////////////////////////////////////////////////////////////////////////////						
						return $this->redirect('ex::dashboard');
						exit;
					}
				}else{
					Auth::clear('member');
					Session::delete('default');
				}
				}else{
				
					$data = array(
							'oneCodeused'=>'Yes',
							'lastconnected'=>array(									
								'IP' => $IPResponse->query,
								'ISO'=> $IPResponse->countryCode,
								'country'=> $IPResponse->country,
								'hostname'=> $IPResponse->as,
								'city'=> $IPResponse->city,
								'region'=> $IPResponse->regionName,									
								'regionName'=> $IPResponse->region,									
								'lat'=> $IPResponse->lat,
								'lon'=> $IPResponse->lon,
								'org'=> $IPResponse->isp,									
								'postal'=> $IPResponse->zip,									
								'DateTime' => new \MongoDate(),
							)
						);
						$details = Details::find('first',array(
							'conditions' => array(
								'username'=>$default['username'],
								'user_id'=>(string)$default['_id']
							)
						))->save($data);
						$details = Details::find('first',array(
							'conditions' => array(
								'username'=>$default['username'],
								'user_id'=>(string)$default['_id']
								)
						));
						Session::write('default',$default);
						$user = Session::read('default');
/////////////////////////////////////////////////////////////////////////////////
								$function = new Functions();
								$IP = $function->get_ip_address();

								$data = array(
									'username' => $user['username'],
								'IP' => $IPResponse->query,
								'ISO'=> $IPResponse->countryCode,
								'country'=> $IPResponse->country,
								'hostname'=> $IPResponse->as,
								'city'=> $IPResponse->city,
								'region'=> $IPResponse->regionName,									
								'regionName'=> $IPResponse->region,									
								'lat'=> $IPResponse->lat,
								'lon'=> $IPResponse->lon,
								'org'=> $IPResponse->isp,									
								'postal'=> $IPResponse->zip,									
									'DateTime' => new \MongoDate(),
								);
								Logins::create()->save($data);
						/////////////////////////////////////////////////////////////////////////////////						
						return $this->redirect('ex::dashboard');
				
				
				}
			}
			//if theres still post data, and we weren't redirected above, then login failed

			if ($this->request->data){
				//Login failed, trigger the error message
				if(isset($this->request->query['check']) && $this->request->query['check']==SECURITY_CHECK){$check = $this->request->query['check'];}
				$noauth = true;
			}
			//Return noauth status
		$page = Pages::find('first',array(
			'conditions'=>array('pagename'=>'login')
		));

		$title = $page['title'];
		$keywords = $page['keywords'];
		$description = $page['description'];
			return compact('noauth','title','keywords','description');
			return $this->redirect('/');
			exit;
        // Handle failed authentication attempts
  }
	 public function delete() {
		Auth::clear('member');
		Session::delete('default');
		return $this->redirect('/');
		exit;
    }
}
?>