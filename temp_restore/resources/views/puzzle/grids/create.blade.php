@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">새 그리드 템플릿 생성</h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('puzzle.grids.store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="level" class="form-label">레벨 선택</label>
                            <select class="form-select @error('level') is-invalid @enderror" id="level" name="level" required>
                                <option value="">레벨을 선택하세요</option>
                                <option value="1" {{ old('level') == 1 ? 'selected' : '' }}>Level 1 - 기본 5x5 그리드 (5개 단어, 2개 교차점)</option>
                                <!-- 향후 더 많은 레벨 추가 예정 -->
                            </select>
                            @error('level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> 레벨 1 그리드 정보</h6>
                            <ul class="mb-0">
                                <li><strong>크기:</strong> 5×5 그리드</li>
                                <li><strong>단어 수:</strong> 5개 (모두 5글자)</li>
                                <li><strong>교차점:</strong> 2개</li>
                                <li><strong>난이도:</strong> 초급</li>
                                <li><strong>특징:</strong> 가로 3개, 세로 2개 단어 배치</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('puzzle.grids.index') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left"></i> 목록으로
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> 그리드 생성
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 