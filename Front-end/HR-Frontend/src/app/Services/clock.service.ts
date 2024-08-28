import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';
import { EmployeeDashboard } from '../Models/employee-dashboard';

@Injectable({
  providedIn: 'root'
})
export class ClockService {

  baseUrl = "http://127.0.0.1:8000/api"

  constructor(public http: HttpClient, private router: Router) {
  }



  CreateClockIn(latitude: number, longitude: number ,clock_in:string, location_type:string="site") {
    const body = { latitude, longitude ,clock_in,location_type};
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseUrl}/clock_in`, body, { headers, responseType: 'json' });
  }

  CreateClockOut(latitude: number, longitude: number ,clock_out:string) {
    const body = { latitude, longitude ,clock_out};
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseUrl}/clock_out`, body, { headers, responseType: 'json' });
  }

  GetUserClocksById(id: number, PgNumber: number, date:string): Observable<EmployeeDashboard[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<EmployeeDashboard[]>(`${this.baseUrl}/clocks/user/${id}?month=${date}&page=${PgNumber}`, { headers });
  } 

  SearchByDate(id: number, date: string) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<EmployeeDashboard[]>(`${this.baseUrl}/clocks/user/${id}?date=${date}`, { headers });

  }

  UpdateUserClock(Userid: number, clockId: number, clock_in: String , clock_out :string) {
    const body = {clock_in , clock_out}; 
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<EmployeeDashboard>(`${this.baseUrl}/update_clock/user/${Userid}/clock/${clockId}`,body,{ headers, responseType: 'json' });
  }


}












