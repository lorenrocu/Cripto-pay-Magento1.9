<?php


class Mage_Criptopay_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = Mage::getModel('criptopay/standard');
		$frmfld  = $standard->setOrder($this->getOrder())->getStandardCheckoutFormFields();
		$payurl = $frmfld['paypage'];
        $form = new Varien_Data_Form();
        $form->setAction($payurl)
            ->setId('criptopay_standard_checkout')
            ->setName('criptopay_standard_checkout')
            ->setMethod('GET')
            ->setUseContainer(true);
//        foreach ($standard->setOrder($this->getOrder())->getStandardCheckoutFormFields() as $field => $value) {
//            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
//        }
        $html = '<html><body>
        ';
        $html.= $this->__('You will be redirected to Criptopay in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("criptopay_standard_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}