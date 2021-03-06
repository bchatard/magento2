<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BraintreeTwo\Gateway\Response;

use Braintree_Transaction;
use Magento\BraintreeTwo\Gateway\Config\Config;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Class CardDetailsHandler
 * @package Magento\BraintreeTwo\Gateway\Response
 */
class CardDetailsHandler implements HandlerInterface
{
    const CARD_TYPE = 'cardType';

    const CARD_EXP_MONTH = 'expirationMonth';

    const CARD_EXP_YEAR = 'expirationYear';

    const CARD_LAST4 = 'last4';

    const CARD_NUMBER = 'cc_number';

    /**
     * @var \Magento\BraintreeTwo\Gateway\Config\Config
     */
    private $config;

    /**
     * @param \Magento\BraintreeTwo\Gateway\Config\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var \Braintree_Transaction $transaction */
        $transaction = $response['object']->transaction;
        /**
         * @TODO after changes in sales module should be refactored for new interfaces
         */
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $creditCard = $transaction->creditCard;
        $payment->setCcLast4($creditCard[self::CARD_LAST4]);
        $payment->setCcExpMonth($creditCard[self::CARD_EXP_MONTH]);
        $payment->setCcExpYear($creditCard[self::CARD_EXP_YEAR]);


        $payment->setCcType($this->getCreditCardType($creditCard[self::CARD_TYPE]));

        // set card details to additional info
        $payment->setAdditionalInformation(self::CARD_NUMBER, 'xxxx-' . $creditCard[self::CARD_LAST4]);
        $payment->setAdditionalInformation(OrderPaymentInterface::CC_TYPE, $creditCard[self::CARD_TYPE]);
    }

    /**
     * Get type of credit card mapped from Braintree
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCctypesMapper();

        return $mapper[$replaced];
    }
}
