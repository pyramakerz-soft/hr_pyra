import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { DashboardMetricSummary, PresenceSnapshot } from '../Models/dashboard-summary';

interface DashboardSummaryResponse {
  summary: {
    metrics: DashboardMetricSummary;
    notifications: any[];
    service_actions: any[];
  };
}

interface DashboardPresenceResponse {
  presence: PresenceSnapshot;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardInsightsService {
  private readonly baseUrl: string;

  constructor(private readonly http: HttpClient, private readonly api: ApiService) {
    this.baseUrl = this.api.BaseUrl;
  }

  getSummary(): Observable<DashboardSummaryResponse> {
    return this.http.get<DashboardSummaryResponse>(`${this.baseUrl}/dashboard/summary`, {
      headers: this.buildHeaders(),
    });
  }

  getPresence(date?: string): Observable<DashboardPresenceResponse> {
    let params = new HttpParams();

    if (date) {
      params = params.set('date', date);
    }

    return this.http.get<DashboardPresenceResponse>(`${this.baseUrl}/dashboard/presence`, {
      headers: this.buildHeaders(),
      params,
    });
  }

  private buildHeaders(): HttpHeaders {
    const token = localStorage.getItem('token') || '';
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }
}

