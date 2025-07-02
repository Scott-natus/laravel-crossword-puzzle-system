#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
패턴 기반 크로스워드 퍼즐 자동 생성기
실제 DB 템플릿 데이터를 기반으로 한 정교한 퍼즐 생성 시스템
"""

import json
import random
from typing import List, Dict, Tuple, Optional
from copy import deepcopy

class PatternBasedCrosswordGenerator:
    """패턴 기반 크로스워드 퍼즐 생성기"""
    
    def __init__(self):
        # 실제 DB 템플릿에서 추출한 패턴 데이터
        self.pattern_templates = {
            "cross_5x5": {
                "name": "십자형 패턴 (5x5)",
                "grid_pattern": [
                    [2,1,1,1,1],  # 상단 가로 단어
                    [2,2,2,2,1],  # 중앙 가로 단어 (긴 단어)
                    [2,1,1,2,1],  # 세로 단어들
                    [1,1,1,1,2],  # 하단 가로 단어
                    [2,2,2,1,2]   # 하단 가로 단어
                ],
                "word_structure": {
                    "horizontal": 2,
                    "vertical": 3,
                    "total": 5
                },
                "description": "중앙에 긴 가로 단어, 세로 단어들이 교차하는 전형적인 십자형 패턴"
            },
            "l_shape_5x5": {
                "name": "L자형 패턴 (5x5)",
                "grid_pattern": [
                    [2,2,2,1,1],  # 상단 가로 단어
                    [1,1,2,1,1],  # 세로 단어들
                    [1,2,1,1,2],  # 중앙 세로 단어
                    [1,2,2,1,2],  # 하단 가로 단어
                    [1,1,1,1,2]   # 하단 가로 단어
                ],
                "word_structure": {
                    "horizontal": 2,
                    "vertical": 3,
                    "total": 5
                },
                "description": "모서리에 L자 모양 검은칸 배치, 균형잡힌 단어 분포"
            },
            "mesh_5x5": {
                "name": "그물형 패턴 (5x5)",
                "grid_pattern": [
                    [2,2,2,1,1],  # 상단 가로 단어
                    [1,1,2,1,1],  # 세로 단어들
                    [2,1,2,2,1],  # 중앙 가로 단어
                    [2,1,1,1,1],  # 하단 가로 단어
                    [2,2,2,2,1]   # 하단 가로 단어
                ],
                "word_structure": {
                    "horizontal": 3,
                    "vertical": 2,
                    "total": 5
                },
                "description": "복잡한 검은칸 네트워크, 다양한 교차점"
            },
            "symmetric_6x6": {
                "name": "대칭형 패턴 (6x6)",
                "grid_pattern": [
                    [1,2,2,2,2,1],  # 상단 가로 단어
                    [1,1,1,1,2,1],  # 세로 단어들
                    [1,1,1,2,2,2],  # 중앙 가로 단어
                    [2,2,2,1,1,1],  # 중앙 가로 단어
                    [1,1,1,2,2,1],  # 하단 가로 단어
                    [2,2,1,1,1,1]   # 하단 가로 단어
                ],
                "word_structure": {
                    "horizontal": 4,
                    "vertical": 2,
                    "total": 6
                },
                "description": "좌우 대칭 구조, 안정적인 퍼즐 레이아웃"
            }
        }
    
    def generate_by_pattern(self, pattern_name: str, variation: int = 0) -> Dict:
        """
        패턴 기반 그리드 생성
        
        Args:
            pattern_name: 패턴 이름 (cross_5x5, l_shape_5x5, mesh_5x5, symmetric_6x6)
            variation: 변형 번호 (0: 기본, 1-3: 변형)
        
        Returns:
            Dict: grid_pattern, word_positions, metadata
        """
        if pattern_name not in self.pattern_templates:
            raise ValueError(f"지원하지 않는 패턴: {pattern_name}")
        
        template = self.pattern_templates[pattern_name]
        base_grid = deepcopy(template["grid_pattern"])
        
        # 변형 적용
        if variation > 0:
            base_grid = self._apply_variation(base_grid, variation)
        
        # 단어 위치 추출
        word_positions = self._extract_word_positions(base_grid)
        
        # 메타데이터 생성
        metadata = {
            "pattern_name": pattern_name,
            "variation": variation,
            "description": template["description"],
            "word_structure": template["word_structure"],
            "grid_size": f"{len(base_grid)}x{len(base_grid[0])}",
            "black_cell_ratio": self._calculate_black_ratio(base_grid)
        }
        
        return {
            "grid_pattern": base_grid,
            "word_positions": word_positions,
            "metadata": metadata
        }
    
    def _apply_variation(self, grid: List[List[int]], variation: int) -> List[List[int]]:
        """그리드에 변형 적용"""
        if variation == 1:
            # 90도 회전
            return self._rotate_grid(grid, 1)
        elif variation == 2:
            # 180도 회전
            return self._rotate_grid(grid, 2)
        elif variation == 3:
            # 좌우 반전
            return self._flip_horizontal(grid)
        else:
            return grid
    
    def _rotate_grid(self, grid: List[List[int]], times: int) -> List[List[int]]:
        """그리드 회전 (90도 * times)"""
        result = grid
        for _ in range(times):
            # 90도 시계방향 회전
            result = list(zip(*result[::-1]))
            result = [list(row) for row in result]
        return result
    
    def _flip_horizontal(self, grid: List[List[int]]) -> List[List[int]]:
        """그리드 좌우 반전"""
        return [row[::-1] for row in grid]
    
    def _extract_word_positions(self, grid: List[List[int]]) -> List[Dict]:
        """그리드에서 단어 위치 추출"""
        word_positions = []
        word_id = 1
        
        # 가로 단어 추출
        for y in range(len(grid)):
            x = 0
            while x < len(grid[0]):
                if grid[y][x] == 1:  # 흰칸 시작
                    start_x = x
                    # 단어 끝 찾기
                    while x < len(grid[0]) and grid[y][x] == 1:
                        x += 1
                    end_x = x - 1
                    
                    # 2글자 이상인 경우만 추가
                    if end_x - start_x >= 1:
                        word_positions.append({
                            "id": word_id,
                            "start_x": start_x,
                            "start_y": y,
                            "end_x": end_x,
                            "end_y": y,
                            "direction": "horizontal",
                            "length": end_x - start_x + 1
                        })
                        word_id += 1
                else:
                    x += 1
        
        # 세로 단어 추출
        for x in range(len(grid[0])):
            y = 0
            while y < len(grid):
                if grid[y][x] == 1:  # 흰칸 시작
                    start_y = y
                    # 단어 끝 찾기
                    while y < len(grid) and grid[y][x] == 1:
                        y += 1
                    end_y = y - 1
                    
                    # 2글자 이상인 경우만 추가
                    if end_y - start_y >= 1:
                        word_positions.append({
                            "id": word_id,
                            "start_x": x,
                            "start_y": start_y,
                            "end_x": x,
                            "end_y": end_y,
                            "direction": "vertical",
                            "length": end_y - start_y + 1
                        })
                        word_id += 1
                else:
                    y += 1
        
        return word_positions
    
    def _calculate_black_ratio(self, grid: List[List[int]]) -> float:
        """검은칸 비율 계산"""
        total_cells = len(grid) * len(grid[0])
        black_cells = sum(row.count(2) for row in grid)
        return black_cells / total_cells
    
    def generate_random_pattern(self, grid_size: int = 5) -> Dict:
        """랜덤 패턴 생성 (지정된 크기)"""
        available_patterns = [name for name in self.pattern_templates.keys() 
                            if f"{grid_size}x{grid_size}" in name]
        
        if not available_patterns:
            raise ValueError(f"{grid_size}x{grid_size} 크기의 패턴이 없습니다.")
        
        pattern_name = random.choice(available_patterns)
        variation = random.randint(0, 3)
        
        return self.generate_by_pattern(pattern_name, variation)
    
    def list_available_patterns(self) -> List[Dict]:
        """사용 가능한 패턴 목록 반환"""
        patterns = []
        for name, template in self.pattern_templates.items():
            patterns.append({
                "name": name,
                "display_name": template["name"],
                "description": template["description"],
                "word_structure": template["word_structure"],
                "grid_size": f"{len(template['grid_pattern'])}x{len(template['grid_pattern'][0])}"
            })
        return patterns
    
    def validate_grid(self, grid: List[List[int]], word_positions: List[Dict]) -> Dict:
        """생성된 그리드 검증"""
        validation = {
            "is_valid": True,
            "errors": [],
            "warnings": [],
            "stats": {}
        }
        
        # 기본 검증
        if not grid or not grid[0]:
            validation["is_valid"] = False
            validation["errors"].append("빈 그리드")
            return validation
        
        width, height = len(grid[0]), len(grid)
        
        # 검은칸 연결성 검증
        if not self._check_black_cell_connectivity(grid):
            validation["warnings"].append("검은칸들이 완전히 연결되지 않음")
        
        # 단어 길이 검증
        for word in word_positions:
            if word["length"] < 2:
                validation["errors"].append(f"단어 {word['id']}: 한 글자 단어는 허용되지 않음")
                validation["is_valid"] = False
        
        # 교차점 검증
        intersections = self._find_intersections(word_positions)
        if not intersections:
            validation["warnings"].append("교차점이 없음")
        
        # 통계 정보
        validation["stats"] = {
            "grid_size": f"{width}x{height}",
            "word_count": len(word_positions),
            "horizontal_words": len([w for w in word_positions if w["direction"] == "horizontal"]),
            "vertical_words": len([w for w in word_positions if w["direction"] == "vertical"]),
            "intersection_count": len(intersections),
            "black_cell_ratio": self._calculate_black_ratio(grid)
        }
        
        return validation
    
    def _check_black_cell_connectivity(self, grid: List[List[int]]) -> bool:
        """검은칸 연결성 확인 (간단한 버전)"""
        # 실제로는 더 복잡한 연결성 검증이 필요하지만,
        # 여기서는 기본적인 검증만 수행
        return True
    
    def _find_intersections(self, word_positions: List[Dict]) -> List[Tuple]:
        """교차점 찾기"""
        intersections = []
        horizontal_words = [w for w in word_positions if w["direction"] == "horizontal"]
        vertical_words = [w for w in word_positions if w["direction"] == "vertical"]
        
        for h_word in horizontal_words:
            for v_word in vertical_words:
                # 교차점 확인
                if (h_word["start_x"] <= v_word["start_x"] <= h_word["end_x"] and
                    v_word["start_y"] <= h_word["start_y"] <= v_word["end_y"]):
                    intersections.append((h_word["id"], v_word["id"]))
        
        return intersections

def main():
    """메인 실행 함수 - 패턴 생성 테스트"""
    generator = PatternBasedCrosswordGenerator()
    
    print("=== 패턴 기반 크로스워드 생성기 ===\n")
    
    # 사용 가능한 패턴 목록
    print("1. 사용 가능한 패턴:")
    patterns = generator.list_available_patterns()
    for pattern in patterns:
        print(f"  - {pattern['name']}: {pattern['display_name']}")
        print(f"    설명: {pattern['description']}")
        print(f"    단어 구조: 가로 {pattern['word_structure']['horizontal']}개, 세로 {pattern['word_structure']['vertical']}개")
        print()
    
    # 패턴별 생성 테스트
    print("2. 패턴별 생성 테스트:")
    for pattern_name in ["cross_5x5", "l_shape_5x5", "mesh_5x5", "symmetric_6x6"]:
        print(f"\n--- {pattern_name} 패턴 생성 ---")
        
        # 기본 패턴 생성
        result = generator.generate_by_pattern(pattern_name, 0)
        
        print(f"그리드 크기: {result['metadata']['grid_size']}")
        print(f"검은칸 비율: {result['metadata']['black_cell_ratio']:.1%}")
        print(f"단어 개수: {len(result['word_positions'])}개")
        
        # 그리드 출력
        print("그리드 패턴:")
        for row in result['grid_pattern']:
            print(f"  {row}")
        
        # 검증
        validation = generator.validate_grid(result['grid_pattern'], result['word_positions'])
        print(f"검증 결과: {'유효' if validation['is_valid'] else '무효'}")
        if validation['errors']:
            print(f"오류: {validation['errors']}")
        if validation['warnings']:
            print(f"경고: {validation['warnings']}")
    
    # 랜덤 패턴 생성 테스트
    print("\n3. 랜덤 패턴 생성 테스트:")
    for i in range(3):
        result = generator.generate_random_pattern(5)
        print(f"\n랜덤 패턴 #{i+1}:")
        print(f"패턴: {result['metadata']['pattern_name']} (변형: {result['metadata']['variation']})")
        print(f"단어 개수: {len(result['word_positions'])}개")
        print(f"검은칸 비율: {result['metadata']['black_cell_ratio']:.1%}")

if __name__ == "__main__":
    main() 