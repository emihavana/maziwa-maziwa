<?php

namespace App\Jobs;

use App\Models\MilkCollection;
use App\Services\HostPinnacleSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// ShouldQueue = runs in the background via the queue worker.
class SendCollectionSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;     // try up to 3 times if the SMS API fails
    public int $backoff = 60;  // wait 60s between tries (provider outages pass)

    // We pass only the ID, not the model: a small payload, and the job
    // reloads fresh data when it actually runs.
    public function __construct(public int $collectionId) {}

    // Laravel injects HostPinnacleSms automatically (type-hint = dependency).
    public function handle(HostPinnacleSms $sms): void
    {
        // Eager-load everything the message needs in one go (avoids N+1 queries).
        $collection = MilkCollection::with(['farmer', 'collector', 'route'])
            ->find($this->collectionId);

        // Nothing to do if the row was deleted or the farmer has no phone.
        // Return quietly (not fail) because retrying can never fix this.
        if (! $collection || ! $collection->farmer?->phone) {
            return;
        }

        $farmer = $collection->farmer;
        // collection_date is a Carbon object thanks to the 'date' cast.
        $date   = $collection->collection_date;

        // Rate that applied to THIS collection (its route, on its date).
        $rate = $farmer->priceOn($collection->route_id, $date);

        // Day totals. copy() is needed because Carbon methods like
        // startOfMonth() MODIFY the object; copying protects $date.
        $litersToday = $farmer->litersOn($date);
        $earnedToday = $farmer->earningsBetween($date->copy(), $date->copy());
        // 1st of the month up to the collection date = "month so far".
        $earnedMonth = $farmer->earningsBetween($date->copy()->startOfMonth(), $date->copy());

        // ?-> and ?? guard against a missing route or collector so the
        // message still sends with "-" instead of crashing.
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

        // If sending failed, fail() marks this attempt as failed so Laravel
        // retries after $backoff seconds, up to $tries times.
        if (! $sms->send($farmer->phone, $message)) {
            $this->fail(new \RuntimeException('SMS send failed'));
        }
    }
}