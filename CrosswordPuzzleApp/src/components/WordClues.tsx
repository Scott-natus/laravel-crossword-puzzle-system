import React from 'react';

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

interface WordCluesProps {
  wordPositions: WordPosition[];
  selectedWord?: WordPosition | null;
  foundWords?: Set<number>;
  answeredWords?: Set<number>;
  wrongAnswers?: Map<number, number>;
  onWordSelect?: (word: WordPosition) => void;
}

const WordClues: React.FC<WordCluesProps> = ({
  wordPositions,
  selectedWord,
  foundWords = new Set(),
  answeredWords = new Set(),
  wrongAnswers = new Map(),
  onWordSelect
}) => {
  const horizontalWords = wordPositions.filter(word => word.direction === 0);
  const verticalWords = wordPositions.filter(word => word.direction === 1);

  const isWordFound = (wordId: number) => foundWords.has(wordId);
  const isWordAnswered = (wordId: number) => answeredWords.has(wordId);
  const getWrongCount = (wordId: number) => wrongAnswers.get(wordId) || 0;
  const isWordSelected = (word: WordPosition) => selectedWord?.id === word.id;

  const renderWordList = (words: WordPosition[], title: string) => (
    <div style={{ marginBottom: '20px' }}>
      <h3 style={{ 
        margin: '0 0 10px 0', 
        fontSize: '16px', 
        fontWeight: 'bold',
        color: '#333',
        borderBottom: '2px solid #007bff',
        paddingBottom: '5px'
      }}>
        {title}
      </h3>
      <div style={{ maxHeight: '200px', overflowY: 'auto' }}>
        {words.map((word) => {
          const isFound = isWordFound(word.word_id);
          const isAnswered = isWordAnswered(word.word_id);
          const wrongCount = getWrongCount(word.word_id);
          const isSelected = isWordSelected(word);
          
          return (
            <div
              key={word.id}
              style={{
                padding: '8px 12px',
                margin: '4px 0',
                borderRadius: '6px',
                cursor: 'pointer',
                backgroundColor: isSelected ? '#007bff' : isFound ? '#d4edda' : '#f8f9fa',
                border: isSelected ? '2px solid #0056b3' : '1px solid #dee2e6',
                color: isSelected ? 'white' : isFound ? '#155724' : '#495057',
                fontWeight: isSelected || isFound ? 'bold' : 'normal',
                transition: 'all 0.2s ease',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center'
              }}
              onClick={() => onWordSelect?.(word)}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span style={{ 
                  fontSize: '12px', 
                  fontWeight: 'bold',
                  color: word.direction === 0 ? '#ff6b6b' : '#4ecdc4',
                  backgroundColor: 'white',
                  borderRadius: '50%',
                  width: '20px',
                  height: '20px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  border: `2px solid ${word.direction === 0 ? '#ff6b6b' : '#4ecdc4'}`
                }}>
                  {word.word_id}
                </span>
                <span style={{ 
                  fontSize: '14px',
                  textDecoration: isAnswered ? 'line-through' : 'none',
                  opacity: isAnswered ? 0.6 : 1
                }}>
                  {word.hint}
                </span>
              </div>
              {wrongCount > 0 && (
                <span style={{ 
                  fontSize: '12px', 
                  color: '#dc3545',
                  fontWeight: 'bold'
                }}>
                  ✗ {wrongCount}
                </span>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );

  return (
    <div style={{ 
      width: '100%', 
      maxWidth: '400px',
      backgroundColor: 'white',
      borderRadius: '8px',
      padding: '20px',
      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
      border: '1px solid #dee2e6'
    }}>
      <h2 style={{ 
        margin: '0 0 20px 0', 
        fontSize: '18px', 
        fontWeight: 'bold',
        color: '#333',
        textAlign: 'center',
        borderBottom: '3px solid #007bff',
        paddingBottom: '10px'
      }}>
        단어 힌트
      </h2>
      
      {renderWordList(horizontalWords, '가로')}
      {renderWordList(verticalWords, '세로')}
      
      <div style={{ 
        marginTop: '15px', 
        padding: '10px',
        backgroundColor: '#f8f9fa',
        borderRadius: '6px',
        fontSize: '12px',
        color: '#6c757d',
        textAlign: 'center'
      }}>
        <div>✓ 완료된 단어</div>
        <div>✗ 틀린 횟수 표시</div>
        <div>클릭하여 단어 선택</div>
      </div>
    </div>
  );
};

export default WordClues; 