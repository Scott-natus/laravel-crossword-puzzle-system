import PushNotification from 'react-native-push-notification';
import { Platform } from 'react-native';
import { apiService } from './api';

export class PushNotificationService {
  private static instance: PushNotificationService;
  private isConfigured = false;

  private constructor() {}

  static getInstance(): PushNotificationService {
    if (!PushNotificationService.instance) {
      PushNotificationService.instance = new PushNotificationService();
    }
    return PushNotificationService.instance;
  }

  configure(): void {
    if (this.isConfigured) {
      return;
    }

    PushNotification.configure({
      onRegister: function (token: { os: string; token: string }) {
        console.log('TOKEN:', token);
        // 서버에 토큰 등록
        PushNotificationService.getInstance().registerToken(token.token);
      },
      
      onNotification: function (notification: any) {
        console.log('NOTIFICATION:', notification);
        
        // 알림 클릭 시 처리
        if (notification.userInteraction) {
          PushNotificationService.getInstance().handleNotificationAction(notification.data);
        }
        
        // 알림 표시
        notification.finish();
      },
      
      permissions: {
        alert: true,
        badge: true,
        sound: true,
      },
      
      popInitialNotification: true,
      requestPermissions: true,
    });

    // Android 채널 생성
    if (Platform.OS === 'android') {
      PushNotification.createChannel(
        {
          channelId: 'crossword-puzzle',
          channelName: '십자낱말 퍼즐',
          channelDescription: '퍼즐 게임 알림',
          playSound: true,
          soundName: 'default',
          importance: 4,
          vibrate: true,
        },
        (created: boolean) => console.log(`채널 생성됨: ${created}`)
      );
    }

    this.isConfigured = true;
  }

  async registerToken(token: string): Promise<void> {
    try {
      const platform = Platform.OS === 'ios' || Platform.OS === 'android' ? Platform.OS : 'android';
      await apiService.registerPushToken(token, platform);
      console.log('푸시 토큰이 서버에 등록되었습니다.');
    } catch (error) {
      console.error('푸시 토큰 등록 실패:', error);
    }
  }

  async unregisterToken(token: string): Promise<void> {
    try {
      await apiService.unregisterPushToken(token);
      console.log('푸시 토큰이 서버에서 삭제되었습니다.');
    } catch (error) {
      console.error('푸시 토큰 삭제 실패:', error);
    }
  }

  handleNotificationAction(data: any): void {
    if (!data) return;

    switch (data.action) {
      case 'open_app':
        // 앱 열기
        console.log('앱 열기 액션');
        break;
      case 'show_result':
        // 결과 화면 표시
        console.log('결과 화면 표시 액션');
        break;
      case 'show_achievement':
        // 업적 화면 표시
        console.log('업적 화면 표시 액션');
        break;
      case 'daily_reminder':
        // 일일 퍼즐 알림
        console.log('일일 퍼즐 알림 액션');
        break;
      case 'level_complete':
        // 레벨 완료 알림
        console.log('레벨 완료 알림 액션');
        break;
      default:
        console.log('알 수 없는 알림 액션:', data.action);
    }
  }

  // 로컬 알림 생성
  createLocalNotification(title: string, message: string, data?: any): void {
    PushNotification.localNotification({
      channelId: 'crossword-puzzle',
      title: title,
      message: message,
      playSound: true,
      soundName: 'default',
      importance: 'high',
      vibrate: true,
      userInfo: data,
    });
  }

  // 알림 취소
  cancelAllNotifications(): void {
    PushNotification.cancelAllLocalNotifications();
  }

  // 특정 알림 취소
  cancelNotification(id: string): void {
    PushNotification.cancelLocalNotifications({ id });
  }

  // 배지 카운트 설정
  setBadgeCount(count: number): void {
    PushNotification.setApplicationIconBadgeNumber(count);
  }

  // 배지 카운트 초기화
  clearBadgeCount(): void {
    PushNotification.setApplicationIconBadgeNumber(0);
  }
}

export const pushNotificationService = PushNotificationService.getInstance(); 