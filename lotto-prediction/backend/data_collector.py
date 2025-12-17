"""
로또 당첨 번호 데이터 수집 모듈
동행복권 공식 사이트에서 최근 당첨 번호를 수집합니다.
"""

import requests
import json
from datetime import datetime, timedelta
from pathlib import Path


class LottoDataCollector:
    """로또 당첨 번호 수집 및 저장 클래스"""
    
    def __init__(self, data_dir='../data'):
        self.data_dir = Path(data_dir)
        self.data_dir.mkdir(exist_ok=True)
        self.data_file = self.data_dir / 'lotto_history.json'
        self.base_url = 'https://www.dhlottery.co.kr/common.do?method=getLottoNumber&drwNo='
        
    def get_latest_draw_number(self):
        """최신 회차 번호를 가져옵니다."""
        # 현재 날짜 기준으로 대략적인 회차 계산
        # 로또 1회차: 2002년 12월 7일
        first_draw_date = datetime(2002, 12, 7)
        today = datetime.now()
        weeks_passed = (today - first_draw_date).days // 7
        return weeks_passed + 1
    
    def fetch_draw_data(self, draw_number):
        """특정 회차의 당첨 번호를 가져옵니다."""
        try:
            url = f"{self.base_url}{draw_number}"
            # User-Agent 헤더 추가하여 차단 방지
            headers = {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            }
            response = requests.get(url, headers=headers, timeout=10)
            
            if response.status_code != 200:
                return None
                
            data = response.json()
            
            # 반환 성공 여부 확인
            if data.get('returnValue') != 'success':
                return None
            
            # 당첨 번호 추출
            winning_numbers = [
                data['drwtNo1'],
                data['drwtNo2'],
                data['drwtNo3'],
                data['drwtNo4'],
                data['drwtNo5'],
                data['drwtNo6']
            ]
            
            bonus_number = data['bnusNo']
            draw_date = data['drwNoDate']
            
            return {
                'draw_number': draw_number,
                'draw_date': draw_date,
                'winning_numbers': sorted(winning_numbers),
                'bonus_number': bonus_number
            }
            
        except Exception as e:
            print(f"Error fetching draw {draw_number}: {e}")
            return None
    
    def get_fallback_data(self):
        """수집 실패 시 사용할 기본 데이터 (최근 10회차 - 2024년 5월 기준)"""
        return [
            {"draw_number": 1118, "draw_date": "2024-05-04", "winning_numbers": [11, 13, 14, 15, 16, 45], "bonus_number": 3},
            {"draw_number": 1119, "draw_date": "2024-05-11", "winning_numbers": [1, 9, 12, 13, 20, 45], "bonus_number": 3},
            {"draw_number": 1120, "draw_date": "2024-05-18", "winning_numbers": [2, 19, 26, 31, 38, 41], "bonus_number": 34},
            {"draw_number": 1121, "draw_date": "2024-05-25", "winning_numbers": [6, 24, 31, 32, 38, 44], "bonus_number": 8},
            {"draw_number": 1122, "draw_date": "2024-06-01", "winning_numbers": [3, 6, 21, 30, 34, 35], "bonus_number": 22},
            {"draw_number": 1123, "draw_date": "2024-06-08", "winning_numbers": [13, 19, 21, 24, 34, 35], "bonus_number": 26},
            {"draw_number": 1124, "draw_date": "2024-06-15", "winning_numbers": [3, 8, 17, 30, 33, 34], "bonus_number": 28},
            {"draw_number": 1125, "draw_date": "2024-06-22", "winning_numbers": [6, 14, 25, 33, 40, 44], "bonus_number": 30},
            {"draw_number": 1126, "draw_date": "2024-06-29", "winning_numbers": [4, 5, 9, 11, 37, 40], "bonus_number": 7},
            {"draw_number": 1127, "draw_date": "2024-07-06", "winning_numbers": [10, 15, 24, 30, 31, 37], "bonus_number": 32}
        ]

    def collect_recent_data(self, months=6):
        """최근 N개월의 당첨 번호를 수집합니다."""
        latest_draw = self.get_latest_draw_number()
        
        # 6개월 = 약 26주
        num_draws = months * 4  # 월 4회 추첨
        start_draw = max(1, latest_draw - num_draws)
        
        all_data = []
        
        print(f"Collecting data from draw {start_draw} to {latest_draw}...")
        
        for draw_num in range(start_draw, latest_draw + 1):
            draw_data = self.fetch_draw_data(draw_num)
            if draw_data:
                all_data.append(draw_data)
                print(f"✓ Draw {draw_num}: {draw_data['winning_numbers']} + {draw_data['bonus_number']}")
            else:
                print(f"✗ Draw {draw_num}: Failed to fetch")
        
        # 데이터 수집 실패 시 기본 데이터 사용
        if not all_data:
            print("⚠ 데이터 수집 실패. 기본 데이터를 사용합니다.")
            return self.get_fallback_data()
            
        return all_data
    
    def save_data(self, data):
        """데이터를 JSON 파일로 저장합니다."""
        with open(self.data_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
        print(f"\n✓ Data saved to {self.data_file}")
    
    def load_data(self):
        """저장된 데이터를 불러옵니다."""
        if not self.data_file.exists():
            return []
        
        with open(self.data_file, 'r', encoding='utf-8') as f:
            return json.load(f)
    
    def update_data(self):
        """데이터를 업데이트합니다 (새로운 회차가 있는 경우)."""
        existing_data = self.load_data()
        
        if not existing_data:
            # 데이터가 없으면 전체 수집
            new_data = self.collect_recent_data(months=6)
            self.save_data(new_data)
            return new_data
        
        # 마지막 저장된 회차 확인
        last_draw = max(d['draw_number'] for d in existing_data)
        latest_draw = self.get_latest_draw_number()
        
        if latest_draw <= last_draw:
            print("Data is up to date!")
            return existing_data
        
        # 새로운 회차만 수집
        print(f"Fetching new draws from {last_draw + 1} to {latest_draw}...")
        for draw_num in range(last_draw + 1, latest_draw + 1):
            draw_data = self.fetch_draw_data(draw_num)
            if draw_data:
                existing_data.append(draw_data)
                print(f"✓ Draw {draw_num}: {draw_data['winning_numbers']} + {draw_data['bonus_number']}")
        
        self.save_data(existing_data)
        return existing_data


def main():
    """테스트 실행"""
    collector = LottoDataCollector()
    
    # 최근 6개월 데이터 수집
    data = collector.collect_recent_data(months=6)
    collector.save_data(data)
    
    print(f"\nTotal draws collected: {len(data)}")


if __name__ == '__main__':
    main()
