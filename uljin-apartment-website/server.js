const express = require('express');
const path = require('path');
const bodyParser = require('body-parser');
const { Pool } = require('pg');
const app = express();
const PORT = process.env.PORT || 3002;

// 정적 파일 제공
app.use(express.static('public'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// EJS 템플릿 엔진 설정
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// PostgreSQL Pool 설정
const pool = new Pool({
  host: '127.0.0.1',
  user: 'myuser',
  password: 'tngkrrhk',
  database: 'mydb',
  port: 5432,
});

// 상담 신청 API
app.post('/api/consult', async (req, res) => {
  try {
    const {
      name, email, phone, title, content,
      agree_privacy, reply_type
    } = req.body;
    const reply_via_email = reply_type === 'email';
    const reply_via_sms = reply_type === 'sms';
    const result = await pool.query(
      `INSERT INTO consult_requests
      (name, email, phone, title, content, agree_privacy, reply_via_email, reply_via_sms)
      VALUES ($1,$2,$3,$4,$5,$6,$7,$8) RETURNING id`,
      [name, email, phone, title, content, agree_privacy === 'on' || agree_privacy === true || agree_privacy === 'true', reply_via_email, reply_via_sms]
    );
    res.json({ success: true, id: result.rows[0].id });
  } catch (err) {
    console.error(err);
    res.status(500).json({ success: false, error: 'DB 저장 오류' });
  }
});

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