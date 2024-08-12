import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RoleModel } from '../Models/role-model';

@Injectable({
  providedIn: 'root',
})
export class PermissionsService {
  baseurl = 'http://127.0.0.1:8000/api/permissions';

  token =
    'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE3MjM0NDE3MTIsImV4cCI6MTcyMzQ0NTMxMiwibmJmIjoxNzIzNDQxNzEyLCJqdGkiOiJDamZtam5VOXBRQk4yc2Y4Iiwic3ViIjoiMSIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.d929E9Bj9OeVUYthWKZ_2ni8yv9wpjpDiTUInOpfyso';

  constructor(public http: HttpClient) {}

  GetAll() {
    const headers = new HttpHeaders({
      Authorization: `Bearer ${this.token}`, // Use backticks for template literals
    });

    return this.http.get<RoleModel[]>(`${this.baseurl}`, { headers }); // Ensure URL path is correct
  }
}
