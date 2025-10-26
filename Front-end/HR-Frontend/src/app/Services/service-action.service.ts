import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { ServiceActionDefinition, ServiceActionRecord } from '../Models/service-action';

interface ServiceActionAvailableResponse {
  actions: {
    definitions: ServiceActionDefinition[];
    scopes: { key: string; label: string }[];
  };
}

interface ServiceActionIndexResponse {
  service_actions: ServiceActionRecord[];
}

interface ServiceActionStoreResponse {
  service_action: ServiceActionRecord;
}

@Injectable({
  providedIn: 'root'
})
export class ServiceActionService {
  private readonly baseUrl: string;

  constructor(private readonly http: HttpClient, private readonly api: ApiService) {
    this.baseUrl = this.api.BaseUrl;
  }

  getAvailable(): Observable<ServiceActionAvailableResponse> {
    return this.http.get<ServiceActionAvailableResponse>(`${this.baseUrl}/service-actions/available`, {
      headers: this.buildHeaders(),
    });
  }

  getRecent(limit = 10): Observable<ServiceActionIndexResponse> {
    const params = new HttpParams().set('limit', limit.toString());

    return this.http.get<ServiceActionIndexResponse>(`${this.baseUrl}/service-actions`, {
      headers: this.buildHeaders(),
      params,
    });
  }

  execute(payload: any): Observable<ServiceActionStoreResponse> {
    return this.http.post<ServiceActionStoreResponse>(`${this.baseUrl}/service-actions`, payload, {
      headers: this.buildHeaders(),
    });
  }

  revertLast(): Observable<ServiceActionStoreResponse> {
    return this.http.post<ServiceActionStoreResponse>(`${this.baseUrl}/service-actions/revert`, {}, {
      headers: this.buildHeaders(),
    });
  }

  private buildHeaders(): HttpHeaders {
    const token = localStorage.getItem('token') || '';
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }
}
