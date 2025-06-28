import { useState, useEffect, useCallback } from 'react';
import { puzzleApi } from '../services/puzzleApi';

export const useHintSystem = (levelId, sessionToken, hintsUsed = 0) => {
    const [hints, setHints] = useState({});
    const [hintCooldowns, setHintCooldowns] = useState({});
    const [hintLimits, setHintLimits] = useState(null);
    const [loading, setLoading] = useState(true);

    // 힌트 제한 정보 로드
    const loadHintLimits = useCallback(async () => {
        if (!levelId) return;

        try {
            setLoading(true);
            const response = await puzzleApi.getHintLimits(levelId);
            
            if (response.success) {
                setHintLimits(response.data);
            }
        } catch (err) {
            console.error('힌트 제한 로드 오류:', err);
        } finally {
            setLoading(false);
        }
    }, [levelId]);

    // 힌트 사용 가능 여부 확인
    const canUseHint = useCallback((hintType) => {
        const limit = hintLimits?.[hintType];
        if (!limit) return false;

        const currentUses = hintsUsed;
        const cooldown = hintCooldowns[hintType] || 0;
        const now = Date.now();

        return currentUses < limit.max_uses_per_game && now >= cooldown;
    }, [hintLimits, hintsUsed, hintCooldowns]);

    // 힌트 사용
    const activateHintCooldown = useCallback((hintType) => {
        if (!canUseHint(hintType)) return false;

        const limit = hintLimits[hintType];
        if (!limit) return false;

        // 쿨다운 설정
        const cooldownEnd = Date.now() + (limit.cooldown_seconds * 1000);
        setHintCooldowns(prev => ({
            ...prev,
            [hintType]: cooldownEnd
        }));

        return true;
    }, [canUseHint, hintLimits]);

    // 힌트 가져오기
    const getHint = useCallback(async (wordId, hintType = 'text') => {
        if (!canUseHint(hintType)) {
            throw new Error('힌트를 사용할 수 없습니다.');
        }
        if (!sessionToken) {
            throw new Error('게임 세션이 없습니다.');
        }

        try {
            const response = await puzzleApi.getHint(sessionToken, wordId, hintType);
            
            if (response.success) {
                activateHintCooldown(hintType);
                setHints(prev => ({
                    ...prev,
                    [wordId]: { ...prev[wordId], [hintType]: response.data.hint }
                }));
                return response.data.hint;
            } else {
                throw new Error(response.message || '힌트를 가져오는데 실패했습니다.');
            }
        } catch (err) {
            console.error('힌트 가져오기 오류:', err);
            throw err;
        }
    }, [canUseHint, activateHintCooldown, sessionToken]);

    // 쿨다운 업데이트
    useEffect(() => {
        const interval = setInterval(() => {
            const now = Date.now();
            let updated = false;

            const newCooldowns = { ...hintCooldowns };
            
            Object.keys(newCooldowns).forEach(hintType => {
                if (newCooldowns[hintType] <= now) {
                    delete newCooldowns[hintType];
                    updated = true;
                }
            });

            if (updated) {
                setHintCooldowns(newCooldowns);
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [hintCooldowns]);

    // 초기 로드
    useEffect(() => {
        loadHintLimits();
    }, [loadHintLimits]);

    // 힌트 사용 횟수 변경 시 재로드
    useEffect(() => {
        loadHintLimits();
    }, [hintsUsed, loadHintLimits]);

    // 남은 힌트 횟수 계산
    const getRemainingHints = useCallback((hintType) => {
        const limit = hintLimits?.[hintType];
        if (!limit) return 0;
        return Math.max(0, limit.max_uses_per_game - hintsUsed);
    }, [hintLimits, hintsUsed]);

    // 쿨다운 남은 시간 계산
    const getCooldownRemaining = useCallback((hintType) => {
        const cooldown = hintCooldowns[hintType];
        if (!cooldown) return 0;
        
        const remaining = cooldown - Date.now();
        return Math.max(0, Math.ceil(remaining / 1000));
    }, [hintCooldowns]);

    // 힌트 비용 계산
    const getHintCost = useCallback((hintType) => {
        const limit = hintLimits?.[hintType];
        return limit?.cost_per_use || 0;
    }, [hintLimits]);

    return {
        hints,
        hintCooldowns,
        hintLimits,
        loading,
        canUseHint,
        activateHintCooldown,
        getHint,
        getRemainingHints,
        getCooldownRemaining,
        getHintCost
    };
}; 