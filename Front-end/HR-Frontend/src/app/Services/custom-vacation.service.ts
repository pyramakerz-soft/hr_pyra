import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { CustomVacation, CustomVacationPayload } from '../Models/custom-vacation';

export interface CustomVacationListResponse {
  result: string;
  message: string;
  vacations: {
    data: CustomVacation[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
  };
}

@Injectable({
  providedIn: 'root'
})
export class CustomVacationService {

  private readonly baseUrl: string;

  constructor(private http: HttpClient, private api: ApiService) {
    this.baseUrl = `${this.api.BaseUrl}/custom-vacations`;
  }

  private buildHeaders(): HttpHeaders {
    const token = localStorage.getItem('token') ?? '';
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }

  getVacations(params?: {
    page?: number;
    per_page?: number;
    search?: string;
    department_id?: number;
    sub_department_id?: number;
    from_date?: string;
    to_date?: string;
  }): Observable<CustomVacationListResponse> {
    let httpParams = new HttpParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
          httpParams = httpParams.set(key, `${value}`);
        }
      });
    }

    return this.http.get<CustomVacationListResponse>(this.baseUrl, {
      headers: this.buildHeaders(),
      params: httpParams,
    });
  }

  createVacation(payload: CustomVacationPayload): Observable<any> {
    return this.http.post<any>(this.baseUrl, payload, {
      headers: this.buildHeaders(),
    });
  }

  updateVacation(id: number, payload: Partial<CustomVacationPayload>): Observable<any> {
    return this.http.put<any>(`${this.baseUrl}/${id}`, payload, {
      headers: this.buildHeaders(),
    });
  }

  deleteVacation(id: number): Observable<any> {
    return this.http.delete<any>(`${this.baseUrl}/${id}`, {
      headers: this.buildHeaders(),
    });
  }
}

