<?php


class Mage_Criptopay_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('criptopay/standard/form.phtml');
        parent::_construct();
    }

}