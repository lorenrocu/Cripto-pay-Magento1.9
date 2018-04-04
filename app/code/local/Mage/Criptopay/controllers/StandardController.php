<?php

class Mage_Criptopay_StandardController extends Mage_Core_Controller_Front_Action
{
    public $isValidResponse = false;

    /**
     * Get singleton with Criptopay strandard
     *

     */
    public function getStandard()
    {
        return Mage::getSingleton('criptopay/standard');
    }

    /**
     * Get Config model
     *
     */
    public function getConfig()
    {
        return $this->getStandard()->getConfig();
    }

    public function getipnUrl ()
    {
         $url = $this->getConfig()->getipnurl();
        return $url;
    }
    
    /**
     *  Return debug flag
     *
     *  @return  boolean
     */
    public function getDebug ()
    {
        return $this->getStandard()->getDebug();
    }

    /**
     * When a customer chooses Criptopay on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setCriptopayStandardQuoteId($session->getQuoteId());

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $order->addStatusToHistory(
            $order->getStatus(),
            Mage::helper('criptopay')->__('Customer was redirected to CriptoPay')
        );
        $order->save();

        $this->getResponse()
            ->setBody($this->getLayout()
                ->createBlock('criptopay/standard_redirect')
                ->setOrder($order)
                ->toHtml());

        $session->unsQuoteId();
    }
	
	
    public function ipnAction()
    {
		$orderid = $_REQUEST['orderid'];
		$ipnJson = json_encode($_REQUEST, true);
		
		
		
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderid);
		if ($this->getDebug()) {
	        $order->addStatusToHistory(
            	$order->getStatus(),
        	    Mage::helper('criptopay')->__('Customer was IPN Data From CriptoPay - ').$ipnJson
    	    );
	        $order->save();
		}
		
		
		$paymentid = $_REQUEST['id'];
		$apiusername = $this->getConfig()->getusername();
		$apipass = $this->getConfig()->getpassword();		
		$apiKey =  base64_encode($apiusername.':'.$apipass);
		
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			  CURLOPT_URL => $this->getipnurl().$paymentid,
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
		} else {
			$responseAr = json_decode($response, true);						
			if (($responseAr['data']['status'] == 30) || ($responseAr['data']['status'] == 20)) {
		        if ($this->getDebug()) {
        		    Mage::getModel('criptopay/api_debug')
		                ->setResponseBody(print_r($this->responseArr,1))
        		        ->save();
		        }
				
                if ($this->saveInvoice($order)) {
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                } else {
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
                }				
				$payment =$order->getPayment();
    			$payment->setAdditionalInformation('criptopay_status',$responseAr['data']['status']);							
				$payment->save;

				 $order->addStatusToHistory(
		            $order->getStatus(),
		            Mage::helper('criptopay')->__('CriptoPay Payment Complete. Transaction ID :'.$responseAr['data']['txid'])
		          );


			      $order->save();
			      $order->sendNewOrderEmail();	
				
			}
			
			
			
		}
		
		
		
		echo "OK";
		exit;


    }	

    /**
     *  Success response from Criptopay
     *
     *  @return	  void
     */
    public function  successResponseAction()
    {

		$orderid = $_REQUEST['orderid'];

        if (!$orderid) {
            $this->_redirect('');
            return ;
        }

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderid);
		if (!$order->getId()) {
            return false;
        }		

		$payment =$order->getPayment();
		$criptopay_status = $payment->getAdditionalInformation('criptopay_status');	
		$transaction_id = $payment->getAdditionalInformation('transaction_id');									

		
		$statusStr = __('Pending');
				
		if ($criptopay_status == '30') $statusStr = __('Success');
		
	    $session = Mage::getSingleton('checkout/session');
		
      	$session->setQuoteId();
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();

        $success_msg =  __('<b>Order No: <b>').$orderid;
		$success_msg .=  __('<br><b>Transcation ID: <b>').$transaction_id;			
//		$success_msg .=  __('<br><b>Payment Status: <b>').$statusStr;						
//        $success_msg .=  __('<br><b>Wait about 5 hours for crypto-pay to process the payment<b>');
        $session->setErrorMessage($success_msg);			 
	    $this->_redirect('criptopay/standard/success');				 
//		$this->_redirect('checkout/onepage/success');			 
    }
	
	  public function successAction ()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setCriptopayStandardQuoteId($session->getQuoteId());
        if (!$session->getErrorMessage()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('criptopay/session');
        $this->renderLayout();

    }	

    /**
     *  Save invoice for order
     *
     *  @param    Mage_Sales_Model_Order $order
     *  @return	  boolean Can save invoice or not
     */
    protected function saveInvoice (Mage_Sales_Model_Order $order)
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();

            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')
               ->addObject($invoice)
               ->addObject($invoice->getOrder())
               ->save();
            return true;
        }

        return false;
    }


   

    /**
     *  Failure Action
     *
     *  @return	  void
     */
    public function  failureResponseAction()
    {
		
		$orderid = $_REQUEST['orderid'];

        if (!$orderid) {
            $this->_redirect('');
            return ;
        }


				
		$statusStr = __('Failed');
	    $session = Mage::getSingleton('checkout/session');
      	$session->setQuoteId($session->getCriptopayStandardQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $success_msg =  __('<b>Order No: <b>').$orderid;
		$success_msg .=  __('<br><b>Payment Status: <b>').$statusStr;						
//		echo $success_msg;
        $session->setErrorMessage($success_msg);			 
	    $this->_redirect('criptopay/standard/failure');				
	}
    public function failureAction ()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setCriptopayStandardQuoteId($session->getQuoteId());
		
        if (!$session->getErrorMessage()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('criptopay/session');
        $this->renderLayout();
    }
	

   
	

	
 			
}