<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>새로운 댓글 알림</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>새로운 댓글이 달렸습니다</h2>
        </div>
        
        <div class="content">
            <p>안녕하세요, {{ $user->name }}님!</p>
            
            <p>귀하의 게시글 "<strong>{{ $board->title }}</strong>"에 새로운 댓글이 달렸습니다.</p>
            
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p><strong>댓글 작성자:</strong> {{ $comment->user->name }}</p>
                <p><strong>댓글 내용:</strong></p>
                <p>{{ $comment->content }}</p>
                <p><small>작성일시: {{ $comment->created_at->format('Y-m-d H:i') }}</small></p>
            </div>
            
            <a href="{{ route('board.show', ['boardType' => $board->boardType->slug, 'board' => $board->id]) }}" class="button">게시글 보기</a>
        </div>
        
        <div class="footer">
            <p>이 알림은 게시글에 새로운 댓글이 달렸을 때 자동으로 발송됩니다.</p>
            <p>알림 설정을 변경하시려면 <a href="{{ route('profile.edit') }}">프로필 설정</a>에서 변경하실 수 있습니다.</p>
        </div>
    </div>
</body>
</html> 