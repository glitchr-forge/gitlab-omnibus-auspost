<?php

namespace Omnibus\Auspost\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Auspost\Api;
use Omnibus\Auspost\Mapping;
use Omnibus\Model\Rate;
use Omnibus\Request\Rating;
use Omnibus\Request\Request;

/** POST /shipping/v1/prices/items: every product the account can send the items with, priced. */
final class RatingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Rating;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Rating);
        $s = $request->shipment;
        $data = $this->api->call('POST', '/shipping/v1/prices/items', [
            'from' => Mapping::party($s->sender, $s->option('sender_state')),
            'to' => Mapping::party($s->recipient, $s->option('recipient_state')),
            'items' => array_map(static fn ($p) => array_filter(['length' => $p->length, 'width' => $p->width, 'height' => $p->height, 'weight' => round(max(0.1, $p->weight / 1000), 2)]), $s->parcels),
        ]);
        $rates = [];
        foreach ($data['items'][0]['prices'] ?? [] as $price) {
            $code = (string) ($price['product_id'] ?? '');
            $rates[] = new Rate('auspost', $code, (string) ($price['product_type'] ?? Mapping::PRODUCTS[$code] ?? $code), (int) round(((float) ($price['calculated_price'] ?? $price['calculated_price_ex_gst'] ?? 0)) * 100), 'AUD');
        }
        usort($rates, static fn (Rate $a, Rate $b) => $a->amount <=> $b->amount);
        $request->setResult($rates);
    }
}
