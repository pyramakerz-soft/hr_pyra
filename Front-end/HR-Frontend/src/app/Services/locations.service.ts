import { Injectable } from '@angular/core';
import { Location } from '../Models/location';
import { Observable } from 'rxjs';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { AssignLocationToUser } from '../Models/assign-location-to-user';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class LocationsService {

  baseurl =""; 
  url="";
  constructor(public http: HttpClient, public Api:ApiService) {
    this.baseurl=Api.BaseUrl+"/locations"
    this.url=Api.BaseUrl+"/"


   }

  getall(pgNumber:number): Observable<Location[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Location[]>(`${this.baseurl}?page=${pgNumber}`, { headers });
  }


  getByID(id:number): Observable<Location> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<Location>(`${this.baseurl}/{id}`, { headers });
  }

  EditByID(name:string , address:string ,latitude: number, longitude: number , id:number){
    const body = { name , address ,latitude, longitude };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseurl}/${id}`, body, { headers, responseType: 'json' });

  }

  CreateAddress(name:string , address:string ,latitude: number, longitude: number ){
    const body = { name , address ,latitude, longitude };
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.post(`${this.baseurl}`, body, { headers, responseType: 'json' });

  }


  DeleteByID( id:number){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.delete(`${this.baseurl}/${id}`, { headers, responseType: 'json' });

  }

  GetAllNames(){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.get(`${this.url}location_names`, { headers, responseType: 'json' });
  }

  SearchByNames(name:string){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.get(`${this.baseurl}?search=${name}`, { headers, responseType: 'json' });

  }

  GetLocationsByUserId(userId: number){
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    return this.http.get(`${this.baseurl}?search=${name}`, { headers, responseType: 'json' });
  }
}
