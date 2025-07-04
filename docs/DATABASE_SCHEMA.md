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

### 7. boards (게시판 테이블)
```sql
CREATE TABLE boards (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    password VARCHAR(255) NOT NULL,
    comment_notify BOOLEAN DEFAULT false,
    views INTEGER DEFAULT 0,
    parent_id BIGINT REFERENCES boards(id) ON DELETE CASCADE,
    board_type_id BIGINT REFERENCES board_types(id) ON DELETE CASCADE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 8. board_types (게시판 타입 테이블)
```sql
CREATE TABLE board_types (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL, -- URL 구분용 (bbs1, talk, proj)
    description TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 9. puzzle_grid_templates (퍼즐 그리드 템플릿 테이블)
```sql
CREATE TABLE puzzle_grid_templates (
    id BIGSERIAL PRIMARY KEY,
    level_id BIGINT REFERENCES puzzle_levels(id) ON DELETE CASCADE,
    template_name VARCHAR(255) NOT NULL,
    grid_pattern JSONB NOT NULL, -- 그리드 패턴 (2차원 배열)
    word_positions JSONB NOT NULL, -- 단어 위치 정보 (id, start_x, start_y, end_x, end_y, direction, length)
    grid_width INTEGER NOT NULL,
    grid_height INTEGER NOT NULL,
    difficulty_rating INTEGER,
    word_count INTEGER NOT NULL,
    intersection_count INTEGER NOT NULL,
    category VARCHAR(50) DEFAULT 'custom',
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**중요**: word_positions의 id 값은 사용자가 설정한 번호를 그대로 저장하며, 자동 정렬하지 않음

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

### boards 테이블 제약조건
- **PRIMARY KEY**: id
- **FOREIGN KEY**: user_id → users(id) CASCADE
- **FOREIGN KEY**: parent_id → boards(id) CASCADE (답글 구조)
- **FOREIGN KEY**: board_type_id → board_types(id) CASCADE
- **NOT NULL**: title, content, password, board_type_id

### board_types 테이블 제약조건
- **PRIMARY KEY**: id
- **UNIQUE**: slug (URL 중복 불가)
- **NOT NULL**: name, slug

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

## 게시판 타입 정보
```sql
-- 기본 게시판 타입들
INSERT INTO board_types (name, slug, description) VALUES
('주저리', 'bbs1', '일반 게시판'),
('자유게시판', 'talk', '자유로운 토론 공간'),
('프로젝트', 'proj', '프로젝트 관련 게시판');
```

## 관련 테이블들
- **puzzle_grid_rules**: 레벨별 그리드 규칙
- **puzzle_grid_templates**: 레벨별 그리드 템플릿
- **puzzle_hint_limits**: 레벨별 힌트 제한
- **puzzle_scoring_rules**: 레벨별 점수 규칙
- **puzzle_sessions**: 퍼즐 세션 기록
- **board_attachments**: 게시판 첨부파일
- **board_comments**: 게시판 댓글

## 유용한 쿼리

### 테이블 구조 확인
```sql
\d puzzle_levels
\d puzzle_words
\d puzzle_hints
\d boards
\d board_types
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

### 게시판별 글 수 확인
```sql
SELECT bt.name, bt.slug, COUNT(b.id) as post_count
FROM board_types bt
LEFT JOIN boards b ON bt.id = b.board_type_id
GROUP BY bt.id, bt.name, bt.slug
ORDER BY bt.id;
```

## 백업 명령어
```bash
# 전체 데이터베이스 백업
pg_dump -h 127.0.0.1 -U myuser mydb > backup_$(date +%Y%m%d_%H%M%S).sql

# 특정 테이블만 백업
pg_dump -h 127.0.0.1 -U myuser -t puzzle_levels mydb > puzzle_levels_backup.sql

# 게시판 관련 테이블 백업
pg_dump -h 127.0.0.1 -U myuser -t boards -t board_types mydb > boards_backup.sql
```

## 최근 변경 이력
- 2025-06-24: 게시판 시스템 로그 설정 개선 및 디버깅 로그 추가
- 2025-06-24: 게시판별 글쓰기 버튼 문제 해결 (boardType 파라미터 직접 읽기 방식으로 변경)
- 2025-06-22: puzzle_levels 테이블 백업 후 신규 구조로 일괄 업데이트
- 2025-06-22: 대량 힌트 생성 스케줄러 도입
- 2025-06-21: 중복 단어 정리 및 넘버링/정규화
- 2025-06-22: crossword_words 테이블에 updated_at 컬럼 추가 (React 퍼즐 생성 기능 지원)
- 2025-06-22: 크로스워드 퍼즐 관련 테이블들 (crossword_puzzles, crossword_words, crossword_grid) 추가 

## 2025-07-01 퍼즐 게임 관련 DB 스키마 작업
- user_puzzle_games 테이블 생성 및 마이그레이션
- puzzle_grid_template_word pivot 테이블 생성
- 퍼즐 정답/진행상황 저장 로직 반영
- 불필요한 컬럼/관계 정리 

## 2025-07-01
- 퍼즐 그리드 템플릿 저장 시 교차점 개수 validation을 '최소값' 기준으로 변경 (기존: 정확히 일치 → 변경: 최소값 이상) 