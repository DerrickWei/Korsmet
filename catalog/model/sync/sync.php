<?php
class ModelSyncSync extends Model {
	private $clientId       = 'e4f58a80-1532-4e4c-a203-07fb3ea20e17';
    private $clientSecret   = '?A9HMSFfR7--WJ?ufXqiCguJ2d26gRl8';
    private $redirectUri    = 'https://www.korsmet.com/index.php?route=common/home/callback';
    private $urlAuthorize   = 'https://login.windows.net/common/oauth2/authorize?resource=https://api.businesscentral.dynamics.com';
    private $urlAccessToken = 'https://login.windows.net/common/oauth2/token?resource=https://api.businesscentral.dynamics.com';
	
    private $auth = "TS5ZQU5HOjhublN2QWllWDlOaDI3T3VIbnVGbk1ZbFJzcjJ0U1cvaE5aZitubk8xNUk9";
    private $endpoint = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies';
    private $endpoint_1 = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/salesOrders?\$filter=number eq 'SO-000432'";
    private $endpoint_2 = "https://api.businesscentral.dynamics.com/v2.0/74611a07-434f-4aba-89c2-c71ef6e38813/Production/ODataV4/Company('Pilot%20Test%20Dec%203%20backup')/getSalesPrice?\$filter=Sales_Type eq 'Customer' and Sales_Code eq 'OPENCART'";

    private $item = "https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/items?\$filter=number eq '8806358586508'";

    private $order_url = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/salesOrders';
    // (5cab940a-9472-ea11-a813-000d3a840bb2)/salesOrderLines

    public function Auth($url = false, $method = false, $data = false) {
        $accessToken = $this->session->data['accessToken'];

        if (!$method) {
            $method = 'GET';
        }

        $sales_order = array(
            'orderDate' => date('Y-m-d'),
            'customerNumber'    => 'OPENCART',
            'currencyCode'      => 'CAD',
            'paymentTermsId'    => 'f74bb0fb-2ee5-4fa3-85bd-e4598e2c3b77'
        );

        $sales_order_line = array(
            "itemId"        => "ed2c2454-9632-4773-be55-000103c04a6c",
            "lineType"      => "Item",
            "quantity"      => 9,
            "unitPrice"     => 2,
            "discountAmount"=> 0,
            "taxCode"       => 'TAXABLE',
            'reservedQuantity'   => 9
        );  

        try {
            // Create a Graph client
            $graph = new Microsoft\Graph\Graph();
            $graph->setAccessToken($accessToken['access_token']);

            try {
                
                if ($method == 'GET') {
                    $result = $graph->createRequest('GET', $url)->execute();
                } else {
                    // Test POST Request
                    $result = $graph->createRequest($method, $url)
                    ->attachBody($data)
                    ->execute();
                }

                // Test POST Request
                //$result = $graph->createRequest('POST', $this->order_url)
                //    ->attachBody($sales_order)
                //    ->execute();

                //$result = $graph->createRequest('GET', $url)->execute();
                //echo "<pre>"; print_r($result->getBody()); die("====");
                return $result->getBody();

            } catch (\Microsoft\Graph\Exception\GraphException $e) {
                
                // Failed to call api
                die($e->getMessage());
            }
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        
            // Failed to get the access token
            die($e->getMessage());
        
        }
    }

    public function updateProduct($data) {
        $this->db->query("UPDATE " . DB_PREFIX . "product 
                             SET quantity = " . (int) $data['inventory'] . ", 
                                 price = " . $this->db->escape($data['price']) . ", 
                                 status = " . $data['price'] == 0 ? 0 : 1 . " 
                           WHERE model = '" . $this->db->escape($data['id']) . "'");    
    }
	
	public function addToken($token) {
        $this->db->query("INSERT INTO bc_token 
                                  SET access_token = '" . $this->db->escape($token['accessToken']) . "', 
                                      refresh_token = '" . $this->db->escape($token['refreshToken']) . "', 
                                      expires = " . $this->db->escape($token['expires']));
    }
	
	public function getToken() {
		$provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => $this->clientId,    		 // The client ID assigned to you by the provider
			'clientSecret'            => $this->clientSecret,        // The client password assigned to you by the provider
			'redirectUri'             => $this->redirectUri,
			'urlAuthorize'            => $this->urlAuthorize,
			'urlAccessToken'          => $this->urlAccessToken,
			'urlResourceOwnerDetails' => '',
        ]);
		
		$token_query = $this->db->query("SELECT * FROM bc_token ORDER BY token_id DESC");
		
		$existingToken = array(
            'access_token'      => $token_query->row['access_token'],
            'refresh_token'     => $token_query->row['refresh_token'],
            'expires'           => $token_query->row['expires']
        ); 
		
		if ($existingToken['expires'] < time()) {
            $newAccessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $existingToken['refresh_token']
            ]);
            
