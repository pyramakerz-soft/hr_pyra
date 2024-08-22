import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ReverseGeocodingService {

  private loadPromise!: Promise<void>;
  constructor(private http: HttpClient) {
    this.loadPromise = new Promise((resolve) => {
      if (typeof google === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyA3LxsmNEJv-yxSF8khxA4LgZwF_k0xePU&libraries=places';
        script.onload = () => resolve();
        document.head.appendChild(script);
      } else {
        resolve();
      }
    });
  }
  reverseGeocode(lat: number, lng: number) {
    const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=YOUR_API_KEY`;
    return this.http.get(url);
  }
  
  load(): Promise<void> {
    return this.loadPromise;
  }
  
  getAddress(lat: number, lng: number): Promise<any> {
    return new Promise((resolve, reject) => {
      this.http
        .get<any>(
          `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=AIzaSyA3LxsmNEJv-yxSF8khxA4LgZwF_k0xePU`
        )
        .pipe(
          map((geoData) => {
            if (!geoData || !geoData.results || geoData.results.length === 0)
              throw null;
            return geoData.results[0];
          })
        )
        .subscribe(
          (data: any) => {
            resolve(data);
          },
          (e: any) => {
            reject(e);
          }
        );
    });
  }


}