<?php
class ControllerExtensionPaymentTillit extends Controller { 
  public function index() {
	  
	  $json = array();
     
    if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'tillit') {
		
      $data = array();
	  
	   $tillit_order_intent = $this->session->data['tillit_order_intent'];
	   
       $country_prefix =  $this->session->data['payment_address']['iso_code_2'];
       $first_name =  $this->session->data['payment_address']['firstname'];
       $last_name =  $this->session->data['payment_address']['lastname'];
       $email =  $this->customer->getEmail();
       $phone_number =  $this->customer->getTelephone();
       $gross_amount =  $this->cart->getTotal();
       $company_name =  $this->session->data['payment_address']['company'];
       $city =  $this->session->data['payment_address']['city'];
       $postal_code =  $this->session->data['payment_address']['postcode'];
       $region =  $this->session->data['payment_address']['zone_code'];
       $street_address =  $this->session->data['payment_address']['address_1'];
       $country =  $this->session->data['payment_address']['iso_code_2'];


       $data['merchant_key'] =  $this->config->get('payment_tillit_merchant_id');
       $data['api_key'] =  $this->config->get('payment_tillit_api_key');
      
       $data['organization_number'] =  '';
       $data['currency'] =  '';
       $data['merchant_id'] =  $this->config->get('payment_tillit_merchant_id');
	   
       $data['api_key'] =  $this->config->get('payment_tillit_api_key');
       $data['choose_product'] =  $this->config->get('payment_tillit_choose_product');
       $data['invoice_days'] =  $this->config->get('payment_tillit_invoice_days');
       $data['payment_mode'] =  $this->config->get('payment_tillit_payment_mode');
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
	   
	   
	   $data['jsonBody'] = '{ "gross_amount": "'.$gross_amount.'","net_amount": "'.$gross_amount.'","currency": "NOK","discount_amount": "0","discount_rate": "0","invoice_type": "FUNDED_INVOICE","tax_amount": "30","tax_rate": "0.1","buyer": {"company": {"company_name": "'.$company_name.'","country_prefix": "'.$country_prefix.'","organization_number": "974652358"},"representative":{"email": "'.$email.'","first_name": "'.$first_name.'","last_name": "'.$last_name.'","phone_number": "'.$phone_number.'"}},"order_intent_id": "'.$tillit_order_intent.'","merchant_additional_info": "Best merchant in town.","merchant_order_id": "cbc43531-f186-472a-92d3-ea9bdf6e9306","merchant_reference": "45aa52f387871e3a210645d4","merchant_urls": { "merchant_cancel_order_url": "{{merchant_url}}/cancel-order","merchant_confirmation_url": "{{merchant_url}}/confimation","merchant_edit_order_url": "{{merchant_url}}/edit-order","merchant_invoice_url": "{{merchant_url}}/invoice","merchant_order_verification_failed_url": "{{merchant_url}}/order-verification-failed","merchant_shipping_document_url": "{{merchant_url}}/shipping-document"},"billing_address": {"city": "'.$city.'","country": "'.$country.'","organization_name": "'.$company_name.'","postal_code": "'.$postal_code.'","references": {"co": "","reference": "","attn": ""},"region": "'.$region.'","street_address": "'.$street_address.'"},"shipping_address": {"city": "'.$city.'","country": "'.$country.'","organization_name": "'.$company_name.'","postal_code": "'.$postal_code.'","references": {"co": "","reference": "","attn": ""},"region": "'.$region.'","street_address": "'.$street_address.'"},"shipping_details": {"carrier_name": "UPS","carrier_tracking_url": "https://example.com/tracking-url/","expected_delivery_date": "2021-01-31","tracking_number": "track1234567890"},"recurring": true,"recurring_details": {"period": "weekly","description": "happening often","token": "jksdfjlsdjf"},"line_items": [{"description": "This is a nice game","details": {"barcodes": [{"value": "5954d9e0-ebfb-4498-86a9-3141e8942dc8","type": "SKU"},{"value": "cece4007-028c-469c-b0d2-2c038b583b69","type": "UPC"}],"brand": "CD Projekt Red","categories": ["Games","Entertainment"],"part_number": "n/a"},"image_url": "https://www.exampleobjects.com/product-image-800x600.jpg","name": "Witcher III - The wild hunt","gross_amount": "200","net_amount": "180","tax_amount": "20","discount_amount": "0","product_page_url": "https://www.example.com/products/7e34f2a8d","quantity": 1.0,"quantity_unit": "pcs","tax_class_name": "VAT 10%","tax_rate": "0.1","type": "DIGITAL","unit_price": "200"},{"description": "This is a red t-shirt","details": {"barcodes": [{"value": "5f35e886-6873-46a9-ae1b-970cc9f63f76","type": "SKU"},{"value": "9ba1fd0e-6048-45f6-b50e-9663e7057af2","type": "UPC"}],"brand": "Gucci","categories": ["Clothing","Designer"],"part_number": "n/a"},"image_url": "https://www.exampleobjects.com/product-image-1200x1200.jpg","name": "Red T-Shirt","gross_amount": "100","net_amount": "90","discount_amount": "0","tax_amount": "10","product_page_url": "https://www.example.com/products/f2a8d7e34","quantity": 5.0,"quantity_unit": "pcs","tax_class_name": "VAT 10%","tax_rate": "0.1","type": "PHYSICAL","unit_price": "20"}]}';
	    


    }
	
    return $this->load->view('extension/payment/tillit', $data);
  }

 public function confirm() {
	
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'tillit') {
			if($this->request->get['order_status']=='APPROVED') {
			$this->load->model('checkout/order');
		
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_tillit_order_status_id'));
		
			$json['redirect'] = $this->url->link('checkout/success');
		    }
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
    
}
