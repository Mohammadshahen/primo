<?php

namespace App\Services;

use App\Models\Suggestion;
use App\Models\User;
use Exception;

class SuggestionService extends Service
{
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
            return Suggestion::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'user_id' => $user->id,
            ]);
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
            return $suggestion;
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' changeStatus');
            $this->throwExceptionJson('فشل تغيير حالة الاقتراح', 500);
        }
    }
}
