import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  BaseUrl="http://127.0.0.1:8000/api"
    // BaseUrl="https://pyramakerz-artifacts.com/hr/public/api"


  constructor() { }
}
