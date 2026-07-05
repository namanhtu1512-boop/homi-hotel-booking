<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\BannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(): View
    {
        return view('admin.banners.index', ['banners' => $this->bannerService->list()]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBanner($request);

        $banner = $this->bannerService->create($data);

        $this->auditLog->log('banner.created', $banner, "Tạo banner \"{$banner->title}\".");

        return redirect()->route('admin.banners.index')->with('success', "Đã tạo banner \"{$banner->title}\".");
    }

    public function edit(int $id): View
    {
        return view('admin.banners.form', ['banner' => $this->bannerService->find($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $banner = $this->bannerService->find($id);
        $data = $this->validateBanner($request, requireImage: false);

        $this->bannerService->update($banner, $data);

        $this->auditLog->log('banner.updated', $banner->fresh(), "Cập nhật banner \"{$banner->title}\".");

        return redirect()->route('admin.banners.index')->with('success', "Đã cập nhật banner \"{$banner->title}\".");
    }

    public function destroy(int $id): RedirectResponse
    {
        $banner = $this->bannerService->find($id);
        $title = $banner->title;

        $this->bannerService->delete($banner);

        $this->auditLog->log('banner.deleted', null, "Xóa banner \"{$title}\".");

        return redirect()->route('admin.banners.index')->with('success', "Đã xóa banner \"{$title}\".");
    }

    private function validateBanner(Request $request, bool $requireImage = true): array
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'subtitle'   => ['nullable', 'string', 'max:255'],
            'image_url'  => ['nullable', 'string', 'max:2000'],
            'image_file' => [$requireImage ? 'required_without:image_url' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'link_url'   => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status'     => ['required', 'in:active,hidden'],
        ], [], [
            'title'      => 'tiêu đề',
            'subtitle'   => 'phụ đề',
            'image_url'  => 'đường dẫn ảnh',
            'image_file' => 'file ảnh',
            'link_url'   => 'liên kết',
            'sort_order' => 'thứ tự',
            'status'     => 'trạng thái',
        ]);

        if ($request->hasFile('image_file')) {
            $data['image_path'] = $request->file('image_file')->store('banners', 'public');
        } elseif (! empty($data['image_url'])) {
            $data['image_path'] = $data['image_url'];
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;

        unset($data['image_url'], $data['image_file']);

        return $data;
    }
}
