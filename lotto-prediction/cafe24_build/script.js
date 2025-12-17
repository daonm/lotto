/**
 * 골프친구-독식 로또 예측 시스템
 * Frontend JavaScript with Authentication
 */

// API Base URL
// API Base URL
// API Base URL
const API_URL = './api';

// State Management
let currentUser = null;
let authToken = null;
let savedCombinations = [];
let currentPage = 'main'; // 'main' or 'mypage'

// DOM Elements
const elements = {
    // Auth
    loginBtn: document.getElementById('loginBtn'),
    signupBtn: document.getElementById('signupBtn'),
    logoutBtn: document.getElementById('logoutBtn'),
    myPageBtn: document.getElementById('myPageBtn'),
    authButtons: document.getElementById('authButtons'),
    userMenu: document.getElementById('userMenu'),
    userName: document.getElementById('userName'),
    authModal: document.getElementById('authModal'),
    closeModal: document.getElementById('closeModal'),
    loginForm: document.getElementById('loginForm'),
    signupForm: document.getElementById('signupForm'),
    showSignup: document.getElementById('showSignup'),
    showLogin: document.getElementById('showLogin'),
    loginFormElement: document.getElementById('loginFormElement'),
    signupFormElement: document.getElementById('signupFormElement'),

    // Pages
    mainPage: document.getElementById('mainPage'),
    myPage: document.getElementById('myPage'),
    backBtn: document.getElementById('backBtn'),

    // Generate
    generateBtn: document.getElementById('generateBtn'),
    numCombinations: document.getElementById('numCombinations'),
    loading: document.getElementById('loading'),
    resultsSection: document.getElementById('resultsSection'),
    resultsInfo: document.getElementById('resultsInfo'),
    combinationsGrid: document.getElementById('combinationsGrid'),

    // Statistics
    coreNumbers: document.getElementById('coreNumbers'),
    lastWeekNumbers: document.getElementById('lastWeekNumbers'),
    excludeNumbers: document.getElementById('excludeNumbers'),
    totalDraws: document.getElementById('totalDraws'),

    // My Page
    savedCombinationsGrid: document.getElementById('savedCombinationsGrid'),
    drawFilter: document.getElementById('drawFilter'),
    checkResultsBtn: document.getElementById('checkResultsBtn'),

    // Batch Controls
    selectAll: document.getElementById('selectAll'),
    deleteSelectedBtn: document.getElementById('deleteSelectedBtn'),
    deleteAllBtn: document.getElementById('deleteAllBtn'),

    // Result Modal
    resultModal: document.getElementById('resultModal'),
    closeResultModal: document.getElementById('closeResultModal'),
    winningNumbersDisplay: document.getElementById('winningNumbersDisplay'),
    resultSummaryContent: document.getElementById('resultSummaryContent'),
    confirmResultBtn: document.getElementById('confirmResultBtn')
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    loadStatistics();
    setupEventListeners();
});

// Authentication Functions
function checkAuth() {
    authToken = localStorage.getItem('authToken');
    const userStr = localStorage.getItem('currentUser');

    if (authToken && userStr) {
        currentUser = JSON.parse(userStr);
        updateUIForAuth(true);
    } else {
        updateUIForAuth(false);
    }
}

function updateUIForAuth(isAuthenticated) {
    if (isAuthenticated && currentUser) {
        elements.authButtons.style.display = 'none';
        elements.userMenu.style.display = 'flex';
        elements.userName.textContent = currentUser.username;
    } else {
        elements.authButtons.style.display = 'flex';
        elements.userMenu.style.display = 'none';
    }
}

