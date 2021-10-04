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

		if (isset($this->request->post['payment_tillit_sort_order'])) {
			$data['payment_tillit_sort_order'] = $this->request->post['payment_tillit_sort_order'];
		} else {
			$data['payment_tillit_sort_order'] = $this->config->get('payment_tillit_sort_order');
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

		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if (isset($this->request->get['order_status_id'])) {
			$order_status_id = (int)$this->request->get['order_status_id'];
		} else {
			$order_status_id = 0;
		}
		
		$this->load->model('extension/payment/tillit');
        $tillit_order_info = $this->model_extension_payment_tillit->getTillitOrderPaymentData($order_id);

        if($tillit_order_info && isset($tillit_order_info['id'])){
			if($order_status_id == $this->config->get('payment_tillit_canceled_status_id')){
				$response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/cancel', [], 'POST');
				if (!isset($response)) {
					$message = sprintf('Could not update status to CANCELLED, please check with Tillit admin for id %s', $tillit_order_info['id']);
				}

	            $response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
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
                    $message = 'Order updated to CANCELLED status in Tillit dashboard.';
                }
			} elseif($order_status_id == $this->config->get('payment_tillit_shipped_status_id')){
				$response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/fulfilled', [], 'POST');
				if (!isset($response)) {
	                $message = sprintf('Could not update status to FULFILLED, please check with Tillit admin for id %s', $tillit_order_info['id']);
	            }

	            $response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
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
                    $message = 'Order updated to FULFILLED status in Tillit dashboard.';
                }
	            
			} elseif($order_status_id == $this->config->get('payment_tillit_delivered_status_id')){
				$response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'] . '/delivered', [], 'POST');
				if (!isset($response)) {
	                $message = sprintf('Could not update status to DELIVERED, please check with Tillit admin for id %s', $tillit_order_info['id']);
	            }
	            $response = $this->module->setTillitPaymentRequest('/v1/order/' . $tillit_order_info['id'], [], 'GET');
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
                    $message = 'Order updated to DELIVERED status in Tillit dashboard.';
                }
			}

		}

		$this->response->setOutput($message);
	}
}