<?php
/**
 * 로또 예측 시스템 - 관리자 페이지
 * 최고 관리자: 김남규 (lgcjnk2@gmail.com)
 */

session_start();

// 관리자 비밀번호 설정 (실제 사용 시 변경하세요!)
define('ADMIN_PASSWORD', 'lotto2024admin!');

// 로그인 처리
if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = '비밀번호가 올바르지 않습니다.';
    }
}

// 로그아웃 처리
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// 로그인 확인
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// DB 연결
$db_path = __DIR__ . '/data/lotto.db';
$db = null;
$users = [];
$stats = [
    'total_users' => 0,
    'total_combinations' => 0
];

if ($is_logged_in && file_exists($db_path)) {
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 사용자 목록 조회
        $stmt = $db->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 통계 조회
        $stats['total_users'] = count($users);
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM saved_combinations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_combinations'] = $result['count'];
        
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로또 예측 시스템 - 관리자</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .header .subtitle {
            color: #94a3b8;
            font-size: 1rem;
        }

        .logout-btn {
            position: absolute;
            top: 2rem;
            right: 2rem;
            padding: 0.75rem 1.5rem;
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            border-radius: 8px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: white;
        }

        /* 로그인 폼 */
        .login-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #fff;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.15);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .error {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            color: #fca5a5;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* 통계 카드 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .stat-card h3 {
            color: #94a3b8;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* 사용자 테이블 */
        .users-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .users-section h2 {
            margin-bottom: 1.5rem;
            color: #fff;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #cbd5e1;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .admin-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #64748b;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php if (!$is_logged_in): ?>
        <!-- 로그인 폼 -->
        <div class="login-container">
            <h2>🔐 관리자 로그인</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>관리자 비밀번호</label>
                    <input type="password" name="admin_password" required autofocus>
                </div>
                <button type="submit" class="login-btn">로그인</button>
            </form>
        </div>
    <?php else: ?>
        <!-- 관리자 대시보드 -->
        <a href="?logout" class="logout-btn">로그아웃</a>
        
        <div class="container">
            <div class="header">
                <h1>🎰 로또 예측 시스템 관리자</h1>
                <p class="subtitle">최고 관리자: 김남규 (lgcjnk2@gmail.com)</p>
            </div>

            <!-- 통계 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>총 회원 수</h3>
                    <div class="value"><?= $stats['total_users'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>저장된 조합 수</h3>
                    <div class="value"><?= $stats['total_combinations'] ?></div>
                </div>
            </div>

            <!-- 회원 목록 -->
            <div class="users-section">
                <h2>👥 회원 목록</h2>
                
                <?php if (isset($db_error)): ?>
                    <div class="error">데이터베이스 오류: <?= htmlspecialchars($db_error) ?></div>
                <?php elseif (empty($users)): ?>
                    <div class="no-data">등록된 회원이 없습니다.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>사용자명</th>
                                    <th>이메일</th>
                                    <th>가입일</th>
                                    <th>권한</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <?php if ($user['email'] === 'lgcjnk2@gmail.com'): ?>
                                                <span class="admin-badge">👑 최고 관리자</span>
                                            <?php else: ?>
                                                일반 회원
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
