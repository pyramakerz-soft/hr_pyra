import { Component, EventEmitter, Inject, Output } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ClockService } from '../../Services/clock.service';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { ClockEventService } from '../../Services/clock-event.service';
import { ReverseGeocodingService } from '../../Services/reverse-geocoding.service';
import Swal from 'sweetalert2';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { WorkTypeService } from '../../Services/work-type.service';
import { WorkType } from '../../Models/work-type';
import { LocationsService } from '../../Services/locations.service';
import { AssignLocationToUser } from '../../Models/assign-location-to-user';
import { TimeApiService } from '../../Services/time-api.service';
import { catchError, map, Observable, of } from 'rxjs';

@Component({
  selector: 'app-clock-in-pop-up',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './clock-in-pop-up.component.html',
  styleUrl: './clock-in-pop-up.component.css'
})
export class ClockInPopUpComponent {

  apiKey = 'AIzaSyA3LxsmNEJv-yxSF8khxA4LgZwF_k0xePU';
  url = `https://www.googleapis.com/geolocation/v1/geolocate?key=${this.apiKey}`;


  public lat: number = 196.0000000;
  public lng: number = 173.0000000;
  public IsClockedIn: boolean = false;
  WorkHome:boolean=false;
  isClockInFromHrToOtherUser: boolean = false;
  isDropdownOpen = false;
  sites:WorkType[] = [];
  Locations: AssignLocationToUser[] = [];
  public locationName: string = "";
  EmpName: string = "";
  JobTitle: string = "";
  UtcTime:string="";
  selectedSite: string = '';
  public reversedGeo: any;
  isLoading:boolean=true

  userId:number|null = null
  TimeClockInFromHrForOthers:string = ""
  DateClockInFromHrForOthers:string = ""
  LocationClockInFromHrForOthers:number = 0
  formattedTime:string=""
  formattedDate:string=""

  constructor(public dialogRef: MatDialogRef<ClockInPopUpComponent>, public clockService: ClockService, 
    public clockEventService: ClockEventService, public revGeo: ReverseGeocodingService, @Inject(MAT_DIALOG_DATA) public data: any , 
    public locationService: LocationsService, public workType:WorkTypeService , public TimeApi:TimeApiService ,public http:HttpClient) {
      this.EmpName = data.Name;
      this.JobTitle = data.job_title;
      this.WorkHome=data.work_home;
      this.isClockInFromHrToOtherUser=data.isClockInFromHrToOtherUser;
      this.userId=data.userId;
  }

  closeDialog(): void {
    this.IsClockedIn = false;
    this.dialogRef.close(this.IsClockedIn);
  }

  public async ngOnInit(): Promise<void> {
    await this.getLocation();
    this.getCurrentFormattedTime();
    if(this.isClockInFromHrToOtherUser == false){
      const result = await this.revGeo.getAddress(this.lat, this.lng);
      this.reversedGeo = result.formatted_address;
      this.locationName = result.address_components[1].long_name + " , " + result.address_components[2].long_name + " , " + result.address_components[3].long_name
      this.isLoading=false;
    }else{
      this.getLocationsFromServerByUserId()
      this.isLoading = false
    }
    this.getWorkTypes()
  }

  getCurrentTimeInUTC(): Promise<string> {
    return new Promise((resolve, reject) => {
      const timestamp = Math.floor(Date.now() / 1000); // Current timestamp in seconds

      this.TimeApi.getCurrentTimeGoogle(this.lat, this.lng).subscribe((response) => {
        if (response ) {
          // Extract offsets from the response
          const dstOffset = response.dstOffset;
          const rawOffset = response.rawOffset;

          // Calculate total offset in seconds
          const totalOffsetInSeconds = dstOffset + rawOffset;
          
          // Calculate the local timestamp
          const localTimestamp = timestamp + totalOffsetInSeconds;
          
          // Convert local timestamp to UTC timestamp
          const utcTimestamp = localTimestamp - totalOffsetInSeconds;

          // Convert UTC timestamp to date
          const utcDate = new Date(utcTimestamp * 1000);

          // Extract year, month, day, hours, minutes, and seconds
          const year = utcDate.getUTCFullYear();
          const month = ('0' + (utcDate.getUTCMonth() + 1)).slice(-2); // Months are 0-based
          const day = ('0' + utcDate.getUTCDate()).slice(-2);
          const hours = ('0' + utcDate.getUTCHours()).slice(-2);
          const minutes = ('0' + utcDate.getUTCMinutes()).slice(-2);
          const seconds = ('0' + utcDate.getUTCSeconds()).slice(-2);

          // Format the UTC date and time string
          const formattedDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
          resolve(formattedDateTime); // Resolve the promise with formatted UTC time
        } else {
          console.error('Error fetching time zone data:');
          reject('Error fetching time zone data');
        }
      }, (error) => {
        console.error('Subscription error:', error);
        reject('Subscription error');
      });
    });
  }

