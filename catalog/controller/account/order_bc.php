<?php
class ControllerAccountOrderBc extends Controller {
    private $endpoint_order = '';

    private function getEnvironment() {
		if ($this->config->get('config_environment') && 0) {
			// **** Development Environment **** //
			$this->endpoint_order = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/salesOrders?\$filter=customerId eq ';
		} else {
			// **** Production Environment **** //
			$this->endpoint_order = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/salesOrders?\$filter=customerId eq ';
		}
    }

    public function index() {
        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/order', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/order');

        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/order_bc', $url, true)
        );

        $this->load->model('account/address');
        $customers = $this->model_account_address->getAddresses(); //echo "<pre>"; print_r($customers); die("==");

        $data['customers'] = array();
        foreach ($customers as $customer) {
            $data['customers'][] = array(
                'lastname'      => $customer['lastname'],
                'customer_id'   => $customer['custom_field'][1],
                'order_list'    => $this->url->link('account/order_bc/history', array('customer_id' => $customer['custom_field'][1]), true)
            );
        }
        //echo "<pre>"; print_r($data['customers']); die("==");

        $data['continue'] = $this->url->link('account/account', '', true);
        
        $data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/account_list', $data));
    }
    
    public function history() { 
        $this->getEnvironment();

        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/order', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/order');

		$this->document->setTitle($this->language->get('heading_title'));
		
        $url = '';

        if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
        
        $data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/order_bc', $url, true)
        );
        
        if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
        }
        
        $this->load->model('checkout/order');
        $this->load->model('account/customer');

        //$custom_field = (array) json_decode($this->model_account_customer->getCustomer($this->customer->getId())['custom_field']); 
        $result = $this->Auth($this->endpoint_order . "'" . $this->request->get['customer_id'] . "'");  echo "<pre>"; print_r($result); die("***");

        $data['orders'] = array();
        foreach ($result['value'] as $order) { 
            if ($order['customerId'] == $this->request->get['customer_id']) { 
                $data['orders'][] = array(
                    'order_id'      => $order['id'],
                    'order_number'  => $order['number'],
                    'name'          => $order['customerName'],
                    'status'        => $order['status'],
                    'total'         => $this->currency->format($order['totalAmountIncludingTax'], $order['currencyCode'], 1),
                    'date_added'    => $order['orderDate'],
                    'view'          => $this->url->link('account/order_bc/info', 'order_id=' . $order['id'], true)
                );
            }
        } 
        
        $pagination = new Pagination();
		$pagination->total = count($data['orders']);
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('account/order_bc', 'page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), (count($data['orders'])) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > (count($data['orders']) - 10)) ? count($data['orders']) : ((($page - 1) * 10) + 10), count($data['orders']), ceil(count($data['orders']) / 10));

        $data['continue'] = $this->url->link('account/account', '', true);
        
        $data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/order_list', $data));
    }

    public function info() {
        $this->getEnvironment(); 

        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
        }
        
        if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
        }
        
        $this->load->language('account/order');

        $this->document->setTitle($this->language->get('text_order'));

        $url = '';

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/order_bc', $url, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_order'),
            'href' => $this->url->link('account/order/info', 'order_id=' . $this->request->get['order_id'] . $url, true)
        );

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];

            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['continue'] = $this->url->link('account/order_bc', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if (!order_id) {
            return new Action('error/not_found');
        } else {
            $order_info = $this->Auth($this->endpoint_order . "(" . $order_id . ")?\$expand=salesOrderLines,customer"); //echo "<pre>"; print_r($order_info); die("****");

            if (!$order_info) {
                return new Action('error/not_found');
            } else {
                // To format price in Currency format
                for ($i = 0; $i < count($order_info['salesOrderLines']); $i++) {
                    $order_info['salesOrderLines'][$i]['unitPrice'] = $this->currency->format($order_info['salesOrderLines'][$i]['unitPrice'], $order_info['currencyCode'], 1); 
                    $order_info['salesOrderLines'][$i]['amountExcludingTax'] = $this->currency->format($order_info['salesOrderLines'][$i]['amountExcludingTax'], $order_info['currencyCode'], 1); 
                }

                $order_info['totalAmountExcludingTax'] = $this->currency->format($order_info['totalAmountExcludingTax'], $order_info['currencyCode'], 1); 
                $order_info['discountAmount'] = $this->currency->format($order_info['discountAmount'], $order_info['currencyCode'], 1);
                $order_info['totalTaxAmount'] = $this->currency->format($order_info['totalTaxAmount'], $order_info['currencyCode'], 1);
                $order_info['totalAmountIncludingTax'] = $this->currency->format($order_info['totalAmountIncludingTax'], $order_info['currencyCode'], 1);

                $data['order_info'] = $order_info;
                $data['reorder_url'] = $this->url->link('account/order_bc/reorder', '', true);

                $this->response->setOutput($this->load->view('account/order_bc_info', $data));
            }
        }
    }

    public function reorder() {
        $this->load->language('account/order');

        $this->load->model('account/order');

        if (isset($this->request->get['oid'])) {
			$order_id = $this->request->get['oid'];
		} else {
			$order_id = 0;
		}

        if ($order_id) {
            if (isset($this->request->get['pid'])) {
                $model = $this->request->get['pid'];
            } else {
                $model = 0;
            }
    
            $product = $this->model_account_order->getProductByModel($model); //echo "<pre>"; print_r($product); die("----");
    
            if ($product && $this->request->get['qty']) {
                $this->cart->add($product['product_id'], $this->request->get['qty'], array());

                $this->session->data['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $product['product_id']), $product['name'], $this->url->link('checkout/cart'));

                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);    
            } else {
                $this->session->data['error'] = sprintf($this->language->get('error_reorder'), $product['name']);
            }
        }

        $this->response->redirect($this->url->link('account/order_bc/info', 'order_id=' . $order_id));
    }

    private function Auth($url = false, $method = false, $data = false) {
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
}