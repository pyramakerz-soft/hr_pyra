import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { SalaryAdjustment } from '../Models/salary-adjustment';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class SalaryAdjustmentService {
  private baseURL: string;

  constructor(private http: HttpClient, private api: ApiService) {
    this.baseURL = this.api.BaseUrl;
  }

  private getHeaders() {
    const token = localStorage.getItem('token');
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }

  getAdjustments(page: number = 1, fromDate?: string, toDate?: string): Observable<any> {
    let params = new HttpParams().set('page', page.toString());
    if (fromDate) params = params.set('from_date', fromDate);
    if (toDate) params = params.set('to_date', toDate);

    return this.http.get<any>(`${this.baseURL}/adjustments`, { headers: this.getHeaders(), params });
  }

  createAdjustment(adjustment: SalaryAdjustment): Observable<any> {
    return this.http.post<any>(`${this.baseURL}/adjustments`, adjustment, { headers: this.getHeaders() });
  }

  updateAdjustment(id: number, adjustment: Partial<SalaryAdjustment>): Observable<any> {
    return this.http.put<any>(`${this.baseURL}/adjustments/${id}`, adjustment, { headers: this.getHeaders() });
  }

  deleteAdjustment(id: number): Observable<any> {
    return this.http.delete<any>(`${this.baseURL}/adjustments/${id}`, { headers: this.getHeaders() });
  }

  exportAdjustments(fromDate?: string, toDate?: string): Observable<Blob> {
    let params = new HttpParams();
    if (fromDate) params = params.set('from_date', fromDate);
    if (toDate) params = params.set('to_date', toDate);

    const headers = new HttpHeaders({
      'Authorization': `Bearer ${localStorage.getItem('token')}`,
      'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });

    return this.http.get(`${this.baseURL}/adjustments/export`, {
      headers: headers,
      params: params,
      responseType: 'blob'
    });
  }
}
