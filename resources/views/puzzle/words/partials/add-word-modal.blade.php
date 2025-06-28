<!-- 단어 추가 모달 -->
<div class="modal fade" id="addWordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">단어 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addWordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category" class="form-label">카테고리</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               maxlength="50" required>
                        <div class="form-text">5글자 이하로 입력하세요.</div>
                    </div>
                    <div class="mb-3">
                        <label for="word" class="form-label">단어</label>
                        <input type="text" class="form-control" id="word" name="word" 
                               maxlength="50" required>
                        <div class="form-text">5글자 이하로 입력하세요.</div>
                    </div>
                    <div class="mb-3">
                        <label for="difficulty" class="form-label">난이도</label>
                        <select class="form-select" id="difficulty" name="difficulty" required>
                            <option value="">난이도 선택</option>
                            <option value="1">쉬움</option>
                            <option value="2">보통</option>
                            <option value="3">어려움</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">입력</button>
                </div>
            </form>
        </div>
    </div>
</div> 