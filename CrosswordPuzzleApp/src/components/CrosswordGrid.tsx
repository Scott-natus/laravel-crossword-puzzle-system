import React, { useEffect, useRef, useState } from 'react';

interface WordPosition {
  id: number;
  word_id: number;
  word: string;
  hint: string;
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: number; // 0: horizontal, 1: vertical
}

interface CrosswordGridProps {
  grid: string[][];
  wordPositions: WordPosition[];
  userInput?: Record<string, string>;
  selectedCell?: { x: number; y: number } | null;
  foundWords?: Set<number>;
  answeredWords?: Set<number>;
  wrongAnswers?: Map<number, number>;
  onCellSelect?: (x: number, y: number) => void;
  onCharInput?: (x: number, y: number, char: string) => void;
  onWordSelect?: (word: WordPosition) => void;
}

const CrosswordGrid: React.FC<CrosswordGridProps> = ({
    grid,
    wordPositions,
    userInput = {},
    selectedCell,
    foundWords = new Set(),
    answeredWords = new Set(),
    wrongAnswers = new Map(),
    onCellSelect,
    onCharInput,
    onWordSelect
}) => {
    const gridRef = useRef(null);
    const [cellSize, setCellSize] = useState(40);

    useEffect(() => {
        const calculateCellSize = () => {
            if (!grid || grid.length === 0) return;
            const gridSize = grid.length;
            const isMobile = false;
            const isPortrait = false;
            let availableWidth = 360;
            let availableHeight = 360;
            const borderWidth = 2;
            const cellBorder = 1;
            const gap = 0;
            const totalBorderWidth = borderWidth * 2 + (gridSize - 1) * cellBorder * 2;
            const totalGapWidth = (gridSize - 1) * gap;
            const maxCellWidth = (availableWidth - totalBorderWidth - totalGapWidth) / gridSize;
            const maxCellHeight = (availableHeight - totalBorderWidth - totalGapWidth) / gridSize;
            const calculatedSize = Math.min(maxCellWidth, maxCellHeight);
            const minSize = 20;
            const maxSize = 50;
            const finalSize = Math.max(minSize, Math.min(maxSize, calculatedSize));
            setCellSize(finalSize);
        };
        calculateCellSize();
    }, [grid]);

    // 단어의 모든 셀 위치를 계산하는 함수
    const getWordPositions = (wordPos: WordPosition) => {
        const positions = [];
        const { start_x, start_y, end_x, end_y, direction } = wordPos;
        
        if (direction === 0) { // horizontal
            for (let x = start_x; x <= end_x; x++) {
                positions.push({ x, y: start_y });
            }
        } else { // vertical
            for (let y = start_y; y <= end_y; y++) {
                positions.push({ x: start_x, y });
            }
        }
        return positions;
    };

    // 특정 위치의 단어 정보 찾기
    const getWordInfoAtPosition = (x: number, y: number): WordPosition | { isIntersection: boolean; horizontalWord: WordPosition; verticalWord: WordPosition } | null => {
        let horizontalWord: WordPosition | null = null;
        let verticalWord: WordPosition | null = null;
        
        for (const wordPos of wordPositions) {
            // 가로 방향 단어
            if (wordPos.direction === 0) { // horizontal
                if (y === wordPos.start_y && x >= wordPos.start_x && x <= wordPos.end_x) {
                    // 단어의 시작 위치인지 확인
                    if (x === wordPos.start_x) {
                        horizontalWord = wordPos;
                    }
                }
            }
            // 세로 방향 단어
            else if (wordPos.direction === 1) { // vertical
                if (x === wordPos.start_x && y >= wordPos.start_y && y <= wordPos.end_y) {
                    // 단어의 시작 위치인지 확인
                    if (y === wordPos.start_y) {
                        verticalWord = wordPos;
                    }
                }
            }
        }
        
        // 교차점인 경우: 가로 단어 우선, 세로 단어는 작은 번호로 표시
        if (horizontalWord && verticalWord) {
            return {
                isIntersection: true,
                horizontalWord: horizontalWord,
                verticalWord: verticalWord
            };
        }
        
        // 단일 단어인 경우
        return horizontalWord || verticalWord;
    };

    // 해당 위치의 모든 단어 찾기 (교차점 처리)
    const getWordsAtPosition = (x: number, y: number) => {
        let foundWords = [];
        
        for (const wordPos of wordPositions) {
            // 가로 방향 단어
            if (wordPos.direction === 0) { // horizontal
                if (y === wordPos.start_y && x >= wordPos.start_x && x <= wordPos.end_x) {
                    foundWords.push(wordPos);
                }
            }
            // 세로 방향 단어
            else if (wordPos.direction === 1) { // vertical
                if (x === wordPos.start_x && y >= wordPos.start_y && y <= wordPos.end_y) {
                    foundWords.push(wordPos);
                }
            }
        }
        
        return foundWords;
    };

    const isCellInWord = (x: number, y: number) => {
        return wordPositions.some(wordPos => {
            const positions = getWordPositions(wordPos);
            return positions.some(pos => pos.x === x && pos.y === y);
        });
    };

    const getCellValue = (x: number, y: number) => {
        const cell = grid[y][x];
        if (cell === '2') return null; // 검은 셀
        const inputValue = userInput[`${x},${y}`];
        if (inputValue) return inputValue;
        return '';
    };

    const isSelected = (x: number, y: number) => {
        return selectedCell && selectedCell.x === x && selectedCell.y === y;
    };

    const isInFoundWord = (x: number, y: number) => {
        return wordPositions.some(wordPos => {
            const positions = getWordPositions(wordPos);
            return positions.some(pos => pos.x === x && pos.y === y);
        });
    };

    const isAnsweredWord = (wordId: number) => {
        return answeredWords.has(wordId);
    };

    const getWrongAnswerCount = (wordId: number) => {
        return wrongAnswers.get(wordId) || 0;
    };

    const handleCellClick = (x: number, y: number) => {
        if (grid[y][x] === '2') return; // 검은 셀 클릭 방지
        
        // 단어 선택 기능
        const wordsAtPosition = getWordsAtPosition(x, y);
        if (wordsAtPosition.length > 0 && onWordSelect) {
            // 첫 번째 단어를 선택 (가로 우선)
            onWordSelect(wordsAtPosition[0]);
        }
        
        if (onCellSelect) {
            onCellSelect(x, y);
        }
    };

    // badge 렌더링 함수
    const renderBadge = (x: number, y: number) => {
        const badgeSize = 16;
        const fontSize = 11;
        // 해당 위치에서 시작하는 단어들만 필터링
        const startWords = wordPositions.filter(
            wp => wp.start_x === x && wp.start_y === y
        );
        if (startWords.length === 2) {
            // 두 단어가 모두 시작하는 교차점
            const [lowWord, highWord] = startWords[0].word_id < startWords[1].word_id
                ? [startWords[0], startWords[1]]
                : [startWords[1], startWords[0]];
            return (
                <div style={{ position: 'relative', width: '100%', height: '100%', overflow: 'visible' }}>
                    {/* 낮은 번호: 왼쪽 위, 빨간색 */}
                    <div
                        style={{
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            width: badgeSize,
                            height: badgeSize,
                            background: '#ff6b6b',
                            color: 'white',
                            borderRadius: '50%',
                            fontSize: fontSize,
                            zIndex: 20,
                            textAlign: 'center',
                            lineHeight: `${badgeSize}px`,
                            fontWeight: 'bold',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.15)',
                            display: 'block'
                        }}
                    >{lowWord.word_id}</div>
                    {/* 높은 번호: 오른쪽 아래, 파란색 */}
                    <div
                        style={{
                            position: 'absolute',
                            bottom: 0,
                            right: 0,
                            width: badgeSize,
                            height: badgeSize,
                            background: '#4ecdc4',
                            color: 'white',
                            borderRadius: '50%',
                            fontSize: fontSize,
                            zIndex: 30,
                            textAlign: 'center',
                            lineHeight: `${badgeSize}px`,
                            fontWeight: 'bold',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.15)',
                            display: 'block'
                        }}
                    >{highWord.word_id}</div>
                </div>
            );
        } else if (startWords.length === 1) {
            // 단일 단어 시작점
            const word = startWords[0];
            return (
                <div
                    style={{
                        position: 'absolute',
                        top: 2,
                        left: 2,
                        width: badgeSize,
                        height: badgeSize,
                        fontSize: fontSize,
                        background: word.direction === 0 ? '#ff6b6b' : '#4ecdc4',
                        borderRadius: '50%',
                        display: 'block',
                        color: 'white',
                        fontWeight: 'bold',
                        zIndex: 20,
                        boxShadow: '0 2px 4px rgba(0,0,0,0.15)',
                        textAlign: 'center',
                        lineHeight: `${badgeSize}px`
                    }}
                >{word.word_id}</div>
            );
        }
        return null;
    };

    return (
        <div className="crossword-grid" ref={gridRef} style={{ display: 'grid', gridTemplateColumns: `repeat(${grid?.length || 0}, ${cellSize}px)`, gap: '0px', border: '2px solid #333', backgroundColor: '#fff', margin: '0 auto', maxWidth: '90vw', maxHeight: '90vh' }}>
            {grid?.map((row, y) => (
                <div key={y} className="grid-row">
                    {row.map((cell, x) => (
                        <div
                            key={`${x}-${y}`}
                            className={`grid-cell ${cell === '2' ? 'black' : ''} ${isSelected(x, y) ? 'selected' : ''} ${isInFoundWord(x, y) ? 'found' : ''} ${isCellInWord(x, y) ? 'word-cell' : ''}`}
                            style={{ 
                                width: `${cellSize}px`, 
                                height: `${cellSize}px`, 
                                border: '1px solid #ccc', 
                                display: 'block', 
                                cursor: 'pointer', 
                                position: 'relative',
                                backgroundColor: cell === '2' ? '#333' : isSelected(x, y) ? '#007bff' : isInFoundWord(x, y) ? '#d4edda' : isCellInWord(x, y) ? '#f8f9fa' : '#fff', 
                                transition: 'all 0.2s ease', 
                                boxSizing: 'border-box',
                                overflow: 'visible',
                            }}
                            onClick={() => handleCellClick(x, y)}
                        >
                            {cell === '2' && renderBadge(x, y)}
                            {cell !== '2' && (
                                <span className="cell-content" style={{ fontSize: `${Math.max(8, Math.min(18, cellSize * 0.4))}px`, fontWeight: 'bold', color: '#333', userSelect: 'none' }}>
                                    {getCellValue(x, y)}
                                </span>
                            )}
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
};

export default CrosswordGrid; 