async function login(email, password) {
    try {
        const response = await fetch(`${API_URL}/auth/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (data.success) {
            authToken = data.token;
            currentUser = data.user;
            localStorage.setItem('authToken', authToken);
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            updateUIForAuth(true);
            closeAuthModal();
            alert('로그인 성공!');
        } else {
            alert('로그인 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('로그인 중 오류가 발생했습니다.');
    }
}

async function signup(username, email, password) {
    try {
        const response = await fetch(`${API_URL}/auth/signup.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password })
        });

        const data = await response.json();

        if (data.success) {
            alert('회원가입 성공! 로그인해주세요.');
            showLoginForm();
        } else {
            alert('회원가입 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Signup error:', error);
        alert('회원가입 중 오류가 발생했습니다.');
    }
}

function logout() {
    authToken = null;
    currentUser = null;
    localStorage.removeItem('authToken');
    localStorage.removeItem('currentUser');
    updateUIForAuth(false);
    showMainPage();
    alert('로그아웃되었습니다.');
}

// Modal Functions
function openAuthModal() {
    elements.authModal.style.display = 'flex';
}

function closeAuthModal() {
    elements.authModal.style.display = 'none';
}

function showLoginForm() {
    elements.loginForm.style.display = 'block';
    elements.signupForm.style.display = 'none';
}

function showSignupForm() {
    elements.loginForm.style.display = 'none';
    elements.signupForm.style.display = 'block';
}

function updateSaveButtonsState() {
    if (!authToken) return;

    const cards = document.querySelectorAll('.combination-card');
    cards.forEach((card, index) => {
        const saveBtn = card.querySelector('.save-combo-btn');
        if (!saveBtn) return;

        const balls = card.querySelectorAll('.lotto-ball');
        const numbers = Array.from(balls).map(b => parseInt(b.textContent));

        const isSaved = savedCombinations.some(saved =>
            JSON.stringify(saved.numbers.sort((a, b) => a - b)) === JSON.stringify(numbers.sort((a, b) => a - b))
        );

        if (isSaved) {
            saveBtn.textContent = '✅ 저장됨';
            saveBtn.style.backgroundColor = '#6c757d';
            saveBtn.classList.add('saved');
            saveBtn.disabled = true;
        } else {
            saveBtn.textContent = '💾 저장';
            saveBtn.style.backgroundColor = ''; // Reset to default (CSS class handles it)
            saveBtn.classList.remove('saved');
            saveBtn.disabled = false;
        }
    });
}

function showMainPage() {
    elements.mainPage.style.display = 'block';
    elements.myPage.style.display = 'none';
    currentPage = 'main';
    updateSaveButtonsState();
}

function showMyPage() {
    if (!authToken) {
        alert('로그인이 필요합니다.');
        openAuthModal();
        return;
    }

    elements.mainPage.style.display = 'none';
    elements.myPage.style.display = 'block';
    currentPage = 'mypage';
    loadMySavedCombinations();
}

// Statistics
async function loadStatistics() {
    try {
        const response = await fetch(`${API_URL}/statistics.php`);
        const data = await response.json();

        if (data.success) {
            const stats = data.data;
            elements.coreNumbers.textContent = (stats.core_numbers || []).join(', ');
            elements.lastWeekNumbers.textContent = (stats.last_week_numbers || []).join(', ');
            elements.excludeNumbers.textContent = (stats.exclude_numbers || []).join(', ');
            elements.totalDraws.textContent = `${stats.total_draws || 0}회`;
        }
    } catch (error) {
        console.error('Statistics error:', error);
        elements.coreNumbers.textContent = '로드 실패';
        elements.lastWeekNumbers.textContent = '로드 실패';
        elements.excludeNumbers.textContent = '로드 실패';
        elements.totalDraws.textContent = '-';
    }
}

