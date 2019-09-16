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
use SerendipityHQ\Bundle\StripeBundle\Model\StripeLocalResourceInterface;
use Stripe\ApiResource;
use Stripe\Card;
use Stripe\PaymentMethod;

/**
 * @author Adamo Crespi <hello@aerendir.me>
 *
 * @see https://stripe.com/docs/api#card_object
 */
class CardSyncer extends AbstractSyncer
{
    /**
     * {@inheritdoc}
     *
     * @param Card $stripeResource
     */
    public function syncLocalFromStripe(StripeLocalResourceInterface $localResource, ApiResource $stripeResource)
    {
        /** @var StripeLocalCard $localResource */
        if ( !$localResource instanceof StripeLocalCard) {
            throw new \InvalidArgumentException('CardSyncer::hydrateLocal() accepts only StripeLocalCard objects as first parameter');
        }

        /** @var Card $stripeResource */
        if ( !$stripeResource instanceof Card && !$stripeResource instanceof PaymentMethod) {
            throw new \InvalidArgumentException('CardSyncer::hydrateLocal() accepts only Stripe\Card objects as second parameter.');
        }
        $isSca = $stripeResource instanceof PaymentMethod;
        $reflect = new \ReflectionClass($localResource);
        dump($localResource, $stripeResource);die;
        foreach ($reflect->getProperties() as $reflectedProperty) {
            // Set the property as accessible
            $reflectedProperty->setAccessible(true);
            /*
             * Guess the kind and set its value (Customer has to be StripeLocalCustomerObject set before the Card
             * $localResource is passed to this hydration method!)
             */
            switch ($reflectedProperty->getName()) {
                case 'id':
                    $reflectedProperty->setValue($localResource, $stripeResource->id);
                    break;

                case 'addressCity':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_city);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->billing_details->address->city);
                    }
                    break;

                case 'addressCountry':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_country);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->billing_details->address->country);
                    }
                    break;

                case 'addressLine1':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_line1);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->billing_details->address->line1);
                    }
                    break;

                case 'addressLine1Check':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_line1_check);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->checks->address_line1_check);
                    }
                    break;

                case 'addressLine2':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_line2);
                    } else {
                        $reflectedProperty->setValue($localResource, null);
                    }
                    break;

                case 'addressState':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_state);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->billing_details->address->state);
                    }
                    break;

                case 'addressZip':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_zip);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->billing_details->address->postal_code);
                    }
                    break;

                case 'addressZipCheck':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->address_zip_check);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->checks->address_postal_code_check);
                    }
                    break;

                case 'brand':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->brand);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->brand);
                    }
                    break;

                case 'country':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->country);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->country);
                    }
                    break;

                case 'customer':
                    $localCustomer = $this->getLocalCustomer($stripeResource->customer);

                    if (false !== $localCustomer) {
                        $reflectedProperty->setValue($localResource, $localCustomer);
                    }
                    break;

                case 'cvcCheck':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->cvc_check);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->checks->cvc_check);
                    }
                    break;

                case 'dynamicLast4':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->dynamic_last4);
                    } else {
                        $reflectedProperty->setValue($localResource, null);
                    }
                    break;

                case 'expMonth':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->exp_month);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->exp_month);
                    }
                    break;

                case 'expYear':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->exp_year);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->exp_year);
                    }
                    break;

                case 'fingerprint':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->fingerprint);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->fingerprint);
                    }
                    break;

                case 'funding':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->funding);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->funding);
                    }
                    break;

                case 'last4':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->last4);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->card->last4);
                    }
                    break;

                case 'metadata':
                    $reflectedProperty->setValue($localResource, $stripeResource->metadata->toArray());
                    break;

                case 'name':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->name);
                    } else {
                        $reflectedProperty->setValue($localResource, $stripeResource->billing_details->name);
                    }
                    break;

                case 'tokenizationMethod':
                    if(!isSca()) {
                        $reflectedProperty->setValue($localResource, $stripeResource->tokenization_method);
                    } else {
                        $reflectedProperty->setValue($localResource, null);
                    }
                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function syncStripeFromLocal(ApiResource $stripeResource, StripeLocalResourceInterface $localResource)
    {
        /** @var Card $stripeResource */
        if ( ! $stripeResource instanceof Card) {
            throw new \InvalidArgumentException('CardSyncer::hydrateLocal() accepts only Stripe\Card objects as first parameter.');
        }

        /** @var StripeLocalCard $localResource */
        if ( ! $localResource instanceof StripeLocalCard) {
            throw new \InvalidArgumentException('CardSyncer::hydrateLocal() accepts only StripeLocalCard objects as second parameter.');
        }

        throw new \RuntimeException('Method not yet implemented');
    }
}
