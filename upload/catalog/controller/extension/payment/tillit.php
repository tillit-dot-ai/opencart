<?php
class ControllerExtensionPaymentTillit extends Controller { 

  public function index() {
    $this->load->model('checkout/order');

    if(!isset($this->session->data['order_id'])) {
      return false;
    }

    $this->load->language('extension/payment/tillit');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    $paymentdata = $this->getTillitIntentOrderData($this->session->data['order_id']);
    
    $response = $this->setTillitPaymentRequest("/v1/order_intent", $paymentdata, 'POST');
    
    $tillit_err = $this->getTillitErrorMessage($response);

    if ($tillit_err) {
      if ($this->checkTillitStartsWithString($tillit_err, '1 validation error for CreateOrderIntentRequestSchema: buyer -> company -> organization_number')) {
        $error = $this->language->get('error_company');
      } else if ($this->checkTillitStartsWithString($tillit_err, '1 validation error for CreateOrderIntentRequestSchema: buyer -> representative -> phone_number')) {
        $error = $this->language->get('error_telephone');
      } else if ($this->checkTillitStartsWithString($tillit_err, 'Minimum Payment using Tillit')) {
        $error = $this->language->get('error_minimum');
      } else if ($this->checkTillitStartsWithString($tillit_err, 'Maximum Payment using Tillit')) {
        $error = $this->language->get('error_maximum');
      } else {
        $error = $tillit_err;
      }
      $data['approval'] = false;
      $data['message'] = sprintf($this->language->get('text_unverify'), $error);

    } else {
      $data['approval'] = true;
      $data['message'] = sprintf($this->language->get('text_verify'), $order_info['payment_company']);
    }

    $data['action'] = $this->url->link('extension/payment/tillit/send', '', true);
	
    return $this->load->view('extension/payment/tillit', $data);
  }

  public function send($value='')
  {
    $json = array();
    $paymentdata = $this->getTillitNewOrderData($this->session->data['order_id']);
    $response = $this->setTillitPaymentRequest('/v1/order', $paymentdata, 'POST');

    if (!isset($response)) {
      $this->session->data['error'] = $this->language->get('error_gateway');
      $json['redirect'] = $this->url->link('checkout/checkout');
    }

    $this->load->model('checkout/order');

    if (isset($response['result']) && $response['result'] === 'failure') {
      $this->session->data['error'] = $this->language->get('error_gateway').' response: '.$response['result'];
      $json['redirect'] = $this->url->link('checkout/checkout');
    }

    if (isset($response['response']['code']) && ($response['response']['code'] === 401 || $response['response']['code'] === 403)) {
      $this->session->data['error'] = $this->language->get('error_gateway').' response_code: '.$response['response']['code'];
      $json['redirect'] = $this->url->link('checkout/checkout');
    }

    if (isset($response['response']['code']) && $response['response']['code'] === 400) {
      $this->session->data['error'] = $this->language->get('error_gateway').' response_code: 400';
      $json['redirect'] = $this->url->link('checkout/checkout');
    }

    if (isset($response['error_message']) && $response['error_message']) {
      $this->session->data['error'] = $response['error_message'];
      $json['redirect'] = $this->url->link('checkout/checkout');
    }

    if (isset($response['response']['code']) && $response['response']['code'] >= 400) {
      $this->session->data['error'] = 'EHF Invoice is not available for this order.'.' response_code: '.$response['response']['code'];
      $json['redirect'] = $this->url->link('checkout/checkout');
    }
    if (isset($response['id']) && $response['id']) {
      $payment_data = array(
        'id' => $response['id'],
        'merchant_reference' => $response['merchant_reference'],
        'state' => $response['state'],
        'status' => $response['status'],
        'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
        'invoice_url' => $response['invoice_url'],
      );
      $this->load->model('extension/payment/tillit');
      $this->model_extension_payment_tillit->setTillitOrderPaymentData($this->session->data['order_id'], $payment_data);
      $json['redirect'] = $response['payment_url'];
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function getTillitNewOrderData($order_id)
  {
    $order_reference = round(microtime(1) * 1000);
    $tracking_number = '';

    $this->load->model('checkout/order');
    $order_info = $this->model_checkout_order->getOrder($order_id);

    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    $items = [];
    
    $order_product = $this->model_checkout_order->getOrderProducts($order_id);

    foreach ($order_product as $product) {
      $product_info = $this->model_catalog_product->getProduct($product['product_id']);
      $categories = $this->model_catalog_product->getCategories($product['product_id']);
        
      if ($product_info['image']) {
        $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
      } else {
        $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
      }

      $rate = ($product['tax']*100)/$product['price'];
      
      $product_item['name'] = $product['name'];
      $product_item['description'] = utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, 100);
      $product_item['gross_amount'] = strval($this->currency->format(($product['price']+$product['tax'])*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false));
      $product_item['net_amount'] = strval($this->currency->format($product['price']*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false));
      $product_item['discount_amount'] = '0.00';
      $product_item['tax_amount'] = strval($this->currency->format($product['tax']*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false));
      $product_item['tax_class_name'] = 'VAT ' . strval($this->getTillitRoundAmount($rate)) . '%';
      $product_item['tax_rate'] = strval($this->getTillitRoundAmount($rate));
      $product_item['unit_price'] = strval($this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false));
      $product_item['quantity'] = $product['quantity'];
      $product_item['quantity_unit'] = 'pcs';
      $product_item['image_url'] = $image;
      $product_item['product_page_url'] = $this->url->link('product/product', 'product_id=' . $product['product_id']);
      $product_item['type'] = 'PHYSICAL';
      $product_item['details'] = array(
        'brand' => $product_info['manufacturer'],
        'barcodes' => array(
          array(
            'type' => 'SKU',
            'value' => $product_info['ean']
          ),
          array(
            'type' => 'UPC',
            'value' => $product_info['upc']
          ),
        ),
      );

      $product_item['details']['categories'] = [];
      if ($categories) {
        $this->load->model('catalog/category');
          foreach ($categories as $category) {
            $category_info = $this->model_catalog_category->getCategory($category['category_id']);
            $product_item['details']['categories'][] = $category_info['name'];
          }
      }

      $items[] = $product_item;
    }

    $totals = $this->model_checkout_order->getOrderTotals($order_id);

    foreach ($totals as $total) {
      if ($total['code'] == 'shipping') {
        $array = array(
          'name' => 'Shipping',
          'description' => $total['title'],
          'gross_amount' => strval($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false)),
          'net_amount' => strval($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false)),
          'discount_amount' => '0.00',
          'tax_amount' => '0.00',
          'tax_class_name' => '',
          'tax_rate' => '0.00',
          'unit_price' => strval($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false)),
          'quantity' => 1,
          'quantity_unit' => 'sc', // shipment charge
          'image_url' => '',
          'product_page_url' => '',
          'type' => 'SHIPPING_FEE'
        );

