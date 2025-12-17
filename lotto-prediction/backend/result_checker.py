"""
당첨 결과 확인 로직
저장된 번호와 실제 당첨 번호를 비교
"""

def check_result(saved_numbers, winning_numbers, bonus_number):
    """
    저장된 번호와 당첨 번호를 비교하여 맞춘 개수 및 등수를 계산합니다.
    
    Args:
        saved_numbers: 저장된 번호 리스트 [1, 2, 3, 4, 5, 6]
        winning_numbers: 당첨 번호 리스트 [5, 12, 21, 33, 37, 42]
        bonus_number: 보너스 번호 (int)
    
    Returns:
        dict: {
            'matched_count': 3,
            'matched_numbers': [5, 12, 21],
            'has_bonus': False,
            'prize': '5등' or None
        }
    """
    # 당첨 번호와 일치하는 번호 찾기
    matched_numbers = [n for n in saved_numbers if n in winning_numbers]
    matched_count = len(matched_numbers)
    
    # 보너스 번호 확인
    has_bonus = bonus_number in saved_numbers
    
    # 등수 판정
    prize = determine_prize(matched_count, has_bonus)
    
    return {
        'matched_count': matched_count,
        'matched_numbers': matched_numbers,
        'has_bonus': has_bonus,
        'prize': prize
    }


def determine_prize(matched_count, has_bonus):
    """
    맞춘 개수와 보너스 여부로 등수를 판정합니다.
    
    Args:
        matched_count: 맞춘 번호 개수
        has_bonus: 보너스 번호 포함 여부
    
    Returns:
        등수 문자열 또는 None
    """
    if matched_count == 6:
        return '1등'
    elif matched_count == 5 and has_bonus:
        return '2등'
    elif matched_count == 5:
        return '3등'
    elif matched_count == 4:
        return '4등'
    elif matched_count == 3:
        return '5등'
    else:
        return None  # 낙첨


def get_prize_info(prize):
    """
    등수별 상금 정보를 반환합니다 (참고용).
    
    Args:
        prize: 등수 문자열
    
    Returns:
        dict: 상금 정보
    """
    prize_info = {
        '1등': {
            'name': '1등',
            'condition': '6개 번호 일치',
            'amount': '약 20억원 (변동)',
            'probability': '1/8,145,060'
        },
        '2등': {
            'name': '2등',
            'condition': '5개 번호 + 보너스 번호 일치',
            'amount': '약 5천만원 (변동)',
            'probability': '1/1,357,510'
        },
        '3등': {
            'name': '3등',
            'condition': '5개 번호 일치',
            'amount': '약 150만원 (고정)',
            'probability': '1/35,724'
        },
        '4등': {
            'name': '4등',
            'condition': '4개 번호 일치',
            'amount': '5만원 (고정)',
            'probability': '1/733'
        },
        '5등': {
            'name': '5등',
            'condition': '3개 번호 일치',
            'amount': '5천원 (고정)',
            'probability': '1/45'
        }
    }
    
    return prize_info.get(prize, None)


if __name__ == '__main__':
    # 테스트
    print("🎯 당첨 결과 확인 테스트\n")
    
    # 테스트 케이스
    test_cases = [
        {
            'name': '1등',
            'saved': [5, 12, 21, 33, 37, 42],
            'winning': [5, 12, 21, 33, 37, 42],
            'bonus': 7
        },
        {
            'name': '2등',
            'saved': [5, 12, 21, 33, 37, 7],
            'winning': [5, 12, 21, 33, 37, 42],
            'bonus': 7
        },
        {
            'name': '3등',
            'saved': [5, 12, 21, 33, 37, 1],
            'winning': [5, 12, 21, 33, 37, 42],
            'bonus': 7
        },
        {
            'name': '4등',
            'saved': [5, 12, 21, 33, 1, 2],
            'winning': [5, 12, 21, 33, 37, 42],
            'bonus': 7
        },
        {
            'name': '5등',
            'saved': [5, 12, 21, 1, 2, 3],
            'winning': [5, 12, 21, 33, 37, 42],
            'bonus': 7
        },
        {
            'name': '낙첨',
            'saved': [1, 2, 3, 4, 6, 8],
            'winning': [5, 12, 21, 33, 37, 42],
            'bonus': 7
        }
    ]
    
    for test in test_cases:
        result = check_result(
            saved_numbers=test['saved'],
            winning_numbers=test['winning'],
            bonus_number=test['bonus']
        )
        
        print(f"예상: {test['name']}")
        print(f"저장 번호: {test['saved']}")
        print(f"당첨 번호: {test['winning']} + 보너스 {test['bonus']}")
        print(f"결과: {result['prize'] or '낙첨'} (맞춘 개수: {result['matched_count']})")
        print(f"일치 번호: {result['matched_numbers']}")
        print()
