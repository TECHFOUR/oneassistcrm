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

class Import_CSVReader_Reader extends Import_FileReader_Reader {

	public function getFirstRowData($hasHeader=true) {
		global $default_charset;

		$fileHandler = $this->getFileHandler();

		$headers = array();
		$firstRowData = array();
		$currentRow = 0;
		while($data = fgetcsv($fileHandler, 0, $this->request->get('delimiter'))) {
			if($currentRow == 0 || ($currentRow == 1 && $hasHeader)) {
				if($hasHeader && $currentRow == 0) {
					foreach($data as $key => $value) {
						$headers[$key] = $this->convertCharacterEncoding($value, $this->request->get('file_encoding'), $default_charset);
					}
				} else {
					foreach($data as $key => $value) {
						$firstRowData[$key] = $this->convertCharacterEncoding($value, $this->request->get('file_encoding'), $default_charset);
					}
					break;
				}
			}
			$currentRow++;
		}

		if($hasHeader) {
			$noOfHeaders = count($headers);
			$noOfFirstRowData = count($firstRowData);
			// Adjust first row data to get in sync with the number of headers
			if($noOfHeaders > $noOfFirstRowData) {
				$firstRowData = array_merge($firstRowData, array_fill($noOfFirstRowData, $noOfHeaders-$noOfFirstRowData, ''));
			} elseif($noOfHeaders < $noOfFirstRowData) {
				$firstRowData = array_slice($firstRowData, 0, count($headers), true);
			}
			$rowData = array_combine($headers, $firstRowData);
		} else {
			$rowData = $firstRowData;
		}

		unset($fileHandler);
		return $rowData;
	}

	public function read() {
		global $default_charset;

		$fileHandler = $this->getFileHandler();
		$status = $this->createTable();
		if(!$status) {
			return false;
		}

		$fieldMapping = $this->request->get('field_mapping');

		$i=-1;
		$total_records = 0; // Added by Raghvender Singh on 17052014
		
		
		
		// Code Added for import file by Raghvender Singh on 17052014
		$row = 1;
		$filename = "log_errors/Log_Errors_".date('d_m_Y_h_i_s').".csv";
		$_SESSION['log_error_path'] = $filename;
		$handle = fopen($filename, 'w+');

        fputcsv($handle, array('Errors','Case ID','Campaign Name','RSM','ASM','SM','Location','Agent Name','Customer Name','Email ID','Date of Birth','Mobile No','Landline No',
            'Address','Landmark','City','State','Pin Code','Plan Name','Plan Price','EMI Option'));

		//End
		
		$InArray=array();
		while($data = fgetcsv($fileHandler, 0, $this->request->get('delimiter'))) {
			$total_records++; // Added by Raghvender Singh on 17052014
			$i++;
			if($this->request->get('has_header') && $i == 0) continue;
			$mappedData = array();
			$allValuesEmpty = true;
			foreach($fieldMapping as $fieldName => $index) {
				$fieldValue = $data[$index];
				$mappedData[$fieldName] = $fieldValue;
				if($this->request->get('file_encoding') != $default_charset) {
					$mappedData[$fieldName] = $this->convertCharacterEncoding($fieldValue, $this->request->get('file_encoding'), $default_charset);
				}
				if(!empty($fieldValue)) $allValuesEmpty = false;
			}
			if($allValuesEmpty) continue;
			$fieldNames = array_keys($mappedData);
			$fieldValues = array_values($mappedData);
			$this->addRecordToDB($fieldNames, $fieldValues,$i,$row,$filename,$handle);
		}
		
		//echo $this->numberOfRecordsRead."________".$this->updated_records."________".$this->skipped_records."________".$this->imported_records; die;
		
		
		$_SESSION['updated_records'] = $this->updated_records;  // Added by Raghvender Singh on 17052014
		$_SESSION['skipped_records'] = $this->skipped_records;
		$_SESSION['imported_records'] = $this->imported_records;
		$_SESSION['numberOfRecordsRead'] = $this->numberOfRecordsRead;
		fclose($handle);
		if($_REQUEST['module'] == 'Leads'){// Added by Raghvender Singh on 17052014
		$_SESSION['total_records'] = $total_records;
		//$_SESSION['total_records'] = $this->updated_records;
		}
		unset($fileHandler);
	}
}
?>
