<tr>
    <td style="padding-left: {{ $depth * 24 }}px; position: relative;">
        @if($depth > 0)
            <span style="position:absolute; left:{{ ($depth-1)*24 }}px; top:8px; color:#888; font-size:18px;">&#x21B3;</span>
        @endif
        <a href="{{ route('board.show', ['boardType' => $board->boardType->slug, 'board' => $board->id]) }}" class="fw-semibold text-decoration-none">
            {{ $board->title }}
            @if($board->comments->count() > 0)
                <span class="text-primary">({{ $board->comments->count() }})</span>
            @endif
        </a>
    </td>
    <td>{{ $board->user->name ?? '알 수 없음' }}</td>
    <td>{{ $board->created_at->format('y-m-d H:i:s') }}</td>
    <td class="text-center">
        @if($board->attachments->count() > 0)
            <span title="첨부파일 있음">📎 {{ $board->attachments->count() }}</span>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    <td class="text-end">{{ $board->views }}</td>
</tr> 