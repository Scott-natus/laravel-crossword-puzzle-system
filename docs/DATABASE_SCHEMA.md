# 데이터베이스 스키마 문서

## 데이터베이스 정보
- **타입**: PostgreSQL
- **데이터베이스명**: mydb
- **사용자**: myuser
- **접속**: `PGPASSWORD=tngkrrhk psql -h 127.0.0.1 -U myuser -d mydb`

## 주요 테이블

### 1. puzzle_words (퍼즐 단어 테이블)
```sql
CREATE TABLE puzzle_words (
    id BIGSERIAL PRIMARY KEY,
    word VARCHAR(255) NOT NULL,
    category VARCHAR(255),
    difficulty INTEGER,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2. puzzle_hints (퍼즐 힌트 테이블)
```sql
CREATE TABLE puzzle_hints (
    id BIGSERIAL PRIMARY KEY,
    word_id BIGINT REFERENCES puzzle_words(id) ON DELETE CASCADE,
    hint_text TEXT NOT NULL,
    hint_type VARCHAR(50) DEFAULT 'ai_generated',
    difficulty VARCHAR(20) NOT NULL, -- 'easy', 'medium', 'hard'
    is_primary BOOLEAN DEFAULT false,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 3. puzzle_levels (퍼즐 레벨 테이블)
```sql
CREATE TABLE puzzle_levels (
    id BIGSERIAL PRIMARY KEY,
    level INTEGER UNIQUE NOT NULL, -- 1-100 이상 가능 (제한 없음)
    level_name VARCHAR(255) NOT NULL,
    word_count INTEGER NOT NULL,
    word_difficulty INTEGER NOT NULL, -- 1-5
    hint_difficulty VARCHAR(20) NOT NULL, -- 'easy', 'medium', 'hard'
    intersection_count INTEGER NOT NULL,
    time_limit INTEGER NOT NULL, -- 초 단위
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    updated_by VARCHAR(255)
);
```

### 4. crossword_puzzles (크로스워드 퍼즐 테이블)
```sql
CREATE TABLE crossword_puzzles (
    id SERIAL PRIMARY KEY,
    level_id INTEGER NOT NULL,
    name VARCHAR(255) NOT NULL,
    grid_size INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5. crossword_words (크로스워드 단어 테이블)
```sql
CREATE TABLE crossword_words (
    id SERIAL PRIMARY KEY,
    puzzle_id INTEGER REFERENCES crossword_puzzles(id) ON DELETE CASCADE,
    word VARCHAR(50) NOT NULL,
    clue TEXT NOT NULL,
    start_x INTEGER NOT NULL,
    start_y INTEGER NOT NULL,
    direction INTEGER NOT NULL CHECK (direction = ANY (ARRAY[0, 1])), -- 0: horizontal, 1: vertical
    length INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 6. crossword_grid (크로스워드 그리드 테이블)
```sql
CREATE TABLE crossword_grid (
    id SERIAL PRIMARY KEY,
    puzzle_id INTEGER REFERENCES crossword_puzzles(id) ON DELETE CASCADE,
    x INTEGER NOT NULL,
    y INTEGER NOT NULL,
    char_value VARCHAR(10),
    is_black BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 제약조건 정보

### puzzle_levels 테이블 제약조건
- **PRIMARY KEY**: id
- **UNIQUE**: level (중복 레벨 번호 불가)
- **NOT NULL**: level, level_name, word_count, word_difficulty, hint_difficulty, intersection_count, time_limit
- **체크 제약조건**: 없음 (level 100 이상 제한 없음)

### puzzle_hints 테이블 제약조건
- **PRIMARY KEY**: id
- **FOREIGN KEY**: word_id → puzzle_words(id) CASCADE
- **NOT NULL**: hint_text, difficulty
- **기본값**: hint_type = 'ai_generated', is_primary = false

## 레벨별 명칭 규칙
```php
// PuzzleLevel::getLevelName($level)
1-10:     실마리 발견자 (Clue Spotter)
11-25:    단서 수집가 (Clue Collector)
26-50:    논리적 추적자 (Logical Tracer)
51-75:    미궁의 해설가 (Labyrinth Commentator)
76-99:    진실의 파수꾼 (Guardian of Truth)
100+:     절대적 해답 (Absolute Resolution)
```

## 관련 테이블들
- **puzzle_grid_rules**: 레벨별 그리드 규칙
- **puzzle_grid_templates**: 레벨별 그리드 템플릿
- **puzzle_hint_limits**: 레벨별 힌트 제한
- **puzzle_scoring_rules**: 레벨별 점수 규칙
- **puzzle_sessions**: 퍼즐 세션 기록

## 유용한 쿼리

### 테이블 구조 확인
```sql
\d puzzle_levels
\d puzzle_words
\d puzzle_hints
```

### 제약조건 확인
```sql
SELECT conname, contype, pg_get_constraintdef(oid) as definition 
FROM pg_constraint 
WHERE conrelid = 'puzzle_levels'::regclass;
```

### 컬럼 정보 확인
```sql
SELECT column_name, data_type, is_nullable, column_default 
FROM information_schema.columns 
WHERE table_name = 'puzzle_levels';
```

## 백업 명령어
```bash
# 전체 데이터베이스 백업
pg_dump -h 127.0.0.1 -U myuser mydb > backup_$(date +%Y%m%d_%H%M%S).sql

# 특정 테이블만 백업
pg_dump -h 127.0.0.1 -U myuser -t puzzle_levels mydb > puzzle_levels_backup.sql
```

## 최근 변경 이력
- 2025-06-22: puzzle_levels 테이블 백업 후 신규 구조로 일괄 업데이트
- 2025-06-22: 대량 힌트 생성 스케줄러 도입
- 2025-06-21: 중복 단어 정리 및 넘버링/정규화
- 2025-06-22: crossword_words 테이블에 updated_at 컬럼 추가 (React 퍼즐 생성 기능 지원)
- 2025-06-22: 크로스워드 퍼즐 관련 테이블들 (crossword_puzzles, crossword_words, crossword_grid) 추가 