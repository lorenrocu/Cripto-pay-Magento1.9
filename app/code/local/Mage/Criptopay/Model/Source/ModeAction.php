<?php

class Mage_Criptopay_Model_Source_ModeAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Criptopay_Model_Config::MODE_LIVE, 'label' => Mage::helper('criptopay')->__('Live')),
            array('value' => Mage_Criptopay_Model_Config::MODE_TEST, 'label' => Mage::helper('criptopay')->__('Test')),			
        );
    }
}



