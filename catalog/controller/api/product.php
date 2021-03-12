<?php
class ControllerApiProduct extends Controller {
    public function updateProduct() {
        $this->load->language('api/product');

		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
            if (isset($this->request->post['id'])) {
                $products = $this->model_catalog_product->getProducts(array('filter_name' => $this->request->post['id'])); 
                foreach ($products as $product) {
                    $product_info = $product;
                }//echo "<pre>"; print_r($product_info); die("======");

                if (!$product_info) {
                    $json['error']['warning'] = $this->language->get('error_product');
                }

                if (!$json) {
                    $product_data = array();
	
                    // Set product default value
                    $product_data['model']      = $product_info['model'];
					$product_data['sku'] 		= '';
					$product_data['upc'] 		= $this->request->post['number'];
					$product_data['ean'] 		= $this->request->post['displayName'];
					$product_data['jan'] 		= '';
					$product_data['isbn'] 		= '';
					$product_data['mpn'] 		= '';
					$product_data['location'] 	= '';
					$product_data['price']		= $this->request->post['unitPrice'];
					$product_data['tax_class_id'] 	= 9;
					$product_data['quantity'] 		= $this->request->post['inventory'];
					$product_data['minimum'] 		= $this->request->post['minimunQuantity'] == 0 ? 1 : $this->request->post['minimunQuantity'];
					$product_data['subtract'] 		= 1;	
					$product_data['stock_status_id'] = 5;	
					$product_data['shipping']		 = 1;	
					$product_data['date_available'] 	= date('Y-m-d');	
					$product_data['length'] 			= '';	
					$product_data['width'] 			    = '';
					$product_data['height'] 			= '';
					$product_data['length_class_id']    = 1;
					$product_data['weight'] 			= '';
					$product_data['weight_class_id']    = '';				
                    $product_data['status'] 			= 1;
                    $product_data['sort_order']         = $product_info['sort_order'];
					$product_data['manufacturer']	    = $this->request->post['dimensionValueCode'];
                    $product_data['manufacturer_id']    = $this->model_catalog_manufacturer->getManufacturerByName($this->request->post['dimensionValueCode']);
                    $product_data['points']             = $product_info['points'];
					$product_data['category']		    = $this->request->post['itemCategoryCode']; 
					$product_data['product_category'] 	= array('0' => $this->model_catalog_product->getCategoryIdByName($this->request->post['itemCategoryCode']));
					$product_data['product_store']		= array('0' => 0);
					$product_data['product_description'] = array(
						'1'	=> array(
							'name'			=> $this->request->post['displayName'],
							'description'	=> '',
							'meta_title'	=> $this->request->post['displayName'],
							'meta_description'	=> '',
							'meta_keyword'		=> '',
							'tag'				=> ''
						)
                    );
                    
                    $this->model_catalog_product->editProduct($product_info['product_id'], $product_data);

                    $json['success'] = $this->language->get('text_success');
                }
            } else {
                $json['error']['warning'] = $this->language->get('error_product');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }
	
	public function updateInventory() {
		$this->load->language('api/product');

		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
            if (isset($this->request->post['id'])) {
                $products = $this->model_catalog_product->getProducts(array('filter_name' => $this->request->post['id'])); //echo "<pre>"; print_r($products); die("===");
                foreach ($products as $product) {
                    $product_info = $product;
				} //echo "<pre>"; print_r($product_info); die("==");

				if (isset($this->request->post['tor_inventory']) && isset($this->request->post['van_inventory']) && isset($this->request->post['inventory'])) {
					$this->model_catalog_product->updateInventory(
						array('tor_inventory' => $this->request->post['tor_inventory'], 'van_inventory' => $this->request->post['van_inventory'], 'inventory' => $this->request->post['inventory']), 
						$product_info['product_id']
					);	

					$json['success'] = $this->language->get('text_success');
				}
			} else {
                $json['error']['warning'] = $this->language->get('error_product');
            }
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}