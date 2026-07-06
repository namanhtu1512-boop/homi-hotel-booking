@extends($layout)

@section('title', 'Chat khách hàng · Homi')
@section('page_title', 'Chat khách hàng')
@section('page_subtitle', 'Hộp thư dùng chung — bất kỳ admin/staff nào cũng thấy và trả lời được mọi hội thoại.')

@section('content')
<div class="card">
    @if ($inbox->isEmpty())
        <div class="empty-box">Chưa có khách hàng nào nhắn tin.</div>
    @else
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Khách hàng</th>
                        <th>Tin nhắn gần nhất</th>
                        <th>Thời gian</th>
                        <th>Chưa đọc</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inbox as $row)
                        <tr>
                            <td>{{ $row['customer']->name ?? 'Khách #' . $row['customer']?->id }}</td>
                            <td style="max-width: 360px;">{{ \Illuminate\Support\Str::limit($row['last_message']->body ?? '', 80) }}</td>
                            <td>{{ $row['last_message']?->created_at->format('H:i d/m') }}</td>
                            <td>
                                @if ($row['unread_count'] > 0)
                                    <span class="badge badge-orange">{{ $row['unread_count'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <a href="{{ route($showRoute, $row['customer']->id) }}" class="btn btn-outline btn-sm">Mở hội thoại</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
