import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class TimeApiService {

  constructor(public http: HttpClient) { }


  getCurrentTime() {
    return this.http.get<{ datetime: string }>('http://worldtimeapi.org/api/timezone/Etc/UTC');
  }
}
