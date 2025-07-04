import React, { useEffect, useRef, useState } from 'react';
import './CrosswordGrid.css';

const CrosswordGrid = ({
    grid,
    wordPositions,
    userInput,
    selectedCell,
    foundWords,
    onCellSelect,
    onCharInput
}) => {
    const gridRef = useRef(null);
    const [cellSize, setCellSize] = useState(40);

    // 동적 셀 크기 계산
    useEffect(() => {
        const calculateCellSize = () => {
            if (!grid || grid.length === 0) return;

            const gridSize = grid.length; // N x N 그리드
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
            const gap = 0; // 셀 간격 (현재는 0)
            
            const totalBorderWidth = borderWidth * 2 + (gridSize - 1) * cellBorder * 2;
            const totalGapWidth = (gridSize - 1) * gap;
            
            const maxCellWidth = (availableWidth - totalBorderWidth - totalGapWidth) / gridSize;
            const maxCellHeight = (availableHeight - totalBorderWidth - totalGapWidth) / gridSize;
            
            // 더 작은 값으로 통일 (정사각형 유지)
            const calculatedSize = Math.min(maxCellWidth, maxCellHeight);
            
            // 최소/최대 크기 제한
            const minSize = isMobile ? 12 : 20;
            const maxSize = isMobile ? 35 : 50;
            
            const finalSize = Math.max(minSize, Math.min(maxSize, calculatedSize));
            
            console.log('셀 크기 계산:', {
                gridSize,
                availableWidth,
                availableHeight,
                calculatedSize,
                finalSize,
                isMobile,
                isPortrait
            });
            
            setCellSize(finalSize);
        };

        calculateCellSize();
        
        // 화면 크기 변경 시 재계산
        const handleResize = () => {
            calculateCellSize();
        };
        
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, [grid]);

    useEffect(() => {
        const handleKeyDown = (e) => {
            if (!selectedCell) return;

            const { x, y } = selectedCell;
            const key = e.key;

            if (key === 'Backspace') {
                onCharInput(x, y, '');
                return;
            }

            if (key === 'ArrowLeft' || key === 'ArrowRight' || key === 'ArrowUp' || key === 'ArrowDown') {
                e.preventDefault();
                let newX = x;
                let newY = y;

                switch (key) {
                    case 'ArrowLeft':
                        newX = Math.max(0, x - 1);
                        break;
                    case 'ArrowRight':
                        newX = Math.min(grid[0].length - 1, x + 1);
                        break;
                    case 'ArrowUp':
                        newY = Math.max(0, y - 1);
                        break;
                    case 'ArrowDown':
                        newY = Math.min(grid.length - 1, y + 1);
                        break;
                }

                onCellSelect(newX, newY);
                return;
            }

            // 한글 입력 처리
            if (/^[가-힣]$/.test(key)) {
                onCharInput(x, y, key);
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [selectedCell, onCharInput, onCellSelect, grid]);

    const isCellInWord = (x, y) => {
        return wordPositions.some(wordPos =>
            wordPos.positions.some(pos => pos.x === x && pos.y === y)
        );
    };

    const getCellValue = (x, y) => {
        const cell = grid[y][x];
        if (cell.isBlack) return null;
        
        const inputValue = userInput[`${x},${y}`];
        if (inputValue) return inputValue;
        
        return cell.char || '';
    };

    const isSelected = (x, y) => {
        return selectedCell && selectedCell.x === x && selectedCell.y === y;
    };

    const isInFoundWord = (x, y) => {
        return wordPositions.some(wordPos => {
            if (!foundWords.has(wordPos.word)) return false;
            return wordPos.positions.some(pos => pos.x === x && pos.y === y);
        });
    };

    const handleCellClick = (x, y) => {
        if (grid[y][x].isBlack) return;
        onCellSelect(x, y);
    };

    // 동적 스타일 계산
    const gridStyle = {
        display: 'grid',
        gridTemplateColumns: `repeat(${grid?.length || 0}, ${cellSize}px)`,
        gap: '0px',
        border: '2px solid #333',
        backgroundColor: '#fff',
        margin: '0 auto',
        maxWidth: '90vw',
        maxHeight: '90vh'
    };

    const cellStyle = {
        width: `${cellSize}px`,
        height: `${cellSize}px`,
        border: '1px solid #ccc',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        cursor: 'pointer',
        position: 'relative',
        backgroundColor: '#fff',
        transition: 'all 0.2s ease',
        boxSizing: 'border-box'
    };

    const contentStyle = {
        fontSize: `${Math.max(8, Math.min(18, cellSize * 0.4))}px`,
        fontWeight: 'bold',
        color: '#333',
        userSelect: 'none'
    };

    return (
        <div className="crossword-grid" ref={gridRef} style={gridStyle}>
            {grid?.map((row, y) => (
                <div key={y} className="grid-row">
                    {row.map((cell, x) => (
                        <div
                            key={`${x}-${y}`}
                            className={`grid-cell ${
                                cell.isBlack ? 'black' : ''
                            } ${
                                isSelected(x, y) ? 'selected' : ''
                            } ${
                                isInFoundWord(x, y) ? 'found' : ''
                            } ${
                                isCellInWord(x, y) ? 'word-cell' : ''
                            }`}
                            style={{
                                ...cellStyle,
                                backgroundColor: cell.isBlack ? '#333' : 
                                    isSelected(x, y) ? '#007bff' :
                                    isInFoundWord(x, y) ? '#d4edda' :
                                    isCellInWord(x, y) ? '#f8f9fa' : '#fff'
                            }}
                            onClick={() => handleCellClick(x, y)}
                        >
                            {!cell.isBlack && (
                                <span className="cell-content" style={contentStyle}>
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