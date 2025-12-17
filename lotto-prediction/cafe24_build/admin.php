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
    'total_combinations' => 0,
    'member_combinations' => 0,
    'guest_combinations' => 0
];

if ($is_logged_in && file_exists($db_path)) {
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 사용자 목록 조회 (조합 수 포함)
        $stmt = $db->query("
            SELECT u.id, u.username, u.email, u.created_at,
                   COUNT(c.id) as combo_count
            FROM users u
            LEFT JOIN saved_combinations c ON u.id = c.user_id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 통계 조회
        $stats['total_users'] = count($users);
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM saved_combinations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_combinations'] = $result['count'];
        
        // 회원/비회원 조합 수
        $stmt = $db->query("SELECT COUNT(*) as count FROM saved_combinations WHERE user_id IS NOT NULL");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['member_combinations'] = $result['count'];
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM saved_combinations WHERE user_id IS NULL");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['guest_combinations'] = $result['count'];
        
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

        /* User Detail Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 2rem;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            color: #fff;
        }

        .close-modal {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            color: #ef4444;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #ef4444;
            color: white;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .user-info p {
            margin-bottom: 0.5rem;
            color: #cbd5e1;
        }

        .user-info strong {
            color: #fff;
        }

        .combinations-list {
            display: grid;
            gap: 1rem;
        }

        .combo-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .combo-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .lotto-balls {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .lotto-ball {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .win-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .win-stat {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .win-stat .label {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }

        .win-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fbbf24;
        }

        .clickable {
            cursor: pointer;
            color: #60a5fa;
            transition: all 0.2s;
        }

        .clickable:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        .section-divider {
            margin: 3rem 0;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
        }

        .all-combos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-controls select,
        .filter-controls button {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-controls button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            font-weight: 600;
        }

        .filter-controls button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
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
                    <h3>회원 조합</h3>
                    <div class="value"><?= $stats['member_combinations'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>게스트 조합</h3>
                    <div class="value"><?= $stats['guest_combinations'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>전체 조합</h3>
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
                                    <th>조합 수</th>
                                    <th>가입일</th>
                                    <th>권한</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td class="clickable" onclick="showUserDetail(<?= $user['id'] ?>)"><?= htmlspecialchars($user['username']) ?></td>
                                        <td class="clickable" onclick="showUserDetail(<?= $user['id'] ?>)"><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= $user['combo_count'] ?>개</td>
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

            <div class="section-divider"></div>

            <!-- 전체 조합 관리 -->
            <div class="users-section">
                <div class="all-combos-header">
                    <h2>🎲 전체 조합 관리</h2>
                    <div class="filter-controls">
                        <select id="drawFilter">
                            <option value="">전체 회차</option>
                        </select>
                        <button onclick="loadAllCombinations()">조회</button>
                    </div>
                </div>
                <div id="allCombosContent">
                    <p class="no-data">회차를 선택하고 조회 버튼을 클릭하세요.</p>
                </div>
            </div>
        </div>

        <!-- User Detail Modal -->
        <div id="userModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalUserName">회원 상세 정보</h2>
                    <button class="close-modal" onclick="closeUserModal()">✕ 닫기</button>
                </div>
                <div class="user-info" id="modalUserInfo"></div>
                <h3 style="color: #fff; margin-bottom: 1rem;">저장된 조합</h3>
                <div class="combinations-list" id="modalCombinations"></div>
                <h3 style="color: #fff; margin: 1.5rem 0 1rem;">당첨 통계</h3>
                <div class="win-stats" id="modalWinStats"></div>
            </div>
        </div>

        <script>
        // Load draw numbers for filter
        fetch('./api/statistics.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('drawFilter');
                    const currentDraw = data.data.total_draws;
                    for (let i = currentDraw; i >= Math.max(1, currentDraw - 50); i--) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = i + '회';
                        select.appendChild(option);
                    }
                }
            });

        function showUserDetail(userId) {
            fetch(`./api/admin/user-details.php?user_id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const modal = document.getElementById('userModal');
                        document.getElementById('modalUserName').textContent = data.user.username + ' 님';
                        
                        document.getElementById('modalUserInfo').innerHTML = `
                            <p><strong>이메일:</strong> ${data.user.email}</p>
                            <p><strong>가입일:</strong> ${new Date(data.user.created_at).toLocaleString('ko-KR')}</p>
                            <p><strong>총 조합 수:</strong> ${data.stats.total_combinations}개</p>
                        `;
                        
                        // Combinations
                        const combosHtml = data.combinations.map(c => `
                            <div class="combo-card">
                                <div class="combo-header">
                                    <span>${c.draw_number}회차</span>
                                    <span>${new Date(c.created_at).toLocaleDateString('ko-KR')}</span>
                                </div>
                                <div class="lotto-balls">
                                    ${c.numbers.map(n => `<div class="lotto-ball">${n}</div>`).join('')}
                                </div>
                            </div>
                        `).join('');
                        document.getElementById('modalCombinations').innerHTML = combosHtml || '<p class="no-data">저장된 조합이 없습니다.</p>';
                        
                        // Win stats
                        const statsHtml = Object.entries(data.stats.wins).map(([rank, count]) => `
                            <div class="win-stat">
                                <div class="label">${rank}</div>
                                <div class="value">${count}</div>
                            </div>
                        `).join('');
                        document.getElementById('modalWinStats').innerHTML = statsHtml;
                        
                        modal.classList.add('active');
                    } else {
                        alert('사용자 정보를 불러올 수 없습니다.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('오류가 발생했습니다.');
                });
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function loadAllCombinations() {
            const drawNumber = document.getElementById('drawFilter').value;
            const url = drawNumber ? `./api/admin/all-combinations.php?draw_number=${drawNumber}` : './api/admin/all-combinations.php';
            
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div style="margin-bottom: 1rem; color: #94a3b8;">
                                <strong>총 ${data.total_combinations}개</strong> (회원: ${data.member_combinations}개, 게스트: ${data.guest_combinations}개)
                            </div>
                        `;
                        
                        if (data.results && data.results.winning_numbers) {
                            html += `
                                <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <h3 style="color: #fff; margin-bottom: 0.5rem;">당첨 번호</h3>
                                    <div class="lotto-balls">
                                        ${data.results.winning_numbers.map(n => `<div class="lotto-ball">${n}</div>`).join('')}
                                        <span style="margin: 0 0.5rem; color: #fff; font-size: 1.5rem;">+</span>
                                        <div class="lotto-ball">${data.results.bonus_number}</div>
                                    </div>
                                    <div style="margin-top: 1rem; color: #cbd5e1;">
                                        ${Object.entries(data.results.prizes || {}).map(([rank, count]) => 
                                            `<span style="margin-right: 1rem;"><strong>${rank}:</strong> ${count}개</span>`
                                        ).join('')}
                                    </div>
                                </div>
                            `;
                        }
                        
                        html += '<div class="table-container"><table><thead><tr><th>ID</th><th>소유자</th><th>회차</th><th>번호</th>';
                        if (data.results) {
                            html += '<th>일치</th><th>결과</th>';
                        }
                        html += '</tr></thead><tbody>';
                        
                        data.combinations.forEach(c => {
                            html += `<tr>
                                <td>${c.id}</td>
                                <td>${c.is_guest ? '🔓 ' : ''}${c.username}</td>
                                <td>${c.draw_number}회</td>
                                <td><div class="lotto-balls">${c.numbers.map(n => `<div class="lotto-ball" style="width:30px;height:30px;font-size:0.85rem;">${n}</div>`).join('')}</div></td>`;
                            
                            if (data.results) {
                                html += `<td>${c.matched_count || 0}개</td>
                                         <td>${c.prize ? '<strong style="color: #fbbf24;">' + c.prize + '</strong>' : '낙첨'}</td>`;
                            }
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table></div>';
                        document.getElementById('allCombosContent').innerHTML = html;
                    } else {
                        alert('조합을 불러올 수 없습니다: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('오류가 발생했습니다.');
                });
        }

        // Close modal on outside click
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeUserModal();
            }
        });
        </script>
    <?php endif; ?>
</body>
</html>
