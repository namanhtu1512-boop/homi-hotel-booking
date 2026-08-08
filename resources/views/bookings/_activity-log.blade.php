<div class="section-kicker" style="margin-top: 22px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
    <span>Nhật ký thao tác</span>
    @if ($chatRoute)
        <a href="{{ $chatRoute }}" class="btn btn-outline btn-sm">Xem chat với khách này</a>
    @endif
</div>

@if ($timeline->isEmpty())
    <div class="empty-box" style="margin-top: 10px;">Chưa có thao tác nào được ghi nhận trên đơn này.</div>
@else
    <div class="table-wrapper" style="margin-top: 10px;">
        <table>
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Người thực hiện</th>
                    <th>Nội dung</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($timeline as $entry)
                    <tr>
                        <td>{{ $entry['at']->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                        <td>{{ $entry['actor'] }}</td>
                        <td>
                            {{ $entry['label'] }}
                            @if ($entry['note'])
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $entry['note'] }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
