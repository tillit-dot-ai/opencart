<?php
class ControllerExtensionPaymentTillit extends Controller { 
	public function index() {
		return $this->load->view('extension/payment/tillit');
	}

	public function confirm() {
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'tillit') {
			$this->load->model('checkout/order');

			//$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_tillit_order_status_id'));
		
		//$json['redirect'] = $this->url->link('checkout/success');
			$url = "https://test.api.tillit.ai/v1/order";
			$data = array();
			 $data['merchant_key'] =  $this->config->get('payment_tillit_merchant_id');
			 $data['api_key'] =  $this->config->get('payment_tillit_api_key');
			 $data['choose_product'] =  $this->config->get('payment_tillit_choose_product');
			 $data['invoice_days'] =  $this->config->get('payment_tillit_invoice_days');
			 $data['payment_mode'] =  $this->config->get('payment_tillit_payment_mode');
			 $data['company_name'] =  $this->config->get('payment_tillit_company_name');
			 $data['company_org'] =  $this->config->get('payment_tillit_company_org');
			 $data['pre_approve_checkout'] =  $this->config->get('payment_tillit_pre_approve_checkout');
			 $data['initiate_payment_refund'] =  $this->config->get('payment_tillit_initiate_payment_refund');
			 $data['status_order_unverify'] =  $this->config->get('payment_tillit_status_order_unverify');
			 $data['status_order_verify'] =  $this->config->get('payment_tillit_status_order_verify');
			 $data['status_order_shipped'] =  $this->config->get('payment_tillit_status_order_shipped');
			 $data['status_order_delivered'] =  $this->config->get('payment_tillit_status_order_delivered');
			 $data['status_order_canceled'] =  $this->config->get('payment_tillit_status_order_canceled');
			 $data['status_order_refunded'] =  $this->config->get('payment_tillit_status_order_refunded');
			 $data['total'] =  $this->config->get('payment_tillit_total');
			 $data['order_status_id'] =  $this->config->get('payment_tillit_order_status_id');
			 $data['geo_zone_id'] =  $this->config->get('payment_tillit_geo_zone_id');
			 $data['status'] =  $this->config->get('payment_tillit_status');
			 $data['sort_order'] =  $this->config->get('payment_tillit_sort_order');

			  $curl = curl_init();

				curl_setopt_array($curl, [
				  CURLOPT_URL => "https://test.api.tillit.ai/v1/orders",
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "GET",
				]);

				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
				  echo "cURL Error #:" . $err;
				} else {
				  echo $response;
				}
								
		}
	
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
		
}
