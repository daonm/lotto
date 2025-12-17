<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>컬럼 추가</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e293b; color: #e2e8f0; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        button { padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; }
        button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>🔧 DB 컬럼 추가</h1>
    
    <?php
    if (!isset($_GET['confirm'])) {
        echo '<p>saved_combinations 테이블에 session_id와 user_type 컬럼을 추가합니다.</p>';
        echo '<form method="get">';
        echo '<input type="hidden" name="confirm" value="yes">';
        echo '<button type="submit">컬럼 추가 실행</button>';
        echo '</form>';
        exit;
    }
    
    $db_path = __DIR__ . '/data/lotto.db';
    
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo '<h2>실행 중...</h2>';
        
        // 컬럼 존재 확인
        $stmt = $db->query("PRAGMA table_info(saved_combinations)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $column_names = array_column($columns, 'name');
        
        $has_session_id = in_array('session_id', $column_names);
        $has_user_type = in_array('user_type', $column_names);
        
        // session_id 추가
        if (!$has_session_id) {
            echo '<p>session_id 컬럼 추가 중...</p>';
            $db->exec("ALTER TABLE saved_combinations ADD COLUMN session_id TEXT");
            echo '<p class="success">✓ session_id 컬럼 추가 완료</p>';
        } else {
            echo '<p class="success">✓ session_id 컬럼 이미 존재</p>';
        }
        
        // user_type 추가
        if (!$has_user_type) {
            echo '<p>user_type 컬럼 추가 중...</p>';
            $db->exec("ALTER TABLE saved_combinations ADD COLUMN user_type TEXT DEFAULT 'member'");
            echo '<p class="success">✓ user_type 컬럼 추가 완료</p>';
        } else {
            echo '<p class="success">✓ user_type 컬럼 이미 존재</p>';
        }
        
        // 기존 데이터 업데이트
        echo '<p>기존 데이터 업데이트 중...</p>';
        $count = $db->exec("UPDATE saved_combinations SET user_type = 'member' WHERE user_type IS NULL OR user_type = ''");
        echo '<p class="success">✓ ' . $count . '개 레코드 업데이트</p>';
        
        echo '<hr>';
        echo '<h2 class="success">✅ 마이그레이션 완료!</h2>';
        echo '<p><a href="admin.php"><button>관리자 페이지로 이동</button></a></p>';
        echo '<p><a href="index.html"><button style="background:#64748b;">메인 페이지로 이동</button></a></p>';
        
    } catch (PDOException $e) {
        echo '<p class="error">❌ 오류: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>
</body>
</html>
