import React, { useState, useEffect } from 'react';
import CrosswordGrid from './CrosswordGrid';
import WordClues from './WordClues';
import './CrosswordPuzzle.css';

const CrosswordPuzzle = ({ levelId = 1 }) => {
    const [puzzle, setPuzzle] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [userInput, setUserInput] = useState({});
    const [selectedCell, setSelectedCell] = useState(null);
    const [selectedWord, setSelectedWord] = useState(null);
    const [foundWords, setFoundWords] = useState(new Set());
    const [gameComplete, setGameComplete] = useState(false);

    useEffect(() => {
        fetchPuzzle();
    }, [levelId]);

    const fetchPuzzle = async () => {
        try {
            setLoading(true);
            console.log('퍼즐 데이터를 가져오는 중...');
            const response = await fetch(`http://222.100.103.227:5050/api/crossword/puzzle/${levelId}`);
            console.log('API 응답:', response);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API 오류:', errorText);
                throw new Error(`퍼즐을 불러올 수 없습니다. (${response.status})`);
            }
            
            const data = await response.json();
            console.log('퍼즐 데이터:', data);
            setPuzzle(data);
        } catch (err) {
            console.error('퍼즐 로딩 오류:', err);
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleCellSelect = (x, y) => {
        setSelectedCell({ x, y });
        
        // 해당 위치의 단어 찾기
        const word = puzzle.word_positions.find(pos => 
            pos.positions.some(p => p.x === x && p.y === y)
        );
        setSelectedWord(word);
    };

    const handleCharInput = (x, y, char) => {
        if (!char || char.length !== 1) return;

        const newInput = {
            ...userInput,
            [`${x},${y}`]: char.toUpperCase()
        };
        setUserInput(newInput);

        // 다음 셀로 이동
        if (selectedWord) {
            const currentIndex = selectedWord.positions.findIndex(p => p.x === x && p.y === y);
            if (currentIndex !== -1 && currentIndex < selectedWord.positions.length - 1) {
                const nextPos = selectedWord.positions[currentIndex + 1];
                setSelectedCell({ x: nextPos.x, y: nextPos.y });
            }
        }

        // 단어 완성 확인
        checkWordCompletion();
    };

    const checkWordCompletion = () => {
        if (!puzzle) return;

        puzzle.word_positions.forEach(wordPos => {
            const userWord = wordPos.positions
                .map(pos => userInput[`${pos.x},${pos.y}`] || '')
                .join('');

            if (userWord === wordPos.word && !foundWords.has(wordPos.word)) {
                setFoundWords(prev => new Set([...prev, wordPos.word]));
            }
        });

        // 모든 단어 완성 확인
        if (foundWords.size === puzzle.words.length) {
            setGameComplete(true);
        }
    };

    const handleWordSelect = (word) => {
        setSelectedWord(word);
        if (word.positions.length > 0) {
            setSelectedCell({ x: word.positions[0].x, y: word.positions[0].y });
        }
    };

    if (loading) {
        return (
            <div className="crossword-puzzle loading">
                <div className="loading-spinner">
                    <div className="spinner"></div>
                    <p>퍼즐을 불러오는 중...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="crossword-puzzle error">
                <div className="error-message">
                    <h3>오류가 발생했습니다</h3>
                    <p>{error}</p>
                    <button onClick={fetchPuzzle}>다시 시도</button>
                </div>
            </div>
        );
    }

    if (!puzzle) {
        return (
            <div className="crossword-puzzle error">
                <div className="error-message">
                    <h3>퍼즐을 찾을 수 없습니다</h3>
                    <p>레벨 {levelId}의 퍼즐이 존재하지 않습니다.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="crossword-puzzle">
            <div className="puzzle-header">
                <h2>{puzzle.puzzle.name}</h2>
                <p>{puzzle.puzzle.description}</p>
                <div className="progress-info">
                    <span>완성된 단어: {foundWords.size} / {puzzle.words.length}</span>
                </div>
            </div>

            <div className="puzzle-content">
                <div className="grid-container">
                    <CrosswordGrid
                        grid={puzzle.grid}
                        wordPositions={puzzle.word_positions}
                        userInput={userInput}
                        selectedCell={selectedCell}
                        foundWords={foundWords}
                        onCellSelect={handleCellSelect}
                        onCharInput={handleCharInput}
                    />
                </div>

                <div className="clues-container">
                    <WordClues
                        words={puzzle.words}
                        wordPositions={puzzle.word_positions}
                        foundWords={foundWords}
                        selectedWord={selectedWord}
                        onWordSelect={handleWordSelect}
                    />
                </div>
            </div>

            {gameComplete && (
                <div className="completion-modal">
                    <div className="modal-content">
                        <h2>🎉 퍼즐 완성!</h2>
                        <p>모든 단어를 찾았습니다!</p>
                        <button onClick={() => window.location.reload()}>
                            다시 플레이
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CrosswordPuzzle; 