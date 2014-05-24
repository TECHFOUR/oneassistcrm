<?php
session_start();
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Import_FileReader_Reader {
    var $status='success'; //Added for status check on 17052014
	var $numberOfRecordsRead = 0;
	var $numberOfUpdated = 0;
	var $errorMessage='';
	var $user;
	var $request;
    var $moduleModel;
	var $updated_records = 0;//Added for status check on 17052014
	var $skipped_records = 0;//Added for skipped record on 17052014
	var $imported_records = 0;//Added for status check on 17052014

	public function  __construct($request, $user) {
		$this->request = $request;
		$this->user = $user;
        $this->moduleModel = Vtiger_Module_Model::getInstance($this->request->get('module'));
	}

	public function getStatus() {
		return $this->status;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	public function getNumberOfRecordsRead() {
		return $this->numberOfRecordsRead;
	}

	public function getnumberOfUpdated() {
		return $this->numberOfUpdated; //Added for Updated number on 17052014
	}

	public function hasHeader() {
		if($this->request->get('has_header') == 'on'
				|| $this->request->get('has_header') == 1
				|| $this->request->get('has_header') == true) {
			return true;
		}
		return false;
	}

	public function getFirstRowData($hasHeader=true) {
		return null;
	}

	public function getFilePath() {
		return Import_Utils_Helper::getImportFilePath($this->user);
	}

	public function getFileHandler() {
		$filePath = $this->getFilePath();
		if(!file_exists($filePath)) {
			$this->status = 'failed';
			$this->errorMessage = "ERR_FILE_DOESNT_EXIST";
			return false;
		}

		$fileHandler = fopen($filePath, 'r');
		if(!$fileHandler) {
			$this->status = 'failed';
			$this->errorMessage = "ERR_CANT_OPEN_FILE";
			return false;
		}
		return $fileHandler;
	}

	public function convertCharacterEncoding($value, $fromCharset, $toCharset) {
		if (function_exists("mb_convert_encoding")) {
			$value = mb_convert_encoding($value, $toCharset, $fromCharset);
		} else {
			$value = iconv($fromCharset, $toCharset, $value);
		}
		return $value;
	}

	public function read() {
		// Sub-class need to implement this
	}

	public function deleteFile() {
		$filePath = $this->getFilePath();
		@unlink($filePath);
	}

	public function createTable() {

		$db = PearDatabase::getInstance();

		$tableName = Import_Utils_Helper::getDbTableName($this->user);
       	$fieldMapping = $this->request->get('field_mapping');
        $moduleFields = $this->moduleModel->getFields();
        $columnsListQuery = 'id INT PRIMARY KEY AUTO_INCREMENT, status INT DEFAULT 0, recordid INT';
       	$fieldTypes = $this->getModuleFieldDBColumnType();
		foreach($fieldMapping as $fieldName => $index) {
            $fieldObject = $moduleFields[$fieldName];
            $columnsListQuery .= $this->getDBColumnType($fieldObject, $fieldTypes);
		}
		$createTableQuery = 'CREATE TABLE '. $tableName . ' ('.$columnsListQuery.') ENGINE=MyISAM ';

		$db->query($createTableQuery);
		return true;
	}
	public function getDashInDate($string) {
			  $string = strtolower($string);
			  //Make alphanumeric (removes all other characters)
			  $string = preg_replace("/[^a-z0-9_\s-]/", "-", $string);
			  //Clean up multiple dashes or whitespaces
			  $string = preg_replace("/[\s-]+/", " ", $string);
			  //Convert whitespaces and underscore to dash
			  $string = preg_replace("/[\s_]/", "-", $string); 
			  list($mm,$dd,$yyyy) = explode("-",$string);
			  $finaldate = $yyyy.'-'.$mm.'-'.$dd;
			  return $finaldate;
		}
		

	public function addRecordToDB($columnNames, $fieldValues, $i,$row,$filename,$handle) {

		$db = PearDatabase::getInstance();
		global $current_user;
		$tableName = Import_Utils_Helper::getDbTableName($this->user);

		$lead_create_update_status = 1;
		// 0  skip, 1 create, 2 update
		if($_REQUEST['module'] == 'Leads'){
			$campaignid = $_SESSION['recordid'];
			
			$logerror = "";
			$fileter_mobile = preg_replace( '/[^0-9]/', '', $fieldValues[10]);

            $mobile_filter_digit = substr($fileter_mobile, -10, 10);


            $camp_name_qry = $db->query("select campaignid from vtiger_campaign inner join vtiger_crmentity on vtiger_crmentity.crmid =  vtiger_campaign.campaignid where deleted = 0 and campaignid = ".$campaignid."");

            if($db->num_rows($camp_name_qry) > 0){

                $row = $db->fetch_array($camp_name_qry);
                $fieldValues[1] = $row['campaignid'];
            }

            //echo "<pre>"; print_r($fieldValues[1]); die();

            $querymobile= $db->query("select mobile from vtiger_leadaddress inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leadaddress.leadaddressid where deleted = 0 and mobile ='".$mobile_filter_digit."'"); // for check mobile no unique
            if($db->num_rows($querymobile) > 0)
            {
                $mobile_check = 1;

            }else{
                $mobile_check = 0;
            }

            $queryemail= $db->query("select email from vtiger_leaddetails inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_leaddetails.leadid  where deleted = 0 and email =".$fieldValues[8]); // for check mobile no unique
            if($db->num_rows($queryemail) > 0)
            {
                $email_check = 1;

            }else{
                $email_check = 0;
            }
		if($fieldValues[0] == '' || strlen($mobile_filter_digit) != 10 || $mobile_check == 1 || $email_check == 1){ // Start code when skip lead

				$this->skipped_records++;
				
				if($mobile_filter_digit == '')
					$logerror .= "Mobile is Empty";
                if($mobile_check == 1)
                $logerror .= "Duplicate Mobile Number";

				if(strlen($mobile_filter_digit) != 10)
					$logerror .= "Mobile number is not Valid";
				if($fieldValues[8] == "")
					$logerror .= "Email ID is Empty";
                if($fieldValues[8] == 1)
                $logerror .= "Duplicate Email ID";

			fputcsv($handle, array($logerror,$fieldValues[0],$fieldValues[1],$fieldValues[2],$fieldValues[3],$fieldValues[4],$fieldValues[5],$fieldValues[6],
					$fieldValues[7],$fieldValues[8],$fieldValues[9],$mobile_filter_digit,$fieldValues[11],$fieldValues[12],$fieldValues[13],$fieldValues[14],$fieldValues[15],
					$fieldValues[16],$fieldValues[17],$fieldValues[18],$fieldValues[19]));
			}
		else{ // When Insert or Update
            global $adb, $current_user;
            $crmid = $adb->getUniqueID("vtiger_crmentity");


            $createrid = $current_user->id;
            $currentdatetime = date("Y-m-d H:i:s");
            $querynum = $adb->query("select prefix, cur_id from vtiger_modentity_num where semodule='Leads' and active = 1");
            $resultnum = $adb->fetch_array($querynum);
            $prefix = $resultnum['prefix'];
            $cur_id = $resultnum['cur_id'];
            $LeadNum = $prefix.$cur_id;
            $next_curr_id = $cur_id + 1;
            $adb->query("update vtiger_modentity_num set cur_id = ".$next_curr_id." where semodule='Leads' and active = 1");
            $callerName = $LeadNum;

            $mobile_filter_digit = $mobile_filter_digit;
			$mobile = trim($mobile_filter_digit);

			if($fieldValues[9] != '') // Date of Birth
				$fieldValues[9] = Import_FileReader_Reader::getDashInDate($fieldValues[9]);
            /*Start Query for dind the Make and Model*/

		fputcsv($handle, array($logerror,$fieldValues[0],$fieldValues[1],$fieldValues[2],$fieldValues[3],$fieldValues[4],$fieldValues[5],$fieldValues[6],
					$fieldValues[7],$fieldValues[8],$fieldValues[9],$mobile_filter_digit,$fieldValues[11],$fieldValues[12],$fieldValues[13],$fieldValues[14],$fieldValues[15],
					$fieldValues[16],$fieldValues[17],$fieldValues[18],$fieldValues[19]));




            $query = "INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,modifiedby,setype,description,createdtime,
		modifiedtime,viewedtime,status,version,presence,deleted,label) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $adb->pquery($query, array($crmid, $createrid, $createrid, $createrid, "Leads", "", $currentdatetime, $currentdatetime, NULL, NULL, 0, 1, 0, $LeadNum));

             $adb->query("insert into vtiger_leaddetails (leadid,case_id,email,firstname,plan_name,plan_price,emioption)
 values(".$crmid.",'".$fieldValues[0]."','".$fieldValues[8]."', '".$fieldValues[7]."', '".$fieldValues[17]."', '".$fieldValues[18]."', '".$fieldValues[19]."')");


            $adb->query("insert into vtiger_leadscf (leadid,campaign_name,lead_rsm,lead_sm,date_of_birth,lead_asm,agent_name,landmark,location,check_status)
		values(".$crmid.",'".$fieldValues[1]."','".$fieldValues[2]."','".$fieldValues[4]."','".$fieldValues[9]."','".$fieldValues[3]."', '".$fieldValues[6]."','".$fieldValues[13]."','".$fieldValues[5]."',1)");


            $adb->query("insert into vtiger_leadaddress (leadaddressid,mobile,phone,code,city,state,address)
			values(".$crmid.",'".$mobile_filter_digit."','".$fieldValues[11]."','".$fieldValues[16]."','".$fieldValues[14]."' ,'".$fieldValues[15]."','".$fieldValues[12]."')");

            if($this->mode != "edit") {
                global $current_user;
                $leadactivitystartdate = date('Y-m-d H:i:s',time());
                $this->createNewActivityImport($createrid,$crmid, $fieldValues, $leadactivitystartdate);
            }

// Start Save in Modtracker table ******************
            $thisid = $adb->getUniqueId('vtiger_modtracker_basic');


            $adb->pquery('INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status)
							VALUES(?,?,?,?,?,?)', Array($thisid, $crmid, 'Leads',$current_user->id, date('Y-m-d H:i:s',time()), 2));

            foreach($all_Values as $key=>$row) {
                if($row != "")	{
                    $adb->pquery('INSERT INTO vtiger_modtracker_detail(id,fieldname,postvalue) VALUES(?,?,?)',
                        Array($thisid, $key, $row));
                }
            }
           // echo "<pre>"; print_r($thisid); die("ram");
// End Save in Modtracker table ******************



			
		}

		}
	else{
		$db->pquery('INSERT INTO '.$tableName.' ('. implode(',', $columnNames).') VALUES ('. generateQuestionMarks($fieldValues) .')', $fieldValues);
		}

		$this->numberOfRecordsRead++;
	}
// End by Raghvender Singh on 19052014 for import list data

    function createNewActivityImport($createrid, $crmid, $fieldValues, $leadactivitystartdate) {

        global $adb, $current_user;
        //echo "<pre>"; print_r($crmid); die("ram");
        $date_format = $current_user->date_format;
        $callerName = "Lead Activity";
        $assigned_user_id = $current_user->id;
        $lead_id = $crmid;


        $currentdatetime = date("Y-m-d H:i:s");
        list($date,$time) = explode(" ",$currentdatetime);


        $time_start = $time;
        $time_end = $time;
        $date_start = $date;
        if($leadactivitystartdate != "") {
            $date_start = DateTimeField::__convertToDBFormat($leadactivitystartdate, $date_format);
            $currentuser = 1;
        }

        // Start Held all old Activity
        $activity_qry = $adb->query("SELECT vtiger_activitycf.*, smownerid FROM vtiger_leaddetails
										INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
										LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.crmid = vtiger_leaddetails.leadid
										INNER JOIN vtiger_activitycf ON vtiger_activitycf.activityid = vtiger_seactivityrel.activityid
										WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.leadid = $lead_id order by vtiger_activitycf.activityid DESC" );
        if($adb->num_rows($activity_qry) > 0) {
            $counter = 0;
            while($row = $adb->fetch_array($activity_qry)) {
                $old_assigned_to = $row['smownerid'];
                $activityid = $row['activityid'];
                $adb->query("UPDATE vtiger_activity SET eventstatus = 'Held' WHERE vtiger_activity.activityid = $activityid ");
                if($counter == 0) {
                    $app_book_date = $row['app_book_date'];
                    $app_book_time = $row['app_book_time'];
                    $driver_pickup = $row['driver_pickup'];
                    $sub_disposition = $row['cf_903'];
                    $status = $row['cf_895'];
                    $adb->query("UPDATE vtiger_activitycf SET re_assigned_to = 1 WHERE vtiger_activitycf.activityid = $activityid ");
                }
                $counter++;
            }
        }

        // End Held old Activity

        $crmid = $adb->getUniqueID("vtiger_crmentity");
        $query = "INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,modifiedby,setype,description,createdtime,
					modifiedtime,viewedtime,status,version,presence,deleted,label) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $adb->pquery($query, array($crmid, $currentuser, $assigned_user_id, $currentuser, "Calendar", "", $currentdatetime, $currentdatetime, NULL, NULL, 0, 1, 0, $callerName));

        $query = "INSERT into vtiger_activity (activityid, subject, activitytype, date_start, due_date, time_start, time_end, eventstatus, visibility) VALUES (?,?,?,?,?,?,?,?,?)";
        $adb->pquery($query, array($crmid, $callerName, 'Call', $date_start, $date_start, $time_start, $time_end, 'Planned', 'all'));
        $adb->query("INSERT into vtiger_activitycf (activityid, app_book_date, app_book_time, driver_pickup, cf_903, cf_895)
					values(".$crmid.",'".$app_book_date."','".$app_book_time."','".$driver_pickup."','".$sub_disposition."','".$status."')");

        $adb->query("INSERT into vtiger_activity_reminder_popup (semodule,recordid,date_start,time_start,status) values('Calendar','".$crmid."','".$startdate."','".$start_time."',0)");

        $adb->query("INSERT into vtiger_seactivityrel (crmid,activityid) values(".$lead_id.",".$crmid.")");
// Start Update in Lead Screen
        if($status == "" || $status == "Call Back" || $status == "Mobile not Reachable OR Switched Off")
            $status = "Open";
        elseif($status == "Appointment Booked")
            $status = "Appointment Booked";
        else
            $status = "Lost";
        $adb->query("UPDATE vtiger_leaddetails INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
						INNER JOIN vtiger_leadscf ON vtiger_leadscf.leadid = vtiger_leaddetails.leadid
						SET app_book_date = '".$app_book_date."', app_book_time = '".$app_book_time."', leadstatus = '".$status."', modifiedby = ".$current_user->id.", modifiedtime = '".date('Y-m-d H:i:s')."' , latest_activity_date = '".$date_start."'
						WHERE vtiger_leaddetails.leadid = ".$lead_id." ");
// End Update in Lead Screen
        // Start Save in Modtracker table ******************
        $thisid = $adb->getUniqueId('vtiger_modtracker_basic');
        $adb->pquery('INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status)
							VALUES(?,?,?,?,?,?)', Array($thisid, $crmid, 'Events',$currentuser, date('Y-m-d H:i:s',time()), 2));

        $all_Values = array('subject'=>$callerName,
            'assigned_user_id'=> $assigned_user_id,
            'date_start'=>$date_start,
            'time_start'=>$time_start,
            'time_end'=>$time_end,
            'due_date'=>$date_start,
            'parent_id'=>$lead_id,
            'activitytype'=>'Call',
            'eventstatus'=>'Planned'
        );

        foreach($all_Values as $key=>$row) {
            if($row != "")	{
                if($key == "date_start" || $key == "due_date")
                    $row = $date_start;
                $adb->pquery('INSERT INTO vtiger_modtracker_detail(id,fieldname,postvalue) VALUES(?,?,?)',
                    Array($thisid, $key, $row));
            }
        }

        $thisid_rel = $adb->getUniqueId('vtiger_modtracker_basic');
        $adb->pquery('INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status)

					VALUES(?,?,?,?,?,?)', Array($thisid_rel, $lead_id, 'Leads',$currentuser, date('Y-m-d H:i:s',time()), 4));

        $adb->pquery('INSERT INTO vtiger_modtracker_relations(id,targetmodule, targetid, changedon) VALUES(?,?,?,?)',
            Array($thisid_rel, 'Calendar', $crmid, date('Y-m-d H:i:s',time())));
        // End Save in Modtracker table ******************



    }

	function addHistoryLead($old_camp_type_id, $old_lead_id, $old_assigned_to, $old_camp_type_priority, $old_campid , $old_outlet, $old_campaignlocation, $assigned_to, $outletmaster,$camp_type_id , $outletid, $campaign_id, $campaignlocation,$all_Request, $registrationno, $campaign_type,$depth, $camp_type_priority,$empty_reg_no,$profileid, $campcategory, $old_campcategory) {
		
					global $adb,$log, $current_user;																									
									
// Start Save in Modtracker table ******************
//echo $old_assigned_to.'___'.$assigned_to.'___'.$outletmaster.'___'.$empty_reg_no;die;
//echo "<pre>"; print_r($_REQUEST); die; 					
							//echo $old_lead_id;die;		
			$this->nonBlankLeadData($all_Request, $old_lead_id);									
								
// End Save in Modtracker table ******************
		//echo $old_lead_id.'___'.$old_camp_type_id.'___'.$camp_type_id.'___'.$old_outlet.'___'.$outletid.'___'.$old_campid.'___'.$campaign_id.'___'.$old_campaignlocation.'___'. $campaignlocation.'___'.$campcategory.'___'.$old_campcategory;die;
	if($registrationno != "" ) {
		$this->setCampaignId($old_lead_id, $old_camp_type_id, $camp_type_id, $old_outlet, $outletid, $old_campid, $campaign_id, $old_campaignlocation, $campaignlocation, $campcategory, $old_campcategory);
		$thisid = $adb->getUniqueId('vtiger_modtracker_basic');					
		$adb->pquery('INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status)
				VALUES(?,?,?,?,?,?)', Array($thisid, $old_lead_id, 'Leads',$current_user->id, date('Y-m-d H:i:s',time()), 0));																															
		
		$sql = 'INSERT INTO vtiger_modtracker_detail(id,fieldname, prevalue, postvalue) VALUES(?,?,?,?)';
							
		$adb->pquery($sql,Array($thisid, 'assigned_user_id', $old_assigned_to, $assigned_to));										
		$adb->pquery($sql,Array($thisid, 'campaigntype', $old_camp_type_id, $camp_type_id));										
		$adb->pquery($sql,Array($thisid, 'outlet', $old_outlet, $outletid));					
		$adb->pquery($sql,Array($thisid, 'campaignid', $old_campid, $campaign_id));
		$adb->pquery($sql,Array($thisid, 'campaignid', $old_campid, $campaign_id));														
		$adb->pquery($sql,Array($thisid, 'campaign_category', $old_campcategory, $campcategory));
				
	$adb->query("UPDATE vtiger_leaddetails 
						INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid
						INNER JOIN vtiger_leadscf ON vtiger_leadscf.leadid = vtiger_leaddetails.leadid
						INNER JOIN vtiger_leadsubdetails ON vtiger_leadsubdetails.leadsubscriptionid = vtiger_leaddetails.leadid 
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
						SET  smownerid = ".$assigned_to.", modifiedby = ".$current_user->id.", 
						modifiedtime = '".date("Y-m-d H:i:s",time())."', outlet = ".$outletid.", campaignid = ".$campaign_id.", 
						campaigntype = ".$camp_type_id.",	campaignlocation = '".$campaignlocation."', plant_code = '".$outletmaster."',
						campaign_category = '".$campcategory."'
						where vtiger_leaddetails.leadid = ".$old_lead_id." ");
	  	$history_qry = $adb->query("SELECT lead_no, campaignid, vtiger_modtracker_basic.id as basicid, vtiger_modtracker_basic.module as basicmodule, vtiger_modtracker_basic.crmid as entityid, whodid, createdtime, changedon, prevalue, postvalue, outletmaster FROM vtiger_modtracker_basic 
		INNER JOIN vtiger_modtracker_detail ON vtiger_modtracker_detail.id = vtiger_modtracker_basic.id
INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_modtracker_basic.crmid
INNER JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid = vtiger_crmentity.crmid
INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid
INNER JOIN vtiger_users on vtiger_users.id = vtiger_modtracker_detail.postvalue
INNER JOIN vtiger_outletmaster on vtiger_outletmaster.outletmasterid = vtiger_users.cf_775
WHERE vtiger_crmentity.deleted = 0 AND vtiger_modtracker_detail.fieldname = 'assigned_user_id' AND history_status = '0' AND vtiger_modtracker_basic.module = 'Leads' AND vtiger_leaddetails.leadid = ".$old_lead_id." ORDER BY basicid");		

  		if($adb->num_rows($history_qry) > 0) {
			while($row = $adb->fetch_array($history_qry)) {			
				$basicmodule = $row['basicmodule'];
				$entityid = $row['entityid'];
				$whodid = $row['whodid'];
				$createdtime = $row['createdtime'];
				$changedon = $row['changedon'];
				$prevalue = $row['prevalue'];
				$postvalue = $row['postvalue'];
				$outletmaster = $row['outletmaster'];					
				$basicid = $row['basicid'];
				$entity_no = $row['lead_no'];
				$campaignid = $row['campaignid'];
		
				$crmid = $adb->getUniqueID("vtiger_crmentity");					
				$createrid = $userid;
				$querynum = $adb->query("select prefix, cur_id from vtiger_modentity_num where semodule='Faq' and active = 1");
				$resultnum = $adb->fetch_array($querynum);
				$prefix = $resultnum['prefix'];
				$cur_id = $resultnum['cur_id'];
				$HISNo = $prefix.$cur_id; 
				$next_curr_id = $cur_id + 1;
				$adb->query("update vtiger_modentity_num set cur_id = ".$next_curr_id." where semodule='Faq' and active = 1");					  				
				$query = "INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,modifiedby,setype,description,createdtime,
				modifiedtime,viewedtime,status,version,presence,deleted,label) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				$adb->pquery($query, array($crmid, $whodid, $whodid, 0, "Faq", "", $createdtime, $changedon, NULL, NULL, 0, 1, 0, $HISNo));
								
				$adb->query("INSERT INTO vtiger_faq (id, faq_no, changed_by, entityid, outlet, post_assigned_to, pre_assigned_to, module, entity_no, campid) VALUES(".$crmid.",'".$HISNo."', ".$whodid.", ".$entityid.", '".$outletmaster."', ".$postvalue.", '".$prevalue."', '".$basicmodule."', '".$entity_no."', '".$campaignid."')"); 
				
				$adb->query("UPDATE vtiger_modtracker_basic SET history_status = '1' WHERE id = ".$basicid."");
				
				$sql = "insert into vtiger_crmentityrel values (?,?,?,?)";
				$adb->pquery($sql, array($entityid,'Leads',$crmid,'Faq'));
				$adb->pquery($sql, array($old_campid,'Campaigns',$crmid,'Faq'));								
				$adb->query("UPDATE vtiger_campaignscf SET reallocated_lead = '1' where campaignid = ".$old_campid." ");
				
				}		
			}
		}
	
	}
			
	function setCampaignId($old_lead_id, $old_camp_type_id, $camp_type_id, $old_outlet, $outletid, $old_campid, $campaign_id, $old_campaignlocation, $campaignlocation, $campcategory, $old_campcategory) {
		global $adb, $current_user;
		$thisid = $adb->getUniqueId('vtiger_modtracker_basic');					
		$adb->pquery('INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status)
				VALUES(?,?,?,?,?,?)', Array($thisid, $old_lead_id, 'Leads',$current_user->id, date('Y-m-d H:i:s',time()), 0));																															
		
		$sql = 'INSERT INTO vtiger_modtracker_detail(id,fieldname, prevalue, postvalue) VALUES(?,?,?,?)';
																			
		$adb->pquery($sql,Array($thisid, 'campaigntype', $old_camp_type_id, $camp_type_id));										
		$adb->pquery($sql,Array($thisid, 'outlet', $old_outlet, $outletid));					
		$adb->pquery($sql,Array($thisid, 'campaignid', $old_campid, $campaign_id));														
		$adb->pquery($sql,Array($thisid, 'campaignlocation', $old_campaignlocation, $campaignlocation));
		$adb->pquery($sql,Array($thisid, 'campaign_category', $old_campcategory, $campcategory));
		
		$adb->query("UPDATE vtiger_leaddetails 
			INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_leadsubdetails ON vtiger_leadsubdetails.leadsubscriptionid = vtiger_leaddetails.leadid 
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_leadscf ON vtiger_leadscf.leadid = vtiger_leaddetails.leadid
			SET  modifiedby = ".$current_user->id.", 
			modifiedtime = '".date("Y-m-d H:i:s",time())."', outlet = ".$outletid.", campaignid = ".$campaign_id.", 
			campaigntype = ".$camp_type_id.", campaignlocation = '".$campaignlocation."', campaign_category = '".$campcategory."'
			where vtiger_leaddetails.leadid = ".$old_lead_id." ");
	}
	
	function nonBlankLeadData($all_Request, $old_lead_id) {
		global $adb, $current_user;
		$date_format = $current_user->date_format;
		foreach($all_Request as $key=>$row) {																				
			if($row != "" && ($key != "module" || $key != "action" || $key != "mobile" || $key != "assigned_to" || $key != "registrationno" || $key != "outlet"))	{
				
				if($key == "dateofbirth" || $key == "dateofsale"|| $key == "lastservicedate"|| $key == "insurancedate"|| $key == "leadactivitydate") {
					if($date_format != "dd-mm-yyyy")
						$row = str_replace("-","/",$row);				
				$row = date("Y-m-d",strtotime($row));															
				}
				
				$adb->query("UPDATE vtiger_leaddetails 
			INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_leadsubdetails ON vtiger_leadsubdetails.leadsubscriptionid = vtiger_leaddetails.leadid 
			INNER JOIN vtiger_leadscf ON vtiger_leadscf.leadid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
			SET  modifiedby = ".$current_user->id.", modifiedtime = '".date("Y-m-d H:i:s",time())."', ".$key." = '".$row."'
			where vtiger_leaddetails.leadid = ".$old_lead_id." ");
			}
		}
	}
	
	/** Function returns the database column type of the field
	 * @param $fieldObject <Vtiger_Field_Model>
	 * @param $fieldTypes <Array> - fieldnames with column type
	 * @return <String> - column name with type for sql creation of table
	 */	
    public function getDBColumnType($fieldObject,$fieldTypes){
        $columnsListQuery = '';
        $fieldName = $fieldObject->getName();
        $dataType = $fieldObject->getFieldDataType();
        if($dataType == 'reference' || $dataType == 'owner' || $dataType == 'currencyList'){
            $columnsListQuery .= ','.$fieldName.' varchar(250)';
        } else {
            $columnsListQuery .= ','.$fieldName.' '.$fieldTypes[$fieldObject->get('column')];
        }
        
        return $columnsListQuery;
    }
    
	/** Function returns array of columnnames and their column datatype
	 * @return <Array>
	 */
    public function getModuleFieldDBColumnType() {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT tablename FROM vtiger_field WHERE tabid=? GROUP BY tablename', array($this->moduleModel->getId()));
        $tables = array();
        if ($result && $db->num_rows($result) > 0) {
            while ($row = $db->fetch_array($result)) {
                $tables[] = $row['tablename'];
            }
        }
        $fieldTypes = array();
        foreach ($tables as $table) {
            $result = $db->pquery("DESC $table", array());
            if ($result && $db->num_rows($result) > 0) {
                while ($row = $db->fetch_array($result)) {
                    $fieldTypes[$row['field']] = $row['type'];
                }
            }
        }
        return $fieldTypes;
    }
}
?>