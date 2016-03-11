<?php
use lithium\util\String;
use app\extensions\action\Functions;

$function = new Functions();
?>
<style>
.Address_success{background-color: #9FFF9F;font-weight:bold}

</style>
<?php // echo $this->_render('element', 'funding_fiat_header');?>



<h2 id=tabs-examples>Deposit / Withdraw <?=$currency?>
<?php if($currency=="CAD" || $currency=="GBP"){?>
<br><small>(<?=$currency?> Deposits will be coming soon. Thank you for your patience while our bank resolves this issue)</small>
<?php }?>
</h2> 
	
		<div class="" data-example-id=togglable-tabs> 
			<ul id=myTabs class="nav nav-tabs nav-justified" role=tablist> 
				<li role=presentation class="active"><a href=#home id=home-tab role=tab data-toggle=tab aria-controls=home aria-expanded=true style="font-weight:bold;color:#5cb85c">Deposit <?=$currency?></a></strong</li> 
				<li role=presentation><a href=#profile role=tab id=profile-tab data-toggle=tab aria-controls=profile  style="font-weight:bold;color:#d9534f;">Withdraw <?=$currency?></a></li>
			</ul>

			<div id=myTabContent class="tab-content"  > 
			
			<div role=tabpanel class="tab-pane fade in active  tab-content-deposit" id=home aria-labelledby=home-tab> 
			<!-- //////////////////////////////////////////////////////////////////////////////////////-->
			<?php 
			if(count($depositRequest)==0){
			?>
			<div style=""><blockquote><small><strong>Note:</strong> Vantu Bank’s customer service may contact you to authenticate your expected payment. Please make sure the contact details below are valid. If Vantu Bank needs to reach you and is unable to do so within 48 hours your funds may be put on hold. The information you provide must be clear in order to prevent delays.</small></blockquote></div>
						<h2>Declaration of Source of Funds (DSF)</h2>
						<form method="POST" action="/users/deposit" class="form">
							<table class="table table-condensed table-bordered table-hover" >
						<tr>
							<th width="50%">FULL NAME OF YOUR ACCOUNT AT VANTU BANK</th>
							<td>ILS FIDUCIARIES (SWITZERLAND) SARL</td>
						</tr>
						<tr>
							<th>FULL ACCOUNT NUMBER OF YOUR ACCOUNT</th>
							<td><?=$currency?>-100-070378-<?php
								switch ($currency){
										case "USD":
										print_r("1");break;
										case "EUR":
										print_r("2");break;
										case "GBP":
										print_r("3");break;
										case "CAD":
										print_r("4");break;										
								}
							?>
							</td>
						</tr>
						<tr>
							<th>YOUR FULL NAME</th>
							<td><input type="text" name="fullName" id="fullName" class="form-control"></td>
						</tr>
						<tr>
							<th>YOUR TELEPHONE NUMBER</th>
							<td><input type="text" name="telephone" id="telephone" class="form-control"></td>
						</tr>
						<tr>
							<th>YOUR FULL PHYSICAL OR STREET ADDRESS <br>(<small>a PO Box number alone is not accepted</small>)</th>
							<td><input type="text" name="address" id="address" class="form-control"></td>
						</tr>
						<tr>
							<th>YOUR CITY, STATE, ZIP CODE, COUNTRY</th>
							<td><input type="text" name="addressDetail" id="addressDetail" class="form-control"></td>
						</tr>
						<tr>
							<th>YOUR EMAIL ADDRESS</th>
							<td><input type="text" class="form-control" name="emailShow" id="emailShow" value="<?=$user['email']?>" disabled>
							<input type="hidden" class="form-control" name="email" id="email" value="<?=$user['email']?>"></td>
						</tr>
						<tr>
							<th>YOUR TYPE OF OCCUPATION/BUSINESS</th>
							<td>
								<select class="form-control" name="occupation" id="occupation">
									<option>-- Select --</option>
									<option value='Accounting'>Accounting</option>
<option value='Architecture'>Architecture</option>
<option value='Community and Social Services'>Community and Social Services</option>
<option value='Computer and Software'>Computer and Software</option>
<option value='Designer'>Designer</option>
<option value='Education'>Education</option>
<option value='Engineering'>Engineering</option>
<option value='Entertainment'>Entertainment</option>
<option value='Farming'>Farming</option>
<option value='Fishing'>Fishing</option>
<option value='Food Industry'>Food Industry</option>
<option value='Forestry'>Forestry</option>
<option value='General Management'>General Management</option>
<option value='Healthcare Services'>Healthcare Services</option>
<option value='Installation and Repair'>Installation and Repair</option>
<option value='Lawyer'>Lawyer</option>
<option value='Manufacturing and Production'>Manufacturing and Production</option>
<option value='Media'>Media</option>
<option value='Mining and Extraction'>Mining and Extraction</option>
<option value='Office and Administrative Support'>Office and Administrative Support</option>
<option value='Personal Care and Service'>Personal Care and Service</option>
<option value='Physical Scientist'>Physical Scientist</option>
<option value='Private Investor'>Private Investor</option>
<option value='Property and Construction'>Property and Construction</option>
<option value='Property Maintenance'>Property Maintenance</option>
<option value='Protective Services'>Protective Services</option>
<option value='Retired'>Retired</option>
<option value='Sales and Marketing'>Sales and Marketing</option>
<option value='Scientist'>Scientist</option>
<option value='Self Employed'>Self Employed</option>
<option value='Sports'>Sports</option>
<option value='Transportation'>Transportation</option>

								</select>
							</td>
						</tr>
						<?php $Reference = substr($details['username'],0,10).rand(10000,99999);?>
						<tr>
					<th colspan="2" style="background-color:#CAFFFF">DETAILS OF YOUR INWARD WIRE PAYMENT</th>
					</tr>
						<tr>
							<th>REFERENCE (<small>Quote this reference number in your deposit</small>)</th>
							<td>
								<input type="text" id="ReferenceShow" name="ReferenceShow" value="<?=$Reference?>" disabled  class="form-control">
								<input type="hidden" id="Reference" name="Reference" value="<?=$Reference?>"  class="form-control">
							</td>
						</tr>
						<tr>
							<th>CURRENCY</th>
							<td>
								<input type="text" id="currencyShow" name="currencyShow" value="<?=$currency?>" disabled  class="form-control">
								<input type="hidden" id="currency" name="currency" value="<?=$currency?>"  class="form-control">
							</td>
						</tr>
						<tr>
							<th>AMOUNT</th>
							<td>
								<input type="text" id="amountFiat" name="amountFiat" value="" class="form-control">
							</td>
						</tr>
						<tr>
							<th colspan="2" style="background-color:#CAFFFF">ORIGINAL SENDING BANK</th>
						</tr>
						<tr>
							<th>Bank Name</th>
							<td><input type="text" class="form-control" name="bankName" id="bankName"></td>
						</tr>
						<tr>
							<th>Bank Address</th>
							<td><input type="text" class="form-control" name="bankAddress" id="bankAddress"></td>
						</tr>
						<tr>
							<th>SWIFT Code</th>
							<td><input type="text" class="form-control" name="swiftCode" id="swiftCode"></td>
						</tr>
						<tr>
							<td colspan="2"  style="background-color:#CAFFFF"><strong>I declare that the source of this payment is one of the following:</strong>
							<table class="table">
								<tr>
									<td><input type="checkbox" name="sourceEmploymentIncome" id="sourceEmploymentIncome" value="Yes"> Employment Income</td>
									<td><input type="checkbox" name="sourceGift" id="sourceGift" value="Yes"> Gift</td>
									<td><input type="checkbox" name="sourceGrants" id="sourceGrants" value="Yes"> Grants/Scholarships</td>
								</tr>
								<tr>
									<td><input type="checkbox" name="sourceInsurance" id="sourceInsurance" value="Yes"> Insurance Claim Payments</td>
									<td><input type="checkbox" name="sourceInvestment" id="sourceInvestment" value="Yes"> Investment Income Savings</td>
									<td><input type="checkbox" name="sourcePension" id="sourcePension" value="Yes"> Retirement/Pension Income</td>
								</tr>							
								<tr>
									<td><input type="checkbox" name="sourceSale" id="sourceSale" value="Yes"> Sale of Assets</td>
									<td><input type="checkbox" name="sourceTrust" id="sourceTrust" value="Yes"> Trust/Inheritance</td>
									<td><input type="checkbox" name="sourceLottery" id="sourceLottery" value="Yes"> Lottery Winnings</td>
								</tr>							
								<tr>
									<td colspan="3"><input type="checkbox"  name="sourceBusiness" id="sourceBusiness" value="Yes"> Business <small>(If this box is checked you will need to complete the Details of Business Payment section below)</small></td>
								</tr>							
								<tr>
									<td><input type="checkbox" name="sourceOther" id="sourceOther" value="Yes"> Other, please be specific.</td>
									<td colspan="2"><input type="text" class="form-control"  name="Other" id="Other" ></td>
								</tr>							
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<table class="table">
									<tr>
										<th colspan="2">DETAILS OF BUSINESS PAYMENT<br><small> (Only complete this section if you have checked the Business box above)</small></th>
									</tr>
									<tr>
										<td width="50%">WHAT IS THE PRINCIPAL BUSINESS ACTIVITY OF THE ORIGINATING PARTY?</td>
										<td><input type="text" class="form-control"  name="Business1" id="Business1" ></td>
									</tr>
									<tr>
										<td>WHAT IS THE NATURE OF YOUR BUSINESS RELATIONSHIP WITH THE ORIGINATING PARTY?</td>
										<td><input type="text" class="form-control" name="Business2" id="Business2" ></td>
									</tr>
									<tr>
										<td>WHAT ARE THE UNDERLYING GOODS OR SERVICES RELATED TO THIS PAYMENT?</td>
										<td><input type="text" class="form-control" name="Business3" id="Business3" ></td>
									</tr>
									<tr>
										<td>PLEASE ADVISE THE WEBSITE OF THE ORIGINATING PARTY, IF APPLICABLE</td>
										<td><input type="text" class="form-control" name="Business4" id="Business4" ></td>
									</tr>
									<tr>
										<td>WHAT ARE YOUR EXPECTED MONTHLY PAYMENTS FROM THE ORIGINATING PARTY (number and value)?</td>
										<td><input type="text" class="form-control" name="Business5" id="Business5" ></td>
									</tr>
									<tr>
									<td colspan="2"><small>If the Details of Business Payment section has been completed then we have attached full supporting documentation for this transaction (e.g. an invoice, a bill, a contract, agreement or similar document).</small></td>
									</tr>
									</table>
							</td>
						</tr>
						<tr>
							<td colspan="2">
							<ol>
								<li><input type="checkbox"  name="attached" id="attached" onclick="CheckDepositForm();"> 
								I/We have attached full supporting documentation for this transaction (e.g. an invoice, a bill, a contract, agreement or similar document).</li>
								<li><input type="checkbox"  name="agree" id="agree"  onclick="CheckDepositForm();"> I/We understand that under the requirements of Vanuatu’s Anti-Money Laundering & Counter-Terrorism Financing Act No. 13 of 2014, Regulations made thereunder and Vantu Bank’s and National Bank of Vanuatu’s respective AML/CTF Compliance Manuals as currently in force, your policy may require both banks to be satisfied as to the source of this payment before accepting any inward wire transfer and that my/our transfer(s) may be held pending or returned in the absence of such confirmation. This payment does not originate from any sanctioned or prohibited country or related sanctioned program.</li>
								<li><input type="checkbox"  name="correct" id="correct"  onclick="CheckDepositForm();"> I/We declare that the above information is true and correct.</li>
							</ol>
							<div class="alert alert-danger" id="AlertSelect" style="display:none">Check all of the above</div>
							</td>
						</tr>
						<tr>
							<td>Date: <?=gmdate('Y-M-d H:i:s',time())?></td>
							<td><input type="submit" value="Submit" class="btn btn-primary" disabled name="DepositSubmit" id="DepositSubmit"></td>
						</tr>
					</table>
						</form>
			<!-- //////////////////////////////////////////////////////////////////////////////////////-->
			<?php }else{?>
			<div style=""><blockquote><small><strong>Note:</strong> Vantu Bank’s customer service may contact you to authenticate your expected payment. Please make sure the contact details below are valid. If Vantu Bank needs to reach you and is unable to do so within 48 hours your funds may be put on hold. The purpose for the wire must be made clear in order to prevent delays.</small></blockquote></div>
			<?php if($fileupload=="NO"){?>
			<div class="alert alert-danger" role="alert">File not uploaded... Not sent to ILS/Vantu Bank</div>
			<?php }?>
			<?php if($fileupload=="YES"){?>
			<div class="alert alert-success" role="alert">File uploaded... Sent to ILS/Vantu Bank</div>
			<?php }?>
			<h2>Deposit request: (DSF)</h2>
			<div>
						<table class="table table-condensed table-bordered table-hover" >
						<tr>
							<th width="50%">YOUR FULL NAME</th>
							<td><?=$depositRequest['data']['fullName']?></td>
						</tr>
						<tr>
							<th>YOUR TELEPHONE NUMBER</th>
							<td><?=$depositRequest['data']['telephone']?></td>
						</tr>
						<tr>
							<th>REFERENCE (<small>Quote this reference number in your deposit</small>)</th>
							<td><strong><?=$depositRequest['data']['Reference']?></strong></td>
						</tr>
						<tr>
							<th>CURRENCY</th>
							<td><?=$depositRequest['data']['currency']?></td>
						</tr>
						<tr>
							<th>AMOUNT</th>
							<td><?=$depositRequest['data']['amountFiat']?></td>
						</tr>
						<tr>
							<th colspan="2" style="background-color:#CAFFFF">ORIGINAL SENDING BANK</th>
						</tr>
						<tr>
							<th>Bank Name</th>
							<td><?=$depositRequest['data']['bankName']?></td>
						</tr>
						<tr>
							<th>Bank Address</th>
							<td><?=$depositRequest['data']['bankAddress']?></td>
						</tr>
						<tr>
							<th>SWIFT Code</th>
							<td><?=$depositRequest['data']['swiftCode']?></td>
						</tr>
				<?php if($depositRequest['SenttoBank']!="Yes"){?>
						<tr>
							<th>UPLOAD SIGNED:<br>Declaration of Source of Funds (DSF)
								<p>If you want to modify the DSF, please <a href="/users/deleteDepositRequest/<?=$depositRequest['data']['Reference']?>/<?=String::hash($depositRequest['_id'])?>/<?=$depositRequest['data']['currency']?>">Delete this request</a> and create a new DSF.</p>
							</th>
							<td>
								<?=$this->form->create("", array('type' => 'file', 'action'=>'uploadDepositPDF/')); ?>
								<div id="DepositSelect" type="file">Select SiiCrypto-<?=$depositRequest['data']['Reference']?>.pdf file...</div>
								<input id="DepositInput"  class="hideMe" style="display:none" name="DepositInput" type="file"></input>
								<input type="hidden" name="currency" value="<?=$depositRequest['data']['currency']?>">
								<input type="hidden" name="SelectedSourceFile" id="SelectedSourceFile" value="">
								<div id="SelectedFile">No file selected...</div>
								<br>
								<?=$this->form->field('Reference',array('type'=>'hidden','value'=>$depositRequest['data']['Reference'])); ?>
								<?=$this->form->submit('Save',array('class'=>'btn btn-primary','id'=>'SaveButton','disabled'=>'disabled')); ?>
								<br><strong>Only PDF file:<br>SiiCrypto-<?=$depositRequest['data']['Reference']?>.pdf </strong>
								<?=$this->form->end(); ?>
							</td>
						</tr>
				<?php }else{?>
				<tr>
							<th colspan="2" style="background-color:#CAFFFF">RECEIVING BANK</th>
				</tr>
				<tr>
					<th>BANK NAME</th>
					<th>Commerzbank A.G</th>
				</tr>
				<tr>
					<th>BANK ADDRESS</th>
					<th>Kaiserplatz 60261, Frankfurt am-Main, Germany</th>
				</tr>
				<tr>
					<th>SWIFT CODE</th>
					<th>COBADEFF</th>
				</tr>
				<tr>
					<th>For the Benefit of</th>
					<th>National Bank of Vanuatu</th>
				</tr>
				<tr>
					<th>Account No</th>
					<th>400870818200</th>
				</tr>
				<tr>
					<th>SWIFT CODE</th>
					<th>NBOVVUVU</th>
				</tr>
				<tr>
					<th>For the Further Benefit of</th>
					<th>Vantu Bank</th>
				</tr>
				<tr>
					<th>Bank Address</th>
					<th>Vantu House, 133 Santina Parade, Elluk, Port Vila, Vanuatu</th>
				</tr>				
				<tr>
					<th>Account No</th>
					<th>0117982004</th>
				</tr>
				<tr>
					<th>Vantu Account Name</th>
					<th>ILS Fiduciaries (Switzerland) Sarl</th>
				</tr>
				<tr>
					<th>Vantu Account No</th>
							<td><strong><?=$currency?>-100-070378-<?php
								switch ($currency){
										case "USD":
										print_r("1");break;
										case "EUR":
										print_r("2");break;
										case "GBP":
										print_r("3");break;
										case "CAD":
										print_r("4");break;										
								}
							?></strong>
						</td>
				</tr>
				<tr>
					<th colspan="2" style="background-color:#CAFFFF">REFERENCE</th>
				</tr>
				<tr>
					<th>SiiCrypto Client Name</th>
					<th><?=$depositRequest['data']['fullName']?></th>
				</tr>
				<tr>
					<th>SiiCrypto Client Reference No (DFS)</th>
					<th><?=$depositRequest['data']['Reference']?></th>
				</tr>
				<tr>
					<th>CURRENCY</th>
					<th><?=$depositRequest['data']['currency']?></th>
				</tr>
				<tr>
					<th>AMOUNT</th>
					<th><?=$depositRequest['data']['amountFiat']?></th>
				</tr>
				<tr>
					<th>AMOUNT WORDS</th>
					<th><?=$depositRequest['data']['currency']?> <?=strtoupper($function->number_to_words($depositRequest['data']['amountFiat']))?> ONLY</th>
				</tr>
				<tr>
					<td colspan=2>
					<div style=""><blockquote><small><strong>Note:</strong> After you send the funds to Vantu Bank, wait for 3 to 7 working days for the funds to be credited to your SiiCrypto Account.</small></blockquote>
					
				<p>If you are not able to send the funds through your bank it is advisable to delete this request. please <a href="/users/deleteDepositRequest/<?=$depositRequest['data']['Reference']?>/<?=String::hash($depositRequest['_id'])?>/<?=$depositRequest['data']['currency']?>">Delete this request</a> and create a new DSF.</p>
					</div>
					</td>
				</tr>
				<?php }?>
			</table>
			</div>
			<?php }?>
			
			</div>			
			
			<div role=tabpanel class="tab-pane fade  tab-content-withdrawal" id=profile aria-labelledby=profile-tab style="padding:10px" > 
				<!-- //////////////////////////////////////////////////////////////////////////////////////-->
				<?php $Reference = substr($details['username'],0,10).rand(10000,99999);?>
				<div style=""><blockquote><small><strong>Note:</strong> Withdrawal from your account will be processed by Admin SiiCrypto and will be instructed to Vantu Bank. The bank will process the funds within 2 to 3 working day. The actual time depends on the routing of your bank.</small></blockquote></div>
						<h2>Withdrawal Request</h2>
						<form method="POST" action="/users/withdraw" class="form">
						<table class="table table-condensed table-bordered table-hover" >
						<tr>
							<th width="50%">VANTU BANK's ACCOUNT</th>
							<td>ILS FIDUCIARIES (SWITZERLAND) SARL</td>
						</tr>
						<tr>
							<th>ACCOUNT NUMBER OF YOUR ACCOUNT</th>
							<td><?=$currency?>-100-070378-<?php
								switch ($currency){
										case "USD":
										print_r("1");break;
										case "EUR":
										print_r("2");break;
										case "GBP":
										print_r("3");break;
										case "CAD":
										print_r("4");break;										
								}
							?>
							</td>
						</tr>
						<tr>
							<th>REFERENCE NO</th>
							<th><?=$Reference?>
							<input type="hidden" id="withdrawReference" name="withdrawReference" value="<?=$Reference?>"  class="form-control">
							<input type="hidden" id="withdrawCurrency" name="withdrawCurrency" value="<?=$currency?>"  class="form-control">
							</th>
						</tr>
						<tr>
							<th>ACCOUNT BALANCE</th>
							<td><?=number_format($details['balance'][$currency],2)?> <?=$currency?></td>
						</tr>
						<tr>
							<th>WITHDRAWAL AMOUNT</th>
							<td>
							<div class="input-group">
								<input type="number" class="form-control" min="10" max="<?=$details['balance'][$currency]?>" value="" step="10" id="withdrawAmount" name="withdrawAmount">
								<div class="input-group-addon"><?=$currency?></div>
							</div>
							</td>
						</tr>
						<tr>
							<th colspan="2" style="background-color:#CAFFFF">RECEIVING ACCOUNT DETAILS</th>
						</tr>
						<tr>
							<th>FULL NAME</th>
							<td><input type="text" class="form-control" value="" onblur="this.value=this.value.toUpperCase();" id="withdrawName" name="withdrawName"></td>
						</tr>
						<tr>
							<th>ACCOUNT NUMBER</th>
							<td><input type="text" class="form-control" value="" onblur="this.value=this.value.toUpperCase();" id="withdrawAccountNumber" name="withdrawAccountNumber"></td>
						</tr>						
						<tr>
							<th>BANK NAME</th>
							<td><input type="text" class="form-control" value="" onblur="this.value=this.value.toUpperCase();" id="withdrawBankName" name="withdrawBankName"></td>
						</tr>
						<tr>
							<th>BANK ADDRESS</th>
							<td><input type="text" class="form-control" value="" onblur="this.value=this.value.toUpperCase();" id="withdrawBankAddress" name="withdrawBankAddress"></td>
						</tr>
						<tr>
							<th>BANK SWIFT CODE</th>
							<td><input type="text" class="form-control" value="" onblur="this.value=this.value.toUpperCase();" id="withdrawSwiftCode" name="withdrawSwiftCode"></td>
						</tr>
						<tr>
							<td colspan="2">
							<ol>
								<li><input type="checkbox"  name="withdrawAgree" id="withdrawAgree" onclick="CheckWithdrawForm();"> 
								I/We confirm that this withdrwal will be transmitted to my own account only. I/We understand that under the requirements of Vanuatu’s Anti-Money Laundering & Counter-Terrorism Financing Act No. 13 of 2014, Regulations made thereunder and Vantu Bank’s and National Bank of Vanuatu’s respective AML/CTF Compliance Manuals as currently in force, your policy may require both banks to be satisfied as to the source of this payment before accepting any outward wire transfer and that my/our transfer(s) may be held pending or returned in the absence of such confirmation.</li>
							</ol>
							<div class="alert alert-danger" id="AlertWithdrawSelect" style="display:none">Fill all details and then check the above</div>
							</td>
							
						</tr>
						<tr>
							<td>Date: <?=gmdate('Y-M-d H:i:s',time())?></td>
							<td><input type="submit" value="Submit" class="btn btn-primary" disabled name="WithdrawSubmit" id="WithdrawSubmit"></td>
						</tr>
						</table>
						</form>
					<!-- //////////////////////////////////////////////////////////////////////////////////////-->
			</div> 
 
	</div>
	</div>