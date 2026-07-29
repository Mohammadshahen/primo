<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Ordar;
use App\Models\Suggestion;
use App\Models\User;

class NotificationService
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function notifyUsersAboutOffer(Offer $offer): void
    {

        $title = 'عرض جديد';
        $body = "تم إنشاء عرض جديد على المنتج {$offer->variant->product->name}";

        User::where('notification_offer', true)
            ->chunk(100, function ($users) use ($offer, $title, $body) {
                foreach ($users as $user) {
                    $this->fcmService->sendToUser($user, $title, $body, [
                        'product_id' => $offer->variant->product->id,
                    ]);
                }
            });
    }

    public function notifictionCreateOrdarForAdmin(Ordar $ordar)
    {
        return $this->fcmService->sendToUserOrdar(
            User::where('is_admin', true)->first(),
            'طلب جديد',
            "لديك طلب جديد من العميل {$ordar->user->name}",
            [
                'ordar_id' => $ordar->id,
            ]
        );
    }

    public function notifictionCreateOrdarForUser(Ordar $ordar)
    {
        return $this->fcmService->sendToUserOrdar(
            $ordar->user,
            'طلب جديد',
            "تم انشاء طلب بنجاح",
            [
                'ordar_id' => $ordar->id,
            ]
        );
    }
    public function notifictionDeliveryOrdarForUser(Ordar $ordar)
    {
        return $this->fcmService->sendToUserOrdar(
            $ordar->user,
            'تم تجهيز طلبك',
            "طلبك جاهز وفي طريقه اليك",
            [
                'ordar_id' => $ordar->id,
            ]
        );
    }

    public function notifictionOnStoreOrdarForUser(Ordar $ordar)
    {
        return $this->fcmService->sendToUserOrdar(
            $ordar->user,
            'تم تجهيز طلبك',
            "طلبك جاهز ويمكنك استلامه من المتجر",
            [
                'ordar_id' => $ordar->id,
            ]
        );
    }

    public function notifictionCompletedOrdarForUser(Ordar $ordar)
    {
        return $this->fcmService->sendToUserOrdar(
            $ordar->user,
            'تم إكمال طلبك',
            "تم إكمال طلبك بنجاح وشكرا لك على اختيارك متجرنا",
            [
                'ordar_id' => $ordar->id,
            ]
        );
    }

    public function notifictionCanceledOrdarForUser(Ordar $ordar)
    {
        return $this->fcmService->sendToUserOrdar(
            $ordar->user,
            'تم إلغاء طلبك',
            "تم إلغاء طلبك بنجاح ونأسف لأي إزعاج قد يكون حدث",
            [
                'ordar_id' => $ordar->id,
            ]
        );
    }

    public function getUserNotifications(User $user)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function notifictionCreateSuggestionForAdmin(Suggestion $suggestion)
    {
        $admin = User::where('is_admin', true)->first();
        if (! $admin) {
            return;
        }

        return $this->fcmService->sendToUser(
            $admin,
            'اقتراح جديد',
            "لديك اقتراح جديد من {$suggestion->user?->name}",
            [
                'suggestion_id' => $suggestion->id,
            ]
        );
    }

    public function notifictionSuggestionAcceptedForUser(Suggestion $suggestion)
    {
        if (! $suggestion->user) {
            return;
        }

        return $this->fcmService->sendToUser(
            $suggestion->user,
            'تم قبول اقتراحك',
            "تم قبول اقتراحك: {$suggestion->name}",
            [
                'suggestion_id' => $suggestion->id,
            ]
        );
    }

    public function notifictionSuggestionProvidedForUser(Suggestion $suggestion)
    {
        if (! $suggestion->user) {
            return;
        }

        return $this->fcmService->sendToUser(
            $suggestion->user,
            'تم توفير المنتج الذي اقترحته',
            "تم توفير المنتج الذي اقترحته: {$suggestion->name}",
            [
                'suggestion_id' => $suggestion->id,
            ]
        );
    }
}
