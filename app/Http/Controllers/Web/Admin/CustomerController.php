<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Màn quản lý khách hàng riêng biệt với /admin/users (quản lý tài khoản
 * admin/staff/customer nói chung) — US09: admin tìm kiếm, lọc khách hàng và
 * xem lịch sử đặt phòng của từng khách. Khóa/mở khóa tài khoản cũng được
 * xử lý trực tiếp tại đây.
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $query = User::where('role', 'customer')
            ->withCount('bookings');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search'    => $request->input('search', ''),
            'status'    => $request->input('status', ''),
        ]);
    }

    public function show(int $id): View
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        $bookings = $customer->bookings()
            ->with(['bookingItems.roomType', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

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
