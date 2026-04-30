import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { ApiService } from './api.service';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class B2bSlotService {
  baseUrl: string;

  constructor(private http: HttpClient, private api: ApiService) {
    this.baseUrl = `${this.api.BaseUrl}/b2b-slots`;
  }

  private getHeaders() {
    const token = localStorage.getItem('token');
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }

  getActiveSlotForUser(userId: number): Observable<any> {
    return this.http.get(`${this.baseUrl}/user/${userId}`, { headers: this.getHeaders() });
  }

  createSlot(payload: any): Observable<any> {
    return this.http.post(`${this.baseUrl}`, payload, { headers: this.getHeaders() });
  }

  updateSlot(slotId: number, payload: any): Observable<any> {
    return this.http.put(`${this.baseUrl}/${slotId}`, payload, { headers: this.getHeaders() });
  }

  deactivateSlot(slotId: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/${slotId}`, { headers: this.getHeaders() });
  }

  getAllSlots(userId?: number): Observable<any> {
    let url = this.baseUrl;
    if (userId) {
      url += `?user_id=${userId}`;
    }
    return this.http.get(url, { headers: this.getHeaders() });
  }
}
