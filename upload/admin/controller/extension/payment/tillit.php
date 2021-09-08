<?php
class ControllerExtensionPaymentTillit extends Controller {
	private $error = array();

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


		if (isset($this->request->post['payment_tillit_canceled_reversal_status_id'])) {
			$data['payment_tillit_canceled_reversal_status_id'] = $this->request->post['payment_tillit_canceled_reversal_status_id'];
		} else {
			$data['payment_tillit_canceled_reversal_status_id'] = $this->config->get('payment_tillit_canceled_reversal_status_id');
		}

		if (isset($this->request->post['payment_tillit_completed_status_id'])) {
			$data['payment_tillit_completed_status_id'] = $this->request->post['payment_tillit_completed_status_id'];
		} else {
			$data['payment_tillit_completed_status_id'] = $this->config->get('payment_tillit_completed_status_id');
		}

		if (isset($this->request->post['payment_tillit_denied_status_id'])) {
			$data['payment_tillit_denied_status_id'] = $this->request->post['payment_tillit_denied_status_id'];
		} else {
			$data['payment_tillit_denied_status_id'] = $this->config->get('payment_tillit_denied_status_id');
		}

		if (isset($this->request->post['payment_tillit_expired_status_id'])) {
			$data['payment_tillit_expired_status_id'] = $this->request->post['payment_tillit_expired_status_id'];
		} else {
			$data['payment_tillit_expired_status_id'] = $this->config->get('payment_tillit_expired_status_id');
		}

		if (isset($this->request->post['payment_tillit_failed_status_id'])) {
			$data['payment_tillit_failed_status_id'] = $this->request->post['payment_tillit_failed_status_id'];
		} else {
			$data['payment_tillit_failed_status_id'] = $this->config->get('payment_tillit_failed_status_id');
		}

		if (isset($this->request->post['payment_tillit_pending_status_id'])) {
			$data['payment_tillit_pending_status_id'] = $this->request->post['payment_tillit_pending_status_id'];
		} else {
			$data['payment_tillit_pending_status_id'] = $this->config->get('payment_tillit_pending_status_id');
		}

		if (isset($this->request->post['payment_tillit_processed_status_id'])) {
			$data['payment_tillit_processed_status_id'] = $this->request->post['payment_tillit_processed_status_id'];
		} else {
			$data['payment_tillit_processed_status_id'] = $this->config->get('payment_tillit_processed_status_id');
		}

		if (isset($this->request->post['payment_tillit_refunded_status_id'])) {
			$data['payment_tillit_refunded_status_id'] = $this->request->post['payment_tillit_refunded_status_id'];
		} else {
			$data['payment_tillit_refunded_status_id'] = $this->config->get('payment_tillit_refunded_status_id');
		}

		if (isset($this->request->post['payment_tillit_reversed_status_id'])) {
			$data['payment_tillit_reversed_status_id'] = $this->request->post['payment_tillit_reversed_status_id'];
		} else {
			$data['payment_tillit_reversed_status_id'] = $this->config->get('payment_tillit_reversed_status_id');
		}

		if (isset($this->request->post['payment_tillit_voided_status_id'])) {
			$data['payment_tillit_voided_status_id'] = $this->request->post['payment_tillit_voided_status_id'];
		} else {
			$data['payment_tillit_voided_status_id'] = $this->config->get('payment_tillit_voided_status_id');
		}
		
		
		if (isset($this->request->post['payment_tillit_order_status_id'])) {
			$data['payment_tillit_order_status_id'] = $this->request->post['payment_tillit_order_status_id'];
		} else {
			$data['payment_tillit_order_status_id'] = $this->config->get('payment_tillit_order_status_id');
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
		} else {
			$data['payment_tillit_status'] = $this->config->get('payment_tillit_status');
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
}