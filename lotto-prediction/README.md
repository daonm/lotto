# 🎯 골프친구-독식 로또 당첨 예측 시스템

로또 명인의 비법을 기반으로 한 AI 당첨 번호 예측 시스템

![Python](https://img.shields.io/badge/Python-3.8+-blue)
![Flask](https://img.shields.io/badge/Flask-3.0.0-green)
![License](https://img.shields.io/badge/License-MIT-yellow)

## 📋 프로젝트 개요

최근 6개월간의 로또 당첨 번호를 분석하고, 검증된 통계 규칙을 적용하여 높은 확률의 번호 조합을 생성하는 웹 애플리케이션입니다.

### ✨ 주요 기능

- 📊 **6개월 데이터 분석**: 동행복권 공식 API에서 최근 당첨 번호 수집
- 🎯 **필수 첨부 규칙**: 3-4회 등장한 핵심 번호와 지난주 번호 필수 포함
- 🚫 **7가지 필터링 규칙**: 연속 번호, 고빈도 번호, 좌우/상하 쏠림 제거
- 📈 **통계 기반 필터링**: 합계 범위, 홀짝 균형, 3의 배수 포함 등
- 💎 **프리미엄 UI/UX**: 다크 모드, 그라데이션, 애니메이션 효과

## 🚀 빠른 시작

### 1. 의존성 설치

```bash
cd backend
pip install -r requirements.txt
```

### 2. 로또 데이터 수집

```bash
python data_collector.py
```

### 3. 서버 실행

```bash
python app.py
```

서버가 `http://localhost:5000`에서 실행됩니다.

### 4. 브라우저에서 접속

```
http://localhost:5000
```

## 📁 프로젝트 구조

```
lotto-prediction/
├── backend/
│   ├── app.py                 # Flask API 서버
│   ├── data_collector.py      # 로또 데이터 수집
│   ├── rule_engine.py         # 핵심 규칙 엔진
│   ├── utils.py               # 유틸리티 함수
│   └── requirements.txt       # Python 의존성
├── frontend/
│   ├── index.html            # 메인 페이지
│   ├── styles.css            # 스타일시트
│   └── script.js             # 프론트엔드 로직
├── data/
│   └── lotto_history.json    # 당첨 번호 데이터
└── README.md
```

## 🎲 핵심 규칙

### 필수 첨부 규칙 (로또 명인의 비법)

1. **핵심 번호 선정**: 최근 6개월간 3-4회 등장한 번호
2. **지난주 번호 포함**: 지난주 당첨 번호를 각 조합에 필수 포함
3. **조합 생성**: 핵심 번호와 지난주 번호로 조합 생성

### 제외 및 필터링 규칙

1. ❌ **연속된 수**: 3자리 이상 연속되는 번호 제외
2. ❌ **최근 고빈도**: 최근 10회차에서 3회 이상 등장한 번호 제외 (39, 43 예외)
3. ❌ **좌우 쏠림**: 로또 용지의 좌우 2줄에 번호 몰림 방지
4. ❌ **상하 쏠림**: 위 3줄에 번호 몰림 방지

### 통계 기반 필터링

1. ✅ **합계 범위**: 121~160 사이 (당첨 확률 49%)
2. ✅ **홀짝 균형**: 모두 홀수 또는 모두 짝수 제외
3. ✅ **범위 제약**: 첫 번호 ≤14, 마지막 번호 ≥35
4. ✅ **3의 배수**: 적절히 포함

## 🔌 API 엔드포인트

### POST /api/generate
번호 조합을 생성합니다.

**Request:**
```json
{
  "num_combinations": 10
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "combinations": [...],
    "core_numbers": [...],
    "last_week_numbers": [...],
    "exclude_numbers": [...],
    "statistics": {...}
  }
}
```

### GET /api/history
로또 당첨 번호 히스토리를 반환합니다.

**Query Parameters:**
- `limit`: 최대 반환 개수 (기본값: 26)

### GET /api/statistics
통계 정보를 반환합니다.

### POST /api/update-data
로또 데이터를 업데이트합니다.

## 🧪 테스트

### 규칙 엔진 테스트
```bash
cd backend
python rule_engine.py
```

### 데이터 수집 테스트
```bash
cd backend
python data_collector.py
```

## 🎨 UI 미리보기

- **다크 모드**: 눈의 피로를 줄이는 프리미엄 다크 테마
- **그라데이션**: 로또 볼 색상 및 배경 그라데이션
- **애니메이션**: 부드러운 전환 효과 및 인터랙션
- **반응형**: 모바일, 태블릿, 데스크톱 지원

## ⚠️ 주의사항

본 시스템은 통계적 분석을 기반으로 하며, **당첨을 보장하지 않습니다**.

로또는 무작위 추첨이므로, 어떤 시스템도 100% 당첨을 예측할 수 없습니다.

## 📝 라이선스

MIT License

## 🤝 기여

버그 리포트 및 기능 제안은 이슈로 등록해주세요.

---

**Made with ❤️ by 골프친구-독식 Team**
