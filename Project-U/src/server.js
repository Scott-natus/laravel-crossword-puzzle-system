const http = require('http');
const url = require('url');
const fs = require('fs');
const path = require('path');
const { Pool } = require('pg');
require('dotenv').config();

// PostgreSQL 연결 설정
const pool = new Pool({
  user: process.env.DB_USER || 'myuser',
  host: process.env.DB_HOST || '127.0.0.1',
  database: process.env.DB_NAME || 'mydb',
  password: process.env.DB_PASSWORD || 'tngkrrhk',
  port: process.env.DB_PORT || 5432,
});

// MIME 타입 매핑
const mimeTypes = {
  '.html': 'text/html',
  '.css': 'text/css',
  '.js': 'text/javascript',
  '.json': 'application/json',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon'
};

// 라우트 핸들러
const routes = {
  '/': 'views/index.html',
  '/about': 'views/about.html',
  '/location': 'views/location.html',
  '/units': 'views/units.html',
  '/sales': 'views/sales.html',
  '/gallery': 'views/gallery.html',
  '/notice': 'views/notice.html',
  '/faq': 'views/faq.html',
  '/contact': 'views/contact.html',
  '/directions': 'views/directions.html'
};

// API 라우트 핸들러
const apiRoutes = {
    '/api/notices': require('./routes/notices')
};

// 정적 파일 서빙
function serveStaticFile(res, filePath) {
  const extname = path.extname(filePath);
  const contentType = mimeTypes[extname] || 'application/octet-stream';

  fs.readFile(filePath, (err, content) => {
    if (err) {
      if (err.code === 'ENOENT') {
        res.writeHead(404);
        res.end('File not found');
      } else {
        res.writeHead(500);
        res.end('Server error');
      }
    } else {
      res.writeHead(200, { 'Content-Type': contentType });
      res.end(content);
    }
  });
}

// 서버 생성
const server = http.createServer((req, res) => {
  const parsedUrl = url.parse(req.url, true);
  const pathname = parsedUrl.pathname;

  console.log(`${req.method} ${pathname}`);

  // CORS 헤더 설정
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  // OPTIONS 요청 처리
  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  // API 라우트 처리
  if (pathname.startsWith('/api/')) {
    const apiRoute = apiRoutes[pathname];
    if (apiRoute) {
      apiRoute(req, res, pool);
    } else {
      res.writeHead(404);
      res.end('API not found');
    }
    return;
  }

  // 정적 파일 처리
  if (pathname.startsWith('/public/')) {
    const filePath = path.join(__dirname, pathname);
    serveStaticFile(res, filePath);
    return;
  }

  // 페이지 라우트 처리
  const routePath = routes[pathname];
  if (routePath) {
    const filePath = path.join(__dirname, routePath);
    serveStaticFile(res, filePath);
  } else {
    // 404 처리
    res.writeHead(404);
    res.end('Page not found');
  }
});

// 서버 시작
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  console.log(`Visit: http://localhost:${PORT}`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully');
  server.close(() => {
    console.log('Process terminated');
    pool.end();
  });
}); 