<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieRequests\StoreCategorieRequest;
use App\Http\Requests\CategorieRequests\UpdateCategorieRequest;
use App\Models\Categorie;
use App\Services\CategorieService;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    protected CategorieService $service;

    public function __construct(CategorieService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $categories = $this->service->list();

        return $this->success($categories, 'تم جلب الفئات بنجاح');
    }

    public function store(StoreCategorieRequest $request)
    {
        $result = $this->service->create($request->validated());

        if (! $result['success']) {
            return $this->error($result['message'] ?? 'فشل إنشاء الفئة', 400);
        }

        return $this->success($result['data'], 'تم إنشاء الفئة بنجاح', 201);
    }

    public function show(Categorie $category)
    {
        return $this->success($category, 'تم جلب بيانات الفئة بنجاح');
    }

    public function update(UpdateCategorieRequest $request, Categorie $category)
    {
        $result = $this->service->update($category, $request->validated());

        if (! $result['success']) {
            return $this->error($result['message'], 404);
        }

        return $this->success($result['data'], 'تم تحديث الفئة بنجاح', 201);
    }

    public function destroy(Categorie $category)
    {
        $result = $this->service->delete($category);

        if (! $result['success']) {
            return $this->error($result['message'], 404);
        }

        return $this->success([], 'تم حذف الفئة بنجاح');
    }

    public function userGitAllGategories()
    {
        $categories = $this->service->listForUser();
        return $this->success($categories, 'تم جلب الأقسام للمستخدم بنجاح');
    }
}
