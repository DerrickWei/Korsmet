<?php

class ControllerSyncPurchaseOrder extends Controller {
    private $endpoint_web_service_purchase = '';

    private function getEnvironment() {
        if ($this->config->get('config_environment') && 0) {
			$this->endpoint_items = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/items";
			$this->endpoint_web_service_purchase = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Pilot%20Test%20Dec%203%20backup')/Purchase_Order";
			$this->endpoint_web_service_purchase_line = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Pilot%20Test%20Dec%203%20backup')/Purchase_Lines?\$select=Document_Type,Document_No,Buy_from_Vendor_No,Line_No,Type,No,Variant_Code,Description,Location_Code,Quantity,Reserved_Qty_Base,Unit_of_Measure_Code,Direct_Unit_Cost,Indirect_Cost_Percent,Unit_Cost_LCY,Unit_Price_LCY,Line_Amount,Job_No,Job_Task_No,Job_Line_Type,Shortcut_Dimension_1_Code,Shortcut_Dimension_2_Code,Expected_Receipt_Date,Outstanding_Quantity,Outstanding_Amount_LCY,Amt_Rcd_Not_Invoiced_LCY";
        } else {
			$this->endpoint_items = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/Item_list";
            $this->endpoint_web_service_purchase = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/Purchase_Order";
            $this->endpoint_web_service_purchase_line = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/Purchase_Lines?\$select=Document_Type,Document_No,Buy_from_Vendor_No,Line_No,Type,No,Variant_Code,Description,Location_Code,Quantity,Reserved_Qty_Base,Unit_of_Measure_Code,Direct_Unit_Cost,Indirect_Cost_Percent,Unit_Cost_LCY,Unit_Price_LCY,Line_Amount,Job_No,Job_Task_No,Job_Line_Type,Shortcut_Dimension_1_Code,Shortcut_Dimension_2_Code,Expected_Receipt_Date,Outstanding_Quantity,Outstanding_Amount_LCY,Amt_Rcd_Not_Invoiced_LCY";
        }
    }

