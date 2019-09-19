<?php

/*
 * This file is part of the SHQStripeBundle.
 *
 * Copyright Carlos Campo.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author   Carlos Campo <carlos.campo@cosmomedia.es>
 * @copyright Copyright (C) 2018 - 2019 Carlos Campo. All rights reserved.
 * @license   MIT License.
 */

namespace SerendipityHQ\Bundle\StripeBundle\Event;

class StripeSubscriptionUpdateEvent extends AbstractStripeSubscriptionEvent
{
    const UPDATE  = 'stripe.local.subscription.update';
    const UPDATED = 'stripe.local.subscription.updated';
}
