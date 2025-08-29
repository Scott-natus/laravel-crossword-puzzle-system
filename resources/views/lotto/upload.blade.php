@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-upload"></i> 로또 용지 업로드
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('lotto.store') }}" enctype="multipart/form-data" id="lottoForm">
                        @csrf
                        
                        <!-- 이미지 업로드 -->
                        <div class="mb-4">
                            <label for="image" class="form-label">
                                <i class="fas fa-image"></i> 로또 용지 이미지
                            </label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                id="image" name="image" accept="image/*" required>
                            <div class="form-text">
                                로또 용지 사진을 업로드해주세요. (JPG, PNG, GIF 형식, 최대 2MB)
                            </div>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 번호 선택 -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-list-ol"></i> 선택한 번호 (6개)
                            </label>
                            <div class="row">
                                @for($i = 1; $i <= 6; $i++)
                                    <div class="col-md-2 mb-2">
                                        <select class="form-select @error('numbers.' . ($i-1)) is-invalid @enderror" 
                                            name="numbers[]" required>
                                            <option value="">번호 선택</option>
                                            @for($num = 1; $num <= 45; $num++)
                                                <option value="{{ $num }}" {{ old('numbers.' . ($i-1)) == $num ? 'selected' : '' }}>
                                                    {{ $num }}
                                                </option>
                                            @endfor
                                        </select>
                                        @error('numbers.' . ($i-1))
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endfor
                            </div>
                            <div class="form-text">
                                1부터 45까지의 번호 중 6개를 선택해주세요.
                            </div>
                        </div>

                        <!-- 선택된 번호 미리보기 -->
                        <div class="mb-4">
                            <label class="form-label">선택된 번호 미리보기</label>
                            <div id="selectedNumbers" class="d-flex flex-wrap gap-2">
                                <span class="text-muted">번호를 선택하면 여기에 표시됩니다.</span>
                            </div>
                        </div>

                        <!-- 똥손력 예측 -->
                        <div class="mb-4">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-magic"></i> 똥손력 계산 예시
                                </h6>
                                <ul class="mb-0">
                                    <li>연속된 번호: +10점씩</li>
                                    <li>끝자리 같은 번호: +15점씩</li>
                                    <li>기본 똥손력: 20~50점</li>
                                    <li>최대 똥손력: 100점</li>
                                </ul>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('lotto.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 돌아가기
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> 업로드하기
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const numberSelects = document.querySelectorAll('select[name="numbers[]"]');
    const selectedNumbersDiv = document.getElementById('selectedNumbers');
    
    function updateSelectedNumbers() {
        const selectedNumbers = [];
        numberSelects.forEach(select => {
            if (select.value) {
                selectedNumbers.push(select.value);
            }
        });
        
        if (selectedNumbers.length > 0) {
            selectedNumbersDiv.innerHTML = selectedNumbers.map(num => 
                `<span class="badge bg-primary fs-6">${num}</span>`
            ).join('');
        } else {
            selectedNumbersDiv.innerHTML = '<span class="text-muted">번호를 선택하면 여기에 표시됩니다.</span>';
        }
    }
    
    numberSelects.forEach(select => {
        select.addEventListener('change', updateSelectedNumbers);
    });
    
    // 폼 제출 전 유효성 검사
    document.getElementById('lottoForm').addEventListener('submit', function(e) {
        const selectedNumbers = [];
        numberSelects.forEach(select => {
            if (select.value) {
                selectedNumbers.push(select.value);
            }
        });
        
        // 중복 번호 체크
        const uniqueNumbers = [...new Set(selectedNumbers)];
        if (selectedNumbers.length !== uniqueNumbers.length) {
            e.preventDefault();
            alert('중복된 번호가 있습니다. 다른 번호를 선택해주세요.');
            return;
        }
        
        // 6개 번호 체크
        if (selectedNumbers.length !== 6) {
            e.preventDefault();
            alert('정확히 6개의 번호를 선택해주세요.');
            return;
        }
    });
    
    // 초기 상태 업데이트
    updateSelectedNumbers();
});
</script>
@endpush

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 1em;
    padding: 0.5em 0.75em;
}

.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
@endsection
