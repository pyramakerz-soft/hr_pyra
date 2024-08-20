import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root'
})
export class ClockService {

  baseUrl = "http://127.0.0.1:8000/api"
  
  constructor(public http: HttpClient, private router: Router) {
  } 


  
  CreateClockIn(latitude: number, longitude: number) {
    const body = { latitude, longitude };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    
    return this.http.post(`${this.baseUrl}/clock_in`, body, { headers, responseType: 'json' });
  }

  CreateClockOut(latitude: number, longitude: number) {
    const body = { latitude, longitude };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseUrl}/clock_out`, body, { headers, responseType: 'json' });
  }

}  
  











