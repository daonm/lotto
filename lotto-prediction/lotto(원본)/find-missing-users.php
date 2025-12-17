<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>누락된 2명 찾기</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e293b; color: #e2e8f0; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .warning { color: #fbbf24; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #334155; }
    </style>
</head>
<body>
    <h1>🔍 누락된 2명 찾기</h1>
    
    <?php
    $db_path = __DIR__ . '/data/lotto.db';
    
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 현재 복구된 회원
        echo '<h2>✅ 복구된 회원 (6명)</h2>';
        $stmt = $db->query("SELECT id, username, email, created_at FROM users ORDER BY id");
        $current_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $current_user_ids = array_column($current_users, 'id');
        
        echo '<table>';
        echo '<tr><th>ID</th><th>이름</th><th>이메일</th><th>가입일</th></tr>';
        foreach ($current_users as $user) {
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td>' . htmlspecialchars($user['username']) . '</td>';
            echo '<td>' . htmlspecialchars($user['email']) . '</td>';
            echo '<td>' . $user['created_at'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        echo '<p>현재 user_id: ' . implode(', ', $current_user_ids) . '</p>';
        
        // saved_combinations에서 모든 user_id 찾기
        echo '<hr>';
        echo '<h2>💾 조합 데이터에 남아있는 user_id</h2>';
        $stmt = $db->query("SELECT DISTINCT user_id FROM saved_combinations WHERE user_id IS NOT NULL ORDER BY user_id");
        $combo_user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo '<p>조합을 저장한 user_id: ' . implode(', ', $combo_user_ids) . '</p>';
        
        // 누락된 user_id 찾기
        $missing_user_ids = array_diff($combo_user_ids, $current_user_ids);
        
        if (!empty($missing_user_ids)) {
            echo '<hr>';
            echo '<h2 class="error">❌ 누락된 회원 (2명)</h2>';
            echo '<p class="error">누락된 user_id: <strong>' . implode(', ', $missing_user_ids) . '</strong></p>';
            
            echo '<table>';
            echo '<tr><th>User ID</th><th>저장된 조합 수</th><th>마지막 활동</th></tr>';
            
            foreach ($missing_user_ids as $uid) {
                echo '<tr>';
                echo '<td>' . $uid . '</td>';
                
                // 조합 수
                $stmt = $db->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_activity FROM saved_combinations WHERE user_id = ?");
                $stmt->execute([$uid]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo '<td>' . $result['count'] . '개</td>';
                echo '<td>' . $result['last_activity'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="success">✅ 누락된 회원 없음!</p>';
        }
        
        echo '<hr>';
        echo '<h2>💡 복구 방법</h2>';
        
        if (!empty($missing_user_ids)) {
            echo '<h3>옵션 1: 더 최근 백업 시도</h3>';
            echo '<p>12월 16일 오전 5시가 아닌 <strong>12월 16일 저녁 또는 12월 17일 새벽 백업</strong>이 있는지 확인:</p>';
            echo '<ul>';
            echo '<li>나의서비스관리 > DATA&DB복원/백업</li>';
            echo '<li>"1일전 (2025-12-17, 05시 백업됨)" 시도</li>';
            echo '<li>⚠️ 단, 이미 손상된 후일 가능성이 높음</li>';
            echo '</ul>';
            
            echo '<h3>옵션 2: 수동 재가입</h3>';
            echo '<p>누락된 2명에게 연락하여 다시 가입 요청</p>';
            echo '<ul>';
            echo '<li>기존 saved_combinations 데이터는 user_id로 남아있음</li>';
            echo '<li>새로 가입하면 새로운 user_id를 받게 됨</li>';
            echo '<li>⚠️ 기존 조합 데이터와 연결 끊김</li>';
            echo '</ul>';
            
            echo '<h3>옵션 3: Cafe24에 추가 지원 요청</h3>';
            echo '<p>1:1 문의로 다음 요청:</p>';
            echo '<ul>';
            echo '<li>"12월 16일 오후 또는 저녁 시스템 스냅샷 복원 가능 여부"</li>';
            echo '<li>"증분 백업 외 전체 백업본 존재 여부"</li>';
            echo '</ul>';
        }
        
    } catch (PDOException $e) {
        echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>
</body>
</html>