    public function index() {
        $this->load->language('sync/purchase_order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
    }

    protected function getList() {
        $this->getEnvironment();

        $this->load->model('sync/sync');

        $url = '';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sync/purchase_order', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
        }

        if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

        $results = $this->model_sync_sync->Auth($this->endpoint_web_service_purchase); 
		$data['purchase_orders'] = $results['value'];      //echo "<pre>"; print_r($data['purchase_orders']); die("==");
		
		$data['print_url'] = $this->url->link('sync/purchase_order/purchase_order_print', 'user_token=' . $this->session->data['user_token'], true);
		$data['export_url'] = $this->url->link('sync/purchase_order/purchase_order_export', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sync/purchase_order_list', $data));
	}

	public function purchase_order_export() {
		$this->getEnvironment();

		$this->load->model('sync/sync');
		$this->model_sync_sync->getToken();

		$this->load->language('sync/purchase_order');
		
		if (isset($this->request->get['purchase_order'])) {
			$purchase_order_number = $this->request->get['purchase_order'];
		} else {
			$purchase_order_number = 0;
		}

		$lines = array();

		$results = $this->model_sync_sync->Auth($this->endpoint_web_service_purchase_line);

		if ($purchase_order_number) {
			foreach ($results['value'] as $line) {  
				
				//print_r(base64_encode($output)); die("1111");

				if ($line['Document_No'] == $purchase_order_number) {
					$model = $this->model_sync_sync->getModelByName($line['Description']); //echo "<pre>"; print_r($line); die("**");
					
					$item = $this->model_sync_sync->Auth($this->endpoint_items . "?\$filter=No eq '" . $line['No'] . "'"); //echo "<pre>"; print_r($item); die("**");

					if ($model) {
						// create curl resource
						$ch = curl_init();
						// set url
						curl_setopt($ch, CURLOPT_URL, 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items(' . $model . ')/picture(' . $model . ')/content');
						// set header
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Authorization: Bearer ' . $this->session->data['accessToken']['access_token'],
						));
						// return the transfer as a string
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
						// $output contains the output string
						$output = curl_exec($ch);		
						// close curl resource to free up system resources
						curl_close($ch);
					} else {
						$output = '';
					}
					
					$total += $line['Line_Amount'];
					$lines[] = array(
						'image'					=> empty($output) ? '' : '<img alt="Embedded Image" src="data:image/jpeg;base64,' . base64_encode($output) . '" width="100px;" height="100px;"/>',
						'Item Number'				=> $line['No'],
						'Iten Name'					=> $line['Description'],	
						'Quantity'					=> $line['Quantity'],
						'Unit Price'				=> $item['value'][0]['Unit_Price']
					); 
				} 
			}

			// filename for download
			$filename = "purchase_order_" . $purchase_order_number . ".xls";

			/*header("Content-Disposition: attachment; filename=\"$filename\"");
			header('Content-Type: application/ms-excel');
			header('Cache-Control: must-revalidate');
			header('Expires: 0');
			header('Pragma: public');

			$flag = false;
			foreach($lines as $row) {
				if(!$flag) {
					// display field/column names as first row
					echo implode("\t", array_keys($row)) . "\r\n";
					$flag = true;
				  }

				echo implode("\t", array_values($row)) . "\r\n";
			}*/
			$test="<table border=1><tr><td>" . $lines[0]['image'] . "</td><td>Cell 2</td></tr></table>";
			header("Content-type: application/vnd.ms-excel");
			header("Content-Disposition: attachment; filename=$filename");
			echo $test;
		} else {
			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
	
	public function purchase_order_print() {
		$this->getEnvironment();

		$this->load->model('sync/sync');
		$this->model_sync_sync->getToken();

		$this->load->language('sync/purchase_order');
		
		if (isset($this->request->get['purchase_order'])) {
			$purchase_order_number = $this->request->get['purchase_order'];
		} else {
			$purchase_order_number = 0;
		}
		
		$results = $this->model_sync_sync->Auth($this->endpoint_web_service_purchase_line);
		//echo "<pre>"; print_r($results); die("====");

		$data['lines'] = array();
		$total = 0;

		if ($purchase_order_number) {
			foreach ($results['value'] as $line) {  //echo "<pre>"; print_r($line); die("**");
				
				//print_r(base64_encode($output)); die("1111");

				if ($line['Document_No'] == $purchase_order_number) {
					$model = $this->model_sync_sync->getModelByName($line['Description']); //echo "<pre>"; print_r($line); die("**");
					
					$item = $this->model_sync_sync->Auth($this->endpoint_items . "?\$filter=No eq '" . $line['No'] . "'"); //echo "<pre>"; print_r($item); die("**");

					if ($model) {
						// create curl resource
						$ch = curl_init();
						// set url
						curl_setopt($ch, CURLOPT_URL, 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items(' . $model . ')/picture(' . $model . ')/content');
						// set header
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Authorization: Bearer ' . $this->session->data['accessToken']['access_token'],
						));
						// return the transfer as a string
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
						// $output contains the output string
						$output = curl_exec($ch);		
						// close curl resource to free up system resources
						curl_close($ch);
					} else {
						$output = '';
					}
					
					$total += $line['Line_Amount'];
					$data['lines'][] = array(
						'image'					=> empty($output) ? '' : base64_encode($output),
						'No'					=> $line['No'],
						'Description'			=> $line['Description'],
						'Outstanding_Qty'		=> $line['Outstanding_Quantity'],	
						'Quantity'					=> $line['Quantity'],
						'Shelf_No'					=> $item['value'][0]['Shelf_No'],
						'Vendor_Item_No'			=> $item['value'][0]['Vendor_Item_No'],
						'unit_price'				=> $item['value'][0]['Unit_Price']
					);
				} 
			}//echo "<pre>"; print_r($data['lines']); die("====");

			$data['total'] = $total;

			$purchase_order = $this->model_sync_sync->Auth($this->endpoint_web_service_purchase . "?\$filter=No eq '" . $purchase_order_number . "'"); //echo "<pre>"; print_r($purchase_order); die("===");
			$data['purchase_order'] = $purchase_order['value'][0];

			$this->response->setOutput($this->load->view('sync/purchase_order_print', $data));
		} else {
			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}