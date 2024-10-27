import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from './api.service';
import { Observable } from 'rxjs';
import { Issue } from '../Models/issue';

@Injectable({
  providedIn: 'root'
})
export class IssuesService {

  baseurl:string = "";
  token: string = "";
  updateIssueUrl:string ="";

  constructor(private route: ActivatedRoute, public http: HttpClient,public Api:ApiService) {

    this.baseurl=Api.BaseUrl+"/get_clock_issues"
    this.updateIssueUrl=Api.BaseUrl+"/update_clock_issue"

   }

   getall(numb:number,date:string): Observable<Issue[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Issue[]>(`${this.baseurl}?month=${date}&page=${numb}`, { headers });
  }

  UpdateIssue(id: number): Observable<any> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
  
    // Ensure the correct payload is passed along with headers
    return this.http.post<any>(`${this.updateIssueUrl}/${id}`, {}, { headers });
  }
  
  searchByDate(date:string){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Issue[]>(`${this.baseurl}?date=${date}`, { headers });
  }
  
  
  }


