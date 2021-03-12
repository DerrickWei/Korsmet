<?php

class ControllerSyncSync extends Controller {
    private $clientId       = 'e4f58a80-1532-4e4c-a203-07fb3ea20e17';
    private $clientSecret   = '?A9HMSFfR7--WJ?ufXqiCguJ2d26gRl8';
    private $redirectUri    = 'https://www.korsmet.com/index.php?route=common/home/callback';
    private $urlAuthorize   = 'https://login.windows.net/common/oauth2/authorize?resource=https://api.businesscentral.dynamics.com';
    private $urlAccessToken = 'https://login.windows.net/common/oauth2/token?resource=https://api.businesscentral.dynamics.com';

    private $error = array();
	private $endpoint_companies = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies';

    private $endpoint_customers = '';
	private $endpoint_itemCategories = '';
	private $endpoint_items = '';
	private $endpoint_item_details = '';
	private $endpoint_salesPrice = '';
	private $endpoint_itemDimensions = '';
	private $endpoint_itemstockkeeping = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/Stockkeeping_Unit_Card?\$filter=Item_No eq ";
	private $endpoint_itemCard = '';


    private function getEnvironment() {
		if ($this->config->get('config_environment') && 0) {
            // **** Development Environment **** //
			$this->endpoint_customers = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/customers';
			
			$this->endpoint_itemCategories = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/itemCategories';
			
			$this->endpoint_items = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/items?\$filter=blocked eq false";
			$this->endpoint_salesPrice = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Pilot%20Test%20Dec%203%20backup')/getSalesPrice?\$filter=Item_No eq ";
			$this->endpoint_itemDimensions = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/items";
			$this->endpoint_itemWarehouseClassCode = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Pilot%20Test%20Dec%203%20backup')/ItemCard2?\$expand=Warehouse_Class_Code_Link";
		} else {
			// **** Production Environment **** //
			$this->endpoint_customers = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/customers';
			//$this->endpoint_customers = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/Stockkeeping_Unit_Card";

			$this->endpoint_itemCategories = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/itemCategories';
			
			$this->endpoint_items = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items";
			$this->endpoint_salesPrice = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/getSalesPrice?\$filter=Item_No eq ";
			$this->endpoint_itemDimensions = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items";
			$this->endpoint_itemWarehouseClassCode = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/ItemCard2?\$filter=No eq '3350900000011'";
			$this->endpoint_itemCard = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Korsmet%20Inc.')/ItemCard2?\$filter=No eq ";
		}
	}

