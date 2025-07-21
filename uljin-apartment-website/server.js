const express = require('express');
const path = require('path');
const app = express();
const PORT = process.env.PORT || 3002;

// 정적 파일 제공
app.use(express.static('public'));

// EJS 템플릿 엔진 설정
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// 메인 페이지 라우트
app.get('/', (req, res) => {
    res.render('pages/index');
});

// 서버 시작
app.listen(PORT, () => {
    console.log(`🚀 울진 아파트 홈페이지 서버가 시작되었습니다!`);
    console.log(`📱 브라우저에서 접속: http://localhost:${PORT}`);
    console.log(`🌐 외부 접속: http://222.100.103.227:${PORT}`);
});

// 에러 핸들링
app.use((err, req, res, next) => {
    console.error('서버 오류:', err);
    res.status(500).send('서버 오류가 발생했습니다.');
}); 