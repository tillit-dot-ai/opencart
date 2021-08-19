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
		
		if (isset($this->error['payment_invoice_days'])) {
			$data['error_invoice_days'] = $this->error['payment_invoice_days'];
		} else {
			$data['error_invoice_days'] = '';
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
		} else if( !empty($this->config->get('payment_tillit_title'))) {
			$data['payment_tillit_title'] = $this->config->get('payment_tillit_title');
		} else {
			$data['payment_tillit_title'] = 'Business invoice 30 days';
		}
		
		if (isset($this->request->post['payment_tillit_sub_title'])) {
			$data['payment_tillit_sub_title'] = $this->request->post['payment_tillit_sub_title'];
		} else if( !empty($this->config->get('payment_tillit_sub_title'))) {
			$data['payment_tillit_sub_title'] = $this->config->get('payment_tillit_sub_title');
		} else {
			$data['payment_tillit_sub_title'] = 'Receive the invoice via EHF and PDF';
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

		if (isset($this->request->post['payment_tillit_logo'])) {
			$data['payment_tillit_logo'] = $this->request->post['payment_tillit_logo'];
		} else {
			$data['payment_tillit_logo'] = $this->config->get('payment_tillit_logo');
		}
		
		if (isset($this->request->post['payment_tillit_choose_product'])) {
			$data['payment_tillit_choose_product'] = $this->request->post['payment_tillit_choose_product'];
		} else {
			$data['payment_tillit_choose_product'] = $this->config->get('payment_tillit_choose_product');
		}
		
		if (isset($this->request->post['payment_tillit_invoice_days'])) {
			$data['payment_tillit_invoice_days'] = $this->request->post['payment_tillit_invoice_days'];
		} else if( !empty($this->config->get('payment_tillit_invoice_days'))) {
			$data['payment_tillit_invoice_days'] = $this->config->get('payment_tillit_invoice_days');
		} else {
			$data['payment_tillit_invoice_days'] = 14;
		}
		
		if (isset($this->request->post['payment_tillit_payment_mode'])) {
			$data['payment_tillit_payment_mode'] = $this->request->post['payment_tillit_payment_mode'];
		} else {
			$data['payment_tillit_payment_mode'] = $this->config->get('payment_tillit_payment_mode');
		}
		
		if (isset($this->request->post['payment_tillit_company_name'])) {
			$data['payment_tillit_company_name'] = $this->request->post['payment_tillit_company_name'];
		} else {
			$data['payment_tillit_company_name'] = $this->config->get('payment_tillit_company_name');
		}
		
		if (isset($this->request->post['payment_tillit_company_org'])) {
			$data['payment_tillit_company_org'] = $this->request->post['payment_tillit_company_org'];
		} else {
			$data['payment_tillit_company_org'] = $this->config->get('payment_tillit_company_org');
		}
		
		if (isset($this->request->post['payment_tillit_purchased_order_shipped'])) {
			$data['payment_tillit_purchased_order_shipped'] = $this->request->post['payment_tillit_purchased_order_shipped'];
		} else {
			$data['payment_tillit_purchased_order_shipped'] = $this->config->get('payment_tillit_purchased_order_shipped');
		}
		
		if (isset($this->request->post['payment_tillit_pre_approve_checkout'])) {
			$data['payment_tillit_pre_approve_checkout'] = $this->request->post['payment_tillit_pre_approve_checkout'];
		} else {
			$data['payment_tillit_pre_approve_checkout'] = $this->config->get('payment_tillit_pre_approve_checkout');
		}
		
		if (isset($this->request->post['payment_tillit_initiate_payment_refund'])) {
			$data['payment_tillit_initiate_payment_refund'] = $this->request->post['payment_tillit_initiate_payment_refund'];
		} else {
			$data['payment_tillit_initiate_payment_refund'] = $this->config->get('payment_tillit_initiate_payment_refund');
		}
		
		if (isset($this->request->post['payment_tillit_status_order_unverify'])) {
			$data['payment_tillit_status_order_unverify'] = $this->request->post['payment_tillit_status_order_unverify'];
		} else {
			$data['payment_tillit_status_order_unverify'] = $this->config->get('payment_tillit_status_order_unverify');
		}
		
		if (isset($this->request->post['payment_tillit_status_order_verify'])) {
			$data['payment_tillit_status_order_verify'] = $this->request->post['payment_tillit_status_order_verify'];
		} else {
			$data['payment_tillit_status_order_verify'] = $this->config->get('payment_tillit_status_order_verify');
		}
		
		if (isset($this->request->post['payment_tillit_status_order_shipped'])) {
			$data['payment_tillit_status_order_shipped'] = $this->request->post['payment_tillit_status_order_shipped'];
		} else {
			$data['payment_tillit_status_order_shipped'] = $this->config->get('payment_tillit_status_order_shipped');
		}
		
		if (isset($this->request->post['payment_tillit_status_order_delivered'])) {
			$data['payment_tillit_status_order_delivered'] = $this->request->post['payment_tillit_status_order_delivered'];
		} else {
			$data['payment_tillit_status_order_delivered'] = $this->config->get('payment_tillit_status_order_delivered');
		}
		
		
		if (isset($this->request->post['payment_tillit_status_order_canceled'])) {
			$data['payment_tillit_status_order_canceled'] = $this->request->post['payment_tillit_status_order_canceled'];
		} else {
			$data['payment_tillit_status_order_canceled'] = $this->config->get('payment_tillit_status_order_canceled');
		}
		
		
		if (isset($this->request->post['payment_tillit_status_order_refunded'])) {
			$data['payment_tillit_status_order_refunded'] = $this->request->post['payment_tillit_status_order_refunded'];
		} else {
			$data['payment_tillit_status_order_refunded'] = $this->config->get('payment_tillit_status_order_refunded');
		}
	
	//=============================================================================	
		if (isset($this->request->post['payment_tillit_total'])) {
			$data['payment_tillit_total'] = $this->request->post['payment_tillit_total'];
		} else {
			$data['payment_tillit_total'] = $this->config->get('payment_tillit_total');
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
	//=============================================================================

/* 
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

	 */
		
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

	/* 	if (!$this->request->post['payment_tillit_email']) {
			$this->error['email'] = $this->language->get('error_email');
		}
    */
 
   if (!$this->request->post['payment_tillit_title']) {
			$this->error['payment_title'] = $this->language->get('error_title');
		}
		
   if (!$this->request->post['payment_tillit_sub_title']) {
			$this->error['payment_sub_title'] = $this->language->get('error_sub_title');
		}
		
   if (!$this->request->post['payment_tillit_merchant_id']) {
			$this->error['payment_merchant_id'] = $this->language->get('error_merchant_id');
		}
		
  if (!$this->request->post['payment_tillit_api_key']) {
			$this->error['payment_api_key'] = $this->language->get('error_api_key');
		}

	/*if (!$this->request->post['payment_tillit_logo']) {
			$this->error['payment_logo'] = $this->language->get('error_logo');
		}*/
		
  if (!$this->request->post['payment_tillit_invoice_days']) {
			$this->error['payment_invoice_days'] = $this->language->get('error_invoice_days');
		}
 
		return !$this->error;
	}
}