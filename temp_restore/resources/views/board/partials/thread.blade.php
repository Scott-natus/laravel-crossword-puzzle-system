<li class="list-group-item" style="margin-left: {{ $depth * 24 }}px; position: relative; @if($currentId == $post->id) background: #e9f7ef; @endif">
    @if($depth > 0)
        <span style="position:absolute; left:-18px; top:10px; color:#888; font-size:18px;">&#x21B3;</span>
    @endif
    <a href="{{ route('board.show', ['boardType' => $post->boardType->slug, 'board' => $post->id]) }}" class="fw-semibold text-decoration-none @if($currentId == $post->id) text-success @endif">
        {{ $post->title }}
    </a>
    <span class="text-muted small">by {{ $post->user->name ?? '알 수 없음' }} | {{ $post->created_at->format('Y-m-d H:i') }}</span>
    @if($post->children->count())
        <ul class="list-group mt-2">
            @foreach($post->children as $child)
                @include('board.partials.thread', ['post' => $child, 'currentId' => $currentId, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li> 