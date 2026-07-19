<?php

namespace App\Services;

use App\Models\Categorie;
use Exception;

class CategorieService extends Service
{
    public function list()
    {
        return Categorie::select('id', 'name', 'image')->get();
    }

    public function listForUser()
    {
        return $this->list();
    }

    public function create(array $data): array
    {
        try {

            $categorie = Categorie::create([
                'name' => $data['name'],
                'image' => FileStorage::storeFile($data['image'], 'categories', 'img') ?? null,
            ]);

            return [
                'success' => true,
                'data' => $categorie,
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' create');

            return [
                'success' => false,
                'message' => 'فشل إنشاء الفئة',
            ];
        }
    }

    public function update(Categorie $categorie, array $data): array
    {

        try {

            $categorie->update([
                'name' => $data['name'] ?? $categorie->name,
                'image' => isset($data['image']) ? FileStorage::fileExists($data['image'], $categorie->image, 'categories', 'img') : $categorie->image
            ]);

            return [
                'success' => true,
                'data' => $categorie,
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' update');

            return [
                'success' => false,
                'message' => 'فشل تحديث الفئة',
            ];
        }
    }

    public function delete(Categorie $category): array
    {
        try {
            if ($category->image) {
                FileStorage::deleteFile($category->image);
            }

            $category->delete();

            return [
                'success' => true,
                'data' => [],
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' delete');
            $this->throwExceptionJson('فشل حذف الفئة', 500);
        }
    }
}
