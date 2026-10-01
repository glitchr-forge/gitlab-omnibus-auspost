<?php

namespace Omnibus\Auspost\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Auspost\Api;
use Omnibus\Request\Cancel;
use Omnibus\Request\Request;

/** DELETE /shipping/v1/shipments: the shipment found by its article's tracking id, deleted before it is lodged. */
final class CancelAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Cancel;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Cancel);
        $found = $this->api->call('GET', '/shipping/v1/shipments', null, ['tracking_ids' => $request->trackingNumber]);
        $id = (string) ($found['shipments'][0]['shipment_id'] ?? '');
        if ('' === $id) {
            $request->setResult(false);

            return;
        }
        $this->api->call('DELETE', '/shipping/v1/shipments', null, ['shipment_ids' => $id]);
        $request->setResult(true);
    }
}
