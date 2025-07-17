<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Laravel 환경 설정
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "완성형 한글 음절 생성 시작...\n";

// 완성형 한글 범위: 가(0xAC00) ~ 힣(0xD7A3)
$start = 0xAC00;
$end = 0xD7A3;

$syllables = [];
$count = 0;

echo "한글 음절 생성 중...\n";

for ($i = $start; $i <= $end; $i++) {
    // UTF-8 인코딩으로 한글 문자 생성
    $syllable = mb_chr($i, 'UTF-8');
    $syllables[] = [
        'consonant' => getInitialConsonant($syllable),
        'syllable' => $syllable,
        'created_at' => now()
    ];
    $count++;
    
    if ($count % 1000 == 0) {
        echo "진행률: {$count}/11,172\n";
    }
}

echo "총 {$count}개의 한글 음절 생성 완료\n";

// 기존 데이터 삭제
DB::table('temp_hangul_syllables')->truncate();
echo "기존 데이터 삭제 완료\n";

// 데이터베이스에 저장 (한 번에 500개씩)
$chunks = array_chunk($syllables, 500);
foreach ($chunks as $index => $chunk) {
    try {
        DB::table('temp_hangul_syllables')->insert($chunk);
        echo "청크 " . ($index + 1) . "/" . count($chunks) . " 저장 완료\n";
    } catch (Exception $e) {
        echo "청크 " . ($index + 1) . " 저장 실패: " . $e->getMessage() . "\n";
        // 실패한 청크는 개별적으로 저장
        foreach ($chunk as $syllable) {
            try {
                DB::table('temp_hangul_syllables')->insert($syllable);
            } catch (Exception $e2) {
                echo "음절 '{$syllable['syllable']}' 저장 실패: " . $e2->getMessage() . "\n";
            }
        }
    }
}

echo "데이터베이스 저장 완료\n";

// 결과 확인
$totalCount = DB::table('temp_hangul_syllables')->count();
echo "저장된 음절 수: {$totalCount}\n";

// 자음별 개수 확인
$consonantCounts = DB::table('temp_hangul_syllables')
    ->select('consonant', DB::raw('COUNT(*) as count'))
    ->groupBy('consonant')
    ->orderBy('consonant')
    ->get();

echo "\n자음별 음절 개수:\n";
foreach ($consonantCounts as $row) {
    echo "{$row->consonant}: {$row->count}개\n";
}

/**
 * 한글 음절에서 초성(자음) 추출
 */
function getInitialConsonant($syllable) {
    $code = mb_ord($syllable, 'UTF-8');
    
    if ($code < 0xAC00 || $code > 0xD7A3) {
        return '';
    }
    
    $syllableIndex = $code - 0xAC00;
    $consonantIndex = intval($syllableIndex / (21 * 28));
    
    $consonants = ['ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];
    
    return $consonants[$consonantIndex];
}

echo "\n완료!\n"; 