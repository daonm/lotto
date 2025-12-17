"""
유틸리티 함수 모듈
"""

from collections import Counter


def get_number_frequency(lotto_data, include_bonus=True):
    """
    로또 번호별 출현 빈도를 계산합니다.
    
    Args:
        lotto_data: 로또 데이터 리스트
        include_bonus: 보너스 번호 포함 여부
    
    Returns:
        Counter 객체 (번호: 출현 횟수)
    """
    frequency = Counter()
    
    for draw in lotto_data:
        for num in draw['winning_numbers']:
            frequency[num] += 1
        
        if include_bonus:
            frequency[draw['bonus_number']] += 1
    
    return frequency


def find_numbers_with_frequency(lotto_data, min_count=3, max_count=4):
    """
    특정 횟수만큼 등장한 번호들을 찾습니다.
    
    Args:
        lotto_data: 로또 데이터 리스트
        min_count: 최소 등장 횟수
        max_count: 최대 등장 횟수
    
    Returns:
        해당 횟수 범위에 등장한 번호 리스트
    """
    frequency = get_number_frequency(lotto_data, include_bonus=True)
    
    result = [
        num for num, count in frequency.items()
        if min_count <= count <= max_count
    ]
    
    return sorted(result)


def get_last_week_numbers(lotto_data):
    """
    지난주(가장 최근) 당첨 번호를 가져옵니다.
    
    Args:
        lotto_data: 로또 데이터 리스트
    
    Returns:
        지난주 당첨 번호 리스트
    """
    if not lotto_data:
        return []
    
    # 가장 최근 회차
    latest = max(lotto_data, key=lambda x: x['draw_number'])
    return latest['winning_numbers']


def get_recent_high_frequency_numbers(lotto_data, recent_count=10, threshold=3, exceptions=None):
    """
    최근 N회차에서 threshold 이상 등장한 번호를 찾습니다.
    
    Args:
        lotto_data: 로또 데이터 리스트
        recent_count: 최근 몇 회차를 볼지
        threshold: 제외할 최소 등장 횟수
        exceptions: 제외하지 않을 번호 리스트
    
    Returns:
        제외해야 할 번호 리스트
    """
    if exceptions is None:
        exceptions = [39, 43]
    
    # 최근 N회차 데이터 추출
    sorted_data = sorted(lotto_data, key=lambda x: x['draw_number'], reverse=True)
    recent_data = sorted_data[:recent_count]
    
    # 빈도 계산
    frequency = get_number_frequency(recent_data, include_bonus=False)
    
    # threshold 이상 등장한 번호 중 예외가 아닌 것
    high_freq = [
        num for num, count in frequency.items()
        if count >= threshold and num not in exceptions
    ]
    
    return sorted(high_freq)


def has_consecutive_numbers(numbers, max_consecutive=3):
    """
    연속된 번호가 max_consecutive개 이상 있는지 확인합니다.
    
    Args:
        numbers: 번호 리스트
        max_consecutive: 최대 허용 연속 개수
    
    Returns:
        True: 연속 번호가 있음, False: 없음
    """
    sorted_nums = sorted(numbers)
    consecutive_count = 1
    
    for i in range(1, len(sorted_nums)):
        if sorted_nums[i] == sorted_nums[i-1] + 1:
            consecutive_count += 1
            if consecutive_count >= max_consecutive:
                return True
        else:
            consecutive_count = 1
    
    return False


def check_odd_even_balance(numbers):
    """
    홀짝 균형을 확인합니다.
    
    Args:
        numbers: 번호 리스트
    
    Returns:
        True: 균형 OK, False: 모두 홀수 또는 모두 짝수
    """
    odd_count = sum(1 for n in numbers if n % 2 == 1)
    even_count = len(numbers) - odd_count
    
    # 모두 홀수이거나 모두 짝수이면 False
    if odd_count == 0 or even_count == 0:
        return False
    
    return True


def calculate_sum(numbers):
    """번호의 합계를 계산합니다."""
    return sum(numbers)


def check_sum_range(numbers, min_sum=121, max_sum=160):
    """
    번호 합계가 지정된 범위 내에 있는지 확인합니다.
    
    Args:
        numbers: 번호 리스트
        min_sum: 최소 합계
        max_sum: 최대 합계
    
    Returns:
        True: 범위 내, False: 범위 밖
    """
    total = calculate_sum(numbers)
    return min_sum <= total <= max_sum


def check_range_constraint(numbers):
    """
    첫 번째 번호 ≤14, 마지막 번호 ≥35 확인
    
    Args:
        numbers: 정렬된 번호 리스트
    
    Returns:
        True: 조건 충족, False: 조건 불충족
    """
    sorted_nums = sorted(numbers)
    return sorted_nums[0] <= 14 and sorted_nums[-1] >= 35


def has_multiples_of_three(numbers):
    """
    3의 배수가 적절히 포함되어 있는지 확인합니다.
    
    Args:
        numbers: 번호 리스트
    
    Returns:
        True: 3의 배수가 1개 이상 포함, False: 없음
    """
    multiples = [n for n in numbers if n % 3 == 0]
    return len(multiples) >= 1


def check_horizontal_bias(numbers):
    """
    좌우 쏠림 방지 - 로또 용지의 좌우 세로 2줄에 번호를 몰아 쓰지 않았는지 확인
    
    로또 용지는 5열로 구성:
    1-9, 10-19, 20-29, 30-39, 40-45
    
    Args:
        numbers: 번호 리스트
    
    Returns:
        True: 쏠림 없음, False: 쏠림 있음
    """
    # 각 열에 속하는 번호 개수 계산
    columns = [
        [1, 2, 3, 4, 5, 6, 7, 8, 9],
        [10, 11, 12, 13, 14, 15, 16, 17, 18, 19],
        [20, 21, 22, 23, 24, 25, 26, 27, 28, 29],
        [30, 31, 32, 33, 34, 35, 36, 37, 38, 39],
        [40, 41, 42, 43, 44, 45]
    ]
    
    counts = [sum(1 for n in numbers if n in col) for col in columns]
    
    # 인접한 2개 열에 5개 이상 몰려있으면 좌우 쏠림
    for i in range(len(counts) - 1):
        if counts[i] + counts[i+1] >= 5:
            return False
    
    return True


def check_vertical_bias(numbers):
    """
    상하 쏠림 방지 - 위 세로 3줄에 번호를 몰아 쓰지 않았는지 확인
    
    Args:
        numbers: 번호 리스트
    
    Returns:
        True: 쏠림 없음, False: 쏠림 있음
    """
    # 1-21 범위 (첫 3줄)에 5개 이상 있으면 상하 쏠림
    top_numbers = [n for n in numbers if n <= 21]
    
    if len(top_numbers) >= 5:
        return False
    
    return True