    public function signin() {
        // Initialize the OAuth client
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => $this->clientId,            // The client ID assigned to you by the provider
            'clientSecret'            => $this->clientSecret,        // The client password assigned to you by the provider
            'redirectUri'             => $this->redirectUri,
            'urlAuthorize'            => $this->urlAuthorize,
            'urlAccessToken'          => $this->urlAccessToken,
			'urlResourceOwnerDetails' => '',
        ]);

        $authUrl = $provider->getAuthorizationUrl();
        $this->session->data['oauthState'] = $provider->getState(); // Save client state so we can validate in callback
        
        // Redirect to AAD signin page
        header('Location: ' . $authUrl);
        exit;    
    } 
    
    public function index() {
		$this->load->language('sync/sync');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->getList();
    }
	
	public function fixSync() {
		$this->getEnvironment();

		$this->load->model('sync/sync');
		$items = $this->model_sync_sync->Auth("https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items");
		//echo "<pre>"; print_r($items['value']); die("***");
		
		foreach ($items['value'] as $item) { //echo "<pre>"; print_r($item); die("/");
			/*    Category Sync    */
			/*$this->load->model('catalog/category');
			$flag = $this->model_sync_sync->getCategoryByBcId($item['id']); //echo "<pre>"; print_r($flag); die("***");
			if ($flag) {
				continue;
			}
			$category = array();
	
			// Set category default value
			$category['parent_id']  = 0;
			$category['column']		= 1;
			$category['sort_order'] = 0;
			$category['status']		= 1;
						
			// Set category description
			$category['category_description'] = array(
				'1' => array(
					'name'    		=> $item['displayName'],
					'meta_title'	=> $item['displayName'],
					'description'	=> $item['displayName'],
					'meta_description' => '',
					'meta_keyword'     => ''
				)
			);
	
			// Add Category
			$this->model_catalog_category->addCategory($category);*/
			
			
			/*    Manufacturer Sync    */
			/*$flag = $this->model_sync_sync->getManufacturerByName($item['code']);   //echo "<pre>"; print_r($flag); die("***");
			if ($flag) {
				// Update Dimension id
				$this->model_sync_sync->updateDimensionId($flag['manufacturer_id'], $item['id']);
			} else {
				// Add Dimension
				$this->load->model('catalog/manufacturer');
				
				$manufacturer = array();
				
				$manufacturer['name'] = $item['code'];
				$product['manufacturer_store'] = array('0' => 0);
				
				$this->model_catalog_manufacturer->addManufacturer($manufacturer);
			}*/
			
			
			/*    Item Sync    */
			$flag = $this->model_sync_sync->getProductByModel($item['id']);   //echo "<pre>"; print_r($flag); die("***");
			
			if ($flag) {
				// Update Product
				/*$product = array();
				
				$dimension = $this->model_sync_sync->Auth("https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items(" . $item['id'] . ")/defaultDimensions");     //echo "<pre>"; print_r($dimension); die("***");

				$product['manufacturer']	= $dimension['value'][0]['dimensionValueCode'];
				$product['manufacturer_id'] = $this->model_sync_sync->getManufacturerByName($dimension['value'][0]['dimensionValueCode']); //echo "<pre>"; print_r($product['manufacturer_id']); die("==");

				$product['upc']         = $item['number'];
				$product['ean'] 		= $item['displayName'];
				$product['price']		= $item['unitPrice'];
				$product['category']		    = $item['itemCategoryCode']; 
				$product['product_category'] 	= array('0' => $this->model_sync_sync->getCategoryIdByName($item['itemCategoryCode']));
				$product['product_description'] = array(
					'1'	=> array(
						'name'			=> $item['displayName'],
						'description'	=> '',
						'meta_title'	=> $item['displayName'],
						'meta_description'	=> '',
						'meta_keyword'		=> '',
						'tag'				=> ''
					)
				);
                    
            	$this->model_sync_sync->modifyProduct($product, $item['id']);*/
            } else {
				// Add Product
                $product = array();

                $dimension = $this->model_sync_sync->Auth("https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items(" . $item['id'] . ")/defaultDimensions");     //echo "<pre>"; print_r($dimension); die("***");

				$product['manufacturer']	= $dimension['value'][0]['dimensionValueCode'];
				$product['manufacturer_id'] = $this->model_sync_sync->getManufacturerByName($dimension['value'][0]['dimensionValueCode']);

				$product['model']		= $item['id'];
				$product['sku'] 		= '';
				$product['upc'] 		= $item['number'];
				$product['ean'] 		= $item['displayName'];
				$product['jan'] 		= '';
				$product['isbn'] 		= '';
				$product['mpn'] 		= '';
				$product['location'] 	= '';
				$product['price']		= $item['Unitprice'];
				$product['tax_class_id'] 	= 9;
				$product['subtract'] 		= 1;	
				$product['stock_status_id'] = 5;	
				$product['shipping']		= 1;	
				$product['date_available'] 	= date('Y-m-d');	
				$product['length'] 			= '';	
				$product['width'] 			= '';
				$product['height'] 			= '';
				$product['length_class_id'] = 1;
				$product['weight'] 			= '';
				$product['weight_class_id'] = '';				
				$product['status'] 			= 1;
				$product['sort_order']		= 1;
				$product['points']			= '';
				$product['category']		    = $item['itemCategoryCode']; 
				$product['product_category'] 	= array('0' => $this->model_sync_sync->getCategoryIdByName($item['itemCategoryCode']));
				$product['product_store']		= array('0' => 0);
				$product['product_description'] = array(
					'1'	=> array(
						'name'			=> $item['displayName'],
						'description'	=> '',
						'meta_title'	=> $item['displayName'],
						'meta_description'	=> '',
						'meta_keyword'		=> '',
						'tag'				=> ''
					)
				);
                $product['product_store'] = array('0' => 0);
                    
                $this->model_sync_sync->addProduct($product);			
			}
		} die("123");
	}
	
	public function fixItemSync() {
		$this->getEnvironment();

		$this->load->model('sync/sync');
		$items = $this->model_sync_sync->Auth("https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/items?\$filter=blocked eq true");	
		//echo "<pre>"; print_r($items['value']); die("***");
		
		foreach ($items['value'] as $item) {
			$flag = $this->model_sync_sync->getProductByModel($item['id']);//echo "<pre>"; print_r($flag); die("***");
			
			$this->model_sync_sync->deleteProduct($flag['product_id']);
		} die('234');
	}
	
	public function syncDiffer() {
		$this->getEnvironment();

		$this->load->model('sync/sync');
		$items = $this->model_sync_sync->Auth($this->endpoint_items);
		
		foreach ($items['value'] as $i) {//echo "<pre>"; print_r($this->model_sync_sync->getProductByModel($i['id'])); die("***");
			if (!$this->model_sync_sync->getProductByModel($i['id'])) {
				$this->load->model('catalog/product');
				$this->load->model('catalog/manufacturer');	
				
				$itemPrice = $this->model_sync_sync->Auth($this->endpoint_salesPrice . "'" . $i['number'] . "'"); 
				$itemDimention = $this->model_sync_sync->Auth($this->endpoint_itemDimensions . "(" . $i['id'] . ")/defaultDimensions");
				$itemWarehouseClassCode = $this->model_sync_sync->Auth($this->endpoint_itemWarehouseClassCode . "'" . $i['number'] . "'"); 
				
				$product = array();

				foreach ($itemDimention['value'] as $dimention) {
					if ($dimention['dimensionCode'] == 'PRODUCT BRAND') {
						$product['manufacturer']	= $dimention['dimensionValueCode'];
						$product['manufacturer_id'] = $this->model_catalog_manufacturer->getManufacturerByName($dimention['dimensionValueCode']);
					}
				}
				
				// Set product filter
				if ($itemWarehouseClassCode['value'][0]['Warehouse_Class_Code_Link'][0]['Code'] == 'NEW') {
					$product_filter_id = 1;
				} else if ($itemWarehouseClassCode['value'][0]['Warehouse_Class_Code_Link'][0]['Code'] == 'CLEARANCE') {
					$product_filter_id = 3;
				}

				$product['product_filter'] = array('0' => $product_filter_id);
				
				// Set product default value
				$product['model']		= $i['id'];
				$product['sku'] 		= '';
				$product['upc'] 		= $i['number'];
				$product['ean'] 		= $i['displayName'];
				$product['jan'] 		= '';
				$product['isbn'] 		= '';
				$product['mpn'] 		= '';
				$product['location'] 	= '';
				$product['price']		= empty($itemPrice['value']) ? $i['unitPrice'] : $itemPrice['value'][0]['Unit_Price'];
				$product['tax_class_id'] 	= 9;
				$product['quantity'] 		= $i['inventory'];
				$product['minimum'] 		= $itemPrice['value'][0]['Minimum_Quantity'] == 0 ? 1 : $itemPrice['value']['Minimum_Quantity'];
				$product['subtract'] 		= 1;	
				$product['stock_status_id'] = 5;	
				$product['shipping']		= 1;	
				$product['date_available'] 	= date('Y-m-d');	
				$product['length'] 			= '';	
				$product['width'] 			= '';
				$product['height'] 			= '';
				$product['length_class_id'] = 1;
				$product['weight'] 			= '';
				$product['weight_class_id'] = '';				
				$product['status'] 			= 1;
				$product['sort_order']		= $key + 1;
				$product['points']			= '';
				$product['category']		= $i['itemCategoryCode']; 
				$product['product_category'] 	= array('0' => $this->model_catalog_product->getCategoryIdByName($i['itemCategoryCode']));
				$product['product_store']		= array('0' => 0);
				$product['product_description'] = array(
					'1'	=> array(
						'name'			=> $i['displayName'],
						'description'	=> '',
						'meta_title'	=> $i['displayName'],
						'meta_description'	=> '',
						'meta_keyword'		=> '',
						'tag'				=> ''
					)
				);
				$product['product_store'] = array('0' => 0);
		
				$this->model_catalog_product->addProduct($product);
			}		
		}
		die("Finish");
	}
	
	public function syncInventory() {
		$this->getEnvironment();
		
		$this->load->model('sync/sync');
		$this->load->model('catalog/product');
		
		$results = $this->model_catalog_product->getProducts();
		foreach ($results as $result) { //echo "<pre>"; print_r($result); die("==");
			$stock = $this->model_sync_sync->getProductAtt($result['product_id']);  //echo "<pre>"; print_r($stock); die("==");
			if (!empty($stock[0]['text']) && !empty($stock[1]['text'])) {
				continue;
			}
			$itemStock = $this->model_sync_sync->Auth($this->endpoint_itemstockkeeping . "'" . $result['upc'] . "'"); //echo "<pre>"; print_r($itemStock); die("====");
			$inventory = array();
			foreach ($itemStock['value'] as $item) {
				if ($item['Location_Code'] == 'TORONTO') {
					$inventory['tor_inventory'] = $item['Inventory'] - $item['Qty_on_Sales_Order'];
				} else if ($item['Location_Code'] == 'VANCOUVER') {
					$inventory['van_inventory'] = $item['Inventory'] - $item['Qty_on_Sales_Order'];
				}	
			} //echo "<pre>"; print_r($inventory); die("====");
			$this->model_catalog_product->addQuantity(array('tor_inventory' => $inventory['tor_inventory'], 'van_inventory' => $inventory['van_inventory']), $result['product_id']); 
		}
		die("Finish");
	}
	
	public function fixInventory() {
		$this->load->model('sync/sync');
		$this->load->model('catalog/product');	
		
		$results = $this->model_catalog_product->getProducts();
		
		foreach ($results as $result) {
			$stock = $this->model_sync_sync->getProductAtt($result['product_id']);  //echo "<pre>"; print_r($stock); die("==");
			
			$quantity = (int)$stock[0]['text'] + (int)$stock[1]['text']; //echo "<pre>"; print_r($quantity); die("==");
			$this->model_sync_sync->updateProductQuantity($result['product_id'], $quantity);
		}
		
		die('Done');
	}
	
	public function fixImage() {
		$this->load->model('sync/sync');	
		
		$products = $this->model_sync_sync->getProductByCategory(); 
		
		foreach ($products as $product) {//echo "<pre>"; print_r('catalog/product/APPAREL/' . $product['upc'] . '.jpg'); die("111");
			$this->model_sync_sync->updateImage('catalog/product/NOT FOR WHOLESALE/' . $product['upc'] . '.jpg', $product['product_id']);
		}
	}
	
	public function fixFilter() {
		$this->load->model('sync/sync');
		$this->load->model('catalog/product');	
		
		$results = $this->model_catalog_product->getProducts();
		
		foreach ($results as $result) {   //echo "<pre>"; print_r($result); die("--");
			$product_manufacturer = $this->model_sync_sync->getProductManufacturertoFilter($result['product_id']); //echo "<pre>"; print_r($product_manufacturer); die("--");
			
			$this->model_sync_sync->addFilterToProduct($product_manufacturer['filter_id'], $result['product_id']);
			$this->model_sync_sync->addFilterToCategory($product_manufacturer['filter_id'], $product_manufacturer['category_id']);
		}
		
		die("Done");
	}
	
    public function sync() {
		$this->getEnvironment();

        $this->load->model('sync/sync');
		$this->load->language('sync/sync');
		
        $json = array();
        
		$result = $this->model_sync_sync->Auth($this->request->get['type'] == 'customer' ? $this->endpoint_customers : ($this->request->get['type'] == 'item' ? $this->endpoint_items : $this->endpoint_itemCategories)); 
		//$result = $this->model_sync_sync->Auth($this->endpoint_test);
		//echo "<pre>"; print_r($result); die("===");

		if (!$this->user->hasPermission('modify', 'sync/sync')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!$result) {
            $json['error'] = $this->language->get('error_api');
        } else {
			switch($this->request->get['type']) {
				case 'customer':				
					$this->load->model('customer/customer');
					$this->load->model('localisation/zone');
					
					foreach ($result['value'] as $c) {
						//if ($c['number'] == 81) {       // Just for testing
							$customer = array(
								'customer_group_id'		=> 1,
								'firstname'				=> 'BC-',
								'lastname'				=> $c['displayName'],
								'email'					=> $c['email'],
								'telephone'				=> $c['phoneNumber'],
								'password'				=> '111111',
								'confirm'				=> '111111',
								'newsletter'			=> 0,
								'status'				=> 1,
								'safe'					=> 0,
								'address'				=> array(
									'1'					=> array(
										'address_id'	=> '',
										'firstname'		=> 'BC-',
										'lastname'		=> $c['displayName'],
										'company'		=> '',
										'address_1'		=> $c['address']['street'],
										'address_2'		=> '',
										'city'			=> $c['address']['city'],
										'postcode'		=> $c['address']['postalCode'],
										'country_id'	=> 38,
										'zone_id'		=> $this->model_localisation_zone->getZoneByCode($c['address']['state']),
										'default'		=> 1
									)
								),
								'company'				=> $c['displayName'],
								'website'				=> '',
								'tracking'				=> '',
								'custom_field'			=> array(
									'1'					=> $c['id'],
									'2'					=> $c['number']
								),
								'affiliate'				=> 0
							);
	
							$this->model_customer_customer->addCustomer($customer);
						//}
					}
				break;
	
				case 'item':
					$this->load->model('catalog/product');
					$this->load->model('catalog/manufacturer');
	
					foreach ($result['value'] as $key => $i) {
						if ($i['itemCategoryCode'] != 'APPAREL') { 				// Just for testing
						//if ($i['number'] == '3350900000011') {				// Just for testing
							$itemPrice = $this->model_sync_sync->Auth($this->endpoint_salesPrice . "'" . $i['number'] . "'"); 
							$itemDimention = $this->model_sync_sync->Auth($this->endpoint_itemDimensions . "(" . $i['id'] . ")/defaultDimensions");
							$itemWarehouseClassCode = $this->model_sync_sync->Auth($this->endpoint_itemWarehouseClassCode . "'" . $i['number'] . "'"); 
							//$itemStock = $this->model_sync_sync->Auth($this->endpoint_stockkeeping . "'" . $i['number'] . "'");
							//echo "<pre>"; print_r($itemStock); print_r($i); die("***");

							$product = array();

							foreach ($itemDimention['value'] as $dimention) {
								if ($dimention['dimensionCode'] == 'PRODUCT BRAND') {
									$product['manufacturer']	= $dimention['dimensionValueCode'];
									$product['manufacturer_id'] = $this->model_catalog_manufacturer->getManufacturerByName($dimention['dimensionValueCode']);
								}
							}
	
							// Set product filter
							if ($itemWarehouseClassCode['value'][0]['Warehouse_Class_Code_Link'][0]['Code'] == 'NEW') {
								$product_filter_id = 1;
							} else if ($itemWarehouseClassCode['value'][0]['Warehouse_Class_Code_Link'][0]['Code'] == 'CLEARANCE') {
								$product_filter_id = 3;
							}

							$product['product_filter'] = array('0' => $product_filter_id);

							// Set product default value
							$product['model']		= $i['id'];
							$product['sku'] 		= '';
							$product['upc'] 		= $i['number'];
							$product['ean'] 		= $i['displayName'];
							$product['jan'] 		= '';
							$product['isbn'] 		= '';
							$product['mpn'] 		= '';
							$product['location'] 	= '';
							$product['price']		= empty($itemPrice['value']) ? $i['unitPrice'] : $itemPrice['value'][0]['Unit_Price'];
							$product['tax_class_id'] 	= 9;
							$product['quantity'] 		= $i['inventory'];
							$product['minimum'] 		= $itemPrice['value'][0]['Minimum_Quantity'] == 0 ? 1 : $itemPrice['value']['Minimum_Quantity'];
							$product['subtract'] 		= 1;	
							$product['stock_status_id'] = 5;	
							$product['shipping']		= 1;	
							$product['date_available'] 	= date('Y-m-d');	
							$product['length'] 			= '';	
							$product['width'] 			= '';
							$product['height'] 			= '';
							$product['length_class_id'] = 1;
							$product['weight'] 			= '';
							$product['weight_class_id'] = '';				
							$product['status'] 			= 1;
							$product['sort_order']		= $key + 1;
							$product['points']			= '';
							$product['category']		= $i['itemCategoryCode']; 
							$product['product_category'] 	= array('0' => $this->model_catalog_product->getCategoryIdByName($i['itemCategoryCode']));
							$product['product_store']		= array('0' => 0);
							$product['product_description'] = array(
								'1'	=> array(
									'name'			=> $i['displayName'],
									'description'	=> '',
									'meta_title'	=> $i['displayName'],
									'meta_description'	=> '',
									'meta_keyword'		=> '',
									'tag'				=> ''
								)
							);
							$product['product_store'] = array('0' => 0);
		
							$this->model_catalog_product->addProduct($product);
						}
					}//echo "<pre>"; print_r($items); die("===");
	
				break;
	
				case 'category':
					$this->load->model('catalog/category');
	
					foreach ($result['value'] as $ct) { 
						$category = array();
	
						// Set category default value
						$category['parent_id']  = 0;
						$category['column']		= 1;
						$category['sort_order'] = 0;
						$category['status']		= 1;
						
						// Set category description
						$category['category_description'] = array(
							'1' => array(
								'name'    		=> $ct['displayName'],
								'meta_title'	=> $ct['displayName'],
								'description'	=> $ct['displayName'],
								'meta_description' => '',
								'meta_keyword'     => ''
							)
						);
	
						// Add Category
						$this->model_catalog_category->addCategory($category);
					}
					
				break;
	
				default:
				break;
			}

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function syncCustomer() {
		$this->getEnvironment();

        $this->load->model('sync/sync');
		$this->load->language('sync/sync');

		$this->load->model('customer/customer');
		$this->load->model('localisation/zone');
		
        $json = array();
        
		$result = $this->model_sync_sync->Auth($this->endpoint_customers); // echo "<pre>"; print_r($result); die("=="); 

		if (!$this->user->hasPermission('modify', 'sync/sync')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!$result) {
            $json['error'] = $this->language->get('error_api');
        } else {
			foreach($result['value'] as $customer) {
				if ($customer_id = $this->model_sync_sync->checkIfExistingCustomer($customer['email'])) {
					// Customer existed
					$address_custom_field = $this->model_sync_sync->getAddressCustomField($customer_id); 
					// Check if Customer Address exist
					if ($customer['id'] !== $address_custom_field[1]) {
						$address = array(
							'firstname'		=> '',
							'lastname'		=> $customer['displayName'],
							'company'		=> '',
							'address_1'		=> $customer['address']['street'],
							'address_2'		=> '',
							'city'			=> $customer['address']['city'],
							'postcode'		=> $customer['address']['postalCode'],
							'country_id'	=> 38,
							'zone_id'		=> $this->model_localisation_zone->getZoneByCode($customer['address']['state']),
							'custom_field'			=> array(
								'1'					=> $customer['id'],
								'2'					=> $customer['number']
							)
						);

						$this->model_sync_sync->addAddress($address, $customer_id);
					}
				} else {
					// Customer not existed
					// if ($customer['id'] == 'b41ccca6-5e28-eb11-bf6b-000d3a0c5819') {
					$customer = array(
						'customer_group_id'		=> 1,
						'firstname'				=> '',
						'lastname'				=> $customer['displayName'],
						'email'					=> $customer['email'],
						'telephone'				=> $customer['phoneNumber'],
						'password'				=> '111111',
						'confirm'				=> '111111',
						'newsletter'			=> 0,
						'status'				=> 1,
						'safe'					=> 0,
						'address'				=> array(
							'1'					=> array(
								'address_id'	=> '',
								'firstname'		=> '',
								'lastname'		=> $customer['displayName'],
								'company'		=> '',
								'address_1'		=> $customer['address']['street'],
								'address_2'		=> '',
								'city'			=> $customer['address']['city'],
								'postcode'		=> $customer['address']['postalCode'],
								'country_id'	=> 38,
								'zone_id'		=> $this->model_localisation_zone->getZoneByCode($customer['address']['state']),
								'default'		=> 1,
								'custom_field'			=> array(
									'1'					=> $customer['id'],
									'2'					=> $customer['number']
								),
							)
						),
						'company'				=> $customer['displayName'],
						'website'				=> '',
						'tracking'				=> '',
						'affiliate'				=> 0
					);

					$this->model_customer_customer->addCustomer($customer);
					// }
				}
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function syncFromBusinessCentral() {
		$this->getEnvironment();

        $this->load->model('sync/sync');
		$this->load->language('sync/sync');
		
        $json = array();
        
		$result = $this->model_sync_sync->Auth($this->request->get['type'] == 'brand' ? $this->endpoint_brand : ($this->request->get['type'] == 'item' ? $this->endpoint_items : $this->endpoint_itemCategories)); 
		//echo "<pre>"; print_r($result); die("===");

		if (!$this->user->hasPermission('modify', 'sync/sync')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!$result) {
            $json['error'] = $this->language->get('error_api');
        } else {
			// Create new Log file
			$logger = new Log("sync_log_" . date('Y-m-d H:i:s') . ".log");
			$logger->write('Sync process started . ');

			switch($this->request->get['type']) {
				case 'category':
					$categories = $this->model_sync_sync->getCategories();	
				break;
	
				case 'item':
					foreach ($result['value'] as $item) {
						$itemCard = $this->model_sync_sync->Auth($this->endpoint_itemCard . "'" . $item['number'] . "'"); 
	
						// Check if Purchasing Code is 'K' or 'KN'
						if (($itemCard['value'][0]['Purchasing_Code'] == 'K') || ($itemCard['value'][0]['Purchasing_Code'] == 'KN')) {
							if ($this->model_sync_sync->getItems($item['id'])) {
								// Edit Product
								$product = array(); //echo "<pre>"; print_r($item); print_r($itemCard); die("===");
	
								$itemDimention = $this->model_sync_sync->Auth($this->endpoint_itemDimensions . "(" . $item['id'] . ")/defaultDimensions"); //echo "<pre>"; print_r($itemDimention); die("===");
	
								foreach ($itemDimention['value'] as $dimention) {
									if ($dimention['dimensionCode'] == 'PRODUCT BRAND') {
										$product['manufacturer']	= $dimention['dimensionValueCode'];
										$product['manufacturer_id'] = $this->model_sync_sync->getManufacturerByName($dimention['dimensionValueCode']);

										$product['product_filter'][] = $this->model_sync_sync->getFilterIdByName($dimention['dimensionValueCode']);
									}
								}

								$stockKeepingUnit = $this->model_sync_sync->Auth($this->endpoint_itemstockkeeping . "'" . $item['number'] . "'"); 

								foreach ($stockKeepingUnit['value'] as $stock) {
									if ($stock['Location_Code'] == 'TORONTO') {
										$product['product_attribute'][] = array(
											'attribute_id'		=> 12,
											'product_attribute_description'		=> array('1' => array('attribute_id' => 12, 'text' => $stock['Inventory'] - $stock['Qty_on_Sales_Order']))
										);
									} else if ($stock['Location_Code'] == 'VANCOUVER') {
										$product['product_attribute'][] = array(
											'attribute_id'		=> 13,
											'product_attribute_description'		=> array('1' => array('attribute_id' => 13, 'text' => $stock['Inventory'] - $stock['Qty_on_Sales_Order']))
										);
									}	
								}
	
								$product['upc']         = $item['number'];
								$product['ean'] 		= $item['displayName'];
								$product['price']		= $item['unitPrice'];
								$product['quantity'] 	= $itemCard['value'][0]['Inventory'] - $itemCard['value'][0]['Qty_on_Sales_Order33272'];
								$product['minimum'] 	= $itemCard['value'][0]['Minimum_Order_Quantity'] == 0 ? 1 : $itemCard['value'][0]['Minimum_Order_Quantity'];
								$product['status'] 			    = $item['blocked'] == 1 ? 0 : 1;
								$product['category']		    = $itemCard['value'][0]['Item_Category_Code']; 
								$product['product_category'] 	= array('0' => $this->model_sync_sync->getCategoryIdByName($itemCard['value'][0]['Item_Category_Code']));
								$product['product_description'] = array(
									'1'	=> array(
										'name'			=> $item['displayName'],
										'description'	=> '',
										'meta_title'	=> $item['displayName'],
										'meta_description'	=> '',
										'meta_keyword'		=> '',
										'tag'				=> ''
									)
								);
								// echo "<pre>"; print_r($product); print_r($this->model_sync_sync->compareProduct($product, $item['id'])); die("=====");
								if ($this->model_sync_sync->compareProduct($product, $item['id'])) {
									// Item has been modified
									$logger->write("Update existing product with data:" . print_r($product, true));
									$this->model_sync_sync->editProduct($product, $item['id']);
								}
								
							} else {
								// Create Product
								$product = array();
	
								$itemDimention = $this->model_sync_sync->Auth($this->endpoint_itemDimensions . "(" . $item['id'] . ")/defaultDimensions"); //echo "<pre>"; print_r($itemDimention); die("===");
	
								foreach ($itemDimention['value'] as $dimention) {
									if ($dimention['dimensionCode'] == 'PRODUCT BRAND') {
										$product['manufacturer']	= $dimention['dimensionValueCode'];
										$product['manufacturer_id'] = $this->model_sync_sync->getManufacturerByName($dimention['dimensionValueCode']);

										$product['product_filter'][] = $this->model_sync_sync->getFilterIdByName($dimention['dimensionValueCode']);
									}
								}

								$stockKeepingUnit = $this->model_sync_sync->Auth($this->endpoint_itemstockkeeping . "'" . $item['number'] . "'");    // echo "<pre>"; print_r($stockKeepingUnit); die("===");

								foreach ($stockKeepingUnit['value'] as $stock) {
									if ($stock['Location_Code'] == 'TORONTO') {
										$product['product_attribute'][] = array(
											'attribute_id'		=> 12,
											'product_attribute_description'		=> array('1' => array('attribute_id' => 12, 'text' => $stock['Inventory'] - $stock['Qty_on_Sales_Order']))
										);
									} else if ($stock['Location_Code'] == 'VANCOUVER') {
										$product['product_attribute'][] = array(
											'attribute_id'		=> 13,
											'product_attribute_description'		=> array('1' => array('attribute_id' => 13, 'text' => $stock['Inventory'] - $stock['Qty_on_Sales_Order']))
										);
									}	
								}
	
								$product['model']		= $item['id'];
								$product['sku'] 		= '';
								$product['upc'] 		= $item['number'];
								$product['ean'] 		= $item['displayName'];
								$product['jan'] 		= '';
								$product['isbn'] 		= '';
								$product['mpn'] 		= '';
								$product['location'] 	= '';
								$product['price']		= $item['unitPrice'];
								$product['tax_class_id'] 	= 9;
								$product['quantity'] 		= $itemCard['value'][0]['Inventory'] - $itemCard['value'][0]['Qty_on_Sales_Order33272'];
								$product['minimum'] 		= $itemCard['value'][0]['Minimum_Order_Quantity'] == 0 ? 1 : $itemCard['value'][0]['Minimum_Order_Quantity'];
								$product['subtract'] 		= 1;	
								$product['stock_status_id'] = 5;	
								$product['shipping']		= 1;	
								$product['date_available'] 	= date('Y-m-d');	
								$product['length'] 			= '';	
								$product['width'] 			= '';
								$product['height'] 			= '';
								$product['length_class_id'] = 1;
								$product['weight'] 			= '';
								$product['weight_class_id'] = '';				
								$product['status'] 			= $item['blocked'] == 1 ? 0 : 1;
								$product['sort_order']		= 1;
								$product['points']			= '';
								$product['category']		    = $itemCard['value'][0]['Item_Category_Code']; 
								$product['product_category'] 	= array('0' => $this->model_sync_sync->getCategoryIdByName($itemCard['value'][0]['Item_Category_Code']));
								$product['product_description'] = array(
									'1'	=> array(
										'name'			=> $item['displayName'],
										'description'	=> '',
										'meta_title'	=> $item['displayName'],
										'meta_description'	=> '',
										'meta_keyword'		=> '',
										'tag'				=> ''
									)
								);
								$product['product_store'] = array('0' => 0);

								// Add log
								$logger->write("Creating new product with data:" . print_r($product, true));
								
								$this->model_sync_sync->addProduct($product);
							}
						}
					}
					
				break;
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    protected function getList() {
        $url = '';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sync/sync', 'user_token=' . $this->session->data['user_token'] . $url, true)
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
		
		$data['signin'] = $this->url->link('sync/sync/signin', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        $data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sync/sync', $data));
	}
	
	public function updateInventory() {
		$this->load->model('sync/sync');
		
		$products = $this->model_sync_sync->getAllProducts();
		foreach($products as $product) {
			$product_attribute = $this->model_sync_sync->getProductAttribute($product['product_id']);
			
			$inventory = 0;
			foreach ($product_attribute as $attribute) {
				$inventory += (int) $attribute['text']; 
			}
			$this->model_sync_sync->modifyQuantity($inventory, $product['product_id']);
		}
		die("done!");
	}

	public function addFilter() {
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('sync/sync');

		$categories = $this->model_catalog_category->getCategories(); 
		foreach ($categories as $category) {
			if ($category['name'] == 'APPAREL') {	// Just For Testing
				$products = $this->model_catalog_product->getProductsByCategoryId($category['category_id']); 

				foreach ($products as $product) { 
					$filter = $this->model_sync_sync->getFilterByProductManufacturer($product['manufacturer_id']); 

					// Add Product Filter
					$this->model_sync_sync->addFilterToProduct($filter['filter_id'], $product['product_id']);

					// Add Category Filter
					$exist_filter = $this->model_sync_sync->getCategoryFilter($filter['filter_id'], $category['category_id']); 
					if (!$exist_filter) {
						$this->model_sync_sync->addFilterToCategory($filter['filter_id'], $category['category_id']);
					}
				}
			}
		}
	}
}