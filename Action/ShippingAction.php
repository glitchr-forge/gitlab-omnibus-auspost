<?php

namespace Omnibus\Auspost\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Auspost\Api;
use Omnibus\Auspost\Mapping;
use Omnibus\Exception\CarrierException;
use Omnibus\Model\Label;
use Omnibus\Request\Request;
use Omnibus\Request\Shipping;

/**
 * POST /shipping/v1/shipments, then POST /shipping/v1/labels for its PDF: the
 * label is generated asynchronously, so the result carries the label's link;
 * the content is fetched when Australia Post says it is ready.
 */
final class ShippingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Shipping;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Shipping);
        $s = $request->shipment;
        $product = $s->service ?? ('AU' === strtoupper($s->recipient->country) ? 'PP' : 'PTI8');
        $data = $this->api->call('POST', '/shipping/v1/shipments', ['shipments' => [array_filter([
            'shipment_reference' => $s->reference ? mb_substr($s->reference, 0, 50) : null,
            'email_tracking_enabled' => (bool) $s->recipient->email,
            'from' => Mapping::party($s->sender, $s->option('sender_state')),
            'to' => Mapping::party($s->recipient, $s->option('recipient_state')),
            'items' => array_map(static fn ($p) => Mapping::item($p, $s, $product), $s->parcels),
        ])]]);
        $shipment = $data['shipments'][0] ?? [];
        $id = (string) ($shipment['shipment_id'] ?? '');
        $number = (string) ($shipment['items'][0]['tracking_details']['article_id'] ?? $shipment['items'][0]['tracking_details']['consignment_id'] ?? '');
        if ('' === $id || '' === $number) {
            throw new CarrierException('auspost', 'Australia Post booked no shipment.');
        }
        $url = null;
        $content = null;
        try {
            $labels = $this->api->call('POST', '/shipping/v1/labels', [
                'wait_for_label_url' => true,
                'preferences' => [['type' => 'PRINT', 'format' => 'PDF', 'groups' => [['group' => 'AU' === strtoupper($s->recipient->country) ? 'Parcel Post' : 'International', 'layout' => $s->option('layout', 'A4-1pp'), 'branded' => false, 'left_offset' => 0, 'top_offset' => 0]]]],
                'shipments' => [['shipment_id' => $id]],
            ]);
            $url = $labels['labels'][0]['url'] ?? null;
            if (\is_string($url) && 'AVAILABLE' === ($labels['labels'][0]['status'] ?? 'AVAILABLE')) {
                $content = $this->api->call('GET', $url)['content'] ?? null;
            }
        } catch (CarrierException) {
            // Pending: GetSlip asks again.
        }
        $request->setResult(new Label('auspost', $number, $content, Label::PDF, $url, 'https://auspost.com.au/mypost/track/#/details/'.rawurlencode($number)));
    }
}
