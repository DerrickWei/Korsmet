<?php
class ControllerApiCustomer extends Controller {
	public function index() {
		$this->load->language('api/customer');

		// Delete past customer in case there is an error
		unset($this->session->data['customer']);

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			// Add keys for missing post vars
			$keys = array(
				'customer_id',
				'customer_group_id',
				'firstname',
				'lastname',
				'email',
				'telephone',
			);

			foreach ($keys as $key) {
				if (!isset($this->request->post[$key])) {
					$this->request->post[$key] = '';
				}
			}

			// Customer
			if ($this->request->post['customer_id']) {
				$this->load->model('account/customer');

				$customer_info = $this->model_account_customer->getCustomer($this->request->post['customer_id']);

				if (!$customer_info || !$this->customer->login($customer_info['email'], '', true)) {
					$json['error']['warning'] = $this->language->get('error_customer');
				}
			}

			if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}

			if ((utf8_strlen($this->request->post['email']) > 96) || (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL))) {
				$json['error']['email'] = $this->language->get('error_email');
			}

			if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}

			// Customer Group
			if (is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
				$customer_group_id = $this->request->post['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}

			// Custom field validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account') { 
					if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
						$json['error']['custom_field' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					}
				}
			}

			if (!$json) {
				$this->session->data['customer'] = array(
					'customer_id'       => $this->request->post['customer_id'],
					'customer_group_id' => $customer_group_id,
					'firstname'         => $this->request->post['firstname'],
					'lastname'          => $this->request->post['lastname'],
					'email'             => $this->request->post['email'],
					'telephone'         => $this->request->post['telephone'],
					'custom_field'      => isset($this->request->post['custom_field']) ? $this->request->post['custom_field'] : array()
				);

				$json['success'] = $this->language->get('text_success');
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// Update Customer infomation
	public function updateCustomer() {
		$this->load->language('api/customer');

		$this->load->model('account/customer');
		$this->load->model('account/address');
		$this->load->model('localisation/zone');

		// Delete past customer in case there is an error
		unset($this->session->data['customer']);

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			if ($this->request->post['id'] && $this->request->post['number']) {
				$custom_field = json_encode(array(
					'1'		=> $this->request->post['id'],
					'2'		=> $this->request->post['number']
				));

				$customer_info = $this->model_account_customer->getCustomerByCustomField($custom_field); 
				//echo "<pre>"; print_r($customer_info); die("***");

				if (!$customer_info) {
					$json['error']['warning'] = $this->language->get('error_customer');
				}

				if (!$json) {
					// Update Customer Basic Info
					$customer_data = array(
						'firstname'		=> 'BC - ',
						'lastname'		=> $this->request->post['displayName'],
						'email'			=> $this->request->post['email'],
						'telephone'		=> $this->request->post['phoneNumber'],
						'custom_field'	=> array(
							'account'	=> array('1' => $this->request->post['id'], '2' => $this->request->post['number'])
						)
					);
					$this->model_account_customer->editCustomer($customer_info['customer_id'], $customer_data);

					// Update Customer Address Info
					$address_data = array(
						'firstname'		=> 'BC - ',
						'lastname'		=> $this->request->post['displayName'],
						'company'		=> '',
						'address_1'		=> $this->request->post['street'],
						'address_2'		=> '',
						'city'			=> $this->request->post['city'],
						'postcode'		=> $this->request->post['postalCode'],
						'country_id'	=> 38,
						'zone_id'		=> $this->model_localisation_zone->getZoneByCode($this->request->post['state'])
					);
					$this->model_account_address->editAddress($customer_info['address_id'], $address_data);

					$json['success'] = $this->language->get('text_success');
				}
			} else {
				$json['error']['warning'] = $this->language->get('error_customer');
			}
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
