@extends('layouts.app')

@section('title', '그리드 템플릿 목록')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title">그리드 템플릿 목록</h4>
                        <p class="card-text">저장된 그리드 템플릿들을 관리할 수 있습니다.</p>
                    </div>
                    <a href="{{ route('puzzle.grid-templates.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 새 템플릿 생성
                    </a>
                </div>
                <div class="card-body">
                    @if($templates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>레벨</th>
                                        <th>템플릿 이름</th>
                                        <th>그리드 크기</th>
                                        <th>단어 수</th>
                                        <th>교차점 수</th>
                                        <th>카테고리</th>
                                        <th>생성일</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>{{ $template->id }}</td>
                                            <td>
                                                <span class="badge bg-info">레벨 {{ $template->level }}</span>
                                                <br>
                                                <small class="text-muted">{{ $template->level_name }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $template->template_name }}</strong>
                                            </td>
                                            <td>{{ $template->grid_width }}×{{ $template->grid_height }}</td>
                                            <td>
                                                <span class="badge bg-success">{{ $template->word_count }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ $template->intersection_count }}</span>
                                            </td>
                                            <td>
                                                @if($template->category === 'custom')
                                                    <span class="badge bg-primary">사용자 정의</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $template->category }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(is_string($template->created_at))
                                                    {{ \Carbon\Carbon::parse($template->created_at)->format('Y-m-d H:i') }}
                                                    <br>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($template->created_at)->diffForHumans() }}</small>
                                                @else
                                                    {{ $template->created_at->format('Y-m-d H:i') }}
                                                    <br>
                                                    <small class="text-muted">{{ $template->created_at->diffForHumans() }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('puzzle.grid-templates.show', $template->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="상세 보기">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteTemplate({{ $template->id }}, '{{ $template->template_name }}')"
                                                            title="삭제">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-puzzle-piece fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">저장된 템플릿이 없습니다</h5>
                            <p class="text-muted">새로운 그리드 템플릿을 생성해보세요.</p>
                            <a href="{{ route('puzzle.grid-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 첫 번째 템플릿 생성
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">템플릿 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>정말로 <strong id="deleteTemplateName"></strong> 템플릿을 삭제하시겠습니까?</p>
                <p class="text-danger"><small>이 작업은 되돌릴 수 없습니다.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">삭제</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let deleteTemplateId = null;

function deleteTemplate(id, name) {
    deleteTemplateId = id;
    document.getElementById('deleteTemplateName').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDelete').addEventListener('click', () => {
    if (!deleteTemplateId) return;
    
    fetch(`/puzzle/grid-templates/${deleteTemplateId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 성공 메시지 표시
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle"></i> ${data.message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // 페이지 새로고침
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('삭제 중 오류가 발생했습니다: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    })
    .finally(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
        deleteTemplateId = null;
    });
});
</script>
@endpush 