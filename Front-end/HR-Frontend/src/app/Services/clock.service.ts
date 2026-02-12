import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';
import { Clock } from '../Models/clock';
import { EmployeeDashboard } from '../Models/employee-dashboard';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class ClockService {

  baseUrl: string = ""
  constructor(public http: HttpClient, private router: Router, public Api: ApiService) {

    this.baseUrl = Api.BaseUrl
  }

  CreateClockIn(latitude: number, longitude: number, clock_in: string, location_type: string = "site") {
    const body = { latitude, longitude, clock_in, location_type };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseUrl}/clock_in`, body, { headers, responseType: 'json' });
  }
  exportSelectedUsers(userIds: number[], from_day?: string, to_day?: string) {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    const body = { user_ids: userIds, from_day, to_day };
    return this.http.post(
      `${this.baseUrl}/users/export-clocks`,
      body,
      { headers, responseType: 'blob' }
    );
  }

  CreateClockInByHrForOther(userId: number, location_id: number | null, clock_in: string, location_type: string = "site") {
    let body
    if (location_type == "home") {
      body = { location_type, clock_in };
    }
    else {
      body = { location_type, clock_in, location_id };
    }
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseUrl}/clock_in/user/${userId}`, body, { headers, responseType: 'json' });
  }

  CreateClockOut(latitude: number, longitude: number, clock_out: string) {
    const body = { latitude, longitude, clock_out };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseUrl}/clock_out`, body, { headers, responseType: 'json' });
  }

  GetUserClocksById(id: number, PgNumber: number, date: string, from_day?: string, to_day?: string): Observable<EmployeeDashboard[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    let url: string;
    if (from_day && to_day) {
      url = `${this.baseUrl}/clocks/user/${id}?page=${PgNumber}&from_day=${from_day}&to_day=${to_day}`;
    } else {
      url = `${this.baseUrl}/clocks/user/${id}?month=${date}&page=${PgNumber}`;
    }
    return this.http.get<EmployeeDashboard[]>(url, { headers });
  }

  SearchByDate(id: number, date: string) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<EmployeeDashboard[]>(`${this.baseUrl}/clocks/user/${id}?date=${date}`, { headers });

  }

  UpdateUserClock(Userid: number, clockId: number, clock_in: String, clock_out: string) {
    const body = { clock_in, clock_out };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<EmployeeDashboard>(`${this.baseUrl}/update_clock/user/${Userid}/clock/${clockId}`, body, { headers, responseType: 'json' });
  }

  UpdateUserClockOut(Userid: number, clockId: number, clock_out: string) {
    const body = { clock_out };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<EmployeeDashboard>(`${this.baseUrl}/update_clock/user/${Userid}/clock/${clockId}`, body, { headers, responseType: 'json' });
  }

  ExportUserDataById(id: number, fromDate?: string, toDate?: string) {


    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    // Build the URL with the optional "fromDate" and "toDate" parameters
    let url = `${this.baseUrl}/clocks/user/${id}?export=true`;

    if (fromDate && toDate) {
      url += `&from_day=${fromDate}&to_day=${toDate}`;
    }

    return this.http.get(url, { headers, responseType: 'blob' });

  }


  ExportAbsentUserData(fromDate?: string, toDate?: string, departmentId?: number | 'none' | null, userId?: number | null, ids?: number[]) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    // Build the URL with the optional parameters
    const params = new URLSearchParams();
    params.append('export', 'true');

    if (fromDate) params.append('from_day', fromDate);
    if (toDate) params.append('to_day', toDate);

    if (departmentId !== null && departmentId !== undefined) {
      params.append('department_id', String(departmentId));
    }

    if (userId) {
      params.append('user_id', String(userId));
    }

    if (ids && ids.length > 0) {
      ids.forEach(id => params.append('ids[]', String(id)));
    }

    const url = `${this.baseUrl}/getAbsentUser/?${params.toString()}`;

    return this.http.get(url, { headers, responseType: 'blob' });
  }

  ExportAllUserDataById(fromDay: string, toDay: string, departmentId: string): Observable<Blob> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    let url = `${this.baseUrl}/all_clocks?export=true&from_day=${fromDay}&to_day=${toDay}`;

    if (departmentId !== "AllDepartment") {
      url += `&department_id=${departmentId}`;
    }

    return this.http.get(url, { headers, responseType: 'blob' });
  }

  downloadAllUsersExcel(): Observable<Blob> {//new
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get(`${this.baseUrl}/users/getAllUsers?export=true`, { headers, responseType: 'blob' });
  }


  GetClockByID(id: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Clock>(`${this.baseUrl}/clock_by_id/${id}`, { headers });
  }
}
