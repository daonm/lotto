"""
인증 및 조합 관리 API 엔드포인트

이 파일은 app.py에 추가할 새로운 API들입니다.
실제로는 app.py의 231줄 이후에 삽입되어야 합니다.
"""

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
        
        db = next(get_db())
        
        existing_user = db.query(User).filter(
            (User.username == username) | (User.email == email)
        ).first()
        
        if existing_user:
            return jsonify({'success': False, 'error': '이미 사용중인 사용자명 또는 이메일입니다'}), 409
        
        password_hash = hash_password(password)
        new_user = User(username=username, email=email, password_hash=password_hash)
        
        db.add(new_user)
        db.commit()
        db.refresh(new_user)
        
        return jsonify({'success': True, 'message': '회원가입 성공', 'user_id': new_user.id}), 201
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
        
        db = next(get_db())
        user = db.query(User).filter(User.email == email).first()
        
        if not user or not verify_password(password, user.password_hash):
            return jsonify({'success': False, 'error': '이메일 또는 비밀번호가 올바르지 않습니다'}), 401
        
        token = generate_token(user.id, user.username)
        
        return jsonify({
            'success': True,
            'token': token,
            'user': {'id': user.id, 'username': user.username, 'email': user.email}
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/auth/me', methods=['GET'])
@token_required
def get_current_user(current_user):
    """현재 사용자 정보"""
    try:
        db = next(get_db())
        user = db.query(User).filter(User.id == current_user['user_id']).first()
        
        if not user:
            return jsonify({'success': False, 'error': '사용자를 찾을 수 없습니다'}), 404
        
        return jsonify({
            'success': True,
            'user': {'id': user.id, 'username': user.username, 'email': user.email}
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
        
        db = next(get_db())
        new_combination = SavedCombination(
            user_id=current_user['user_id'],
            numbers=json.dumps(numbers),
            draw_number=draw_number
        )
        
        db.add(new_combination)
        db.commit()
        db.refresh(new_combination)
        
        return jsonify({'success': True, 'saved_id': new_combination.id, 'message': '번호가 저장되었습니다'}), 201
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/combinations/my', methods=['GET'])
@token_required
def get_my_combinations(current_user):
    """내 저장된 조합 목록"""
    try:
        draw_number = request.args.get('draw_number', type=int)
        db = next(get_db())
        
        query = db.query(SavedCombination).filter(SavedCombination.user_id == current_user['user_id'])
        if draw_number:
            query = query.filter(SavedCombination.draw_number == draw_number)
        
        combinations = query.order_by(SavedCombination.created_at.desc()).all()
        
        result = []
        for combo in combinations:
            result.append({
                'id': combo.id,
                'numbers': json.loads(combo.numbers),
                'draw_number': combo.draw_number,
                'created_at': combo.created_at.isoformat(),
                'checked': combo.checked,
                'matched_count': combo.matched_count,
                'prize': combo.prize
            })
        
        return jsonify({'success': True, 'combinations': result})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/combinations/<int:combination_id>', methods=['DELETE'])
@token_required
def delete_combination(current_user, combination_id):
    """저장된 조합 삭제"""
    try:
        db = next(get_db())
        combination = db.query(SavedCombination).filter(
            SavedCombination.id == combination_id,
            SavedCombination.user_id == current_user['user_id']
        ).first()
        
        if not combination:
            return jsonify({'success': False, 'error': '조합을 찾을 수 없습니다'}), 404
        
        db.delete(combination)
        db.commit()
        
        return jsonify({'success': True, 'message': '삭제되었습니다'})
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
        
        db = next(get_db())
        combinations = db.query(SavedCombination).filter(
            SavedCombination.user_id == current_user['user_id'],
            SavedCombination.draw_number == draw_number
        ).all()
        
        results = []
        for combo in combinations:
            saved_numbers = json.loads(combo.numbers)
            result = check_result(saved_numbers, winning_numbers, bonus_number)
            
            combo.checked = True
            combo.matched_count = result['matched_count']
            combo.prize = result['prize']
            
            results.append({
                'combination_id': combo.id,
                'numbers': saved_numbers,
                'matched_count': result['matched_count'],
                'matched_numbers': result['matched_numbers'],
                'has_bonus': result['has_bonus'],
                'prize': result['prize']
            })
        
        db.commit()
        
        return jsonify({
            'success': True,
            'winning_numbers': winning_numbers,
            'bonus_number': bonus_number,
            'results': results
        })
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500
