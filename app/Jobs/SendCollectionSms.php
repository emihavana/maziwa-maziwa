<?php

namespace App\Jobs;

use App\Models\MilkCollection;
use App\Services\HostPinnacleSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCollectionSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $collectionId) {}

    public function handle(HostPinnacleSms $sms): void
    {
        $collection = MilkCollection::with(['farmer', 'collector', 'route'])
            ->find($this->collectionId);

        if (! $collection || ! $collection->farmer?->phone) {
            return;
        }

        $farmer = $collection->farmer;
        $date   = $collection->collection_date;

        $rate        = $farmer->priceOn($collection->route_id, $date);
        $litersToday = $farmer->litersOn($date);
        $earnedToday = $farmer->earningsBetween($date->copy(), $date->copy());
        $earnedMonth = $farmer->earningsBetween($date->copy()->startOfMonth(), $date->copy());

        $message = sprintf(
            "Dear %s, we received %s L (%s). Route: %s @ KES %s/L. " .
            "Today: %s L = KES %s. Month so far: KES %s. Collector: %s.",
            $farmer->name,
            number_format((float) $collection->qty, 2),
            $date->format('d/m/Y'),
            $collection->route?->name ?? '-',
            number_format($rate, 2),
            number_format($litersToday, 2),
            number_format($earnedToday, 2),
            number_format($earnedMonth, 2),
            $collection->collector?->name ?? '-'
        );

        // Throwing an exception makes Laravel retry (up to $tries, waiting $backoff).
        if (! $sms->send($farmer->phone, $message)) {
            throw new \RuntimeException('SMS send failed');
        }
    }

    // Runs once, after all retries are used up.
    public function failed(\Throwable $e): void
    {
        Log::error("Collection SMS permanently failed for collection {$this->collectionId}: {$e->getMessage()}");
    }
}