 <?php

class ControllerApiSync extends Controller {
    public function category() {
        $this->load->language('api/sync');
        $this->load->model('sync/sync');

        $json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
            if (isset($this->request->post['id'])) {
                $category = $this->model_sync_sync->getCategoryById($this->request->post['id']);

                if ($category) {
                    // Update Category
                    $this->model_sync_sync->updateCategory($this->request->post['displayName'], $category['category_id']);
                } else {
                    // New Category
                    $this->model_sync_sync->addCategory(array('bc_id' => $this->request->post['id'], 'name' => $this->request->post['displayName']));
                }

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error']['warning'] = $this->language->get('error_api');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }

    public function dimension() {
        $this->load->language('api/sync');
        $this->load->model('sync/sync');

        $json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
            if (isset($this->request->post['id'])) {
                $manufacturer = $this->model_sync_sync->getManufacturerById($this->request->post['id']);

                if ($manufacturer) {
                    // Update Manufacturer
                    $this->model_sync_sync->updateManufacturer($this->request->post['displayName'], $manufacturer['manufacturer_id']);
                } else {
                    // New Manufacturer
                    $this->model_sync_sync->addManufacturer(array('bc_id' => $this->request->post['id'], 'name' => $this->request->post['displayName']));
                }

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error']['warning'] = $this->language->get('error_api');
            } 
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }
	
	public function item() {
        $this->load->language('api/sync');
        $this->load->model('sync/sync');

        $json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else { 
            if (isset($this->request->post['id'])) {
                $item = $this->model_sync_sync->getProductByModel($this->request->post['id']); //echo "<pre>"; print_r($product); die("==");

                if ($item) {
                    // Update Product
                    $product = array();

                    $product['manufacturer']	= $this->request->post['dimensionValueCode'];
					$product['manufacturer_id'] = $this->model_sync_sync->getManufacturerByName($dimention['dimensionValueCode']);

                    $product['upc']         = $this->request->post['number'];
                    $product['ean'] 		= $this->request->post['displayName'];
                    $product['price']		= $this->request->post['price'];
					$product['quantity'] 	= $this->request->post['inventory'];
                    $product['minimum'] 	= $this->request->post['Minimum_Quantity'] == 0 ? 1 : $this->request->post['Minimum_Quantity'];
                    $product['category']		    = $this->request->post['itemCategoryCode']; 
                    $product['product_category'] 	= array('0' => $this->model_sync_sync->getCategoryIdByName($this->request->post['itemCategoryCode']));
                    $product['product_description'] = array(
						'1'	=> array(
							'name'			=> $this->request->post['displayName'],
							'description'	=> '',
							'meta_title'	=> $this->request->post['displayName'],
							'meta_description'	=> '',
							'meta_keyword'		=> '',
							'tag'				=> ''
						)
                    );
                    //echo "<pre>"; print_r($product); die("==");
                    $this->model_sync_sync->modifyProduct($product, $this->request->post['id']);
                } else {
                    // Add Product
                    $product = array();

                    $product['manufacturer']	= $this->request->post['dimensionValueCode'];
					$product['manufacturer_id'] = $this->model_sync_sync->getManufacturerByName($dimention['dimensionValueCode']);

					$product['model']		= $this->request->post['id'];
					$product['sku'] 		= '';
					$product['upc'] 		= $this->request->post['number'];
					$product['ean'] 		= $this->request->post['displayName'];
					$product['jan'] 		= '';
					$product['isbn'] 		= '';
					$product['mpn'] 		= '';
					$product['location'] 	= '';
					$product['price']		= $this->request->post['price'];
					$product['tax_class_id'] 	= 9;
					$product['quantity'] 		= $this->request->post['inventory'];
					$product['minimum'] 		= $this->request->post['Minimum_Quantity'] == 0 ? 1 : $this->request->post['Minimum_Quantity'];
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
					$product['category']		= $this->request->post['itemCategoryCode']; 
					$product['product_category'] 	= array('0' => $this->model_sync_sync->getCategoryIdByName($this->request->post['itemCategoryCode']));
					$product['product_store']		= array('0' => 0);
					$product['product_description'] = array(
						'1'	=> array(
							'name'			=> $this->request->post['displayName'],
							'description'	=> '',
							'meta_title'	=> $this->request->post['displayName'],
							'meta_description'	=> '',
							'meta_keyword'		=> '',
							'tag'				=> ''
						)
					);
                    $product['product_store'] = array('0' => 0);
                    
                    $this->model_sync_sync->addProduct($product);
                }

                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error']['warning'] = $this->language->get('error_api');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }
}