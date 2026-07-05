<?php

namespace App\Services;

use App\Models\ContactMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    public function create(array $data): ContactMessage
    {
        return ContactMessage::create($data);
    }

    public function adminList(array $filters = []): LengthAwarePaginator
    {
        $query = ContactMessage::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15)->withQueryString();
    }

    public function markRead(ContactMessage $message): ContactMessage
    {
        $message->update(['status' => 'read']);

        return $message->fresh();
    }

    public function delete(ContactMessage $message): void
    {
        $message->delete();
    }
}
