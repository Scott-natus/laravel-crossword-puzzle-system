import React, { useState } from 'react';
import axios from 'axios';

const PuzzleGenerator = () => {
    const [isGenerating, setIsGenerating] = useState(false);
    const [puzzle, setPuzzle] = useState(null);
    const [error, setError] = useState(null);
    const [selectedLevel, setSelectedLevel] = useState(1);

    // 레벨별 정보
    const levelInfo = {
        1: { name: "실마리 발견자", wordCount: 5, intersectionCount: 2 },
        2: { name: "실마리 발견자", wordCount: 5, intersectionCount: 2 },
        3: { name: "실마리 발견자", wordCount: 6, intersectionCount: 2 },
        4: { name: "실마리 발견자", wordCount: 6, intersectionCount: 2 },
        5: { name: "실마리 발견자", wordCount: 6, intersectionCount: 2 },
        6: { name: "실마리 발견자", wordCount: 7, intersectionCount: 2 },
        7: { name: "실마리 발견자", wordCount: 7, intersectionCount: 2 },
        8: { name: "실마리 발견자", wordCount: 7, intersectionCount: 2 },
        9: { name: "실마리 발견자", wordCount: 8, intersectionCount: 2 },
        10: { name: "실마리 발견자", wordCount: 8, intersectionCount: 2 }
    };

    const generatePuzzle = async () => {
        setIsGenerating(true);
        setError(null);
        
        try {
            console.log(`레벨 ${selectedLevel} 크로스워드 퍼즐 생성 시작...`);
            
            // 1. 기존 API로 단어 조합과 힌트 가져오기
            const crosswordResponse = await axios.get(`/api/puzzle/crossword-words?level=${selectedLevel}`);
            const crosswordData = crosswordResponse.data;
            console.log('크로스워드 데이터:', crosswordData);
            
            const { words, intersections } = crosswordData.words;
            const hints = crosswordData.hints || {};
            
            // 2. 교차점 정보를 활용한 그리드 생성
            const { grid, wordInfo } = createCrosswordGrid(words, intersections);
            console.log('크로스워드 그리드 생성 완료');
            
            // 3. 퍼즐 데이터 구성
            const puzzleData = {
                level: selectedLevel,
                grid,
                words,
                intersections,
                hints,
                wordInfo,
                stats: {
                    wordCount: crosswordData.wordCount,
                    intersectionCount: crosswordData.intersectionCount
                }
            };
            
            console.log('퍼즐 데이터:', puzzleData);
            setPuzzle(puzzleData);
            
        } catch (err) {
            console.error('퍼즐 생성 오류:', err);
            setError(err.response?.data?.message || err.message || '퍼즐 생성 중 오류가 발생했습니다.');
        } finally {
            setIsGenerating(false);
        }
    };

    const createCrosswordGrid = (words, intersections) => {
        // 동적 그리드 크기 계산 (제미나이 알고리즘)
        const gridSize = calculateOptimalGridSize(words, intersections);
        const grid = Array(gridSize).fill(null).map(() => Array(gridSize).fill(''));
        const wordInfo = [];
        
        console.log('백트래킹 알고리즘 시작:', {
            gridSize,
            words,
            intersections
        });
        
        // 백트래킹으로 단어 배치 시도
        const success = backtrackPlacement(words, intersections, grid, wordInfo, 0, gridSize);
        
        if (!success) {
            console.warn('백트래킹 배치 실패, 기본 배치로 대체');
            // 백트래킹 실패 시 기본 배치
            return basicPlacement(words, intersections, gridSize);
        }
        
        return { grid, wordInfo };
    };

    // 백트래킹 배치 알고리즘
    const backtrackPlacement = (words, intersections, grid, wordInfo, wordIndex, gridSize) => {
        if (wordIndex >= words.length) {
            // 모든 단어 배치 완료
            console.log('✅ 모든 단어 배치 완료!', wordInfo.map(info => `${info.word}(${info.direction})`));
            return true;
        }
        
        const currentWord = words[wordIndex];
        console.log(`🔄 단어 ${wordIndex + 1}/${words.length} 배치 시도: "${currentWord}"`);
        
        // 첫 번째 단어는 중앙에 가로로 배치
        if (wordIndex === 0) {
            const centerRow = Math.floor(gridSize / 2);
            const startCol = Math.floor((gridSize - currentWord.length) / 2);
            
            console.log(`📍 첫 번째 단어 "${currentWord}" 중앙 배치 시도: (${startCol}, ${centerRow})`);
            
            if (canPlaceWord(grid, currentWord, startCol, centerRow, 'horizontal', wordInfo)) {
                placeWord(grid, currentWord, startCol, centerRow, 'horizontal');
                wordInfo.push({
                    word: currentWord,
                    startX: startCol,
                    startY: centerRow,
                    direction: 'horizontal',
                    clue: `가로 ${wordInfo.length + 1}`
                });
                
                console.log(`✅ 첫 번째 단어 "${currentWord}" 배치 성공`);
                
                if (backtrackPlacement(words, intersections, grid, wordInfo, wordIndex + 1, gridSize)) {
                    return true;
                }
                
                // 백트래킹: 단어 제거
                console.log(`🔄 첫 번째 단어 "${currentWord}" 배치 실패, 제거`);
                removeWord(grid, currentWord, startCol, centerRow, 'horizontal');
                wordInfo.pop();
            } else {
                console.log(`❌ 첫 번째 단어 "${currentWord}" 배치 불가`);
            }
            return false;
        }
        
        // 나머지 단어들은 교차점을 통해 배치
        const validPositions = findValidPositions(grid, currentWord, intersections, wordInfo, gridSize);
        console.log(`📍 "${currentWord}" 유효한 배치 위치 ${validPositions.length}개 발견:`, validPositions);
        
        for (const position of validPositions) {
            const { x, y, direction } = position;
            console.log(`🔄 "${currentWord}" 배치 시도: (${x}, ${y}) ${direction}`);
            
            if (canPlaceWord(grid, currentWord, x, y, direction, wordInfo)) {
                placeWord(grid, currentWord, x, y, direction);
                wordInfo.push({
                    word: currentWord,
                    startX: x,
                    startY: y,
                    direction: direction,
                    clue: `${direction === 'horizontal' ? '가로' : '세로'} ${wordInfo.length + 1}`
                });
                
                console.log(`✅ "${currentWord}" 배치 성공: (${x}, ${y}) ${direction}`);
                
                if (backtrackPlacement(words, intersections, grid, wordInfo, wordIndex + 1, gridSize)) {
                    return true;
                }
                
                // 백트래킹: 단어 제거
                console.log(`🔄 "${currentWord}" 배치 실패, 제거`);
                removeWord(grid, currentWord, x, y, direction);
                wordInfo.pop();
            } else {
                console.log(`❌ "${currentWord}" 배치 불가: (${x}, ${y}) ${direction}`);
            }
        }
        
        console.log(`❌ "${currentWord}" 모든 배치 시도 실패`);
        return false;
    };

    // 유효한 배치 위치 찾기
    const findValidPositions = (grid, word, intersections, wordInfo, gridSize) => {
        const positions = [];
        
        console.log(`🔍 "${word}"의 교차점 정보:`, intersections);
        console.log(`🔍 현재 배치된 단어들:`, wordInfo.map(info => `${info.word}(${info.startX},${info.startY})`));
        
        // 교차점을 통한 배치 위치 찾기
        intersections.forEach((intersection, idx) => {
            const { word1, word2, syllable, word1Position, word2Position, word1Direction, word2Direction } = intersection;
            
            console.log(`🔍 교차점 ${idx + 1}:`, {
                word1, word2, syllable,
                word1Position, word2Position,
                word1Direction, word2Direction
            });
            
            // 현재 단어가 교차점에 포함되어 있는지 확인
            if (word === word1 || word === word2) {
                const otherWord = word === word1 ? word2 : word1;
                const otherWordInfo = wordInfo.find(info => info.word === otherWord);
                
                console.log(`🔍 "${word}"가 교차점에 포함됨, 상대 단어: "${otherWord}"`);
                console.log(`🔍 상대 단어 정보:`, otherWordInfo);
                
                if (otherWordInfo) {
                    // 교차점 위치 계산 (방향에 따라 다르게 계산)
                    let crossX, crossY;
                    
                    if (otherWordInfo.direction === 'horizontal') {
                        // 상대 단어가 가로인 경우
                        crossX = otherWordInfo.startX + (word === word1 ? word1Position : word2Position);
                        crossY = otherWordInfo.startY;
                    } else {
                        // 상대 단어가 세로인 경우
                        crossX = otherWordInfo.startX;
                        crossY = otherWordInfo.startY + (word === word1 ? word1Position : word2Position);
                    }
                    
                    // 현재 단어의 교차점 위치
                    const currentWordPosition = word === word1 ? word1Position : word2Position;
                    const currentWordDirection = word === word1 ? word1Direction : word2Direction;
                    
                    console.log(`🔍 교차점 위치 계산:`, {
                        crossX, crossY,
                        currentWordPosition, currentWordDirection,
                        otherWordDirection: otherWordInfo.direction,
                        otherWordStart: `(${otherWordInfo.startX}, ${otherWordInfo.startY})`
                    });
                    
                    // 배치 시작 위치 계산
                    let startX, startY;
                    if (currentWordDirection === 'horizontal') {
                        startX = crossX - currentWordPosition;
                        startY = crossY;
                    } else {
                        startX = crossX;
                        startY = crossY - currentWordPosition;
                    }
                    
                    console.log(`🔍 배치 시작 위치: (${startX}, ${startY})`);
                    
                    // 그리드 범위 검사 (단어 길이 고려)
                    const wordLength = word.length;
                    const endX = startX + (currentWordDirection === 'horizontal' ? wordLength - 1 : 0);
                    const endY = startY + (currentWordDirection === 'vertical' ? wordLength - 1 : 0);
                    
                    if (startX >= 0 && startY >= 0 && endX < gridSize && endY < gridSize) {
                        positions.push({
                            x: startX,
                            y: startY,
                            direction: currentWordDirection
                        });
                        
                        console.log(`✅ 유효한 배치 위치 추가: (${startX}, ${startY}) ${currentWordDirection} (끝: ${endX}, ${endY})`);
                    } else {
                        console.log(`❌ 배치 위치가 그리드 범위를 벗어남: (${startX}, ${startY}) ~ (${endX}, ${endY}) / 그리드 크기: ${gridSize}x${gridSize}`);
                    }
                } else {
                    console.log(`❌ 상대 단어 "${otherWord}" 정보를 찾을 수 없음 (아직 배치되지 않음)`);
                }
            } else {
                console.log(`❌ "${word}"가 이 교차점에 포함되지 않음`);
            }
        });
        
        console.log(`🔍 "${word}" 최종 유효한 배치 위치:`, positions);
        return positions;
    };

    // 단어 배치 가능 여부 확인 (단어 독립성 검증 포함)
    const canPlaceWord = (grid, word, x, y, direction, wordInfo) => {
        const wordLength = word.length;
        
        // 그리드 범위 확인
        if (direction === 'horizontal') {
            if (x + wordLength > grid.length) return false;
        } else {
            if (y + wordLength > grid.length) return false;
        }
        
        // 단어 배치 경로 확인
        for (let i = 0; i < wordLength; i++) {
            const checkX = direction === 'horizontal' ? x + i : x;
            const checkY = direction === 'vertical' ? y + i : y;
            
            // 이미 다른 글자가 있고, 교차점이 아닌 경우
            if (grid[checkY][checkX] !== '' && grid[checkY][checkX] !== word[i]) {
                return false;
            }
        }
        
        // 같은 교차점에 이미 단어가 배치되어 있는지 확인
        for (let i = 0; i < wordLength; i++) {
            const checkX = direction === 'horizontal' ? x + i : x;
            const checkY = direction === 'vertical' ? y + i : y;
            
            // 이미 글자가 있는 위치에서
            if (grid[checkY][checkX] !== '') {
                // 이미 배치된 단어들과 같은 위치에 배치되는지 확인
                for (const placedWord of wordInfo) {
                    const placedLength = placedWord.word.length;
                    for (let j = 0; j < placedLength; j++) {
                        const placedX = placedWord.direction === 'horizontal' ? placedWord.startX + j : placedWord.startX;
                        const placedY = placedWord.direction === 'vertical' ? placedWord.startY + j : placedWord.startY;
                        
                        // 같은 위치에 다른 방향으로 단어가 배치되려고 하는 경우
                        if (placedX === checkX && placedY === checkY) {
                            // 교차점인 경우: 다른 방향이면 허용, 같은 방향이면 충돌
                            if (placedWord.direction === direction) {
                                console.log(`❌ 같은 방향으로 단어 충돌: "${placedWord.word}" (${placedX},${placedY})`);
                                return false;
                            } else {
                                // 교차점 배치: 문자가 일치하는지 확인
                                const placedChar = placedWord.word[j];
                                const currentChar = word[i];
                                if (placedChar !== currentChar) {
                                    console.log(`❌ 교차점에서 문자 불일치: "${placedChar}" vs "${currentChar}" (${placedX},${placedY})`);
                                    return false;
                                }
                                console.log(`✅ 교차점 배치 확인: "${placedChar}" (${placedX},${placedY})`);
                            }
                        }
                    }
                }
            }
        }
        
        // 첫 번째 단어는 단어 독립성 검증 건너뛰기
        if (wordInfo.length === 0) {
            console.log(`✅ 첫 번째 단어 "${word}" - 단어 독립성 검증 건너뜀`);
            return true;
        }
        
        // 단어 독립성 검증: 의도치 않은 단어 생성 방지
        if (!isValidWordPlacement(grid, word, x, y, direction, wordInfo)) {
            return false;
        }
        
        return true;
    };

    // 단어 독립성 검증 (개선된 버전)
    const isValidWordPlacement = (grid, word, x, y, direction, wordInfo) => {
        // 임시로 단어 배치
        const tempGrid = grid.map(row => [...row]);
        placeWord(tempGrid, word, x, y, direction);
        
        // 교차점 위치들 찾기
        const intersectionPoints = new Set();
        for (let i = 0; i < word.length; i++) {
            const checkX = direction === 'horizontal' ? x + i : x;
            const checkY = direction === 'vertical' ? y + i : y;
            
            // 원본 그리드에서 이미 글자가 있는 위치는 교차점
            if (grid[checkY][checkX] !== '') {
                intersectionPoints.add(`${checkX},${checkY}`);
            }
        }
        
        console.log(`🔍 교차점 위치들:`, Array.from(intersectionPoints));
        
        // 교차점이 있으면 모든 새로운 단어 허용 (교차점에서 생성되는 단어들)
        if (intersectionPoints.size > 0) {
            console.log(`✅ 교차점이 있으므로 모든 새로운 단어 허용`);
            return true;
        }
        
        // 교차점이 없는 경우에만 단어 독립성 검증
        const directions = [
            { dx: 1, dy: 0 },   // 가로
            { dx: 0, dy: 1 },   // 세로
        ];
        
        for (let startY = 0; startY < tempGrid.length; startY++) {
            for (let startX = 0; startX < tempGrid.length; startX++) {
                if (tempGrid[startY][startX] === '') continue;
                
                for (const dir of directions) {
                    const newWord = extractWord(tempGrid, startX, startY, dir.dx, dir.dy);
                    if (newWord.length >= 2 && !wordInfo.some(info => info.word === newWord)) {
                        console.log(`❌ 교차점 없이 의도치 않은 단어 생성: "${newWord}"`);
                        return false;
                    }
                }
            }
        }
        
        return true;
    };

    // 단어 추출
    const extractWord = (grid, startX, startY, dx, dy) => {
        let word = '';
        let x = startX, y = startY;
        
        while (x >= 0 && y >= 0 && x < grid.length && y < grid.length && grid[y][x] !== '') {
            word += grid[y][x];
            x += dx;
            y += dy;
        }
        
        return word;
    };

    // 단어 배치
    const placeWord = (grid, word, x, y, direction) => {
        for (let i = 0; i < word.length; i++) {
            const placeX = direction === 'horizontal' ? x + i : x;
            const placeY = direction === 'vertical' ? y + i : y;
            grid[placeY][placeX] = word[i];
        }
    };

    // 단어 제거
    const removeWord = (grid, word, x, y, direction) => {
        for (let i = 0; i < word.length; i++) {
            const placeX = direction === 'horizontal' ? x + i : x;
            const placeY = direction === 'vertical' ? y + i : y;
            grid[placeY][placeX] = '';
        }
    };

    // 기본 배치 (백트래킹 실패 시)
    const basicPlacement = (words, intersections, gridSize) => {
        const grid = Array(gridSize).fill(null).map(() => Array(gridSize).fill(''));
        const wordInfo = [];
        
        // 첫 번째 단어를 중앙에 배치
        if (words.length > 0) {
            const firstWord = words[0];
            const centerRow = Math.floor(gridSize / 2);
            const startCol = Math.floor((gridSize - firstWord.length) / 2);
            
            for (let i = 0; i < firstWord.length; i++) {
                grid[centerRow][startCol + i] = firstWord[i];
            }
            
            wordInfo.push({
                word: firstWord,
                startX: startCol,
                startY: centerRow,
                direction: 'horizontal',
                clue: `가로 ${wordInfo.length + 1}`
            });
        }
        
        return { grid, wordInfo };
    };

    // 동적 그리드 크기 계산 함수 (제미나이 알고리즘 - 개선된 버전)
    const calculateOptimalGridSize = (words, intersections) => {
        if (!words || words.length === 0) return 5;
        
        // 1. 실제 필요한 칸 수 계산
        const totalSyllables = words.reduce((sum, word) => sum + word.length, 0);
        const intersectionCount = intersections ? intersections.length : 0;
        
        // 2. 교차점을 고려한 실제 채워야 할 칸 수
        // 교차점에서는 두 단어가 같은 칸을 공유하므로 중복 제거
        const actualFilledCells = totalSyllables - intersectionCount;
        
        // 3. 최소 그리드 크기 계산
        // 실제 채워야 할 칸 수 + 여유 공간 (2-3칸)
        const minRequiredSize = Math.ceil(Math.sqrt(actualFilledCells + 3));
        
        // 4. 단어 배치를 위한 추가 공간 고려
        // 가장 긴 단어가 가로/세로로 배치될 수 있으므로
        const maxWordLength = Math.max(...words.map(word => word.length));
        const sizeForWordPlacement = maxWordLength + 2;
        
        // 5. 최종 크기 결정 (더 큰 값 선택)
        const optimalSize = Math.max(minRequiredSize, sizeForWordPlacement);
        
        // 6. 최소/최대 제한
        const finalSize = Math.min(Math.max(optimalSize, 5), 15);
        
        console.log(`그리드 크기 계산 (개선된 알고리즘):`, {
            totalSyllables,
            intersectionCount,
            actualFilledCells,
            minRequiredSize,
            sizeForWordPlacement,
            optimalSize,
            finalSize,
            words: words.map(w => `${w}(${w.length})`).join(', ')
        });
        
        return finalSize;
    };

    const renderGrid = (grid) => {
        const gridSize = grid.length;
        
        // 동적 셀 크기 계산
        const calculateCellSize = () => {
            const isMobile = window.innerWidth <= 480;
            const isPortrait = window.innerHeight > window.innerWidth;
            
            let availableWidth, availableHeight;
            
            if (isMobile && isPortrait) {
                // 모바일 세로 화면: 화면의 90% 사용
                availableWidth = window.innerWidth * 0.9;
                availableHeight = window.innerHeight * 0.9;
            } else if (isMobile) {
                // 모바일 가로 화면: 화면의 85% 사용
                availableWidth = window.innerWidth * 0.85;
                availableHeight = window.innerHeight * 0.8;
            } else {
                // 데스크톱/태블릿: 기본 크기 유지
                availableWidth = Math.min(600, window.innerWidth * 0.8);
                availableHeight = Math.min(600, window.innerHeight * 0.8);
            }

            // border와 gap 고려하여 실제 사용 가능한 크기 계산
            const borderWidth = 2; // 그리드 테두리
            const cellBorder = 1; // 셀 테두리
            const gap = 1; // 셀 간격
            
            const totalBorderWidth = borderWidth * 2 + (gridSize - 1) * cellBorder * 2;
            const totalGapWidth = (gridSize - 1) * gap;
            
            const maxCellWidth = (availableWidth - totalBorderWidth - totalGapWidth) / gridSize;
            const maxCellHeight = (availableHeight - totalBorderWidth - totalGapWidth) / gridSize;
            
            // 더 작은 값으로 통일 (정사각형 유지)
            const calculatedSize = Math.min(maxCellWidth, maxCellHeight);
            
            // 최소/최대 크기 제한
            const minSize = isMobile ? 12 : 20;
            const maxSize = isMobile ? 35 : 50;
            
            return Math.max(minSize, Math.min(maxSize, calculatedSize));
        };
        
        const cellSize = calculateCellSize();
        
        return (
            <div className="crossword-grid" style={{ 
                display: 'grid', 
                gridTemplateColumns: `repeat(${gridSize}, ${cellSize}px)`, 
                gap: '1px',
                border: '2px solid #333',
                backgroundColor: '#fff',
                margin: '20px auto',
                maxWidth: '90vw',
                maxHeight: '90vh'
            }}>
                {grid.map((row, rowIndex) =>
                    row.map((cell, colIndex) => (
                        <div
                            key={`${rowIndex}-${colIndex}`}
                            style={{
                                width: `${cellSize}px`,
                                height: `${cellSize}px`,
                                border: '1px solid #ccc',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                backgroundColor: cell ? '#fff' : '#f0f0f0',
                                fontSize: `${Math.max(8, Math.min(14, cellSize * 0.4))}px`,
                                fontWeight: 'bold',
                                boxSizing: 'border-box'
                            }}
                        >
                            {cell || ''}
                        </div>
                    ))
                )}
            </div>
        );
    };

    return (
        <div className="puzzle-generator" style={{ padding: '20px' }}>
            <div style={{ 
                backgroundColor: '#f8f9fa', 
                padding: '20px', 
                borderRadius: '10px', 
                marginBottom: '20px',
                border: '1px solid #dee2e6'
            }}>
                <h2 style={{ color: '#495057', marginBottom: '20px' }}>
                    🎯 크로스워드 퍼즐 생성기
                </h2>
                
                <div style={{ 
                    display: 'grid', 
                    gridTemplateColumns: '1fr 1fr', 
                    gap: '20px',
                    marginBottom: '20px'
                }}>
                    <div>
                        <label htmlFor="level" style={{ display: 'block', marginBottom: '10px', fontWeight: 'bold' }}>
                            레벨 선택:
                        </label>
                        <select 
                            id="level"
                            value={selectedLevel} 
                            onChange={(e) => setSelectedLevel(parseInt(e.target.value))}
                            style={{
                                width: '100%',
                                padding: '10px',
                                border: '1px solid #ced4da',
                                borderRadius: '5px',
                                fontSize: '16px'
                            }}
                        >
                            {Object.keys(levelInfo).map(level => (
                                <option key={level} value={level}>
                                    레벨 {level} - {levelInfo[level].name} 
                                    ({levelInfo[level].wordCount}단어, {levelInfo[level].intersectionCount}교차점)
                                </option>
                            ))}
                        </select>
                    </div>
                    
                    <div>
                        <button 
                            onClick={generatePuzzle} 
                            disabled={isGenerating}
                            style={{ 
                                width: '100%',
                                padding: '15px',
                                fontSize: '16px',
                                backgroundColor: isGenerating ? '#6c757d' : '#007bff',
                                color: 'white',
                                border: 'none',
                                borderRadius: '5px',
                                cursor: isGenerating ? 'not-allowed' : 'pointer',
                                fontWeight: 'bold'
                            }}
                        >
                            {isGenerating ? '🔄 생성 중...' : '🎲 퍼즐 생성하기'}
                        </button>
                    </div>
                </div>
            </div>
            
            {error && (
                <div style={{ 
                    color: '#721c24', 
                    backgroundColor: '#f8d7da',
                    border: '1px solid #f5c6cb',
                    borderRadius: '5px',
                    padding: '15px',
                    marginBottom: '20px'
                }}>
                    <strong>❌ 오류:</strong> {error}
                </div>
            )}
            
            {puzzle && (
                <div style={{ 
                    backgroundColor: '#fff', 
                    padding: '20px', 
                    borderRadius: '10px',
                    border: '1px solid #dee2e6'
                }}>
                    <div style={{ 
                        backgroundColor: '#d4edda', 
                        padding: '15px', 
                        borderRadius: '5px',
                        border: '1px solid #c3e6cb',
                        marginBottom: '20px'
                    }}>
                        <h4 style={{ color: '#155724', margin: 0 }}>✅ 퍼즐 생성 성공!</h4>
                        <div style={{ 
                            display: 'grid', 
                            gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', 
                            gap: '10px',
                            marginTop: '10px'
                        }}>
                            <div><strong>레벨:</strong> {puzzle.level}</div>
                            <div><strong>단어 개수:</strong> {puzzle.stats.wordCount}개</div>
                            <div><strong>교차점 개수:</strong> {puzzle.stats.intersectionCount}개</div>
                            <div><strong>그리드 크기:</strong> {puzzle.grid.length}×{puzzle.grid.length}</div>
                        </div>
                    </div>
                    
                    <div style={{ marginBottom: '20px' }}>
                        <h4>🎮 크로스워드 그리드</h4>
                        {renderGrid(puzzle.grid)}
                    </div>
                    
                    <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: '1fr 1fr', 
                        gap: '20px',
                        marginBottom: '20px'
                    }}>
                        <div>
                            <h5>단어 목록 & 힌트:</h5>
                            <ul style={{ listStyle: 'none', padding: 0 }}>
                                {puzzle.wordInfo.map((info, index) => (
                                    <li key={index} style={{ 
                                        marginBottom: '10px',
                                        padding: '10px',
                                        backgroundColor: '#fff',
                                        border: '1px solid #ddd',
                                        borderRadius: '5px'
                                    }}>
                                        <div><strong>{info.clue}: {info.word}</strong></div>
                                        <div style={{ fontSize: '14px', color: '#666' }}>
                                            {(() => {
                                                const wordHints = puzzle.hints[info.word];
                                                if (wordHints && Object.keys(wordHints).length > 0) {
                                                    // 첫 번째 힌트 표시 (easy, medium, hard 중 하나)
                                                    const firstHint = Object.values(wordHints)[0];
                                                    return firstHint.hint || '힌트 없음';
                                                }
                                                return '힌트 없음';
                                            })()}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        
                        <div>
                            <h5>교차점 정보:</h5>
                            <ul style={{ listStyle: 'none', padding: 0 }}>
                                {puzzle.intersections.map((intersection, index) => (
                                    <li key={index} style={{ 
                                        marginBottom: '10px',
                                        padding: '10px',
                                        backgroundColor: '#e3f2fd',
                                        border: '1px solid #2196f3',
                                        borderRadius: '5px'
                                    }}>
                                        <div><strong>교차점 {index + 1}</strong></div>
                                        <div>{intersection.word1} ↔ {intersection.word2}</div>
                                        <div style={{ fontSize: '14px', color: '#1976d2' }}>
                                            공통 음절: <strong>{intersection.syllable}</strong>
                                        </div>
                                        {intersection.word1Position !== undefined && (
                                            <div style={{ fontSize: '12px', color: '#666', marginTop: '5px' }}>
                                                <div>{intersection.word1}의 {intersection.word1Position + 1}번째 음절 ({intersection.word1Direction})</div>
                                                <div>{intersection.word2}의 {intersection.word2Position + 1}번째 음절 ({intersection.word2Direction})</div>
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default PuzzleGenerator; 