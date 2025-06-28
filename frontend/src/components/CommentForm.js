import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import { commentService } from '../services/comment';

const CommentForm = ({ onCommentAdded, parentId = null }) => {
  const { boardType, id } = useParams();
  const [formData, setFormData] = useState({
    writer: '',
    password: '',
    content: ''
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await commentService.createComment(boardType, id, {
        ...formData,
        parent_id: parentId
      });
      setFormData({
        writer: '',
        password: '',
        content: ''
      });
      if (onCommentAdded) {
        onCommentAdded();
      }
    } catch (error) {
      console.error('Error creating comment:', error);
      alert(error.message || '댓글 작성 중 오류가 발생했습니다.');
    }
  };

  return (
    <form onSubmit={handleSubmit} className="mb-4">
      <div className="row g-3">
        <div className="col-md-4">
          <input
            type="text"
            className="form-control"
            name="writer"
            value={formData.writer}
            onChange={handleChange}
            placeholder="작성자"
            required
          />
        </div>
        <div className="col-md-4">
          <input
            type="password"
            className="form-control"
            name="password"
            value={formData.password}
            onChange={handleChange}
            placeholder="비밀번호"
            required
          />
        </div>
        <div className="col-12">
          <textarea
            className="form-control"
            name="content"
            value={formData.content}
            onChange={handleChange}
            placeholder="댓글을 입력하세요"
            rows="3"
            required
          />
        </div>
        <div className="col-12">
          <button type="submit" className="btn btn-primary">
            {parentId ? '답글 작성' : '댓글 작성'}
          </button>
        </div>
      </div>
    </form>
  );
};

export default CommentForm; 