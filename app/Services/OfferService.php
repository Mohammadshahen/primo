<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Variant;
use Exception;
use Illuminate\Support\Facades\DB;

class OfferService extends Service
{
    protected function normalizeDiscountData(array $data): array
    {
        if (array_key_exists('discount_percentage', $data) && !is_null($data['discount_percentage'])) {
            $variantId = $data['variant_id'] ?? null;
            $variant = $variantId ? Variant::find($variantId) : null;

            if (!$variant) {
                return $data;
            }

            $data['discount_value'] = round(
                (float) $variant->price * ((float) $data['discount_percentage'] / 100),
                2
            );
        }

        return $data;
    }

    public function list()
    {
        return Offer::with('variant.product:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function show(Offer $offer)
    {
        return $offer->load('variant.product:id,name');
    }

    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            $data = $this->normalizeDiscountData($data);

            $offer = Offer::create([
                'variant_id' => $data['variant_id'],
                'from' => $data['from'],
                'to' => $data['to'],
                'discount_percentage' => $data['discount_percentage'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $offer->load('variant.product:id,name'),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' create');

            $this->throwExceptionJson('فشل إنشاء العرض', 500);
        }
    }

    public function update(Offer $offer, array $data): array
    {

        $from = $data['from'] ?? $offer->from;
        $to = $data['to'] ?? $offer->to;
        if ($from > $to) {
            return $this->throwExceptionJson('تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية.', 422);
        }
        try {
            DB::beginTransaction();

            $variantId = $data['variant_id'] ?? $offer->variant_id;
            $data['variant_id'] = $variantId;
            if (isset($data['discount_value'])) {
                $discount_percentage = null;
            } else {
                $discount_percentage = $data['discount_percentage'] ?? $offer->discount_percentage;
            }
            if (isset($data['discount_percentage'])) {
                $data = $this->normalizeDiscountData($data);
            }

            $offer->update([
                'variant_id' => $data['variant_id'],
                'from' => $data['from'] ?? $offer->from,
                'to' => $data['to'] ?? $offer->to,
                'discount_percentage' => $discount_percentage,
                'discount_value' => array_key_exists('discount_value', $data)
                    ? $data['discount_value']
                    : $offer->discount_value,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $offer->load('variant.product:id,name'),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' update');

            $this->throwExceptionJson('فشل تحديث العرض', 500);
        }
    }

    public function delete(Offer $offer): array
    {
        try {
            $offer->delete();

            return [
                'success' => true,
                'data' => [],
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' delete');

            $this->throwExceptionJson('فشل حذف العرض', 500);
        }
    }
}
