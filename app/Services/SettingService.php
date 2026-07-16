<?php

namespace App\Services;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\DB;

class SettingService extends Service
{
    public function updateDeliveryPrice(float $price): array
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

    public function getDeliveryPrice(): float
    {
        try {
            return (float) Setting::getValue('delivery_price', 0.0);
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' getDeliveryPrice');
            $this->throwExceptionJson('فشل في جلب سعر التوصيل', 500);
        }
    }

    public function updateDollarValue(float $value): array
    {
        try {
            DB::beginTransaction();

            Setting::setValue('dollar_value', $value);

            DB::commit();

            return [
                'dollar_value' => $value,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' updateDollarValue');
            $this->throwExceptionJson('فشل في تحديث قيمة الدولار', 500);
        }
    }

    public function getDollarValue(): float
    {
        try {
            return (float) Setting::getValue('dollar_value', 1.0);
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' getDollarValue');
            $this->throwExceptionJson('فشل في جلب قيمة الدولار', 500);
        }
    }

    /**
     * Update or create general settings (admin).
     * Returns array of saved key => value pairs or error structure on failure.
     *
     * @param array $data
     * @return array
     */
    public function updateGeneralSettings(array $data): array
    {
        try {
            DB::beginTransaction();

            $keys = [
                'facebook_account',
                'admin_phone',
                'customer_service_phone',
                'working_hours',
                'location',
                'dollar_value',
            ];

            $result = [];

            foreach ($keys as $key) {
                if (array_key_exists($key, $data)) {
                    Setting::setValue($key, $data[$key]);
                    $result[$key] = $data[$key];
                }
            }

            DB::commit();

            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' updateGeneralSettings');
            $this->throwExceptionJson('فشل في تحديث الإعدادات العامة', 500);
        }
    }

    /**
     * Get general settings for user.
     *
     * @return array
     */
    public function getGeneralSettings(): array
    {
        try {
            $keys = [
                'facebook_account',
                'admin_phone',
                'customer_service_phone',
                'working_hours',
                'location',
                'dollar_value',
            ];

            $data = [];
            foreach ($keys as $key) {
                $data[$key] = Setting::getValue($key, null);
            }

            return $data;
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' getGeneralSettings');
            $this->throwExceptionJson('فشل في جلب الإعدادات العامة', 500);
        }
    }
}
