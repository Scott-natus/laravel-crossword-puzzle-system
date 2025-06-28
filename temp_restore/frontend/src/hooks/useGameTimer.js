import { useState, useEffect, useCallback, useRef } from 'react';

export const useGameTimer = (totalTime = 300) => {
    const [timeLeft, setTimeLeft] = useState(totalTime);
    const [isRunning, setIsRunning] = useState(false);
    const [isPaused, setIsPaused] = useState(false);
    const intervalRef = useRef(null);
    const startTimeRef = useRef(null);
    const pausedTimeRef = useRef(0);

    // 타이머 시작
    const startTimer = useCallback(() => {
        if (isRunning) return;

        setIsRunning(true);
        setIsPaused(false);
        startTimeRef.current = Date.now();
        pausedTimeRef.current = 0;

        intervalRef.current = setInterval(() => {
            setTimeLeft(prev => {
                const newTime = prev - 1;
                if (newTime <= 0) {
                    clearInterval(intervalRef.current);
                    setIsRunning(false);
                    return 0;
                }
                return newTime;
            });
        }, 1000);
    }, [isRunning]);

    // 타이머 일시정지
    const pauseTimer = useCallback(() => {
        if (!isRunning || isPaused) return;

        clearInterval(intervalRef.current);
        setIsPaused(true);
        pausedTimeRef.current = Date.now();
    }, [isRunning, isPaused]);

    // 타이머 재개
    const resumeTimer = useCallback(() => {
        if (!isRunning || !isPaused) return;

        const pauseDuration = Date.now() - pausedTimeRef.current;
        startTimeRef.current += pauseDuration;

        setIsPaused(false);
        pausedTimeRef.current = 0;

        intervalRef.current = setInterval(() => {
            setTimeLeft(prev => {
                const newTime = prev - 1;
                if (newTime <= 0) {
                    clearInterval(intervalRef.current);
                    setIsRunning(false);
                    return 0;
                }
                return newTime;
            });
        }, 1000);
    }, [isRunning, isPaused]);

    // 타이머 정지
    const stopTimer = useCallback(() => {
        clearInterval(intervalRef.current);
        setIsRunning(false);
        setIsPaused(false);
        startTimeRef.current = null;
        pausedTimeRef.current = 0;
    }, []);

    // 타이머 리셋
    const resetTimer = useCallback(() => {
        stopTimer();
        setTimeLeft(totalTime);
    }, [stopTimer, totalTime]);

    // 타이머 설정
    const setTimer = useCallback((newTime) => {
        stopTimer();
        setTimeLeft(newTime);
    }, [stopTimer]);

    // 경과 시간 계산
    const getElapsedTime = useCallback(() => {
        if (!startTimeRef.current) return 0;

        const currentTime = Date.now();
        const pauseDuration = pausedTimeRef.current ? currentTime - pausedTimeRef.current : 0;
        return Math.floor((currentTime - startTimeRef.current - pauseDuration) / 1000);
    }, []);

    // 남은 시간 포맷팅
    const getFormattedTime = useCallback(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, [timeLeft]);

    // 진행률 계산
    const getProgress = useCallback(() => {
        return ((totalTime - timeLeft) / totalTime) * 100;
    }, [totalTime, timeLeft]);

    // 경고 상태 (30초 이하)
    const isWarning = timeLeft <= 30;

    // 위험 상태 (10초 이하)
    const isDanger = timeLeft <= 10;

    // 컴포넌트 언마운트 시 정리
    useEffect(() => {
        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, []);

    return {
        timeLeft,
        isRunning,
        isPaused,
        isWarning,
        isDanger,
        startTimer,
        pauseTimer,
        resumeTimer,
        stopTimer,
        resetTimer,
        setTimer,
        getElapsedTime,
        getFormattedTime,
        getProgress
    };
}; 