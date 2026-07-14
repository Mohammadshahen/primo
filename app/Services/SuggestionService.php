<?php

namespace App\Services;

use App\Models\Suggestion;
use App\Models\User;
use App\Services\NotificationService;
use Exception;

class SuggestionService extends Service
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Create a suggestion for a user.
     *
     * @param array $data
     * @param User $user
     * @return Suggestion
     * @throws Exception
     */
    public function createSuggestion(array $data, User $user): Suggestion
    {
        try { 
            $suggestion = Suggestion::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'user_id' => $user->id,
            ]);

            // Notify admins about the new suggestion
            try {
                $this->notificationService->notifictionCreateSuggestionForAdmin($suggestion);
            } catch (Exception $e) {
                // Log but don't fail the request if notification sending fails
                $this->logException($e, __METHOD__ . ' notify admins on suggestion create');
            }

            return $suggestion;
        } catch (Exception $e) {
          $this->logException($e, __METHOD__ . ' createSuggestion');
          $this->throwExceptionJson('فشل إنشاء الاقتراح', 500);
        }
    }

    /**
     * Change suggestion status.
     *
     * @param int $id
     * @param string $status
     * @return Suggestion
     * @throws Exception
     */
    public function changeStatus(int $id, string $status): Suggestion
    {
        try {
            $suggestion = Suggestion::findOrFail($id);
            $suggestion->status = $status;
            $suggestion->save();

            // If admin accepted the suggestion, notify the suggestion owner
            if ($status === 'approved') {
                try {
                    $this->notificationService->notifictionSuggestionAcceptedForUser($suggestion);
                } catch (Exception $e) {
                    $this->logException($e, __METHOD__ . ' notify user on suggestion accepted');
                }
            }

            return $suggestion;
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' changeStatus');
            $this->throwExceptionJson('فشل تغيير حالة الاقتراح', 500);
        }
    }

    /**
     * Get all suggestions for admin.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSuggestions()
    {
        try {
            return Suggestion::with('user')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' getAllSuggestions');
            $this->throwExceptionJson('فشل جلب الاقتراحات', 500);
        }
    }

    /**
     * Get a single suggestion by id.
     *
     * @param int $id
     * @return Suggestion
     */
    public function getSuggestionById(int $id): Suggestion
    {
        try {
            return Suggestion::with('user')->findOrFail($id);
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' getSuggestionById');
            $this->throwExceptionJson('فشل جلب الاقتراح', 500);
        }
    }
}
