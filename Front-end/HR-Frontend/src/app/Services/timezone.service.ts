import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Timezone } from '../Models/timeZone';

@Injectable({
  providedIn: 'root'
})
export class TimeZoneService {

  baseUrl: string;

  constructor(
    private http: HttpClient,
    private api: ApiService
  ) {
    this.baseUrl = this.api.BaseUrl; // Make sure ApiService provides a valid baseUrl
  }

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');
    return new HttpHeaders().set('Authorization', `Bearer ${token}`);
  }

  // Fetch all timezones
  getAllTimezones(): Observable<Timezone[]> {
    return this.http.get<Timezone[]>(`${this.baseUrl}/timezones`, {
      headers: this.getHeaders()
    });
  }

  // Fetch a specific timezone by ID
  getTimezoneById(id: number): Observable<Timezone> {
    return this.http.get<Timezone>(`${this.baseUrl}/timezones/${id}`, {
      headers: this.getHeaders()
    });
  }

  // Create a new timezone
  createTimezone(data: Timezone): Observable<Timezone> {
    return this.http.post<Timezone>(`${this.baseUrl}/timezones`, data, {
      headers: this.getHeaders()
    });
  }

  // Update an existing timezone
  updateTimezone(id: number, data: Timezone): Observable<Timezone> {
    return this.http.put<Timezone>(`${this.baseUrl}/timezones/${id}`, data, {
      headers: this.getHeaders()
    });
  }

  // Delete a timezone
  deleteTimezone(id: number): Observable<any> {
    return this.http.delete<any>(`${this.baseUrl}/api/timezones/${id}`, {
      headers: this.getHeaders()
    });
  }
}
