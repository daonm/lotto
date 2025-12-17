"""
사용자 인증 유틸리티
JWT 토큰 생성/검증 및 비밀번호 해싱
"""

import jwt
import bcrypt
from datetime import datetime, timedelta
from functools import wraps
from flask import request, jsonify

# JWT 설정
SECRET_KEY = 'your-secret-key-change-this-in-production'  # 프로덕션에서는 환경변수로 관리
ALGORITHM = 'HS256'
TOKEN_EXPIRATION_HOURS = 24


def hash_password(password: str) -> str:
    """
    비밀번호를 bcrypt로 해싱합니다.
    
    Args:
        password: 평문 비밀번호
    
    Returns:
        해싱된 비밀번호 문자열
    """
    # bcrypt는 바이트로 작업하므로 인코딩
    password_bytes = password.encode('utf-8')
    salt = bcrypt.gensalt()
    hashed = bcrypt.hashpw(password_bytes, salt)
    return hashed.decode('utf-8')


def verify_password(password: str, password_hash: str) -> bool:
    """
    비밀번호를 검증합니다.
    
    Args:
        password: 평문 비밀번호
        password_hash: 저장된 해시
    
    Returns:
        일치하면 True, 아니면 False
    """
    password_bytes = password.encode('utf-8')
    hash_bytes = password_hash.encode('utf-8')
    return bcrypt.checkpw(password_bytes, hash_bytes)


def generate_token(user_id: int, username: str) -> str:
    """
    JWT 토큰을 생성합니다.
    
    Args:
        user_id: 사용자 ID
        username: 사용자명
    
    Returns:
        JWT 토큰 문자열
    """
    payload = {
        'user_id': user_id,
        'username': username,
        'exp': datetime.utcnow() + timedelta(hours=TOKEN_EXPIRATION_HOURS),
        'iat': datetime.utcnow()
    }
    
    token = jwt.encode(payload, SECRET_KEY, algorithm=ALGORITHM)
    return token


def verify_token(token: str) -> dict:
    """
    JWT 토큰을 검증하고 페이로드를 반환합니다.
    
    Args:
        token: JWT 토큰 문자열
    
    Returns:
        페이로드 딕셔너리 또는 None (검증 실패 시)
    """
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        return payload
    except jwt.ExpiredSignatureError:
        return None  # 토큰 만료
    except jwt.InvalidTokenError:
        return None  # 유효하지 않은 토큰


def token_required(f):
    """
    API 엔드포인트에 토큰 인증을 요구하는 데코레이터
    
    Usage:
        @app.route('/api/protected')
        @token_required
        def protected_route(current_user):
            return jsonify({'user': current_user})
    """
    @wraps(f)
    def decorated(*args, **kwargs):
        token = None
        
        # Authorization 헤더에서 토큰 추출
        if 'Authorization' in request.headers:
            auth_header = request.headers['Authorization']
            try:
                # "Bearer <token>" 형식
                token = auth_header.split(' ')[1]
            except IndexError:
                return jsonify({'success': False, 'error': 'Invalid token format'}), 401
        
        if not token:
            return jsonify({'success': False, 'error': 'Token is missing'}), 401
        
        # 토큰 검증
        payload = verify_token(token)
        
        if not payload:
            return jsonify({'success': False, 'error': 'Token is invalid or expired'}), 401
        
        # 페이로드를 current_user로 전달
        return f(current_user=payload, *args, **kwargs)
    
    return decorated


if __name__ == '__main__':
    # 테스트
    print("🔐 Auth Utility Test\n")
    
    # 1. 비밀번호 해싱 테스트
    password = "test123!@#"
    hashed = hash_password(password)
    print(f"Original: {password}")
    print(f"Hashed:   {hashed}")
    print(f"Verify:   {verify_password(password, hashed)}")
    print(f"Wrong:    {verify_password('wrongpass', hashed)}\n")
    
    # 2. JWT 토큰 테스트
    token = generate_token(user_id=1, username='testuser')
    print(f"Token: {token}\n")
    
    payload = verify_token(token)
    print(f"Payload: {payload}")
