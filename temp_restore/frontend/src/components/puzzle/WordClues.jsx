import React from 'react';
import './WordClues.css';

const WordClues = ({
    words,
    wordPositions,
    foundWords,
    selectedWord,
    onWordSelect
}) => {
    const horizontalWords = words.filter(word => word.direction === 0);
    const verticalWords = words.filter(word => word.direction === 1);

    const isWordFound = (word) => {
        return foundWords.has(word.word);
    };

    const isWordSelected = (word) => {
        return selectedWord && selectedWord.word_id === word.id;
    };

    const handleWordClick = (word) => {
        const wordPosition = wordPositions.find(wp => wp.word_id === word.id);
        onWordSelect(wordPosition);
    };

    return (
        <div className="word-clues">
            <div className="clues-section">
                <h3>가로</h3>
                <div className="clues-list">
                    {horizontalWords.map((word, index) => (
                        <div
                            key={word.id}
                            className={`clue-item ${
                                isWordFound(word) ? 'found' : ''
                            } ${
                                isWordSelected(word) ? 'selected' : ''
                            }`}
                            onClick={() => handleWordClick(word)}
                        >
                            <span className="clue-number">{index + 1}.</span>
                            <span className="clue-text">{word.clue}</span>
                            {isWordFound(word) && (
                                <span className="found-indicator">✓</span>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="clues-section">
                <h3>세로</h3>
                <div className="clues-list">
                    {verticalWords.map((word, index) => (
                        <div
                            key={word.id}
                            className={`clue-item ${
                                isWordFound(word) ? 'found' : ''
                            } ${
                                isWordSelected(word) ? 'selected' : ''
                            }`}
                            onClick={() => handleWordClick(word)}
                        >
                            <span className="clue-number">{horizontalWords.length + index + 1}.</span>
                            <span className="clue-text">{word.clue}</span>
                            {isWordFound(word) && (
                                <span className="found-indicator">✓</span>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="progress-summary">
                <p>완성된 단어: {foundWords.size} / {words.length}</p>
            </div>
        </div>
    );
};

export default WordClues; 