<?php

class Mage_Criptopay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'criptopay_standard';
    protected $_formBlockType = 'criptopay/standard_form';
    protected $_infoBlockType = 'criptopay/standard_info';
	
    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_order = null;
	
	
    /**
     * Get Config model
     *
     * @return object Mage_Criptopay_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('criptopay/config');
    }

/**
	 * Return the payment info model instance for the order
	 *
	 * @return Mage_Payment_Model_Info
	 */
	public function getInfoInstance()
	{
		$payment = $this->getData('info_instance');
		if (! $payment)
		{
			$payment = $this->getOrder()->getPayment();
			$this->setInfoInstance($payment);
		}
		return $payment;
	}

	
/**
	 * Return the specified additional information from the payment info instance
	 *
	 * @param string $key
	 * @param Varien_Object $payment
	 * @return string
	 */
	public function get_PaymentInfoData($key, $payment = null)
	{
		return $payment->getAdditionalInformation($key);
	}	
	
	/**
	 * Return the transaction id for the current transaction
	 *
	 * @return string
	 */
	public function get_TransactionId()
	{
		return $this->get_PaymentInfoData('transaction_id');
	}	
	
	 public function prepareSave()
    {
	    $info = $this->getInfoInstance();    
	    return $this;

    }	

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
	   $info = $this->getInfoInstance();
       $info->setCcNumberEnc($data->getCriptopaytoken())
	   ->setCcSsStartMonth($data->getEmi());


	   
        return $this;
    }
    /**
     * Return debug flag
     *
     *  @return  boolean
     */
    public function getDebug ()
    {
        return $this->getConfig()->getDebug();
    }

    /**
     *  Returns Target URL
     *
     *  @return	  string Target URL
     */
    public function getCriptopayUrl ()
    {
         $url = $this->getConfig()->getpayurl();
        return $url;
    }
	
    public function getCriptopaypageUrl ()
    {
         $url = $this->getConfig()->getpaypageurl();
        return $url;
    }	
	
	
   

    /**
     *  Return URL for Criptopay success response
     *
     *  @return	  string URL
     */
    public function getSuccessURL ()
    {
        return Mage::getUrl('criptopay/standard/successresponse');
    }

    /**
     *  Return URL for Criptopay failure response
     *
     *  @return	  string URL
     */
    protected function getFailureURL ()
    {
        return Mage::getUrl('criptopay/standard/failureresponse');
    }
	
    protected function getIPNURL ()
    {
        return Mage::getUrl('criptopay/standard/ipn');
    }	

    /**
     * Transaction unique ID sent to Criptopay and sent back by Criptopay for order restore
     * Using created order ID
     *
     *  @return	  string Transaction unique number
     */
    protected function getVendorTxCode ()
    {
        return $this->getOrder()->getRealOrderId().'_'.time();
    }

    /**
     *  Returns cart formatted
     *  String format:
     *  Number of lines:Name1:Quantity1:CostNoTax1:Tax1:CostTax1:Total1:Name2:Quantity2:CostNoTax2...
     *
     *  @return	  string Formatted cart items
     */
    protected function getFormattedCart ()
    {
        $items = $this->getOrder()->getAllItems();
        $resultParts = array();
        $totalLines = 0;
        if ($items) {
            foreach($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $quantity = $item->getQtyOrdered();

                $cost = sprintf('%.2f', $item->getBasePrice() - $item->getBaseDiscountAmount());
                $tax = sprintf('%.2f', $item->getBaseTaxAmount());
                $costPlusTax = sprintf('%.2f', $cost + $tax/$quantity);

                $totalCostPlusTax = sprintf('%.2f', $quantity * $cost + $tax);

                $resultParts[] = str_replace(':', ' ', $item->getName());
                $resultParts[] = $quantity;
                $resultParts[] = $cost;
                $resultParts[] = $tax;
                $resultParts[] = $costPlusTax;
                $resultParts[] = $totalCostPlusTax;
                $totalLines++; //counting actual formatted items
            }
       }

       // add delivery
       $shipping = $this->getOrder()->getBaseShippingAmount();
       if ((int)$shipping > 0) {
           $totalLines++;
           $resultParts = array_merge($resultParts, array('Shipping','','','','',sprintf('%.2f', $shipping)));
       }

       $result = $totalLines . ':' . implode(':', $resultParts);
       return $result;
    }


    /**
     *  Form block description
     *
     *  @return	 object
     */
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('criptopay/form_standard', $name);
        $block->setMethod($this->_code);
        $block->setPayment($this->getPayment());
        return $block;
    }

 /**
     *  Return Order Place Redirect URL
     *
     *  @return	  string Order Redirect URL
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('criptopay/standard/redirect');
    }    


    /**
     *  Return Standard Checkout Form Fields for request to Criptopay
     *
     *  @return	  array Array of hidden form fields
     */
    public function getStandardCheckoutFormFields ()
    {
        $order = $this->getOrder();		
        $amount = $order->getBaseGrandTotal();
        $description = Mage::app()->getStore()->getName() . ' ' . ' payment';
		
			
		
		$amount = $order->getBaseGrandTotal();
		$amount = number_format($amount, 0, '.', '');
		$OrderID = $order->increment_id;

		$apiusername = $this->getConfig()->getusername();
		$apipass = $this->getConfig()->getpassword();		


		$apiKey =  base64_encode($apiusername.':'.$apipass);

		$orderurl  = "?orderid=".$OrderID;
		$paymentAr['amount'] = $amount;		 
		$paymentAr['currency'] = trim($order->getOrderCurrency()->getCurrencyCode());		 			 				
		$paymentAr['concept'] = 'OrderNo:'.$OrderID;
		$paymentAr['urlOk'] = $this->getSuccessURL().$orderurl;
		$paymentAr['urlKo'] = $this->getFailureURL().$orderurl;		
		$paymentAr['urlIpn'] = $this->getIPNURL().$orderurl;						 						
		$paymentJson =  json_encode($paymentAr, true);
		
//		echo $paymentJson;
//		exit;

				if ($this->getDebug()) {
		        	$order->addStatusToHistory(
		    	        $order->getStatus(),
        			    Mage::helper('criptopay')->__('CriptoPay Request - ').$paymentJson
			        );
			        $order->save();
				}



		$curl = curl_init();

		curl_setopt_array($curl, array(
			  CURLOPT_URL => $this->getCriptopayUrl(),
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_SSL_VERIFYHOST => false,  
			  CURLOPT_SSL_VERIFYPEER => false,    
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS =>$paymentJson,
			  CURLOPT_HTTPHEADER => array(
			    "cache-control: no-cache",
			    "content-type: application/json",
			    "Authorization: Basic ".$apiKey
			  ),
		));


		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo $err;
			exit;
		} else {
			$responseAr = json_decode($response, true);			
			if ($responseAr['status'] == 'ok') {
				
				if ($this->getDebug()) {
		        	$order->addStatusToHistory(
		    	        $order->getStatus(),
        			    Mage::helper('criptopay')->__('CriptoPay Response - ').$response
			        );
			        $order->save();
				}

				$payment = $order->getPayment();
    			$payment->setAdditionalInformation('transaction_id', trim($responseAr['data']['id']));							
				$payment->save;

				
		        $order->addStatusToHistory(
		            $order->getStatus(),
        		    Mage::helper('criptopay')->__('CriptoPay ID - ').trim($responseAr['data']['id'])
		        );
		        $order->save();
				
				$exurl = $this->getConfig()->getexurl();		
				$curl = curl_init();
				curl_setopt_array($curl, array(
				  CURLOPT_URL => $exurl,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_SSL_VERIFYHOST => false,  
				  CURLOPT_SSL_VERIFYPEER => false,    
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "GET",
				  CURLOPT_HTTPHEADER => array(
					    "cache-control: no-cache",
				    	"content-type: application/json",	
					  ),
				));


				$response = curl_exec($curl);
				$err = curl_error($curl);
				curl_close($curl);		
				if (!$err) {
					$exresponseAr = json_decode($response, true);								
				} 
				
		        $order->addStatusToHistory(
		            $order->getStatus(),
        		    Mage::helper('criptopay')->__('CriptoPay Exchange Rate EUR to BTC - Buy : ').trim($exresponseAr['data']['buy']). ' , Sell : '.trim($exresponseAr['data']['sell'])
		        );
		        $order->save();					
				
							
						
				$fields['paypage'] = $this->getCriptopaypageUrl().trim($responseAr['data']['id']);
				header("Location: {$fields['paypage']}");

			}
			else 
			{
				echo $responseAr['error'];
				}
		}
				exit;		
	    return $fields;
    }
}