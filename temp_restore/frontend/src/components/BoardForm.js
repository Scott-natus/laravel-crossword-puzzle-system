import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import boardService from '../services/board';

const BoardForm = () => {
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    password: '',
    files: []
  });
  const [existingFiles, setExistingFiles] = useState([]);
  const [filesToDelete, setFilesToDelete] = useState([]);
  const [error, setError] = useState('');
  const [uploading, setUploading] = useState(false);
  const navigate = useNavigate();
  const { boardType, id } = useParams();
  const isEditMode = !!id;

  useEffect(() => {
    if (isEditMode) {
      const fetchBoard = async () => {
        try {
          const data = await boardService.getBoard(boardType, id);
          setFormData(prev => ({
            ...prev,
            title: data.title,
            content: data.content
          }));
          setExistingFiles(data.attachments || []);
        } catch (err) {
          setError(err.message || '게시글을 불러오는데 실패했습니다.');
        }
      };
      fetchBoard();
    }
  }, [boardType, id, isEditMode]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prevState => ({
      ...prevState,
      [name]: value
    }));
  };

  const handleFileChange = (e) => {
    setFormData(prevState => ({
      ...prevState,
      files: Array.from(e.target.files)
    }));
  };

  const handleDeleteFile = (fileId) => {
    setFilesToDelete(prev => [...prev, fileId]);
    setExistingFiles(prev => prev.filter(file => file.id !== fileId));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setUploading(true);

    try {
      const formDataToSend = new FormData();
      formDataToSend.append('title', formData.title);
      formDataToSend.append('content', formData.content);
      formDataToSend.append('password', formData.password);
      
      formData.files.forEach(file => {
        formDataToSend.append('attachments[]', file);
      });

      if (isEditMode) {
        formDataToSend.append('_method', 'PUT');
        formDataToSend.append('files_to_delete', JSON.stringify(filesToDelete));
        await boardService.updateBoard(boardType, id, formDataToSend);
      } else {
        await boardService.createBoard(boardType, formDataToSend);
      }
      
      navigate(`/board/${boardType}`);
    } catch (err) {
      setError(err.message || '게시글 저장에 실패했습니다.');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="board-form">
      <h2>{isEditMode ? '게시글 수정' : '게시글 작성'}</h2>
      {error && <div className="alert alert-danger">{error}</div>}
      
      <form onSubmit={handleSubmit}>
        <div className="form-group mb-3">
          <label htmlFor="title">제목</label>
          <input
            type="text"
            className="form-control"
            id="title"
            name="title"
            value={formData.title}
            onChange={handleChange}
            required
          />
        </div>

        <div className="form-group mb-3">
          <label htmlFor="content">내용</label>
          <textarea
            className="form-control"
            id="content"
            name="content"
            value={formData.content}
            onChange={handleChange}
            rows="10"
            required
          />
        </div>

        <div className="form-group mb-3">
          <label htmlFor="password">비밀번호</label>
          <input
            type="password"
            className="form-control"
            id="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
            required
          />
        </div>

        {isEditMode && existingFiles.length > 0 && (
          <div className="form-group mb-3">
            <label>기존 첨부파일</label>
            <ul className="list-group">
              {existingFiles.map(file => (
                <li key={file.id} className="list-group-item d-flex justify-content-between align-items-center">
                  <a 
                    href={`/storage/${file.path}`}
                    download={file.original_name}
                    className="text-decoration-none"
                  >
                    {file.original_name}
                  </a>
                  <button
                    type="button"
                    className="btn btn-sm btn-danger"
                    onClick={() => handleDeleteFile(file.id)}
                  >
                    삭제
                  </button>
                </li>
              ))}
            </ul>
          </div>
        )}

        <div className="form-group mb-3">
          <label htmlFor="files">새 첨부파일</label>
          <input
            type="file"
            className="form-control"
            id="files"
            multiple
            onChange={handleFileChange}
          />
        </div>

        <div className="d-flex justify-content-between">
          <button
            type="button"
            className="btn btn-secondary"
            onClick={() => navigate(`/board/${boardType}${isEditMode ? `/${id}` : ''}`)}
          >
            취소
          </button>
          <button
            type="submit"
            className="btn btn-primary"
            disabled={uploading}
          >
            {uploading ? '저장 중...' : (isEditMode ? '수정' : '작성')}
          </button>
        </div>
      </form>
    </div>
  );
};

export default BoardForm; 