import { api } from '../services/api';

export const commentService = {
  // 댓글 목록 조회
  getComments: async (boardType, boardId) => {
    try {
      const response = await api.get(`/board/${boardType}/${boardId}/comments`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching comments:', error);
      throw error.response?.data || { message: '댓글을 불러오는 중 오류가 발생했습니다.' };
    }
  },

  // 댓글 작성
  createComment: async (boardType, boardId, commentData) => {
    try {
      const response = await api.post(`/board/${boardType}/${boardId}/comments`, commentData, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error creating comment:', error);
      throw error.response?.data || { message: '댓글 작성 중 오류가 발생했습니다.' };
    }
  },

  // 댓글 수정
  updateComment: async (boardType, boardId, commentId, commentData) => {
    try {
      const response = await api.put(`/board/${boardType}/${boardId}/comments/${commentId}`, commentData, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error updating comment:', error);
      throw error.response?.data || { message: '댓글 수정 중 오류가 발생했습니다.' };
    }
  },

  // 댓글 삭제
  deleteComment: async (boardType, boardId, commentId) => {
    try {
      const response = await api.delete(`/board/${boardType}/${boardId}/comments/${commentId}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting comment:', error);
      throw error.response?.data || { message: '댓글 삭제 중 오류가 발생했습니다.' };
    }
  }
}; 