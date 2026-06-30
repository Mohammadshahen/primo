<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressService extends Service
{
    public function list(User $user): array
    {
        try {
            return [
                'success' => true,
                'data' => $user->addresses()->latest()->get(),
            ];
        } catch (\Throwable $e) {
            $this->logException($e, 'list addresses');
            return [
                'success' => false,
                'message' => 'فشل في جلب العناوين',
            ];
        }
    }

    public function create(User $user, array $data): array
    {
        try {
            DB::beginTransaction();

            $address = $user->addresses()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'location_lat' => $data['location_lat'],
                'location_lng' => $data['location_lng'],
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $address,
                'message' => 'تم إنشاء العنوان بنجاح',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logException($e, 'create address');
            return [
                'success' => false,
                'message' => 'فشل في إنشاء العنوان',
            ];
        }
    }

    public function update(Address $address, array $data): array
    {
        try {
            DB::beginTransaction();

            $address->update($data);

            DB::commit();

            return [
                'success' => true,
                'data' => $address->fresh(),
                'message' => 'تم تحديث العنوان بنجاح',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logException($e, 'update address');
            return [
                'success' => false,
                'message' => 'فشل في تحديث العنوان',
            ];
        }
    }

    public function delete(Address $address): array
    {
        try {
            DB::beginTransaction();
            $address->delete();
            DB::commit();

            return [
                'success' => true,
                'message' => 'تم حذف العنوان بنجاح',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logException($e, 'delete address');
            return [
                'success' => false,
                'message' => 'فشل في حذف العنوان',
            ];
        }
    }

    public function saveAdminAddress(array $data): array
    {
        try {
            DB::beginTransaction();

            $address = Address::updateOrCreate(
                ['user_id' => 1], // Assuming admin addresses have no associated user
                [
                    'name' => 'store_address',
                    'description' => $data['description'] ?? null,
                    'location_lat' => $data['location_lat'] ,
                    'location_lng' => $data['location_lng'],
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'data' => $address,
                'message' => 'تم حفظ عنوان الإدارة بنجاح',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->logException($e, 'save admin address');
            return [
                'success' => false,
                'message' => 'فشل في حفظ عنوان الإدارة',
            ];
        }
    }
}
