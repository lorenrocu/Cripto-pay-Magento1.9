<?php

class Mage_Criptopay_Model_Source_PaymentAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Criptopay_Model_Config::PAYMENT_TYPE_PAYMENT, 'label' => Mage::helper('criptopay')->__('PAYMENT')),
            array('value' => Mage_Criptopay_Model_Config::PAYMENT_TYPE_DEFERRED, 'label' => Mage::helper('criptopay')->__('DEFERRED')),
            array('value' => Mage_Criptopay_Model_Config::PAYMENT_TYPE_AUTHENTICATE, 'label' => Mage::helper('criptopay')->__('AUTHENTICATE')),
        );
    }
}