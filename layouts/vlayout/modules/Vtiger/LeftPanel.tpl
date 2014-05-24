<link type="text/css" href="popupdragable/jquery_002.css" rel="stylesheet">
		<script type="text/javascript" src="popupdragable/index.js"></script>
		<script type="text/javascript" src="popupdragable/jquery_004.js"></script>

	<div id="example8" class="example_block">
	   <div class="demo">
	 	   <div id="window_block8" style="display:none;">

               {if ($MODULE eq 'Leads' || $MODULE eq 'Events' || $MODULE eq 'Calendar') && $customer_name neq ''}
               		<div style="padding:10px;">
                            <div  style="font-size:12px;">
                                <div style="color:#FECC00" title="{$client_id}"><b><u><a style="color:#FECC00" href="index.php?module=Contacts&view=Detail&record={$contact_id}&mode=showDetailViewByMode&requestMode=full" target="_blank">Customer Information</a></u></b>
                                </div>
	                            <table>
								   <tr><td style="color:#999999">&nbsp;</td><td>&nbsp;</td></tr>
                                   <tr><td style="color:#999999">Customer</td><td>{if $customer_name eq ''}- -{else}: {$customer_name}{/if}</td></tr>
	                               <tr><td style="color:#999999">Email</td><td>{if $email_id eq ''}- -{else}: {$email_id}{/if}</td></tr>
	                               <tr><td style="color:#999999">Mobile</td><td>{if $mobile_no eq ''}- -{else}: {$mobile_no}{/if}</td></tr>
	                               <tr><td style="color:#999999">City</td><td>{if $city eq ''}- -{else}: {$city}{/if}</td></tr>
                                   <tr><td style="color:#999999">Plan</td><td>{if $plan_name eq ''}- -{else}: {$plan_name}{/if}</td></tr>
                                   <tr><td style="color:#999999">Plan</td><td>{if $plan_price eq ''}- -{else}: {$plan_price}{/if}</td></tr>
                                   <tr><td style="color:#999999">EMI</td><td>{if $emioption eq ''}- -{else}: {$emioption}{/if}</td></tr>
                                   
	                            </table>
	                        </div>
	          		 </div>
               {/if}

					{*
					<div style="padding:10px;">
						<div  style="font-size:12px;">
							<div style="color:#FECC00"><b><u>Account Manager Info</u></b>
                    		</div>
				      		<table>
                                  <tr><td style="color:#999999">&nbsp;</td><td>&nbsp;</td></tr>
                                  <tr><td style="color:#999999;font-size: 12px;">Branch</td><td>{if $Branch eq ''}- -{else}: {$Branch}{/if}</td></tr>
                                  <tr><td style="color:#999999">Team</td><td>{if $TEAM eq ''}- -{else}: {$TEAM}{/if}</td></tr>
                                  <tr><td style="color:#999999">BSM</td><td>{if $BSM eq ''}- -{else}: {$BSM}{/if}</td></tr>
                                  <tr><td style="color:#999999">KAM</td><td>{if $BTL eq ''}- -{else}: {$BTL}{/if}</td></tr>
                                  <tr><td style="color:#999999">AM Mobile</td><td>{if $Account_Manager_Contact eq ''}- -{else}: {$Account_Manager_Contact}{/if}</td></tr>
                                  <tr><td style="color:#999999">AM Email</td><td>{if $Account_Manager_Email eq ''}- -{else}: {$Account_Manager_Email}{/if}</td></tr>
                            </table>
		       			</div>
					</div>
                   *}
			</div>
		</div>
	</div>