// Generate Combinations
async function generateCombinations() {
    const numCombinations = parseInt(elements.numCombinations.value);

    if (numCombinations < 1 || numCombinations > 20) {
        alert('조합 수는 1-20 사이여야 합니다.');
        return;
    }

    elements.generateBtn.disabled = true;
    elements.loading.classList.add('active');

    try {
        const response = await fetch(`${API_URL}/generate.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ num_combinations: numCombinations })
        });

        const data = await response.json();

        if (data.success) {
            displayResults(data.data);
        } else {
            alert('번호 생성 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Generate error:', error);
        alert('번호 생성 중 오류가 발생했습니다.');
    } finally {
        elements.generateBtn.disabled = false;
        elements.loading.classList.remove('active');
    }
}

function displayResults(data) {
    const { combinations, statistics } = data;

    // Show results section
    elements.resultsSection.style.display = 'block';

    // Results info
    elements.resultsInfo.innerHTML = `
        <p><strong>총 생성:</strong> ${statistics.total_generated}개</p>
        <p><strong>필터링 통과:</strong> ${statistics.after_filtering}개</p>
        <p><strong>통과율:</strong> ${statistics.filter_rate}</p>
    `;

    // Combinations grid
    elements.combinationsGrid.innerHTML = '';

    // 전체 저장 버튼 추가 (로그인 시에만)
    if (authToken && combinations.length > 0) {
        const saveAllContainer = document.createElement('div');
        saveAllContainer.className = 'save-all-container';
        saveAllContainer.style.textAlign = 'right';
        saveAllContainer.style.marginBottom = '1rem';

        const saveAllBtn = document.createElement('button');
        saveAllBtn.textContent = '💾 전체 저장';
        saveAllBtn.className = 'save-all-btn';
        // 인라인 스타일로 즉시 적용 (CSS 파일 수정 없이)
        saveAllBtn.style.padding = '8px 16px';
        saveAllBtn.style.backgroundColor = '#4CAF50';
        saveAllBtn.style.color = 'white';
        saveAllBtn.style.border = 'none';
        saveAllBtn.style.borderRadius = '4px';
        saveAllBtn.style.cursor = 'pointer';
        saveAllBtn.style.fontSize = '14px';
        saveAllBtn.style.fontWeight = 'bold';

        saveAllBtn.addEventListener('click', () => saveAllCombinations(combinations));

        saveAllContainer.appendChild(saveAllBtn);
        elements.combinationsGrid.appendChild(saveAllContainer);
    }

    combinations.forEach((combo, index) => {
        const card = createCombinationCard(combo, index);
        elements.combinationsGrid.appendChild(card);
    });

    // Scroll to results
    elements.resultsSection.scrollIntoView({ behavior: 'smooth' });
}

function createCombinationCard(combo, index) {
    const card = document.createElement('div');
    card.className = 'combination-card';
    card.style.setProperty('--index', index);

    const numbers = combo.numbers;
    const explanation = combo.explanation;

    // HTML 구조 생성
    let saveBtnHtml = '';
    if (authToken) {
        saveBtnHtml = `<button class="save-combo-btn" id="saveBtn-${index}">💾 저장</button>`;
    }

    card.innerHTML = `
        <div class="combination-header">
            <span class="combination-number">#${index + 1}</span>
            ${saveBtnHtml}
        </div>
        <div class="lotto-balls">
            ${numbers.map((num, i) => `
                <div class="lotto-ball color-${(i % 5) + 1}">${num}</div>
            `).join('')}
        </div>
        <div class="combination-explanation">${explanation}</div>
    `;

    // 이벤트 리스너 등록
    if (authToken) {
        const saveBtn = card.querySelector(`#saveBtn-${index}`);
        if (saveBtn) {
            saveBtn.addEventListener('click', (e) => {
                saveCombination(numbers, e.target);
            });
        }
    }

    return card;
}

// Save Combination
async function saveCombination(numbers, btnElement) {
    if (!authToken) {
        alert('로그인이 필요합니다.');
        openAuthModal();
        return;
    }

    // 이미 저장된 경우 중복 저장 방지 (버튼이 비활성화되어 있어도 체크)
    if (btnElement && btnElement.classList.contains('saved')) {
        return;
    }

    // Get next draw number (임시로 1203 사용, 실제로는 현재 회차 + 1)
    const drawNumber = 1203;

    try {
        const response = await fetch(`${API_URL}/combinations/save.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ numbers, draw_number: drawNumber })
        });

        const data = await response.json();

        if (data.success) {
            // 버튼 스타일 변경
            if (btnElement) {
                btnElement.textContent = '✅ 저장됨';
                btnElement.style.backgroundColor = '#6c757d'; // 회색으로 변경
                btnElement.classList.add('saved');
                btnElement.disabled = true;
            } else {
                // 전체 저장 시에는 개별 알림 생략
                if (!btnElement) alert('번호가 저장되었습니다!');
            }
        } else {
            alert('저장 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('저장 중 오류가 발생했습니다.');
    }
}

// Save All Combinations
async function saveAllCombinations(combinations) {
    if (!confirm(`총 ${combinations.length}개의 조합을 모두 저장하시겠습니까?`)) return;

    let savedCount = 0;
    // 모든 저장 버튼을 찾음
    const buttons = document.querySelectorAll('.save-combo-btn');

    for (let i = 0; i < combinations.length; i++) {
        const combo = combinations[i];
        // 버튼이 있으면 해당 버튼을 넘겨서 상태 업데이트
        const btn = buttons[i];

        // 이미 저장된 것은 건너뛰기
        if (btn && btn.classList.contains('saved')) continue;

        await saveCombination(combo.numbers, btn);
        savedCount++;

        // 서버 부하 방지를 위한 약간의 지연
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    alert(`${savedCount}개의 조합이 저장되었습니다.`);
}

// Load My Saved Combinations
async function loadMySavedCombinations() {
    if (!authToken) return;

    try {
        const response = await fetch(`${API_URL}/combinations/my.php`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });

        const data = await response.json();

        if (data.success) {
            savedCombinations = data.combinations;
            displaySavedCombinations(savedCombinations);
            populateDrawFilter(savedCombinations);
            updateBatchControls(); // 초기화
        }
    } catch (error) {
        console.error('Load combinations error:', error);
    }
}

function displaySavedCombinations(combinations) {
    if (combinations.length === 0) {
        elements.savedCombinationsGrid.innerHTML = '<p class="empty-message">저장된 조합이 없습니다.</p>';
        elements.selectAll.disabled = true;
        elements.deleteAllBtn.disabled = true;
        return;
    }

    elements.savedCombinationsGrid.innerHTML = '';
    elements.selectAll.disabled = false;
    elements.deleteAllBtn.disabled = false;

    combinations.forEach(combo => {
        const card = createSavedComboCard(combo);
        elements.savedCombinationsGrid.appendChild(card);
    });

    // 전체 선택 체크박스 초기화
    elements.selectAll.checked = false;
    updateDeleteSelectedBtn();
}

function createSavedComboCard(combo) {
    const card = document.createElement('div');
    card.className = 'saved-combo-card';

    const date = new Date(combo.created_at).toLocaleDateString('ko-KR');

    let resultBadge = '';
    if (combo.checked) {
        if (combo.prize) {
            resultBadge = `<div class="result-badge winner">${combo.prize} 당첨! 🎉</div>`;
        } else {
            resultBadge = `<div class="result-badge loser">낙첨 (${combo.matched_count}개 일치)</div>`;
        }
    }

    card.innerHTML = `
        <input type="checkbox" class="card-checkbox" data-id="${combo.id}">
        <div class="saved-combo-header">
            <div class="combo-meta">
                <strong>회차:</strong> ${combo.draw_number}회 | <strong>저장일:</strong> ${date}
            </div>
            <button class="delete-btn" id="deleteBtn-${combo.id}">🗑️ 삭제</button>
        </div>
        <div class="lotto-balls">
            ${combo.numbers.map((num, i) => `
                <div class="lotto-ball color-${(i % 5) + 1}">${num}</div>
            `).join('')}
        </div>
        ${resultBadge}
    `;

    // 삭제 버튼 이벤트 리스너
    const deleteBtn = card.querySelector(`#deleteBtn-${combo.id}`);
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            deleteCombination(combo.id);
        });
    }

    // 체크박스 이벤트 리스너
    const checkbox = card.querySelector('.card-checkbox');
    checkbox.addEventListener('change', updateDeleteSelectedBtn);

    return card;
}

