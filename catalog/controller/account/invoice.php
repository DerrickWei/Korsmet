<?php

class ControllerAccountInvoice extends Controller {
    private $endpoint_invoice = '';

    private function getEnvironment() {
		if ($this->config->get('config_environment') && 0) {
			// **** Development Environment **** //
			$this->endpoint_invoice = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(41456175-baaa-4c6a-bbee-15c57159eedf)/salesInvoices';
		} else {
			// **** Production Environment **** //
			$this->endpoint_invoice = 'https://api.businesscentral.dynamics.com/v1.0/api/v1.0/companies(79e3a081-7ac2-45f7-998d-3d1b2e925d2a)/salesInvoices';
		}
	}

    public function index() {
        $this->getEnvironment(); 

        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/invoice');

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
			'text' => $this->language->get('text_invoice'),
			'href' => $this->url->link('account/invoice', '', true)
        );

        if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

        $this->load->model('checkout/order');
        $this->load->model('account/customer');

        $custom_field = (array) json_decode($this->model_account_customer->getCustomer($this->customer->getId())['custom_field']); 
        $result = $this->Auth($this->endpoint_invoice . "?\$filter=customerId eq " . $custom_field[1]);  //echo "<pre>"; print_r($result); die("*****");

        $data['invoices'] = array();
        foreach ($result['value'] as $invoice) {
            //if ($invoice['customerId'] == $custom_field[1]) { //echo "<pre>"; print_r($invoice); die("=====");
                $data['invoices'][] = array(
                    'id'        => $invoice['id'],
                    'number'    => $invoice['number'],
                    'remaining_amount'   => $this->currency->format($invoice['remainingAmount'], $invoice['currencyCode'], 1),
                    'total_amount'       => $this->currency->format($invoice['totalAmountIncludingTax'], $invoice['currencyCode'], 1),
                    'status'             => $invoice['status'],
                    'view'               => $this->url->link('account/invoice/info', 'invoice_id=' . $invoice['id'], true)
                );
            //}    
        }//echo "<pre>"; print_r($data['invoice']); die("**");

        $pagination = new Pagination();
		$pagination->total = count($data['invoices']);
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('account/invoice', 'page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), (count($data['invoices'])) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > (count($data['invoices']) - 10)) ? count($data['invoices']) : ((($page - 1) * 10) + 10), count($data['invoices']), ceil(count($data['invoices']) / 10));

        $data['continue'] = $this->url->link('account/account', '', true);
        
        $data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/invoice_list', $data));
    }

    public function info() {
        $this->getEnvironment(); 

        if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
        }
        
        if (isset($this->request->get['invoice_id'])) {
			$invoice_id = $this->request->get['invoice_id'];
		} else {
			$invoice_id = 0;
		}

        $this->load->language('account/invoice');  
        
        if (!$invoice_id) {
            return new Action('error/not_found');
        } else {
            $invoice_info = $this->Auth($this->endpoint_invoice . "(" . $invoice_id . ")?\$expand=salesInvoiceLines,customer"); //echo "<pre>"; print_r($invoice_info); die("****");

            if (!$invoice_info) {
                return new Action('error/not_found');
            } else {
                // To format price in Currency format
                for ($i = 0; $i < count($invoice_info['salesInvoiceLines']); $i++) {
                    $invoice_info['salesInvoiceLines'][$i]['unitPrice'] = $this->currency->format($invoice_info['salesInvoiceLines'][$i]['unitPrice'], $invoice_info['currencyCode'], 1); 
                    $invoice_info['salesInvoiceLines'][$i]['amountExcludingTax'] = $this->currency->format($invoice_info['salesInvoiceLines'][$i]['amountExcludingTax'], $invoice_info['currencyCode'], 1); 
                }

                $invoice_info['totalAmountExcludingTax'] = $this->currency->format($invoice_info['totalAmountExcludingTax'], $invoice_info['currencyCode'], 1); 
                $invoice_info['discountAmount'] = $this->currency->format($invoice_info['discountAmount'], $invoice_info['currencyCode'], 1);
                $invoice_info['totalTaxAmount'] = $this->currency->format($invoice_info['totalTaxAmount'], $invoice_info['currencyCode'], 1);
                $invoice_info['totalAmountIncludingTax'] = $this->currency->format($invoice_info['totalAmountIncludingTax'], $invoice_info['currencyCode'], 1);
                $invoice_info['remainingAmount'] = $this->currency->format($invoice_info['remainingAmount'], $invoice_info['currencyCode'], 1);

                $data['invoice_info'] = $invoice_info;

                $this->response->setOutput($this->load->view('account/invoice_info', $data));
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