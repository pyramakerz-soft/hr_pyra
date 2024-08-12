import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class RolesService {
  
  baseurl ="http://127.0.0.1:8000/api/roles"; 

  constructor() { }
}
