<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\NewContactMessageReceived;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactMessageService
{
    public function create(array $data): ContactMessage
    {
        $message = ContactMessage::create($data);

        // Thông báo cho admin về liên hệ mới — trước đây tin nhắn chỉ nằm im
        // trong DB, không ai biết để phản hồi khách (xem NewBookingReceived,
        // NewGroupBookingRequest để biết pattern tương tự).
        User::where('role', 'admin')->each(
            fn (User $u) => $u->notify(new NewContactMessageReceived($message))
        );

        return $message;
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
