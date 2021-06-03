<?php

/*
 * This file is part of the SHQStripeBundle.
 *
 * Copyright Adamo Aerendir Crespi 2016-2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    Adamo Aerendir Crespi <hello@aerendir.me>
 * @copyright Copyright (C) 2016 - 2017 Aerendir. All rights reserved.
 * @license   MIT License.
 */

namespace SerendipityHQ\Bundle\StripeBundle\Syncer;

use SerendipityHQ\Bundle\StripeBundle\Model\StripeLocalCard;
use SerendipityHQ\Bundle\StripeBundle\Model\StripeLocalCharge;
use SerendipityHQ\Bundle\StripeBundle\Model\StripeLocalResourceInterface;
use SerendipityHQ\Component\ValueObjects\Email\Email;
use SerendipityHQ\Component\ValueObjects\Money\Money;
use Stripe\ApiResource;
use Stripe\AttachedObject;
use Stripe\Charge;
use Stripe\StripeObject;
use Stripe\PaymentIntent;

/**
 * @author Adamo Crespi <hello@aerendir.me>
 *
 * @see https://stripe.com/docs/api#card_object
 */
class ChargeSyncer extends AbstractSyncer
{
    /**
     * {@inheritdoc}
     */
    public function syncLocalFromStripe(StripeLocalResourceInterface $localResource, ApiResource $stripeResource)
    {
        /** @var StripeLocalCharge $localResource */
        if ( ! $localResource instanceof StripeLocalCharge) {
            throw new \InvalidArgumentException('ChargeSyncer::syncLocalFromStripe() accepts only StripeLocalCharge objects as first parameter.');
        }

        /** @var Charge $stripeResource */
        if ( !$stripeResource instanceof Charge && !$stripeResource instanceof PaymentIntent) {
            throw new \InvalidArgumentException('ChargeSyncer::syncLocalFromStripe() accepts only Stripe\Charge objects as second parameter.');
        }

        $reflect = new \ReflectionClass($localResource);
        foreach ($reflect->getProperties() as $reflectedProperty) {
            // Set the property as accessible
            $reflectedProperty->setAccessible(true);

            // Guess the kind and set its value
            switch ($reflectedProperty->getName()) {
                case 'id':
                    $reflectedProperty->setValue($localResource, $stripeResource->id);
                    break;

                case 'amount':
                    $reflectedProperty->setValue($localResource, new Money(['baseAmount' => $stripeResource->amount, 'currency' => $stripeResource->currency]));
                    break;

                case 'balanceTransaction':
                    $reflectedProperty->setValue($localResource, $stripeResource->balance_transaction ?? null);
                    break;

                case 'created':
                    $created = new \DateTime();
                    $reflectedProperty->setValue($localResource, $created->setTimestamp($stripeResource->created));
                    break;

                case 'captured':
                    $reflectedProperty->setValue($localResource, $stripeResource->captured ?? false);
                    break;

                case 'description':
                    $reflectedProperty->setValue($localResource, $stripeResource->description);
                    break;

                case 'failureCode':
                    $reflectedProperty->setValue($localResource, $stripeResource->failure_code ?? null);
                    break;

                case 'failureMessage':
                    $reflectedProperty->setValue($localResource, $stripeResource->failure_message ?? null);
                    break;

                case 'fraudDetails':
                    $fraudDetails = $stripeResource->fraud_details ?? null;

                    // If the object come from an Event is an AttachedObject
                    if ($stripeResource->fraud_details && ($stripeResource->fraud_details instanceof AttachedObject || $stripeResource->fraud_details instanceof StripeObject)) {
                        $fraudDetails = $fraudDetails->toArray();
                    }

                    $reflectedProperty->setValue($localResource, $fraudDetails);
                    break;

                case 'livemode':
                    $reflectedProperty->setValue($localResource, $stripeResource->livemode);
                    break;

                case 'metadata':
                    $metadata = $stripeResource->metadata;

                    // If the object come from an Event is an AttachedObject
                    if ($stripeResource->metadata instanceof AttachedObject) {
                        $metadata = $metadata->toArray();
                    }

                    $reflectedProperty->setValue($localResource, $metadata);
                    break;

                case 'outcome':
                    $outcome = $stripeResource->outcome ?? null;

                    // If the object come from an Event is an AttachedObject
                    if ($stripeResource->outcome && $stripeResource->outcome instanceof StripeObject) {
                        $outcome = $outcome->toArray();
                    }

                    $reflectedProperty->setValue($localResource, $outcome);
                    break;

                case 'paid':
                    if (isset($stripeResource->paid)) {
                        $reflectedProperty->setValue($localResource, $stripeResource->paid);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->status == "success");
                    }
                    break;

                case 'receiptEmail':
                    $email = ('' === trim($stripeResource->receipt_email)) ? null : new Email($stripeResource->receipt_email);
                    $reflectedProperty->setValue($localResource, $email);
                    break;

                case 'receiptNumber':
                    $reflectedProperty->setValue($localResource, $stripeResource->receipt_number ?? null);
                    break;

                case 'statementDescriptor':
                    $reflectedProperty->setValue($localResource, $stripeResource->statement_descriptor);
                    break;

                case 'status':
                    $reflectedProperty->setValue($localResource, $stripeResource->status);
                    break;
                case 'redirectToUrl':
                    $reflectedProperty->setValue($localResource, isset($stripeResource->status) && $stripeResource->status != "success" && isset($stripeResource->next_action) && isset($stripeResource->next_action->redirect_to_url) ? $stripeResource->next_action->redirect_to_url->url : null);
                    break;
                case 'type':
                    $reflectedProperty->setValue($localResource, get_class($stripeResource));
                    break;
                case 'callbackUrl':
                    if (isset($stripeResource->status) && $stripeResource->status != "succeeded" && isset($stripeResource->next_action) && isset($stripeResource->next_action->redirect_to_url)) {
                        
                        $reflectedProperty->setValue($localResource, $stripeResource->next_action->redirect_to_url->return_url);
                    } else {
                        $reflectedProperty->setValue($localResource, null);
                    }
                    break;
                case 'clientSecret':
                    //Guardar client_secret
                    if ($stripeResource->client_secret) {
                        $reflectedProperty->setValue($localResource, $stripeResource->client_secret);
                    }
                    break;
                
            }
        }
        // Ever first persist the $localStripeResource: descendant syncers may require the object is known by the EntityManager.
        $this->getEntityManager()->persist($localResource);