            // Store New Access Token to DB
			$token = array(
				'accessToken'	=> $newAccessToken->getToken(),
				'refreshToken'	=> $newAccessToken->getRefreshToken(),
				'expires'		=> $newAccessToken->getExpires()
			);

			$this->addToken($token);
			$this->session->data['accessToken'] = $token;
        } else {
			$this->session->data['accessToken'] = $existingToken;
		}
	}
	
	public function getCategoryById($bc_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE bc_id = '" . $this->db->escape($bc_id) . "'");  
        
        return $query->row;
    }
	
	public function updateCategory($name, $category_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "category_description SET name = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($nama) . "', meta_title = '" . $this->db->escape($name) . "' WHERE category_id = " . (int) $category_id);
    }
	
	public function addCategory($category) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "category SET bc_id = '" . $this->db->escape($category['bc_id']) . "', status = 1, date_modified = NOW(), date_added = NOW()");

        $category_id = $this->db->getLastId();

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int) $category_id . "', language_id = 1, name = '" . $this->db->escape($category['name']) . "', description = '" . $this->db->escape($category['name']) . "', meta_title = '" . $this->db->escape($category['meta_title']) . "'");

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_path SET category_id = '" . (int) $category_id . "', path_id = '" . (int)$category_id . "', level = 0");

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int) $category_id . "', store_id = 0");

        $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int) $category_id . "', store_id = 0, layout_id = 0");

        $this->cache->delete('category');

		return $category_id;
    }

    public function getManufacturerById($bc_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer WHERE bc_id = '" . $this->db->escape($bc_id) . "'");

        return $query->row;
    }

    public function updateManufacturer($name, $manufacturer_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "manufacturer SET name = '" . $name . "' WHERE manufacturer_id = " . (int) $manufacturer_id);
    }

    public function addManufacturer($manufacturer) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET name = '" . $this->db->escape($manufacturer['name']) . "', description = '" . $this->db->escape($manufacturer['name']) . "', bc_id = '" . $this->db->escape($manufacturer['bc_id']) . "', type = 0, sort_order = 0");

        $manufacturer_id = $this->db->getLastId();

        $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int) $manufacturer_id . "', store_id = 0");

        $this->cache->delete('manufacturer');

		return $manufacturer_id;
    }
	
	public function getManufacturerByName($name) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $this->db->escape($name) . "'");

		return $query->row['manufacturer_id'];
	}
	
	public function getCategoryIdByName($name) {
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $this->db->escape($name) . "'");

		return $query->row['category_id'];
	}
	
	public function getProductByModel($model) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($model) . "'");

        return $query->row;
    }

    public function addProduct($data) { //echo "<pre>"; print_r($data); die("===");
		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

		$product_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if (isset($data['product_store'])) {
			foreach ($data['product_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$recurring['customer_group_id'] . ", `recurring_id` = " . (int)$recurring['recurring_id']);
			}
		}
		
		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
				if ((int)$product_reward['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
				}
			}
		}
		
		// SEO URL
		if (isset($data['product_seo_url'])) {
			foreach ($data['product_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}
		
		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}


		$this->cache->delete('product');

		return $product_id;
	}
	
	public function modifyProduct($data, $bc_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($bc_id) . "'");
		$product_id = $query->row['product_id'];

		$this->db->query("UPDATE " . DB_PREFIX . "product SET upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', price = '" . (float)$data['price'] . "', date_modified = NOW() WHERE model = '" . $this->db->escape($bc_id) . "'");
		
		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET category_id = '" . (int)$category_id . "' WHERE product_id = '" . (int)$product_id . "'");
			}
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("UPDATE " . DB_PREFIX . "product_description SET name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}
	}
}