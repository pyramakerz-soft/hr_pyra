import { Component, EventEmitter, Inject, Output } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ClockService } from '../../Services/clock.service';
import { HttpErrorResponse } from '@angular/common/http';
import { ClockEventService } from '../../Services/clock-event.service';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-clock-in-pop-up',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './clock-in-pop-up.component.html',
  styleUrl: './clock-in-pop-up.component.css'
})
export class ClockInPopUpComponent {

  public lat: number = 196.0000000;
  public lng: number = 173.0000000;
  public IsClockedIn: boolean = false;
  public reversedGeo: any;
  public locationName: string = "";
  sites: { value: string, label: string }[] = [
    { value: 'home', label: 'home' },
    { value: 'site', label: 'site' },
  ];
  EmpName: string = "";
  JobTitle: string = "";
  WorkHome:boolean=false;
  UtcTime:string="";
  selectedSite: string = '';

  constructor(public dialogRef: MatDialogRef<ClockInPopUpComponent>, public clockService: ClockService, public clockEventService: ClockEventService, public revGeo: ReverseGeocodingService, @Inject(MAT_DIALOG_DATA) public data: any , ) {

    this.EmpName = data.Name;
    this.JobTitle = data.job_title;
     this.WorkHome=data.work_home;
    console.log(this.EmpName, this.JobTitle , this.WorkHome)

  }

  closeDialog(): void {
    this.IsClockedIn = false;
    this.dialogRef.close(this.IsClockedIn);
  }

  public async ngOnInit(): Promise<void> {
    await this.getLocation();

    const result = await this.revGeo.getAddress(this.lat, this.lng);
    this.reversedGeo = result.formatted_address;
    this.locationName = result.address_components[1].long_name + " , " + result.address_components[2].long_name + " , " + result.address_components[3].long_name
  }



  getCurrentTimeInUTC(): string {
    const currentDate = new Date();
  
    // Extract the individual date and time components
    const year = currentDate.getUTCFullYear();
    const month = String(currentDate.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-based, so add 1
    const day = String(currentDate.getUTCDate()).padStart(2, '0');
    const hours = String(currentDate.getUTCHours()).padStart(2, '0');
    const minutes = String(currentDate.getUTCMinutes()).padStart(2, '0');
    const seconds = String(currentDate.getUTCSeconds()).padStart(2, '0');
  
    // Combine components into the desired format
    const formattedUTCDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  
    return formattedUTCDate;
  }

  async sendLocation(): Promise<void> {
    await this.getLocation();
    this.UtcTime= this.getCurrentTimeInUTC();

    if(this.WorkHome==false){
    this.clockService.CreateClockIn(this.lat, this.lng ,this.UtcTime ,"site").subscribe(
      (response: any) => {
        this.IsClockedIn = true;
        this.dialogRef.close(this.IsClockedIn);
        this.clockEventService.notifyClockedIn();
      },
      (error: HttpErrorResponse) => {
        const errorMessage = error.error?.message || 'An unknown error occurred';
        console.log(error.error.message)
        if(error.error.message=="The location type field is required"){
          Swal.fire({
            text: "The location type field is required",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",

          });
        }
      else  if (error.error.message.includes("User is not located at the correct location")){
        Swal.fire({
          text: "You Are not located at the correct location",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
      }
      else{
        Swal.fire({
          text: "Try In Another Time",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
      }
        console.log(this.lat, this.lng)
      }
    );
  }
  else{
    this.clockService.CreateClockIn(this.lat, this.lng , this.UtcTime,this.selectedSite).subscribe(
      (response: any) => {
        this.IsClockedIn = true;
        this.dialogRef.close(this.IsClockedIn);
        this.clockEventService.notifyClockedIn();
      },
      (error: HttpErrorResponse) => {
        const errorMessage = error.error?.message || 'An unknown error occurred';
        console.log(error.error.message)
        if(error.error.message=="The location type field is required"){
          Swal.fire({
            text: "The location type field is required",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",

          });
        }
      else  if (error.error.message.includes("User is not located at the correct location")){
        Swal.fire({
          text: "You Are not located at the correct location",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
      }
      else{
        Swal.fire({
          text: "Try In Another Time",
          confirmButtonText: "OK",
          confirmButtonColor: "#FF7519",

        });
      }
        console.log(this.lat, this.lng)
      }
    );
  }
  }

  getLocation(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (typeof window !== 'undefined' && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position: GeolocationPosition) => {
            this.lat = position.coords.latitude;
            this.lng = position.coords.longitude;

            resolve();
          },
          (error: GeolocationPositionError) => {
            Swal.fire({
              text: "Error retrieving location.",
              confirmButtonText: "OK",
              confirmButtonColor: "#FF7519",

            });
            reject(error);
          },
          {
            enableHighAccuracy: true,
            maximumAge: 0, // Disable caching for the most up-to-date reading
            timeout: 10000 // Allow up to 10 seconds to retrieve location
          }
        );
      } else {
        console.warn('Geolocation is not supported or not running in a browser.');
        reject(new Error('Geolocation is not supported or not running in a browser.'));
      }
    });
  }




  getCurrentFormattedTime(): string {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    const minutesStr = minutes < 10 ? '0' + minutes : minutes.toString();

    return `${hours}:${minutesStr} ${ampm}`;
  }

  getCurrentDate(): string {
    const date = new Date();
    return date.toLocaleDateString('en-US', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  }

  getWorkTypes(){

  }
}