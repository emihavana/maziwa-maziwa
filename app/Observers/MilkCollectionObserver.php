<?php

namespace App\Observers;

use App\Jobs\SendCollectionSms;
use App\Models\MilkCollection;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

// ShouldHandleEventsAfterCommit: wait until the DB transaction commits.
// Without it, the job could start before the row is saved and not find it.
class MilkCollectionObserver implements ShouldHandleEventsAfterCommit
{
    // Runs automatically after a new MilkCollection row is inserted.
    public function created(MilkCollection $collection): void
    {
        // Optional: skip corrections so farmers aren't texted twice.
        if ($collection->is_correction) return;

        // Only queue the work (fast). The slow SMS call happens in the
        // background, so the collector's screen never waits on it.
        // Put the SMS job in the database queue (the "jobs" table),
        // while everything else in the app keeps using "sync".
        SendCollectionSms::dispatch($collection->id)->onConnection('database');
    }
}