<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuggestionRequest;
use App\Http\Requests\suggestionRequests\ChangeSuggestionStatusRequest;
use App\Services\SuggestionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SuggestionController extends Controller
{
    private SuggestionService $service;

    public function __construct(SuggestionService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a new suggestion (user route).
     *
     * @param StoreSuggestionRequest $request
     * @return JsonResponse
     */
    public function store(StoreSuggestionRequest $request): JsonResponse
    {
        $user = $request->user();

        $suggestion = $this->service->createSuggestion($request->validated(), $user);

        return response()->json(['data' => $suggestion], 201);
    }

    /**
     * Admin: change suggestion status.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(ChangeSuggestionStatusRequest $request, int $id): JsonResponse
    {
        $suggestion = $this->service->changeStatus($id, $request->validated()['status']);

        return response()->json(['data' => $suggestion]);
    }
}