  async sendLocationByToken(): Promise<void> {
    this.UtcTime=await this.getCurrentTimeInUTC();
    if(this.WorkHome==false){
      this.clockService.CreateClockIn(this.lat, this.lng ,this.UtcTime ,"site").subscribe(
        (response: any) => {
        this.IsClockedIn = true;
        this.dialogRef.close(this.IsClockedIn);
        this.clockEventService.notifyClockedIn();
      },
      (error: HttpErrorResponse) => {
        const errorMessage = error.error?.message || 'An unknown error occurred';
        if(error.error.message.includes("The location type field is required")){
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
        else  if (error.error.message.includes("You already have an existing clock-in without clocking out")){
          Swal.fire({
            text: "You Didn't clock out from the last clock in",
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
          if(error.error.message.includes("The location type field is required")){
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
        else  if (error.error.message.includes("You already have an existing clock-in without clocking out")){
          Swal.fire({
            text: "You Didn't clock out from the last clock in",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",
  
          });
        }
        else{
          console.log(error.error.message)
          Swal.fire({
            text: "Try In Another Time",
            confirmButtonText: "OK",
            confirmButtonColor: "#FF7519",

          });
        }
        }
      );
    }
  }

  formatDateToUTCForHr(dateString: string): string {
    const isoDateString: string = dateString.replace(' ', 'T');
    
    const date: Date = new Date(isoDateString);
    
    const year: string = date.getUTCFullYear().toString();
    const month: string = String(date.getUTCMonth() + 1).padStart(2, '0'); 
    const day: string = String(date.getUTCDate()).padStart(2, '0');
    const hours: string = String(date.getUTCHours()).padStart(2, '0');
    const minutes: string = String(date.getUTCMinutes()).padStart(2, '0');
    const seconds: string = String(date.getUTCSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  }


  sendLocationByHrForOthers(){
    const clockIn = this.DateClockInFromHrForOthers + " " + this.TimeClockInFromHrForOthers + ":00"
    const UTCTime = this.formatDateToUTCForHr(clockIn)
    console.log(this.selectedSite)
    if(this.userId){
      if(this.WorkHome==false){
        this.clockService.CreateClockInByHrForOther(this.userId, this.LocationClockInFromHrForOthers, UTCTime ,"site").subscribe(
          (response: any) => {
            this.IsClockedIn = true;
            this.dialogRef.close(this.IsClockedIn);
            this.clockEventService.notifyClockedIn();
          },
          (error: HttpErrorResponse) => {
            console.log(error.error.message)
            if (error.error.message.includes("You already have an existing clock-in without clocking out")){
              Swal.fire({
                text: "You Didn't clock out from the last clock in",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
      
              });
            } else{
              Swal.fire({
                text: "Try In Another Time",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
    
              });
            }
          }
        );
      }
      else{
        this.clockService.CreateClockInByHrForOther(this.userId, this.LocationClockInFromHrForOthers, UTCTime,this.selectedSite).subscribe(
          (response: any) => {
            this.IsClockedIn = true;
            this.dialogRef.close(this.IsClockedIn);
            this.clockEventService.notifyClockedIn();
          },
          (error: HttpErrorResponse) => {
            console.log(error.error.message)
            if (error.error.message.includes("You already have an existing clock-in without clocking out")){
              Swal.fire({
                text: "You Didn't clock out from the last clock in",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
      
              });
            } else{
              Swal.fire({
                text: "Try In Another Time",
                confirmButtonText: "OK",
                confirmButtonColor: "#FF7519",
    
              });
            }
          }
        );
      }
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
 
  getCurrentFormattedTime() {
    const timestamp = Math.floor(Date.now() / 1000); // Current timestamp in seconds
  
    this.TimeApi.getCurrentTimeGoogle(this.lat, this.lng).subscribe((data) => {
      if (data) {
        // Calculate local time
        const totalOffsetInSeconds = data.dstOffset + data.rawOffset;
        const localTimeInSeconds = timestamp + totalOffsetInSeconds;

        // Convert local time from seconds to milliseconds
        const localDate = new Date(localTimeInSeconds * 1000); 
        
        // Extract hours, minutes, and AM/PM
        const hours = localDate.getUTCHours();
        const minutes = localDate.getUTCMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = (hours % 12) || 12; // Convert 24-hour to 12-hour format
        const minutesStr = minutes < 10 ? '0' + minutes : minutes;
        this.formattedTime = `${formattedHours}:${minutesStr} ${ampm}`;

        // Format the date
        const monthNames = [
          'January', 'February', 'March', 'April', 'May', 'June',
          'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const month = monthNames[localDate.getUTCMonth()];
        const day = localDate.getUTCDate();
        const year = localDate.getUTCFullYear();
        this.formattedDate = `${month} ${day}, ${year}`;

       
      } else {
        console.error('Error fetching time zone data:');
        this.formattedTime = 'Error fetching time';
        this.formattedDate = 'Error fetching date';
      }
    });

  }
  
  getWorkTypes(){
    this.workType.getall().subscribe(
      (workTypes: any) => {
        this.sites = workTypes.workTypes
      }
    );
  }

  getLocationsFromServerByUserId(){
    if(this.userId){
      this.locationService.GetLocationsByUserId(this.userId).subscribe(
        (locations: any) => {
          this.Locations = locations.user_locations
        } 
      );
    }
  }

  toggleDropdown() {
    this.isDropdownOpen = !this.isDropdownOpen;
  }
}