        $items[] = $array;
      }

      if ($total['code'] == 'coupon') {
        $array = array(
          'name' => 'Discount',
          'description' => $total['title'],
          'gross_amount' => strval($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false)),
          'net_amount' => strval($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false)),
          'discount_amount' => '0.00',
          'tax_amount' => '0.00',
          'tax_class_name' => '',
          'tax_rate' => '0%',
          'unit_price' => strval($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false)),
          'quantity' => 1,
          'quantity_unit' => 'item', // shipment charge
          'image_url' => '',
          'product_page_url' => '',
          'type' => 'PHYSICAL'
        );

        $items[] = $array;
      }
    }

    $this->load->model('localisation/country');
    $payment_country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);
    $shipping_country_info = $this->model_localisation_country->getCountry($order_info['shipping_country_id']);

    $company_id = '';
    if(isset($this->session->data['payment_address']['company_id']) && $this->session->data['payment_address']['company_id']){
      $company_id = $this->session->data['payment_address']['company_id'];
    }

    $request_data = array(
      'gross_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
      'net_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
      'currency' => $order_info['currency_code'],
      'discount_amount' => '0.00',
      'discount_rate' => '0.00',
      'invoice_type' => $this->config->get('payment_tillit_choose_product'),
      'tax_amount' => '0',
      'tax_rate' => '0.00',
      'buyer' => array(
        'company' => array(
          'company_name' => $order_info['payment_company'],
          'country_prefix' => $payment_country_info['iso_code_2'],
          'organization_number' => $company_id,
          'website' => '',
        ),
        'representative' => array(
          'email' => $order_info['email'],
          'first_name' => $order_info['firstname'],
          'last_name' => $order_info['lastname'],
          'phone_number' => $order_info['telephone'],
        ),
      ),
      'merchant_additional_info' => $order_info['store_name'],
      'merchant_order_id' => strval($order_info['order_id']),
      'merchant_reference' => strval($order_reference),
      'merchant_urls' => array(
        'merchant_confirmation_url' => $this->url->link('extension/payment/tillit/confirm&order_id=' . $order_info['order_id']),
        'merchant_cancel_order_url' => $this->url->link('extension/payment/tillit/cancel&order_id=' . $order_info['order_id']),
        'merchant_edit_order_url' => '',
        'merchant_order_verification_failed_url' => '',
        'merchant_invoice_url' => '',
        'merchant_shipping_document_url' => ''
      ),
      'billing_address' => array(
        'city' => $order_info['payment_city'],
        'country' => $payment_country_info['iso_code_2'],
        'organization_name' => $order_info['payment_company'],
        'postal_code' => $order_info['payment_postcode'],
        'region' => $order_info['payment_zone'],
        'street_address' => $order_info['payment_address_1'] . $order_info['payment_address_2']
      ),
      'shipping_address' => array(
        'city' => $order_info['shipping_city'],
        'country' => $shipping_country_info['iso_code_2'],
        'organization_name' => $order_info['shipping_company'],
        'postal_code' => $order_info['shipping_postcode'],
        'region' => $order_info['shipping_zone'],
        'street_address' => $order_info['shipping_address_1'] . $order_info['shipping_address_2']
      ),
      'recurring' => false,
      'order_note' => $order_info['comment'],
      'line_items' => $items,
    );

    return $request_data;
  }

  public function getTillitProductItems($order_id)
  {
    $this->load->model('checkout/order');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    $items = [];
    
    $order_info = $this->model_checkout_order->getOrder($order_id);
    $line_items = $this->model_checkout_order->getOrderProducts($order_id);

    foreach ($line_items as $product) {
      $product_info = $this->model_catalog_product->getProduct($product['product_id']);
      $categories = $this->model_catalog_product->getCategories($product['product_id']);
        
      if ($product_info['image']) {
        $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
      } else {
        $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
      }
      $rate = ($product['tax']*100)/$product['price'];
      $product = array(
        'name' => $product['name'],
        'description' => $product['name'],
        'gross_amount' => strval($this->getTillitRoundAmount($this->currency->format(($product['price']+$product['tax'])*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false))),
        'net_amount' => strval($this->getTillitRoundAmount($this->currency->format($product['total'], $order_info['currency_code'], $order_info['currency_value'], false))),
        'discount_amount' => '0',
        'tax_amount' => strval($this->getTillitRoundAmount($this->currency->format($product['tax']*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false))),
        'tax_class_name' => 'VAT ' . strval($this->getTillitRoundAmount($rate)) . '%',
        'tax_rate' => strval($this->getTillitRoundAmount($rate)),
        'unit_price' => strval($this->getTillitRoundAmount($this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false))),
        'quantity' => $product['quantity'],
        'quantity_unit' => 'pcs',
        'type' => 'PHYSICAL',
      );
      $items[] = $product;
    }

    $totals = $this->model_checkout_order->getOrderTotals($order_id);

    foreach ($totals as $total) {
      if ($total['code'] == 'shipping') {
        $array = array(
          'name' => 'Shipping',
          'description' => $total['title'],
          'gross_amount' => strval($this->getTillitRoundAmount($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false))),
          'net_amount' => strval($this->getTillitRoundAmount($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false))),
          'discount_amount' => '0',
          'tax_amount' => '0',
          'tax_class_name' => '',
          'tax_rate' => '0',
          'unit_price' => strval($this->getTillitRoundAmount($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false))),
          'quantity' => 1,
          'quantity_unit' => 'pcs', // shipment charge
          'image_url' => '',
          'product_page_url' => '',
          'type' => 'SHIPPING'
        );

        $items[] = $array;
      }
      if ($total['code'] == 'coupon') {
        $array = array(
          'name' => 'Discount',
          'description' => $total['title'],
          'gross_amount' => strval($this->getTillitRoundAmount($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false))),
          'net_amount' => strval($this->getTillitRoundAmount($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false))),
          'discount_amount' => '0.00',
          'tax_amount' => '0',
          'tax_class_name' => '',
          'tax_rate' => '0%',
          'unit_price' => strval($this->getTillitRoundAmount($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'], false))),
          'quantity' => 1,
          'quantity_unit' => $total['code'], // shipment charge
          'image_url' => '',
          'product_page_url' => '',
          'type' => 'DISCOUNT'
        );

        $items[] = $array;
      }
    }
    return $items;
  }

  public function getTillitRoundAmount($amount)
  {
    return number_format($amount, 2, '.', '');
  }

  public function getTillitIntentOrderData($order_id){

    $this->load->model('checkout/order');
    $order_info = $this->model_checkout_order->getOrder($order_id);

    $this->load->model('localisation/country');
    $country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);

    if(isset($this->session->data['payment_address']['company_id']) && $this->session->data['payment_address']['company_id']){
      $company_id = $this->session->data['payment_address']['company_id'];
    } else {
      $this->load->model('extension/payment/tillit');
      $company_info = $this->model_extension_payment_tillit->getAddressByCompany($order_info['payment_company']);
      $company_id = $company_info['company_id'];
    }
  
    $request_data = array(
        'gross_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
        'buyer' => array(
          'company' => array(
            'company_name' => $order_info['payment_company'],
            'country_prefix' => $country_info['iso_code_2'],
            'organization_number' => $company_id,
            'website' => '',
          ),
          'representative' => array(
            'email' => $order_info['email'],
            'first_name' => $order_info['firstname'],
            'last_name' => $order_info['lastname'],
            'phone_number' => $order_info['telephone'],
          ),
        ),
        'currency' => $order_info['currency_code'],
        'merchant_short_name' => $this->config->get('payment_tillit_merchant_id'),
        'invoice_type' => $this->config->get('payment_tillit_choose_product'),
        'line_items' => array(
          array(
            'name' => 'Cart',
            'description' => '',
            'gross_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
            'net_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
            'discount_amount' => '0',
            'tax_amount' => '0',
            'tax_class_name' => 'VAT 0 %',
            'tax_rate' => '0.00',
            'unit_price' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
            'quantity' => 1,
            'quantity_unit' => 'item',
            'image_url' => '',
            'product_page_url' => '',
            'type' => 'PHYSICAL',
            'details' => array(
              'brand' => '',
              'categories' => [],
              'barcodes' => [],
            ),
          )
        ),
    );

    return $request_data;
  }

  public function setTillitPaymentRequest($endpoint, $payload = [], $method = 'POST'){

    if($this->config->get('payment_tillit_mode')){
      $base_url = 'https://api.tillit.ai';
    } else {
      $base_url = 'https://test.api.tillit.ai';
    }

    if (strpos($_SERVER['SERVER_NAME'], 'tillit.ai') !== false) {
      $base_url = $this->config->get('payment_tillit_staging_server');
    }

    if ($method == "POST" || $method == "PUT") {
      $url = $base_url.$endpoint;
      $url = $url . '?client=OC&client_v=1.0';
      $params = empty($payload) ? '' : json_encode($payload);
      $headers = [
        'Content-Type: application/json; charset=utf-8',
        'X-API-Key:' . $this->config->get('payment_tillit_api_key'),
      ];
      if ($this->config->get('payment_tillit_debug')) {
        $this->log->write('TILLIT REQUEST :: IPN URL: ' . $url);
        $this->log->write('TILLIT REQUEST :: IPN PAYLOAD: ' . $params);
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      $response = curl_exec($ch);
      curl_getinfo($ch);
      curl_close($ch);
    } else {
      $url = $base_url.$endpoint;
      $url = $url . '?client=OC&client_v=1.0';
      $headers = [
        'Content-Type: application/json; charset=utf-8',
        'X-API-Key:' . $this->config->get('payment_tillit_api_key'),
      ];

      if ($this->config->get('payment_tillit_debug')) {
        $this->log->write('TILLIT REQUEST :: IPN URL: ' . $url);
      }

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      $response = curl_exec($ch);
      
      curl_getinfo($ch);
      curl_close($ch);
    }
    if ($this->config->get('payment_tillit_debug')) {
      $this->log->write('TILLIT RESPONSE :: ' . $response);
    }
    $response = json_decode($response, true);
    return $response;
  }

  public function checkTillitStartsWithString($string, $startString)
  {
    $len = strlen($startString);
    return substr($string, 0, $len) === $startString;
  }

  public function getTillitErrorMessage($body)
  {
    if (!$body) {
      return 'Something went wrong please contact store owner.';
    }

    if (isset($body['response']['code']) && $body['response'] && $body['response']['code'] && $body['response']['code'] >= 400) {
      return sprintf($this->l('Tillit response code %d'), $body['response']['code']);
    }
      
    if (is_string($body)) {
      return $body;
    }
      
    if(isset($body['error_details']) && $body['error_details']) {
      return $body['error_details'];
    }
    
    if (isset($body['error_code']) && $body['error_code']) {
      return $body['error_message'];
    }
  }

  public function confirm() {		
		if (isset($this->request->get['order_id'])) {
      $order_id = $this->request->get['order_id'];
    } elseif(isset($this->session->data['order_id'])) {
      $order_id = $this->session->data['order_id'];
    } else {
      $order_id = 0 ;
    }
  
    $this->load->model('checkout/order');
    $order_info = $this->model_checkout_order->getOrder($order_id);

    if($order_info){
      $this->load->model('extension/payment/tillit');
      $tillit_order_info = $this->model_extension_payment_tillit->getTillitOrderPaymentData($order_id);
      if($tillit_order_info && isset($tillit_order_info['id'])){
        $response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
        $tillit_err = $this->getTillitErrorMessage($response);
        if ($tillit_err) {
          $this->response->redirect($this->url->link('checkout/failure'));
          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_tillit_failed_status_id'));
        }

        if (isset($response['state']) && $response['state'] == 'VERIFIED') {
          $payment_data = array(
            'id' => $response['id'],
            'merchant_reference' => $response['merchant_reference'],
            'state' => $response['state'],
            'status' => $response['status'],
            'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
            'invoice_url' => $response['invoice_url'],
          );
          $this->model_extension_payment_tillit->setTillitOrderPaymentData($order_id, $payment_data);
          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_tillit_verified_status_id'));
          $this->response->redirect($this->url->link('checkout/success'));
        } elseif (isset($response['state']) && $response['state'] == 'UNVERIFIED') {
          $payment_data = array(
            'id' => $response['id'],
            'merchant_reference' => $response['merchant_reference'],
            'state' => $response['state'],
            'status' => $response['status'],
            'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
            'invoice_url' => $response['invoice_url'],
          );
          $this->model_extension_payment_tillit->setTillitOrderPaymentData($order_id, $payment_data); 
          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_tillit_unverified_status_id'));
          $this->response->redirect($this->url->link('checkout/success'));
        } else {
          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_tillit_failed_status_id'));
          $this->response->redirect($this->url->link('checkout/failure'));
        }
      } else {
        $this->session->data['error'] = 'Unable to find the requested order, please try again.';
        $this->response->redirect($this->url->link('checkout/checkout'));
      }
    } else {
      $this->session->data['error'] = 'Unable to find the requested order, please contact store owner.';
    }
	}

  public function company() {

    $this->load->language('extension/payment/tillit');

    $json = array();

    if (isset($this->request->get['country_id']) && $this->request->get['country_id']) {
      $country_id = $this->request->get['country_id'];
    } else {
      $country_id = 0;
    }

    if (isset($this->request->get['company']) && $this->request->get['company']) {
      $company = $this->request->get['company'];
    } else {
      $company = false;
    }

    $countryList = array(222,160);

    if ($company && in_array($country_id, $countryList)) {
      $base_url = '';
      if($country_id == 222){
        $base_url = 'https://gb.search.tillit.ai/';
      }
      if($country_id == 160){
        $base_url = 'https://no.search.tillit.ai/';
      }
      
      $params = array(
        'limit' => 50,
        'offset' => 0,
        'q' => $company,
      );
      $url = $base_url.'search?'.http_build_query($params);

      $headers = [
        'Content-Type: application/json; charset=utf-8',
      ];

      if ($this->config->get('payment_tillit_debug')) {
        $this->log->write('TILLIT REQUEST :: IPN URL: ' . $url);
      }

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      $response = curl_exec($ch);
      
      curl_getinfo($ch);
      curl_close($ch);
      $result = json_decode($response, true);
      if(isset($result['data'])){
        $json['success'] = 'success';
        $json['data'] = $result['data'];
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function address() {

    $this->load->language('extension/payment/tillit');

    $json = array();

    if (isset($this->request->get['company_id']) && $this->request->get['company_id']) {
      $company_id = $this->request->get['company_id'];
    } else {
      $company_id = false;
    }


    if ($company_id) {
      $base_url = '';
      if($this->config->get('payment_tillit_mode')){
        $base_url = 'https://api.tillit.ai';
      } else {
        $base_url = 'https://test.api.tillit.ai';
      }

      if (strpos($_SERVER['SERVER_NAME'], 'tillit.ai') !== false) {
        $base_url = $this->config->get('payment_tillit_staging_server');
      }
      
      $url = $base_url.'/v1/company/'.$company_id.'/address';

      $headers = [
        'Content-Type: application/json; charset=utf-8',
      ];

      if ($this->config->get('payment_tillit_debug')) {
        $this->log->write('TILLIT REQUEST :: IPN URL: ' . $url);
      }

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 60);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      $response = curl_exec($ch);
      
      curl_getinfo($ch);
      curl_close($ch);
      $result = json_decode($response, true);
      $json = $result;

    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
    
}
