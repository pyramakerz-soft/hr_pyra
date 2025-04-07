import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { TeamLead } from '../Models/teamLead';

@Injectable({
  providedIn: 'root'
})
export class TeamLeadsService {
  baseurl =""; 

  constructor(public http: HttpClient , public Api:ApiService) { 
    this.baseurl=Api.BaseUrl+"/users/teamlead_names"

  }

  getall(): Observable<TeamLead[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<TeamLead[]>(this.baseurl, { headers });
  }
}
