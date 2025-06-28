import { useState, useEffect, useCallback } from 'react';
import { puzzleApi } from '../services/puzzleApi';

export const usePuzzleGame = (levelId) => {
    const [puzzle, setPuzzle] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [sessionToken, setSessionToken] = useState(null);

    // 퍼즐 로드
    const loadPuzzle = useCallback(async () => {
        if (!levelId) return;

        try {
            setLoading(true);
            setError(null);

            const response = await puzzleApi.generatePuzzle(levelId);
            
            if (response.success) {
                setPuzzle(response.data);
                setSessionToken(response.data.session_token);
            } else {
                setError(response.message || '퍼즐을 불러오는데 실패했습니다.');
            }
        } catch (err) {
            console.error('퍼즐 로드 오류:', err);
            setError('퍼즐을 불러오는데 실패했습니다.');
        } finally {
            setLoading(false);
        }
    }, [levelId]);

    // 답안 제출
    const submitAnswer = useCallback(async (wordId, answer) => {
        if (!sessionToken) return false;

        try {
            const response = await puzzleApi.submitAnswer(sessionToken, wordId, answer);
            return response.success;
        } catch (err) {
            console.error('답안 제출 오류:', err);
            return false;
        }
    }, [sessionToken]);

    // 완료 확인
    const checkCompletion = useCallback(async () => {
        if (!sessionToken) return false;

        try {
            const response = await puzzleApi.checkCompletion(sessionToken);
            return response.success && response.data.completed;
        } catch (err) {
            console.error('완료 확인 오류:', err);
            return false;
        }
    }, [sessionToken]);

    // 게임 상태 저장
    const saveGameState = useCallback(async (gameState) => {
        if (!sessionToken) return false;

        try {
            const response = await puzzleApi.saveGameState(sessionToken, gameState);
            return response.success;
        } catch (err) {
            console.error('게임 상태 저장 오류:', err);
            return false;
        }
    }, [sessionToken]);

    // 게임 결과 제출
    const submitGameResult = useCallback(async (result) => {
        if (!sessionToken) return false;

        try {
            const response = await puzzleApi.submitGameResult(sessionToken, result);
            return response.success;
        } catch (err) {
            console.error('게임 결과 제출 오류:', err);
            return false;
        }
    }, [sessionToken]);

    // 초기 로드
    useEffect(() => {
        loadPuzzle();
    }, [loadPuzzle]);

    return {
        puzzle,
        loading,
        error,
        sessionToken,
        submitAnswer,
        checkCompletion,
        saveGameState,
        submitGameResult,
        reloadPuzzle: loadPuzzle
    };
}; 