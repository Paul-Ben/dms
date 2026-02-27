<?php

namespace App\Helpers;

use App\Models\VisitorActivity;

class ViewDetector
{
    public static function hasBeenViewed(int $fileMovementId, int $recipientId): bool
    {
        $pattern = '%/document/document/' . $fileMovementId . '/view%';
        return VisitorActivity::where('user_id', $recipientId)
            ->where('url', 'like', $pattern)
            ->exists();
    }

    public static function firstViewedAt(int $fileMovementId, int $recipientId): ?string
    {
        $pattern = '%/document/document/' . $fileMovementId . '/view%';
        $row = VisitorActivity::where('user_id', $recipientId)
            ->where('url', 'like', $pattern)
            ->orderBy('created_at', 'asc')
            ->first(['created_at']);
        return optional($row)->created_at?->toDateTimeString();
    }
}
