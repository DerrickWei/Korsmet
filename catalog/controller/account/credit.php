<?php

class ControllerAccountCredit extends Controller {
    private $endpoint_credit = '';

    private function getEnvironment() {
		if ($this->config->get('config_environment') && 0) {
			// **** Development Environment **** //
			$this->endpoint_credit = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/salesCreditMemos';
		} else {
			// **** Production Environment **** //
			$this->endpoint_credit = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/salesCreditMemos';
		}
	}

    public function index() {
        $this->getEnvironment(); 

        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/credit');

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
			'text' => $this->language->get('text_credit'),
			'href' => $this->url->link('account/credit', '', true)
        );

        if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

        $this->load->model('checkout/order');
        $this->load->model('account/customer');

        $custom_field = (array) json_decode($this->model_account_customer->getCustomer($this->customer->getId())['custom_field']); 
        $result = $this->Auth($this->endpoint_credit . "?\$filter=customerId eq " . $custom_field[1]); //echo "<pre>"; print_r($result); die("+++");

        $data['credit_memos'] = array();
        foreach ($result['value'] as $credit) { 
            if (substr($credit['number'], 0, 2) == 'PS' && $credit['status'] != 'Corrective' && $credit['status'] != 'Canceled') { //echo "<pre>"; print_r($credit); die("=====");
                $data['credit_memos'][] = array(
                    'id'        => $credit['id'],
                    'number'    => $credit['number'],
                    'total_amount'       => $this->currency->format($credit['totalAmountIncludingTax'], $credit['currencyCode'], 1),
                    'status'             => $credit['status'],
                    'view'               => $this->url->link('account/credit/info', 'credit_id=' . $credit['id'], true)
                );
            }    
        }//echo "<pre>"; print_r($data['invoice']); die("**");

        $pagination = new Pagination();
		$pagination->total = count($data['credit_memos']);
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('account/credit', 'page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), (count($data['credit_memos'])) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > (count($data['credit_memos']) - 10)) ? count($data['credit_memos']) : ((($page - 1) * 10) + 10), count($data['credit_memos']), ceil(count($data['credit_memos']) / 10));

        $data['continue'] = $this->url->link('account/account', '', true);
        
        $data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/credit_list', $data));
    }

    public function info() {
        $this->getEnvironment(); 

        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
        }
        
        if (isset($this->request->get['credit_id'])) {
			$credit_id = $this->request->get['credit_id'];
		} else {
			$credit_id = 0;
		}

        $this->load->language('account/credit');  
        
        if (!$credit_id) {
            return new Action('error/not_found');
        } else {
            $credit_info = $this->Auth($this->endpoint_credit . "(" . $credit_id . ")?\$expand=salesCreditMemoLines,customer"); //echo "<pre>"; print_r($credit_info); die("****");

            if (!$credit_info) {
                return new Action('error/not_found');
            } else { 
                // To format price in Currency format
                for ($i = 0; $i < count($credit_info['salesCreditMemoLines']); $i++) {
                    if ($lineType == 'Item') {
                        $credit_info['salesCreditMemoLines'][$i]['unitPrice'] = $this->currency->format($credit_info['salesCreditMemoLines'][$i]['unitPrice'], $credit_info['currencyCode'], 1); 
                        $credit_info['salesCreditMemoLines'][$i]['amountExcludingTax'] = $this->currency->format($credit_info['salesCreditMemoLines'][$i]['amountExcludingTax'], $credit_info['currencyCode'], 1); 
                    }
                }

                $credit_info['totalAmountExcludingTax'] = $this->currency->format($credit_info['totalAmountExcludingTax'], $credit_info['currencyCode'], 1); 
                $credit_info['discountAmount'] = $this->currency->format($credit_info['discountAmount'], $credit_info['currencyCode'], 1);
                $credit_info['totalTaxAmount'] = $this->currency->format($credit_info['totalTaxAmount'], $credit_info['currencyCode'], 1);
                $credit_info['totalAmountIncludingTax'] = $this->currency->format($credit_info['totalAmountIncludingTax'], $credit_info['currencyCode'], 1);

                $data['credit_info'] = $credit_info;

                $this->response->setOutput($this->load->view('account/credit_info', $data));
            }
        }
    }

    private function Auth($url = false, $method = false, $data = false) {
        $this->load->model('sync/sync');
		$this->model_sync_sync->getToken();
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