function updateDeleteSelectedBtn() {
    const checkboxes = document.querySelectorAll('.card-checkbox:checked');
    elements.deleteSelectedBtn.disabled = checkboxes.length === 0;
    elements.deleteSelectedBtn.textContent = checkboxes.length > 0 ? `선택 삭제 (${checkboxes.length})` : '선택 삭제';
}

function updateBatchControls() {
    elements.selectAll.checked = false;
    updateDeleteSelectedBtn();
}

function populateDrawFilter(combinations) {
    const draws = [...new Set(combinations.map(c => c.draw_number))].sort((a, b) => b - a);

    elements.drawFilter.innerHTML = '<option value="">전체</option>';
    draws.forEach(draw => {
        const option = document.createElement('option');
        option.value = draw;
        option.textContent = `${draw}회`;
        elements.drawFilter.appendChild(option);
    });
}

// Delete Combination (Single)
async function deleteCombination(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await fetch(`${API_URL}/combinations/delete-batch.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ ids: [id] }) // Reuse batch delete for single
        });

        const data = await response.json();

        if (data.success) {
            // alert('삭제되었습니다.'); // 너무 빈번한 알림 방지
            loadMySavedCombinations();
        } else {
            alert('삭제 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    }
}

// Batch Delete
async function deleteSelected() {
    const checkboxes = document.querySelectorAll('.card-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));

    if (ids.length === 0) return;

    if (!confirm(`선택한 ${ids.length}개의 조합을 삭제하시겠습니까?`)) return;

    try {
        const response = await fetch(`${API_URL}/combinations/delete-batch.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ ids: ids })
        });

        const data = await response.json();

        if (data.success) {
            alert(`${data.deleted_count}개의 조합이 삭제되었습니다.`);
            loadMySavedCombinations();
        } else {
            alert('삭제 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Batch delete error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    }
}

async function deleteAll() {
    const drawNumber = elements.drawFilter.value;
    const msg = drawNumber ? `${drawNumber}회차의 모든 조합을 삭제하시겠습니까?` : '저장된 모든 조합을 삭제하시겠습니까?';

    if (!confirm(msg)) return;

    try {
        const body = { all: true };
        if (drawNumber) body.draw_number = parseInt(drawNumber);

        const response = await fetch(`${API_URL}/combinations/delete-batch.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(body)
        });

        const data = await response.json();

        if (data.success) {
            alert(`${data.deleted_count}개의 조합이 삭제되었습니다.`);
            loadMySavedCombinations();
        } else {
            alert('삭제 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Delete all error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    }
}

// Check Results
async function checkResults() {
    const drawNumber = elements.drawFilter.value;

    if (!drawNumber) {
        alert('회차를 선택해주세요.');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/combinations/check-results.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ draw_number: parseInt(drawNumber) })
        });

        const data = await response.json();

        if (data.success) {
            showResultModal(data, drawNumber);
            loadMySavedCombinations(); // 결과 업데이트를 위해 목록 갱신
        } else {
            alert('당첨 확인 실패: ' + data.error);
        }
    } catch (error) {
        console.error('Check results error:', error);
        alert('당첨 확인 중 오류가 발생했습니다.');
    }
}

function showResultModal(data, drawNumber) {
    const { winning_numbers, bonus_number, results } = data;

    // 당첨 번호 표시
    elements.winningNumbersDisplay.innerHTML = `
        ${winning_numbers.map((num, i) => `<div class="lotto-ball color-${(i % 5) + 1}">${num}</div>`).join('')}
        <div class="plus-sign">+</div>
        <div class="lotto-ball color-5">${bonus_number}</div>
    `;

    // 결과 요약
    const summary = {
        '1등': 0, '2등': 0, '3등': 0, '4등': 0, '5등': 0, '낙첨': 0
    };

    data.results.forEach(r => {
        if (r.prize) summary[r.prize]++;
        else summary['낙첨']++;
    });

    elements.resultSummaryContent.innerHTML = `
        <div class="result-summary-item">
            <span>1등</span>
            <span class="rank-badge rank-1">${summary['1등']}개</span>
        </div>
        <div class="result-summary-item">
            <span>2등</span>
            <span class="rank-badge rank-2">${summary['2등']}개</span>
        </div>
        <div class="result-summary-item">
            <span>3등</span>
            <span class="rank-badge rank-3">${summary['3등']}개</span>
        </div>
        <div class="result-summary-item">
            <span>4등</span>
            <span class="rank-badge rank-4">${summary['4등']}개</span>
        </div>
        <div class="result-summary-item">
            <span>5등</span>
            <span class="rank-badge rank-5">${summary['5등']}개</span>
        </div>
        <div class="result-summary-item">
            <span>낙첨</span>
            <span class="rank-badge rank-fail">${summary['낙첨']}개</span>
        </div>
    `;

    elements.resultModal.style.display = 'flex';
}

function closeResultModal() {
    elements.resultModal.style.display = 'none';
}

// Event Listeners
function setupEventListeners() {
    // Auth buttons
    elements.loginBtn.addEventListener('click', () => {
        openAuthModal();
        showLoginForm();
    });

    elements.signupBtn.addEventListener('click', () => {
        openAuthModal();
        showSignupForm();
    });

    elements.logoutBtn.addEventListener('click', logout);
    elements.myPageBtn.addEventListener('click', showMyPage);
    elements.backBtn.addEventListener('click', showMainPage);

    // Modal
    elements.closeModal.addEventListener('click', closeAuthModal);
    elements.showSignup.addEventListener('click', (e) => {
        e.preventDefault();
        showSignupForm();
    });
    elements.showLogin.addEventListener('click', (e) => {
        e.preventDefault();
        showLoginForm();
    });

    // Close modal when clicking outside
    elements.authModal.addEventListener('click', (e) => {
        if (e.target === elements.authModal) {
            closeAuthModal();
        }
    });

    // Forms
    elements.loginFormElement.addEventListener('submit', (e) => {
        e.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        login(email, password);
    });

    elements.signupFormElement.addEventListener('submit', (e) => {
        e.preventDefault();
        const username = document.getElementById('signupUsername').value;
        const email = document.getElementById('signupEmail').value;
        const password = document.getElementById('signupPassword').value;
        signup(username, email, password);
    });

    // Generate
    elements.generateBtn.addEventListener('click', generateCombinations);

    // My Page
    elements.drawFilter.addEventListener('change', () => {
        const drawNumber = elements.drawFilter.value;
        if (drawNumber) {
            const filtered = savedCombinations.filter(c => c.draw_number == drawNumber);
            displaySavedCombinations(filtered);
        } else {
            displaySavedCombinations(savedCombinations);
        }
    });

    elements.checkResultsBtn.addEventListener('click', checkResults);

    // Batch Controls
    elements.selectAll.addEventListener('change', (e) => {
        const checkboxes = document.querySelectorAll('.card-checkbox');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateDeleteSelectedBtn();
    });

    elements.deleteSelectedBtn.addEventListener('click', deleteSelected);
    elements.deleteAllBtn.addEventListener('click', deleteAll);

    // Result Modal
    elements.closeResultModal.addEventListener('click', closeResultModal);
    elements.confirmResultBtn.addEventListener('click', closeResultModal);

    // Close result modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === elements.resultModal) closeResultModal();
    });
}
