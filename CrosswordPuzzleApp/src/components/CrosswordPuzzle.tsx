import React, { useState, useEffect } from 'react';
import CrosswordGrid from './CrosswordGrid';
import WordClues from './WordClues';

interface WordPosition {
  id: number;
  word_id: number;
  word: string;
  hint: string;
  start_x: number;
  start_y: number;
  end_x: number;
  end_y: number;
  direction: number;
}

interface CrosswordPuzzleProps {
  puzzleData: {
    grid: string[][];
    wordPositions: WordPosition[];
  };
  onComplete?: () => void;
}

const CrosswordPuzzle: React.FC<CrosswordPuzzleProps> = ({
  puzzleData,
  onComplete
}) => {
  const [userInput, setUserInput] = useState<Record<string, string>>({});
  const [selectedCell, setSelectedCell] = useState<{ x: number; y: number } | null>(null);
  const [foundWords, setFoundWords] = useState<Set<number>>(new Set());
  const [answeredWords, setAnsweredWords] = useState<Set<number>>(new Set());
  const [wrongAnswers, setWrongAnswers] = useState<Map<number, number>>(new Map());
  const [selectedWord, setSelectedWord] = useState<WordPosition | null>(null);

  const handleCellSelect = (x: number, y: number) => {
    setSelectedCell({ x, y });
  };

  const handleCharInput = (x: number, y: number, char: string) => {
    const newInput = { ...userInput };
    if (char === '') {
      delete newInput[`${x},${y}`];
    } else {
      newInput[`${x},${y}`] = char;
    }
    setUserInput(newInput);
    
    // 자동으로 다음 셀로 이동
    const nextCell = getNextCell(x, y);
    if (nextCell) {
      setSelectedCell(nextCell);
    }
  };

  const getNextCell = (x: number, y: number) => {
    const { grid } = puzzleData;
    const maxX = grid[0].length - 1;
    const maxY = grid.length - 1;
    
    // 오른쪽으로 이동
    if (x < maxX && grid[y][x + 1] !== '2') {
      return { x: x + 1, y };
    }
    // 다음 줄로 이동
    if (y < maxY) {
      for (let nextY = y + 1; nextY <= maxY; nextY++) {
        for (let nextX = 0; nextX <= maxX; nextX++) {
          if (grid[nextY][nextX] !== '2') {
            return { x: nextX, y: nextY };
          }
        }
      }
    }
    return null;
  };

  const handleWordSelect = (word: WordPosition) => {
    setSelectedWord(word);
  };

  const checkWordCompletion = () => {
    const { wordPositions } = puzzleData;
    const newFoundWords = new Set(foundWords);
    const newAnsweredWords = new Set(answeredWords);
    const newWrongAnswers = new Map(wrongAnswers);

    wordPositions.forEach(wordPos => {
      const wordCells = getWordCells(wordPos);
      const userWord = wordCells.map(cell => userInput[`${cell.x},${cell.y}`] || '').join('');
      
      if (userWord.length === wordPos.word.length) {
        if (userWord.toLowerCase() === wordPos.word.toLowerCase()) {
          newFoundWords.add(wordPos.word_id);
          newAnsweredWords.add(wordPos.word_id);
          newWrongAnswers.delete(wordPos.word_id);
        } else {
          const wrongCount = (newWrongAnswers.get(wordPos.word_id) || 0) + 1;
          newWrongAnswers.set(wordPos.word_id, wrongCount);
        }
      }
    });

    setFoundWords(newFoundWords);
    setAnsweredWords(newAnsweredWords);
    setWrongAnswers(newWrongAnswers);
  };

  const getWordCells = (wordPos: WordPosition) => {
    const cells = [];
    const { start_x, start_y, end_x, end_y, direction } = wordPos;
    
    if (direction === 0) { // horizontal
      for (let x = start_x; x <= end_x; x++) {
        cells.push({ x, y: start_y });
      }
    } else { // vertical
      for (let y = start_y; y <= end_y; y++) {
        cells.push({ x: start_x, y });
      }
    }
    
    return cells;
  };

  useEffect(() => {
    checkWordCompletion();
  }, [userInput]);

  return (
    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '20px', padding: '20px' }}>
      <CrosswordGrid
        grid={puzzleData.grid}
        wordPositions={puzzleData.wordPositions}
        userInput={userInput}
        selectedCell={selectedCell}
        foundWords={foundWords}
        answeredWords={answeredWords}
        wrongAnswers={wrongAnswers}
        onCellSelect={handleCellSelect}
        onCharInput={handleCharInput}
        onWordSelect={handleWordSelect}
      />
      <WordClues
        wordPositions={puzzleData.wordPositions}
        selectedWord={selectedWord}
        foundWords={foundWords}
        answeredWords={answeredWords}
        wrongAnswers={wrongAnswers}
        onWordSelect={handleWordSelect}
      />
    </div>
  );
};

export default CrosswordPuzzle; 