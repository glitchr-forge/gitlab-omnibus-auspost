<?php

namespace Omnibus\Auspost;

use Omnibus\Core\GatewayFactory;
use Omnibus\Auspost\Action\ShippingAction;
use Omnibus\Auspost\Action\TrackingAction;
use Omnibus\Auspost\Action\PackingSlipAction;

class AuspostGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'auspost',
            'omnibus.factory_title' => 'Australia Post',
        ]);
    }
}