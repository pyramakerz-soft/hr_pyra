import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class ChartsService {

  baseUrl:string=""
  constructor(public http: HttpClient, private router: Router , public Api:ApiService) {

    this.baseUrl=Api.BaseUrl
  }


  GetEmployeePerMonth(Year:Number){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.get(`${this.baseUrl}/employees_per_month?year=${Year}`, { headers, responseType: 'json' });
  }


}
