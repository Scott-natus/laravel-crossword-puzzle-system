const url = require('url');

module.exports = async function(req, res, pool) {
    const parsedUrl = url.parse(req.url, true);
    const query = parsedUrl.query;
    
    try {
        if (req.method === 'GET') {
            // 공지사항 목록 조회
            const limit = query.limit ? parseInt(query.limit) : 10;
            const offset = query.offset ? parseInt(query.offset) : 0;
            
            const result = await pool.query(`
                SELECT id, title, content, is_important, view_count, created_at, updated_at
                FROM notices 
                ORDER BY is_important DESC, created_at DESC 
                LIMIT $1 OFFSET $2
            `, [limit, offset]);
            
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                success: true,
                data: result.rows,
                total: result.rows.length
            }));
            
        } else if (req.method === 'POST') {
            // 공지사항 등록 (관리자용)
            let body = '';
            req.on('data', chunk => {
                body += chunk.toString();
            });
            
            req.on('end', async () => {
                try {
                    const { title, content, is_important } = JSON.parse(body);
                    
                    const result = await pool.query(`
                        INSERT INTO notices (title, content, is_important)
                        VALUES ($1, $2, $3)
                        RETURNING id, title, content, is_important, created_at
                    `, [title, content, is_important || false]);
                    
                    res.writeHead(201, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({
                        success: true,
                        data: result.rows[0]
                    }));
                } catch (error) {
                    res.writeHead(400, { 'Content-Type': 'application/json' });
                    res.end(JSON.stringify({
                        success: false,
                        message: '잘못된 요청입니다.'
                    }));
                }
            });
            
        } else {
            res.writeHead(405, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                success: false,
                message: '허용되지 않는 메서드입니다.'
            }));
        }
    } catch (error) {
        console.error('공지사항 API 오류:', error);
        res.writeHead(500, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            success: false,
            message: '서버 오류가 발생했습니다.'
        }));
    }
}; 