        // Out of the foreach, process the source to associate to the charge.
        if (isset($stripeResource->source)) {
            $localCard = $this->getEntityManager()->getRepository('SHQStripeBundle:StripeLocalCard')->findOneByStripeId($stripeResource->source->id);
        } else {
            $localCard = $this->getEntityManager()->getRepository('SHQStripeBundle:StripeLocalCard')->findOneByStripeId($stripeResource->payment_method);
        }

        // Chek if the card exists
        if (null === $localCard) {
            // It doesn't exist: create and persist it
            $localCard = new StripeLocalCard();
        }

        // Sync the local card with the remote object
        if (isset($stripeResource->source)) {
            $this->getCardSyncer()->syncLocalFromStripe($localCard, $stripeResource->source);
        } else {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($stripeResource->payment_method);
            $this->getCardSyncer()->syncLocalFromStripe($localCard, $paymentMethod);
        }

        /*
         * Persist the card again: if it is a newly created card, we have to persist it, but, as the id of a local card
         * is its Stripe ID, we can persist it only after the sync.
         */
        $this->getEntityManager()->persist($localCard);

        // Now set the Card as source of the StripeLocalCharge object
        $defaultSourceProperty = $reflect->getProperty('source');
        $defaultSourceProperty->setAccessible(true);
        $defaultSourceProperty->setValue($localResource, $localCard);

        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function syncStripeFromLocal(ApiResource $stripeResource, StripeLocalResourceInterface $localResource)
    {
        /** @var Charge $stripeResource */
        if ( ! $stripeResource instanceof Charge) {
            throw new \InvalidArgumentException('ChargeSyncer::hydrateStripe() accepts only Stripe\Charge objects as first parameter.');
        }

        /** @var StripeLocalCharge $localResource */
        if ( ! $localResource instanceof StripeLocalCharge) {
            throw new \InvalidArgumentException('ChargeSyncer::hydrateStripe() accepts only StripeLocalCharge objects as second parameter.');
        }

        throw new \RuntimeException('Method not yet implemented');
    }

    /**
     * @param StripeLocalCharge $localCharge
     * @param array             $error
     */
    public function handleFraudDetection(StripeLocalCharge $localCharge, array $error)
    {
        $reflect = new \ReflectionClass($localCharge);

        // Set the Charge Stripe ID as returned by the error
        $propertyId = $reflect->getProperty('id');
        $propertyId->setAccessible(true);
        $propertyId->setValue($localCharge, $error['error']['charge']);

        // Set other required fields. They will be update with right data by the webhook calling
        $propertyCaptured = $reflect->getProperty('captured');
        $propertyCaptured->setAccessible(true);
        $propertyCaptured->setValue($localCharge, false);

        $propertyCreated = $reflect->getProperty('created');
        $propertyCreated->setAccessible(true);
        // Set fictionally
        $propertyCreated->setValue($localCharge, new \DateTime());

        $propertyLivemode = $reflect->getProperty('livemode');
        $propertyLivemode->setAccessible(true);
        $propertyLivemode->setValue($localCharge, true);

        $propertyPaid = $reflect->getProperty('paid');
        $propertyPaid->setAccessible(true);
        $propertyPaid->setValue($localCharge, false);

        // Mark the card as fraudulent
        $localCharge->getCustomer()->getDefaultSource()->setError($error['concatenated']);

        // Save the local charge to the database
        $this->getEntityManager()->persist($localCharge);
        $this->getEntityManager()->flush();
    }
}
