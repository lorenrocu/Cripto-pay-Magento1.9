<?php

class Mage_Criptopay_Model_Config extends Varien_Object
{
    const MODE_TEST         = 'TEST';
    const MODE_LIVE         = 'LIVE';

    const PAYMENT_TYPE_PAYMENT      = 'PAYMENT';
    const PAYMENT_TYPE_DEFERRED     = 'DEFERRED';
    const PAYMENT_TYPE_AUTHENTICATE = 'AUTHENTICATE';
    const PAYMENT_TYPE_AUTHORISE    = 'AUTHORISE';


    /**
     *  Return config var
     *
     *  @param    string Var key
     *  @param    string Default value for non-existing key
     *  @return	  mixed
     */
    public function getConfigData($key, $default=false)
    {
        if (!$this->hasData($key)) {
             $value = Mage::getStoreConfig('payment/criptopay_standard/'.$key);
             if (is_null($value) || false===$value) {
                 $value = $default;
             }
            $this->setData($key, $value);
        }
        return $this->getData($key);
    }


    /**
     *  Return Store description sent to Criptopay
     *
     *  @return	  string Description
     */
    public function getDescription ()
    {
        return $this->getConfigData('description');
    }

    /**
     *  Return Criptopay registered Product Id
     *
     *  @return	  string Product Id
     */
    public function getusername ()
    {
        return $this->getConfigData('cpusername');
    }


    public function getpassword ()
    {
        return $this->getConfigData('cppassword');
    }
	

    public function getpaymode()
    {
		$mode = 'production' ;
		if ($this->getConfigData('mode') == 'TEST') $mode = 'sandbox' ;
		return $mode;

    }	
	


    public function getpayurl()
    {
		$url = 'https://api.cripto-pay.com/payments/new' ;
		if ($this->getConfigData('mode') == 'TEST') $url = 'https://apidev.cripto-pay.com/payments/new' ;
		return $url;

    }	
	
    public function getexurl()
    {
		$url = 'https://api.cripto-pay.com/public/price/eur/btc' ;
		if ($this->getConfigData('mode') == 'TEST') $url = 'https://apidev.cripto-pay.com/public/price/eur/btc' ;
		return $url;

    }	
		
    public function getpaypageurl()
    {
		$url = 'https://www.cripto-pay.com/pago/' ;
		if ($this->getConfigData('mode') == 'TEST') $url = 'https://developers.cripto-pay.com/pago/' ;
		return $url;

    }		


    public function getipnurl()
    {
		$url = 'https://api.cripto-pay.com/payments/' ;
		if ($this->getConfigData('mode') == 'TEST') $url = 'https://apidev.cripto-pay.com/payments/' ;
		return $url;

    }		


     /**
     *  Return new order status
     *
     *  @return	  string New order status
     */
    public function getNewOrderStatus ()
    {
        return $this->getConfigData('order_status');
    }

    /**
     *  Return debug flag
     *
     *  @return	  boolean Debug flag (0/1)
     */
    public function getDebug ()
    {
        return $this->getConfigData('debug_flag');
    }




}