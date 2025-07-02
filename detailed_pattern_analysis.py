#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
크로스워드 퍼즐 템플릿 패턴 분석 및 자동 생성
실제 DB 템플릿 데이터를 기반으로 한 패턴 기반 생성 시스템
"""

import json
import re
from typing import List, Dict, Tuple, Optional

class CrosswordPatternAnalyzer:
    """크로스워드 퍼즐 패턴 분석기"""
    
    def __init__(self):
        # 실제 DB 템플릿 데이터 (all_level_grid_template.txt에서 추출)
        self.template_data = [
            # 레벨 1 템플릿 #19 (십자형 패턴)
            {
                "id": 25,
                "name": "레벨 1 템플릿 #19",
                "grid_pattern": [[2,1,1,1,1],[2,2,2,2,1],[2,1,1,2,1],[1,1,1,1,2],[2,2,2,1,2]],
                "word_positions": [
                    {"id":2,"start_x":0,"start_y":1,"end_x":3,"end_y":1,"direction":"horizontal","length":4},
                    {"id":5,"start_x":0,"start_y":4,"end_x":2,"end_y":4,"direction":"horizontal","length":3},
                    {"id":1,"start_x":0,"start_y":0,"end_x":0,"end_y":2,"direction":"vertical","length":3},
                    {"id":3,"start_x":3,"start_y":1,"end_x":3,"end_y":2,"direction":"vertical","length":2},
                    {"id":4,"start_x":4,"start_y":3,"end_x":4,"end_y":4,"direction":"vertical","length":2}
                ],
                "pattern_type": "cross"
            },
            # 레벨 1 템플릿 #8 (L자형 패턴)
            {
                "id": 10,
                "name": "레벨 1 템플릿 #8",
                "grid_pattern": [[2,2,2,1,1],[1,1,2,1,1],[1,2,1,1,2],[1,2,2,1,2],[1,1,1,1,2]],
                "word_positions": [
                    {"id":1,"start_x":0,"start_y":0,"end_x":2,"end_y":0,"direction":"horizontal","length":3},
                    {"id":2,"start_x":2,"start_y":0,"end_x":2,"end_y":1,"direction":"vertical","length":2},
                    {"id":3,"start_x":1,"start_y":2,"end_x":1,"end_y":3,"direction":"vertical","length":2},
                    {"id":4,"start_x":4,"start_y":2,"end_x":4,"end_y":4,"direction":"vertical","length":3},
                    {"id":5,"start_x":1,"start_y":3,"end_x":2,"end_y":3,"direction":"horizontal","length":2}
                ],
                "pattern_type": "l_shape"
            },
            # 레벨 2 템플릿 #4 (그물형 패턴)
            {
                "id": 29,
                "name": "레벨 2 템플릿 #4",
                "grid_pattern": [[2,2,2,1,1],[1,1,2,1,1],[2,1,2,2,1],[2,1,1,1,1],[2,2,2,2,1]],
                "word_positions": [
                    {"id":1,"start_x":0,"start_y":0,"end_x":2,"end_y":0,"direction":"horizontal","length":3},
                    {"id":3,"start_x":2,"start_y":2,"end_x":3,"end_y":2,"direction":"horizontal","length":2},
                    {"id":5,"start_x":0,"start_y":4,"end_x":3,"end_y":4,"direction":"horizontal","length":4},
                    {"id":4,"start_x":0,"start_y":2,"end_x":0,"end_y":4,"direction":"vertical","length":3},
                    {"id":2,"start_x":2,"start_y":0,"end_x":2,"end_y":2,"direction":"vertical","length":3}
                ],
                "pattern_type": "mesh"
            },
            # 레벨 5 템플릿 #1 (대칭형 패턴)
            {
                "id": 21,
                "name": "레벨 5 템플릿 #1",
                "grid_pattern": [[1,2,2,2,2,1],[1,1,1,1,2,1],[1,1,1,2,2,2],[2,2,2,1,1,1],[1,1,1,2,2,1],[2,2,1,1,1,1]],
                "word_positions": [
                    {"id":1,"start_x":1,"start_y":0,"end_x":4,"end_y":0,"direction":"horizontal","length":4},
                    {"id":3,"start_x":3,"start_y":2,"end_x":5,"end_y":2,"direction":"horizontal","length":3},
                    {"id":4,"start_x":0,"start_y":3,"end_x":2,"end_y":3,"direction":"horizontal","length":3},
                    {"id":5,"start_x":3,"start_y":4,"end_x":4,"end_y":4,"direction":"horizontal","length":2},
                    {"id":6,"start_x":0,"start_y":5,"end_x":1,"end_y":5,"direction":"horizontal","length":2},
                    {"id":2,"start_x":4,"start_y":0,"end_x":4,"end_y":2,"direction":"vertical","length":3}
                ],
                "pattern_type": "symmetric"
            }
        ]
    
    def analyze_patterns(self) -> Dict:
        """모든 템플릿의 패턴을 분석"""
        analysis = {
            "grid_sizes": {},
            "pattern_types": {},
            "word_distributions": {},
            "black_cell_ratios": {},
            "common_rules": {}
        }
        
        for template in self.template_data:
            grid = template["grid_pattern"]
            width, height = len(grid[0]), len(grid)
            size_key = f"{width}x{height}"
            
            # 그리드 크기별 분석
            if size_key not in analysis["grid_sizes"]:
                analysis["grid_sizes"][size_key] = {
                    "count": 0,
                    "word_counts": [],
                    "black_ratios": [],
                    "patterns": []
                }
            
            analysis["grid_sizes"][size_key]["count"] += 1
            analysis["grid_sizes"][size_key]["patterns"].append(template["pattern_type"])
            
            # 검은칸 비율 계산
            black_cells = sum(row.count(2) for row in grid)
            total_cells = width * height
            black_ratio = black_cells / total_cells
            analysis["grid_sizes"][size_key]["black_ratios"].append(black_ratio)
            
            # 단어 개수 분석
            word_count = len(template["word_positions"])
            analysis["grid_sizes"][size_key]["word_counts"].append(word_count)
            
            # 패턴 유형별 분석
            pattern_type = template["pattern_type"]
            if pattern_type not in analysis["pattern_types"]:
                analysis["pattern_types"][pattern_type] = {
                    "count": 0,
                    "grid_sizes": [],
                    "word_distributions": []
                }
            
            analysis["pattern_types"][pattern_type]["count"] += 1
            analysis["pattern_types"][pattern_type]["grid_sizes"].append(size_key)
            
            # 단어 분포 분석 (가로/세로)
            horizontal_words = [w for w in template["word_positions"] if w["direction"] == "horizontal"]
            vertical_words = [w for w in template["word_positions"] if w["direction"] == "vertical"]
            analysis["pattern_types"][pattern_type]["word_distributions"].append({
                "horizontal": len(horizontal_words),
                "vertical": len(vertical_words),
                "total": word_count
            })
        
        return analysis
    
    def extract_common_rules(self) -> Dict:
        """공통 규칙 추출"""
        rules = {
            "black_cell_rules": [],
            "word_placement_rules": [],
            "intersection_rules": [],
            "size_constraints": {}
        }
        
        # 검은칸 규칙
        rules["black_cell_rules"] = [
            "검은칸들은 서로 연결되어야 함",
            "검은칸 비율은 30-40% 범위",
            "가장자리에 검은칸이 배치되어 단어 구분",
            "대칭성 유지 (좌우 또는 상하 대칭)"
        ]
        
        # 단어 배치 규칙
        rules["word_placement_rules"] = [
            "가로-세로 단어만 교차 가능",
            "같은 방향 단어는 교차하지 않음",
            "독립적인 단어들은 검은칸으로 분리",
            "최소 2글자 이상 (한 글자 단어 없음)",
            "가로/세로 단어 수 균형 유지"
        ]
        
        # 교차점 규칙
        rules["intersection_rules"] = [
            "교차점은 가로-세로 단어만",
            "교차점에서 글자가 일치해야 함",
            "교차점 수는 단어 개수에 비례"
        ]
        
        # 크기별 제약
        rules["size_constraints"] = {
            "5x5": {"min_words": 5, "max_words": 5, "black_ratio": "30-40%"},
            "6x6": {"min_words": 6, "max_words": 6, "black_ratio": "35-45%"},
            "7x7": {"min_words": 9, "max_words": 9, "black_ratio": "40-50%"}
        }
        
        return rules
    
    def generate_pattern_template(self, pattern_type: str, grid_size: int) -> Optional[Dict]:
        """패턴 유형에 따른 템플릿 생성"""
        templates = {
            "cross": {
                "5x5": {
                    "description": "십자형 패턴 - 중앙에 긴 가로 단어, 세로 단어들이 교차",
                    "black_cell_pattern": [
                        [2,1,1,1,1],
                        [2,2,2,2,1],
                        [2,1,1,2,1],
                        [1,1,1,1,2],
                        [2,2,2,1,2]
                    ],
                    "word_structure": {
                        "horizontal": 2,
                        "vertical": 3,
                        "total": 5
                    }
                }
            },
            "l_shape": {
                "5x5": {
                    "description": "L자형 패턴 - 모서리에 L자 모양 검은칸 배치",
                    "black_cell_pattern": [
                        [2,2,2,1,1],
                        [1,1,2,1,1],
                        [1,2,1,1,2],
                        [1,2,2,1,2],
                        [1,1,1,1,2]
                    ],
                    "word_structure": {
                        "horizontal": 2,
                        "vertical": 3,
                        "total": 5
                    }
                }
            },
            "mesh": {
                "5x5": {
                    "description": "그물형 패턴 - 복잡한 검은칸 네트워크",
                    "black_cell_pattern": [
                        [2,2,2,1,1],
                        [1,1,2,1,1],
                        [2,1,2,2,1],
                        [2,1,1,1,1],
                        [2,2,2,2,1]
                    ],
                    "word_structure": {
                        "horizontal": 3,
                        "vertical": 2,
                        "total": 5
                    }
                }
            },
            "symmetric": {
                "6x6": {
                    "description": "대칭형 패턴 - 좌우 대칭 구조",
                    "black_cell_pattern": [
                        [1,2,2,2,2,1],
                        [1,1,1,1,2,1],
                        [1,1,1,2,2,2],
                        [2,2,2,1,1,1],
                        [1,1,1,2,2,1],
                        [2,2,1,1,1,1]
                    ],
                    "word_structure": {
                        "horizontal": 4,
                        "vertical": 2,
                        "total": 6
                    }
                }
            }
        }
        
        size_key = f"{grid_size}x{grid_size}"
        if pattern_type in templates and size_key in templates[pattern_type]:
            return templates[pattern_type][size_key]
        
        return None

def main():
    """메인 실행 함수"""
    analyzer = CrosswordPatternAnalyzer()
    
    print("=== 크로스워드 퍼즐 패턴 분석 결과 ===\n")
    
    # 패턴 분석
    analysis = analyzer.analyze_patterns()
    
    print("1. 그리드 크기별 분석:")
    for size, data in analysis["grid_sizes"].items():
        print(f"  {size}: {data['count']}개 템플릿")
        print(f"    - 단어 개수: {min(data['word_counts'])}-{max(data['word_counts'])}개")
        print(f"    - 검은칸 비율: {min(data['black_ratios']):.1%}-{max(data['black_ratios']):.1%}")
        print(f"    - 패턴 유형: {', '.join(set(data['patterns']))}")
        print()
    
    print("2. 패턴 유형별 분석:")
    for pattern_type, data in analysis["pattern_types"].items():
        print(f"  {pattern_type}: {data['count']}개 템플릿")
        print(f"    - 그리드 크기: {', '.join(set(data['grid_sizes']))}")
        
        # 단어 분포 평균 계산
        avg_horizontal = sum(d["horizontal"] for d in data["word_distributions"]) / len(data["word_distributions"])
        avg_vertical = sum(d["vertical"] for d in data["word_distributions"]) / len(data["word_distributions"])
        print(f"    - 평균 단어 분포: 가로 {avg_horizontal:.1f}개, 세로 {avg_vertical:.1f}개")
        print()
    
    # 공통 규칙 추출
    rules = analyzer.extract_common_rules()
    
    print("3. 공통 규칙:")
    print("  검은칸 규칙:")
    for rule in rules["black_cell_rules"]:
        print(f"    - {rule}")
    
    print("  단어 배치 규칙:")
    for rule in rules["word_placement_rules"]:
        print(f"    - {rule}")
    
    print("  교차점 규칙:")
    for rule in rules["intersection_rules"]:
        print(f"    - {rule}")
    
    print("\n4. 패턴 템플릿 예시:")
    for pattern_type in ["cross", "l_shape", "mesh", "symmetric"]:
        template = analyzer.generate_pattern_template(pattern_type, 5 if pattern_type != "symmetric" else 6)
        if template:
            print(f"  {pattern_type} 패턴:")
            print(f"    - 설명: {template['description']}")
            print(f"    - 단어 구조: 가로 {template['word_structure']['horizontal']}개, 세로 {template['word_structure']['vertical']}개")
            print()

if __name__ == "__main__":
    main() 