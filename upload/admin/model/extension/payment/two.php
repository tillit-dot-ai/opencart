<?php
class ModelExtensionPaymentTwo extends Model {
		
	public function getTwoOrderPaymentData($order_id)
    {
    	$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "two_order WHERE order_id = '" . (int)$order_id . "'");
		
		if ($query->num_rows) {
			return $query->row;
		} else {
			return false;
		}
	}
    
	
	public function setTwoOrderPaymentData($order_id, $payment_data)
    {
         $query = $this->db->query("UPDATE " . DB_PREFIX . "two_order SET id = '" . $this->db->escape($payment_data['id']) . "', merchant_reference = '" . $this->db->escape($payment_data['merchant_reference']) . "', state = '" . $this->db->escape($payment_data['state']) . "', status = '" . $this->db->escape($payment_data['status']) . "', day_on_invoice = '" . $this->db->escape($payment_data['day_on_invoice']) . "', invoice_url = '" . $this->db->escape($payment_data['invoice_url']) . "' WHERE order_id = '" . (int)$order_id . "'");
    }
	
}
