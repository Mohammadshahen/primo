<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\DB;

class SettingService extends Service
{
    public function updateDeliveryPrice(float $price)
    {
        try {
            DB::beginTransaction();

            Setting::setValue('delivery_price', $price);

            DB::commit();

            return ['delivery_price' => $price];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' updateDeliveryPrice');
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث سعر التوصيل: ' . $e->getMessage(),
            ];
        }
    }
}
