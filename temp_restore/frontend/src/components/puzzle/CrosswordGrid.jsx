import React, { useEffect, useRef } from 'react';
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

    return (
        <div className="crossword-grid" ref={gridRef}>
            {grid.map((row, y) => (
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
                            onClick={() => handleCellClick(x, y)}
                        >
                            {!cell.isBlack && (
                                <span className="cell-content">
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