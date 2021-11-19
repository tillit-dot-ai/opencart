<?php
class ModelExtensionPaymentTwo extends Model
{
  public function getMethod($address, $total) {
    $this->load->language('extension/payment/two');

    $query = $this->db->query(
      'SELECT * FROM ' .
        DB_PREFIX .
        "zone_to_geo_zone WHERE geo_zone_id = '" .
        (int) $this->config->get('payment_two_geo_zone_id') .
        "' AND country_id = '" .
        (int) $address['country_id'] .
        "' AND (zone_id = '" .
        (int) $address['zone_id'] .
        "' OR zone_id = '0')"
    );

    if (
      $this->config->get('payment_two_total') > 0 &&
      $this->config->get('payment_two_total') > $total
    ) {
      $status = false;
    } elseif (!$this->config->get('payment_two_geo_zone_id')) {
      $status = true;
    } elseif ($query->num_rows) {
      $status = true;
    } else {
      $status = false;
    }

    $currencies = ['GBP', 'NOK'];

    if (
      !in_array(strtoupper($this->session->data['currency']), $currencies)
    ) {
      $status = false;
    }

    if (
      $this->customer->isLogged() &&
      $this->customer->getAccountType() == 'personal'
    ) {
      $status = false;
    } elseif (
      isset($this->session->data['guest']['account_type']) &&
      $this->session->data['guest']['account_type'] == 'personal'
    ) {
      $status = false;
    }

    $method_data = [];

    if ($status) {
      $method_data = [
        'code' => 'two',
        'title' => sprintf(
          $this->config->get('payment_two_title'),
          $this->config->get('payment_two_invoice_days')
        ),
        'terms' => $this->config->get('payment_two_sub_title'),
        'sort_order' => $this->config->get('payment_two_sort_order'),
      ];
    }

    return $method_data;
  }

  public function setTwoOrderPaymentData($order_id, $payment_data) {
    $result = $this->getTwoOrderPaymentData($order_id);
    if ($result) {
      $query = $this->db->query(
        'UPDATE ' .
          DB_PREFIX .
          "two_order SET id = '" .
          $this->db->escape($payment_data['id']) .
          "', merchant_reference = '" .
          $this->db->escape($payment_data['merchant_reference']) .
          "', state = '" .
          $this->db->escape($payment_data['state']) .
          "', status = '" .
          $this->db->escape($payment_data['status']) .
          "', day_on_invoice = '" .
          $this->db->escape($payment_data['day_on_invoice']) .
          "', invoice_url = '" .
          $this->db->escape($payment_data['invoice_url']) .
          "' WHERE order_id = '" .
          (int) $order_id .
          "'"
      );
    } else {
      $query = $this->db->query(
        'INSERT INTO ' .
          DB_PREFIX .
          "two_order SET order_id = '" .
          (int) $order_id .
          "', id = '" .
          $this->db->escape($payment_data['id']) .
          "', merchant_reference = '" .
          $this->db->escape($payment_data['merchant_reference']) .
          "', state = '" .
          $this->db->escape($payment_data['state']) .
          "', status = '" .
          $this->db->escape($payment_data['status']) .
          "', day_on_invoice = '" .
          $this->db->escape($payment_data['day_on_invoice']) .
          "', invoice_url = '" .
          $this->db->escape($payment_data['invoice_url']) .
          "'"
      );
    }
  }

  public function getTwoOrderPaymentData($order_id) {
    $query = $this->db->query(
      'SELECT DISTINCT * FROM ' .
        DB_PREFIX .
        "two_order WHERE order_id = '" .
        (int) $order_id .
        "'"
    );

    if ($query->num_rows) {
      return $query->row;
    } else {
      return false;
    }
  }
}
