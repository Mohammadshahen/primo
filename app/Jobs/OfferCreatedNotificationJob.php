<?php

namespace App\Jobs;

use App\Models\Offer;
use App\Services\NotificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OfferCreatedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Offer $offer;

    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            $notificationService->notifyUsersAboutOffer(
                $this->offer->loadMissing('variant.product:id,name')
            );
        } catch (Exception $e) {
            Log::error('OfferCreatedNotificationJob failed', [
                'offer_id' => $this->offer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
