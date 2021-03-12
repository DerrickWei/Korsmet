<?php
class ModelSyncSync extends Model {
	private $clientId       = 'e4f58a80-1532-4e4c-a203-07fb3ea20e17';
    private $clientSecret   = '?A9HMSFfR7--WJ?ufXqiCguJ2d26gRl8';
    private $redirectUri    = 'https://www.korsmet.com/index.php?route=common/home/callback';
    private $urlAuthorize   = 'https://login.windows.net/common/oauth2/authorize?resource=https://api.businesscentral.dynamics.com';
    private $urlAccessToken = 'https://login.windows.net/common/oauth2/token?resource=https://api.businesscentral.dynamics.com';
	
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
	
    public function Auth($url = false, $method = false, $data = false) {
		$this->getToken();
        $accessToken = $this->session->data['accessToken'];

        if (!$method) {
            $method = 'GET';
        }

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
	
	public function updateUpc ($itemDetail) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET upc = '" . $this->db->escape($itemDetail['upc']) . "' WHERE model = '" . $this->db->escape($itemDetail['id']) . "'");
    }

    public function getModelByUpc ($upc) {
        $query = $this->db->query("SELECT model FROM " . DB_PREFIX . "product WHERE upc = '" . $this->db->escape($upc) . "'");

        return $query->row['model'];
    }
	
	public function getProductManufacturertoFilter ($product_id) {
		$query = $this->db->query("SELECT f.*, p2c.category_id
                                     FROM " . DB_PREFIX . "filter_description f 
                                     JOIN " . DB_PREFIX . "manufacturer m
                                       ON f.name = m.name 
									 JOIN " . DB_PREFIX . "product p
									   ON m.manufacturer_id = p.manufacturer_id 
									 JOIN " . DB_PREFIX . "product_to_category p2c
									   ON p.product_id = p2c.product_id   
									WHERE p.product_id = '" . (int) $product_id . "'");	
		return $query->row;
	}

    public function getFilterByProductManufacturer ($manufacturer_id) { 
        $query = $this->db->query("SELECT f.* 
                                     FROM " . DB_PREFIX . "filter_description f 
                                     JOIN " . DB_PREFIX . "manufacturer m
                                       ON f.name = m.name 
                                    WHERE m.manufacturer_id = " . (int) $manufacturer_id);
        return $query->row;
    }

    public function addFilterToCategory($filter_id, $category_id) {//die("1111");
        $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "category_filter SET category_id = " . (int) $category_id . ", filter_id = " . (int) $filter_id);
    }

    public function addFilterToProduct($filter_id, $product_id) {
        $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_filter SET product_id = " . (int) $product_id . ", filter_id = " . (int) $filter_id);
    }

    public function getCategoryFilter($filter_id, $category_id) {
        $query = $this->db->query("SELECT * 
                                     FROM " . DB_PREFIX . "category_filter 
                                    WHERE filter_id = " . (int) $filter_id . " AND category_id = " . (int) $category_id);
        return $query->row;
    }
	
	public function getModelByName($name) {
		$query = $this->db->query("SELECT model FROM " . DB_PREFIX . "product WHERE ean = '" . $this->db->escape($name) . "'");
		
		return $query->row['model'];
	}
	
	public function getProductByModel($model) {
		$query = $this->db->query("SELECT * 
									 FROM " . DB_PREFIX . "product 
									WHERE model = '" . $this->db->escape($model) . "'");
		
		return $query->row;
	}
	
	public function getProductByCategory() {
		$query = $this->db->query("SELECT p.* FROM " . DB_PREFIX . "product p JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id = p2c.product_id WHERE p2c.category_id = 96");	
		
		return $query->rows;
	}
	
	public function updateImage($image, $product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($image) . "' WHERE product_id = " . (int) $product_id);
	}
	
	public function getProductCategory($product_id) {
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = " . (int) $product_id);
		
		return $query->row;
	}
	
	public function getProductAtt($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
		
		return $query->rows;
	}
	
	public function getCategoryByBcId($bc_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category WHERE bc_id = '" . $this->db->escape($bc_id) . "'");	
		
		return $query->row;
	}
	
	public function getManufacturerByName($name) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $this->db->escape($name) . "'");
		
		return $query->row['manufacturer_id'];
	}

