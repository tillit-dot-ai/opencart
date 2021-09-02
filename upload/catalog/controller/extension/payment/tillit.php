<?php
class ControllerExtensionPaymentTillit extends Controller { 
  public function index() {
	  
	  $json = array();
     
    if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'tillit') {
		
      $data = array();
	  
	  

       $merchant_key =  $this->config->get('payment_tillit_merchant_id');
       $gross_amount =  '0.00';
       $company_name = $this->session->data['payment_address']['company'];
       $country_prefix =  $this->session->data['payment_address']['iso_code_2'];
       $organization_number =  '';
       $first_name =  $this->session->data['payment_address']['firstname'];
       $last_name =  $this->session->data['payment_address']['lastname'];
       $email =  $this->customer->getEmail();
       $phone_number =  $this->customer->getTelephone();
       $currency =  '';
       $merchant_id =  $this->config->get('payment_tillit_merchant_id');
       $gross_amount =  $this->cart->getTotal();

	   
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
	   
	   $data['tillit_api_url'] = '';
	   
	   if($data['payment_mode']=='production'){
		   $data['tillit_api_url'] = 'https://api.tillit.ai/v1/';
	   } elseif($data['payment_mode']=='development'){
			$data['tillit_api_url'] = 'https://test.api.tillit.ai/v1/';
	   } elseif($data['payment_mode']=='staging'){
		   $data['tillit_api_url'] = 'https://staging.api.tillit.ai/v1/';		   
	   }
	   
	   $data['jsonBody'] = '{"merchant_short_name":"'.$merchant_key.'","gross_amount":"'.$gross_amount.'", "buyer":{"company":{"company_name":"'.$company_name.'", "country_prefix":"'.$country_prefix.'","organization_number":"919501847"},"representative":{"email":"'.$email.'","first_name":"'.$first_name.'","last_name":"'.$last_name.'","phone_number":"'.$phone_number.'"}},"currency":"NOK","merchant_id":"'.$merchant_key.'","line_items":[{"name":"Cart","description":"","gross_amount":"'.$gross_amount.'","net_amount":"'.$gross_amount.'","discount_amount":"0","tax_amount":"0.00","tax_class_name":"VAT 0.00%","tax_rate":"0.000000","unit_price":"'.$gross_amount.'","quantity":1,"quantity_unit":"item","image_url":"","product_page_url":"","type":"PHYSICAL","details":{"categories":[],"barcodes":[]}}]}';
	   
		


    }
	
    return $this->load->view('extension/payment/tillit', $data);
  }

 public function confirm() {
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'tillit') {
			$this->load->model('checkout/order');

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_tillit_order_status_id'));
		
			$json['redirect'] = $this->url->link('checkout/success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
    
}
