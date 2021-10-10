<?php
class ControllerExtensionPaymentTillit extends Controller {
	private $error = array();
	public function install() {

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tillit_order` (
		 `tillit_order_id` int(11) NOT NULL AUTO_INCREMENT,
		 `order_id` int(11) NOT NULL,
		 `id` varchar(128) NOT NULL,
		 `merchant_reference` varchar(128) NOT NULL,
		 `state` varchar(128) NOT NULL,
		 `status` varchar(128) NOT NULL,
		 `day_on_invoice` varchar(32) NOT NULL,
		 `invoice_url` text NOT NULL,
		 PRIMARY KEY (`tillit_order_id`),
		 KEY `customer_id` (`order_id`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");

		$customer_col = $this->db->query("DESCRIBE `" . DB_PREFIX . "customer`");
		$fileds = array();
        foreach ($customer_col->rows as $column) {
            $fileds[] = $column['Field'];
        }
        if (!in_array('account_type', $fileds)) {
        	$this->db->query("ALTER TABLE `" . DB_PREFIX . "customer` ADD `account_type` VARCHAR(32) NOT NULL AFTER `date_added`");
        }

        $address_col = $this->db->query("DESCRIBE `" . DB_PREFIX . "address`");
		$fileds = array();
        foreach ($address_col->rows as $column) {
            $fileds[] = $column['Field'];
        }
        if (!in_array('company_id', $fileds)) {
        	$this->db->query("ALTER TABLE `" . DB_PREFIX . "address` ADD `company_id` VARCHAR(32) NOT NULL AFTER `company`");
        }
		
	}

	public function uninstall() {
		$this->db->query("DROP TABLE `" . DB_PREFIX . "tillit_order`");
	}

	public function index() {
		$this->load->language('extension/payment/tillit');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			
			$this->model_setting_setting->editSetting('payment_tillit', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['payment_title'])) {
			$data['error_title'] = $this->error['payment_title'];
		} else {
			$data['error_title'] = '';
		}
		if (isset($this->error['payment_sub_title'])) {
			$data['error_sub_title'] = $this->error['payment_sub_title'];
		} else {
			$data['error_sub_title'] = '';
		}
		if (isset($this->error['payment_merchant_id'])) {
			$data['error_merchant_id'] = $this->error['payment_merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}
		if (isset($this->error['payment_api_key'])) {
			$data['error_api_key'] = $this->error['payment_api_key'];
		} else {
			$data['error_api_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/tillit', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/tillit', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


		if (isset($this->request->post['payment_tillit_title'])) {
			$data['payment_tillit_title'] = $this->request->post['payment_tillit_title'];
		} elseif ($this->config->get('payment_tillit_title')) {
			$data['payment_tillit_title'] = $this->config->get('payment_tillit_title');
		} else {
			$data['payment_tillit_title'] = 'Business invoice %s days';
		}
		if (isset($this->request->post['payment_tillit_sub_title'])) {
			$data['payment_tillit_sub_title'] = $this->request->post['payment_tillit_sub_title'];
		} elseif ($this->config->get('payment_tillit_sub_title')) {
			$data['payment_tillit_sub_title'] = $this->config->get('payment_tillit_sub_title');
		} else {
			$data['payment_tillit_sub_title'] = 'Receive the invoice via EHF and email';
		}
		
		if (isset($this->request->post['payment_tillit_merchant_id'])) {
			$data['payment_tillit_merchant_id'] = $this->request->post['payment_tillit_merchant_id'];
		} else {
			$data['payment_tillit_merchant_id'] = $this->config->get('payment_tillit_merchant_id');
		}
		
		if (isset($this->request->post['payment_tillit_api_key'])) {
			$data['payment_tillit_api_key'] = $this->request->post['payment_tillit_api_key'];
		} else {
			$data['payment_tillit_api_key'] = $this->config->get('payment_tillit_api_key');
		}
		if (isset($this->request->post['payment_tillit_choose_product'])) {
			$data['payment_tillit_choose_product'] = $this->request->post['payment_tillit_choose_product'];
		} else {
			$data['payment_tillit_choose_product'] = $this->config->get('payment_tillit_choose_product');
		}
		if (isset($this->request->post['payment_tillit_invoice_days'])) {
			$data['payment_tillit_invoice_days'] = $this->request->post['payment_tillit_invoice_days'];
		} elseif ($this->config->get('payment_tillit_invoice_days')) {
			$data['payment_tillit_invoice_days'] = $this->config->get('payment_tillit_invoice_days');
		} else {
			$data['payment_tillit_invoice_days'] = 14;
		}
		
		if (isset($this->request->post['payment_tillit_total'])) {
			$data['payment_tillit_total'] = $this->request->post['payment_tillit_total'];
		} elseif ($this->config->get('payment_tillit_total')) {
			$data['payment_tillit_total'] = $this->config->get('payment_tillit_total');
		} else {
			$data['payment_tillit_total'] = 0.01;
		}


		if (isset($this->request->post['payment_tillit_verified_status_id'])) {
			$data['payment_tillit_verified_status_id'] = $this->request->post['payment_tillit_verified_status_id'];
		} elseif($this->config->get('payment_tillit_verified_status_id')) {
			$data['payment_tillit_verified_status_id'] = $this->config->get('payment_tillit_verified_status_id');
		} else {
			$data['payment_tillit_verified_status_id'] = 2;
		}

		if (isset($this->request->post['payment_tillit_unverified_status_id'])) {
			$data['payment_tillit_unverified_status_id'] = $this->request->post['payment_tillit_unverified_status_id'];
		} elseif($this->config->get('payment_tillit_unverified_status_id')) {
			$data['payment_tillit_unverified_status_id'] = $this->config->get('payment_tillit_unverified_status_id');
		} else {
			$data['payment_tillit_unverified_status_id'] = 1;
		}

		if (isset($this->request->post['payment_tillit_failed_status_id'])) {
			$data['payment_tillit_failed_status_id'] = $this->request->post['payment_tillit_failed_status_id'];
		} elseif($this->config->get('payment_tillit_failed_status_id')) {
			$data['payment_tillit_failed_status_id'] = $this->config->get('payment_tillit_failed_status_id');
		} else {
			$data['payment_tillit_failed_status_id'] = 10;
		}

		if (isset($this->request->post['payment_tillit_canceled_status_id'])) {
			$data['payment_tillit_canceled_status_id'] = $this->request->post['payment_tillit_canceled_status_id'];
		} elseif($this->config->get('payment_tillit_canceled_status_id')) {
			$data['payment_tillit_canceled_status_id'] = $this->config->get('payment_tillit_canceled_status_id');
		} else {
			$data['payment_tillit_canceled_status_id'] = 7;
		}

		if (isset($this->request->post['payment_tillit_refunded_status_id'])) {
			$data['payment_tillit_refunded_status_id'] = $this->request->post['payment_tillit_refunded_status_id'];
		} elseif($this->config->get('payment_tillit_refunded_status_id')) {
			$data['payment_tillit_refunded_status_id'] = $this->config->get('payment_tillit_refunded_status_id');
		} else {
			$data['payment_tillit_refunded_status_id'] = 11;
		}

		if (isset($this->request->post['payment_tillit_shipped_status_id'])) {
			$data['payment_tillit_shipped_status_id'] = $this->request->post['payment_tillit_shipped_status_id'];
		} elseif($this->config->get('payment_tillit_shipped_status_id')) {
			$data['payment_tillit_shipped_status_id'] = $this->config->get('payment_tillit_shipped_status_id');
		} else {
			$data['payment_tillit_shipped_status_id'] = 3;
		}

		if (isset($this->request->post['payment_tillit_delivered_status_id'])) {
			$data['payment_tillit_delivered_status_id'] = $this->request->post['payment_tillit_delivered_status_id'];
		} elseif($this->config->get('payment_tillit_delivered_status_id')) {
			$data['payment_tillit_delivered_status_id'] = $this->config->get('payment_tillit_delivered_status_id');
		} else {
			$data['payment_tillit_delivered_status_id'] = 5;
		}
		
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_tillit_geo_zone_id'])) {
			$data['payment_tillit_geo_zone_id'] = $this->request->post['payment_tillit_geo_zone_id'];
		} else {
			$data['payment_tillit_geo_zone_id'] = $this->config->get('payment_tillit_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_tillit_status'])) {
			$data['payment_tillit_status'] = $this->request->post['payment_tillit_status'];
		} elseif($this->config->get('payment_tillit_status')) {
			$data['payment_tillit_status'] = $this->config->get('payment_tillit_status');
		}else{
			$data['payment_tillit_status'] = 1;
		}

		if (isset($this->request->post['payment_tillit_debug'])) {
			$data['payment_tillit_debug'] = $this->request->post['payment_tillit_debug'];
		} else {
			$data['payment_tillit_debug'] = $this->config->get('payment_tillit_debug');
		}

		if (isset($this->request->post['payment_tillit_mode'])) {
			$data['payment_tillit_mode'] = $this->request->post['payment_tillit_mode'];
		} else {
			$data['payment_tillit_mode'] = $this->config->get('payment_tillit_mode');
		}

		if (isset($this->request->post['payment_tillit_sort_order'])) {
			$data['payment_tillit_sort_order'] = $this->request->post['payment_tillit_sort_order'];
		} else {
			$data['payment_tillit_sort_order'] = $this->config->get('payment_tillit_sort_order');
		}
		
		$data['tillit_host'] = false;

		if (strpos($_SERVER['SERVER_NAME'], 'tillit.ai') !== false) {
		    $data['tillit_host'] = true;
		}

		if (isset($this->request->post['payment_tillit_staging_server'])) {
			$data['payment_tillit_staging_server'] = $this->request->post['payment_tillit_staging_server'];
		} elseif($this->config->get('payment_tillit_staging_server')) {
			$data['payment_tillit_staging_server'] = $this->config->get('payment_tillit_staging_server');
		} else {
			$data['payment_tillit_staging_server'] = 'https://staging.tillit.ai';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/tillit', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/tillit')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		return !$this->error;
	}

	public function history() {
		$this->load->language('extension/payment/tillit');

		$this->load->model('extension/payment/tillit');

		$data['tillit'] = $this->model_extension_payment_tillit->getTillitOrderPaymentData($this->request->get['order_id']);

		$this->response->setOutput($this->load->view('sale/order_tillit', $data));
	}

	public function updateOrder() {
		$json = array();
		$this->load->language('extension/payment/tillit');

		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if (isset($this->request->post['order_status_id'])) {
			$order_status_id = (int)$this->request->post['order_status_id'];
		} else {
			$order_status_id = 0;
		}
		
		$this->load->model('extension/payment/tillit');
        $tillit_order_info = $this->model_extension_payment_tillit->getTillitOrderPaymentData($order_id);

        if($tillit_order_info && isset($tillit_order_info['id'])){
			if($order_status_id == $this->config->get('payment_tillit_canceled_status_id')){
				$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/cancel', [], 'POST');
				if (!isset($response)) {
	                $json['error'] = sprintf($this->language->get('text_response_error'), 'Something went wrong, Please check with Tillit dashboard for id '.$tillit_order_info['id']);
	            } elseif (isset($response['error_message']) ) {
	            	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	            } else {
	            	$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
	                if (isset($response['state']) && $response['state'] == 'CANCELLED') {
	                    $payment_data = array(
	                        'id' => $response['id'],
			                'merchant_reference' => $response['merchant_reference'],
			                'state' => $response['state'],
			                'status' => $response['status'],
			                'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
			                'invoice_url' => $response['invoice_url'],
	                    );
	                    $this->model_extension_payment_tillit->setTillitOrderPaymentData($order_id, $payment_data);
	                    $json['success'] = sprintf($this->language->get('text_response_success'),$response['state']);
	                } elseif (isset($response['error_message'])) {
	                	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	                }
	            }
	            
			} elseif($order_status_id == $this->config->get('payment_tillit_shipped_status_id')){
				$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/fulfilled', [], 'POST');
				if (!isset($response)) {
	                $json['error'] = sprintf($this->language->get('text_response_error'), 'Something went wrong, Please check with Tillit dashboard for id '.$tillit_order_info['id']);
	            } elseif (isset($response['error_message']) ) {
	            	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	            } else {
	            	$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
	                if (isset($response['state']) && $response['state'] == 'FULFILLED') {
	                    $payment_data = array(
	                        'id' => $response['id'],
			                'merchant_reference' => $response['merchant_reference'],
			                'state' => $response['state'],
			                'status' => $response['status'],
			                'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
			                'invoice_url' => $response['invoice_url'],
	                    );
	                    $this->model_extension_payment_tillit->setTillitOrderPaymentData($order_id, $payment_data);
	                    $json['success'] = sprintf($this->language->get('text_response_success'),$response['state']);
	                } elseif (isset($response['error_message'])) {
	                	$$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	                }
	            }
			} elseif($order_status_id == $this->config->get('payment_tillit_delivered_status_id')){
				$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/delivered', [], 'POST');

				if (!isset($response)) {
	                $json['error'] = sprintf($this->language->get('text_response_error'), 'Something went wrong, Please check with Tillit dashboard for id '.$tillit_order_info['id']);
	            } elseif (isset($response['error_message']) ) {
	            	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	            } else {
		            $response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
	                if (isset($response['state']) && $response['state'] == 'DELIVERED') {
	                    $payment_data = array(
	                        'id' => $response['id'],
			                'merchant_reference' => $response['merchant_reference'],
			                'state' => $response['state'],
			                'status' => $response['status'],
			                'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
			                'invoice_url' => $response['invoice_url'],
	                    );
	                    $this->model_extension_payment_tillit->setTillitOrderPaymentData($order_id, $payment_data);
	                    $json['success'] = sprintf($this->language->get('text_response_success'),$response['state']);
	                } elseif (isset($response['error_message'])) {
	                	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	                }
	            }
			} elseif($order_status_id == $this->config->get('payment_tillit_refunded_status_id')){
				$this->load->model('sale/order');
				$this->load->model('catalog/product');
				$this->load->model('tool/image');

				$order_info = $this->model_sale_order->getOrder($order_id);
				if($order_info){
					$items = [];
    
					$order_product = $this->model_sale_order->getOrderProducts($order_id);

					foreach ($order_product as $product) {
						$product_info = $this->model_catalog_product->getProduct($product['product_id']);

						if ($product_info['image']) {
							$image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
						}

						$rate = ($product['tax']*100)/$product['price'];
						$product = array(
							'name' => $product['name'],
							'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, 100),
							'gross_amount' => strval($this->currency->format(($product['price']+$product['tax'])*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false)),
							'net_amount' => strval($this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false)),
							'discount_amount' => '0.00',
							'tax_amount' => strval($this->currency->format($product['tax']*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false)),
							'tax_class_name' => 'VAT ' . strval($this->getTillitRoundAmount($rate)) . '%',
							'tax_rate' => strval($this->getTillitRoundAmount($rate)),
							'unit_price' => strval($this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false)),
							'quantity' => $product['quantity'],
							'quantity_unit' => 'pcs',
							'image_url' => $image,
							'product_page_url' => $this->url->link('product/product', 'product_id=' . $product['product_id']),
							'type' => 'PHYSICAL'
						);

						$items[] = $product;
					}

					$totals = $this->model_sale_order->getOrderTotals($order_id);

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
				}

				$request_data = array(
			      'amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
			      'currency' => $order_info['currency_code'],
			      'initiate_payment_to_buyer' => true,
			      'line_items' => $items,
			    );

				$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/refund', $request_data, 'POST');
				//echo "<pre>";print_r($response);die;

				if (!isset($response)) {
	                $json['error'] = sprintf($this->language->get('text_response_error'), 'Something went wrong, Please check with Tillit dashboard for id '.$tillit_order_info['id']);
	            } elseif (isset($response['error_message']) ) {
	            	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	            } else {
		            $response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');

	                if (isset($response['state']) && $response['state'] == 'REFUNDED') {
	                    $payment_data = array(
	                        'id' => $response['id'],
			                'merchant_reference' => $response['merchant_reference'],
			                'state' => $response['state'],
			                'status' => $response['status'],
			                'day_on_invoice' => $this->config->get('payment_tillit_invoice_days'),
			                'invoice_url' => $response['invoice_url'],
	                    );
	                    $this->model_extension_payment_tillit->setTillitOrderPaymentData($order_id, $payment_data);
	                    $json['success'] = sprintf($this->language->get('text_response_success'),$response['state']);
	                } elseif (isset($response['error_message'])) {
	                	$json['error'] = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
	                }
	            }
			}

		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function editOrder() {
		$this->load->language('extension/payment/tillit');

		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$this->load->model('extension/payment/tillit');
        $tillit_order_info = $this->model_extension_payment_tillit->getTillitOrderPaymentData($order_id);
        $message = '';
        if($tillit_order_info && isset($tillit_order_info['id'])){
			$this->load->model('sale/order');
			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			$order_info = $this->model_sale_order->getOrder($order_id);
			if($order_info && $order_info['payment_code'] == 'tillit'){
				$items = [];

				$order_product = $this->model_sale_order->getOrderProducts($order_id);

				foreach ($order_product as $product) {
					$product_info = $this->model_catalog_product->getProduct($product['product_id']);

					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
					}

					$rate = ($product['tax']*100)/$product['price'];
					$product = array(
						'name' => $product['name'],
						'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, 100),
						'gross_amount' => strval($this->currency->format(($product['price']+$product['tax'])*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false)),
						'net_amount' => strval($this->currency->format($product['price']*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false)),
						'discount_amount' => '0.00',
						'tax_amount' => strval($this->currency->format($product['tax']*$product['quantity'], $order_info['currency_code'], $order_info['currency_value'], false)),
						'tax_class_name' => 'VAT ' . strval($this->getTillitRoundAmount($rate)) . '%',
						'tax_rate' => strval($this->getTillitRoundAmount($rate)),
						'unit_price' => strval($this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value'], false)),
						'quantity' => $product['quantity'],
						'quantity_unit' => 'pcs',
						'image_url' => $image,
						'product_page_url' => $this->url->link('product/product', 'product_id=' . $product['product_id']),
						'type' => 'PHYSICAL'
					);

					$items[] = $product;
				}

				$totals = $this->model_sale_order->getOrderTotals($order_id);

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
			}
			$order_reference = round(microtime(1) * 1000);
			$this->load->model('localisation/country');
		    $payment_country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);
		    $shipping_country_info = $this->model_localisation_country->getCountry($order_info['shipping_country_id']);
			
			$request_data = array(
		      'gross_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
		      'net_amount' => strval($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)),
		      'currency' => $order_info['currency_code'],
		      'discount_amount' => '0.00',
		      'discount_rate' => '0.00',
		      'invoice_type' => $this->config->get('payment_tillit_choose_product'),
		      'tax_amount' => '0',
		      'tax_rate' => '0.00',
		      'merchant_additional_info' => $order_info['store_name'],
		      'merchant_reference' => strval($order_reference),
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
		      'shipping_details' => array(
		          'carrier_name' => $order_info['shipping_method'],
		          'carrier_tracking_url' => $order_info['store_url'],
		          'expected_delivery_date' => date('Y-m-d', strtotime('+ 7 days')),
		          'tracking_number' => 'track1234567891',
		      ),
		      'recurring' => false,
		      'order_note' => $order_info['comment'],
		      'line_items' => $items,
		    );

			$response = $this->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], $request_data, 'PUT');
			//echo "<pre>";print_r($response);die;
			if (isset($response['error_message']) ) {
            	$message = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
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
                $message = sprintf($this->language->get('text_response_success'),$response['state']);
            } elseif (isset($response['error_message'])) {
            	$message = sprintf($this->language->get('text_response_error'), $response['error_message'].' : '.$response['error_details']);
            }
		}

		$this->response->setOutput($message);
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

	    //$base_url = 'https://staging.api.tillit.ai';

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
	public function getTillitRoundAmount($amount) {
		return number_format($amount, 2, '.', '');
	}
}