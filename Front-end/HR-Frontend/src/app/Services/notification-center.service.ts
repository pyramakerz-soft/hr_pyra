import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { SystemNotificationRecord } from '../Models/system-notification';

interface NotificationTypesResponse {
  types: Record<string, string>;
}

interface NotificationIndexResponse {
  notifications: SystemNotificationRecord[];
}

interface NotificationStoreResponse {
  notification: SystemNotificationRecord;
}

@Injectable({
  providedIn: 'root'
})
export class NotificationCenterService {
  private readonly baseUrl: string;

  constructor(private readonly http: HttpClient, private readonly api: ApiService) {
    this.baseUrl = this.api.BaseUrl;
  }

  getTypes(): Observable<NotificationTypesResponse> {
    return this.http.get<NotificationTypesResponse>(`${this.baseUrl}/notifications/types`, {
      headers: this.buildHeaders(),
    });
  }

  getNotifications(limit = 20): Observable<NotificationIndexResponse> {
    const params = new HttpParams().set('limit', limit.toString());

    return this.http.get<NotificationIndexResponse>(`${this.baseUrl}/notifications`, {
      headers: this.buildHeaders(),
      params,
    });
  }

  createNotification(payload: any): Observable<NotificationStoreResponse> {
    return this.http.post<NotificationStoreResponse>(`${this.baseUrl}/notifications`, payload, {
      headers: this.buildHeaders(),
    });
  }

  markAsRead(notificationId: number, userId?: number): Observable<any> {
    const body: any = {};

    if (userId) {
      body.user_id = userId;
    }

    return this.http.post(`${this.baseUrl}/notifications/${notificationId}/read`, body, {
      headers: this.buildHeaders(),
    });
  }

  private buildHeaders(): HttpHeaders {
    const token = localStorage.getItem('token') || '';
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }
}

