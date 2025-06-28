import axios from 'axios';

// API 기본 설정
const api = axios.create({
    // baseURL: 'http://222.100.103.227:5050/api',
    baseURL: 'http://localhost:5050/api', // 임시로 로컬 서버 사용
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
    },
});

export const puzzleApi = {
    async generatePuzzle(levelId) {
        try {
            // 이제 경로는 baseURL 뒤에 붙습니다: http://.../api/puzzle/generate
            const res = await api.post('/puzzle/generate', { level_id: levelId });
            return res.data;
        } catch (err) {
            return { success: false, message: err?.response?.data?.message || err.message };
        }
    },
    async submitAnswer(sessionToken, wordId, answer) {
        try {
            const res = await api.post('/puzzle/submit-answer', {
                session_token: sessionToken,
                word_id: wordId,
                answer
            });
            return res.data;
        } catch (err) {
            return { success: false, message: err?.response?.data?.message || err.message };
        }
    },
    async getHint(sessionToken, wordId, hintType = 'text') {
        try {
            const res = await api.post('/puzzle/hint', {
                session_token: sessionToken,
                word_id: wordId,
                hint_type: hintType
            });
            return res.data;
        } catch (err) {
            return { success: false, message: err?.response?.data?.message || err.message };
        }
    },
    async checkCompletion(sessionToken) {
        try {
            const res = await api.post('/puzzle/check-completion', {
                session_token: sessionToken
            });
            return res.data;
        } catch (err) {
            return { success: false, message: err?.response?.data?.message || err.message };
        }
    },
    async saveGameState(sessionToken, gameState) {
        try {
            const res = await api.post('/puzzle/save-state', {
                session_token: sessionToken,
                game_state: gameState
            });
            return res.data;
        } catch (err) {
            return { success: false, message: err?.response?.data?.message || err.message };
        }
    },
    async submitGameResult(sessionToken, result) {
        try {
            const res = await api.post('/puzzle/submit-result', {
                session_token: sessionToken,
                ...result
            });
            return res.data;
        } catch (err) {
            return { success: false, message: err?.response?.data?.message || err.message };
        }
    },
    async getHintLimits(levelId) {
        // 이 함수는 아직 백엔드에 구현되지 않았을 수 있으므로, 임시 데이터를 반환합니다.
        // 만약 실제 API가 있다면 아래 주석을 해제하세요.
        // const res = await api.get(`/puzzle/hint-limits?level_id=${levelId}`);
        // return res.data;
        return { success: true, data: { text: { max_uses_per_game: 3, cost_per_use: 10, cooldown_seconds: 10 } } };
    }
}; 