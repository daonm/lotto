"""
로또 번호 조합 생성 규칙 엔진
로또 명인의 비법을 기반으로 한 필터링 및 조합 생성
"""

import itertools
from utils import (
    find_numbers_with_frequency,
    get_last_week_numbers,
    get_recent_high_frequency_numbers,
    has_consecutive_numbers,
    check_odd_even_balance,
    check_sum_range,
    check_range_constraint,
    has_multiples_of_three,
    check_horizontal_bias,
    check_vertical_bias
)


class LottoRuleEngine:
    """로또 번호 조합 생성 및 필터링 엔진"""
    
    def __init__(self, lotto_data):
        """
        Args:
            lotto_data: 로또 당첨 번호 데이터 리스트
        """
        self.lotto_data = lotto_data
        self.core_numbers = []
        self.last_week_numbers = []
        self.exclude_numbers = []
        
    def find_core_numbers(self):
        """
        최근 6개월간 3-4회 등장한 핵심 번호를 찾습니다.
        """
        self.core_numbers = find_numbers_with_frequency(
            self.lotto_data,
            min_count=3,
            max_count=4
        )
        return self.core_numbers
    
    def get_last_week_numbers(self):
        """
        지난주 당첨 번호를 가져옵니다.
        """
        self.last_week_numbers = get_last_week_numbers(self.lotto_data)
        return self.last_week_numbers
    
    def find_exclude_numbers(self):
        """
        제외해야 할 번호를 찾습니다.
        최근 10회차에서 3회 이상 등장한 번호 (39, 43 제외)
        """
        self.exclude_numbers = get_recent_high_frequency_numbers(
            self.lotto_data,
            recent_count=10,
            threshold=3,
            exceptions=[39, 43]
        )
        return self.exclude_numbers

    def analyze_history(self):
        """
        전체 히스토리를 분석하여 통계 데이터를 생성합니다.
        (API 서빙용)
        """
        self.find_core_numbers()
        self.get_last_week_numbers()
        self.find_exclude_numbers()
        return {
            'core_numbers': self.core_numbers,
            'last_week_numbers': self.last_week_numbers,
            'exclude_numbers': self.exclude_numbers
        }

    
    def generate_base_combinations(self, num_combinations=100):
        """
        기본 조합을 생성합니다.
        지난주 번호 각각 + 핵심 번호로 조합
        
        Args:
            num_combinations: 생성할 조합 수
        
        Returns:
            생성된 조합 리스트
        """
        combinations = []
        
        # 지난주 번호와 핵심 번호를 합쳐서 전체 후보군 생성
        # 중복 제거
        all_candidates = list(set(self.last_week_numbers + self.core_numbers))
        
        # 제외 번호 필터링
        filtered_candidates = [n for n in all_candidates if n not in self.exclude_numbers]
        
        # 후보가 6개 미만이면 1-45 중에서 보충
        if len(filtered_candidates) < 6:
            # 전체 번호 중 후보와 제외 번호가 아닌 것 추가
            all_numbers = list(range(1, 46))
            extra_candidates = [
                n for n in all_numbers 
                if n not in filtered_candidates and n not in self.exclude_numbers
            ]
            filtered_candidates.extend(extra_candidates[:6 - len(filtered_candidates)])
        
        # 6개 조합 생성
        if len(filtered_candidates) >= 6:
            all_combos = list(itertools.combinations(filtered_candidates, 6))
            
            # 제한된 수만큼만 가져오기
            import random
            if len(all_combos) > num_combinations * 10:
                all_combos = random.sample(all_combos, num_combinations * 10)
            
            combinations = [list(combo) for combo in all_combos]
        
        return combinations
    
    def apply_filters(self, combinations):
        """
        생성된 조합에 필터링 규칙을 적용합니다.
        
        Args:
            combinations: 조합 리스트
        
        Returns:
            필터링된 조합 리스트
        """
        filtered = []
        
        for combo in combinations:
            # 1. 연속된 번호 3자리 이상 제외
            if has_consecutive_numbers(combo, max_consecutive=3):
                continue
            
            # 2. 홀짝 균형 체크 (모두 홀수 또는 모두 짝수 제외)
            if not check_odd_even_balance(combo):
                continue
            
            # 3. 합계 범위 체크 (121-160)
            if not check_sum_range(combo, min_sum=121, max_sum=160):
                continue
            
            # 4. 범위 제약 (첫 번호 ≤14, 마지막 번호 ≥35)
            if not check_range_constraint(combo):
                continue
            
            # 5. 3의 배수 포함 여부
            if not has_multiples_of_three(combo):
                continue
            
            # 6. 좌우 쏠림 방지
            if not check_horizontal_bias(combo):
                continue
            
            # 7. 상하 쏠림 방지
            if not check_vertical_bias(combo):
                continue
            
            # 모든 필터를 통과한 조합만 추가
            filtered.append(sorted(combo))
        
        return filtered
    
    def generate_combinations(self, num_combinations=10):
        """
        최종 조합을 생성합니다.
        
        Args:
            num_combinations: 생성할 조합 수
        
        Returns:
            dict: {
                'combinations': 조합 리스트,
                'core_numbers': 핵심 번호,
                'last_week_numbers': 지난주 번호,
                'exclude_numbers': 제외 번호,
                'statistics': 통계 정보
            }
        """
        # 1. 핵심 번호 찾기
        self.find_core_numbers()
        
        # 2. 지난주 번호 가져오기
        self.get_last_week_numbers()
        
        # 3. 제외 번호 찾기
        self.find_exclude_numbers()
        
        # 4. 기본 조합 생성
        base_combos = self.generate_base_combinations(num_combinations=num_combinations * 10)
        
        # 5. 필터링 적용
        filtered_combos = self.apply_filters(base_combos)
        
        # 6. 요청된 수만큼만 반환
        final_combos = filtered_combos[:num_combinations]
        
        # 7. 통계 정보 생성
        statistics = {
            'total_generated': len(base_combos),
            'after_filtering': len(filtered_combos),
            'returned': len(final_combos),
            'filter_rate': f"{len(filtered_combos) / max(1, len(base_combos)) * 100:.1f}%"
        }
        
        return {
            'combinations': final_combos,
            'core_numbers': self.core_numbers,
            'last_week_numbers': self.last_week_numbers,
            'exclude_numbers': self.exclude_numbers,
            'statistics': statistics
        }
    
    def explain_combination(self, combination):
        """
        특정 조합에 대한 설명을 생성합니다.
        
        Args:
            combination: 번호 조합 리스트
        
        Returns:
            설명 문자열
        """
        explanations = []
        
        # 합계
        total = sum(combination)
        explanations.append(f"합계: {total}")
        
        # 홀짝
        odd_count = sum(1 for n in combination if n % 2 == 1)
        even_count = 6 - odd_count
        explanations.append(f"홀수 {odd_count}개, 짝수 {even_count}개")
        
        # 핵심 번호 포함 여부
        core_in_combo = [n for n in combination if n in self.core_numbers]
        if core_in_combo:
            explanations.append(f"핵심 번호 포함: {core_in_combo}")
        
        # 지난주 번호 포함 여부
        last_week_in_combo = [n for n in combination if n in self.last_week_numbers]
        if last_week_in_combo:
            explanations.append(f"지난주 번호 포함: {last_week_in_combo}")
        
        return " | ".join(explanations)


