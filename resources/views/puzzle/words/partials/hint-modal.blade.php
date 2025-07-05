<!-- 힌트 추가/수정 모달 -->
<div class="modal fade" id="hintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hintModalTitle">힌트 추가</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="hintForm">
                <div class="modal-body">
                    <input type="hidden" id="hintWordId" name="word_id">
                    <input type="hidden" id="hintId" name="hint_id">
                    
                    <div class="mb-3">
                        <label for="hintType" class="form-label">힌트 타입</label>
                        <select class="form-select" id="hintType" name="type" required>
                            <option value="">선택하세요</option>
                            <option value="text">텍스트</option>
                            <option value="image">이미지</option>
                            <option value="sound">사운드</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hintDifficulty" class="form-label">난이도</label>
                        <select class="form-select" id="hintDifficulty" name="difficulty" required>
                            <option value="">선택하세요</option>
                            <option value="1">쉬움</option>
                            <option value="2">보통</option>
                            <option value="3">어려움</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="textHintSection" style="display: none;">
                        <label for="hintContent" class="form-label">힌트 내용</label>
                        <textarea class="form-control" id="hintContent" name="content" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3" id="fileHintSection" style="display: none;">
                        <label for="hintFile" class="form-label">파일 업로드</label>
                        <input type="file" class="form-control" id="hintFile" name="file">
                        <div class="form-text">이미지: JPG, PNG, GIF / 사운드: MP3, WAV (최대 10MB)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div> 