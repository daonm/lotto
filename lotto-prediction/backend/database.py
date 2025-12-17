"""
데이터베이스 관리 (Native SQLite3)
호환성 문제를 피하기 위해 SQLAlchemy 대신 Python 내장 sqlite3를 사용합니다.
"""

import sqlite3
from pathlib import Path
from datetime import datetime

# 데이터베이스 파일 경로
BASE_DIR = Path(__file__).parent
DATA_DIR = BASE_DIR / '../data'
DATA_DIR.mkdir(exist_ok=True)
DB_PATH = DATA_DIR / 'lotto.db'

def get_db_connection():
    """데이터베이스 연결을 생성하고 반환합니다."""
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row  # 컬럼명으로 데이터 접근 가능하게 설정
    return conn

def init_db():
    """데이터베이스 테이블을 초기화합니다."""
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # 사용자 테이블 생성
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    ''')
    
    # 저장된 조합 테이블 생성
    cursor.execute('''
    CREATE TABLE IF NOT EXISTS saved_combinations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        numbers TEXT NOT NULL,
        draw_number INTEGER NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        checked BOOLEAN DEFAULT 0,
        matched_count INTEGER,
        prize TEXT,
        FOREIGN KEY (user_id) REFERENCES users (id)
    )
    ''')
    
    conn.commit()
    conn.close()
    print(f"✅ Database initialized at {DB_PATH}")

if __name__ == '__main__':
    init_db()
