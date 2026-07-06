<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Chat hỗ trợ giữa khách hàng và admin/staff — hộp thư dùng chung (bất kỳ
 * admin/staff nào cũng thấy và trả lời được mọi hội thoại), không phải
 * chat 1-1 riêng theo từng nhân viên. Không có model Conversation riêng —
 * 1 hội thoại = mọi ChatMessage cùng customer_id.
 */
class ChatService
{
    public function messagesForCustomer(int $customerId, ?int $afterId = null): Collection
    {
        return ChatMessage::with('sender')
            ->where('customer_id', $customerId)
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();
    }

    public function send(int $customerId, User $sender, string $body): ChatMessage
    {
        return ChatMessage::create([
            'customer_id' => $customerId,
            'sender_id'   => $sender->id,
            'body'        => $body,
        ]);
    }

    /**
     * Khách đọc các tin nhân viên/admin đã gửi cho mình.
     */
    public function markReadForCustomer(int $customerId): void
    {
        ChatMessage::where('customer_id', $customerId)
            ->where('sender_id', '!=', $customerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Nhân viên/admin đọc các tin khách đã gửi (hộp thư dùng chung — bất kỳ
     * ai đọc cũng coi như cả hộp thư đã đọc, giống hộp mail hỗ trợ chung).
     */
    public function markReadForStaff(int $customerId): void
    {
        ChatMessage::where('customer_id', $customerId)
            ->where('sender_id', $customerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCountForCustomer(int $customerId): int
    {
        return ChatMessage::where('customer_id', $customerId)
            ->where('sender_id', '!=', $customerId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Tổng số tin khách gửi mà admin/staff chưa đọc, tính trên mọi hội
     * thoại — dùng cho badge trên nav admin/staff.
     */
    public function unreadCountForStaff(): int
    {
        return ChatMessage::whereNull('read_at')
            ->whereColumn('sender_id', 'customer_id')
            ->count();
    }

    /**
     * Danh sách khách đã có hội thoại, kèm tin nhắn mới nhất + số tin
     * khách gửi mà admin/staff chưa đọc, sắp theo tin mới nhất trước.
     *
     * @return SupportCollection<int, array{customer: User, last_message: ChatMessage, unread_count: int}>
     */
    public function inboxList(): SupportCollection
    {
        $customerIds = ChatMessage::select('customer_id')
            ->selectRaw('MAX(id) as last_message_id')
            ->groupBy('customer_id')
            ->orderByDesc('last_message_id')
            ->pluck('last_message_id', 'customer_id');

        $lastMessages = ChatMessage::with('sender')
            ->whereIn('id', $customerIds->values())
            ->get()
            ->keyBy('customer_id');

        $customers = User::whereIn('id', $customerIds->keys())->get()->keyBy('id');

        return $customerIds->keys()
            ->map(fn (int $customerId) => [
                'customer'     => $customers->get($customerId),
                'last_message' => $lastMessages->get($customerId),
                'unread_count' => ChatMessage::where('customer_id', $customerId)
                    ->where('sender_id', $customerId)
                    ->whereNull('read_at')
                    ->count(),
            ])
            ->values();
    }
}
