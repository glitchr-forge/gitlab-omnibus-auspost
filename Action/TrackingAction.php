<?php

namespace Omnibus\Auspost\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\Auspost\Api;
use Omnibus\Auspost\Mapping;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;

/** GET /shipping/v1/track: the article's events, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->call('GET', '/shipping/v1/track', null, ['tracking_ids' => $request->trackingNumber]);
        $result = $data['tracking_results'][0] ?? [];
        $events = [];
        foreach ($result['trackable_items'][0]['events'] ?? $result['events'] ?? [] as $event) {
            $events[] = new TrackingEvent(new \DateTimeImmutable((string) ($event['date'] ?? 'now')), Mapping::status($event['description'] ?? null), (string) ($event['description'] ?? ''), $event['location'] ?? null);
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $request->setResult(new TrackingModel('auspost', $request->trackingNumber, Mapping::status($result['status'] ?? ($events ? $events[array_key_last($events)]->description : null)), $events));
    }
}
