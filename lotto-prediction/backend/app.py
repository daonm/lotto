"""
Flask API 서버
로또 번호 조합 생성 및 데이터 제공 API
"""

from flask import Flask, jsonify, request, send_from_directory
from flask_cors import CORS
import json
import sys
from pathlib import Path

# 현재 디렉토리를 모듈 검색 경로에 추가
sys.path.append(str(Path(__file__).parent))

from data_collector import LottoDataCollector
from rule_engine import LottoRuleEngine
from database import init_db, get_db_connection
from auth import hash_password, verify_password, generate_token, token_required
from result_checker import check_result

app = Flask(__name__, static_folder='../frontend')
CORS(app)  # 프론트엔드에서 접근 가능하도록 CORS 설정

# 경로 설정
BASE_DIR = Path(__file__).parent
DATA_DIR = BASE_DIR / '../data'

def load_lotto_data():
    """로또 데이터를 로드합니다."""
    data_file = DATA_DIR / 'lotto_history.json'
    
    if not data_file.exists():
        # 데이터가 없으면 수집
        try:
            collector = LottoDataCollector(data_dir=DATA_DIR)
            data = collector.collect_recent_data(months=6)
            collector.save_data(data)
            return data
        except Exception as e:
            print(f"Data collection failed: {e}")
            return []
    
    try:
        with open(data_file, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception as e:
        print(f"Data load failed: {e}")
        return []


@app.route('/')
def serve_index():
    """메인 페이지 제공"""
    return send_from_directory(app.static_folder, 'index.html')


@app.route('/<path:path>')
def serve_static(path):
    """정적 파일 제공"""
    return send_from_directory(app.static_folder, path)


@app.route('/favicon.ico')
def favicon():
    """파비콘 요청 처리 (204 No Content)"""
    return '', 204



@app.route('/api/generate', methods=['POST'])
def generate_combinations():
    """로또 번호 조합을 생성합니다."""
    try:
        data = request.get_json() or {}
        num_combinations = data.get('num_combinations', 10)
        
        lotto_data = load_lotto_data()
        
        # 데이터가 없으면 빈 결과 반환
        if not lotto_data:
             return jsonify({
                'success': True,
                'data': {
                    'combinations': [],
                    'statistics': {'total_generated': 0, 'after_filtering': 0, 'filter_rate': '0%'}
                }
            })

        engine = LottoRuleEngine(lotto_data)
        result = engine.generate_combinations(num_combinations=num_combinations)
        
        # 프론트엔드 형식을 위해 데이터 가공
        combinations_with_explanation = []
        for combo in result['combinations']:
            combinations_with_explanation.append({
                'numbers': combo,
                'explanation': engine.explain_combination(combo)
            })
        
        result['combinations'] = combinations_with_explanation
        
        return jsonify({
            'success': True,
            'data': result
        })
    except Exception as e:
        print(f"Generate Error: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/statistics', methods=['GET'])
def get_statistics():
    """통계 정보를 반환합니다."""
    try:
        lotto_data = load_lotto_data()
        
        if not lotto_data:
             return jsonify({
                'success': True,
                'data': {
                    'core_numbers': [],
                    'last_week_numbers': [],
                    'exclude_numbers': [],
                    'total_draws': 0
                }
            })

        engine = LottoRuleEngine(lotto_data)
        engine.analyze_history()
        
        return jsonify({
            'success': True,
            'data': {
                'core_numbers': sorted(list(engine.core_numbers)),
                'last_week_numbers': engine.last_week_numbers,
                'exclude_numbers': sorted(list(engine.exclude_numbers)),
                'total_draws': len(lotto_data)
            }
        })
    except Exception as e:
        print(f"Statistics Error: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


# ===== 인증 API =====

@app.route('/api/auth/signup', methods=['POST'])
def signup():
    """회원가입"""
    try:
        data = request.get_json()
        username = data.get('username')
        email = data.get('email')
        password = data.get('password')
        
        if not username or not email or not password:
            return jsonify({'success': False, 'error': '모든 필드를 입력해주세요'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # 중복 확인
        cursor.execute('SELECT id FROM users WHERE username = ? OR email = ?', (username, email))
        if cursor.fetchone():
            conn.close()
            return jsonify({'success': False, 'error': '이미 사용중인 사용자명 또는 이메일입니다'}), 409
        
        password_hash = hash_password(password)
        
        cursor.execute(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
            (username, email, password_hash)
        )
        user_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        return jsonify({'success': True, 'message': '회원가입 성공', 'user_id': user_id}), 201
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/auth/login', methods=['POST'])
def login():
    """로그인"""
    try:
        data = request.get_json()
        email = data.get('email')
        password = data.get('password')
        
        if not email or not password:
            return jsonify({'success': False, 'error': '이메일과 비밀번호를 입력해주세요'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute('SELECT * FROM users WHERE email = ?', (email,))
        user = cursor.fetchone()
        conn.close()
        
        if not user or not verify_password(password, user['password_hash']):
            return jsonify({'success': False, 'error': '이메일 또는 비밀번호가 올바르지 않습니다'}), 401
        
        token = generate_token(user['id'], user['username'])
        
        return jsonify({
            'success': True,
            'token': token,
            'user': {'id': user['id'], 'username': user['username'], 'email': user['email']}
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/auth/me', methods=['GET'])
@token_required
def get_current_user(current_user):
    """현재 사용자 정보"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute('SELECT id, username, email FROM users WHERE id = ?', (current_user['user_id'],))
        user = cursor.fetchone()
        conn.close()
        
        if not user:
            return jsonify({'success': False, 'error': '사용자를 찾을 수 없습니다'}), 404
        
        return jsonify({
            'success': True,
            'user': {'id': user['id'], 'username': user['username'], 'email': user['email']}
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# ===== 조합 저장 및 관리 API =====

@app.route('/api/combinations/save', methods=['POST'])
@token_required
def save_combination(current_user):
    """번호 조합 저장"""
    try:
        data = request.get_json()
        numbers = data.get('numbers')
        draw_number = data.get('draw_number')
        
        if not numbers or not draw_number:
            return jsonify({'success': False, 'error': '번호와 회차 정보가 필요합니다'}), 400
        
        if len(numbers) != 6:
            return jsonify({'success': False, 'error': '번호는 6개여야 합니다'}), 400
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute(
            'INSERT INTO saved_combinations (user_id, numbers, draw_number) VALUES (?, ?, ?)',
            (current_user['user_id'], json.dumps(numbers), draw_number)
        )
        saved_id = cursor.lastrowid
        conn.commit()
        conn.close()
        
        return jsonify({'success': True, 'saved_id': saved_id, 'message': '번호가 저장되었습니다'}), 201
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/combinations/my', methods=['GET'])
@token_required
def get_my_combinations(current_user):
    """내 저장된 조합 목록"""
    try:
        draw_number = request.args.get('draw_number', type=int)
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        query = 'SELECT * FROM saved_combinations WHERE user_id = ?'
        params = [current_user['user_id']]
        
        if draw_number:
            query += ' AND draw_number = ?'
            params.append(draw_number)
            
        query += ' ORDER BY created_at DESC'
        
        cursor.execute(query, params)
        combinations = cursor.fetchall()
        conn.close()
        
        result = []
        for combo in combinations:
            result.append({
                'id': combo['id'],
                'numbers': json.loads(combo['numbers']),
                'draw_number': combo['draw_number'],
                'created_at': combo['created_at'],
                'checked': bool(combo['checked']),
                'matched_count': combo['matched_count'],
                'prize': combo['prize']
            })
        
        return jsonify({'success': True, 'combinations': result})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/combinations/<int:combination_id>', methods=['DELETE'])
@token_required
def delete_combination(current_user, combination_id):
    """저장된 조합 삭제"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        cursor.execute(
            'DELETE FROM saved_combinations WHERE id = ? AND user_id = ?',
            (combination_id, current_user['user_id'])
        )
        
        if cursor.rowcount == 0:
            conn.close()
            return jsonify({'success': False, 'error': '조합을 찾을 수 없거나 삭제 권한이 없습니다'}), 404
            
        conn.commit()
        conn.close()
        
        return jsonify({'success': True, 'message': '삭제되었습니다'})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/combinations/delete-batch', methods=['POST'])
@token_required
def delete_combinations_batch(current_user):
    """저장된 조합 일괄 삭제 (선택 삭제 또는 전체 삭제)"""
    try:
        data = request.get_json()
        ids = data.get('ids', [])
        delete_all = data.get('all', False)
        
        # Safety check: if ids are provided, ensure delete_all is False
        if ids:
            delete_all = False
            
        draw_number = data.get('draw_number') # 전체 삭제 시 특정 회차만 삭제할 경우
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        if delete_all:
            query = 'DELETE FROM saved_combinations WHERE user_id = ?'
            params = [current_user['user_id']]
            
            if draw_number:
                query += ' AND draw_number = ?'
                params.append(draw_number)
                
            cursor.execute(query, params)
            deleted_count = cursor.rowcount
        elif ids:
            # 리스트를 SQL 파라미터로 변환 (?, ?, ?)
            placeholders = ', '.join(['?'] * len(ids))
            query = f'DELETE FROM saved_combinations WHERE user_id = ? AND id IN ({placeholders})'
            params = [current_user['user_id']] + ids
            
            cursor.execute(query, params)
            deleted_count = cursor.rowcount
        else:
            conn.close()
            return jsonify({'success': False, 'error': '삭제할 대상이 지정되지 않았습니다'}), 400
            
        conn.commit()
        conn.close()
        
        return jsonify({'success': True, 'message': f'{deleted_count}개의 조합이 삭제되었습니다', 'deleted_count': deleted_count})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500



@app.route('/api/combinations/check-results', methods=['POST'])
@token_required
def check_results(current_user):
    """당첨 결과 확인"""
    try:
        data = request.get_json()
        draw_number = data.get('draw_number')
        
        if not draw_number:
            return jsonify({'success': False, 'error': '회차 번호가 필요합니다'}), 400
        
        lotto_data = load_lotto_data()
        winning_draw = next((d for d in lotto_data if d['draw_number'] == draw_number), None)
        
        if not winning_draw:
            return jsonify({'success': False, 'error': '해당 회차의 당첨 번호를 찾을 수 없습니다'}), 404
        
        winning_numbers = winning_draw['winning_numbers']
        bonus_number = winning_draw['bonus_number']
        
        conn = get_db_connection()
        cursor = conn.cursor()
        
        # 해당 회차의 사용자 조합 조회
        cursor.execute(
            'SELECT * FROM saved_combinations WHERE user_id = ? AND draw_number = ?',
            (current_user['user_id'], draw_number)
        )
        combinations = cursor.fetchall()
        
        results = []
        for combo in combinations:
            saved_numbers = json.loads(combo['numbers'])
            result = check_result(saved_numbers, winning_numbers, bonus_number)
            
            # 결과 업데이트
            cursor.execute(
                '''
                UPDATE saved_combinations 
                SET checked = 1, matched_count = ?, prize = ? 
                WHERE id = ?
                ''',
                (result['matched_count'], result['prize'], combo['id'])
            )
            
            results.append({
                'combination_id': combo['id'],
                'numbers': saved_numbers,
                'matched_count': result['matched_count'],
                'matched_numbers': result['matched_numbers'],
                'has_bonus': result['has_bonus'],
                'prize': result['prize']
            })
        
        conn.commit()
        conn.close()
        
        return jsonify({
            'success': True,
            'winning_numbers': winning_numbers,
            'bonus_number': bonus_number,
            'results': results
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


if __name__ == '__main__':
    # 데이터 디렉토리 생성
    DATA_DIR.mkdir(exist_ok=True)
    
    # 데이터베이스 초기화
    init_db()
    
    print("=" * 60)
    print("🎯 골프친구-독식 로또 예측 시스템 서버 시작")
    print("=" * 60)
    print("\n📡 서버 주소: http://localhost:5000")
    print("📝 API 엔드포인트:")
    print("  - POST /api/generate : 번호 조합 생성")
    print("  - GET  /api/history  : 당첨 번호 히스토리")
    print("  - GET  /api/statistics : 통계 정보")
    print("  - POST /api/auth/signup : 회원가입")
    print("  - POST /api/auth/login : 로그인")
    print("\n" + "=" * 60 + "\n")
    
    app.run(debug=True, host='0.0.0.0', port=5000)