	public function getFilterIdByName($name) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "filter_description WHERE name = '" . $this->db->escape($name) . "'");
		
		return $query->row['filter_id'];
	}
	
	public function updateDimensionId($id, $bc_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "manufacturer SET bc_id = '" . $this->db->escape($bc_id) . "' WHERE manufacturer_id = '" . (int)$id . "'");
	}
	
	public function getCategoryIdByName($name) {
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '" . $this->db->escape($name) . "'");

		return $query->row['category_id'];
	}
	
	public function modifyProduct($data, $bc_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($bc_id) . "'");
		$product_id = $query->row['product_id'];

		$this->db->query("UPDATE " . DB_PREFIX . "product SET upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', price = '" . (float)$data['price'] . "', date_modified = NOW() WHERE model = '" . $this->db->escape($bc_id) . "'");
		
		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET category_id = '" . (int)$category_id . "' WHERE product_id = '" . (int)$product_id . "'");
			}
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("UPDATE " . DB_PREFIX . "product_description SET name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}
	}
	
	public function addProduct($data) { //echo "<pre>"; print_r($data); die("===");
		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

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

				$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$data['product_category'][0] . "', filter_id = '" . (int)$filter_id . "'");
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
	
	public function deleteProduct($product_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int)$product_id);
		$this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int)$product_id . "'");

		$this->cache->delete('product');
	}
	
	public function updateProductQuantity($product_id, $quantity) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = '" . (int) $quantity . "' WHERE product_id = '" . (int) $product_id . "'");
	}

	public function getCategories() {
		$result = $this->db->query("SELECT bc_id, category_id FROM " . DB_PREFIX . "category");

		return $result->rows;
	}

	public function getItems($item_id) {
		$result = $this->db->query("SELECT product_id, model, upc FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($item_id) . "'");

		return $result->num_rows;
	}

	public function editProduct($data, $bc_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE model = '" . $this->db->escape($bc_id) . "'");
		$product_id = $query->row['product_id'];

		$this->db->query("UPDATE " . DB_PREFIX . "product SET upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', status = '" . (int)$data['status'] . "', price = '" . (float)$data['price'] . "', date_modified = NOW() WHERE model = '" . $this->db->escape($bc_id) . "'");
		
		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET category_id = '" . (int) $category_id . "' WHERE product_id = '" . (int)$product_id . "'");
			}
		}

		foreach ($data['product_filter'] as $filter_id) {
			$this->db->query("UPDATE " . DB_PREFIX . "product_filter SET filter_id = '" . (int)$filter_id . "' WHERE product_id = '" . (int)$product_id . "'");

			$category_filter = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$data['product_category'][0] . "' AND filter_id = '" . (int)$filter_id . "'");
			if (!$category_filter->num_rows) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$data['product_category'][0] . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("UPDATE " . DB_PREFIX . "product_description SET name = '" . $this->db->escape($value['name']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}
	}

	public function compareProduct($product, $bc_id) {
		$product_query = $this->db->query("SELECT product_id 
									   		 FROM " . DB_PREFIX . "product 
									  		WHERE upc = '" . $this->db->escape($product['upc']) . "' 
											  AND ean = '" . $this->db->escape($product['ean']) . "' 
											  AND quantity = '" . (int) $product['quantity'] . "' 
											  AND minimum = '" . (int) $product['minimum'] . "' 
											  AND manufacturer_id = '" . (int) $product['manufacturer_id'] . "' 
											  AND price = '" . (float) $product['price'] . "' 
											  AND model = '" . $this->db->escape($bc_id) . "'");
		if ($product_query->row['product_id']) {
			$product_to_category = $this->db->escape("SELECT category_id 
														FROM " . DB_PREFIX . "product_to_category
													   WHERE product_id = " . (int) $product_query->row['product_id'] . " 
														 AND category_id = " . (int) $product['product_category'][0]['category_id']);
			if ($product_to_category->row['category_id']) {
				foreach ($product['product_attribute'] as $product_attribute) {
					if ($product_attribute['attribute_id']) {
						foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
							$attribute = $this->db->query("SELECT * 
															 FROM " . DB_PREFIX . "product_attribute 
														    WHERE product_id = " . (int) $product_query->row['product_id'] . " 
															  AND attribute_id = " . (int) $product_attribute['attribute_id'] . " 
															  AND text = '" . $this->db->escape($product_attribute_description['text']) . "'");
							if ($attribute->row['product_id']) {
								// Product has not been modified
								return false;
							} else {
								// Product has been modified
								return true;
							}
						}
					}
				}
			} else {
				// Product has been modified
				return true;
			}
		} else {
			// Product has been modified
			return true;
		}
	}

	public function checkIfExistingCustomer($email) {
		$customer_query = $this->db->query("SELECT customer_id 
											  FROM " . DB_PREFIX . "customer 
											 WHERE email = '" . $this->db->escape($email) . "'");

		return $customer_query->row['customer_id'];
	}

	public function getAddressCustomField($customer_id) {
		$query = $this->db->query("SELECT custom_field 
									 FROM " . DB_PREFIX . "address 
									WHERE customer_id = " . (int) $customer_id);

		return json_decode($query->row['custom_field'], true);
	}

	public function addAddress($address, $customer_id) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($address['firstname']) . "', lastname = '" . $this->db->escape($address['lastname']) . "', company = '" . $this->db->escape($address['company']) . "', address_1 = '" . $this->db->escape($address['address_1']) . "', address_2 = '" . $this->db->escape($address['address_2']) . "', city = '" . $this->db->escape($address['city']) . "', postcode = '" . $this->db->escape($address['postcode']) . "', country_id = '" . (int)$address['country_id'] . "', zone_id = '" . (int)$address['zone_id'] . "', custom_field = '" . $this->db->escape(isset($address['custom_field']) ? json_encode($address['custom_field']) : json_encode(array())) . "'");
	}
	
	public function getAllProducts() {
		$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product");	
		
		return $query->rows;
	}
	
	public function getProductAttribute($product_id) {
		$query = $this->db->query("SELECT text FROM " . DB_PREFIX . "product_attribute WHERE product_id = " . (int) $product_id);
		
		return $query->rows;
	}
	
	public function modifyQuantity($inventory, $product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = " . (int) $inventory . " WHERE product_id = " . (int) $product_id);
	}
}