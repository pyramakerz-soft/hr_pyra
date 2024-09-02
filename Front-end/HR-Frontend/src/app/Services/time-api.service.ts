import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class TimeApiService {

  constructor(public http: HttpClient) { }


  getCurrentTimeGoogle(latitude:number, longitude:number) {
    // return this.http.get<{ datetime: string }>('http://worldtimeapi.org/api/timezone/Etc/UTC');
    return this.http.get<{ dstOffset: number, rawOffset: number, timeZoneId: string, timeZoneName: string }>(
      `https://maps.googleapis.com/maps/api/timezone/json?location=${latitude},${longitude}&timestamp=1693651200&key=AIzaSyA3LxsmNEJv-yxSF8khxA4LgZwF_k0xePU`
    );
  }

//   getCurrentTime() {
//     return this.http.get<{ datetime: string }>('http://worldtimeapi.org/api/timezone/Etc/UTC');
// }
}