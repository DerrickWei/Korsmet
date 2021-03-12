<?php
class ControllerCommonHome extends Controller {
	private $clientId       = 'e4f58a80-1532-4e4c-a203-07fb3ea20e17';
    private $clientSecret   = '?A9HMSFfR7--WJ?ufXqiCguJ2d26gRl8';
    private $redirectUri    = 'https://www.korsmet.com/index.php?route=common/home/callback';
    private $urlAuthorize   = 'https://login.windows.net/common/oauth2/authorize?resource=https://api.businesscentral.dynamics.com';
    private $urlAccessToken = 'https://login.windows.net/common/oauth2/token?resource=https://api.businesscentral.dynamics.com';

	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	/* OAuth2 Get AccessToken Call back Function */
	public function callback() {
		// Validate state
		/*$expectedState = $this->session->data['oauthState'];
		$providedState = $this->request->get['state'];

		if (!isset($expectedState)) {
			// If there is no expected state in the session,
			// do nothing and redirect to the home page.
			die('Invalid auth state');
		}

		if (!isset($providedState) || $expectedState != $providedState) {
			die('The provided auth state did not match the expected value');
		}*/

		// Authorization code should be in the "code" query param
		$authCode = $this->request->get['code'];

		if (isset($authCode)) {
			// Initialize the OAuth client
			$oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
				'clientId'                => $this->clientId,    		 // The client ID assigned to you by the provider
				'clientSecret'            => $this->clientSecret,        // The client password assigned to you by the provider
				'redirectUri'             => $this->redirectUri,
				'urlAuthorize'            => $this->urlAuthorize,
				'urlAccessToken'          => $this->urlAccessToken,
				'urlResourceOwnerDetails' => '',
			]);
	  
			try {
				// Make the token request
				$accessToken = $oauthClient->getAccessToken('authorization_code', [
					'code' => $authCode
				]);
				
				// Store Accesstoken to DB
				$token = array(
					'accessToken'	=> $accessToken->getToken(),
					'refreshToken'	=> $accessToken->getRefreshToken(),
					'expires'		=> $accessToken->getExpires()
				);

				$this->load->model('sync/sync');
				$this->model_sync_sync->addToken($token);
	  
				// Store Accesstoken to session
				$this->session->data['accessToken'] = $accessToken;
				echo "<pre>"; print_r($accessToken); die("=====");

			} catch (League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
				die($e->getMessage());
			}
		}
	}
}