def main():
    """테스트 실행"""
    import json
    from pathlib import Path
    
    # 데이터 로드
    data_file = Path('../data/lotto_history.json')
    
    if not data_file.exists():
        print("Error: lotto_history.json not found. Run data_collector.py first.")
        return
    
    with open(data_file, 'r', encoding='utf-8') as f:
        lotto_data = json.load(f)
    
    # 규칙 엔진 생성
    engine = LottoRuleEngine(lotto_data)
    
    # 조합 생성
    result = engine.generate_combinations(num_combinations=10)
    
    # 결과 출력
    print("=" * 60)
    print("🎯 골프친구-독식 로또 번호 조합 생성 결과")
    print("=" * 60)
    
    print(f"\n📊 통계 정보:")
    print(f"  - 핵심 번호 (3-4회 등장): {result['core_numbers']}")
    print(f"  - 지난주 당첨 번호: {result['last_week_numbers']}")
    print(f"  - 제외 번호 (최근 10회차 고빈도): {result['exclude_numbers']}")
    print(f"\n  - 생성된 조합 수: {result['statistics']['total_generated']}")
    print(f"  - 필터링 통과: {result['statistics']['after_filtering']}")
    print(f"  - 통과율: {result['statistics']['filter_rate']}")
    
    print(f"\n🎲 추천 조합 ({len(result['combinations'])}개):")
    for i, combo in enumerate(result['combinations'], 1):
        explanation = engine.explain_combination(combo)
        print(f"\n  {i}. {combo}")
        print(f"     {explanation}")


if __name__ == '__main__':
    main()
