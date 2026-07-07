<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Quản lý khách hàng (US09) — tách khỏi /admin/users (quản lý tài khoản
 * admin/staff/customer nói chung). Trang này tập trung vào góc nhìn CRM:
 * tìm kiếm khách hàng, khóa/mở khóa, xem lịch sử đặt phòng của từng khách.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $query = User::where('role', 'customer');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('bookings')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search'    => $request->input('search', ''),
        ]);
    }

    public function show(int $id): View
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        $bookings = $this->bookingService->myBookings($customer, [], 10);

        return view('admin.customers.show', [
            'customer' => $customer,
            'bookings' => $bookings,
        ]);
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        if ($customer->id === Auth::id()) {
            return back()->with('error', 'Không thể khóa tài khoản của chính mình.');
        }

        $customer->update([
            'status' => $customer->status === 'active' ? 'locked' : 'active',
        ]);

        $this->auditLog->log('customer.status_toggled', $customer, "Đổi trạng thái khách hàng \"{$customer->name}\" thành \"{$customer->status}\".");

        return redirect()
            ->route('admin.customers.show', $customer->id)
            ->with('success', "Đã chuyển khách hàng \"{$customer->name}\" sang trạng thái \"{$customer->status}\".");
    }
}
